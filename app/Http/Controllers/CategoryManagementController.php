<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryManagementController extends Controller
{
    /**
     * Menampilkan seluruh kategori.
     */
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = Category::query()
            ->with('department')
            ->withCount('documents');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Department Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                $request->input('department_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            }

            if ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $categories = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        $statistics = [
            'total' => Category::count(),

            'active' => Category::where(
                'is_active',
                true
            )->count(),

            'inactive' => Category::where(
                'is_active',
                false
            )->count(),

            'used' => Category::has('documents')->count(),
        ];

        return view(
            'categories.index',
            compact(
                'categories',
                'departments',
                'statistics'
            )
        );
    }


    /**
     * Form tambah kategori.
     */
    public function create(): View
    {
        $this->ensureSuperAdmin();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'categories.create',
            compact('departments')
        );
    }


    /**
     * Simpan kategori.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $slug = Str::slug(
            (string) $request->input('name')
        );

        $request->merge([
            'slug' => $slug,
        ]);

        $validated = $request->validate([
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('is_active', true)
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique('categories', 'name')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'department_id',
                            $request->input('department_id')
                        )
                    ),
            ],

            'slug' => [
                'required',
                'string',
                'max:180',

                Rule::unique('categories', 'slug')
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'department_id',
                            $request->input('department_id')
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $category = Category::create([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'slug' =>
                $validated['slug'],

            'description' =>
                $validated['description'] ?? null,

            'is_active' =>
                true,
        ]);

        $this->logActivity(
            request: $request,
            category: $category,
            action: 'create_category',
            description:
                Auth::user()->name
                . ' created category "'
                . $category->name
                . '".'
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category successfully created.'
            );
    }


    /**
     * Form edit kategori.
     */
    public function edit(Category $category): View
    {
        $this->ensureSuperAdmin();

        $category->load('department');

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'categories.edit',
            compact(
                'category',
                'departments'
            )
        );
    }


    /**
     * Update kategori.
     */
    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $slug = Str::slug(
            (string) $request->input('name')
        );

        $request->merge([
            'slug' => $slug,
        ]);

        $validated = $request->validate([
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('is_active', true)
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'department_id',
                            $request->input('department_id')
                        )
                    ),
            ],

            'slug' => [
                'required',
                'string',
                'max:180',

                Rule::unique('categories', 'slug')
                    ->ignore($category->id)
                    ->where(
                        fn ($query) =>
                        $query->where(
                            'department_id',
                            $request->input('department_id')
                        )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $oldData = [
            'department_id' =>
                $category->department_id,

            'name' =>
                $category->name,

            'slug' =>
                $category->slug,
        ];

        $category->update([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'slug' =>
                $validated['slug'],

            'description' =>
                $validated['description'] ?? null,
        ]);

        $this->logActivity(
            request: $request,
            category: $category,
            action: 'update_category',
            description:
                Auth::user()->name
                . ' updated category "'
                . $category->name
                . '".',
            additionalMetadata: [
                'previous_data' => $oldData,
            ]
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Category successfully updated.'
            );
    }


    /**
     * Aktif / nonaktif kategori.
     */
    public function toggleStatus(
        Request $request,
        Category $category
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $newStatus = ! $category->is_active;

        $category->update([
            'is_active' => $newStatus,
        ]);

        $action = $newStatus
            ? 'activate_category'
            : 'deactivate_category';

        $statusText = $newStatus
            ? 'activated'
            : 'deactivated';

        $this->logActivity(
            request: $request,
            category: $category,
            action: $action,
            description:
                Auth::user()->name
                . ' '
                . $statusText
                . ' category "'
                . $category->name
                . '".'
        );

        return back()->with(
            'success',
            'Category "'
            . $category->name
            . '" successfully '
            . $statusText
            . '.'
        );
    }


    /**
     * Hanya Super Admin.
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
     * Catat activity category.
     */
    private function logActivity(
        Request $request,
        Category $category,
        string $action,
        string $description,
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
                    'target_category_id' =>
                        $category->id,

                    'target_category_name' =>
                        $category->name,

                    'target_category_slug' =>
                        $category->slug,

                    'department_id' =>
                        $category->department_id,

                    'category_status' =>
                        $category->is_active
                            ? 'active'
                            : 'inactive',
                ],
                $additionalMetadata
            ),
        ]);
    }
}