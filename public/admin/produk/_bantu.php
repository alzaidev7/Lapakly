<?php
/**
 * Fungsi bantu bersama untuk halaman admin produk.
 * Dipakai oleh index.php, edit.php, dan hapus.php.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/masuk.php');
    exit;
}

require_once __DIR__ . '/../../../inti/koneksi.php';

const FOTO_MAKS = 3 * 1024 * 1024;          // 3 MB
const FOTO_DIR  = __DIR__ . '/../../foto';  // public/foto
const FOTO_URL  = '/foto/';                 // alamat folder foto di browser

function aman($nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil !== '' ? $hasil : '?';
}

function warna_hue(string $nama): int
{
    return abs(crc32($nama)) % 360;
}

function nama_admin(): string
{
    return (string) ($_SESSION['admin_nama'] ?? $_SESSION['nama'] ?? 'Admin');
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid($token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- Pesan sekali tampil ---------- */

function set_flash(string $jenis, string $pesan): void
{
    $_SESSION['flash'] = ['jenis' => $jenis, 'pesan' => $pesan];
}

function ambil_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- Foto ---------- */

// Validasi dan simpan foto upload. Mengembalikan [namaFile, pesanError].
function simpanFoto($berkas): array
{
    if (!$berkas || $berkas['error'] === UPLOAD_ERR_NO_FILE) {
        return ['', ''];
    }
    if (
        $berkas['error'] === UPLOAD_ERR_INI_SIZE ||
        $berkas['error'] === UPLOAD_ERR_FORM_SIZE ||
        $berkas['size'] > FOTO_MAKS
    ) {
        return ['', 'Ukuran foto maksimal 3 MB.'];
    }
    if ($berkas['error'] !== UPLOAD_ERR_OK) {
        return ['', 'Foto gagal diunggah. Coba lagi.'];
    }

    $info = @getimagesize($berkas['tmp_name']);
    $jenis = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = $info['mime'] ?? '';
    if (!isset($jenis[$mime])) {
        return ['', 'Format foto harus JPG, PNG, atau WebP.'];
    }

    if (!is_dir(FOTO_DIR) && !mkdir(FOTO_DIR, 0775, true)) {
        return ['', 'Folder foto tidak bisa dibuat.'];
    }

    $nama = bin2hex(random_bytes(8)) . '.' . $jenis[$mime];
    if (!move_uploaded_file($berkas['tmp_name'], FOTO_DIR . '/' . $nama)) {
        return ['', 'Foto gagal disimpan.'];
    }
    return [$nama, ''];
}

// Hapus berkas foto dari folder, kecuali masih dipakai produk lain.
function hapusBerkasFoto(PDO $pdo, $nama, int $kecualiId): void
{
    $nama = basename((string) $nama);
    if ($nama === '' || !preg_match('/^[\w.-]+$/', $nama)) {
        return;
    }

    $cek = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE foto = ? AND id <> ?');
    $cek->execute([$nama, $kecualiId]);
    if ((int) $cek->fetchColumn() > 0) {
        return;
    }

    $jalur = FOTO_DIR . '/' . $nama;
    if (is_file($jalur)) {
        @unlink($jalur);
    }
}
