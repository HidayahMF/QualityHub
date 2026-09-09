<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-blue-600">Document Management</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Scan / Capture Berkas QC</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Digitalisasi berkas QC langsung ke BMC QualityHub.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('documents.scan.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1.05fr_.95fr]">
            @csrf

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Capture Berkas</h2>
                    <p class="mt-1 text-xs text-slate-500">Gunakan kamera perangkat atau pilih foto dari galeri.</p>
                </div>

                <div class="p-5">
                    <div id="previewBox" class="relative flex min-h-[360px] items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                        <div id="emptyState" class="px-6 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h1.5l1.1-1.65a1.5 1.5 0 0 1 1.248-.666h2.804a1.5 1.5 0 0 1 1.248.666l1.1 1.65h1.5A2.25 2.25 0 0 1 20.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25h-12A2.25 2.25 0 0 1 4 17.25v-7.5A2.25 2.25 0 0 1 6.25 7.5Z"/><circle cx="12.25" cy="13.5" r="3.25"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">Belum ada berkas</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Ambil foto dokumen QC atau pilih gambar yang sudah ada.</p>
                        </div>
                        <img id="imagePreview" class="hidden max-h-[520px] w-full object-contain" alt="Preview hasil scan">
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h1.5l1.1-1.65a1.5 1.5 0 0 1 1.248-.666h2.804a1.5 1.5 0 0 1 1.248.666l1.1 1.65h1.5A2.25 2.25 0 0 1 20.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25h-12A2.25 2.25 0 0 1 4 17.25v-7.5A2.25 2.25 0 0 1 6.25 7.5Z"/><circle cx="12.25" cy="13.5" r="3.25"/></svg>
                            Ambil Foto
                            <input id="cameraInput" type="file" name="file" accept="image/*" capture="environment" class="hidden">
                        </label>

                        <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h3l1.5 1.5h7.5a2.25 2.25 0 0 1 2.25 2.25v8.25A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V7.5Z"/></svg>
                            Pilih Foto
                            <input id="uploadInput" type="file" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <p class="mt-3 text-center text-xs text-slate-400">Format: JPG, JPEG, PNG, WEBP, GIF, BMP, TIF, TIFF · Maks. 20 MB</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Informasi Berkas</h2>
                    <p class="mt-1 text-xs text-slate-500">Lengkapi metadata agar berkas mudah dicari kembali.</p>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label for="title" class="mb-1.5 block text-sm font-medium text-slate-700">Judul Dokumen <span class="text-red-500">*</span></label>
                        <input id="title" name="title" value="{{ old('title') }}" required class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: INSTRUKSI PERUBAHAN TYPE (RETOOLING)">
                    </div>

                    <div>
                        <label for="file_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama File <span class="text-red-500">*</span></label>
                        <input id="file_name" name="file_name" value="{{ old('file_name') }}" required class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: instruksi-perubahan-type">
                        <p class="mt-1 text-xs text-slate-400">Ekstensi gambar akan ditambahkan otomatis.</p>
                    </div>

                    <div>
                        <label for="category_id" class="mb-1.5 block text-sm font-medium text-slate-700">Kategori <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" required class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}{{ $category->department ? ' · '.$category->department->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="scan_type" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis Scan</label>
                            <input id="scan_type" name="scan_type" value="{{ old('scan_type', 'Retooling') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Retooling">
                        </div>
                        <div>
                            <label for="scan_date" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal <span class="text-red-500">*</span></label>
                            <input id="scan_date" type="date" name="scan_date" value="{{ old('scan_date', now()->format('Y-m-d')) }}" required class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="shift" class="mb-1.5 block text-sm font-medium text-slate-700">Shift</label>
                            <input id="shift" name="shift" value="{{ old('shift') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="1 / 2 / 3">
                        </div>
                        <div>
                            <label for="line" class="mb-1.5 block text-sm font-medium text-slate-700">Line</label>
                            <input id="line" name="line" value="{{ old('line') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: MOP 3">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="from_type" class="mb-1.5 block text-sm font-medium text-slate-700">Dari Type</label>
                            <input id="from_type" name="from_type" value="{{ old('from_type') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Type sebelumnya">
                        </div>
                        <div>
                            <label for="to_type" class="mb-1.5 block text-sm font-medium text-slate-700">Ke Type</label>
                            <input id="to_type" name="to_type" value="{{ old('to_type') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Type berikutnya">
                        </div>
                    </div>

                    <div>
                        <label for="scan_time" class="mb-1.5 block text-sm font-medium text-slate-700">Jam</label>
                        <input id="scan_time" type="time" name="scan_time" value="{{ old('scan_time') }}" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div id="selectedFile" class="hidden rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5 text-xs text-blue-800"></div>

                    <button id="saveButton" type="submit" disabled class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-45">
                        Simpan Berkas QC
                    </button>
                </div>
            </section>
        </form>
    </div>

    <script>
        (() => {
            const cameraInput = document.getElementById('cameraInput');
            const uploadInput = document.getElementById('uploadInput');
            const preview = document.getElementById('imagePreview');
            const emptyState = document.getElementById('emptyState');
            const selectedFile = document.getElementById('selectedFile');
            const saveButton = document.getElementById('saveButton');

            function showPreview(file, sourceInput) {
                if (!file) return;
                if (!file.type.startsWith('image/')) return;

                // Input kamera adalah input form yang benar; input galeri hanya dipakai untuk memilih file.
                if (sourceInput === uploadInput) {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    cameraInput.files = transfer.files;
                }

                const url = URL.createObjectURL(file);
                preview.src = url;
                preview.classList.remove('hidden');
                emptyState.classList.add('hidden');
                selectedFile.textContent = `Berkas dipilih: ${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
                selectedFile.classList.remove('hidden');
                saveButton.disabled = false;

                preview.onload = () => URL.revokeObjectURL(url);
            }

            cameraInput.addEventListener('change', () => showPreview(cameraInput.files[0], cameraInput));
            uploadInput.addEventListener('change', () => showPreview(uploadInput.files[0], uploadInput));
        })();
    </script>
</x-app-layout>
