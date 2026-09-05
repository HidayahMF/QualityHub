<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentManagementController extends Controller
{
    /**
     * Menampilkan daftar departemen.
     */
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $query = Department::query()
            ->withCount([
                'categories',
                'users',
            ]);

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($departmentQuery) use ($search) {
                $departmentQuery
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status') === 'active'
            );
        }

        $departments = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $statistics = [
            'total' =>
                Department::count(),

            'active' =>
                Department::where(
                    'is_active',
                    true
                )->count(),

            'inactive' =>
                Department::where(
                    'is_active',
                    false
                )->count(),
        ];

        return view(
            'departments.index',
            compact(
                'departments',
                'statistics'
            )
        );
    }

    /**
     * Menampilkan formulir tambah departemen.
     */
    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('departments.create');
    }

    /**
     * Menyimpan departemen baru.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'code' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[A-Za-z0-9_-]+$/',
                    'unique:departments,code',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ],
            [
                'name.required' =>
                    'Nama departemen wajib diisi.',

                'name.max' =>
                    'Nama departemen maksimal 255 karakter.',

                'code.required' =>
                    'Kode departemen wajib diisi.',

                'code.max' =>
                    'Kode departemen maksimal 20 karakter.',

                'code.regex' =>
                    'Kode departemen hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',

                'code.unique' =>
                    'Kode departemen sudah digunakan.',

                'description.max' =>
                    'Deskripsi maksimal 2000 karakter.',
            ]
        );

        $department = Department::create([
            'name' =>
                trim($validated['name']),

            'code' =>
                strtoupper(
                    trim($validated['code'])
                ),

            'description' =>
                isset($validated['description'])
                && trim($validated['description']) !== ''
                    ? trim($validated['description'])
                    : null,

            'is_active' =>
                true,
        ]);

        $this->logActivity(
            request: $request,
            action: 'create_department',
            description:
                Auth::user()->name
                . ' membuat departemen "'
                . $department->name
                . '".',
            department: $department
        );

        return redirect()
            ->route('departments.index')
            ->with(
                'success',
                'Departemen berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan formulir edit departemen.
     */
    public function edit(
        Department $department
    ): View {

        $this->ensureSuperAdmin();

        $department->loadCount([
            'categories',
            'users',
        ]);

        return view(
            'departments.edit',
            compact('department')
        );
    }

    /**
     * Memperbarui data departemen.
     */
    public function update(
        Request $request,
        Department $department
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'code' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[A-Za-z0-9_-]+$/',

                    Rule::unique(
                        'departments',
                        'code'
                    )->ignore(
                        $department->id
                    ),
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ],
            [
                'name.required' =>
                    'Nama departemen wajib diisi.',

                'name.max' =>
                    'Nama departemen maksimal 255 karakter.',

                'code.required' =>
                    'Kode departemen wajib diisi.',

                'code.max' =>
                    'Kode departemen maksimal 20 karakter.',

                'code.regex' =>
                    'Kode departemen hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',

                'code.unique' =>
                    'Kode departemen sudah digunakan.',

                'description.max' =>
                    'Deskripsi maksimal 2000 karakter.',
            ]
        );

        $before = [
            'name' =>
                $department->name,

            'code' =>
                $department->code,

            'description' =>
                $department->description,

            'is_active' =>
                (bool) $department->is_active,
        ];

        $department->update([
            'name' =>
                trim($validated['name']),

            'code' =>
                strtoupper(
                    trim($validated['code'])
                ),

            'description' =>
                isset($validated['description'])
                && trim($validated['description']) !== ''
                    ? trim($validated['description'])
                    : null,
        ]);

        $department->refresh();

        $this->logActivity(
            request: $request,
            action: 'update_department',
            description:
                Auth::user()->name
                . ' memperbarui departemen "'
                . $department->name
                . '".',
            department: $department,
            additionalMetadata: [
                'before' =>
                    $before,

                'after' => [
                    'name' =>
                        $department->name,

                    'code' =>
                        $department->code,

                    'description' =>
                        $department->description,

                    'is_active' =>
                        (bool) $department->is_active,
                ],
            ]
        );

        return redirect()
            ->route('departments.index')
            ->with(
                'success',
                'Departemen berhasil diperbarui.'
            );
    }

    /**
     * Mengaktifkan atau menonaktifkan departemen.
     *
     * Departemen tidak dapat dinonaktifkan apabila masih memiliki
     * kategori aktif atau pengguna aktif.
     */
    public function toggleStatus(
        Request $request,
        Department $department
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $newStatus =
            ! (bool) $department->is_active;

        if (! $newStatus) {
            $hasActiveCategories =
                $department
                    ->categories()
                    ->where(
                        'is_active',
                        true
                    )
                    ->exists();

            if ($hasActiveCategories) {
                return back()->withErrors([
                    'department' =>
                        'Departemen tidak dapat dinonaktifkan karena masih memiliki kategori aktif. Nonaktifkan kategori terkait terlebih dahulu.',
                ]);
            }

            $hasActiveUsers =
                $department
                    ->users()
                    ->where(
                        'account_status',
                        'active'
                    )
                    ->exists();

            if ($hasActiveUsers) {
                return back()->withErrors([
                    'department' =>
                        'Departemen tidak dapat dinonaktifkan karena masih memiliki pengguna aktif.',
                ]);
            }
        }

        $department->update([
            'is_active' =>
                $newStatus,
        ]);

        $department->refresh();

        $action =
            $department->is_active
                ? 'activate_department'
                : 'deactivate_department';

        $description =
            $department->is_active
                ? Auth::user()->name
                    . ' mengaktifkan departemen "'
                    . $department->name
                    . '".'
                : Auth::user()->name
                    . ' menonaktifkan departemen "'
                    . $department->name
                    . '".';

        $this->logActivity(
            request: $request,
            action: $action,
            description: $description,
            department: $department
        );

        return back()->with(
            'success',
            $department->is_active
                ? 'Departemen berhasil diaktifkan.'
                : 'Departemen berhasil dinonaktifkan.'
        );
    }

    /**
     * Authorization khusus Super Admin.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isSuperAdmin(),
            403
        );
    }

    /**
     * Mencatat aktivitas Department Management.
     */
    private function logActivity(
        Request $request,
        string $action,
        string $description,
        Department $department,
        array $additionalMetadata = []
    ): void {

        ActivityLog::create([
            'user_id' =>
                Auth::id(),

            'document_id' =>
                null,

            'action' =>
                $action,

            'description' =>
                $description,

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'metadata' => array_merge(
                [
                    'target_department_id' =>
                        $department->id,

                    'target_department_name' =>
                        $department->name,

                    'target_department_code' =>
                        $department->code,

                    'department_status' =>
                        $department->is_active
                            ? 'active'
                            : 'inactive',
                ],
                $additionalMetadata
            ),
        ]);
    }
}
