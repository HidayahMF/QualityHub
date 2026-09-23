<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
     * Nonaktifkan kategori.
     */
    public function deactivate(
        Request $request,
        Category $category
    ): RedirectResponse {

        return $this->setStatus(
            request: $request,
            category: $category,
            isActive: false
        );
    }


    /**
     * Aktifkan kembali kategori.
     */
    public function activate(
        Request $request,
        Category $category
    ): RedirectResponse {

        return $this->setStatus(
            request: $request,
            category: $category,
            isActive: true
        );
    }


    /**
     * Hapus permanen kategori yang belum digunakan.
     */
    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $categoryName = DB::transaction(
            function () use ($request, $category): string {
                $lockedCategory = Category::query()
                    ->with('department')
                    ->lockForUpdate()
                    ->findOrFail($category->getKey());

                $documentCount = $lockedCategory
                    ->documents()
                    ->count();

                if ($documentCount > 0) {
                    throw ValidationException::withMessages([
                        'category' =>
                            'Kategori "'
                            . $lockedCategory->name
                            . '" digunakan oleh '
                            . $documentCount
                            . ' dokumen dan tidak dapat dihapus permanen.',
                    ]);
                }

                $this->logActivity(
                    request: $request,
                    category: $lockedCategory,
                    action: 'delete_category',
                    description:
                        Auth::user()->name
                        . ' deleted category "'
                        . $lockedCategory->name
                        . '".',
                    additionalMetadata: [
                        'document_count' => $documentCount,
                    ]
                );

                $categoryName = $lockedCategory->name;

                $lockedCategory->delete();

                return $categoryName;
            }
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Kategori "'
                . $categoryName
                . '" berhasil dihapus permanen.'
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
     * Ubah status kategori secara eksplisit dan atomik.
     */
    private function setStatus(
        Request $request,
        Category $category,
        bool $isActive
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $categoryName = DB::transaction(
            function () use ($request, $category, $isActive): string {
                $lockedCategory = Category::query()
                    ->with('department')
                    ->lockForUpdate()
                    ->findOrFail($category->getKey());

                $documentCount = $lockedCategory
                    ->documents()
                    ->count();

                if (
                    $isActive
                    && ! $lockedCategory->department?->is_active
                ) {
                    throw ValidationException::withMessages([
                        'category' =>
                            'Kategori tidak dapat diaktifkan karena departemennya tidak aktif.',
                    ]);
                }

                if ((bool) $lockedCategory->is_active === $isActive) {
                    return $lockedCategory->name;
                }

                $lockedCategory->update([
                    'is_active' => $isActive,
                ]);

                $action = $isActive
                    ? 'activate_category'
                    : 'deactivate_category';

                $statusText = $isActive
                    ? 'activated'
                    : 'deactivated';

                $this->logActivity(
                    request: $request,
                    category: $lockedCategory,
                    action: $action,
                    description:
                        Auth::user()->name
                        . ' '
                        . $statusText
                        . ' category "'
                        . $lockedCategory->name
                        . '".',
                    additionalMetadata: [
                        'document_count' => $documentCount,
                    ]
                );

                return $lockedCategory->name;
            }
        );

        $statusText = $isActive
            ? 'activated'
            : 'deactivated';

        return back()->with(
            'success',
            'Category "'
            . $categoryName
            . '" successfully '
            . $statusText
            . '.'
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

        $category->loadMissing('department:id,name');

        $actor = Auth::user();

        $documentCount = array_key_exists(
            'document_count',
            $additionalMetadata
        )
            ? (int) $additionalMetadata['document_count']
            : $category->documents()->count();

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

                    'category_id' =>
                        $category->id,

                    'target_category_name' =>
                        $category->name,

                    'category_name' =>
                        $category->name,

                    'target_category_slug' =>
                        $category->slug,

                    'department_id' =>
                        $category->department_id,

                    'department_name' =>
                        $category->department?->name,

                    'actor_user_id' =>
                        $actor?->id,

                    'actor_user_name' =>
                        $actor?->name,

                    'document_count' =>
                        $documentCount,

                    'timestamp' =>
                        now()->toIso8601String(),

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
