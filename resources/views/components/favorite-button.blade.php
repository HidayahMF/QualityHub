@props([
    'document',
    'favorited' => false,
    'compact' => false,
])

@if($favorited)

    <form
        method="POST"
        action="{{ route(
            'favorites.destroy',
            $document
        ) }}"
    >
        @csrf
        @method('DELETE')

        <button
            type="submit"
            title="Hapus dari Favorit"
            @class([
                'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition',
                'px-3 py-2 text-xs' => $compact,
                'px-4 py-2.5 text-sm' => ! $compact,
                'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100',
            ])
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="{{ $compact ? 16 : 18 }}"
                height="{{ $compact ? 16 : 18 }}"
                viewBox="0 0 24 24"
                fill="currentColor"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="m12 17.27
                       5.18 3.04
                       -1.4-5.89
                       4.58-3.96
                       -6.03-.52
                       L12 4.38
                       9.67 9.94
                       l-6.03.52
                       4.58 3.96
                       -1.4 5.89Z"
                />
            </svg>

            @unless($compact)
                Tersimpan
            @endunless
        </button>
    </form>

@else

    <form
        method="POST"
        action="{{ route(
            'favorites.store',
            $document
        ) }}"
    >
        @csrf

        <button
            type="submit"
            title="Tambahkan ke Favorit"
            @class([
                'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition',
                'px-3 py-2 text-xs' => $compact,
                'px-4 py-2.5 text-sm' => ! $compact,
                'bg-white text-slate-600 border border-slate-200 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700',
            ])
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="{{ $compact ? 16 : 18 }}"
                height="{{ $compact ? 16 : 18 }}"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="m12 17.27
                       5.18 3.04
                       -1.4-5.89
                       4.58-3.96
                       -6.03-.52
                       L12 4.38
                       9.67 9.94
                       l-6.03.52
                       4.58 3.96
                       -1.4 5.89Z"
                />
            </svg>

            @unless($compact)
                Tambahkan ke Favorit
            @endunless
        </button>
    </form>

@endif
