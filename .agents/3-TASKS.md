# SIMANO Task List

## T-01: Setup Laravel 13 + Inertia + Vue 3 TypeScript + Vite
- **Modul:** Setup
- **Deskripsi:** Inisialisasi project dan konfigurasi stack utama.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** -
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `composer.json`, `package.json`, `vite.config.ts`, `resources/js/app.ts`

## T-02: Konfigurasi MariaDB, `.env`, Apache virtual host
- **Modul:** Setup
- **Deskripsi:** Konfigurasi database dan web server lokal.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-01
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `.env`, `public/.htaccess`, konfigurasi Apache

## T-03: Pasang Varlet UI, `@varlet/mcp`, Ziggy, Lottie
- **Modul:** Setup
- **Deskripsi:** Pasang dependency UI, routing frontend, dan animasi.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-01
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `package.json`, `resources/js/app.ts`, `vite.config.ts`

## T-04: Setup layout mobile-first employee dan desktop-first admin
- **Modul:** UI
- **Deskripsi:** Buat layout responsif berdasarkan role pengguna.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-03
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `resources/js/Layouts/`, `resources/css/`

## T-05: Buat migration dan model users
- **Modul:** Auth
- **Deskripsi:** Buat user berbasis NIK dan profil SSO.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-02
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Models/User.php`, `database/migrations/`

## T-06: Pasang Spatie Permission, role, permission, seeder
- **Modul:** Auth
- **Deskripsi:** Konfigurasi role dan permission Admin/HR serta employee.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-05
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Models/User.php`, `database/seeders/`, `config/permission.php`

## T-07: Integrasi SSO
- **Modul:** Auth
- **Deskripsi:** Implementasi redirect, callback, session, dan sinkron profil SSO.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-05
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Sso/`, `app/Services/Sso/`, `routes/web.php`, `config/services.php`

## T-08: Middleware auth, role, permission, dan route protection
- **Modul:** Auth
- **Deskripsi:** Lindungi halaman dan aksi sesuai akses pengguna.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-06, T-07
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Http/Middleware/`, `routes/web.php`

## T-09: Migration/model evaluation periods
- **Modul:** Evaluation
- **Deskripsi:** Buat periode evaluasi bulanan beserta status aktif.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-02
- **Tanggal:** 2026-09-11
- **Estimasi:** 3 jam
- **File yang diubah:** `app/Models/EvaluationPeriod.php`, `database/migrations/`

## T-10: Migration/model templates dan questions
- **Modul:** Evaluation
- **Deskripsi:** Buat template kategori dan 14 pertanyaan dinamis.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-02
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Models/EvaluationTemplate.php`, `app/Models/Question.php`, `database/migrations/`

## T-11: Migration/model evaluations dan evaluation details
- **Modul:** Evaluation
- **Deskripsi:** Simpan header evaluasi, jawaban, target, evaluator, dan skor rata-rata.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-02
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Models/Evaluation.php`, `app/Models/EvaluationDetail.php`, `database/migrations/`

## T-12: Unique constraint evaluasi bulanan dan index rekap
- **Modul:** Evaluation
- **Deskripsi:** Cegah duplikasi evaluasi dan optimalkan query laporan.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-11
- **Tanggal:** 2026-09-11
- **Estimasi:** 2 jam
- **File yang diubah:** `database/migrations/`

## T-13: Audit log migration, model, dan service
- **Modul:** Audit
- **Deskripsi:** Catat aksi administratif dan ekspor laporan.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-06
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Models/AuditLog.php`, `app/Services/`, `database/migrations/`

## T-14: CRUD periode evaluasi Admin/HR
- **Modul:** Admin
- **Deskripsi:** Kelola bulan, tahun, dan status periode.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-09, T-08
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/Periods/`, `routes/web.php`

## T-15: CRUD template dan 14 pertanyaan
- **Modul:** Admin
- **Deskripsi:** Kelola kategori, template, pertanyaan, dan status aktif.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-10, T-08
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/Templates/`, `routes/web.php`

## T-16: Master user dan pencarian target
- **Modul:** Evaluation
- **Deskripsi:** Tampilkan dan cari target dari master user aktif.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-05, T-08
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Http/Controllers/Evaluation/`, `app/Http/Requests/`, `routes/web.php`

## T-17: Halaman login, dashboard, profil, dan status evaluasi
- **Modul:** Frontend
- **Deskripsi:** Buat halaman utama employee dan status evaluasi bulanan.
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-07
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `resources/js/Pages/Auth/`, `resources/js/Pages/Dashboard.vue`, `resources/js/Pages/Evaluations/`

