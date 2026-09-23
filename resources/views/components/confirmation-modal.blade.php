@props([
    'name',
    'action',
    'method' => 'PATCH',
    'title',
    'description',
    'secondaryDescription' => null,
    'eyebrow' => 'BMC QualityHub',
    'confirmLabel' => 'Lanjutkan',
    'cancelLabel' => 'Batal',
    'variant' => 'primary',
    'typedConfirmation' => false,
    'typedConfirmLabel' => 'HAPUS PERMANEN',
])

@php
    $styles = [
        'primary' => [
            'icon' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'button' => 'bg-blue-800 hover:bg-blue-900 focus:ring-blue-500 shadow-blue-900/15',
        ],
        'warning' => [
            'icon' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'button' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 shadow-amber-900/15',
        ],
        'danger' => [
            'icon' => 'bg-red-50 text-red-700 ring-red-100',
            'button' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500 shadow-red-900/15',
        ],
        'success' => [
            'icon' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'button' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 shadow-emerald-900/15',
        ],
    ];

    $style = $styles[$variant] ?? $styles['primary'];
    $titleId = 'confirmation-' . $name . '-title';
    $descriptionId = 'confirmation-' . $name . '-description';
@endphp

<x-modal
    :name="$name"
    maxWidth="md"
    containerClass="flex items-center justify-center sm:px-4"
    panelClass="relative rounded-3xl border border-blue-100 bg-white shadow-2xl shadow-blue-950/20"
    backdropClass="bg-slate-950/45 backdrop-blur-[2px]"
    :labelledBy="$titleId"
    :describedBy="$descriptionId"
    focusable
>
    <div class="h-1.5 w-full bg-blue-800"></div>

    <form
        method="POST"
        action="{{ $action }}"
        class="p-6 sm:p-7"
        @if($typedConfirmation)
            data-qh-typed-modal-name="{{ $name }}"
            x-data="qualityHubTypedConfirmation"
            x-on:open-modal.window="resetOnOpen"
            x-on:submit="submit"
        @endif
    >
        @csrf
        @if(strtoupper($method) !== 'POST')
            @method($method)
        @endif

        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl ring-1 {{ $style['icon'] }}">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                    <path d="M9 12l2 2 4-4" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">
                    {{ $eyebrow }}
                </p>

                <h2 id="{{ $titleId }}" class="mt-1.5 text-xl font-bold text-slate-900">
                    @if($typedConfirmation)
                        <span x-show="firstStep">{{ $title }}</span>
                        <span x-show="secondStep" x-cloak>Konfirmasi Penghapusan Permanen</span>
                    @else
                        {{ $title }}
                    @endif
                </h2>

                <div id="{{ $descriptionId }}" class="mt-2 space-y-3 text-sm leading-6 text-slate-600">
                    <div @if($typedConfirmation) x-show="firstStep" @endif>
                        <p>{{ $description }}</p>
                        @if($secondaryDescription)
                            <p class="mt-3">{{ $secondaryDescription }}</p>
                        @endif
                        @if($slot->isNotEmpty())
                            {{ $slot }}
                        @endif
                    </div>

                    @if($typedConfirmation)
                        <div x-show="secondStep" x-cloak>
                            <p>Untuk memastikan penghapusan dilakukan dengan sengaja, ketik HAPUS pada kolom di bawah.</p>
                            <label for="{{ $name }}-confirmation" class="mt-4 block font-semibold text-slate-700">
                                Ketik HAPUS
                            </label>
                            <input
                                id="{{ $name }}-confirmation"
                                x-ref="confirmationInput"
                                x-model="confirmation"
                                type="text"
                                name="confirmation"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="HAPUS"
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm text-slate-900 focus:border-red-500 focus:ring-red-500"
                            >
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="button"
                x-on:click="$dispatch('close')"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
            >
                {{ $cancelLabel }}
            </button>

            @if($typedConfirmation)
                <button
                    type="button"
                    x-show="firstStep"
                    x-on:click="advance"
                    class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $style['button'] }}"
                >
                    {{ $confirmLabel }}
                </button>

                <button
                    type="submit"
                    x-show="secondStep"
                    x-bind:disabled="cannotSubmit"
                    x-cloak
                    disabled
                    class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 {{ $style['button'] }}"
                >
                    {{ $typedConfirmLabel }}
                </button>
            @else
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $style['button'] }}"
                >
                    {{ $confirmLabel }}
                </button>
            @endif
        </div>
    </form>
</x-modal>
