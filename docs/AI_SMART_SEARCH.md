AI Smart Search — Implementation and Operation

Overview
- This repository integrates a local neural embedding service (FastAPI) with the Laravel application as an authorization gateway. The system runs 100% locally: the embedding model is downloaded and loaded from disk, no cloud inference is used.

Key facts
- Model: intfloat/multilingual-e5-small
- Local model path: D:\BMC_AI\Models\multilingual-e5-small
- Model folder size on disk: 2,280,206,659 bytes (~2.28 GB)
- Embedding runtime: Python FastAPI service (ai-service) on loopback (127.0.0.1:8765)
- Laravel remains the authorization and candidate-builder gateway; Python does not access MariaDB or other production databases.
- All runtime flags: TRANSFORMERS_OFFLINE=1, HF_HUB_OFFLINE=1, model loader uses local_files_only=True.

Model files (on-disk summary)
- Large weight files present (duplicates / alternate formats):
  - pytorch_model.bin (~470.7 MB)
  - model.safetensors (~470.6 MB)
  - onnx/model.onnx (~470.3 MB)
  - openvino/openvino_model.bin (~470.0 MB)
  - onnx/model_O4.onnx (~235.1 MB)
  - onnx/model_qint8_avx512_vnni.onnx (~118.3 MB)
- Tokenizer / vocab files:
  - tokenizer.json (~17.1 MB)
  - sentencepiece.bpe.model (~5.07 MB)
  - tokenizer_config.json, special_tokens_map.json, config.json (small)
- Misc: README, eval results and metadata cache files

Which weights are actually used
- The SentenceTransformers / HuggingFace PyTorch loader will use either `model.safetensors` or `pytorch_model.bin` depending on the backend. Both exist in this folder; the runtime will prefer `safetensors` if supported.
- ONNX and OpenVINO artifacts are alternate exported runtimes; they are not used by the canonical PyTorch loader but can be used for optimized inference in other runtimes.
- No files were removed or modified by this report. Do not delete model artifacts without explicit review.

Provenance and acquisition
- Source repository on Hugging Face: "intfloat/multilingual-e5-small"
- Acquisition method: `huggingface_hub.snapshot_download(...)` executed once to populate `D:\BMC_AI\Models\multilingual-e5-small`.
- Local files include metadata files (.metadata) indicating download provenance; check `README.md` and `.cache/huggingface` subfolders for exact snapshot info.

Security and runtime constraints
- FastAPI binds to loopback only (`127.0.0.1:8765`) and only Laravel (loopback client) may call it.
- Environment variables set for offline operation: `TRANSFORMERS_OFFLINE=1` and `HF_HUB_OFFLINE=1`.
- `LocalSentenceTransformerEmbedder` is configured to load with `local_files_only=True`.
- Python process does not open database connections for embedding/parse operations; Laravel performs DB queries.

Operational notes
- Candidate pipeline: Laravel builds candidate payloads (max configured `candidate_limit`, default 250) and sends to FastAPI `/search` endpoint for semantic ranking.
- Fallback behavior: If local AI is unavailable or malformed, Laravel falls back to keyword search without page failure.

Quick run (developer)
1. Ensure the model folder exists at `D:\BMC_AI\Models\multilingual-e5-small`.
2. Start Python FastAPI (ai-service) virtualenv and `start.ps1`.
3. Start Laravel (php artisan serve or PHP-FPM local env).
4. Use web UI to exercise Documents → AI Smart Search.

Contact
- See `ai-service/README.md` for more developer instructions and local troubleshooting.
# AI Smart Search BMC QualityHub

Dokumen ini menjelaskan AI Smart Search versi pertama untuk BMC QualityHub. Sistem ini adalah **hybrid semantic search**, bukan chatbot dan bukan generative AI. Database tetap menjadi sumber kebenaran. Parser deterministic menangani field bisnis yang harus tepat, sedangkan neural sentence embedding membantu mengurutkan kandidat berdasarkan kemiripan makna.

## 1. Tujuan fitur

Pengguna dapat mencari dokumen dengan bahasa sehari-hari, misalnya `cari dokumen retooling line 2 bulan september`, tanpa harus mengetahui nama file atau keyword persis. Hasil tetap berupa record dokumen nyata yang dapat diakses melalui Laravel.

## 2. Masalah yang diselesaikan

