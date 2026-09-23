<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan audit trail BMC SmartFind.
     */
    public function index(Request $request): View
    {
        abort_unless(
            Auth::check() && Auth::user()->isAdmin(),
            403
        );

        $dateToRules = [
            'nullable',
            'date',
        ];

        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'action' => [
                'nullable',
                'string',
                'max:100',
            ],
            'document_id' => [
                'nullable',
                'integer',
                'exists:documents,id',
            ],
            'ip_address' => [
                'nullable',
                'string',
                'max:45',
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_to' => $dateToRules,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = ActivityLog::query()
            ->with([
                'user',
                'document.category.department',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Global Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'action',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'ip_address',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'user',
                        function ($userQuery) use ($search) {
                            $userQuery
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
                                );
                        }
                    )
                    ->orWhereHas(
                        'document',
                        function ($documentQuery) use ($search) {
                            $documentQuery
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'document_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'part_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'file_name',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('user_id')) {
            $query->where(
                'user_id',
                $request->input('user_id')
            );
        }

        if ($request->filled('action')) {
            $query->where(
                'action',
                $request->input('action')
            );
        }

        if ($request->filled('document_id')) {
            $query->where(
                'document_id',
                $request->input('document_id')
            );
        }

        if ($request->filled('ip_address')) {
            $query->where(
                'ip_address',
                'like',
                '%' . trim((string) $request->input('ip_address')) . '%'
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('date_to')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Results
        |--------------------------------------------------------------------------
        */

        $logs = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        */

        $users = User::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);

        $documents = Document::query()
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'document_code',
            ]);

        $actions = ActivityLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $documentActions = [
            'upload_document',
            'view_document',
            'open_document',
            'download_document',
            'update_document',
            'archive_document',
            'permanent_delete_document',
            'restore_document',
            'add_favorite',
            'remove_favorite',
            'upload_document_revision',
            'open_document_revision',
            'download_document_revision',
        ];

        $statistics = [
            'total' => ActivityLog::count(),

            'today' => ActivityLog::whereDate(
                'created_at',
                today()
            )->count(),

            'document_activity' => ActivityLog::whereIn(
                'action',
                $documentActions
            )->count(),

            'user_management' => ActivityLog::whereIn(
                'action',
                [
                    'approve_user',
                    'reject_user',
                    'activate_user',
                    'deactivate_user',
                ]
            )->count(),
        ];

        return view(
            'activity-logs.index',
            compact(
                'logs',
                'users',
                'documents',
                'actions',
                'statistics'
            )
        );
    }
}
