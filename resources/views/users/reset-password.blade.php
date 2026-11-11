<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Kembali ke User Management</a>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Reset Password Pengguna</h1>
            <p class="mt-2 text-sm text-slate-600">Reset password <strong>{{ $user->name }}</strong> ({{ $user->email }}). Pengguna akan logout dari semua perangkat dan wajib mengganti password sementara saat login.</p>
        </div>
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('users.password.update', $user) }}" autocomplete="off" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')
            <div>
                <label for="current_password" class="block text-sm font-semibold text-slate-700">Password Anda (Super Admin)</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border-slate-200">
                <p class="mt-1 text-xs text-slate-500">Verifikasi identitas administrator sebelum mengubah password akun lain.</p>
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Password Sementara Baru</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200">
                <p class="mt-1 text-xs text-slate-500">Minimal 12 karakter; huruf besar, kecil, angka, dan simbol.</p>
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Ulangi Password Sementara</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-200">
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-bold text-white hover:bg-amber-700">Reset Password</button>
                <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
