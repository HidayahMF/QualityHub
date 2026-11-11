<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Kembali ke User Management</a>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Tambah Akun</h1>
            <p class="mt-2 text-sm text-slate-600">Hanya Super Admin dapat membuat akun Admin Dokumen atau Karyawan. Password awal wajib diganti oleh pengguna setelah login.</p>
        </div>
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <strong>Periksa kembali input:</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('users.store') }}" autocomplete="off" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">Nama Lengkap</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}" maxlength="255" class="mt-2 w-full rounded-xl border-slate-200">
            </div>
            <div>
                <label for="employee_id" class="block text-sm font-semibold text-slate-700">ID Karyawan</label>
                <input id="employee_id" name="employee_id" type="text" required value="{{ old('employee_id') }}" maxlength="50" class="mt-2 w-full rounded-xl border-slate-200">
            </div>
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email untuk Login</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}" maxlength="255" autocomplete="off" class="mt-2 w-full rounded-xl border-slate-200">
            </div>
            <div>
                <label for="department_id" class="block text-sm font-semibold text-slate-700">Departemen</label>
                <select id="department_id" name="department_id" required class="mt-2 w-full rounded-xl border-slate-200">
                    <option value="">Pilih Departemen</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="role" class="block text-sm font-semibold text-slate-700">Role</label>
                <select id="role" name="role" required class="mt-2 w-full rounded-xl border-slate-200">
                    <option value="user" @selected(old('role', 'user') === 'user')>Karyawan — cari, lihat, unduh dokumen</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin — kelola dokumen</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Role Super Admin tidak dapat dibuat dari formulir ini.</p>
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Password Sementara</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200">
                <p class="mt-1 text-xs text-slate-500">Minimal 12 karakter; huruf besar, kecil, angka, dan simbol. Berikan secara pribadi, bukan lewat grup.</p>
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200">
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-blue-800 px-5 py-3 text-sm font-bold text-white hover:bg-blue-900">Buat Akun</button>
                <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
