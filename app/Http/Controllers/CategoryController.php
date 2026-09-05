<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar kategori dokumen.
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
            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $query = Category::query()
            ->with('department')
            ->withCount('documents');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'slug',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'department',
                        function (Builder $departmentQuery) use ($search) {
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
                                );
                        }
                    );
            });
        }

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                $request->input('department_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status') === 'active'
            );
        }

        $categories = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        $statistics = [
            'total' =>
                Category::count(),

            'active' =>
                Category::where(
                    'is_active',
                    true
                )->count(),

            'inactive' =>
                Category::where(
                    'is_active',
                    false
                )->count(),

            'used' =>
                Category::whereHas('documents')->count(),
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
     * Menampilkan form tambah kategori.
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
     * Menyimpan kategori baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'categories',
                    'name'
                )->where(
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
                'max:2000',
            ],
        ]);

        $slug = $this->makeUniqueSlug(
            name: $validated['name'],
            departmentId: (int) $validated['department_id']
        );

        Category::create([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'slug' =>
                $slug,

            'description' =>
                $validated['description']
                ?? null,

            'is_active' =>
                true,
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Kategori berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan form edit kategori.
     */
    public function edit(Category $category): View
    {
        $this->ensureSuperAdmin();

        $departments = Department::query()
            ->where(function (Builder $query) use ($category) {
                $query
                    ->where(
                        'is_active',
                        true
                    )
                    ->orWhereKey(
                        $category->department_id
                    );
            })
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
     * Memperbarui kategori.
     */
    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'categories',
                    'name'
                )
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
                'max:2000',
            ],
        ]);

        $selectedDepartment = Department::findOrFail(
            $validated['department_id']
        );

        $departmentChanged =
            (int) $selectedDepartment->id
            !== (int) $category->department_id;

        if (
            $departmentChanged
            && ! $selectedDepartment->is_active
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'department_id' =>
                        'Kategori hanya dapat dipindahkan ke departemen yang aktif.',
                ]);
        }

        $slug = $this->makeUniqueSlug(
            name: $validated['name'],
            departmentId: (int) $validated['department_id'],
            ignoreCategoryId: (int) $category->id
        );

        $category->update([
            'department_id' =>
                $validated['department_id'],

            'name' =>
                $validated['name'],

            'slug' =>
                $slug,

            'description' =>
                $validated['description']
                ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Kategori berhasil diperbarui.'
            );
    }

    /**
     * Mengaktifkan atau menonaktifkan kategori.
     */
    public function toggleStatus(
        Category $category
    ): RedirectResponse {

        $this->ensureSuperAdmin();

        $category->load('department');

        if ($category->is_active) {
            $category->update([
                'is_active' => false,
            ]);

            return back()->with(
                'success',
                'Kategori berhasil dinonaktifkan. Dokumen yang sudah ada tetap tersimpan.'
            );
        }

        if (
            ! $category->department
            || ! $category->department->is_active
        ) {
            return back()->withErrors([
                'category' =>
                    'Kategori tidak dapat diaktifkan karena departemennya tidak aktif.',
            ]);
        }

        $category->update([
            'is_active' => true,
        ]);

        return back()->with(
            'success',
            'Kategori berhasil diaktifkan.'
        );
    }

    /**
     * Membuat slug unik di dalam satu departemen.
     */
    private function makeUniqueSlug(
        string $name,
        int $departmentId,
        ?int $ignoreCategoryId = null
    ): string {

        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'kategori';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            Category::query()
                ->where(
                    'department_id',
                    $departmentId
                )
                ->where(
                    'slug',
                    $slug
                )
                ->when(
                    $ignoreCategoryId !== null,
                    fn (Builder $query) =>
                    $query->where(
                        'id',
                        '!=',
                        $ignoreCategoryId
                    )
                )
                ->exists()
        ) {
            $slug =
                $baseSlug
                . '-'
                . $counter;

            $counter++;
        }

        return $slug;
    }

    /**
     * Categories Management hanya untuk Super Admin.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isSuperAdmin(),
            403
        );
    }
}
