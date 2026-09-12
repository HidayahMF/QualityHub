<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\InputSanitizationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'auth.register',
            compact('departments')
        );
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(
        Request $request,
        InputSanitizationService $input
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Server-side Normalization
        |--------------------------------------------------------------------------
        |
        | Role dan account_status TIDAK pernah diambil dari request user.
        | Walaupun field ditambahkan lewat DevTools, backend tetap menetapkan
        | role=user dan account_status=pending.
        |
        */
        $request->merge([
            'employee_id' =>
                $input->singleLine(
                    $request->input('employee_id')
                ),

            'name' =>
                $input->singleLine(
                    $request->input('name')
                ),

            'email' =>
                $input->email(
                    $request->input('email')
                ),
        ]);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:50',
                'unique:users,employee_id',
            ],

            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'department_id' => [
                'required',
                'integer',
                Rule::exists(
                    'departments',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'password' => [
                'required',
                'string',
                'max:'.max(64, (int) config('smartfind_security.auth.password_max_length', 128)),
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = User::create([
            'employee_id' =>
                $validated['employee_id'],

            'name' =>
                $validated['name'],

            'email' =>
                $validated['email'],

            'department_id' =>
                $validated['department_id'],

            // Selalu ditentukan backend, tidak dapat dioverride request.
            'role' => 'user',
            'account_status' => 'pending',

            'password' =>
                Hash::make(
                    $validated['password']
                ),
        ]);

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Pendaftaran berhasil. Akun Anda sedang menunggu persetujuan administrator.'
            );
    }
}
