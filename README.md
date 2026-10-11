Lapakly 🛍️

Aplikasi katalog produk dan manajemen toko berbasis web untuk UMKM.

Lapakly adalah proyek web yang dirancang untuk membantu pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) menampilkan produk serta mengelola katalog melalui dashboard administrator yang sederhana dan mudah digunakan.

✨ Fitur

- Katalog Produk — Menampilkan produk beserta nama, kategori, harga, deskripsi, dan foto.
- Dashboard Admin — Halaman pengelolaan toko yang hanya dapat diakses oleh administrator yang telah login.
- Manajemen Produk (CRUD) — Menambah, melihat, mengedit, dan menghapus data produk.
- Manajemen Stok — Mengatur jumlah stok, termasuk stok nol untuk produk yang habis.
- Status Produk — Mengatur status produk aktif atau nonaktif.
- Autentikasi Admin — Login, logout, dan pengelolaan sesi administrator.
- Keamanan Password — Menggunakan "password_hash()" untuk menyimpan password dalam bentuk hash.
- Database — Menggunakan PDO untuk menghubungkan aplikasi dengan database.

🛠️ Teknologi

- PHP Native
- MySQL / MariaDB
- PDO
- HTML5
- CSS3
- JavaScript

📁 Struktur Proyek

Lapakly/
├── aplikasi/
├── basis-data/
├── inti/
│   ├── koneksi.php
│   └── periksa-admin.php
├── public/
│   ├── admin/
│   │   ├── produk/
│   │   ├── masuk.php
│   │   ├── keluar.php
│   │   ├── index.php
│   │   └── profil.php
│   ├── aset/
│   │   └── gaya.css
│   ├── foto/
│   └── index.php
├── .gitignore
└── README.md

⚙️ Persyaratan

Sebelum menjalankan Lapakly, pastikan perangkat atau server memiliki:

- PHP dengan ekstensi PDO MySQL
- MySQL atau MariaDB
- Browser modern

🚀 Menjalankan Secara Lokal

1. Clone repository:

   git clone URL_REPOSITORY

2. Masuk ke direktori proyek:

   cd Lapakly

3. Buat database bernama "lapakly" pada MySQL atau MariaDB.

4. Siapkan tabel database sesuai skema yang digunakan aplikasi.

5. Sesuaikan konfigurasi koneksi database pada "inti/koneksi.php".

6. Jalankan server PHP dari direktori proyek:

   php -S 127.0.0.1:8000 -t public

7. Buka aplikasi melalui browser:

   http://127.0.0.1:8000

🔐 Keamanan

- Password administrator disimpan menggunakan hash, bukan teks biasa.
- Halaman administrator memerlukan autentikasi sesi.
- File konfigurasi database tidak boleh berisi kredensial produksi yang dipublikasikan.
- File SQL yang berisi data sensitif tidak boleh diunggah ke repository publik.
- Gunakan HTTPS dan konfigurasi database yang aman saat deployment.
- File pembuat akun administrator sebaiknya dijalankan secara lokal melalui terminal dan tidak diekspos sebagai halaman publik.

🌐 Deployment

Lapakly dapat dikembangkan untuk deployment pada layanan hosting yang mendukung PHP dan MySQL/MariaDB.

Konfigurasi produksi harus dipisahkan dari konfigurasi lokal. Pastikan document root diarahkan ke direktori "public/" agar file internal aplikasi tidak dapat diakses langsung melalui web.

📌 Status Proyek

Status: Dalam pengembangan (In Development).

Lapakly merupakan proyek pengembangan web yang berfokus pada katalog produk, pengelolaan data, dan dashboard administrator untuk kebutuhan UMKM.

👨‍💻 Kontributor

Alzai — Developer

Dibuat sebagai proyek pembelajaran dan pengembangan aplikasi web berbasis database.

---

Lapakly — Simplifying product management for small businesses.
