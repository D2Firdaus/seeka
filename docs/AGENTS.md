# AGENTS.md — Modul Pencarian & Pencocokan (Lost & Found Kampus)

Panduan untuk AI coding agent dan kontributor yang mengerjakan modul **pencarian** dan **pencocokan untuk notifikasi**.
Bagian bertanda **[PLACEHOLDER]** adalah nilai awal yang harus ditala dengan data uji, bukan nilai final.
Bagian bertanda **[RENCANA]** belum diimplementasikan di kode.

---

## 1. Konteks

- Sistem Lost & Found kampus berbasis **Laravel**. Algoritma pencarian dan pencocokan berjalan di **layanan Python terpisah (FastAPI)** yang dipanggil Laravel lewat HTTP. **[RENCANA]**
- Database MySQL/MariaDB. **Primary key berupa kode string**, bukan angka:
  `USR001` (users), `CTG001` (categories), `LOC001` (locations), `ITM001` (items), `IMG001` (item_images),
  `EMB001` (item_embeddings), `MTC001` (item_matches), `CLM001` (claims), `HND001` (handovers),
  `LOG001` (item_logs), `DSP001` (disputes).
- Nama PK deskriptif (`item_id`, `claim_id`, `user_id`, dst.), bukan `id`. Semua PK dan FK bertipe `varchar(10)`.
- Tabel yang relevan untuk modul ini: `items`, `categories`, `locations`, `claims`, `item_embeddings`, `item_matches`, `users`.
- Penjelasan seluruh tabel dan alur data: **`DATABASE.md`**. Sumber kebenaran skema: `lost_and_found.sql` dan folder `database/migrations/`.

## 2. Ruang lingkup dan prioritas

**A. Pencarian**: pengguna mengetik kata kunci (judul, opsional deskripsi, kategori, lokasi, tanggal) dan mendapat daftar laporan yang paling relevan.

**B. Pencocokan untuk notifikasi**: saat ada laporan baru, sistem mencari pasangan yang cocok dan memberi tahu pemilik laporan lewat email (WhatsApp menyusul).

Kedua fitur memakai **mesin yang sama**. Perbedaannya: pencarian mengutamakan *recall* (hasil yang kurang relevan boleh tampil di bawah), notifikasi mengutamakan *presisi* (salah kirim lebih merugikan daripada terlewat).

### Lima skenario penelitian

Penelitian membandingkan **tepat lima skenario**, tidak lebih:

| # | Skenario | Reranker + threshold |
|---|---|---|
| 1 | baseline: Jaro-Winkler (JW) + metadata lunak | tidak |
| 2 | 1 + BGE-M3 sparse | tidak |
| 3 | 1 + BGE-M3 dense | tidak |
| 4 | 1 + BGE-M3 sparse + dense | tidak |
| 5 | 1 + 2 + 3 (JW + metadata + sparse + dense) + reranking + threshold | **ya** |

Hanya skenario 5 yang punya threshold, jadi hanya skenario 5 yang menghasilkan notifikasi.

### Cakupan implementasi saat ini

Karena tugas memprioritaskan **website Laravel**, yang diimplementasikan di website sekarang adalah **skenario 3 + reranking + threshold**, yaitu skenario 5 *tanpa sparse*:

```text
tahap 1  JW (judul) + metadata lunak + BGE-M3 dense  -> ambil top-K
tahap 2  reranker atas pasangan (query, kandidat)    -> skor 0-1
tahap 3  threshold pada skor reranker                -> notifikasi, atau "belum ada yang cocok"
```

- **BGE-M3 sparse belum diimplementasikan di website.** Jangan membangun sparse, kolom `vector_type`, atau struktur lain untuk sparse sebelum diminta. Kolom `item_matches.bgem3_sparse` sudah ada dan sengaja bernilai NULL sampai sparse ditambahkan.
- Skenario 1 sampai 4 **tidak dijalankan di website**. Semuanya dibandingkan **offline di skrip evaluasi** pada data uji yang sama (bagian 10).
- Pencarian FULLTEXT MySQL (`items`) tetap tersedia sebagai cadangan bila layanan Python tidak tersedia. **[RENCANA]**

