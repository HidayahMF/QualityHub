AI-Service (FastAPI) — Local Embedder

Overview
- The ai-service provides a local HTTP API for parsing and embedding-based search. It is intentionally local-only and designed to be called by the Laravel application running on the same host.

Key runtime expectations
- Model: `intfloat/multilingual-e5-small` — must be present locally at `D:\BMC_AI\Models\multilingual-e5-small`.
- Virtualenv: use `.venv` in `ai-service`.
- Start script: `start.ps1` (Windows) runs Uvicorn on `127.0.0.1:8765`.
- Offline env vars: set `TRANSFORMERS_OFFLINE=1` and `HF_HUB_OFFLINE=1` before starting.
- Embedder configuration: `LocalSentenceTransformerEmbedder` is instantiated with `local_files_only=True` to prevent runtime downloads.

Developer quickstart
1. Activate venv: `.venv\Scripts\Activate.ps1` and install requirements (already done in CI for this project).
2. Ensure the model exists at `D:\BMC_AI\Models\multilingual-e5-small`.
3. Start service: `powershell -File .\start.ps1`.
4. Verify health: `GET http://127.0.0.1:8765/health` → `model_loaded: true`.

Important security notes
- The service binds loopback only; do not change bind address to 0.0.0.0 in production.
- Python code must not contain database credentials; Laravel performs DB access and passes metadata/candidates.
- No cloud API keys should be present for embedding inference.

Troubleshooting
- If `/health` shows `model_loaded: false`, check that model path is correct and that `local_files_only=True` is honored; review startup logs for loader errors.
# BMC QualityHub Local AI Smart Search

Service ini melakukan entity extraction deterministik dan semantic ranking
metadata dokumen. Service tidak membaca MariaDB, tidak menerima isi PDF/gambar,
dan tidak menyediakan endpoint cloud. Laravel tetap menentukan dokumen yang
boleh dilihat user sebelum metadata kandidat dikirim ke service ini.

## Alur

1. Laravel mengirim query dan vocabulary master ke `POST /parse`.
2. Laravel memvalidasi entity hasil parser, menerapkan filter database, scope,
   dan authorization existing.
3. Laravel mengirim hanya metadata kandidat yang sudah authorized ke
   `POST /search`.
4. Service membangun canonical metadata, memberi prefix E5 `query:` dan
   `passage:`, membuat normalized embedding, lalu menghitung cosine similarity.
5. Service mengembalikan ID kandidat dan skor relevansi. Laravel harus tetap
   melakukan intersection dengan ID kandidat authorized sebelum merender hasil.

Parser memakai vocabulary yang dikirim Laravel agar Line, product, tipe Axle,
posisi, dan Part Number Brake mengikuti isi database. Nilai yang tidak dikenal
tidak dibuat-buat. Definisi `minggu lalu` adalah Senin sampai Minggu pada minggu
kalender sebelumnya berdasarkan `reference_date` dari Laravel.

## Setup environment terisolasi

Python yang ditemukan pada workstation adalah Python 3.11.9. Windows launcher
`py` tidak mengenali instalasi tersebut, jadi gunakan perintah `python`.

```powershell
cd D:\BMC_Project\BMC-SmartFind\ai-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements-dev.txt
```

Perintah instalasi dependency adalah langkah setup yang membutuhkan paket yang
sudah disediakan oleh tim IT atau akses package registry yang disetujui. Runtime
service tidak menjalankan instalasi maupun download otomatis.

## Model lokal

Target model adalah `intfloat/multilingual-e5-small`. Salin folder model yang
sudah diperoleh melalui proses setup terpisah ke lokasi lokal, misalnya:

```text
D:\BMC_AI\Models\multilingual-e5-small
```

Service memanggil `SentenceTransformer` dengan `local_files_only=True` dan
`trust_remote_code=False`. `TRANSFORMERS_OFFLINE=1`, `HF_HUB_OFFLINE=1`, serta
telemetry opt-out juga diaktifkan sebelum library model di-import. Bila folder
model belum ada, service tetap hidup untuk health/parser, melaporkan
`model_loaded=false`, dan `POST /search` mengembalikan HTTP 503. Tidak ada
fallback ke internet atau cloud AI.

## Menjalankan service

```powershell
cd D:\BMC_Project\BMC-SmartFind\ai-service
$env:AI_MODEL_PATH = 'D:\BMC_AI\Models\multilingual-e5-small'
.\start.ps1
```

Service bind ke `127.0.0.1:8765`, sehingga browser tidak perlu dan tidak boleh
mengaksesnya secara langsung. Jangan ubah host menjadi `0.0.0.0` tanpa review
keamanan jaringan.

Environment opsional:

```text
AI_MODEL_LABEL=intfloat/multilingual-e5-small
AI_MAX_CANDIDATES=250
```

Path model tidak dikembalikan oleh API.

## API

`GET /health`:

```json
{
  "status": "degraded",
  "mode": "local",
  "model_loaded": false,
  "model": "intfloat/multilingual-e5-small",
  "offline_mode": true,
  "detail_code": "model_not_found"
}
```

`POST /parse` menerima query, tanggal referensi dari timezone Laravel, dan
vocabulary opsional:

```json
{
  "query": "cari perubahan product A ke product E line 2 september 2026",
  "reference_date": "2026-09-17",
  "known_values": {
    "lines": [{"value": "LINE_2", "aliases": ["line 2"]}],
    "products": [
      {"value": "product_A", "aliases": ["product A"]},
      {"value": "product_E", "aliases": ["product E"]}
    ]
  }
}
```

`POST /search` menerima field parse yang sama ditambah maksimal 250 kandidat
secara default dan `top_k` 1 sampai 50. Metadata kandidat yang didukung:

```json
{
  "query": "cari retooling line 2",
  "reference_date": "2026-09-17",
  "candidates": [
    {
      "document_id": 16,
      "title": "INSTRUKSI PERUBAHAN TYPE (RETOOLING)",
      "file_name": "instruksi-perubahan-type-retooling.pdf",
      "document_code": "IK-RETOOLING",
      "part_number": "product_A-product_E",
      "keywords": "retooling LINE_2 product_A product_E",
      "department": "Quality",
      "category": "Instruksi Kerja",
      "scan_type": "Retooling",
      "line": "LINE_2",
      "shift": 1,
      "from_type": "product_A",
      "to_type": "product_E",
      "from_cycle_time": 25,
      "to_cycle_time": 22,
      "document_date": "2026-09-15",
      "updated_at": "2026-09-15T08:00:00+07:00"
    }
  ],
  "top_k": 20
}
```

Response hanya berisi structured entity, ID kandidat, dan cosine similarity.
Skor tersebut adalah skor relevansi, bukan persentase akurasi.

## Test tanpa model

Test menggunakan fake embedder. Test tidak membuka koneksi ke internet dan
tidak memerlukan folder model.

```powershell
cd D:\BMC_Project\BMC-SmartFind\ai-service
.\.venv\Scripts\python.exe -m pytest -q
```
