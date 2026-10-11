# DATABASE.md — Basis Data Lost & Found Kampus

Dokumen ini menjelaskan **untuk apa tiap tabel ada** dan **bagaimana data mengalir**, supaya agent atau kontributor lain tidak perlu menebak.
Sumber kebenaran struktur: `database/migrations/` dan `database/lost_and_found.sql`. Diagram: ERD draw.io (notasi crow's foot).
Panduan modul pencarian dan pencocokan ada di `AGENTS.md`.

---

## 1. Tujuan sistem

Sistem membantu civitas kampus melaporkan barang **hilang** (`lost`) dan barang **ditemukan** (`found`), mencocokkan keduanya, lalu mengembalikan barang ke pemilik yang sah lewat klaim, verifikasi admin, dan serah terima yang tercatat.

Database mendukung empat kebutuhan:

1. **Pelaporan**: siapa melaporkan apa, kapan, di mana (`users`, `items`, `categories`, `locations`, `item_images`).
2. **Pencarian dan pencocokan otomatis**: data teks dan metadata untuk JW + BGE-M3 + reranker, serta hasil pencocokan dan notifikasinya (`item_embeddings`, `item_matches`, `notifications`).
3. **Pengembalian yang aman**: klaim, verifikasi, serah terima, dan sanggahan (`claims`, `handovers`, `disputes`).
4. **Audit**: riwayat perubahan status (`item_logs`).

## 2. Konvensi

- **PK berupa kode string berurutan**, bukan angka: `USR001`, `ITM001`, `CLM001`, dst. Semua PK dan FK bertipe `varchar(10)`.
- **Nama PK deskriptif** (`item_id`, `claim_id`), bukan `id`.
- **FK diberi nama sesuai peran** bila satu tabel merujuk tabel yang sama lebih dari sekali (`claimant_id` dan `verified_by` sama-sama merujuk `users.user_id`; `lost_item_id` dan `found_item_id` sama-sama merujuk `items.item_id`).
- Kode dibuat oleh trait `HasCodeId` di aplikasi (bukan oleh database). **Pembuatan data baru harus di dalam `DB::transaction(...)`.**
- Semua tabel proyek: InnoDB, `utf8mb4`, ber-`created_at`/`updated_at` kecuali `item_logs` (hanya `created_at`, karena tidak pernah diubah).
- `items` memakai **soft delete** (`deleted_at`). Tabel lain dihapus permanen atau mengikuti aturan `ON DELETE` di bagian 5.

| Tabel | Awalan kode | Contoh |
|---|---|---|
| users | USR | USR001 |
| categories | CTG | CTG001 |
| locations | LOC | LOC001 |
| items | ITM | ITM001 |
| item_images | IMG | IMG001 |
| item_embeddings | EMB | EMB001 |
| item_matches | MTC | MTC001 |
| claims | CLM | CLM001 |
| handovers | HND | HND001 |
| item_logs | LOG | LOG001 |
| disputes | DSP | DSP001 |

## 3. Peta tabel dan fungsinya

| Tabel | Fungsi |
|---|---|
| `users` | Akun semua pengguna (mahasiswa, dosen, staf, admin) dan preferensi kanal notifikasi. |
| `categories` | Daftar jenis barang (elektronik, tas, kunci, ...). Tabel referensi. |
| `locations` | Daftar lokasi kampus (gedung, perpustakaan, kantin, ...). Tabel referensi. |
| `items` | **Tabel inti.** Satu baris = satu laporan, baik hilang maupun ditemukan, dibedakan kolom `type`. |
| `item_images` | Foto-foto sebuah laporan (satu laporan bisa banyak foto). |
| `item_embeddings` | Cache vektor dense BGE-M3 per laporan per model, agar tidak dihitung ulang. |
| `item_matches` | Pasangan (laporan hilang, laporan ditemukan) yang **lolos threshold**, beserta komponen skor dan status notifikasi. |
| `claims` | Pengajuan klaim kepemilikan atas barang yang ditemukan, beserta bukti dan hasil verifikasi. |
| `handovers` | Bukti serah terima fisik barang setelah klaim disetujui. |
| `disputes` | Sanggahan pihak lain terhadap klaim yang mencurigakan. |
| `item_logs` | Audit trail perubahan status laporan. Hanya di-insert, tidak diedit. |
| `notifications` | Notifikasi di dalam aplikasi (tabel framework Laravel, dengan `notifiable_id` bertipe string). |

Tabel framework lain yang ikut: `password_reset_tokens`, `sessions` (kolom `user_id`-nya string), serta `jobs` dan `failed_jobs` bila antrian memakai database.

## 4. Penjelasan tabel

**users**
- `nim_nip` (unik): identitas kampus, mencegah akun palsu dan membantu verifikasi.
- `role`: `admin` atau `user`; menentukan hak verifikasi klaim dan serah terima.
- `email_verified_at`: email notifikasi hanya dikirim ke alamat yang sudah terverifikasi.
- `notify_email`, `notify_whatsapp`: preferensi kanal notifikasi per pengguna. `phone` dipakai kanal WhatsApp.

**items**
- `type` (`lost`/`found`): membedakan siapa pelapornya. Seluruh pencocokan membandingkan laporan `lost` dengan laporan `found`. `category_id` menjawab "barang apa", `type` menjawab "laporan apa"; keduanya tidak saling menggantikan.
- `title`: masukan Jaro-Winkler. `title` + `description`: masukan BGE-M3 dense dan reranker.
- `hidden_detail`: ciri rahasia untuk verifikasi klaim. **Tidak boleh bocor** ke pencarian, embedding, pencocokan, respons API publik, notifikasi, maupun log.
- `date_event`: tanggal hilang atau ditemukan; dipakai skor tanggal.
- `status`: tahap laporan (lihat bagian 6).
- `storage_location`: untuk barang `found`, tempat barang disimpan sekarang (pos satpam, ruang admin).
- `user_id`: pelapor.

**item_embeddings**
- Satu baris per (`item_id`, `model_name`), dijamin unik. Saat ini berisi vektor dense BGE-M3 (`model_name = 'BAAI/bge-m3'`).
- `text_hash`: hash teks sumber. Bila berbeda dari hash teks terbaru, embedding dihitung ulang.
- `embedding`: vektor float32 sebagai BLOB. BGE-M3 dense 1024 dimensi = 4096 byte.
- BGE-M3 sparse belum disimpan. Saat sparse diimplementasikan, tabel ini kemungkinan perlu kolom `vector_type` dan UNIQUE yang diperluas.

**item_matches**
- Hanya berisi pasangan yang **lolos threshold** (keluaran skenario 5). Skenario 1 sampai 4 tidak punya threshold, dibandingkan offline di skrip evaluasi, dan tidak menulis ke tabel ini.
- `lost_item_id`, `found_item_id`: pasangan yang cocok. `UNIQUE` pada pasangan mencegah notifikasi ganda.
- Komponen tahap 1: `jw_score` (Jaro-Winkler pada judul), `metadata_score` (kategori + lokasi + tanggal), `bgem3_dense` (cosine dense BGE-M3), `bgem3_sparse` (skor sparse BGE-M3).
- `bgem3_sparse` bernilai **NULL** selama sparse belum diimplementasikan di website. Setelah itu, ubah menjadi `NOT NULL`. Skalanya bukan cosine, jadi jangan dibandingkan atau dijumlahkan langsung dengan `bgem3_dense` tanpa normalisasi.
- `bge_reranker`: skor reranker (sigmoid 0 sampai 1). Ini **skor akhir**: dasar urutan dan dasar threshold. Komponen lain hanya catatan dan fitur.
- `pipeline_version`: skenario, model, bobot, dan threshold yang menghasilkan baris itu, sehingga skor dapat ditelusuri dan dihitung ulang.
- Tidak ada kolom skor gabungan tahap 1 maupun skor skenario 4; keduanya dapat dihitung ulang dari komponen dan bobot di `pipeline_version`.
- Penamaan `bgem3_*` dan `bge_reranker` terikat pada model. Bila model diganti, tambahkan kolom baru atau catat modelnya di `pipeline_version`.
- `status`: lihat bagian 6. `notified_at`: waktu notifikasi terkirim.

**claims**
- `item_id`: barang `found` yang diklaim. `claimant_id`: pengaju.
- `lost_item_id` (nullable): laporan hilang milik pengaju, bila ada. Klaim yang disetujui jadi pasangan (lost, found) yang **terbukti benar**, sehingga berguna sebagai data uji evaluasi.
- `proof_description`, `proof_image`: bukti kepemilikan dari pengaju.
- `verified_by` (nullable), `verified_at`, `admin_note`: keputusan admin.

**handovers**
- Satu serah terima per klaim (`claim_id` unik). `handed_by`: petugas yang menyerahkan. `received_by`: penerima. `photo`: foto bukti.

**disputes**
- `claim_id`: klaim yang disanggah. `reported_by`: pelapor sanggahan. `resolved_by` (nullable): admin yang menyelesaikan.

**item_logs**
- Setiap perubahan status laporan: `old_status`, `new_status`, siapa (`user_id`), kapan, dari IP mana (`ip_address`). Tidak pernah diedit atau dihapus oleh admin biasa.

## 5. Relasi (semua foreign key)

| Kolom | Merujuk | Wajib? | Saat induk dihapus |
|---|---|---|---|
| items.user_id | users.user_id | ya | RESTRICT |
| items.category_id | categories.category_id | ya | RESTRICT |
| items.location_id | locations.location_id | ya | RESTRICT |
| item_images.item_id | items.item_id | ya | CASCADE |
| item_embeddings.item_id | items.item_id | ya | CASCADE |
| item_matches.lost_item_id | items.item_id | ya | CASCADE |
| item_matches.found_item_id | items.item_id | ya | CASCADE |
| item_logs.item_id | items.item_id | ya | CASCADE |
| item_logs.user_id | users.user_id | ya | RESTRICT |
| claims.item_id | items.item_id | ya | CASCADE |
| claims.claimant_id | users.user_id | ya | RESTRICT |
| claims.lost_item_id | items.item_id | tidak | SET NULL |
| claims.verified_by | users.user_id | tidak | SET NULL |
| handovers.claim_id | claims.claim_id | ya (unik) | CASCADE |
| handovers.handed_by | users.user_id | ya | RESTRICT |
| handovers.received_by | users.user_id | ya | RESTRICT |
| disputes.claim_id | claims.claim_id | ya | CASCADE |
| disputes.reported_by | users.user_id | ya | RESTRICT |
| disputes.resolved_by | users.user_id | tidak | SET NULL |

Total 19 foreign key. Pengguna tidak dihapus permanen selama masih punya laporan, klaim, atau log (RESTRICT) demi jejak audit.

## 6. Nilai enum dan alur status

| Kolom | Nilai |
|---|---|
| `users.role` | `admin`, `user` |
| `items.type` | `lost`, `found` |
| `items.status` | `open`, `claimed`, `returned`, `closed` |
| `claims.status` | `pending`, `approved`, `rejected` |
| `disputes.status` | `open`, `resolved`, `dismissed` |
| `item_matches.status` | `new`, `notified`, `viewed`, `dismissed` |

Alur status:

```text
items (found):   open -> claimed -> returned            klaim ditolak: claimed -> open
items (lost):    open -> closed                         ditutup pemilik ("sudah ketemu")
claims:          pending -> approved | rejected
item_matches:    new -> notified -> viewed | dismissed  dismissed = pemilik menandai "bukan barang saya"
disputes:        open -> resolved | dismissed
```

## 7. Alur data

1. **Registrasi.** Pengguna mendaftar, `users` terisi (`role = user`, `nim_nip`, `phone`, preferensi notifikasi).
2. **Membuat laporan.** Pengguna mengisi `items` (`type`, `title`, `description`, `category_id`, `location_id`, `date_event`, opsional `hidden_detail` dan `storage_location`) dan mengunggah foto ke `item_images`. Perubahan status dicatat di `item_logs`.
3. **Pengindeksan.** Setelah laporan dibuat atau teksnya berubah, layanan pencarian menghitung embedding dense BGE-M3 dari judul + deskripsi dan menyimpannya di `item_embeddings` (dilewati bila `text_hash` sama). Skor Jaro-Winkler dihitung saat dibutuhkan dari judul dan **tidak disimpan**.
4. **Pencarian.** Kueri pengguna dibandingkan dengan laporan `open` yang sesuai. Tahap 1: JW + metadata (kategori, lokasi, tanggal) + dense digabung berbobot, lalu diambil top-K. Tahap 2: reranker. Tahap 3: threshold. Hasilnya tidak disimpan. Pencarian FULLTEXT pada `items` tersedia sebagai cadangan bila layanan Python tidak tersedia.
5. **Pencocokan dan notifikasi.** Laporan baru dicocokkan dengan laporan lawan jenis yang `open` lewat tiga tahap yang sama. Pasangan yang lolos threshold dicatat di `item_matches` (`UNIQUE` mencegah duplikat). Notifikasi dikirim lewat email (opsional WhatsApp) dan masuk `notifications`. `item_matches.status` bergerak `new` → `notified` → `viewed`/`dismissed`.
6. **Klaim.** Pemilik membuka halaman barang dan mengajukan `claims` (`pending`) dengan bukti, opsional mengaitkan `lost_item_id`. `items.status` menjadi `claimed`.
7. **Verifikasi.** Admin memeriksa bukti (membandingkan dengan `hidden_detail`), lalu mengisi `verified_by`, `verified_at`, `admin_note`, dan mengubah `claims.status`. Bila ditolak, `items.status` kembali `open`.
8. **Sanggahan (opsional).** Pihak lain dapat membuat `disputes` terhadap klaim yang mencurigakan; admin menyelesaikannya.
9. **Serah terima.** Petugas mengisi `handovers` (penyerah, penerima, waktu, foto). `items.status` menjadi `returned`.
10. **Audit.** Setiap langkah yang mengubah status menulis satu baris di `item_logs`.

## 8. Aturan integritas yang dijaga aplikasi (database tidak bisa)

- `item_matches.lost_item_id` harus merujuk `items.type = 'lost'`, `found_item_id` harus `'found'`, dan keduanya tidak boleh sama.
- `claims.item_id` hanya boleh merujuk laporan `found`; `claims.lost_item_id` (bila diisi) harus laporan `lost` milik `claimant_id`.
- `claims.verified_by` harus `users.role = 'admin'` dan tidak boleh sama dengan `claimant_id`.
- `handovers.handed_by` harus **berbeda** dari `claims.verified_by` (pemisahan peran verifikasi dan serah terima).
- Hanya laporan `open` dan belum di-soft-delete yang boleh jadi kandidat pencarian atau pencocokan.
- `item_matches` hanya boleh berisi pasangan yang lolos threshold reranker.
- `hidden_detail` tidak boleh menjadi masukan JW, BGE-M3, maupun reranker.
- `hidden_detail` hanya boleh dibaca untuk verifikasi klaim oleh admin.
- Pengguna tidak boleh menerima notifikasi tentang laporannya sendiri.

## 9. Indeks dan alasannya

| Tabel | Indeks | Untuk |
|---|---|---|
| items | `(type, status, date_event)` | Mengambil kandidat: jenis + status + rentang tanggal |
| items | `date_event` | Mengurutkan berdasarkan terbaru |
| items | FULLTEXT `(title, description)` | Pencarian dasar; cadangan bila layanan Python tidak tersedia |
| claims | `(item_id, status)` | Klaim `pending` per barang |
| item_logs | `(item_id, created_at)` | Riwayat satu laporan berurutan waktu |
| disputes | `(claim_id, status)` | Sanggahan terbuka per klaim |
| item_embeddings | UNIQUE `(item_id, model_name)` | Satu embedding per laporan per model |
| item_matches | UNIQUE `(lost_item_id, found_item_id)` | Mencegah notifikasi ganda |
| item_matches | `status` | Menyaring pasangan menurut status |
| users | UNIQUE `email`, `nim_nip`; `role` | Login, identitas, penyaringan admin |

Kolom FK otomatis ter-index oleh InnoDB.

## 10. Yang tidak disimpan di database

- **Skor Jaro-Winkler per pasangan** selain pada baris `item_matches` yang lolos threshold: dihitung saat dibutuhkan dari judul.
- **Skor skenario 1 sampai 4 dan seluruh hasil evaluasi**: ada di skrip evaluasi offline dan berkas CSV, bukan tabel aplikasi. Data uji (query, kandidat, label, termasuk query tanpa pasangan) juga di CSV.
- **Bobot, `candidate_k`, dan threshold**: ada di file konfigurasi (lihat `AGENTS.md`), bukan tabel. Nilai yang berlaku saat suatu baris dibuat dirangkum di `item_matches.pipeline_version`.
- **Hasil pencarian** pengguna: dihitung saat itu juga.

## 11. Bila mengubah skema

Lakukan semuanya, jangan sebagian:

1. Buat **migration baru** (jangan mengedit migration lama yang sudah dijalankan).
2. Perbarui `database/lost_and_found.sql`.
3. Perbarui diagram ERD.
4. Perbarui dokumen ini (peta tabel, relasi, enum, alur).
5. Perbarui `AGENTS.md` bila menyangkut pencarian atau notifikasi.
6. Bila menambah tabel ber-PK kode, daftarkan awalan kodenya di bagian 2 dan di model (`$codePrefix`).

## 12. File terkait

- `database/migrations/`, `database/seeders/`, `database/lost_and_found.sql`
- `app/Models/Concerns/HasCodeId.php`: pembuat kode PK
- `AGENTS.md`: panduan modul pencarian dan pencocokan notifikasi
- `README.md` (di dalam paket): langkah pemasangan di proyek Laravel