## 3. Aturan yang tidak boleh dilanggar

1. **`items.hidden_detail` tidak boleh masuk ke mana pun** selain verifikasi klaim oleh admin. Artinya: bukan bagian teks untuk JW, embedding BGE-M3, maupun **input reranker**; bukan fitur pencocokan, bukan bagian respons API pencarian, bukan isi notifikasi, dan tidak ditulis ke log.
   Alasan: notifikasi bisa dipakai sebagai "oracle" untuk menebak ciri rahasia lewat laporan berulang.
2. **Notifikasi tidak boleh memuat** kontak penemu, nomor telepon, atau `hidden_detail`. Isinya hanya info publik (judul, kategori, lokasi, tanggal, foto kecil opsional) dan tautan ke halaman barang.
3. Kalimat notifikasi bersifat perkiraan ("mungkin cocok dengan laporanmu"), bukan klaim pasti. Kepemilikan tetap dibuktikan lewat login dan alur klaim.
4. Hanya laporan `status = 'open'` dan belum di-soft-delete (`deleted_at IS NULL`) yang boleh jadi kandidat.
5. Satu pasangan (lost, found) **tidak boleh diberi notifikasi dua kali** (`UNIQUE` di `item_matches`). Pengguna tidak boleh diberi tahu tentang laporannya sendiri.
6. Jangan menyetel ambang batas atau bobot memakai **data uji (test set)**. Penalaan hanya di data validasi.
7. Jangan mengubah skema database tanpa: migration baru, pembaruan `lost_and_found.sql`, pembaruan diagram ERD, dan pembaruan `DATABASE.md`.

## 4. Spesifikasi pencarian

**Filter keras (hanya ini):** `status = 'open'`, `deleted_at IS NULL`, dan jenis laporan yang sesuai (pencarian barang temuan → `type = 'found'`).
Kategori, lokasi, dan tanggal **bukan filter keras**, hanya skor, karena pengguna sering salah memilih kategori.

### Tahap 1: komponen skor dan penggabungan

| Komponen | Cara | Masukan |
|---|---|---|
| `jw` | Jaro-Winkler | **judul** yang dinormalisasi (lowercase, trim, hapus tanda baca). Hanya judul: JW dirancang untuk string pendek, dan sensitif terhadap awalan dan urutan kata, jadi tidak cocok untuk deskripsi panjang. |
| `metadata` | skor lunak | kategori sama, lokasi sama, peluruhan tanggal |
| `dense` | cosine BGE-M3 dense (1024 dimensi) | judul + deskripsi. Embedding dokumen dihitung dan disimpan di `item_embeddings`. |
| `sparse` | skor lexical weights BGE-M3 | **[RENCANA]** skenario 2, 4, dan 5 |

- **Penggabungan:** jumlah berbobot dari skor tiap komponen yang sudah dinormalisasi (min-max per query di antara kandidat). Komponen yang tidak aktif atau tidak diisi pengguna dibuang, dan **bobot sisanya dinormalkan ulang**. Tidak ada percabangan `if` per kombinasi.
- Skor `dense` adalah cosine, sedangkan skor `sparse` bukan cosine. Jangan menjumlahkan keduanya tanpa normalisasi.
- **Tanggal:** `peluruhan_tanggal` menurun terhadap selisih hari. Barang yang ditemukan jauh *sebelum* laporan hilang dibuat diberi nilai mendekati 0.
- Ambil `candidate_k` kandidat teratas. Metrik yang dikejar di tahap ini adalah **Recall@K**.

Pada skenario 1 sampai 4, peringkat tahap 1 adalah hasil akhir.

