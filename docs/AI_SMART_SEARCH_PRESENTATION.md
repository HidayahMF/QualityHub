AI Smart Search — Presentation Summary

Executive bullets
- Local multilingual embedder: `intfloat/multilingual-e5-small` loaded from disk at `D:\BMC_AI\Models\multilingual-e5-small`.
- System runs fully offline: `TRANSFORMERS_OFFLINE=1`, `HF_HUB_OFFLINE=1`, `local_files_only=True`.
- FastAPI serves embeddings and ranking on loopback; Laravel remains gateway and authorization layer.
- Verified: model acquisition, embedding, cosine ranking, FastAPI `/search` endpoint, and Laravel integration tests.

Technical snapshot
- Model size: ~2.28 GB (includes PyTorch, safetensors, ONNX, OpenVINO exports and tokenizer files).
- Primary weight files present: `pytorch_model.bin`, `model.safetensors`.
- Alternate runtimes present: ONNX (`onnx/model.onnx`, `model_O4.onnx`, quantized onnx), OpenVINO.

Acceptance highlights
- Real model inference tested end-to-end using local FastAPI.
- Real semantic ranking validated: relevant retooling doc scored higher than unrelated candidate.
- Laravel integration tests covering AI behavior passed (subset and full suite).
# Catatan Presentasi AI Smart Search

## A. Naskah presentasi 3 menit

“Project saya adalah AI Smart Search untuk BMC QualityHub. Masalah awalnya, pencarian keyword mengharuskan pengguna mengetahui kata yang hampir sama dengan metadata. Pengguna produksi lebih alami mengetik, misalnya, ‘cari dokumen retooling line 2 bulan September’ atau ‘axle assy LH terbaru’.

AI yang saya gunakan bukan chatbot dan bukan generative AI. Sistem memakai hybrid search: parser deterministic mengambil entity bisnis seperti jenis dokumen, line, shift, product, posisi, part number, dan tanggal; kemudian sentence embedding model multilingual mengubah makna query serta metadata kandidat menjadi vector. Cosine similarity mengurutkan vector yang paling mirip.

Database tetap menjadi sumber kebenaran. Browser hanya berbicara dengan Laravel. Laravel melakukan authentication, mengambil dokumen aktif yang boleh dilihat pengguna, dan memfilter entity secara exact. Python FastAPI tidak memiliki password MariaDB. Python hanya menerima metadata kandidat minimum, lalu mengembalikan ID dan skor relevansi. Laravel memeriksa kembali bahwa setiap ID memang ada dalam kandidat authorized.

Seluruh inference berjalan lokal. Model dibaca dari folder lokal dengan `local_files_only`, offline environment diaktifkan, dan service bind ke `127.0.0.1`. Tidak ada OpenAI, Gemini, Claude, remote Hugging Face inference, atau fallback cloud. Jika Python atau model tidak tersedia, aplikasi tidak crash: pencarian keyword normal tetap dapat dipakai dan UI menjelaskan kondisi tersebut secara jujur.

Jadi nilai AI project ini adalah NLP multilingual dan neural semantic similarity, digabung dengan rule bisnis serta kontrol akses Laravel. Hasil tetap dokumen nyata, bukan jawaban yang dikarang.”

## B. Naskah presentasi 5 menit

Mulai dengan masalah pengguna: “Nama file di QualityHub tidak selalu sama dengan bahasa yang diingat pengguna. Keyword search mencari teks exact, sedangkan manusia sering mengingat maknanya.”

Berikan contoh `dokumen perubahan product A ke product E`. Parser mengenali konteks Retooling serta entity from/to dari value database. Laravel kemudian mengambil dokumen aktif yang sesuai. Setiap kandidat dibentuk menjadi kalimat metadata canonical, misalnya jenis dokumen, title, line, dari type, ke type, cycle time, dan tanggal.

Jelaskan embedding: “Model `multilingual-e5-small` mengubah query dan canonical metadata menjadi vector angka. Query memakai prefix `query:` dan dokumen memakai `passage:` sesuai pola retrieval E5. Vector dinormalisasi. Dot product vector ternormalisasi menjadi cosine similarity, lalu skor terbesar berada di atas. Angka ini adalah skor relevansi, bukan akurasi.”

Jelaskan mengapa hybrid: “Line dan part number tidak boleh ditafsirkan bebas oleh model. Rule exact menangani fakta bisnis. Embedding membantu saat kata berbeda tetapi maknanya berdekatan. Database tidak pernah diganti oleh output model.”

Jelaskan keamanan: “Route berada di middleware authentication dan active-account yang sama dengan Documents. Python tidak mengakses database. Laravel hanya mengirim kandidat authorized, lalu meng-intersect ID output untuk mencegah ID injection. V1 hanya metadata; isi PDF dan gambar tidak diproses.”

Jelaskan offline: “FastAPI hanya pada localhost dan model berada pada disk lokal. Environment library dipaksa offline. Ketika WAN diputus, Laravel, MariaDB, dan Python di jaringan internal masih bekerja. Jika LAN antara client dan server diputus, tentu web server juga tidak dapat diakses; itu berbeda dari ketergantungan cloud.”