## T-18: Form evaluasi mobile-first dengan pencarian target
- **Modul:** Evaluation
- **Deskripsi:** Buat form kategori, target, dan 14 pertanyaan skala 1–5.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-14, T-15, T-16, T-17
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `resources/js/Pages/Evaluations/Create.vue`, `resources/js/Components/`

## T-19: Validasi skor 1–5 dan 14 pertanyaan wajib
- **Modul:** Evaluation
- **Deskripsi:** Validasi frontend dan server-side untuk seluruh jawaban.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-18
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Http/Requests/StoreEvaluationRequest.php`, `resources/js/Pages/Evaluations/Create.vue`

## T-20: Submit evaluasi transaction dan kalkulasi rata-rata
- **Modul:** Evaluation
- **Deskripsi:** Simpan evaluasi atomik dan hitung nilai rata-rata.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-11, T-19
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Evaluation/`, `app/Services/Evaluation/`

## T-21: Validasi duplikasi evaluator-target-periode
- **Modul:** Evaluation
- **Deskripsi:** Terapkan validasi aplikasi dan constraint database.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-12, T-20
- **Tanggal:** 2026-09-11
- **Estimasi:** 3 jam
- **File yang diubah:** `app/Services/Evaluation/`, `app/Http/Requests/`, `database/migrations/`

## T-22: Dashboard rekap anonim Admin/HR
- **Modul:** Reporting
- **Deskripsi:** Tampilkan agregasi skor tanpa identitas evaluator.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-20, T-08
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/Dashboard.vue`, `resources/js/Pages/Admin/Evaluations/`

## T-23: Filter bulan, tahun, kategori, dan target
- **Modul:** Reporting
- **Deskripsi:** Tambahkan filter dan pagination laporan.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Http/Requests/`, `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/Evaluations/`

## T-24: Riwayat dan perbandingan skor bulanan
- **Modul:** Reporting
- **Deskripsi:** Tampilkan tren skor target antarperiode.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/Evaluations/`

## T-25: Export Excel tanpa identitas evaluator
- **Modul:** Reporting
- **Deskripsi:** Ekspor hasil agregasi sesuai filter.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Exports/`, `app/Http/Controllers/Admin/`, `routes/web.php`

## T-26: Export PDF tanpa identitas evaluator
- **Modul:** Reporting
- **Deskripsi:** Buat laporan PDF agregat sesuai filter.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `resources/views/exports/`, `app/Http/Controllers/Admin/`, `routes/web.php`

## T-27: Audit log perubahan dan aktivitas ekspor
- **Modul:** Audit
- **Deskripsi:** Catat aksi perubahan data dan ekspor laporan.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-13, T-14, T-15, T-25, T-26
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `app/Services/`, `app/Http/Controllers/Admin/`

## T-28: Loading, success, empty, error Lottie
- **Modul:** UI
- **Deskripsi:** Tambahkan state visual dengan lottie-web.
- **Prioritas:** Low
- **Status:** Done
- **Dependensi:** T-03, T-18, T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 4 jam
- **File yang diubah:** `resources/js/Components/`, `public/animations/`

## T-29: Security hardening
- **Modul:** Security
- **Deskripsi:** Terapkan CSRF, secure session cookie, HTTPS, state, dan nonce SSO.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-07, T-08
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `.env`, `config/session.php`, `app/Http/Middleware/`, `app/Services/Sso/`

## T-30: Optimasi query, pagination, debounce, cache
- **Modul:** Performance
- **Deskripsi:** Optimalkan pencarian target dan laporan Admin/HR.
- **Prioritas:** Mid
- **Status:** Done
- **Dependensi:** T-22, T-23
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `app/Http/Controllers/`, `app/Services/`, `resources/js/Composables/`

## T-31: Feature test
- **Modul:** Testing
- **Deskripsi:** Uji auth, role, periode, template, evaluasi, dan anonimitas.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-08, T-21, T-22
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `tests/Feature/`, `tests/Unit/`

## T-32: Build production, Apache, backup, scheduler
- **Modul:** Deployment
- **Deskripsi:** Siapkan build, Apache, backup MariaDB, dan scheduler Laravel.
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-30, T-31
- **Tanggal:** 2026-09-11
- **Estimasi:** 1 hari
- **File yang diubah:** `public/.htaccess`, `routes/console.php`, dokumentasi server lokal
