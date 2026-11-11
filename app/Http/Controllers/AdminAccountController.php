<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isSuperAdmin(),
            403
        );
    }

    private function authorizeTarget(User $user): void
    {
        abort_if(
            $user->isSuperAdmin() || $user->id === Auth::id(),
            403,
            'Akun Super Admin tidak boleh diubah oleh fitur ini.'
        );
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('users.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_id')],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => [
                'required', 'integer',
                Rule::exists('departments', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => [
                'required', 'string', 'confirmed',
                'max:'.max(64, (int) config('smartfind_security.auth.password_max_length', 128)),
                Password::defaults(),
            ],
        ]);

        $email = Str::lower(trim($validated['email']));

        DB::transaction(function () use ($request, $validated, $email): void {
            $user = User::create([
                'employee_id' => trim($validated['employee_id']),
                'name' => trim($validated['name']),
                'email' => $email,
                'department_id' => $validated['department_id'],
                'role' => $validated['role'],
                'account_status' => 'active',
                'password' => Hash::make($validated['password']),
                'must_change_password' => true,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'document_id' => null,
                'action' => 'create_user_by_super_admin',
                'description' => Auth::user()->name.' membuat akun '.$user->name.'.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'target_user_id' => $user->id,
                    'target_employee_id' => $user->employee_id,
                    'target_role' => $user->role,
                    'target_email' => $user->email,
                ],
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun berhasil dibuat. Berikan password sementara secara pribadi dan minta pengguna segera menggantinya.');
    }

    public function editPassword(User $user): View
    {
        $this->authorizeSuperAdmin();
        $this->authorizeTarget($user);

        return view('users.reset-password', compact('user'));
    }

    public function updatePassword(
        Request $request,
        User $user,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $this->authorizeSuperAdmin();
        $this->authorizeTarget($user);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required', 'string', 'confirmed',
                'max:'.max(64, (int) config('smartfind_security.auth.password_max_length', 128)),
                Password::defaults(),
            ],
        ]);

        DB::transaction(function () use ($request, $user, $validated, $sessionSecurity): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->authorizeTarget($target);

            $target->forceFill([
                'password' => Hash::make($validated['password']),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            $sessionSecurity->revokeDatabaseSessionsForUser($target);

            DB::table('password_reset_tokens')
                ->where('email', $target->email)
                ->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'document_id' => null,
                'action' => 'reset_user_password_by_super_admin',
                'description' => Auth::user()->name.' mengatur ulang password akun '.$target->name.'.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'target_user_id' => $target->id,
                    'target_role' => $target->role,
                    'target_email' => $target->email,
                ],
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Password sementara berhasil diatur. Seluruh sesi akun dicabut dan pengguna wajib mengganti password ketika login.');
    }
}