Tutup dengan failure handling dan keterbatasan: “Model belum boleh diunduh diam-diam. Health menunjukkan `model_loaded=false` jika folder belum siap. Unit test menggunakan fake embedder deterministic agar parser, canonical text, cosine ranking, validasi, authorization, timeout, dan malformed response dapat diuji tanpa Internet.”

## C. Naskah presentasi 10 menit

### Menit 0–1: konteks

“BMC QualityHub menyimpan dokumen QC termasuk Retooling, Axle Assy, dan Brake Assy. Search lama tetap berguna, tetapi membutuhkan kata yang cukup persis. Tujuan fitur baru adalah pencarian bahasa sehari-hari yang tetap aman untuk data perusahaan.”

### Menit 1–2: pilihan pendekatan

“Saya tidak memilih chatbot. Tugasnya adalah information retrieval, sehingga sentence embedding lebih tepat. Model target multilingual karena query Bahasa Indonesia bercampur istilah produksi berbahasa Inggris.”

### Menit 2–4: entity extraction

Tampilkan query `cari dokumen retooling line 2 bulan september`. Uraikan:

```text
document_type = retooling
line          = LINE_2
month         = 9
```

“Entity berasal dari regex, kamus bulan, dan lookup master database. Parser tidak membuat nilai yang tidak dikenal. Untuk ‘minggu lalu’, definisinya Senin–Minggu kalender sebelumnya dalam timezone Asia/Jakarta.”

### Menit 4–6: embedding dan cosine similarity

“Canonical metadata mengubah record yang terpisah menjadi kalimat yang konsisten. E5 menghasilkan vector untuk `query:` dan `passage:`. Normalisasi membagi vector dengan panjangnya. Cosine similarity membandingkan arah vector. Dua kalimat dapat dekat walaupun kata persisnya berbeda.”

Gambar sederhana:

```text
query ----->
             sudut kecil = makna lebih mirip
document -->
```

### Menit 6–7: hybrid flow

“Parser exact dilakukan sebelum semantic ranking. Laravel membatasi kandidat Retooling, LINE_2, dan bulan 9. AI hanya meranking sisa kandidat. Untuk kata ‘terbaru’, urutan tanggal dikerjakan terpisah; semantic score tidak disalahartikan sebagai waktu.”

### Menit 7–8: keamanan

“Browser tidak tahu port Python. Laravel adalah gateway. Python tidak punya DB credential. Hanya metadata minimum dikirim. Setelah Python mengembalikan ID, Laravel melakukan intersection terhadap kandidat awal. ID dokumen archived atau ID yang disisipkan secara malicious dibuang.”

### Menit 8–9: offline dan kegagalan

“Loader hanya menerima local path dan `local_files_only=true`. Runtime mengaktifkan offline flags dan tidak mempunyai endpoint cloud. `/health` tetap menjelaskan state jika model belum ada. Timeout atau JSON rusak tidak menghasilkan error halaman; normal keyword search tetap ada.”

### Menit 9–10: pengujian dan limitasi

“Python test memakai fake embedder untuk menguji cosine ranking secara deterministic tanpa download. Laravel test memakai `Http::fake`, jadi tidak perlu proses Python dan dapat menguji data minimization, scope, ID injection, timeout, unavailable, serta malformed response. Keterbatasan V1 adalah hanya metadata dan perlu evaluasi kualitas/latency nyata setelah model disediakan.”

## D. Tanya-jawab dosen

### “Apa AI-nya?”

AI-nya adalah neural multilingual sentence embedding yang memetakan makna query dan metadata ke vector, lalu semantic similarity meranking dokumen. Entity parser deterministic melengkapinya untuk fakta bisnis exact, sehingga keseluruhannya merupakan hybrid AI search.

### “Ini bukannya cuma LIKE query?”

Tidak. `LIKE` hanya mencari pola teks. Di sini model neural menghasilkan vector makna dan cosine similarity membandingkannya. `LIKE` tetap ada pada pencarian normal/fallback, sedangkan AI mode memakai embedding setelah filter structured.

### “Kenapa disebut semantic search?”

Karena ranking membandingkan representasi makna kalimat, bukan hanya kecocokan karakter. Query dan metadata dengan kata berbeda dapat memiliki vector berdekatan.

### “Embedding itu apa?”

Embedding adalah hasil neural model berupa daftar angka yang mewakili ciri semantik kalimat. Analogi sederhananya adalah koordinat makna.

### “Cosine similarity itu apa?”

Cosine similarity mengukur kesamaan arah dua vector. Nilai lebih tinggi berarti arah representasinya lebih serupa. Dalam implementasi, vector dinormalisasi dan dihitung secara batch dengan NumPy.

### “Kenapa pakai model multilingual?”

Pengguna memakai Bahasa Indonesia, sementara nama proses seperti Retooling, Brake Assy, product, line, dan part number memakai istilah campuran. Model multilingual dirancang menangani lebih dari satu bahasa dalam ruang vector yang sama.

