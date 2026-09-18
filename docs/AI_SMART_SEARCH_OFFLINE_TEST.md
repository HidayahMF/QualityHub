AI Smart Search — Offline Acceptance Test

Purpose
- Steps for a human to validate that the AI Smart Search runs entirely from a local model when disconnected from the internet.

Preconditions
- Model folder present: `D:\BMC_AI\Models\multilingual-e5-small`
- FastAPI (ai-service) configured with `TRANSFORMERS_OFFLINE=1` and `HF_HUB_OFFLINE=1`.
- FastAPI binds to `127.0.0.1:8765`.
- Laravel app running locally with access to candidate documents.

Manual offline test steps
1. Start FastAPI (ai-service) normally while network is connected and verify `/health` returns `model_loaded: true`.
2. Start Laravel and open Documents → AI Smart Search. Run a known query (see examples below) and confirm semantic results.
3. Physically disconnect machine from network (disable Wi‑Fi or unplug ethernet). Do NOT delete model files.
4. Re-run the same query in the browser UI. Expected: AI semantic results continue to be returned and the `model_loaded` indicator remains true in FastAPI responses.
5. Reconnect network and verify behavior remains unchanged.

Indicators that confirm local inference
- FastAPI `/health` returns `model_loaded: true` and `mode: local`.
- FastAPI logs show model loaded from `D:\BMC_AI\Models\multilingual-e5-small` and no outbound HTTP calls to HF or other APIs.
- No brand-new downloads are triggered by FastAPI when offline.

Example queries
- "cari dokumen retooling line 2 perubahan product A ke product E"
- "pencarian brake assy 402 502"

Notes
- This document does not perform the disconnect automatically; manual network disconnect is required to prove offline acceptance.
# Uji Offline AI Smart Search

Dokumen ini membuktikan bahwa inference AI Smart Search tidak membutuhkan Internet atau cloud AI. “Offline” berarti WAN/Internet boleh terputus sementara server, MariaDB, dan jaringan lokal BMC tetap tersedia. Jika LAN antara browser client dan server BMC dicabut, client tentu tidak dapat membuka aplikasi web.

## 1. Prasyarat

Pastikan komponen berikut sudah disiapkan:

- PHP/Laravel dan MariaDB BMC QualityHub berjalan seperti biasa.
- Python 3.11 tersedia melalui perintah `python`.
- Virtual environment berada di `D:\BMC_Project\BMC-SmartFind\ai-service\.venv`.
- Dependency Python telah dipasang ke virtual environment, bukan global.
- Folder model lokal sudah disalin ke `D:\BMC_AI\Models\multilingual-e5-small` atau path lokal lain yang dikonfigurasi.
- Folder model lengkap berasal dari proses akuisisi administrator yang terpisah. Runtime service tidak mengunduh model.

Jangan lanjut ke demo “hasil AI tetap muncul” bila model belum tersedia. Dalam kondisi itu, perilaku yang benar adalah `/health` mengembalikan `model_loaded=false`, UI tetap hidup, dan normal keyword search tetap dapat dipakai.

## 2. Verifikasi folder model tanpa menyalakan service

Buka PowerShell:

```powershell
Test-Path -LiteralPath 'D:\BMC_AI\Models\multilingual-e5-small'
```

Hasil harus `True`. Jangan menaruh model di `public/` dan jangan memasukkan model ke Git.

## 3. Aktifkan konfigurasi offline

Di terminal khusus FastAPI:

```powershell
Set-Location 'D:\BMC_Project\BMC-SmartFind\ai-service'
$env:AI_MODEL_PATH = 'D:\BMC_AI\Models\multilingual-e5-small'
$env:AI_MODEL_LABEL = 'intfloat/multilingual-e5-small'
$env:TRANSFORMERS_OFFLINE = '1'
$env:HF_HUB_OFFLINE = '1'
$env:HF_HUB_DISABLE_TELEMETRY = '1'
```

Service juga menetapkan offline flags sebelum mengimpor model. Perintah di atas membuat kondisi demo eksplisit dan mudah diperiksa.

## 4. Start local AI service

Cara yang direkomendasikan:

```powershell
Set-Location 'D:\BMC_Project\BMC-SmartFind\ai-service'
.\start.ps1
```

Cara manual yang setara:

```powershell
Set-Location 'D:\BMC_Project\BMC-SmartFind\ai-service'
& '.\.venv\Scripts\python.exe' -m uvicorn app.main:app --host 127.0.0.1 --port 8765
```

Host harus tetap `127.0.0.1`. Jangan menggantinya menjadi `0.0.0.0` pada deployment biasa karena browser tidak perlu mengakses Python.

## 5. Periksa health

Pada terminal kedua:

```powershell
Invoke-RestMethod -Method Get -Uri 'http://127.0.0.1:8765/health' | ConvertTo-Json -Depth 5
```