Pencarian keyword bekerja baik jika kata pada query sama dengan kata pada metadata. Pengguna kadang memakai ungkapan berbeda, seperti “perubahan product” untuk dokumen Retooling. AI Smart Search menambah pemahaman entity bisnis dan kemiripan makna tanpa mengganti pencarian normal.

## 3. Jenis AI yang digunakan

Jenis AI utamanya adalah Natural Language Processing dengan sentence embedding model dan semantic similarity. Sistem juga memakai aturan regex serta lookup database. Kombinasi aturan exact dan model neural ini disebut hybrid AI.

## 4. Perbedaan keyword search dan semantic search

Keyword search seperti mencari tulisan yang sama persis pada label rak. Semantic search mengubah query dan deskripsi metadata menjadi vector, lalu mencari vector yang arahnya paling mirip. Pencarian normal tetap tersedia sebagai fitur terpisah dan sebagai fallback lokal ketika service AI tidak siap.

## 5. NLP

Natural Language Processing atau NLP adalah teknik agar komputer dapat mengolah bahasa manusia. Pada sistem ini, NLP dipakai untuk membaca frasa tanggal, tipe dokumen, line, shift, type produk, posisi Axle, dan part number Brake.

## 6. Entity extraction

Entity extraction mengambil fakta terstruktur dari query. Contoh query `retooling line 2 shift 1 september 2026` dapat menghasilkan `document_type=Retooling`, `line=LINE_2`, `shift=1`, serta rentang tanggal 1–30 September 2026. Parser hanya mengeluarkan nilai yang ditemukan lewat pola yang dikenal atau lookup yang dikirim Laravel; nilai yang tidak dikenal tidak ditebak.

## 7. Embedding dengan analogi

Embedding seperti mengubah arti kalimat menjadi koordinat angka. Kalimat dengan arti yang berdekatan cenderung memperoleh koordinat yang berdekatan meskipun tidak menggunakan kata yang sama persis.

## 8. Vector

Vector adalah daftar angka, misalnya `[0.12, -0.30, 0.81, ...]`. Model multilingual menghasilkan vector berdimensi tetap untuk setiap query dan teks metadata. Angka tersebut bukan data bisnis baru dan tidak menggantikan record database.

## 9. Cosine similarity

Cosine similarity mengukur apakah arah dua vector hampir sama. Secara teknis:

```text
cosine(a, b) = (a · b) / (||a|| × ||b||)
```

Implementasi menormalisasi vector terlebih dahulu, sehingga dot product vector yang sudah ternormalisasi setara dengan cosine similarity. Nilainya disebut **skor relevansi**, bukan persentase akurasi.

## 10. Cara semantic similarity bekerja

Query diberi prefix E5 `query:`, sedangkan canonical metadata diberi prefix `passage:`. Model meng-encode keduanya, vector dinormalisasi, lalu NumPy menghitung seluruh skor kandidat secara batch. Urutan skor terbesar menjadi ranking semantic.

## 11. Mengapa semantic search disebut AI

Model sentence embedding adalah neural network yang telah belajar representasi bahasa dari data pelatihan. Sistem tidak sekadar memeriksa `LIKE`; model menghitung kedekatan makna dari representasi hasil pembelajaran. Aturan deterministic tetap dipakai untuk fakta bisnis yang tidak boleh samar.

## 12. Hybrid search

Alurnya adalah:

```text
Natural Language Query
        |
        v
Deterministic Entity Extraction
        |
        v
Authorized Database Candidate Filtering
        |
        v
Local Sentence Embedding
        |
        v
Normalized Cosine Similarity
        |
        v
Semantic Ranking
        |
        v
Laravel validates/intersects result IDs
        |
        v
UI
```

Filter terstruktur memperkecil kandidat secara exact. Model hanya meranking kandidat yang lolos.

## 13. Database sebagai source of truth

Dokumen, kategori, departemen, Retooling, Axle, Brake, line, type, tanggal, dan status aktif berasal dari MariaDB melalui Laravel. Python tidak membuat record dan tidak memiliki credential database. Jika ID tidak berada dalam kandidat Laravel, hasil itu dibuang walaupun dikembalikan oleh Python.

## 14. Mengapa tidak memakai chatbot atau LLM cloud

Tujuan fitur adalah retrieval dokumen, bukan menghasilkan jawaban bebas. Chatbot generatif menambah risiko halusinasi, kompleksitas, biaya, dan pengiriman data keluar. Sentence embedding lokal lebih sesuai untuk ranking semantic metadata dan konsepnya lebih mudah diaudit.

