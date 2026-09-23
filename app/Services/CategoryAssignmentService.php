<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryAssignmentService
{
    public function __construct(
        private readonly InputSanitizationService $input
    ) {}

    public function prepareRequest(Request $request): void
    {
        $attemptsInlineCreation =
            $request->filled('new_category_name')
            || $request->filled('new_category_department_id');

        if ($attemptsInlineCreation) {
            abort_unless(
                $request->user()?->isSuperAdmin(),
                403
            );
        }

        $request->merge([
            'new_category_name' => $this->input->singleLine(
                $request->input('new_category_name')
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(Request $request): array
    {
        $hasCategory = $request->filled('category_id');
        $hasNewCategory = $request->filled('new_category_name');

        return [
            'category_id' => [
                'nullable',
                Rule::requiredIf(! $hasNewCategory),
                Rule::prohibitedIf($hasNewCategory),
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'new_category_name' => [
                'nullable',
                Rule::requiredIf(! $hasCategory),
                Rule::prohibitedIf($hasCategory),
                'string',
                'max:150',
            ],
            'new_category_department_id' => [
                'nullable',
                Rule::requiredIf($hasNewCategory),
                Rule::prohibitedIf(! $hasNewCategory),
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
        ];
    }

    /**
     * Menyiapkan category dan path tanpa menulis database. Category baru
     * benar-benar dibuat di dalam transaksi dokumen melalui resolve().
     *
     * @param  array<string, mixed>  $validated
     * @return array{
     *     category_id: int|null,
     *     department_id: int,
     *     department_name: string,
     *     name: string,
     *     slug: string,
     *     create: bool
     * }
     */
    public function prepare(array $validated): array
    {
        if (($validated['category_id'] ?? null) !== null) {
            $category = $this->activeCategoryQuery()
                ->find($validated['category_id']);

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori atau departemen yang dipilih sudah tidak aktif.',
                ]);
            }

            return $this->selectionFromCategory($category);
        }

        $department = Department::query()
            ->where('is_active', true)
            ->find($validated['new_category_department_id'] ?? null);

        if (! $department) {
            throw ValidationException::withMessages([
                'new_category_department_id' => 'Departemen yang dipilih sudah tidak aktif.',
            ]);
        }

        $name = (string) ($validated['new_category_name'] ?? '');
        $slug = Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'new_category_name' => 'Nama kategori harus menghasilkan slug yang valid.',
            ]);
        }

        $categories = Category::query()
            ->where('department_id', $department->id)
            ->get();

        $sameName = $categories->first(
            fn (Category $category) =>
                $this->normalizedName($category->name)
                === $this->normalizedName($name)
        );

        if ($sameName) {
            if (! $sameName->is_active) {
                throw ValidationException::withMessages([
                    'new_category_name' => 'Kategori dengan nama tersebut sedang tidak aktif.',
                ]);
            }

            $sameName->setRelation('department', $department);

            return $this->selectionFromCategory($sameName);
        }

        if ($categories->contains('slug', $slug)) {
            throw ValidationException::withMessages([
                'new_category_name' => 'Slug kategori sudah digunakan oleh nama lain pada departemen ini.',
            ]);
        }

        return [
            'category_id' => null,
            'department_id' => (int) $department->id,
            'department_name' => (string) $department->name,
            'name' => $name,
            'slug' => $slug,
            'create' => true,
        ];
    }

    /**
     * @param  array{
     *     category_id: int|null,
     *     department_id: int,
     *     department_name: string,
     *     name: string,
     *     slug: string,
     *     create: bool
     * }  $selection
     */
    public function resolve(
        Request $request,
        array $selection,
        string $source
    ): Category {
        if (! $selection['create']) {
            $category = $this->activeCategoryQuery()
                ->lockForUpdate()
                ->find($selection['category_id']);

            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori atau departemen yang dipilih sudah tidak aktif.',
                ]);
            }

            return $category;
        }

        $department = Department::query()
            ->where('is_active', true)
            ->lockForUpdate()
            ->find($selection['department_id']);

        if (! $department) {
            throw ValidationException::withMessages([
                'new_category_department_id' => 'Departemen yang dipilih sudah tidak aktif.',
            ]);
        }

        $categories = Category::query()
            ->where('department_id', $department->id)
            ->lockForUpdate()
            ->get();

        $sameName = $categories->first(
            fn (Category $category) =>
                $this->normalizedName($category->name)
                === $this->normalizedName($selection['name'])
        );

        if ($sameName) {
            if (! $sameName->is_active) {
                throw ValidationException::withMessages([
                    'new_category_name' => 'Kategori dengan nama tersebut sedang tidak aktif.',
                ]);
            }

            $sameName->setRelation('department', $department);

            return $sameName;
        }

        if ($categories->contains('slug', $selection['slug'])) {
            throw ValidationException::withMessages([
                'new_category_name' => 'Slug kategori sudah digunakan oleh nama lain pada departemen ini.',
            ]);
        }

        $category = Category::query()->firstOrCreate(
            [
                'department_id' => $department->id,
                'slug' => $selection['slug'],
            ],
            [
                'name' => $selection['name'],
                'description' => null,
                'is_active' => true,
            ]
        );
        $category->setRelation('department', $department);

        if (! $category->wasRecentlyCreated) {
            if (
                $this->normalizedName($category->name)
                !== $this->normalizedName($selection['name'])
            ) {
                throw ValidationException::withMessages([
                    'new_category_name' => 'Slug kategori sudah digunakan oleh nama lain pada departemen ini.',
                ]);
            }

            if (! $category->is_active) {
                throw ValidationException::withMessages([
                    'new_category_name' => 'Kategori dengan nama tersebut sedang tidak aktif.',
                ]);
            }

            return $category;
        }

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'document_id' => null,
            'action' => 'create_category',
            'description' => $request->user()?->name
                . ' created category "'
                . $category->name
                . '".',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'target_category_id' => $category->id,
                'target_category_name' => $category->name,
                'target_category_slug' => $category->slug,
                'department_id' => $category->department_id,
                'category_status' => 'active',
                'creation_source' => $source,
            ],
        ]);

        return $category;
    }

    private function activeCategoryQuery(): Builder
    {
        return Category::query()
            ->with('department')
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn (Builder $query) => $query->where('is_active', true)
            );
    }

    /**
     * @return array{
     *     category_id: int,
     *     department_id: int,
     *     department_name: string,
     *     name: string,
     *     slug: string,
     *     create: false
     * }
     */
    private function selectionFromCategory(Category $category): array
    {
        return [
            'category_id' => (int) $category->id,
            'department_id' => (int) $category->department_id,
            'department_name' => (string) $category->department->name,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'create' => false,
        ];
    }

    private function normalizedName(string $name): string
    {
        return Str::lower(
            trim(preg_replace('/\s+/u', ' ', $name) ?? $name)
        );
    }
}
