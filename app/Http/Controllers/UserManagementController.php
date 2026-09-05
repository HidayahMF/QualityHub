<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Menampilkan seluruh user BMC SmartFind.
     */
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $query = User::with('department')
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

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($department) use ($search) {
                        $department->where('name', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'account_status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Role Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('role')) {
            $query->where(
                'role',
                $request->role
            );
        }

        $users = $query
            ->paginate(15)
            ->withQueryString();

        $statistics = [
            'total' => User::count(),

            'active' => User::where(
                'account_status',
                'active'
            )->count(),

            'pending' => User::where(
                'account_status',
                'pending'
            )->count(),

            'inactive' => User::whereIn(
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

        abort_unless(Auth::user()->isAdmin(), 403);

        abort_if(
            $user->account_status !== 'pending',
            422,
            'Only pending accounts can be approved.'
        );

        $user->update([
            'account_status' => 'active',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'approve_user',
            description:
                Auth::user()->name
                . ' approved account '
                . $user->name
                . '.'
        );

        return back()->with(
            'success',
            'Account '.$user->name.' successfully approved.'
        );
    }


    /**
     * Reject akun pending.
     */
    public function reject(
        Request $request,
        User $user
    ): RedirectResponse {

        abort_unless(Auth::user()->isAdmin(), 403);

        abort_if(
            $user->isSuperAdmin(),
            403,
            'Super Admin account cannot be rejected.'
        );

        abort_if(
            $user->account_status !== 'pending',
            422,
            'Only pending accounts can be rejected.'
        );

        $user->update([
            'account_status' => 'rejected',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'reject_user',
            description:
                Auth::user()->name
                . ' rejected account '
                . $user->name
                . '.'
        );

        return back()->with(
            'success',
            'Account '.$user->name.' has been rejected.'
        );
    }


    /**
     * Nonaktifkan user.
     */
    public function deactivate(
        Request $request,
        User $user
    ): RedirectResponse {

        abort_unless(Auth::user()->isAdmin(), 403);

        abort_if(
            $user->isSuperAdmin(),
            403,
            'Super Admin account cannot be disabled.'
        );

        abort_if(
            $user->id === Auth::id(),
            403,
            'You cannot disable your own account.'
        );

        $user->update([
            'account_status' => 'inactive',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'deactivate_user',
            description:
                Auth::user()->name
                . ' disabled account '
                . $user->name
                . '.'
        );

        return back()->with(
            'success',
            'Account '.$user->name.' has been disabled.'
        );
    }


    /**
     * Aktifkan kembali user.
     */
    public function activate(
        Request $request,
        User $user
    ): RedirectResponse {

        abort_unless(Auth::user()->isAdmin(), 403);

        abort_if(
            $user->account_status === 'pending',
            422,
            'Pending accounts must use the approval process.'
        );

        $user->update([
            'account_status' => 'active',
        ]);

        $this->logUserActivity(
            request: $request,
            targetUser: $user,
            action: 'activate_user',
            description:
                Auth::user()->name
                . ' activated account '
                . $user->name
                . '.'
        );

        return back()->with(
            'success',
            'Account '.$user->name.' is now active.'
        );
    }


    /**
     * Catat aktivitas administrasi user.
     */
    private function logUserActivity(
        Request $request,
        User $targetUser,
        string $action,
        string $description
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
                'target_status' => $targetUser->account_status,
            ],
        ]);
    }
}