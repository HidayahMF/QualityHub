<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\InputSanitizationService;
use App\Services\SessionSecurityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Menampilkan seluruh user BMC QualityHub.
     */
    public function index(
        Request $request,
        InputSanitizationService $input
    ): View {
        $this->ensureSuperAdmin();

        /*
        |--------------------------------------------------------------------------
        | Query Input Hardening
        |--------------------------------------------------------------------------
        |
        | Search dinormalisasi sebagai plain text. Status dan role hanya boleh
        | berasal dari whitelist backend, jadi manipulasi query string tidak dapat
        | memasukkan nilai arbitrary ke filter account management.
        |
        */
        $request->merge([
            'search' =>
                $input->search(
                    $request->input('search')
                ),
        ]);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:160',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'active',
                    'inactive',
                    'rejected',
                ]),
            ],
            'role' => [
                'nullable',
                Rule::in([
                    'super_admin',
                    'admin',
                    'user',
                ]),
            ],
        ]);

        $query = User::query()
            ->with('department')
            ->orderByRaw("
                CASE
                    WHEN account_status = 'pending' THEN 1
                    WHEN account_status = 'active' THEN 2
                    WHEN account_status = 'inactive' THEN 3
                    WHEN account_status = 'rejected' THEN 4
                    ELSE 5
                END
            ")
            ->latest('id');

        if (($validated['search'] ?? '') !== '') {
            $search = $input->escapeLike(
                (string) $validated['search']
            );

            $query->where(
                function (Builder $query) use ($search) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'employee_id',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'department',
                            function (Builder $department) use ($search) {
                                $department
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        if (! empty($validated['status'])) {
            $query->where(
                'account_status',
                $validated['status']
            );
        }

        if (! empty($validated['role'])) {
            $query->where(
                'role',
                $validated['role']
            );
        }

        $users = $query
            ->paginate(15)
            ->withQueryString();

        $statistics = [
            'total' =>
                User::count(),

            'active' =>
                User::where(
                    'account_status',
                    'active'
                )->count(),

            'pending' =>
                User::where(
                    'account_status',
                    'pending'
                )->count(),

            'inactive' =>
                User::whereIn(
                    'account_status',
                    ['inactive', 'rejected']
                )->count(),
        ];

        return view(
            'users.index',
            compact(
                'users',
                'statistics'
            )
        );
    }

    /**
     * Approve akun pending.
     */
    public function approve(
        Request $request,
        User $user
    ): RedirectResponse {
        $this->ensureSuperAdmin();
        $this->ensureTargetCanBeManaged($user);

        abort_if(
            $user->account_status !== 'pending',
            422,
            'Hanya akun pending yang dapat disetujui.'
        );

        $beforeStatus = $user->account_status;

        $user->update([
            'account_status' => 'active',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'approve_user',
            description:
                Auth::user()->name
                . ' menyetujui akun '
                . $user->name
                . '.',
            beforeStatus: $beforeStatus
        );

        return back()->with(
            'success',
            'Akun '.$user->name.' berhasil disetujui.'
        );
    }

    /**
     * Reject akun pending.
     */
    public function reject(
        Request $request,
        User $user,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $this->ensureSuperAdmin();
        $this->ensureTargetCanBeManaged($user);

        abort_if(
            $user->account_status !== 'pending',
            422,
            'Hanya akun pending yang dapat ditolak.'
        );

        $beforeStatus = $user->account_status;

        $user->forceFill([
            'account_status' => 'rejected',
            'remember_token' => Str::random(60),
        ])->save();

        // Revoke persisted sessions immediately, not only on the next request.
        $sessionSecurity->revokeDatabaseSessionsForUser($user);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'reject_user',
            description:
                Auth::user()->name
                . ' menolak akun '
                . $user->name
                . '.',
            beforeStatus: $beforeStatus
        );

        return back()->with(
            'success',
            'Akun '.$user->name.' telah ditolak.'
        );
    }

    /**
     * Nonaktifkan user aktif.
     */
    public function deactivate(
        Request $request,
        User $user,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $this->ensureSuperAdmin();
        $this->ensureTargetCanBeManaged($user);

        abort_if(
            $user->id === Auth::id(),
            403,
            'Anda tidak dapat menonaktifkan akun sendiri.'
        );

        abort_if(
            $user->account_status !== 'active',
            422,
            'Hanya akun aktif yang dapat dinonaktifkan.'
        );

        $beforeStatus = $user->account_status;

        $user->forceFill([
            'account_status' => 'inactive',
            'remember_token' => Str::random(60),
        ])->save();

        // Revoke persisted sessions immediately, not only on the next request.
        $sessionSecurity->revokeDatabaseSessionsForUser($user);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'deactivate_user',
            description:
                Auth::user()->name
                . ' menonaktifkan akun '
                . $user->name
                . '.',
            beforeStatus: $beforeStatus
        );

        return back()->with(
            'success',
            'Akun '.$user->name.' telah dinonaktifkan.'
        );
    }

    /**
     * Aktifkan kembali akun inactive / rejected.
     */
    public function activate(
        Request $request,
        User $user
    ): RedirectResponse {
        $this->ensureSuperAdmin();
        $this->ensureTargetCanBeManaged($user);

        abort_unless(
            in_array(
                $user->account_status,
                ['inactive', 'rejected'],
                true
            ),
            422,
            'Hanya akun inactive atau rejected yang dapat diaktifkan kembali.'
        );

        $beforeStatus = $user->account_status;

        $user->update([
            'account_status' => 'active',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'activate_user',
            description:
                Auth::user()->name
                . ' mengaktifkan akun '
                . $user->name
                . '.',
            beforeStatus: $beforeStatus
        );

        return back()->with(
            'success',
            'Akun '.$user->name.' sekarang aktif.'
        );
    }

    /**
     * Hapus permanen akun selain Super Admin tanpa membatasi status.
     * Dokumen, revisi, dan log QC dipertahankan lewat FK nullOnDelete;
     * favorit pribadi dibersihkan lewat FK cascadeOnDelete.
     */
public function destroy(
    Request $request,
    User $user,
    SessionSecurityService $sessionSecurity
): RedirectResponse {
    $this->ensureSuperAdmin();
    $this->ensureTargetCanBeManaged($user);

    abort_if(
        $user->id === Auth::id(),
        403,
        'Anda tidak dapat menghapus akun sendiri.'
    );

    // Never redirect delete results to a previous avatar/image URL.
    // A nested image request can replace Laravel's previous URL in session.
    try {
        $validated = $request->validate([
            'confirmation' => ['required', 'string', 'max:20'],
        ]);
    } catch (ValidationException $exception) {
        throw $exception->redirectTo(route('users.index'));
    }

    if (trim($validated['confirmation']) !== 'HAPUS') {
        throw ValidationException::withMessages([
            'confirmation' => 'Ketik HAPUS untuk mengonfirmasi penghapusan permanen.',
        ])->redirectTo(route('users.index'));
    }

    $deletedName = $user->name;
    $photoPath = null;
    $deletedUserId = (int) $user->id;

    try {
        DB::transaction(function () use ($request, $user, $sessionSecurity, &$photoPath) {
            $target = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $this->ensureTargetCanBeManaged($target);

            abort_if(
                $target->id === Auth::id(),
                403,
                'Anda tidak dapat menghapus akun sendiri.'
            );

            // Account deletion is allowed for every status. Foreign keys preserve
            // company documents, revisions and audit history by nulling the
            // deleted account's references; personal favorites are removed.
            // Super Admin accounts remain protected above.

            // Resolve avatar after commit only; never remove files in a
            // transaction that could be rolled back.
            $photoPath = $target->profile_photo_path;

            $sessionSecurity->revokeDatabaseSessionsForUser($target);

            DB::table('password_reset_tokens')
                ->where('email', $target->email)
                ->delete();

            // Audit mencatat Super Admin yang melakukan penghapusan.
            ActivityLog::create([
                'user_id' => Auth::id(),
                'document_id' => null,
                'action' => 'delete_user_permanently',
                'description' => Auth::user()->name
                    . ' menghapus permanen akun '
                    . $target->name . '.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'target_user_id' => $target->id,
                    'target_employee_id' => $target->employee_id,
                    'target_name' => $target->name,
                    'target_email' => $target->email,
                    'target_role' => $target->role,
                    'before_status' => $target->account_status,
                    'after_status' => 'deleted',
                ],
            ]);

            $target->delete();
        });
    } catch (QueryException $exception) {
        report($exception);

        return redirect()->route('users.index')->withErrors([
            'user' => 'Akun masih memiliki hubungan data lain sehingga tidak dapat dihapus. Hubungi admin IT.',
        ]);
    }

    // Only delete a private avatar belonging to this deleted user.
    // A failed file cleanup must not reverse a successful database deletion.
    if (
        is_string($photoPath)
        && preg_match(
            '~\\Aprofile-photos/'.preg_quote((string) $deletedUserId, '~')
                .'/[0-9a-f-]{36}\\.(jpg|png|webp)\\z~D',
            $photoPath
        ) === 1
    ) {
        try {
            $disk = Storage::disk('local');
            if ($disk->exists($photoPath) && ! $disk->delete($photoPath)) {
                return redirect()->route('users.index')
                    ->with('success', 'Akun '.$deletedName.' berhasil dihapus permanen.')
                    ->with('warning', 'Foto profil lama belum dapat dibersihkan. Hubungi IT.');
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('users.index')
                ->with('success', 'Akun '.$deletedName.' berhasil dihapus permanen.')
                ->with('warning', 'Foto profil lama belum dapat dibersihkan. Hubungi IT.');
        }
    }

    return redirect()->route('users.index')->with(
        'success',
        'Akun ' . $deletedName . ' berhasil dihapus permanen.'
    );
}

    private function ensureSuperAdmin(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isSuperAdmin(),
            403
        );
    }

    /**
     * UI sudah menandai Super Admin sebagai "Dilindungi".
     * Backend juga wajib memaksakan aturan yang sama karena tombol UI dapat
     * dilewati dengan request manual / DevTools.
     */
    private function ensureTargetCanBeManaged(User $user): void
    {
        abort_if(
            $user->isSuperAdmin(),
            403,
            'Akun Super Admin dilindungi dari perubahan status.'
        );
    }

    /**
     * Catat aktivitas administrasi user.
     */
    private function logUserActivity(
        Request $request,
        User $targetUser,
        string $action,
        string $description,
        string $beforeStatus
    ): void {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'document_id' => null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'target_user_id' => $targetUser->id,
                'target_employee_id' => $targetUser->employee_id,
                'target_name' => $targetUser->name,
                'target_email' => $targetUser->email,
                'before_status' => $beforeStatus,
                'after_status' => $targetUser->account_status,
            ],
        ]);
    }
}