### “Kenapa tidak pakai ChatGPT?”

Use case utamanya retrieval, sehingga model generatif tidak diperlukan. Sentence embedding lebih kecil, dapat berjalan CPU lokal, tidak menghasilkan jawaban bebas, dan lebih sesuai dengan larangan pengiriman data ke cloud.

### “Apakah data perusahaan keluar?”

Tidak pada arsitektur runtime ini. Laravel mengirim metadata kandidat ke service pada `127.0.0.1`. Tidak ada cloud inference atau telemetry yang sengaja diaktifkan. Model dibaca dari folder lokal.

### “Kalau internet mati bagaimana?”

AI tetap bekerja jika model sudah lokal dan service internal hidup. Laravel, MariaDB, dan Python hanya membutuhkan server/jaringan lokal.

### “Kalau server AI mati bagaimana?”

Laravel menangkap connection error atau timeout. UI menampilkan bahwa AI lokal tidak tersedia, lalu pengguna tetap dapat memakai normal keyword search. Tidak ada hasil AI palsu.

### “Apa bedanya keyword search dengan AI Smart Search?”

Keyword search memeriksa kata/pola yang sama pada kolom. AI Smart Search mengekstrak entity exact dan meranking makna metadata memakai embedding. Keduanya tersedia agar pengguna dapat memilih.

### “Bagaimana AI tahu LINE_2?”

Laravel mengirim lookup code dan alias line dari database. Parser mencocokkan frasa `line 2` dengan nilai canonical `LINE_2`. Laravel yang menerapkan filter database, jadi model tidak menebak line.

### “Bagaimana AI tahu bulan September?”

Kamus bulan deterministic memetakan `september` ke angka 9. Jika ada tahun, parser membuat batas awal/akhir bulan. Tanpa tahun, Laravel memfilter nomor bulan sesuai definisi V1.

### “Apakah AI bisa halusinasi?”

Embedding hanya memberi skor, bukan membuat dokumen. Parser tidak mengeluarkan entity yang tidak ditemukan. Database menentukan kandidat dan Laravel memvalidasi ID akhir. Desain ini membatasi ruang halusinasi secara kuat.

### “Apakah AI langsung mengakses database?”

Tidak. Python sama sekali tidak memiliki credential MariaDB. Laravel melakukan seluruh query, authorization, filter, dan hydration hasil.

### “Kenapa memakai Python?”

Ekosistem inference sentence-transformers dan operasi vector NumPy matang serta mudah dipelajari. FastAPI memberi kontrak JSON kecil. Laravel tetap menangani domain aplikasi dan keamanan.

### “Laravel berperan sebagai apa?”

Laravel adalah gateway, validator, authorization layer, database owner, candidate builder, dan renderer UI. Python hanya memahami query dan meranking metadata yang sudah dibatasi.

### “Apa kelemahan sistem ini?”

Model membutuhkan RAM dan latency CPU, kualitas ranking perlu diukur dengan contoh BMC, parser hanya mengenali format yang didukung, dan V1 tidak memahami isi file. Ketersediaan model lokal juga menjadi prasyarat operasional.

### “Bagaimana pengembangannya ke depan?”

Kembangkan berdasarkan data evaluasi: sinonim domain, relevance benchmark, cache lokal dengan invalidasi metadata, dan observability tanpa menyimpan data rahasia. OCR atau indeks isi dokumen memerlukan review privasi dan persetujuan terpisah.

## E. Skenario demo

### Demo 1 — Retooling, line, bulan

Ketik `cari dokumen retooling line 2 bulan september`. Tunjukkan entity `retooling`, `LINE_2`, `month=9`, lalu hasil aktual. Jelaskan filter exact dilakukan sebelum ranking.

### Demo 2 — perubahan product

Ketik `dokumen perubahan product A ke product E`. Tunjukkan `from_type` dan `to_type` dari lookup. Bandingkan dengan keyword berbeda yang mempunyai maksud sama.

### Demo 3 — Brake Assy

Ketik `cari brake assy 402 502`. Tunjukkan canonical part number yang dikenali dan hasil yang metadata Brake-nya memang cocok.

### Demo 4 — Axle terbaru

Ketik `axle assy LH terbaru`. Tunjukkan entity posisi `LH` dan `sort=latest`. Jelaskan bahwa tanggal mengatur recency, sedangkan embedding mengatur relevansi makna.

### Demo 5 — makna mirip

Siapkan dua query dengan kata berbeda, misalnya `instruksi ganti tipe produksi` dan title Retooling canonical. Tunjukkan normal keyword dapat lebih terbatas, sementara embedding dapat memberi ranking semantic ketika kandidat exact masih sesuai.

### Demo 6 — WAN dimatikan

Jalankan search saat Internet aktif, catat hasil, matikan Wi-Fi/WAN tanpa memutus LAN/localhost, lalu ulangi query yang sama. Tunjukkan `/health` tetap `mode=local`, `offline_mode=true`, dan hasil tetap ada. Gunakan langkah rinci pada `AI_SMART_SEARCH_OFFLINE_TEST.md`.
