@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'containerClass' => '',
    'panelClass' => 'bg-white rounded-lg shadow-xl',
    'backdropClass' => 'bg-gray-500 opacity-75',
    'labelledBy' => null,
    'describedBy' => null,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    data-qh-modal-name="{{ $name }}"
    data-qh-modal-show="{{ $show ? 'true' : 'false' }}"
    data-qh-modal-focusable="{{ $attributes->has('focusable') ? 'true' : 'false' }}"
    x-data="qualityHubModal"
    x-on:open-modal.window="open"
    x-on:close-modal.window="closeRequested"
    x-on:close.stop="close"
    x-on:keydown.escape.window="close"
    x-on:keydown.tab.prevent="handleTab"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 {{ $containerClass }}"
    role="dialog"
    aria-modal="true"
    @if($labelledBy)
        aria-labelledby="{{ $labelledBy }}"
    @endif
    @if($describedBy)
        aria-describedby="{{ $describedBy }}"
    @endif
    x-cloak
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="close"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 {{ $backdropClass }}"></div>
    </div>

    <div
        x-show="show"
        class="mb-6 overflow-hidden transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto {{ $panelClass }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
