<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/masuk.php');
    exit;
}

require_once __DIR__ . '/../../inti/koneksi.php';

// Sesuaikan kalau nama tabel/kolom akun admin kamu berbeda.
const TABEL_ADMIN = 'admin';
const KOLOM_SANDI = 'password';

function aman($nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
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

function kembali_ke_profil(string $jenis, string $pesan): void
{
    $_SESSION['flash'] = ['jenis' => $jenis, 'pesan' => $pesan];
    header('Location: /admin/profil.php');
    exit;
}

/* ---------- Ambil data akun ---------- */

$idAdmin = (int) $_SESSION['admin_id'];
$adaEmail = true;

try {
    $q = $pdo->prepare(
        'SELECT id, nama, email, ' . KOLOM_SANDI . ' AS sandi FROM ' . TABEL_ADMIN . ' WHERE id = ?'
    );
    $q->execute([$idAdmin]);
} catch (PDOException $e) {
    // Kolom email belum ada: tetap tampilkan halaman tanpa field email.
    $adaEmail = false;
    try {
        $q = $pdo->prepare(
            'SELECT id, nama, ' . KOLOM_SANDI . ' AS sandi FROM ' . TABEL_ADMIN . ' WHERE id = ?'
        );
        $q->execute([$idAdmin]);
    } catch (PDOException $e2) {
        http_response_code(500);
        exit('Tabel akun admin tidak ditemukan. Cek TABEL_ADMIN dan KOLOM_SANDI di profil.php.');
    }
}

$admin = $q->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    session_destroy();
    header('Location: /admin/masuk.php');
    exit;
}

$_SESSION['admin_nama'] = $admin['nama'];

$galatProfil = '';
$galatSandi = '';
$nilaiNama = (string) $admin['nama'];
$nilaiEmail = $adaEmail ? (string) ($admin['email'] ?? '') : '';

