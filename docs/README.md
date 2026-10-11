# Lost & Found Kampus - database dengan PK kode string

PK berupa kode: USR001, CTG001, LOC001, ITM001, IMG001, EMB001, MTC001, CLM001, HND001, LOG001, DSP001.

## Dokumen

- `DATABASE.md`: fungsi tiap tabel, relasi, enum, alur data, dan daftar periksa perubahan skema.
- `AGENTS.md`: panduan modul pencarian dan pencocokan notifikasi (lima skenario penelitian, cakupan implementasi, konfigurasi, evaluasi).

## Isi paket

- `database/migrations/`, `database/seeders/`
- `database/lost_and_found.sql`: alternatif import langsung tanpa Laravel
- `app/Models/Concerns/HasCodeId.php`: pembuat kode PK berurutan

## Langkah di proyek Laravel

1. Salin isi `database/migrations/` dan `database/seeders/` ke proyekmu.
   - `0001_01_01_000000_create_users_table.php` MENGGANTI file bawaan Laravel dengan nama yang sama
     (PK users menjadi `user_id` string, dan `sessions.user_id` ikut string).
   - Kalau database sudah pernah di-migrate: `php artisan migrate:fresh --seed` (menghapus semua data).
   - Tabel `item_matches` berubah dari versi sebelumnya (kolom skor sekarang `jw_score`, `metadata_score`,
     `bgem3_dense`, `bgem3_sparse`, `bge_reranker`, `pipeline_version`). Bila tabel itu sudah ada dan berisi data,
     jangan edit migration lama; buat migration baru yang mengubahnya.
2. Salin `app/Models/Concerns/HasCodeId.php` ke proyekmu.
3. Pasang di tiap model. Contoh `app/Models/Claim.php`:

       use App\Models\Concerns\HasCodeId;

       class Claim extends Model
       {
           use HasCodeId;
           protected $primaryKey = 'claim_id';
           protected string $codePrefix = 'CLM';
       }

   Awalan per model: User=USR (primaryKey user_id), Category=CTG, Location=LOC, Item=ITM,
   ItemImage=IMG, ItemEmbedding=EMB, ItemMatch=MTC, Handover=HND, ItemLog=LOG, Dispute=DSP.
4. Simpan data di dalam transaksi supaya kode berurutan aman dari bentrok:

       DB::transaction(fn () => Claim::create([...]));

5. Relasi Eloquent: sebutkan nama kolomnya, mis.
   `$this->belongsTo(User::class, 'claimant_id', 'user_id');`

## Catatan

- Migration `..._create_notifications_table.php` MENGGANTIKAN yang dibuat `php artisan notifications:table`
  (notifiable_id bertipe string). Jangan simpan keduanya.
- Jika memakai Sanctum (personal_access_tokens), kolom morph-nya juga bertipe angka; ganti ke `stringMorphs()`.
- `item_matches.bgem3_sparse` sengaja NULL sampai BGE-M3 sparse diimplementasikan di website.
- `lost_and_found.sql` adalah alternatif import langsung tanpa Laravel.