## 15. Mengapa AI dijalankan lokal

Kebijakan utama adalah data BMC tidak dikirim ke layanan AI eksternal. FastAPI bind ke `127.0.0.1`, model dibaca dari disk lokal, dan browser hanya berkomunikasi dengan Laravel. Internet/WAN tidak diperlukan pada runtime.

## 16. Manfaat keamanan dan privasi

Python menerima query, lookup bisnis, dan metadata kandidat minimum. Python tidak menerima password, cookie, token CSRF, header Authorization, file path absolut, isi PDF, gambar, atau credential MariaDB. Laravel mempertahankan authentication dan scope dokumen yang sudah ada.

## 17. Arsitektur BMC QualityHub ke AI

```text
Browser pengguna
      |
      | HTTPS/HTTP aplikasi lokal, session + CSRF
      v
Laravel BMC QualityHub
      |
      | HTTP server-side ke 127.0.0.1 saja
      v
FastAPI AI Smart Search
      |
      | local_files_only, offline environment
      v
Model multilingual-e5-small pada disk lokal
```

Tidak ada CORS AI karena browser tidak mengakses FastAPI. Python tidak dapat mengambil data database sendiri.

## 18. Alur query lengkap

Laravel memvalidasi query maksimal 500 karakter. Laravel meminta parser Python mengekstrak entity dengan tanggal acuan Asia/Jakarta dan lookup master lokal. Laravel membangun query kandidat dari dokumen aktif yang dapat dilihat pengguna, menerapkan entity exact, dan membentuk metadata. Python meranking kandidat. Laravel memvalidasi JSON, tipe ID, range skor, serta melakukan intersection ID sekali lagi sebelum membentuk URL detail dokumen.

```text
User: "cari dokumen retooling line 2 bulan september"
                         |
                         v
               Laravel Validation
                         |
                         v
               Entity Extraction
                         |
       +-----------------+----------------+
       |                 |                |
    Retooling          LINE_2         September
       |                 |                |
       +-----------------+----------------+
                         |
                         v
                DB Candidate Filter
                         |
                         v
                 Metadata Documents
                         |
                         v
                Local Embedding AI
                         |
                         v
                 Cosine Similarity
                         |
                         v
                Relevance Ranking
                         |
                         v
          Laravel ID Scope Validation
                         |
                         v
                   Search Results
```

## 19. Contoh Retooling

Query `perubahan product A ke product E di line 2` dapat mengenali jenis Retooling, `from_type=product_A`, `to_type=product_E`, dan `line=LINE_2`. Laravel memfilter snapshot/scan metadata exact. Canonical text memasukkan title, line, shift, from/to type, dan cycle time yang benar-benar tersimpan.

## 20. Contoh Axle

Query `axle assy posisi LH terbaru` mengenali jenis Axle Assy, posisi `LH`, dan `latest=true`. Kandidat memakai snapshot Axle historis bila tersedia. Setelah ranking, `latest` mengurutkan hasil yang relevan menggunakan `document_date` lalu `created_at`; semantic score tidak dianggap sebagai waktu.

## 21. Contoh Brake

Query `dokumen brake assy part number 402 502` mengenali jenis Brake Assy dan part number dari lookup database, termasuk bentuk penulisan dengan spasi atau garis miring. Part number hasil hanya berasal dari metadata kandidat nyata.

## 22. Date parser

Parser memakai tanggal referensi dari Laravel dalam timezone aplikasi `Asia/Jakarta`:

- `hari ini`: tanggal referensi.
- `kemarin`: satu hari sebelum tanggal referensi.
- `minggu lalu`: Senin sampai Minggu pada minggu kalender sebelumnya.
- `bulan september`: `month=9`; bila tahun tidak disebut, filter bulan berlaku lintas tahun.
- `september 2026`: rentang `2026-09-01` sampai `2026-09-30`.
- tanggal eksplisit yang didukung menghasilkan `date_from` dan `date_to` yang sama.

## 23. Filtering Line dan Product

Daftar alias tidak dikunci pada satu seed. Laravel mengirim lookup dari master database dan nilai historis yang relevan. Parser mencocokkan code maupun name secara case-insensitive, kemudian Laravel menerapkan hasil pada metadata dokumen. Karena filter terjadi di Laravel, Python tidak dapat memperluas akses.