### Tahap 2: reranker (hanya skenario 5)

- Model: `BAAI/bge-reranker-v2-m3` (cross-encoder multilingual). Skor dinormalisasi dengan sigmoid ke rentang 0 sampai 1.
- Masukan: pasangan teks (laporan pembuat query, kandidat) berisi judul dan deskripsi. **Tidak pernah `hidden_detail`.**
- Reranker hanya dijalankan pada `candidate_k` kandidat, bukan seluruh korpus, dan lewat queue untuk notifikasi.
- Reranker tidak melihat metadata kecuali dimasukkan sebagai teks. Cara memakai metadata di sini masih keputusan terbuka (bagian 12).

### Tahap 3: threshold (hanya skenario 5)

- Pasangan dianggap cocok bila `skor reranker ≥ threshold`. Threshold dipilih di **data validasi**.
- Bila tidak ada kandidat yang lolos, kembalikan hasil kosong. UI menampilkan "belum ada yang cocok" dan tidak memaksa menampilkan peringkat teratas.

## 5. Spesifikasi pencocokan untuk notifikasi

**Pemicu (dua arah):**

| Kejadian | Cari di | Aksi |
|---|---|---|
| Laporan *found* baru | laporan *lost* yang `open` | beri notifikasi ke pemilik laporan yang cocok |
| Laporan *lost* baru | laporan *found* yang `open` | tampilkan saran di formulir; email sebagai pelengkap |

**Alur untuk satu laporan baru:**

1. **Kandidat:** laporan lawan jenis yang `open`, dengan toleransi tanggal (lihat bagian 4).
2. **Tahap 1:** hitung JW + metadata + dense (+ sparse bila sudah ada), gabungkan, ambil `candidate_k` teratas. Teks laporan baru menjadi query.
3. **Tahap 2:** reranker menghitung skor tiap kandidat.
4. **Tahap 3:** ambil paling banyak `top_n` kandidat dengan `skor reranker ≥ threshold`.
5. **Catat:** `INSERT` ke `item_matches` (pasangan sudah ada → lewati) dengan seluruh komponen skor dan `pipeline_version`. **Hanya pasangan yang lolos threshold yang dicatat.**
6. **Kirim:** buat queue job untuk email. Setelah terkirim, set `status = 'notified'` dan `notified_at`.
7. **Berhenti** mengirim bila laporan sudah `closed`/`returned`, atau laporan hilangnya sudah lebih tua dari batas usia yang ditetapkan.

**Pembatasan:** maksimum notifikasi per pengguna per hari, dan hormati preferensi kanal pengguna (email, WhatsApp, atau keduanya).

## 6. Konfigurasi (satu tempat, tanpa angka tersebar di kode)

```yaml
pipeline:
  scenario: s5                 # s1 | s2 | s3 | s4 | s5; hanya s5 memakai reranker dan threshold
  disabled_components: [sparse]  # website sementara: sparse belum diimplementasikan.
                                 # Skrip evaluasi mengosongkan daftar ini untuk s2, s4, s5 penuh.

scenarios:                     # komponen per skenario (tetap, bukan untuk ditala)
  s1: { components: [jw, metadata] }
  s2: { components: [jw, metadata, sparse] }
  s3: { components: [jw, metadata, dense] }
  s4: { components: [jw, metadata, sparse, dense] }
  s5: { components: [jw, metadata, sparse, dense], rerank: true, threshold: true }

retrieval:
  candidate_k: 30              # [PLACEHOLDER] jumlah kandidat yang diteruskan ke tahap 2
  normalize: minmax_per_query
  weights:                     # [PLACEHOLDER] ditala di data validasi; dinormalkan ulang untuk komponen aktif
    jw: 0.3
    metadata: 0.2
    dense: 0.3
    sparse: 0.2
  date_decay_half_life_days: 7 # [PLACEHOLDER]

rerank:
  model: BAAI/bge-reranker-v2-m3

notify:
  threshold: 0.50              # [PLACEHOLDER] pada skor reranker (sigmoid 0-1); ditala di data validasi
  top_n: 3
  max_per_day_per_user: 5      # [PLACEHOLDER]
  max_age_days_lost_report: 90 # [PLACEHOLDER]
```

