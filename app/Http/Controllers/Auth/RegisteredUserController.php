<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('departments'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:50',
                'unique:users,employee_id',
            ],

            'name' => [
                'required',
                'string',
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
                'exists:departments,id',
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = User::create([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'department_id' => $request->department_id,

            // User baru tidak langsung mendapatkan akses admin
            'role' => 'user',

            // Harus disetujui Admin terlebih dahulu
            'account_status' => 'pending',

            'password' => Hash::make($request->password),
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