/*
 * QualityHub My Profile enhancements.
 * Native event listeners; no inline scripts, CDN, or relaxed CSP directives.
 */
const root = document.querySelector('[data-qh-profile-page]');

if (root) {
    const photoInput = root.querySelector('#profilePhotoInput');
    const preview = root.querySelector('#profilePhotoPreview');
    const fallback = root.querySelector('[data-qh-avatar-fallback]');
    const fileName = root.querySelector('[data-qh-photo-file-name]');
    let currentObjectUrl = null;

    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];

        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }

        if (!file) {
            if (fileName) fileName.textContent = 'Pilih gambar untuk melihat pratinjau.';
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2097152) {
            if (fileName) fileName.textContent = 'Gunakan JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.';
            photoInput.value = '';
            return;
        }

        currentObjectUrl = URL.createObjectURL(file);
        if (preview) {
            preview.src = currentObjectUrl;
            preview.classList.remove('hidden');
        }
        fallback?.classList.add('hidden');
        if (fileName) fileName.textContent = file.name;
    });

    window.addEventListener('pagehide', () => {
        if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
    }, { once: true });

    root.querySelectorAll('[data-qh-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.qhPasswordToggle ?? '');
        if (!input) return;

        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Sembunyikan' : 'Lihat';
            button.setAttribute('aria-pressed', String(visible));
            input.focus({ preventScroll: true });
        });
    });

    const password = root.querySelector('#update_password_password');
    const label = root.querySelector('[data-qh-password-strength-label]');
    const bars = [...root.querySelectorAll('[data-qh-password-strength-bar]')];

    const updateStrength = () => {
        if (!password || !label) return;

        const value = password.value;
        const score = !value ? 0 : [
            value.length >= 12,
            value.length >= 16,
            /[a-z]/.test(value) && /[A-Z]/.test(value),
            /\d/.test(value) && /[^a-zA-Z0-9]/.test(value),
        ].filter(Boolean).length;

        const names = ['Belum diisi', 'Rendah', 'Cukup', 'Baik', 'Kuat'];
        const colors = ['bg-slate-200', 'bg-rose-500', 'bg-amber-500', 'bg-blue-500', 'bg-emerald-500'];
        label.textContent = names[score];

        bars.forEach((bar, index) => {
            bar.classList.remove(...colors);
            bar.classList.add(index < score ? colors[score] : 'bg-slate-200');
        });
    };

    password?.addEventListener('input', updateStrength);
}
