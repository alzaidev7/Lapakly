<?php
require_once __DIR__ . '/_bantu.php';

const KEMBALI = '/admin/produk/index.php';

function kembali(): void
{
    header('Location: ' . KEMBALI);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali();
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    set_flash('galat', 'Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    kembali();
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    set_flash('galat', 'Produk tidak ditemukan.');
    kembali();
}

try {
    $cari = $pdo->prepare('SELECT nama, foto FROM produk WHERE id = ?');
    $cari->execute([$id]);
    $produk = $cari->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        set_flash('galat', 'Produk tidak ditemukan. Mungkin sudah dihapus.');
        kembali();
    }

    $hapus = $pdo->prepare('DELETE FROM produk WHERE id = ?');
    $hapus->execute([$id]);

    if ($hapus->rowCount() > 0) {
        hapusBerkasFoto($pdo, $produk['foto'], $id);
        set_flash('sukses', 'Produk "' . $produk['nama'] . '" sudah dihapus.');
    } else {
        set_flash('galat', 'Produk tidak bisa dihapus. Coba lagi.');
    }
} catch (PDOException $e) {
    set_flash('galat', 'Produk tidak bisa dihapus karena masih dipakai data lain.');
}

kembali();