`pipeline_version` yang ditulis ke `item_matches` merangkum skenario, model, bobot, dan threshold yang aktif saat baris dibuat.

## 7. Database

Struktur lengkap, relasi, enum, dan alur data ada di **`DATABASE.md`** (sumber kebenaran: `database/migrations/`). Yang relevan untuk modul ini:

- `item_embeddings`: cache vektor dense BGE-M3 per laporan per model (`UNIQUE (item_id, model_name)`, `text_hash` untuk deteksi teks berubah). Vektor 1024 dimensi float32 = 4096 byte.
- `item_matches`: hanya pasangan yang lolos threshold. Kolom: `jw_score`, `metadata_score`, `bgem3_dense`, `bgem3_sparse` (NULL sampai sparse ada), `bge_reranker` (skor akhir: urutan dan dasar threshold), `pipeline_version`, `status`, `notified_at`. `UNIQUE (lost_item_id, found_item_id)` mencegah notifikasi ganda.
- Tidak ada kolom skor gabungan tahap 1 maupun skor skenario 4. Nilainya dapat dihitung ulang dari komponen dan bobot di `pipeline_version`.
- `claims.lost_item_id`: pasangan (lost, found) yang terbukti benar; sumber data uji evaluasi.
- `users.notify_email`, `users.notify_whatsapp`: preferensi kanal notifikasi. Kirim email hanya bila `email_verified_at` terisi.
- `notifications`: `notifiable_id` bertipe string (bukan morph angka bawaan Laravel).
- Membuat ID baru memakai trait `HasCodeId` dan **harus di dalam `DB::transaction(...)`**. Awalan kode: lihat `DATABASE.md` bagian 2.
- Gunakan `item_id` string apa adanya sebagai kunci dokumen di JSON antar layanan. Untuk data berskala kampus, cosine langsung dengan NumPy sudah cukup; FAISS tidak wajib.
- Perubahan skema mengikuti daftar periksa di `DATABASE.md` bagian 11.

## 8. Arsitektur dan struktur yang disarankan

```text
laravel/                    # aplikasi web, queue, notifikasi
  app/Jobs/MatchNewItem.php         # dipanggil saat laporan dibuat
  app/Notifications/PossibleMatch.php
search-service/             # Python / FastAPI
  app/config.py             # baca konfigurasi bagian 6
  app/preprocess.py         # normalisasi judul
  app/jw.py                 # Jaro-Winkler
  app/dense.py              # embedding dense BGE-M3 (hitung dan muat)
  app/sparse.py             # BGE-M3 sparse            [RENCANA]
  app/scoring.py            # skor metadata, normalisasi, penggabungan berbobot
  app/reranker.py           # bge-reranker-v2-m3
  app/gate.py               # threshold dan aturan notifikasi
  app/main.py               # endpoint
  eval/                     # skrip evaluasi lima skenario (offline)
  tests/
```

Endpoint yang diusulkan: `POST /search`, `POST /match` (satu laporan baru → kandidat + keputusan), `POST /index` (upsert embedding saat laporan dibuat/diubah).
Fungsi penggabungan skor dan gerbang ditulis sebagai **fungsi murni** (tanpa akses database langsung) agar mudah diuji.
Setiap tahap bisa dinyalakan atau dimatikan lewat konfigurasi (bagian 6), sehingga skenario 1 sampai 5 dijalankan dengan kode yang sama.

## 9. Pengujian

Perintah (sesuaikan dengan proyek): `php artisan test` untuk Laravel, `pytest` untuk layanan Python.

Wajib ada:

- Unit test untuk normalisasi judul, JW, penggabungan berbobot (komponen nonaktif → bobot dinormalkan ulang), dan gerbang threshold.
- **Tes privasi:** pastikan `hidden_detail` tidak muncul di payload respons pencarian, input reranker, isi email, maupun log.
- **Idempotensi:** memproses laporan yang sama dua kali tidak membuat dua baris `item_matches` dan tidak mengirim dua email.
- Kasus tepi: judul kosong, tidak ada kandidat, satu kandidat saja, query typo, laporan tanpa deskripsi, pengguna mencocokkan dengan laporannya sendiri, tidak ada kandidat yang lolos threshold.
- Tes bahwa bobot dan threshold di konfigurasi benar-benar dipakai (bukan angka yang di-hardcode).
- Tes bahwa mengubah `pipeline.scenario` mengubah komponen yang aktif (s1 tidak memanggil dense maupun reranker).

## 10. Evaluasi (untuk jurnal)

**Data uji:** pasangan laporan hilang dan ditemukan yang benar. Bagi menjadi train/validasi/uji dengan seed tetap. Penalaan bobot dan threshold hanya di data validasi.
**Sertakan query tanpa pasangan** (laporan hilang yang barangnya tidak pernah ditemukan). Tanpa itu tidak ada *false positive* dan threshold tidak bisa diuji.

**Skenario yang dibandingkan:** kelima skenario di bagian 2, semuanya dijalankan **offline** pada data uji yang sama. Hasil ditulis ke CSV dan dapat dijalankan ulang dari satu perintah.

| Perbandingan | Yang diukur |
|---|---|
| 1 vs 2 | kontribusi sparse |
| 1 vs 3 | kontribusi dense |
| 2, 3 vs 4 | kontribusi menggabungkan sparse dan dense |
| 4 vs 5 | kontribusi reranker + threshold |

**Metrik:**
- Skenario 1 sampai 5: Precision@k, Recall@k, MRR.
- Tahap 1: Recall@K dengan K = `candidate_k`.
- Skenario 5 saja: Precision dan Recall pada threshold, kurva Precision-Recall, dan persentase laporan yang mendapat setidaknya satu notifikasi benar.

## 11. Konvensi

- Identifier kode dan komentar teknis dalam bahasa Inggris. Teks yang tampil ke pengguna (UI, email) dalam bahasa Indonesia.
- Jangan menaruh angka ambang atau bobot di dalam kode. Semuanya lewat konfigurasi (bagian 6).
- Jangan menambah dependensi baru tanpa mencatat alasannya di PR atau commit message.
- Perubahan pada rumus skor, threshold, atau data yang masuk ke notifikasi: jelaskan alasannya dan hasil evaluasinya.

## 12. Keputusan yang masih terbuka

- Nilai akhir `candidate_k`, bobot komponen, `date_decay_half_life_days`, dan `threshold` (ditala dari data validasi).
- Metode normalisasi skor tahap 1 (min-max per query adalah default awal) dan metode penggabungan alternatif sebagai pembanding.
- Cara metadata dipakai terhadap reranker: dimasukkan sebagai teks di pasangan (kategori, lokasi), digabung setelah rerank, atau sebagai aturan terpisah (terutama tanggal). Perlu diuji.
- Cara memilih threshold: presisi target atau F-beta pada data validasi.
- Panjang masukan maksimum untuk reranker dan BGE-M3 pada teks laporan.
- Penyimpanan embedding: BLOB di database (saat ini) atau file `.npy`.
- Pencocokan metadata lokasi: cocok persis saja, atau ada kedekatan antar gedung (butuh data tambahan).
- Kanal WhatsApp: gateway yang dipakai, biaya, dan aturannya. Tahap awal cukup email dan notifikasi di dalam aplikasi.
- Saat sparse ditambahkan: bentuk penyimpanan sparse (kemungkinan perlu kolom `vector_type` di `item_embeddings`).
