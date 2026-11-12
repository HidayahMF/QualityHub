<x-app-layout>
    {{-- BMC QualityHub · Activity Log · compact enterprise blue-white UI.
         Existing GET filters, audits, metadata and permissions stay intact. --}}
    @php
        $actionLabels = [
                        'activate_category' => 'Aktifkan Kategori',
                        'activate_department' => 'Aktifkan Departemen',
                        'activate_user' => 'Aktifkan Pengguna',
                        'add_favorite' => 'Tambah Favorit',
                        'approve_user' => 'Setujui Pengguna',
                        'archive_document' => 'Arsipkan Dokumen',
                        'create_category' => 'Tambah Kategori',
                        'create_department' => 'Tambah Departemen',
                        'deactivate_category' => 'Nonaktifkan Kategori',
                        'deactivate_department' => 'Nonaktifkan Departemen',
                        'deactivate_user' => 'Nonaktifkan Pengguna',
                        'delete_category' => 'Hapus Kategori',
                        'download_document' => 'Unduh Dokumen',
                        'download_document_revision' => 'Unduh Revisi Dokumen',
                        'open_document' => 'Buka Dokumen',
                        'open_document_revision' => 'Buka Revisi Dokumen',
                        'permanent_delete_document' => 'Hapus Permanen Dokumen',
                        'reject_user' => 'Tolak Pengguna',
                        'remove_favorite' => 'Hapus Favorit',
                        'restore_document' => 'Pulihkan Dokumen',
                        'update_category' => 'Perbarui Kategori',
                        'update_department' => 'Perbarui Departemen',
                        'update_document' => 'Perbarui Dokumen',
                        'upload_document' => 'Unggah Dokumen',
                        'upload_document_revision' => 'Unggah Revisi Dokumen',
                        'view_document' => 'Lihat Dokumen',
                    ];

        $sortedActions = collect($actions)
            ->sortBy(fn ($action) => strtolower(
                $actionLabels[$action] ?? ucwords(str_replace('_', ' ', (string) $action))
            ))->values();

        $hasExtraFilters = request()->filled('user_id')
            || request()->filled('action')
            || request()->filled('document_id')
            || request()->filled('ip_address')
            || request()->filled('date_from')
            || request()->filled('date_to');

        $hasAnyFilters = $hasExtraFilters || request()->filled('search');
    @endphp

    <div class="mx-auto max-w-[1220px] space-y-4 pb-7" data-qh-audit-page>
        {{-- Compact page title --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="mb-1 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.13em] text-blue-700">
                    <span class="h-3.5 w-1 rounded-full bg-blue-700" aria-hidden="true"></span>
                    Keamanan & Audit
                </div>
                <h1 class="!text-[26px] font-extrabold tracking-tight text-slate-900">Activity Log</h1>
            </div>
            <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                Jejak Audit Aktif
            </span>
        </header>

        {{-- Four statistics in one restrained strip instead of tall separate cards. --}}
        <section aria-label="Ringkasan audit" class="grid grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5 md:grid-cols-4">
            @foreach([
                ['label' => 'Total Aktivitas', 'value' => $statistics['total'], 'accent' => 'text-blue-800'],
                ['label' => 'Hari Ini', 'value' => $statistics['today'], 'accent' => 'text-emerald-700'],
                ['label' => 'Aktivitas Dokumen', 'value' => $statistics['document_activity'], 'accent' => 'text-blue-800'],
                ['label' => 'Aktivitas Akun', 'value' => $statistics['user_management'], 'accent' => 'text-slate-900'],
            ] as $stat)
                <div class="min-w-0 border-b border-r border-slate-100 px-4 py-3.5 last:border-r-0 sm:px-5">
                    <p class="text-[12px] font-semibold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-1 !text-[24px] font-extrabold leading-tight {{ $stat['accent'] }}">{{ number_format($stat['value']) }}</p>
                </div>
            @endforeach
        </section>

        {{-- Search is always visible; advanced filters stay available but compact. --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5" aria-label="Filter audit">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="search" class="mb-1.5 block text-xs font-semibold text-slate-600">Cari aktivitas</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input id="search" name="search" type="search" value="{{ request('search') }}" maxlength="255"
                                placeholder="Pengguna, dokumen, aksi, atau IP..."
                                class="block min-h-[43px] w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex min-h-[43px] items-center justify-center rounded-xl bg-blue-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">
                            Cari
                        </button>
                        <a href="{{ route('activity-logs.index') }}" class="inline-flex min-h-[43px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            Atur Ulang
                        </a>
                    </div>
                </div>

                <details class="group mt-3 border-t border-slate-100 pt-3" @if($hasExtraFilters) open @endif>
                    <summary class="flex w-fit cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-bold text-blue-700 transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 [&::-webkit-details-marker]:hidden">
                        <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        Filter Lanjutan
                        @if($hasExtraFilters)
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] text-blue-800">Aktif</span>
                        @endif
                    </summary>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="min-w-0">
                            <label for="user_id" class="mb-1.5 block text-xs font-semibold text-slate-600">Pengguna</label>
                            <select data-qh-searchable-select id="user_id" name="user_id" class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Semua Pengguna</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label for="action" class="mb-1.5 block text-xs font-semibold text-slate-600">Aksi</label>
                            <select data-qh-searchable-select id="action" name="action" class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Semua Aksi</option>
                                @foreach($sortedActions as $action)
                                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $actionLabels[$action] ?? ucwords(str_replace('_', ' ', $action)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label for="document_id" class="mb-1.5 block text-xs font-semibold text-slate-600">Dokumen</label>
                            <select data-qh-searchable-select id="document_id" name="document_id" class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Semua Dokumen</option>
                                @foreach($documents as $document)
                                    <option value="{{ $document->id }}" @selected((string) request('document_id') === (string) $document->id)>{{ $document->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0">
                            <label for="ip_address" class="mb-1.5 block text-xs font-semibold text-slate-600">Alamat IP</label>
                            <input id="ip_address" name="ip_address" type="text" value="{{ request('ip_address') }}" maxlength="45" placeholder="127.0.0.1"
                                class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                        </div>
                        <div class="min-w-0">
                            <label for="date_from" class="mb-1.5 block text-xs font-semibold text-slate-600">Tanggal Mulai</label>
                            <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}"
                                class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                        </div>
                        <div class="min-w-0">
                            <label for="date_to" class="mb-1.5 block text-xs font-semibold text-slate-600">Tanggal Akhir</label>
                            <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}"
                                class="block min-h-[42px] w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-blue-600 focus:ring-blue-600">
                        </div>
                        <div class="flex items-end sm:col-span-2 xl:col-span-2">
                            <button type="submit" class="min-h-[42px] rounded-xl bg-blue-700 px-5 text-sm font-bold text-white transition hover:bg-blue-800">Terapkan Filter</button>
                        </div>
                    </div>
                </details>
            </form>
        </section>

        {{-- Readable, responsive activity feed. Detail is native <details>, no CSP-unsafe x-data. --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5" aria-labelledby="qhAuditHeading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                <div class="min-w-0">
                    <h2 id="qhAuditHeading" class="!text-[17px] font-extrabold text-slate-900">Catatan Audit</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format($logs->total()) }} catatan ditemukan</p>
                </div>
                <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-semibold text-blue-800">Terbaru dahulu</span>
            </div>

            @if($logs->count())
                <div class="divide-y divide-slate-100" data-qh-audit-list>
                    @foreach($logs as $log)
                        @php
                            $metadata = is_array($log->metadata) ? $log->metadata : [];
                            $actionLabel = $actionLabels[$log->action]
                                ?? ucwords(str_replace('_', ' ', $log->action));

                            $actionStyle = match (true) {
                                in_array($log->action, [
                                    'permanent_delete_document', 'delete_category', 'reject_user',
                                ], true) => 'bg-rose-50 text-rose-800 border-rose-100',
                                in_array($log->action, [
                                    'archive_document', 'deactivate_user', 'deactivate_department', 'deactivate_category',
                                ], true) => 'bg-amber-50 text-amber-800 border-amber-100',
                                in_array($log->action, [
                                    'restore_document', 'approve_user', 'activate_user', 'activate_category', 'activate_department',
                                ], true) => 'bg-emerald-50 text-emerald-800 border-emerald-100',
                                default => 'bg-blue-50 text-blue-800 border-blue-100',
                            };

                            $documentTitle = $log->document?->title
                                ?? (is_string($metadata['document_title'] ?? null) ? $metadata['document_title'] : null);
                            $targetName = is_string($metadata['target_name'] ?? null) ? $metadata['target_name'] : null;
                            $targetTitle = $documentTitle ?? $targetName;
                            $documentCode = $log->document?->document_code
                                ?? (is_string($metadata['document_code'] ?? null) ? $metadata['document_code'] : null);
                            $summary = (string) ($log->description ?? '');
                        @endphp

                        <article class="px-4 py-4 transition-colors hover:bg-blue-50/30 sm:px-5" data-qh-audit-entry>
                            <div class="grid min-w-0 grid-cols-1 items-start gap-3 lg:grid-cols-[190px_minmax(0,1fr)_150px] lg:gap-5">
                                {{-- Who: fixed width on desktop, not a narrow table column. --}}
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-blue-100 bg-blue-100 font-bold text-blue-800">
                                        @if($log->user?->profile_photo_path)
                                            <img src="{{ route('activity-logs.photo.show', $log->user) }}" alt="Foto profil {{ $log->user->name }}"
                                                class="h-full w-full object-cover" loading="lazy" decoding="async">
                                        @else
                                            <span aria-label="{{ $log->user ? 'Inisial '.$log->user->name : 'Aktivitas sistem' }}">
                                                {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-bold text-slate-900" title="{{ $log->user?->name ?? 'Sistem' }}">{{ $log->user?->name ?? 'Sistem' }}</p>
                                        <p class="truncate text-[11px] text-slate-500" title="{{ $log->user?->email ?? 'Aktivitas sistem' }}">{{ $log->user?->email ?? 'Aktivitas sistem' }}</p>
                                    </div>
                                </div>

                                {{-- What and which target --}}
                                <div class="min-w-0 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex max-w-full items-center rounded-lg border px-2.5 py-1 text-[11px] font-bold {{ $actionStyle }}">{{ $actionLabel }}</span>
                                        @if($documentCode)
                                            <span class="max-w-full truncate rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-600">{{ $documentCode }}</span>
                                        @endif
                                    </div>

                                    @if($log->document)
                                        <a href="{{ route('documents.show', $log->document) }}" class="block break-words text-sm font-bold leading-snug text-blue-800 hover:underline">
                                            {{ $log->document->title }}
                                        </a>
                                    @elseif($targetTitle)
                                        <p class="break-words text-sm font-bold leading-snug text-slate-800">{{ $targetTitle }}</p>
                                    @endif

                                    @if($summary !== '')
                                        <p class="break-words text-[13px] leading-5 text-slate-600">{{ \Illuminate\Support\Str::limit($summary, 150) }}</p>
                                    @elseif(!$targetTitle)
                                        <p class="text-xs text-slate-400">Aktivitas tercatat</p>
                                    @endif
                                </div>

                                {{-- When and where, separated from content. --}}
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 lg:block lg:text-right">
                                    <time datetime="{{ $log->created_at?->toIso8601String() }}" class="font-bold text-slate-800">
                                        {{ $log->created_at?->locale('id')->translatedFormat('d M Y') }}
                                    </time>
                                    <span class="text-slate-400 lg:mt-1 lg:block">{{ $log->created_at?->format('H:i:s') }}</span>
                                    <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-600 lg:mt-2 lg:inline-block">{{ $log->ip_address ?? 'IP tidak tersedia' }}</span>
                                </div>
                            </div>

                            {{-- Native disclosure keeps long audit metadata out of row layout. --}}
                            <details class="group mt-2.5 border-t border-slate-100 pt-2.5" data-qh-audit-detail>
                                <summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 [&::-webkit-details-marker]:hidden">
                                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                    Lihat Detail
                                </summary>
                                <div class="mt-2 rounded-xl border border-blue-100 bg-slate-50/80 p-4" data-qh-audit-detail-content>
                                    <div class="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-2">
                                        <div class="min-w-0">
                                            <h3 class="mb-3 !text-[13px] font-bold text-slate-900">Informasi Aktivitas</h3>
                                            <dl class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                                                <div class="min-w-0"><dt class="text-slate-500">Aksi</dt><dd class="mt-1 break-words font-semibold text-slate-800">{{ $actionLabel }}</dd></div>
                                                <div class="min-w-0"><dt class="text-slate-500">Waktu</dt><dd class="mt-1 font-semibold text-slate-800">{{ $log->created_at?->format('d M Y H:i:s') }}</dd></div>
                                                <div class="min-w-0"><dt class="text-slate-500">Pengguna</dt><dd class="mt-1 break-words font-semibold text-slate-800">{{ $log->user?->name ?? 'Sistem' }}</dd></div>
                                                <div class="min-w-0"><dt class="text-slate-500">Alamat IP</dt><dd class="mt-1 break-all font-mono font-semibold text-slate-800">{{ $log->ip_address ?? '—' }}</dd></div>
                                                @if($log->document?->category?->department)
                                                    <div class="min-w-0"><dt class="text-slate-500">Departemen</dt><dd class="mt-1 break-words font-semibold text-slate-800">{{ $log->document->category->department->name }}</dd></div>
                                                @endif
                                                @if($log->document?->category)
                                                    <div class="min-w-0"><dt class="text-slate-500">Kategori</dt><dd class="mt-1 break-words font-semibold text-slate-800">{{ $log->document->category->name }}</dd></div>
                                                @endif
                                            </dl>

                                            @if($summary !== '')
                                                <div class="mt-3 border-t border-slate-200 pt-3">
                                                    <p class="text-[11px] font-semibold text-slate-500">Deskripsi Lengkap</p>
                                                    <p class="mt-1 whitespace-pre-wrap break-words text-xs leading-5 text-slate-700">{{ $summary }}</p>
                                                </div>
                                            @endif
                                            <div class="mt-3 border-t border-slate-200 pt-3">
                                                <p class="text-[11px] font-semibold text-slate-500">Agen Pengguna</p>
                                                <p class="mt-1 break-all text-xs leading-5 text-slate-700">{{ $log->user_agent ?? '—' }}</p>
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <h3 class="mb-3 !text-[13px] font-bold text-slate-900">Metadata</h3>
                                            @if(count($metadata))
                                                <dl class="divide-y divide-slate-200/80 rounded-xl border border-slate-200 bg-white px-3">
                                                    @foreach($metadata as $key => $value)
                                                        <div class="grid min-w-0 grid-cols-1 gap-1 py-2.5 sm:grid-cols-[135px_minmax(0,1fr)] sm:gap-3">
                                                            <dt class="break-words text-[11px] font-bold text-slate-500">{{ ucwords(str_replace('_', ' ', (string) $key)) }}</dt>
                                                            <dd class="min-w-0 whitespace-pre-wrap break-words text-xs leading-5 text-slate-700">
                                                                @if(is_array($value))
                                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}
                                                                @elseif(is_bool($value))
                                                                    {{ $value ? 'ya' : 'tidak' }}
                                                                @else
                                                                    {{ $value ?? '—' }}
                                                                @endif
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            @else
                                                <p class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs text-slate-500">Tidak ada metadata tambahan.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </article>
                    @endforeach
                </div>

                @if($logs->hasPages())
                    <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
                        {{ $logs->links() }}
                    </div>
                @endif
            @else
                <div class="px-5 py-14 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/>
                        </svg>
                    </div>
                    <h3 class="!text-[16px] font-bold text-slate-800">Tidak ada catatan aktivitas</h3>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter pencarian.</p>
                    @if($hasAnyFilters)
                        <a href="{{ route('activity-logs.index') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Atur Ulang Filter</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