/* ---------- Simpan profil ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'profil') {
    $nilaiNama = trim((string) ($_POST['nama'] ?? ''));
    $nilaiEmail = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $galatProfil = 'Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (mb_strlen($nilaiNama) < 2 || mb_strlen($nilaiNama) > 60) {
        $galatProfil = 'Nama harus 2 sampai 60 karakter.';
    } elseif ($adaEmail && $nilaiEmail !== '' && !filter_var($nilaiEmail, FILTER_VALIDATE_EMAIL)) {
        $galatProfil = 'Format email belum benar. Contoh: nama@gmail.com';
    } else {
        try {
            $dipakai = false;
            if ($adaEmail && $nilaiEmail !== '') {
                $cek = $pdo->prepare('SELECT COUNT(*) FROM ' . TABEL_ADMIN . ' WHERE email = ? AND id <> ?');
                $cek->execute([$nilaiEmail, $idAdmin]);
                $dipakai = (int) $cek->fetchColumn() > 0;
            }

            if ($dipakai) {
                $galatProfil = 'Email ini sudah dipakai akun lain.';
            } else {
                if ($adaEmail) {
                    $u = $pdo->prepare('UPDATE ' . TABEL_ADMIN . ' SET nama = ?, email = ? WHERE id = ?');
                    $u->execute([$nilaiNama, $nilaiEmail !== '' ? $nilaiEmail : null, $idAdmin]);
                } else {
                    $u = $pdo->prepare('UPDATE ' . TABEL_ADMIN . ' SET nama = ? WHERE id = ?');
                    $u->execute([$nilaiNama, $idAdmin]);
                }
                $_SESSION['admin_nama'] = $nilaiNama;
                kembali_ke_profil('sukses', 'Profil sudah diperbarui.');
            }
        } catch (PDOException $e) {
            $galatProfil = 'Profil gagal disimpan. Coba lagi.';
        }
    }
}

/* ---------- Ubah kata sandi ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'sandi') {
    $lama = (string) ($_POST['lama'] ?? '');
    $baru = (string) ($_POST['baru'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');

    if (($_SESSION['kunci_sampai'] ?? 0) > time()) {
        $galatSandi = 'Terlalu banyak percobaan salah. Coba lagi beberapa menit lagi.';
    } elseif (!csrf_valid($_POST['csrf'] ?? null)) {
        $galatSandi = 'Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($lama === '' || $baru === '' || $konfirmasi === '') {
        $galatSandi = 'Semua kolom kata sandi wajib diisi.';
    } elseif (!password_verify($lama, (string) $admin['sandi'])) {
        $_SESSION['gagal_sandi'] = ($_SESSION['gagal_sandi'] ?? 0) + 1;
        if ($_SESSION['gagal_sandi'] >= 5) {
            $_SESSION['kunci_sampai'] = time() + 300;
            $_SESSION['gagal_sandi'] = 0;
        }
        $galatSandi = 'Kata sandi lama salah.';
    } elseif (strlen($baru) < 12) {
        $galatSandi = 'Kata sandi baru minimal 12 karakter.';
    } elseif ($baru !== $konfirmasi) {
        $galatSandi = 'Konfirmasi tidak sama dengan kata sandi baru.';
    } elseif (hash_equals($lama, $baru)) {
        $galatSandi = 'Kata sandi baru harus berbeda dari yang lama.';
    } else {
        try {
            $u = $pdo->prepare('UPDATE ' . TABEL_ADMIN . ' SET ' . KOLOM_SANDI . ' = ? WHERE id = ?');
            $u->execute([password_hash($baru, PASSWORD_DEFAULT), $idAdmin]);
            unset($_SESSION['gagal_sandi'], $_SESSION['kunci_sampai']);
            session_regenerate_id(true);
            kembali_ke_profil('sukses', 'Kata sandi sudah diganti.');
        } catch (PDOException $e) {
            $galatSandi = 'Kata sandi gagal diganti. Coba lagi.';
        }
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$csrf = csrf_token();
$namaAdmin = (string) $admin['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#10261F">
    <title>Profil | Lapakly</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --hutan: #10261F;
            --lime: #D7F5A5;
            --latar: #F3F6F2;
            --putih: #ffffff;
            --teks: #26372F;
            --redup: #78847B;
            --garis: #E2E8E0;
            --daun: #3F6B2C;
            --bata: #B8422E;
            --lebar-samping: 244px;
            --font: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body.kunci { overflow: hidden; }

        body {
            margin: 0;
            background: var(--latar);
            color: var(--teks);
            font-family: var(--font);
            font-size: 15px;
            line-height: 1.5;
        }

        a { color: inherit; }
        button { font-family: inherit; }

        .i {
            width: 20px; height: 20px;
            flex: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        :focus-visible { outline: 3px solid #8fc25a; outline-offset: 2px; }

        /* ---------- Kerangka ---------- */
        .app {
            display: grid;
            grid-template-columns: var(--lebar-samping) minmax(0, 1fr);
            min-height: 100vh;
        }

        .samping {
            position: sticky;
            top: 0;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            padding: 1.25rem 1rem;
            background: var(--hutan);
            color: #cfe0d4;
            overflow-y: auto;
        }

        .merek {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .25rem .5rem 1.6rem;
            color: #fff;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -.04em;
            text-decoration: none;
        }
        .merek-ikon {
            display: grid;
            place-items: center;
            width: 38px; height: 38px;
            border-radius: 11px;
            background: var(--lime);
            color: var(--hutan);
        }

        .menu-judul {
            margin: 0 .75rem .6rem;
            color: #7f9a8b;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .menu { display: flex; flex-direction: column; gap: .2rem; }
        .menu a, .keluar {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: .7rem .75rem;
            border-radius: 11px;
            color: #cfe0d4;
            font-size: .95rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .2s, color .2s;
        }
        .menu a:hover, .keluar:hover { background: rgba(255, 255, 255, .06); color: #fff; }
        .menu a[aria-current="page"] { background: rgba(255, 255, 255, .1); color: #fff; }
        .menu a[aria-current="page"] .i { color: var(--lime); }

        .samping-bawah { margin-top: auto; padding-top: 1.5rem; }
        .kartu-kecil {
            margin-bottom: .8rem;
            padding: .9rem 1rem;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
            background: rgba(255, 255, 255, .04);
            font-size: .8rem;
            line-height: 1.5;
            color: #b8cdbf;
        }
        .kartu-kecil strong { display: block; margin-bottom: .2rem; color: var(--lime); font-weight: 600; }

        .tirai { display: none; }

        .utama { min-width: 0; }

        .atas {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: .9rem;
            min-height: 64px;
            padding: .5rem clamp(1rem, 3vw, 2rem);
            background: var(--putih);
            border-bottom: 1px solid var(--garis);
        }
        .hamburger {
            display: none;
            place-items: center;
            width: 42px; height: 42px;
            margin-left: -.4rem;
            border: 1px solid var(--garis);
            border-radius: 11px;
            background: var(--putih);
            color: var(--hutan);
            cursor: pointer;
        }
        .jejak { display: flex; flex-direction: column; min-width: 0; line-height: 1.25; }
        .jejak small { color: var(--redup); font-size: .72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .jejak strong { color: var(--hutan); font-size: 1.05rem; font-weight: 600; }

        .pengguna { display: flex; align-items: center; gap: .65rem; margin-left: auto; }
        .avatar {
            display: grid;
            place-items: center;
            width: 40px; height: 40px;
            border-radius: 12px;
            background: #EAF2E4;
            color: var(--hutan);
            font-weight: 600;
        }
        .pengguna-info { display: flex; flex-direction: column; line-height: 1.25; }
        .pengguna-info strong { font-size: .8rem; font-weight: 600; color: var(--hutan); }
        .pengguna-info span { display: flex; align-items: center; gap: .35rem; color: var(--redup); font-size: .7rem; }
        .pengguna-info span::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #5a9a3c; }

        .isi { width: 100%; max-width: 1180px; margin: 0 auto; padding: clamp(1.25rem, 3vw, 2rem); }

        /* ---------- Judul halaman ---------- */
        .kepala {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .label {
            margin: 0;
            color: #64864B;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .16em;
        }
        h1 {
            margin: .35rem 0;
            color: var(--hutan);
            font-size: clamp(1.7rem, 4.5vw, 2.4rem);
            font-weight: 700;
            letter-spacing: -.04em;
            line-height: 1.1;
        }
        .deskripsi { margin: 0; color: var(--redup); max-width: 52ch; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 44px;
            padding: .65rem 1.1rem;
            border: 1px solid transparent;
            border-radius: 11px;
            background: var(--hutan);
            color: #fff;
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, transform .12s, border-color .2s;
        }
        .btn:hover { background: #234738; }
        .btn:active { transform: scale(.97); }
        .btn-garis { background: var(--putih); color: var(--hutan); border-color: var(--garis); }
        .btn-garis:hover { background: #F3F6F2; border-color: #c3cfc1; }
        .btn-bahaya { background: var(--bata); }
        .btn-bahaya:hover { background: #9c3624; }

        a.pengguna {
            padding: .3rem .55rem .3rem .3rem;
            border-radius: 14px;
            color: inherit;
            text-decoration: none;
            transition: background .2s;
        }
        a.pengguna:hover { background: #F2F5F0; }
        /* ---------- Pesan ---------- */
        .pesan {
            position: fixed;
            top: calc(76px + env(safe-area-inset-top, 0px));
            right: clamp(1rem, 3vw, 2rem);
            z-index: 30;
            display: flex;
            align-items: center;
            gap: .65rem;
            max-width: min(420px, calc(100vw - 2rem));
            padding: .75rem .8rem .75rem 1rem;
            border-radius: 13px;
            background: var(--hutan);
            color: #fff;
            font-size: .88rem;
            font-weight: 500;
            box-shadow: 0 12px 30px rgba(16, 42, 34, .3);
            animation: pesan 5.5s cubic-bezier(.3, 1.2, .5, 1) forwards;
        }
        .pesan .i { color: var(--lime); }
        .pesan-galat { background: #7a2a1b; }
        .pesan-galat .i { color: #ffd2c8; }
        .pesan button {
            display: grid;
            place-items: center;
            width: 30px; height: 30px;
            margin-left: .25rem;
            border: 0;
            border-radius: 8px;
            background: rgba(255, 255, 255, .12);
            color: inherit;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        /* ---------- Profil ---------- */
        .grid-profil { display: grid; gap: 1.25rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
        @media (min-width: 1000px) {
            .grid-profil { grid-template-columns: 300px minmax(0, 1fr); gap: 1.5rem; }
            .kartu-diri { position: sticky; top: 88px; }
        }
        .kolom-form { display: grid; gap: 1.25rem; min-width: 0; }

        .kartu-diri {
            padding: 1.5rem;
            border: 1px solid var(--garis);
            border-radius: 16px;
            background: var(--putih);
            text-align: center;
        }
        .avatar-besar {
            display: grid;
            place-items: center;
            width: 76px; height: 76px;
            margin: 0 auto .9rem;
            border-radius: 22px;
            background: var(--hutan);
            color: var(--lime);
            font-size: 2rem;
            font-weight: 700;
        }
        .kartu-diri h2 { margin: 0; color: var(--hutan); font-size: 1.2rem; letter-spacing: -.02em; overflow-wrap: anywhere; }
        .peran { margin: .2rem 0 1.1rem; color: var(--redup); font-size: .82rem; }
        .rincian { margin: 0; padding-top: 1rem; border-top: 1px dashed var(--garis); text-align: left; display: grid; gap: .7rem; }
        .rincian div { display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem; }
        .rincian dt { color: var(--redup); }
        .rincian dd { margin: 0; color: var(--hutan); font-weight: 600; text-align: right; overflow-wrap: anywhere; }

        .panel {
            border: 1px solid var(--garis);
            border-radius: 16px;
            background: var(--putih);
            box-shadow: 0 10px 35px rgba(16, 42, 34, .04);
            overflow: hidden;
        }
        .panel-kepala { padding: 1.2rem 1.4rem .9rem; border-bottom: 1px solid var(--garis); }
        .panel-kepala h2 { margin: 0 0 .15rem; color: var(--hutan); font-size: 1.05rem; font-weight: 600; }
        .panel-kepala p { margin: 0; color: var(--redup); font-size: .85rem; }
        .panel-isi { padding: 1.3rem 1.4rem 1.4rem; }

        .bidang { margin-bottom: 1.15rem; }
        .bidang label { display: block; margin-bottom: .4rem; color: var(--hutan); font-size: .88rem; font-weight: 600; }
        .bidang input {
            width: 100%;
            min-height: 46px;
            padding: .65rem .9rem;
            border: 1px solid var(--garis);
            border-radius: 11px;
            background: #FAFBF9;
            color: var(--teks);
            font: inherit;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .bidang input:focus { outline: none; background: #fff; border-color: var(--hutan); box-shadow: 0 0 0 4px rgba(215, 245, 165, .7); }
        .bidang input:user-invalid { border-color: var(--bata); }
        .pw { position: relative; }
        .pw input { padding-right: 3.2rem; }
        .mata {
            position: absolute;
            top: 50%; right: .35rem;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            width: 40px; height: 40px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--redup);
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .mata:hover, .mata[aria-pressed="true"] { background: #EEF2EC; color: var(--hutan); }
        .bantu { display: block; margin-top: .4rem; color: var(--redup); font-size: .8rem; }

        .kekuatan { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-top: .6rem; }
        .kekuatan span { height: 5px; border-radius: 3px; background: #E2E8E0; transition: background .3s; }
        .kekuatan[data-n="1"] span:nth-child(-n+1) { background: #d9644f; }
        .kekuatan[data-n="2"] span:nth-child(-n+2) { background: #e4a63a; }
        .kekuatan[data-n="3"] span:nth-child(-n+3) { background: #8fbf4f; }
        .kekuatan[data-n="4"] span:nth-child(-n+4) { background: #3f8f4a; }
        #cocok[data-s="ok"] { color: var(--daun); font-weight: 600; }
        #cocok[data-s="beda"] { color: var(--bata); font-weight: 600; }

        .galat {
            margin-bottom: 1.15rem;
            padding: .75rem 1rem;
            border-left: 5px solid var(--bata);
            border-radius: 4px 11px 11px 4px;
            background: #FBEDEA;
            color: #7d2515;
            font-weight: 500;
            font-size: .9rem;
            animation: getar .45s;
        }
        .catatan {
            margin-bottom: 1.15rem;
            padding: .8rem 1rem;
            border-radius: 11px;
            background: #F6F2E2;
            color: #6b5a1e;
            font-size: .85rem;
            line-height: 1.6;
        }
        .catatan code { display: block; margin-top: .4rem; padding: .4rem .6rem; border-radius: 8px; background: rgba(0, 0, 0, .06); font-size: .8rem; overflow-wrap: anywhere; }

        .aksi-form { display: flex; justify-content: flex-end; margin-top: 1.4rem; }
        .btn.proses { opacity: .75; pointer-events: none; }

        @media (max-width: 600px) {
            .aksi-form .btn { width: 100%; }
            .panel-kepala, .panel-isi { padding-left: 1.1rem; padding-right: 1.1rem; }
        }

        @keyframes getar {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-7px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(2px); }
        }

        @media (max-width: 899px) {
            .app { display: block; }
            .hamburger { display: grid; }

            .samping {
                position: fixed;
                top: 0; bottom: 0; left: 0;
                z-index: 50;
                width: min(84vw, 290px);
                height: auto;
                transform: translateX(-100%);
                visibility: hidden;
                transition: transform .3s cubic-bezier(.3, .8, .3, 1), visibility 0s .3s;
            }
            .app.terbuka .samping { transform: none; visibility: visible; transition-delay: 0s; }

            .tirai {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 40;
                background: rgba(16, 38, 31, .55);
                opacity: 0;
                pointer-events: none;
                transition: opacity .3s;
            }
            .app.terbuka .tirai { opacity: 1; pointer-events: auto; }
        }

        @media (max-width: 520px) {
            .pengguna-info { display: none; }
            .pesan { left: 1rem; right: 1rem; max-width: none; }
        }

        @keyframes pesan {
            0%   { opacity: 0; transform: translateY(-12px); }
            8%   { opacity: 1; transform: none; }
            88%  { opacity: 1; transform: none; }
            100% { opacity: 0; transform: translateY(-12px); visibility: hidden; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-layers" viewBox="0 0 24 24"><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/></symbol>
    <symbol id="i-luar" viewBox="0 0 24 24"><path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></symbol>
    <symbol id="i-keluar" viewBox="0 0 24 24"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4"/><path d="M6 12h10"/></symbol>
    <symbol id="i-daun" viewBox="0 0 24 24"><path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14"/><path d="M5 19c3-5 6-8 10-10"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
    <symbol id="i-cari" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/></symbol>
    <symbol id="i-tambah" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-pensil" viewBox="0 0 24 24"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></symbol>
    <symbol id="i-sampah" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/><path d="M9 7V4h6v3"/></symbol>
    <symbol id="i-centang" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/></symbol>
    <symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></symbol>
    <symbol id="i-mata" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="i-mata-coret" viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.5 6.6C3.7 8.3 2 12 2 12s3.6 7 10 7c1.6 0 3-.4 4.3-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></symbol>
</svg>

<?php if ($flash): ?>
    <?php $sukses = $flash['jenis'] === 'sukses'; ?>
    <div class="pesan <?= $sukses ? 'pesan-sukses' : 'pesan-galat' ?>" role="status" id="pesan">
        <svg class="i"><use href="#i-<?= $sukses ? 'centang' : 'info' ?>"/></svg>
        <span><?= aman($flash['pesan']) ?></span>
        <button type="button" id="pesan-tutup" aria-label="Tutup pesan">&times;</button>
    </div>
<?php endif; ?>

<div class="app" id="app">

    <div class="tirai" id="tirai"></div>

    <aside class="samping" id="samping" aria-label="Menu utama">
        <a class="merek" href="/admin/index.php">
            <span class="merek-ikon"><svg class="i"><use href="#i-daun"/></svg></span>
            Lapakly.
        </a>

        <p class="menu-judul">Menu utama</p>
        <nav class="menu">
            <a href="/admin/index.php"><svg class="i"><use href="#i-grid"/></svg>Dashboard</a>
            <a href="/admin/produk/index.php"><svg class="i"><use href="#i-layers"/></svg>Produk</a>
            <a href="/" target="_blank" rel="noopener"><svg class="i"><use href="#i-luar"/></svg>Lihat Katalog</a>
        </nav>

        <div class="samping-bawah">
            <div class="kartu-kecil">
                <strong>Lapakly Workspace</strong>
                Kelola produk dan jaga katalog usahamu tetap terbaru.
            </div>
            <a class="keluar" href="/admin/keluar.php"><svg class="i"><use href="#i-keluar"/></svg>Keluar dari akun</a>
        </div>
    </aside>

    <div class="utama">

        <header class="atas">
            <button type="button" class="hamburger" id="buka" aria-label="Buka menu" aria-controls="samping" aria-expanded="false">
                <svg class="i"><use href="#i-menu"/></svg>
            </button>
            <div class="jejak">
                <small>Lapakly Workspace / Admin</small>
                <strong>Profil</strong>
            </div>
            <a class="pengguna" href="/admin/profil.php" title="Pengaturan profil" aria-label="Pengaturan profil">
                <span class="avatar" aria-hidden="true"><?= aman(mb_substr(inisial($namaAdmin), 0, 1)) ?></span>
                <span class="pengguna-info">
                    <strong><?= aman($namaAdmin) ?></strong>
                    <span>Administrator</span>
                </span>
            </a>
        </header>


        <main class="isi">

            <header class="kepala">
                <div>
                    <p class="label">PENGATURAN AKUN</p>
                    <h1>Profil kamu.</h1>
                    <p class="deskripsi">Ubah nama, email, dan kata sandi akunmu di sini.</p>
                </div>
            </header>

            <div class="grid-profil">

                <aside class="kartu-diri">
                    <span class="avatar-besar" aria-hidden="true"><?= aman(mb_substr(inisial($namaAdmin), 0, 1)) ?></span>
                    <h2><?= aman($namaAdmin) ?></h2>
                    <p class="peran">Administrator</p>
                    <dl class="rincian">
                        <div>
                            <dt>Email</dt>
                            <dd><?= $adaEmail && ($admin['email'] ?? '') !== '' ? aman($admin['email']) : 'Belum diisi' ?></dd>
                        </div>
                        <div>
                            <dt>ID akun</dt>
                            <dd>#<?= (int) $admin['id'] ?></dd>
                        </div>
                    </dl>
                </aside>

                <div class="kolom-form">

                    <section class="panel">
                        <div class="panel-kepala">
                            <h2>Informasi akun</h2>
                            <p>Nama ini tampil di dashboard dan pojok kanan atas.</p>
                        </div>

                        <form method="POST" action="" class="panel-isi">
                            <input type="hidden" name="csrf" value="<?= aman($csrf) ?>">
                            <input type="hidden" name="aksi" value="profil">

                            <?php if ($galatProfil !== ''): ?>
                                <div class="galat" role="alert"><?= aman($galatProfil) ?></div>
                            <?php endif; ?>

                            <div class="bidang">
                                <label for="nama">Nama</label>
                                <input type="text" id="nama" name="nama" maxlength="60" required
                                       autocomplete="name" value="<?= aman($nilaiNama) ?>">
                            </div>

                            <?php if ($adaEmail): ?>
                                <div class="bidang">
                                    <label for="email">Alamat email</label>
                                    <input type="email" id="email" name="email" maxlength="150"
                                           autocomplete="email" inputmode="email"
                                           placeholder="nama@gmail.com" value="<?= aman($nilaiEmail) ?>">
                                    <span class="bantu">Opsional. Dipakai sebagai kontak akun.</span>
                                </div>
                            <?php else: ?>
                                <div class="catatan">
                                    Kolom email belum ada di tabel <code><?= aman(TABEL_ADMIN) ?></code>.
                                    Jalankan perintah ini di database, lalu muat ulang halaman:
                                    <code>ALTER TABLE <?= aman(TABEL_ADMIN) ?> ADD COLUMN email VARCHAR(150);</code>
                                </div>
                            <?php endif; ?>

                            <div class="aksi-form">
                                <button type="submit" class="btn" data-kirim="Menyimpan">Simpan profil</button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-kepala">
                            <h2>Ubah kata sandi</h2>
                            <p>Masukkan kata sandi lama, lalu pilih yang baru.</p>
                        </div>

                        <form method="POST" action="" class="panel-isi" id="form-sandi">
                            <input type="hidden" name="csrf" value="<?= aman($csrf) ?>">
                            <input type="hidden" name="aksi" value="sandi">

                            <?php if ($galatSandi !== ''): ?>
                                <div class="galat" role="alert"><?= aman($galatSandi) ?></div>
                            <?php endif; ?>

                            <div class="bidang">
                                <label for="pw-lama">Kata sandi lama</label>
                                <div class="pw">
                                    <input type="password" id="pw-lama" name="lama" required autocomplete="current-password">
                                    <button type="button" class="mata" data-target="pw-lama" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                        <svg class="i"><use href="#i-mata"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="bidang">
                                <label for="pw-baru">Kata sandi baru</label>
                                <div class="pw">
                                    <input type="password" id="pw-baru" name="baru" required minlength="8" autocomplete="new-password" aria-describedby="kekuatan-teks">
                                    <button type="button" class="mata" data-target="pw-baru" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                        <svg class="i"><use href="#i-mata"/></svg>
                                    </button>
                                </div>
                                <div class="kekuatan" id="kekuatan" data-n="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                                <span class="bantu" id="kekuatan-teks">Minimal 8 karakter.</span>
                            </div>

                            <div class="bidang">
                                <label for="pw-konfirmasi">Konfirmasi kata sandi baru</label>
                                <div class="pw">
                                    <input type="password" id="pw-konfirmasi" name="konfirmasi" required autocomplete="new-password" aria-describedby="cocok">
                                    <button type="button" class="mata" data-target="pw-konfirmasi" aria-label="Tampilkan kata sandi" aria-pressed="false">
                                        <svg class="i"><use href="#i-mata"/></svg>
                                    </button>
                                </div>
                                <span class="bantu" id="cocok" data-s="" role="status"></span>
                            </div>

                            <div class="aksi-form">
                                <button type="submit" class="btn" data-kirim="Menyimpan">Ubah kata sandi</button>
                            </div>
                        </form>
                    </section>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);

    /* Menu samping (hamburger) */
    const app = $('app'), buka = $('buka'), samping = $('samping');
    function menu(terbuka, fokusTombol) {
        app.classList.toggle('terbuka', terbuka);
        document.body.classList.toggle('kunci', terbuka);
        buka.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        buka.setAttribute('aria-label', terbuka ? 'Tutup menu' : 'Buka menu');
        if (terbuka) samping.querySelector('a').focus();
        else if (fokusTombol) buka.focus();
    }
    buka.addEventListener('click', () => menu(!app.classList.contains('terbuka'), true));
    $('tirai').addEventListener('click', () => menu(false, true));
    samping.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => menu(false, false)));
    addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && app.classList.contains('terbuka')) menu(false, true);
    });
    matchMedia('(min-width: 900px)').addEventListener('change', (e) => { if (e.matches) menu(false, false); });

    /* Pesan */
    const pesan = $('pesan');
    if (pesan) {
        $('pesan-tutup').addEventListener('click', () => pesan.remove());
        setTimeout(() => pesan.remove(), 5600);
    }


    /* Tampilkan / sembunyikan kata sandi */
    document.querySelectorAll('.mata').forEach((b) => b.addEventListener('click', () => {
        const input = $(b.dataset.target);
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        b.setAttribute('aria-pressed', tampil ? 'true' : 'false');
        b.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        b.querySelector('use').setAttribute('href', tampil ? '#i-mata-coret' : '#i-mata');
    }));

    /* Kekuatan kata sandi baru + cek konfirmasi */
    const baru = $('pw-baru'), konf = $('pw-konfirmasi');
    const teksKekuatan = [
        'Minimal 8 karakter.',
        'Lemah. Tambah panjang dan variasi huruf.',
        'Cukup. Tambah angka atau simbol.',
        'Bagus.',
        'Kuat.'
    ];

    function skor(p) {
        if (!p) return 0;
        let n = 0;
        if (p.length >= 8) n++;
        if (p.length >= 12) n++;
        if (/[a-z]/.test(p) && /[A-Z]/.test(p) && /\d/.test(p)) n++;
        if (/[^A-Za-z0-9]/.test(p)) n++;
        if (p.length < 8) n = Math.min(n, 1);
        return Math.max(n, 1);
    }

    function cekKonfirmasi() {
        const el = $('cocok');
        if (!konf.value) { el.dataset.s = ''; el.textContent = ''; return; }
        const sama = konf.value === baru.value;
        el.dataset.s = sama ? 'ok' : 'beda';
        el.textContent = sama ? 'Cocok.' : 'Belum sama dengan kata sandi baru.';
    }

    baru.addEventListener('input', () => {
        const n = skor(baru.value);
        $('kekuatan').dataset.n = n;
        $('kekuatan-teks').textContent = teksKekuatan[n];
        cekKonfirmasi();
    });
    konf.addEventListener('input', cekKonfirmasi);

    /* Cegah klik ganda saat kirim */
    document.querySelectorAll('form').forEach((form) => {
        let terkirim = false;
        form.addEventListener('submit', (e) => {
            if (terkirim) { e.preventDefault(); return; }
            terkirim = true;
            const tombol = form.querySelector('[data-kirim]');
            if (tombol) { tombol.dataset.awal = tombol.textContent; tombol.classList.add('proses'); tombol.textContent = tombol.dataset.kirim; }
        });
    });
    addEventListener('pageshow', () => document.querySelectorAll('.btn.proses').forEach((b) => {
        b.classList.remove('proses');
        if (b.dataset.awal) b.textContent = b.dataset.awal;
    }));
})();
</script>

</body>
</html>