Sebelum demo search, periksa:

```text
mode          = local
offline_mode  = true
model_loaded  = true
```

Label model boleh terlihat. Response tidak boleh memperlihatkan DB credential, secret, atau path absolut model.

## 6. Konfigurasi dan start Laravel

Environment Laravel yang relevan:

```dotenv
AI_SMART_SEARCH_ENABLED=true
AI_SMART_SEARCH_URL=http://127.0.0.1:8765
AI_SMART_SEARCH_TIMEOUT=10
AI_SMART_SEARCH_TOP_K=10
```

Setelah mengubah `.env`, bersihkan config cache:

```powershell
Set-Location 'D:\BMC_Project\BMC-SmartFind'
php artisan optimize:clear
```

Untuk development pada komputer yang sama:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Untuk server BMC sebenarnya, gunakan web server existing. Hanya web Laravel yang boleh tersedia bagi client LAN; FastAPI tetap loopback.

## 7. Uji sebelum memutus WAN

Login ke BMC QualityHub dan buka Documents. Jalankan minimal query berikut:

```text
cari dokumen retooling line 2 bulan september
dokumen perubahan product A ke product E
cari brake assy 402 502
axle assy LH terbaru
```

Catat jumlah serta urutan hasil. Pastikan UI memberi label “AI Lokal” dan tidak menyebut hasil keyword fallback sebagai hasil AI.

## 8. Putuskan Internet/WAN

Putuskan Wi-Fi/Internet upstream dengan cara operasional yang disetujui IT. Pertahankan:

- loopback `127.0.0.1` antara Laravel dan FastAPI;
- koneksi Laravel ke MariaDB lokal/internal;
- LAN antara browser client dan web server BMC bila client berada pada komputer lain.

Jangan menonaktifkan adapter LAN yang menjadi satu-satunya jalan client menuju BMC QualityHub.

## 9. Ulangi uji dalam kondisi offline

Refresh halaman dan jalankan query yang sama. Kriteria lulus:

1. halaman BMC QualityHub tetap dapat dibuka;
2. `/health` tetap `offline_mode=true` dan `model_loaded=true`;
3. hasil AI tetap berasal dari dokumen aktual;
4. query baru tetap dapat diranking;
5. tidak ada permintaan cloud atau pesan download model;
6. urutan dapat dibandingkan dengan hasil sebelum WAN diputus.

## 10. Verifikasi port lokal

Cari PID yang mendengarkan port 8765:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8765 |
    Select-Object LocalAddress, LocalPort, OwningProcess
```

`LocalAddress` harus `127.0.0.1`. Periksa prosesnya:

```powershell
$aiPid = (Get-NetTCPConnection -State Listen -LocalPort 8765).OwningProcess
Get-Process -Id $aiPid | Select-Object Id, ProcessName, Path
```

Untuk audit lebih dalam, tim IT dapat memantau outbound connection PID yang sama selama beberapa query. Desain service tidak mempunyai cloud endpoint; offline flags dan `local_files_only` mencegah pencarian model remote.

## 11. Uji failure handling

Hentikan FastAPI dengan `Ctrl+C`, lalu lakukan AI search kembali. Kriteria lulus:

- halaman tidak error 500;
- UI menampilkan bahwa AI lokal tidak tersedia;
- UI menyediakan/menggunakan fallback keyword lokal secara jujur;
- normal search existing tetap bekerja;
- tidak ada percobaan cloud fallback.

Nyalakan FastAPI kembali setelah test.

## 12. Uji model tidak tersedia

Jangan menghapus folder model. Untuk environment test, start service dengan `AI_MODEL_PATH` yang menunjuk ke folder test yang tidak ada:

```powershell
Set-Location 'D:\BMC_Project\BMC-SmartFind\ai-service'
$env:AI_MODEL_PATH = 'D:\BMC_AI\Models\__missing_test_model__'
& '.\.venv\Scripts\python.exe' -m uvicorn app.main:app --host 127.0.0.1 --port 8765
```

Kriteria lulus:

- proses FastAPI tetap dapat memberi health;
- health menunjukkan `model_loaded=false` dan pesan aman;
- `/search` tidak membuat fake result;
- Laravel/UI tetap aman dan normal search tersedia;
- tidak muncul download otomatis.

## 13. Bukti demo yang disimpan

Untuk laporan mata kuliah, simpan bukti yang tidak mengandung data rahasia:

- tanggal/waktu demo;
- screenshot status WAN terputus;
- output aman `/health`;
- query contoh yang sudah disetujui;
- screenshot UI sebelum dan setelah WAN diputus;
- nilai `LocalAddress=127.0.0.1` untuk port 8765;
- catatan bahwa LAN lokal tetap terhubung.

Jangan merekam isi dokumen rahasia, cookie, token, `.env`, DB password, atau absolute private document path.