## 24. Semantic ranking

Canonical text setiap kandidat dibangun dengan label yang eksplisit agar model memahami konteks, contohnya:

```text
Jenis Dokumen: Retooling. Judul: INSTRUKSI PERUBAHAN TYPE (RETOOLING).
Line: LINE_2. Shift: 1. Dari Type: product_A. Ke Type: product_E.
Cycle Time Dari: 25.00. Cycle Time Ke: 22.00. Tanggal: 2026-09-15.
```

Field kosong tidak dibuat-buat. Semua kandidat di-encode dalam batch dan diranking dengan operasi NumPy.

## 25. Jika AI mati

Laravel menangkap connection error, timeout, HTTP error, dan response malformed. Halaman Documents tetap dapat dibuka. UI menjelaskan bahwa AI lokal tidak tersedia dan menyediakan pencarian keyword lokal. Sistem tidak membuat skor atau hasil AI palsu.

## 26. Jika internet mati

Runtime tetap bekerja selama Laravel, MariaDB, FastAPI, folder model, dan jaringan lokal BMC masih tersedia. Offline environment mencegah library model mencari file ke internet. Cara demonstrasi rinci ada di `docs/AI_SMART_SEARCH_OFFLINE_TEST.md`.

## 27. Jika LAN client-server dicabut

Offline AI berarti tidak bergantung pada Internet/WAN atau cloud AI. Jika komputer pengguna berbeda dari server dan kabel LAN ke server dicabut, browser tentu tidak dapat membuka BMC QualityHub. Hal ini adalah putusnya koneksi aplikasi lokal, bukan kegagalan offline inference.

## 28. Model AI

Target awal adalah `intfloat/multilingual-e5-small`. Model ini multilingual, cocok untuk Bahasa Indonesia dan istilah Inggris yang bercampur pada metadata produksi, serta lebih ringan dibanding model besar. Arsitektur embedder berupa abstraction sehingga model lokal dapat diganti lewat konfigurasi tanpa mengubah parser atau Laravel.

## 29. Alasan memilih model

E5 dibuat untuk retrieval dengan pasangan query dan passage. Varian small lebih realistis untuk CPU dan RAM server terbatas. Meski demikian, kelayakan latency dan memory tetap harus diuji pada server tujuan setelah folder model tersedia; source code tidak menganggap keberadaan model sebagai fakta.

## 30. Lokasi model

Lokasi default yang direkomendasikan:

```text
D:\BMC_AI\Models\multilingual-e5-small
```

Path dikonfigurasi melalui environment service Python. Folder model tidak masuk repository dan tidak diletakkan di public web directory.

## 31. Jaminan tidak mengunduh model saat runtime

Service mengaktifkan `TRANSFORMERS_OFFLINE=1`, `HF_HUB_OFFLINE=1`, dan menonaktifkan telemetry Hugging Face. Loader menerima local path dan memanggil SentenceTransformer dengan `local_files_only=True`. Folder yang hilang menghasilkan state `model_loaded=false`; tidak ada fallback URL inference atau auto-download.

## 32. Python service

FastAPI memisahkan config, schema Pydantic, entity parser, canonical metadata builder, embedder abstraction, dan ranking. Model dimuat sekali saat startup. Endpoint:

- `GET /health`: status aman, mode lokal, offline mode, dan state model.
- `POST /parse`: entity extraction deterministic agar Laravel dapat memfilter database terlebih dahulu.
- `POST /search`: canonical text, embedding batch, cosine similarity, dan ranking.

Service default dijalankan pada `127.0.0.1:8765`.

## 33. Laravel service

Laravel mempunyai konfigurasi terpusat dan service HTTP khusus. URL hanya diterima jika host adalah loopback `127.0.0.1`, `localhost`, atau `::1`; scheme wajib HTTP lokal. Timeout dan jumlah hasil dibatasi. Controller hanya mengurus validasi/request-response, sedangkan pembentukan kandidat dan komunikasi AI berada dalam service terpisah.

## 34. API `/health`

Health response hanya memuat informasi aman seperti status, `mode=local`, `offline_mode=true`, label model, dan `model_loaded`. Absolute model path, environment secret, serta credential sistem tidak diekspos. Service dapat sehat sebagai proses tetapi belum ready untuk search ketika model belum dimuat.

## 35. API `/search`

