@props([
    'categories',
    'departments' => collect(),
    'allowCreate' => false,
    'selectedCategoryId' => old('category_id'),
    'newCategoryName' => old('new_category_name'),
    'newCategoryDepartmentId' => old('new_category_department_id'),
    'label' => 'Kategori',
    'placeholder' => 'Cari atau pilih kategori',
    'id' => 'category_id',
])

@php
    $allowCreate = (bool) $allowCreate;
    $searchId = $id.'_search';
    $listboxId = $id.'_listbox';
    $helpId = $id.'_help';
    $errorId = $id.'_error';
    $newNameId = $id.'_new_name';
    $newDepartmentId = $id.'_new_department';
    $categoryError = $errors->first('category_id')
        ?: $errors->first('new_category_name')
        ?: $errors->first('new_category_department_id');
    $initialMode = $allowCreate && filled($newCategoryName)
        ? 'new'
        : (filled($selectedCategoryId) ? 'existing' : 'search');
@endphp

<div
    data-category-combobox
    data-category-mode="{{ $initialMode }}"
    data-allow-create="{{ $allowCreate ? 'true' : 'false' }}"
>
    <div data-category-native>
        <label
            for="{{ $id }}"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            {{ $label }}
            <span class="text-red-500">*</span>
        </label>

        <select
            id="{{ $id }}"
            name="category_id"
            required
            @class([
                'block w-full rounded-xl bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500',
                'border-red-300' => $categoryError,
                'border-slate-300' => ! $categoryError,
            ])
            @if ($categoryError) aria-invalid="true" @endif
        >
            <option value="">Pilih kategori</option>

            @foreach ($categories as $category)
                @php
                    $departmentName = $category->department?->name ?? '-';
                    $departmentCode = $category->department?->code;
                    $departmentLabel = filled($departmentCode)
                        ? $departmentName.' ('.$departmentCode.')'
                        : $departmentName;
                @endphp
                <option
                    value="{{ $category->id }}"
                    data-category-name="{{ strtolower($category->name) }}"
                    @selected((string) $selectedCategoryId === (string) $category->id)
                >
                    {{ $departmentLabel }} &mdash; {{ $category->name }}@unless($category->is_active) (Tidak Aktif)@endunless
                </option>
            @endforeach
        </select>
    </div>

    <div data-category-enhanced class="hidden">
        <label
            for="{{ $searchId }}"
            class="mb-2 block text-sm font-semibold text-slate-700"
        >
            {{ $label }}
            <span class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input
                id="{{ $searchId }}"
                type="search"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="{{ $listboxId }}"
                aria-expanded="false"
                aria-required="true"
                aria-describedby="{{ $helpId }}{{ $categoryError ? ' '.$errorId : '' }}"
                @if ($categoryError) aria-invalid="true" @endif
                autocomplete="off"
                placeholder="{{ $placeholder }}"
                data-category-search
                class="block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 pr-10 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
            >

            <svg
                aria-hidden="true"
                class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.007l3.26 3.261a.75.75 0 101.061-1.061l-3.261-3.26A6 6 0 009 3zm-4.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0z" clip-rule="evenodd" />
            </svg>

            <div
                id="{{ $listboxId }}"
                role="listbox"
                data-category-listbox
                class="absolute z-40 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
            >
                <span class="hidden bg-blue-50" aria-hidden="true"></span>

                @foreach ($categories as $category)
                    @php
                        $departmentName = $category->department?->name ?? '-';
                        $departmentCode = $category->department?->code;
                        $departmentLabel = filled($departmentCode)
                            ? $departmentName.' ('.$departmentCode.')'
                            : $departmentName;
                    @endphp
                    <button
                        id="{{ $id }}_option_{{ $category->id }}"
                        type="button"
                        role="option"
                        aria-selected="{{ (string) $selectedCategoryId === (string) $category->id ? 'true' : 'false' }}"
                        data-category-option
                        data-category-value="{{ $category->id }}"
                        data-category-name="{{ strtolower($category->name) }}"
                        data-category-search-text="{{ strtolower($departmentLabel.' '.$category->name) }}"
                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium text-slate-900">
                                {{ $category->name }}
                                @unless($category->is_active)
                                    <span class="text-amber-700">(Tidak Aktif)</span>
                                @endunless
                            </span>
                            <span class="block text-xs text-slate-500">{{ $departmentLabel }}</span>
                        </span>
                    </button>
                @endforeach

                @if ($allowCreate)
                    <button
                        id="{{ $id }}_create_option"
                        type="button"
                        role="option"
                        aria-selected="false"
                        data-category-create-option
                        class="hidden w-full rounded-lg border-t border-slate-100 px-3 py-2 text-left text-sm font-semibold text-blue-700 hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                    >
                        Buat kategori baru: <span data-category-create-label></span>
                    </button>
                @endif

                <p
                    data-category-empty
                    class="hidden px-3 py-3 text-sm text-slate-500"
                >
                    Kategori tidak ditemukan.
                </p>
            </div>
        </div>

        <p id="{{ $helpId }}" class="mt-1.5 text-xs text-slate-500">
            @if ($allowCreate)
                Ketik untuk mencari. Jika belum tersedia, pilih opsi membuat kategori baru lalu tentukan departemennya.
            @else
                Ketik untuk mencari, lalu pilih kategori dari daftar.
            @endif
        </p>

        <p data-category-status class="sr-only" aria-live="polite"></p>

        @if ($allowCreate)
            <div
                data-category-new-fields
                class="mt-4 hidden rounded-xl border border-blue-100 bg-blue-50/60 p-4"
            >
                <p class="mb-3 text-sm font-semibold text-blue-900">
                    Kategori Baru
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="{{ $newNameId }}"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Nama Kategori <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="{{ $newNameId }}"
                            name="new_category_name"
                            value="{{ $newCategoryName }}"
                            maxlength="150"
                            autocomplete="off"
                            data-category-new-name
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label
                            for="{{ $newDepartmentId }}"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Departemen <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="{{ $newDepartmentId }}"
                            name="new_category_department_id"
                            data-category-new-department
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Pilih departemen</option>
                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    @selected((string) $newCategoryDepartmentId === (string) $department->id)
                                >
                                    {{ $department->name }}@if(filled($department->code)) ({{ $department->code }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="mt-2 text-xs text-blue-800">
                    Kategori baru akan dibuat di departemen yang dipilih.
                </p>
            </div>
        @endif

        @if ($categoryError)
            <p id="{{ $errorId }}" class="mt-2 text-sm text-red-600">
                {{ $categoryError }}
            </p>
        @endif
    </div>
</div>
