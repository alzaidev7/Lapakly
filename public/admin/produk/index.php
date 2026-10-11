<?php
require_once __DIR__ . '/_bantu.php';

$produk = $pdo
    ->query('SELECT id, nama, kategori, harga, status, foto FROM produk ORDER BY id DESC')
    ->fetchAll(PDO::FETCH_ASSOC);

$total = count($produk);
$aktif = 0;
foreach ($produk as $item) {
    if ($item['status'] === 'aktif') {
        $aktif++;
    }
}
$nonaktif = $total - $aktif;

$flash = ambil_flash();
$csrf = csrf_token();
$namaAdmin = nama_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#10261F">
    <title>Kelola Produk | Lapakly</title>
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

        /* ---------- Panel & alat ---------- */
        .panel {
            overflow: hidden;
            border: 1px solid var(--garis);
            border-radius: 16px;
            background: var(--putih);
            box-shadow: 0 10px 35px rgba(16, 42, 34, .04);
        }
        .panel-atas {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
            padding: 1.1rem 1.25rem .4rem;
        }
        .panel-atas h2 { margin: 0; color: var(--hutan); font-size: 1.05rem; font-weight: 600; }
        .jumlah { color: var(--redup); font-size: .8rem; }

        .alat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            padding: .75rem 1.25rem 1rem;
            border-bottom: 1px solid var(--garis);
        }
        .cari { position: relative; flex: 1 1 220px; max-width: 360px; }
        .cari .i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--redup); width: 18px; height: 18px; pointer-events: none; }
        .cari input {
            width: 100%;
            min-height: 42px;
            padding: .55rem .9rem .55rem 2.5rem;
            border: 1px solid var(--garis);
            border-radius: 11px;
            background: #FAFBF9;
            color: var(--teks);
            font: inherit;
            font-size: .9rem;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .cari input:focus { outline: none; background: #fff; border-color: var(--hutan); box-shadow: 0 0 0 4px rgba(215, 245, 165, .7); }

        .filter { display: flex; gap: .25rem; padding: 4px; border-radius: 12px; background: #EEF2EC; }
        .filter button {
            min-height: 34px;
            padding: .3rem .8rem;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--redup);
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .filter button[aria-pressed="true"] { background: var(--putih); color: var(--hutan); box-shadow: 0 1px 3px rgba(16, 42, 34, .15); }
        .filter small { margin-left: .2rem; font-weight: 500; opacity: .7; }

        /* ---------- Tabel ---------- */
        .gulir { overflow-x: auto; }
        .tabel { width: 100%; border-collapse: collapse; }
        .tabel th, .tabel td { padding: .9rem 1.25rem; text-align: left; border-bottom: 1px solid #EDF0EC; vertical-align: middle; }
        .tabel th {
            background: #FAFBF9;
            color: var(--redup);
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .tabel tbody tr { transition: background .2s; }
        .tabel tbody tr:hover { background: #FAFCF8; }
        .tabel tr[hidden] { display: none !important; }
        .th-aksi { text-align: right !important; }

        .produk-sel { display: flex; align-items: center; gap: .9rem; min-width: 0; }
        .thumb {
            position: relative;
            display: grid;
            place-items: center;
            flex: none;
            width: 46px; height: 46px;
            border-radius: 11px;
            overflow: hidden;
            background: hsl(var(--h, 150) 36% 40%);
            color: rgba(255, 255, 255, .92);
            font-size: .85rem;
            font-weight: 700;
        }
        .thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .nama { display: block; color: var(--hutan); font-weight: 600; overflow-wrap: anywhere; }
        .id { display: block; color: var(--redup); font-size: .75rem; }
        .sel-harga { font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .sel-kategori { white-space: nowrap; }

        .status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            background: #EAF7DE;
            color: var(--daun);
            font-size: .75rem;
            font-weight: 600;
        }
        .status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .status.nonaktif { background: #F1F1EF; color: #666D67; }

        .aksi-sel { display: flex; justify-content: flex-end; gap: .45rem; }
        .btn-aksi {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            min-height: 38px;
            padding: .4rem .8rem;
            border: 1px solid var(--garis);
            border-radius: 10px;
            background: var(--putih);
            color: var(--hutan);
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, border-color .2s, color .2s;
        }
        .btn-aksi .i { width: 17px; height: 17px; }
        .btn-aksi:hover { background: #F3F6F2; border-color: #b9c7b6; }
        .btn-aksi.hapus { color: var(--bata); }
        .btn-aksi.hapus:hover { background: #FBEDEA; border-color: #e6b4aa; }

        .kosong-blok { padding: 3rem 1.25rem; text-align: center; }
        .kosong-ikon {
            display: grid;
            place-items: center;
            width: 56px; height: 56px;
            margin: 0 auto .9rem;
            border-radius: 16px;
            background: #EAF2E4;
            color: var(--hutan);
        }
        .kosong-ikon .i { width: 26px; height: 26px; }
        .kosong-blok h3 { margin: 0 0 .4rem; color: var(--hutan); font-size: 1.1rem; }
        .kosong-blok p { margin: 0 auto 1.25rem; max-width: 40ch; color: var(--redup); }
        .kosong-blok[hidden] { display: none; }

        .bawah { padding: 1rem 1.25rem; color: var(--redup); font-size: .78rem; }

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

        /* ---------- Dialog hapus ---------- */
        .dialog {
            width: calc(100% - 2rem);
            max-width: 400px;
            padding: 0;
            border: 0;
            border-radius: 20px;
            background: var(--putih);
            color: var(--teks);
            box-shadow: 0 30px 60px rgba(0, 0, 0, .35);
        }
        .dialog[open] { animation: dialog .28s cubic-bezier(.2, 1.3, .5, 1); }
        .dialog::backdrop { background: rgba(16, 38, 31, .6); backdrop-filter: blur(3px); animation: pudar .25s; }
        .dialog-isi { padding: 1.5rem 1.4rem 1.25rem; text-align: center; }
        .dialog-ikon {
            display: grid;
            place-items: center;
            width: 54px; height: 54px;
            margin: 0 auto .9rem;
            border-radius: 16px;
            background: #FBEDEA;
            color: var(--bata);
        }
        .dialog-ikon .i { width: 26px; height: 26px; }
        .dialog h2 { margin: 0 0 .5rem; color: var(--hutan); font-size: 1.2rem; font-weight: 700; letter-spacing: -.02em; }
        .dialog p { margin: 0 0 1.25rem; color: var(--redup); }
        .dialog p strong { color: var(--hutan); overflow-wrap: anywhere; }
        .dialog-aksi { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }

        /* ---------- Responsif ---------- */
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

        @media (max-width: 720px) {
            .tabel, .tabel tbody { display: block; }
            .tabel thead { display: none; }
            .tabel tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-areas: "p p" "k h" "s a";
                gap: .85rem 1rem;
                padding: 1rem 1.1rem;
                border-bottom: 1px solid var(--garis);
            }
            .tabel td { display: block; padding: 0; border: 0; }
            .sel-produk { grid-area: p; }
            .sel-kategori { grid-area: k; }
            .sel-harga { grid-area: h; }
            .sel-status { grid-area: s; align-self: center; }
            .sel-aksi { grid-area: a; }
            .sel-kategori::before, .sel-harga::before {
                content: attr(data-label);
                display: block;
                color: var(--redup);
                font-size: .7rem;
                font-weight: 500;
            }
            .tabel tbody tr:last-child { border-bottom: 0; }
            .cari { max-width: none; }
            .filter { width: 100%; }
            .filter button { flex: 1; }
            .kepala .btn { width: 100%; }
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
        @keyframes dialog { from { opacity: 0; transform: translateY(14px) scale(.94); } to { opacity: 1; transform: none; } }
        @keyframes pudar { from { opacity: 0; } to { opacity: 1; } }

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
            <a href="/admin/produk/index.php" aria-current="page"><svg class="i"><use href="#i-layers"/></svg>Produk</a>
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
                <strong>Produk</strong>
            </div>
            <div class="pengguna">
                <span class="avatar" aria-hidden="true"><?= aman(mb_substr(inisial($namaAdmin), 0, 1)) ?></span>
                <div class="pengguna-info">
                    <strong><?= aman($namaAdmin) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>
        </header>

        <main class="isi">

            <header class="kepala">
                <div>
                    <p class="label">PENGELOLAAN TOKO</p>
                    <h1>Produk kamu.</h1>
                    <p class="deskripsi">Kelola katalog produk usahamu dari satu tempat.</p>
                </div>
                <a href="tambah.php" class="btn">
                    <svg class="i"><use href="#i-tambah"/></svg>
                    Tambah produk
                </a>
            </header>

            <section class="panel">

                <div class="panel-atas">
                    <h2>Daftar produk</h2>
                    <span class="jumlah" id="jumlah"><?= $total ?> produk</span>
                </div>

                <?php if ($total === 0): ?>

                    <div class="kosong-blok">
                        <div class="kosong-ikon"><svg class="i"><use href="#i-layers"/></svg></div>
                        <h3>Belum ada produk</h3>
                        <p>Tambahkan produk pertamamu supaya bisa dikelola dari dashboard Lapakly.</p>
                        <a href="tambah.php" class="btn"><svg class="i"><use href="#i-tambah"/></svg>Tambah produk pertama</a>
                    </div>

                <?php else: ?>

                    <div class="alat">
                        <div class="cari">
                            <svg class="i"><use href="#i-cari"/></svg>
                            <input type="search" id="cari" placeholder="Cari nama atau kategori" aria-label="Cari produk" autocomplete="off">
                        </div>
                        <div class="filter" role="group" aria-label="Filter status">
                            <button type="button" data-f="semua" aria-pressed="true">Semua<small><?= $total ?></small></button>
                            <button type="button" data-f="aktif" aria-pressed="false">Aktif<small><?= $aktif ?></small></button>
                            <button type="button" data-f="nonaktif" aria-pressed="false">Nonaktif<small><?= $nonaktif ?></small></button>
                        </div>
                    </div>

                    <div class="gulir">
                        <table class="tabel">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th class="th-aksi">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                <?php foreach ($produk as $item): ?>
                                    <?php
                                    $adaFoto = trim((string) $item['foto']) !== '';
                                    $aktifItem = $item['status'] === 'aktif';
                                    ?>
                                    <tr data-status="<?= $aktifItem ? 'aktif' : 'nonaktif' ?>"
                                        data-cari="<?= aman(mb_strtolower($item['nama'] . ' ' . $item['kategori'])) ?>">
                                        <td class="sel-produk">
                                            <div class="produk-sel">
                                                <span class="thumb" style="--h: <?= warna_hue((string) $item['nama']) ?>">
                                                    <?= aman(inisial((string) $item['nama'])) ?>
                                                    <?php if ($adaFoto): ?>
                                                        <img src="<?= aman(FOTO_URL . rawurlencode((string) $item['foto'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                                                    <?php endif; ?>
                                                </span>
                                                <div>
                                                    <span class="nama"><?= aman($item['nama']) ?></span>
                                                    <span class="id">ID #<?= (int) $item['id'] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="sel-kategori" data-label="Kategori"><?= aman($item['kategori']) ?></td>
                                        <td class="sel-harga" data-label="Harga"><?= aman(rupiah($item['harga'])) ?></td>
                                        <td class="sel-status">
                                            <span class="status<?= $aktifItem ? '' : ' nonaktif' ?>"><?= $aktifItem ? 'Aktif' : 'Nonaktif' ?></span>
                                        </td>
                                        <td class="sel-aksi">
                                            <div class="aksi-sel">
                                                <a class="btn-aksi" href="edit.php?id=<?= (int) $item['id'] ?>">
                                                    <svg class="i"><use href="#i-pensil"/></svg>Edit
                                                </a>
                                                <button type="button" class="btn-aksi hapus" data-hapus
                                                        data-id="<?= (int) $item['id'] ?>"
                                                        data-nama="<?= aman($item['nama']) ?>">
                                                    <svg class="i"><use href="#i-sampah"/></svg>Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="kosong-blok" id="kosong-cari" hidden>
                            <div class="kosong-ikon"><svg class="i"><use href="#i-cari"/></svg></div>
                            <h3>Tidak ada yang cocok</h3>
                            <p>Coba kata kunci lain atau ubah filter status.</p>
                        </div>
                    </div>

                <?php endif; ?>

                <div class="bawah">Data ditampilkan langsung dari database Lapakly.</div>
            </section>
        </main>
    </div>
</div>

<dialog class="dialog" id="dialog-hapus" aria-labelledby="dh-judul">
    <form method="POST" action="hapus.php" class="dialog-isi" id="form-hapus">
        <input type="hidden" name="csrf" value="<?= aman($csrf) ?>">
        <input type="hidden" name="id" id="dh-id" value="">
        <div class="dialog-ikon"><svg class="i"><use href="#i-sampah"/></svg></div>
        <h2 id="dh-judul">Hapus produk ini?</h2>
        <p><strong id="dh-nama"></strong> akan dihapus permanen beserta fotonya. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="dialog-aksi">
            <button type="button" class="btn btn-garis" id="dh-batal" autofocus>Batal</button>
            <button type="submit" class="btn btn-bahaya" id="dh-ya">Ya, hapus</button>
        </div>
    </form>
</dialog>

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

    /* Cari + filter */
    const tbody = $('tbody');
    if (tbody) {
        const baris = Array.from(tbody.querySelectorAll('tr[data-status]'));
        const tombol = Array.from(document.querySelectorAll('.filter button'));
        let status = 'semua', kata = '';

        function terapkan() {
            let tampil = 0;
            baris.forEach((tr) => {
                const cocok = (status === 'semua' || tr.dataset.status === status) && tr.dataset.cari.includes(kata);
                tr.hidden = !cocok;
                if (cocok) tampil++;
            });
            $('kosong-cari').hidden = tampil !== 0;
            $('jumlah').textContent = tampil === baris.length
                ? baris.length + ' produk'
                : tampil + ' dari ' + baris.length + ' produk';
        }

        $('cari').addEventListener('input', (e) => {
            kata = e.target.value.trim().toLowerCase();
            terapkan();
        });
        tombol.forEach((b) => b.addEventListener('click', () => {
            status = b.dataset.f;
            tombol.forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
            terapkan();
        }));
    }

    /* Konfirmasi hapus */
    const dialog = $('dialog-hapus'), formHapus = $('form-hapus');
    document.querySelectorAll('[data-hapus]').forEach((b) => b.addEventListener('click', () => {
        $('dh-id').value = b.dataset.id;
        $('dh-nama').textContent = b.dataset.nama;
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else if (confirm('Hapus produk "' + b.dataset.nama + '"? Tindakan ini tidak bisa dibatalkan.')) {
            formHapus.submit();
        }
    }));
    $('dh-batal').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
    let menghapus = false;
    formHapus.addEventListener('submit', (e) => {
        if (menghapus) { e.preventDefault(); return; }
        menghapus = true;
        $('dh-ya').textContent = 'Menghapus';
    });
    addEventListener('pageshow', () => { menghapus = false; $('dh-ya').textContent = 'Ya, hapus'; });
})();
</script>

</body>
</html>