Request berisi query, entity hasil parser, `top_k`, dan array candidate metadata. Response berupa JSON dengan query, intent, entity, model label, mode lokal, serta daftar `document_id` dan `semantic_score`. Endpoint tidak mengembalikan HTML atau record database lengkap.

## 36. Authorization

Route AI berada dalam middleware yang sama dengan Documents: security headers, authentication, active account, dan session freshness. Candidate query dimulai dari `Document::query()->where('is_active', true)`, sama seperti search existing. Laravel menyimpan set ID kandidat dan membuang ID asing, duplikat, tidak aktif, atau tidak valid dari response AI.

## 37. Candidate filtering dan metadata schema

Schema metadata V1 memuat hanya nilai yang relevan:

| Field | Sumber |
|---|---|
| `document_id`, `updated_at`, `document_date`, `created_at` | Document |
| `title`, `file_name`, `document_code`, `part_number`, `keywords` | Document |
| `category`, `department` | Category/Department |
| `scan_type`, `shift`, `line`, `from_type`, `to_type` | DocumentScanMetadata |
| `from_cycle_time`, `to_cycle_time` | DocumentRetooling snapshot |
| `axle_type`, `position`, `line` | Axle snapshot, legacy fallback bila snapshot tidak ada |
| `brake_part_number`, `line` | Brake snapshot, legacy fallback bila snapshot tidak ada |

Tidak ada `file_path`, isi file, uploader credential, token, atau session.

## 38. Embedding cache

V1 tidak memakai vector database atau persistent embedding cache agar arsitektur tetap sederhana dan metadata tidak menjadi stale. Ranking dihitung per request. Jika volume dan pengukuran latency kelak membuktikan cache perlu, key harus mencakup `document_id`, `updated_at`, model label, serta hash canonical metadata. Cache hanya optimasi lokal yang dapat dibangun ulang.

## 39. Limitasi versi pertama

- Model folder harus disiapkan secara manual dan legal oleh administrator.
- Kualitas semantic harus dievaluasi memakai query serta metadata BMC yang representatif.
- Inference CPU dipengaruhi jumlah kandidat dan tekanan RAM.
- Parser tanggal hanya mendukung bentuk yang didokumentasikan.
- V1 tidak membaca PDF/gambar, tidak melakukan OCR, tidak merangkum isi, dan tidak menjawab pertanyaan generatif.
- Query bebas yang tidak memiliki kandidat relevan tetap bergantung pada kualitas embedding dan threshold/ranking.

## 40. Pengembangan lanjutan

Tahap berikutnya hanya dilakukan setelah persetujuan keamanan dan pengukuran nyata. Pilihan yang masuk akal adalah evaluasi relevance berlabel, cache embedding lokal yang invalidasinya benar, sinonim bisnis terkelola, metrics latency tanpa menyimpan query rahasia, serta OCR/index isi dokumen yang melalui review privasi terpisah. Database dan Laravel tetap menjadi source of truth.

## Glosarium

| Istilah | Definisi singkat |
|---|---|
| AI | Sistem komputasi yang memakai model/algoritme untuk melakukan tugas yang membutuhkan pola atau pemahaman. |
| NLP | Pemrosesan bahasa manusia oleh komputer. |
| Embedding | Representasi makna teks sebagai vector angka. |
| Vector | Daftar angka dalam ruang berdimensi tetap. |
| Semantic Search | Pencarian berdasarkan kedekatan makna. |
| Cosine Similarity | Ukuran kesamaan arah dua vector. |
| Entity Extraction | Pengambilan fakta terstruktur dari kalimat. |
| Hybrid Search | Kombinasi filter exact dan ranking semantic. |
| Candidate | Dokumen authorized yang mungkin menjadi hasil. |
| Ranking | Pengurutan kandidat menurut relevansi. |
| Inference | Proses memakai model terlatih untuk menghasilkan embedding. |
| Model | Parameter neural yang telah dilatih dan dimuat dari disk lokal. |
| API | Kontrak komunikasi terstruktur antarproses. |
| Localhost | Komputer yang sama, umumnya `127.0.0.1`. |
| On-Premise | Berjalan pada infrastruktur internal organisasi. |
| Offline | Tidak membutuhkan Internet atau cloud pada runtime. |
| Source of Truth | Sumber data resmi yang menentukan fakta sistem. |
| Fallback | Jalur aman yang digunakan saat fitur utama tidak tersedia. |
