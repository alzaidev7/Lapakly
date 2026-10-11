<?php

require_once __DIR__ . '/../inti/koneksi.php';

$sql = "SELECT id, nama, kategori, deskripsi, harga, foto
        FROM produk
        WHERE status = 'aktif'
        ORDER BY id DESC";

$stmt = $pdo->query($sql);
$produk = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102A22">
    <meta name="description" content="Lapakly membantu usaha lokal menampilkan produk secara online melalui katalog yang sederhana dan menarik.">

    <link rel="stylesheet" href="aset/gaya.css">

    <title>Lapakly — Katalog Usaha Lokal</title>
</head>

<body>
    <!-- NAVIGASI -->
    <nav class="menu-nav">
        <a href="index.php" class="logo-menu" aria-label="Lapakly, halaman utama">
            Lapakly<span>.</span>
        </a>

        <div class="daftar-menu">
            <a href="#beranda" class="tautan-menu">Beranda</a>
            <a href="#katalog" class="tautan-menu">Katalog</a>
            <a href="#tentang" class="tautan-menu">Tentang</a>
        </div>
    </nav>

    <main>
        <!-- HERO -->
        <section class="hero-web" id="beranda">
            <div class="isi-hero">
                <p class="label-hero">SOLUSI DIGITAL UMKM</p>

                <h1 class="judul-hero">
                    Lapak online yang bikin jualan makin mudah.
                </h1>

                <p class="deskripsi-hero">
                    Tampilkan produk terbaikmu, kenalkan bisnismu,
                    dan biarkan pelanggan menemukan apa yang mereka cari.
                </p>

                <div class="aksi-hero">
                    <a href="#katalog" class="tombol-utama">
                        Lihat Katalog
                    </a>

                    <a href="#tentang" class="tombol-kedua">
                        Kenali Lapakly
                    </a>
                </div>

                <p class="catatan-hero">
                    Sederhana untuk memulai, siap untuk berkembang.
                </p>
            </div>

            <div class="hiasan-hero" aria-hidden="true">
                <div class="kartu-sambutan">
                    <div class="ikon-sambutan">✳</div>

                    <p class="label-kartu">DARI LOKAL UNTUK SEMUA</p>

                    <h2 class="judul-kartu">
                        Mulai dari sini.
                    </h2>

                    <p class="isi-kartu">
                        Bawa produk lokalmu lebih dekat ke pelanggan.
                    </p>

                    <div class="hiasan-garis"></div>
                    <p class="teks-kecil">Temukan produk favoritmu.</p>
                </div>
            </div>
        </section>

        <!-- KATALOG -->
        <section class="bagian-katalog" id="katalog">
            <div class="kepala-katalog">
                <div>
                    <p class="label-hero">PILIHAN PRODUK</p>
                    <h2 class="judul-katalog">Temukan favoritmu.</h2>
                    <p class="deskripsi-katalog">
                        Lihat produk pilihan dari usaha lokal.
                    </p>
                </div>

                <span class="jumlah-produk"> <?= count($produk) ?> produk pilihan</span>
            </div>

            <div class="daftar-produk">

<?php if (empty($produk)): ?>

    <p>Belum ada produk yang tersedia.</p>

<?php else: ?>

    <?php foreach ($produk as $item): ?>

        <article class="kartu-produk">

            <div class="gambar-produk">
                <?php if (!empty($item['foto'])): ?>
                    <img
                        src="foto/<?= rawurlencode(basename($item['foto'])) ?>"
                        alt="<?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>"
                        class="foto-produk"
                        loading="lazy"
                    >
                <?php endif; ?>
            </div>

            <div class="isi-produk">

                <p class="kategori-produk">
                    <?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <h3 class="nama-produk">
                    <?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <p class="deskripsi-produk">
                    <?= htmlspecialchars($item['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="baris-harga">

                    <p class="harga-produk">
                        Rp<?= number_format((float) $item['harga'], 0, ',', '.') ?>
                    </p>

                    <a
                        href="#kontak"
                        class="tombol-produk"
                        aria-label="Tanya tentang <?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        Tanya
                    </a>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

<?php endif; ?>

            </div>

            <p class="catatan-katalog"> Produk ditampilkan langsung dari katalog Lapakly.</p>
        </section>

        <!-- TENTANG -->
        <section class="bagian-tentang" id="tentang">
            <div class="isi-tentang">
                <p class="label-hero">TENTANG LAPAKLY</p>

                <h2 class="judul-tentang">
                    Usaha kecil, peluang lebih besar.
                </h2>

                <p class="deskripsi-tentang">
                    Lapakly membantu pelaku UMKM menampilkan produk
                    mereka secara online melalui katalog yang sederhana,
                    menarik, dan mudah digunakan.
                </p>

                <a href="#katalog" class="tombol-utama">
                    Jelajahi Produk
                </a>
            </div>

            <div class="hiasan-tentang" aria-hidden="true">
                <span class="ikon-tentang">✳</span>
                <p class="judul-hiasan">Tumbuh bersama.</p>
                <p class="teks-hiasan">
                    Mulai dari usaha lokal, berkembang bersama pelanggan.
                </p>
            </div>
        </section>

        <!-- AJAKAN MENGHUBUNGI -->
        <section class="ajakan-kontak" id="kontak">
            <p class="label-hero">ADA YANG MENARIK?</p>

            <h2>Temukan produk yang kamu suka.</h2>

            <p>
                Hubungi pemilik usaha untuk bertanya tentang produk
                dan cara pemesanan.
            </p>

            <a href="#katalog" class="tombol-utama">
                Lihat Produk
            </a>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="kaki-web">
        <div class="isi-kaki">
            <div class="tentang-kaki">
                <a href="index.php" class="logo-kaki">
                    Lapakly<span>.</span>
                </a>

                <p>
                    Bikin lapak online jadi lebih gampang.
                    Bantu usaha lokal berkembang di dunia digital.
                </p>
            </div>

            <div class="tautan-kaki">
                <h2>Navigasi</h2>
                <a href="#beranda">Beranda</a>
                <a href="#katalog">Katalog</a>
                <a href="#tentang">Tentang</a>
            </div>

            <div class="kontak-kaki">
                <h2>Temukan Kami</h2>
                <a href="https://www.instagram.com/"
                   target="_blank" rel="noopener noreferrer">Instagram</a>
                <a href="https://www.facebook.com/"
                   target="_blank" rel="noopener noreferrer">Facebook</a>
                <a href="mailto:halo@lapakly.com">Email</a>
                <a href="https://wa.me/"
                   target="_blank" rel="noopener noreferrer">WhatsApp</a>
            </div>
        </div>

        <div class="garis-kaki"></div>

        <p class="hak-cipta">
            &copy; 2026 Lapakly. Dibuat untuk mendukung usaha lokal.
        </p>
    </footer>
</body>
</html>
