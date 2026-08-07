# SOPRA — System for Operational Personnel Resource Allocation

## v3 update (modular file structure)

The three big pages (`payment_ledger.php`, `duty_assignments.php`,
`personnel_overview.php`) were each one long file mixing filter
parsing, POST handling, CSV export, stats and HTML together. They're
now split into small, single-purpose files under `config/`,
`includes/`, `actions/`, `exports/` and `views/` — see **Struktur
fail** below for the full map. Behavior is unchanged; only the file
layout is new.

## v2 update (English UI + expanded Duty module + PWA)

- **Full English interface.** Every page (login, payment ledger, duty
  module, personal view) is now in English. `config.php` now also
  defines `MONTHS_FULL` in English and a new `DUTY_TYPES` list.
- **Duty & Operation Location, expanded** (`duty_assignments.php`):
  - Search by member name, **filter by Rank**, and **filter by Status**
    (Upcoming / Ongoing / Completed — computed from the date range,
    not stored).
  - "Record Duty Assignment" form now has: member (searchable
    dropdown), **Duty Type** (Confidential / Court Hearing / LDP /
    Exhibition / Other, searchable dropdown), **State** →
    **District** cascading searchable dropdowns (`STATES_DISTRICTS`
    in `config.php`), an optional specific-location text field,
    **Departure Date** and **Return Date** (calendar pickers), and a
    **"Still ongoing / return date not yet known"** checkbox that
    leaves Return Date empty for operations still in progress.
  - The table shows a computed **Duration** column (e.g. "3 days", or
    "Ongoing" while Return Date is empty).
  - **CSV export** for duty records, honoring whatever
    search/member/rank/status filters are currently applied.
  - Note: Confidential-type duty records are **not** hidden or
    redacted from other admins or from CSV export — every admin sees
    the same full detail, same as every other duty record.
- **Database change:** `duty_assignments` now has `state`, `district`,
  `duty_type`, `date_start`, `date_end` (nullable) instead of the old
  single `location` + `duty_date`. **Existing installs** should run
  `migration_v2_duty_fields.sql` once (see below) — brand-new installs just import
  the updated `database_schema.sql`.
- **Installable on mobile (PWA).** `manifest.json` and
  `service-worker.js` let an Admin "Add to Home Screen" / install
  SOPRA like an app. The manifest link and service-worker
  registration are only ever served on the admin dashboard pages
  (`payment_ledger.php`, `duty_assignments.php`) — a `user`-role account or
  an anonymous visitor is redirected by `requireAdmin()` before that
  HTML (and the manifest reference in it) is ever sent to their
  browser, so only an authenticated Admin's browser ever gets the
  install prompt. Two important caveats:
  - **HTTPS is required** for the service worker to register on a
    real phone. `http://localhost` in XAMPP is fine for local testing
    only — you'll need real HTTPS hosting before this works on an
    actual mobile device.
  - The service worker only caches static files (CSS, JS, icons) for
    a faster app shell. It deliberately **never caches any `.php`
    page** — including duty/payment data — so a lost or shared phone
    can't surface stale sensitive data offline after logout.

### Upgrading an existing install

```
mysql -u root -p sopra_db < migration_v2_duty_fields.sql
```

This backfills `date_start` from the old `duty_date`, and sets
`state`/`district` to `"UNSPECIFIED"` on old rows (the old free-text
`location` is **not** auto-mapped to a state/district — go back and
correct those rows from the UI when convenient).

---


Sistem web (PHP + MySQL) untuk PTK N.C.I.D: kutip sumbangan bulanan
daripada anggota (jumlah bebas ikut kemampuan sendiri) dan jejak
tugasan/operasi setiap anggota (lokasi, tarikh, tujuan).

## Apa yang diubah daripada projek asal (Homestay Payment Ledger)

1. **Jenama & nama** — ditukar kepada **SOPRA**, logo sebenar PTK
   (`assets/logo.png`) dipaparkan di skrin log masuk dan di setiap
   topbar, gantikan lencana SVG generik yang lama.
2. **Login admin sahaja** — `signup.php` (pendaftaran sendiri) telah
   **dibuang sepenuhnya**. Satu-satunya cara akaun baru dicipta ialah
   melalui **Urus Pengguna** di dashboard Admin — admin masukkan
   username, password, peranan (admin/pengguna) dan kaitkan dengan
   rekod anggota. Ini sudah wujud dalam projek asal, cuma laluan
   pendaftaran awam kini ditutup.
3. **Bayaran ikut kehendak sendiri** — sistem lama guna yuran tetap
   ikut pangkat (`RANK_FEES`). Ini telah **dibuang**. Bila admin klik
   petak bulan (merah) untuk tandakan seseorang sudah bayar, sistem
   akan tanya **jumlah RM** yang dibayar (`prompt()` ringkas), lalu
   simpan jumlah itu + tarikh hari itu direkod (`paid_date`). Klik
   petak hijau semula akan buka balik status (merah) dan padam jumlah
   & tarikh yang direkod — sama seperti ciri tick hijau/merah dalam
   antara muka asal anda, cuma kini menyimpan jumlah & tarikh sekali.
4. **Ciri baru — Tugasan & Lokasi Operasi** (`duty_assignments.php`): admin
   boleh rekod bila-bila seseorang anggota pergi bertugas — **lokasi**,
   **tarikh** dan **tujuan/nama operasi**. Papan pemuka admin memaparkan
   tugasan akan datang, dan setiap anggota boleh lihat jadual tugasan
   mereka sendiri di `personnel_overview.php`. Ini gantikan modul
   "Tambah Booking Homestay" lama yang tidak berkaitan dengan anggota
   individu.
5. **Eksport CSV** — dua butang eksport, dan **nama fail sentiasa
   mengandungi tarikh hari ia dieksport**:
   - *Eksport Bulan Ini* → `SOPRA_Bayaran_<Bulan>_<Tahun>_Eksport_<tarikh-hari-ini>.csv`
     — senarai siapa sudah/belum bayar bulan tersebut, jumlah RM dan
     hari mereka bayar.
   - *Eksport Tahun Penuh* → `SOPRA_Bayaran_<Tahun>_Eksport_<tarikh-hari-ini>.csv`
     — matriks 12 bulan penuh (status, RM, tarikh bayar setiap bulan).

## Struktur fail (v3 — modular)

Setiap halaman kini ialah **controller nipis** (parse input → panggil
action/export → `require` view). Logik POST, eksport CSV dan HTML
setiap satu dipisahkan ke fail sendiri supaya senang dibaca & disunting:

```
SOPRA/
├── assets/
│   ├── logo.png            # logo PTK sebenar
│   ├── icon-192.png        # PWA icon (192x192)
│   └── icon-512.png        # PWA icon (512x512)
│
├── config.php               # bootstrap nipis: session_start() + require semua fail config/ & includes/ di bawah
├── config/
│   ├── database.php         # sambungan PDO ($pdo) + seed admin pertama-kali
│   └── constants.php        # APP_NAME, RANKS, MONTHS, DUTY_TYPES, STATES_DISTRICTS
│
├── includes/                # helper functions dikongsi merentasi halaman
│   ├── auth.php              # isLoggedIn(), isAdmin(), requireLogin/Admin/User()
│   ├── format_helpers.php     # e(), fmtRM(), fmtDate()
│   ├── duty_helpers.php       # dutyStatus(), fmtDuration()
│   ├── payment_query.php      # buildQuery() — filter payment ledger
│   ├── payment_data.php       # payInfo() — lookup peta bayaran
│   ├── payment_stats.php      # computePaymentStats() — kad ringkasan
│   └── duty_query.php         # buildDutyQuery() — filter tugasan
│
├── actions/                 # pengendali POST (satu fail = satu halaman)
│   ├── payment_actions.php    # toggle bayar, tambah/sunting/padam anggota & pengguna
│   └── duty_actions.php       # rekod/padam tugasan (dengan validasi)
│
├── exports/                 # eksport CSV (satu fail = satu halaman)
│   ├── payment_export.php     # eksport bulan / eksport tahun penuh
│   └── duty_export.php        # eksport senarai tugasan
│
├── views/                   # HTML sahaja — tiada logik DB/POST
│   ├── payment_ledger_view.php
│   ├── duty_assignments_view.php
│   └── personnel_overview_view.php
│
├── payment_ledger.php        # controller: ledger bayaran + urus anggota/pengguna
├── duty_assignments.php      # controller: rekod tugasan/lokasi operasi
├── personnel_overview.php    # controller: paparan diri sendiri (bayaran + tugasan)
├── login.php                 # log masuk (admin sahaja cipta akaun)
├── logout.php
├── index.php                 # redirect ke login.php
│
├── database_schema.sql               # skema pangkalan data (v2) — import dahulu on a fresh install
├── migration_v2_duty_fields.sql      # run once on an EXISTING sopra_db instead of database_schema.sql
├── migration_drop_purpose_notes.sql
├── personnel_seed_data.sql
│
├── searchable_dropdown.js    # searchable dropdown widget (shared)
├── manifest.json              # PWA manifest (served on admin pages only)
├── service-worker.js          # PWA service worker — static assets only, never caches .php
└── styles.css                 # gaya (navy/gold/paper, hijau=bayar, merah=belum)
```

Setiap `payment_ledger.php` / `duty_assignments.php` / `personnel_overview.php`
di root kini kurang daripada 100 baris — ia cuma menyediakan data lalu
`require` fail `views/*_view.php` yang sepadan. Tiada perubahan tingkah
laku daripada v2: setiap fail di atas disalin/dipisahkan baris demi
baris daripada `payment_ledger.php`, `duty_assignments.php` dan
`personnel_overview.php` yang asal.

## Pasang (XAMPP / phpMyAdmin)

1. Import `database_schema.sql` (cipta pangkalan data `sopra_db` & semua jadual).
2. Letak folder `SOPRA/` dalam `htdocs/`.
3. Semak `config.php` — tukar `DB_USER`/`DB_PASS` jika perlu.
4. Buka `http://localhost/SOPRA/login.php` — log masuk kali pertama
   dengan **admin / admin123** (akaun ini dicipta automatik jika jadual
   `users` kosong). **Tukar password ini serta-merta** melalui Urus
   Pengguna selepas log masuk.
5. Tambah anggota (Tambah Anggota), kemudian cipta akaun pengguna untuk
   setiap anggota melalui Urus Pengguna.

## Nota

- Semua pangkat (`RANKS`) dan nama bulan boleh disunting dalam
  `config/constants.php`.
- `paid_date` direkod secara automatik sebagai tarikh hari admin
  menekan butang (bukan tarikh boleh sunting) — mudah dan konsisten
  untuk laporan.
