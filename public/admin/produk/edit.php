<?php
require_once __DIR__ . '/_bantu.php';

$id = filter_var(
    $_GET['id'] ?? $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$produk = false;

if ($id && $id > 0) {
    $q = $pdo->prepare(
        'SELECT id, nama, kategori, deskripsi, harga, stok, status, foto
         FROM produk WHERE id = ?'
    );
    $q->execute([$id]);
    $produk = $q->fetch(PDO::FETCH_ASSOC);
}

if (!$produk) {
    set_flash('galat', 'Produk tidak ditemukan.');
    header('Location: /admin/produk/index.php');
    exit;
}

$error = '';

$nilai = [
    'nama'      => (string) $produk['nama'],
    'kategori'  => (string) $produk['kategori'],
    'deskripsi' => (string) $produk['deskripsi'],
    'harga'     => (string) (int) round((float) $produk['harga']),
    'stok'      => (string) $produk['stok'],
    'status'    => $produk['status'] === 'nonaktif'
        ? 'nonaktif'
        : 'aktif',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    foreach (array_keys($nilai) as $k) {
        $nilai[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    $status = trim((string) ($_POST['status'] ?? ''));

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($nilai['nama'] === '' || $nilai['kategori'] === '') {
        $error = 'Nama dan kategori wajib diisi.';
    } elseif (!ctype_digit($nilai['harga'])) {
        $error = 'Harga harus berupa angka bulat, tanpa titik atau koma.';
    } elseif (
    $nilai['stok'] === '' ||
    !ctype_digit($nilai['stok'])
) {
    $error = 'Stok harus berupa bilangan bulat mulai dari 0.';
} elseif ((float) $nilai['stok'] > 4294967295) {
    $error = 'Jumlah stok terlalu besar.';
    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $error = 'Status produk tidak valid.';
    } else {
        $nilai['status'] = $status;

        [$fotoBaru, $error] = simpanFoto($_FILES['foto'] ?? null);

        if ($error === '') {
            $fotoSimpan = $fotoBaru !== ''
                ? $fotoBaru
                : (string) $produk['foto'];

            try {
                $u = $pdo->prepare(
                    'UPDATE produk
                     SET nama = ?, kategori = ?, deskripsi = ?,
                         harga = ?, stok = ?, status = ?, foto = ?
                     WHERE id = ?'
                );

                $u->execute([
                    $nilai['nama'],
                    $nilai['kategori'],
                    $nilai['deskripsi'],
                    (int) $nilai['harga'],
                    (int) $nilai['stok'],
                    $nilai['status'],
                    $fotoSimpan,
                    $id,
                ]);

                if ($fotoBaru !== '') {
                    hapusBerkasFoto($pdo, $produk['foto'], $id);
                }

                set_flash(
                    'sukses',
                    'Perubahan "' . $nilai['nama'] . '" sudah tersimpan.'
                );

                header('Location: /admin/produk/index.php');
                exit;
            } catch (PDOException $e) {
                if ($fotoBaru !== '') {
                    @unlink(FOTO_DIR . '/' . $fotoBaru);
                }

                $error = 'Perubahan gagal disimpan. Coba lagi.';
            }
        }
    }
}

$v = array_map('aman', $nilai);

$fotoSekarang = trim((string) $produk['foto']);
$adaFoto = $fotoSekarang !== '';
$urlFoto = $adaFoto
    ? FOTO_URL . rawurlencode($fotoSekarang)
    : '';

$mati = $nilai['status'] === 'nonaktif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Edit Produk | Lapakly</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --tinta: #14313a;
            --kertas: #eef4f0;
            --putih: #fbfdfc;
            --garis: #c9d8d0;
            --kuning: #f4b41a;
            --daun: #2b8a68;
            --bata: #c4452f;
            --redup: #5d7780;
            --tampil: 'Bricolage Grotesque', 'Trebuchet MS', system-ui, sans-serif;
            --isi: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            background: var(--kertas);
            color: var(--tinta);
            font-family: var(--isi);
            font-size: 1rem;
            line-height: 1.5;
        }

        .halaman {
            max-width: 1040px;
            margin: 0 auto;
            padding: 1rem 1.25rem 0;
        }

        .atas {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .25rem 0 1.5rem;
        }

        .kembali {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            color: var(--tinta);
            font-weight: 600;
            text-decoration: none;
            padding: .4rem .6rem .4rem .2rem;
            border-radius: 8px;
        }

        .kembali:focus-visible {
            outline: 3px solid var(--kuning);
            outline-offset: 2px;
        }

        .merek {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -.02em;
        }

        .judul h1 {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: clamp(2.1rem, 7vw, 3.4rem);
            line-height: 1.02;
            letter-spacing: -.03em;
            margin: 0 0 .6rem;
        }

        .judul p {
            margin: 0;
            color: var(--redup);
            max-width: 46ch;
        }

        .isi {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.75rem;
            margin-top: 1.75rem;
            align-items: start;
        }

        .pratinjau { order: -1; }

        @media (min-width: 860px) {
            .isi {
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 3rem;
            }

            .pratinjau {
                order: 0;
                position: sticky;
                top: 1.5rem;
            }
        }

        .papan {
            position: relative;
            background-color: var(--tinta);
            background-image: radial-gradient(rgba(255,255,255,.13) 1.5px, transparent 1.6px);
            background-size: 22px 22px;
            border-radius: 20px;
            padding: 1rem 1rem 1.75rem;
            display: flex;
            justify-content: center;
            overflow: hidden;
        }

        .ayun {
            width: min(100%, 260px);
            transform-origin: 50% 7px;
            animation: ayun 2.6s ease-in-out .2s both;
            filter: drop-shadow(0 14px 14px rgba(0,0,0,.35));
        }

        .tali {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .paku {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--kuning);
        }

        .garis-tali {
            width: 2px;
            height: 34px;
            background: rgba(255,255,255,.6);
        }

        .label {
            position: relative;
            background: var(--putih);
            padding: 1.7rem 1rem 1rem;
            clip-path: polygon(16% 0,84% 0,100% 8%,100% 100%,0 100%,0 8%);
        }

        .label::before {
            content: '';
            position: absolute;
            top: .55rem;
            left: 50%;
            width: 14px;
            height: 14px;
            margin-left: -7px;
            border-radius: 50%;
            background: var(--tinta);
        }

        .gambar {
            position: relative;
            aspect-ratio: 16 / 10;
            border-radius: 8px;
            overflow: hidden;
            background: var(--warna, #6f8d96);
            display: grid;
            place-items: center;
        }

        .inisial {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 3rem;
            color: rgba(255,255,255,.88);
        }

        .gambar img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity .35s;
        }

        .gambar.ada img { opacity: 1; }
        .gambar.ada .inisial { opacity: 0; }

        .teks { padding-top: .8rem; }

        .p-nama {
            font-family: var(--tampil);
            font-weight: 700;
            font-size: 1.3rem;
            line-height: 1.15;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .p-kat {
            display: inline-block;
            margin-top: .35rem;
            padding: .05rem .5rem;
            border-radius: 6px;
            background: rgba(20,49,58,.08);
            font-size: .82rem;
            font-weight: 600;
        }

        .p-harga {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 1.8rem;
            margin: .5rem 0 .25rem;
        }

        .p-stok {
            display: inline-block;
            margin: .2rem 0 .6rem;
            padding: .25rem .65rem;
            border-radius: 7px;
            background: #e0f2e8;
            color: #216a4d;
            font-size: .85rem;
            font-weight: 700;
        }

        .p-desk {
            margin: 0;
            font-size: .88rem;
            color: var(--redup);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
        }

        .kosong { color: #98aeb4; }
        .p-kat.kosong { background: rgba(20,49,58,.05); }

        .cap {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%,-50%) rotate(-12deg) scale(1.7);
            opacity: 0;
            padding: .15rem .7rem;
            border: 3px solid var(--bata);
            border-radius: 6px;
            background: rgba(251,253,252,.9);
            color: var(--bata);
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 1.15rem;
            transition: transform .4s, opacity .2s;
        }

        .label.mati .cap {
            opacity: 1;
            transform: translate(-50%,-50%) rotate(-12deg) scale(1);
        }

        .label.mati .gambar { filter: grayscale(.9) brightness(.92); }

        .p-status {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-top: .8rem;
            padding-top: .7rem;
            border-top: 2px dotted var(--garis);
            font-size: .85rem;
            font-weight: 600;
        }

        .p-status i {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--daun);
        }

        .label.mati .p-status i { background: var(--bata); }

        .bidang { margin-bottom: 1.35rem; }

        .nama-bidang {
            display: block;
            font-weight: 600;
            margin-bottom: .4rem;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            font: inherit;
            color: var(--tinta);
            background: var(--putih);
            border: 1.5px solid var(--garis);
            border-radius: 10px;
            padding: .8rem .9rem;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--tinta);
            box-shadow: 0 0 0 4px rgba(244,180,26,.4);
        }

        textarea {
            resize: vertical;
            min-height: 5.5rem;
            line-height: 1.45;
        }

        .rp { position: relative; }

        .rp span {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: var(--redup);
            pointer-events: none;
        }

        .rp input {
            padding-left: 2.7rem;
            font-weight: 600;
        }

        .pilihan {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .55rem;
        }

        .pilihan button {
            font: inherit;
            font-size: .88rem;
            color: var(--tinta);
            background: transparent;
            border: 1.5px solid var(--garis);
            border-radius: 999px;
            padding: .3rem .8rem;
            cursor: pointer;
        }

        .pilihan button[aria-pressed="true"] {
            background: var(--tinta);
            border-color: var(--tinta);
            color: var(--putih);
        }

        .petunjuk {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: .4rem;
            font-size: .85rem;
            color: var(--redup);
        }

        .saklar {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(20,49,58,.08);
            border-radius: 12px;
            padding: 4px;
        }

        .saklar input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .saklar label {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: .65rem .5rem;
            font-weight: 600;
            color: var(--redup);
            cursor: pointer;
        }

        .geser {
            position: absolute;
            top: 4px;
            bottom: 4px;
            left: 4px;
            width: calc(50% - 4px);
            background: var(--putih);
            border-radius: 9px;
            transition: transform .3s;
        }

        #st-non:checked ~ .geser { transform: translateX(100%); }
        #st-aktif:checked ~ label[for="st-aktif"] { color: var(--daun); }
        #st-non:checked ~ label[for="st-non"] { color: var(--bata); }

        .sembunyi {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
            clip: rect(0 0 0 0);
        }

        .unggah {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: .8rem;
            border: 2px dashed var(--garis);
            border-radius: 12px;
            background: var(--putih);
            cursor: pointer;
        }

        .unggah.ada {
            border-style: solid;
            border-color: var(--daun);
        }

        .unggah-ikon {
            flex: none;
            width: 60px;
            height: 60px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(20,49,58,.08);
            overflow: hidden;
        }

        .unggah-ikon img {
            display: none;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .unggah.ada .unggah-ikon svg { display: none; }
        .unggah.ada .unggah-ikon img { display: block; }

        .unggah-teks {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .unggah-teks strong {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .unggah-teks small {
            color: var(--redup);
            font-size: .85rem;
        }

        .hapus-foto {
            font: inherit;
            font-size: .85rem;
            color: var(--bata);
            background: none;
            border: 0;
            padding: 0;
            cursor: pointer;
            text-decoration: underline;
        }

        .aksi {
            position: sticky;
            bottom: 0;
            display: flex;
            gap: .75rem;
            padding: 1.1rem 0 calc(1rem + env(safe-area-inset-bottom,0px));
            background: linear-gradient(to top,var(--kertas) 72%,rgba(238,244,240,0));
        }

        .simpan {
            flex: 1;
            font: inherit;
            font-family: var(--tampil);
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--tinta);
            background: var(--kuning);
            border: 0;
            border-radius: 12px;
            padding: .95rem 1.25rem;
            cursor: pointer;
            box-shadow: 0 4px 0 #b9850b;
        }

        .simpan.proses { opacity: .7; }

        .batal {
            display: grid;
            place-items: center;
            padding: 0 1.1rem;
            color: var(--tinta);
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid var(--garis);
            border-radius: 12px;
            background: var(--kertas);
        }

        .galat {
            margin-bottom: 1.35rem;
            padding: .8rem 1rem;
            border-left: 5px solid var(--bata);
            border-radius: 4px 10px 10px 4px;
            background: #fbe9e5;
            color: #7d2515;
            font-weight: 500;
        }

        @keyframes ayun {
            0% { transform: rotate(11deg); }
            22% { transform: rotate(-7deg); }
            44% { transform: rotate(4deg); }
            64% { transform: rotate(-2deg); }
            82% { transform: rotate(.8deg); }
            100% { transform: rotate(0); }
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
<div class="halaman">

    <header class="atas">
        <a class="kembali" href="/admin/produk/index.php">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.4"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M15 5l-7 7 7 7"/>
            </svg>
            Produk
        </a>
        <span class="merek">Lapakly</span>
    </header>

    <div class="judul">
        <h1>Edit produk</h1>
        <p>
            Ubah detail produk #<?= (int) $produk['id'] ?>.
            Pratinjau ikut berubah saat kamu mengetik.
        </p>
    </div>

    <div class="isi">

        <aside class="pratinjau" aria-label="Pratinjau produk">
            <div class="papan">
                <div class="ayun" id="ayun">
                    <div class="tali">
                        <span class="paku"></span>
                        <span class="garis-tali"></span>
                    </div>

                    <div class="label<?= $mati ? ' mati' : '' ?>" id="label">
                        <div class="gambar<?= $adaFoto ? ' ada' : '' ?>" id="p-gambar">
                            <span class="inisial" id="p-inisial">?</span>
                            <img id="p-foto" alt=""
                                <?= $adaFoto ? 'src="' . aman($urlFoto) . '"' : '' ?>>
                            <span class="cap">Nonaktif</span>
                        </div>

                        <div class="teks">
                            <p class="p-nama kosong" id="p-nama">Nama produk</p>
                            <span class="p-kat kosong" id="p-kat">Kategori</span>
                            <p class="p-harga kosong" id="p-harga">Rp 0</p>
                            <span class="p-stok" id="p-stok">
                                Stok: <?= (int) $produk['stok'] ?> unit
                            </span>
                            <p class="p-desk kosong" id="p-desk">
                                Deskripsi akan tampil di sini.
                            </p>
                            <div class="p-status">
                                <i></i>
                                <span id="p-status"><?= $mati ? 'Nonaktif' : 'Aktif' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <form method="POST" action="" id="form"
              enctype="multipart/form-data"
              data-foto="<?= aman($urlFoto) ?>">

            <input type="hidden" name="csrf" value="<?= aman(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $produk['id'] ?>">

            <?php if ($error !== ''): ?>
                <div class="galat" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="bidang">
                <label class="nama-bidang" for="nama">Nama produk</label>
                <input type="text" id="nama" name="nama"
                       placeholder="Contoh: Kopi Susu"
                       value="<?= $v['nama'] ?>"
                       autocomplete="off" required>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="kategori">Kategori</label>
                <input type="text" id="kategori" name="kategori"
                       placeholder="Contoh: Minuman"
                       value="<?= $v['kategori'] ?>"
                       autocomplete="off" required>

                <div class="pilihan" id="pilihan-kategori">
                    <button type="button" aria-pressed="false">Minuman</button>
                    <button type="button" aria-pressed="false">Makanan</button>
                    <button type="button" aria-pressed="false">Camilan</button>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="harga">Harga</label>
                <div class="rp">
                    <span>Rp</span>
                    <input type="number" id="harga" name="harga"
                           min="0" step="1" inputmode="numeric"
                           placeholder="15000"
                           value="<?= $v['harga'] ?>" required>
                </div>

                <div class="pilihan" id="pilihan-harga">
                    <button type="button" data-nilai="5000" aria-pressed="false">5.000</button>
                    <button type="button" data-nilai="10000" aria-pressed="false">10.000</button>
                    <button type="button" data-nilai="15000" aria-pressed="false">15.000</button>
                    <button type="button" data-nilai="25000" aria-pressed="false">25.000</button>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="stok">Stok produk</label>
                <input type="number" id="stok" name="stok"
                       min="0" step="1" inputmode="numeric"
                       placeholder="Contoh: 10"
                       value="<?= $v['stok'] ?>" required>
                <div class="petunjuk">
                    <!--<span>Stok minimal 1 unit.</span>-->
                    <span>Bilangan bulat</span>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                          placeholder="Ceritakan singkat soal produk ini..."><?= $v['deskripsi'] ?></textarea>
                <div class="petunjuk">
                    <span>Opsional</span>
                    <span id="hitung">0 karakter</span>
                </div>
            </div>

            <div class="bidang">
                <span class="nama-bidang" id="judul-foto">Foto produk</span>

                <input type="file" id="foto" name="foto"
                       class="sembunyi"
                       accept="image/jpeg,image/png,image/webp"
                       aria-labelledby="judul-foto"
                       aria-describedby="foto-info">

                <label class="unggah<?= $adaFoto ? ' ada' : '' ?>"
                       for="foto" id="unggah">
                    <span class="unggah-ikon">
                        <svg width="26" height="26" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="3"/>
                            <circle cx="9" cy="10" r="1.6"/>
                            <path d="M21 16l-5-5-8 8"/>
                        </svg>
                        <img id="foto-thumb" alt=""
                            <?= $adaFoto ? 'src="' . aman($urlFoto) . '"' : '' ?>>
                    </span>

                    <span class="unggah-teks">
                        <strong id="foto-judul">
                            <?= $adaFoto ? aman($fotoSekarang) : 'Pilih foto' ?>
                        </strong>
                        <small id="foto-detail">
                            <?= $adaFoto
                                ? 'Foto sekarang. Ketuk untuk menggantinya.'
                                : 'JPG, PNG, atau WebP. Maksimal 3 MB.' ?>
                        </small>
                    </span>
                </label>

                <div class="petunjuk">
                    <span id="foto-info" data-s="" role="status"></span>
                    <button type="button" class="hapus-foto"
                            id="foto-hapus" hidden>
                        Batalkan foto baru
                    </button>
                </div>
            </div>

            <div class="bidang">
                <span class="nama-bidang" id="judul-status">Status produk</span>

                <div class="saklar" role="radiogroup" aria-labelledby="judul-status">
                    <input type="radio" id="st-aktif" name="status" value="aktif"
                        <?= !$mati ? 'checked' : '' ?>>
                    <input type="radio" id="st-non" name="status" value="nonaktif"
                        <?= $mati ? 'checked' : '' ?>>
                    <span class="geser" aria-hidden="true"></span>
                    <label for="st-aktif">Aktif</label>
                    <label for="st-non">Nonaktif</label>
                </div>

                <div class="petunjuk">
                    <span>Produk nonaktif disembunyikan dari katalog.</span>
                </div>
            </div>

            <div class="aksi">
                <button type="submit" name="simpan" class="simpan" id="simpan">
                    <span>Simpan perubahan</span>
                </button>
                <a class="batal" href="/admin/produk/index.php">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const rupiah = new Intl.NumberFormat('id-ID');

    const f = {
        nama: $('nama'),
        kategori: $('kategori'),
        harga: $('harga'),
        stok: $('stok'),
        deskripsi: $('deskripsi'),
        foto: $('foto')
    };

    const p = {
        label: $('label'),
        ayun: $('ayun'),
        gambar: $('p-gambar'),
        inisial: $('p-inisial'),
        foto: $('p-foto'),
        nama: $('p-nama'),
        kat: $('p-kat'),
        harga: $('p-harga'),
        stok: $('p-stok'),
        desk: $('p-desk'),
        status: $('p-status')
    };

    function tulis(node, teks, kosong) {
        const bersih = teks.trim();
        node.textContent = bersih || kosong;
        node.classList.toggle('kosong', !bersih);
    }

    function goyang() {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        p.ayun.animate([
            { transform: 'rotate(0deg)' },
            { transform: 'rotate(3deg)', offset: .3 },
            { transform: 'rotate(-1.5deg)', offset: .65 },
            { transform: 'rotate(0deg)' }
        ], { duration: 650, easing: 'ease-in-out' });
    }

    function warna(s) {
        let h = 0;
        for (const c of s) {
            h = (h * 31 + c.charCodeAt(0)) % 360;
        }
        return `hsl(${h} 38% 40%)`;
    }

    function sinkronChip(wadah, cocok) {
        wadah.querySelectorAll('button').forEach((b) => {
            b.setAttribute('aria-pressed', cocok(b) ? 'true' : 'false');
        });
    }

    function ubahNama() {
        const v = f.nama.value.trim();
        tulis(p.nama, v, 'Nama produk');

        const huruf = v.split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((w) => w[0])
            .join('')
            .toUpperCase();

        p.inisial.textContent = huruf || '?';

        if (v) {
            p.gambar.style.setProperty('--warna', warna(v));
        } else {
            p.gambar.style.removeProperty('--warna');
        }
    }

    function ubahKategori() {
        const v = f.kategori.value.trim();
        tulis(p.kat, v, 'Kategori');

        sinkronChip(
            $('pilihan-kategori'),
            (b) => b.textContent.toLowerCase() === v.toLowerCase()
        );
    }

    function ubahHarga() {
        const n = Number(f.harga.value);
        tulis(
            p.harga,
            f.harga.value !== '' && Number.isFinite(n)
                ? 'Rp ' + rupiah.format(n)
                : '',
            'Rp 0'
        );

        sinkronChip(
            $('pilihan-harga'),
            (b) => Number(b.dataset.nilai) === n
        );
    }

    function ubahStok() {
        const n = Number(f.stok.value);
        p.stok.textContent = f.stok.value !== '' && Number.isFinite(n)
            ? 'Stok: ' + rupiah.format(n) + ' unit'
            : 'Stok produk';
    }

    function ubahDeskripsi() {
        const v = f.deskripsi.value;
        tulis(p.desk, v, 'Deskripsi akan tampil di sini.');
        $('hitung').textContent = v.trim().length + ' karakter';

        f.deskripsi.style.height = 'auto';
        f.deskripsi.style.height = f.deskripsi.scrollHeight + 2 + 'px';
    }

    const MAKS = 3 * 1024 * 1024;
    const JENIS = ['image/jpeg', 'image/png', 'image/webp'];
    const unggah = $('unggah');
    const thumb = $('foto-thumb');
    const hapus = $('foto-hapus');
    const fotoLama = $('form').dataset.foto || '';
    const judulLama = $('foto-judul').textContent.trim();
    const detailLama = $('foto-detail').textContent.trim();

    let urlFoto = null;

    function ukuran(b) {
        return b < 1048576
            ? Math.max(1, Math.round(b / 1024)) + ' KB'
            : (b / 1048576).toFixed(1).replace('.', ',') + ' MB';
    }

    function info(status, teks) {
        const el = $('foto-info');
        el.dataset.s = status;
        el.textContent = teks;
    }

    function pasangFoto(src) {
        if (src) {
            thumb.src = src;
            p.foto.src = src;
            unggah.classList.add('ada');
            p.gambar.classList.add('ada');
        } else {
            thumb.removeAttribute('src');
            p.foto.removeAttribute('src');
            unggah.classList.remove('ada');
            p.gambar.classList.remove('ada');
        }
    }

    function resetFoto(pesan = '') {
        if (urlFoto) URL.revokeObjectURL(urlFoto);
        urlFoto = null;
        f.foto.value = '';

        pasangFoto(fotoLama);

        $('foto-judul').textContent = judulLama;
        $('foto-detail').textContent = detailLama;
        hapus.hidden = true;
        info(pesan ? 'galat' : '', pesan);
    }

    function ubahFoto() {
        const berkas = f.foto.files[0];

        if (!berkas) {
            resetFoto();
            return;
        }

        if (!JENIS.includes(berkas.type)) {
            resetFoto('Format foto harus JPG, PNG, atau WebP.');
            return;
        }

        if (berkas.size > MAKS) {
            resetFoto('Ukuran foto melebihi batas 3 MB.');
            return;
        }

        if (urlFoto) URL.revokeObjectURL(urlFoto);

        urlFoto = URL.createObjectURL(berkas);
        pasangFoto(urlFoto);

        $('foto-judul').textContent = berkas.name;
        $('foto-detail').textContent =
            ukuran(berkas.size) + '. Foto baru, menggantikan yang lama.';

        hapus.hidden = false;
        info('ok', 'Foto baru siap disimpan.');
        goyang();
    }

    p.foto.addEventListener('error', () => {
        if (!urlFoto) p.gambar.classList.remove('ada');
    });

    thumb.addEventListener('error', () => {
        if (!urlFoto) unggah.classList.remove('ada');
    });

    function ubahStatus() {
        const terpilih = document.querySelector(
            'input[name="status"]:checked'
        );

        const mati = terpilih && terpilih.value === 'nonaktif';

        p.label.classList.toggle('mati', Boolean(mati));
        p.status.textContent = mati ? 'Nonaktif' : 'Aktif';
    }

    f.nama.addEventListener('input', ubahNama);
    f.kategori.addEventListener('input', ubahKategori);
    f.harga.addEventListener('input', ubahHarga);
    f.stok.addEventListener('input', ubahStok);
    f.deskripsi.addEventListener('input', ubahDeskripsi);
    f.foto.addEventListener('change', ubahFoto);

    hapus.addEventListener('click', () => resetFoto());

    ['dragenter', 'dragover'].forEach((namaAcara) => {
        unggah.addEventListener(namaAcara, (e) => {
            e.preventDefault();
            unggah.classList.add('tarik');
        });
    });

    ['dragleave', 'drop'].forEach((namaAcara) => {
        unggah.addEventListener(namaAcara, (e) => {
            e.preventDefault();
            unggah.classList.remove('tarik');
        });
    });

    unggah.addEventListener('drop', (e) => {
        if (e.dataTransfer.files.length) {
            f.foto.files = e.dataTransfer.files;
            ubahFoto();
        }
    });

    $('pilihan-kategori').addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;

        f.kategori.value = b.textContent;
        ubahKategori();
        goyang();
    });

    $('pilihan-harga').addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;

        f.harga.value = b.dataset.nilai;
        ubahHarga();
        goyang();
    });

    document.querySelectorAll('input[name="status"]').forEach((r) => {
        r.addEventListener('change', () => {
            ubahStatus();
            goyang();
        });
    });

    const form = $('form');
    const tombol = $('simpan');
    let terkirim = false;

    form.addEventListener('submit', (e) => {
        if (!form.reportValidity()) {
            e.preventDefault();
            return;
        }

        if (terkirim) {
            e.preventDefault();
            return;
        }

        terkirim = true;
        tombol.classList.add('proses');
        tombol.querySelector('span').textContent = 'Menyimpan...';
    });

    addEventListener('pageshow', () => {
        terkirim = false;
        tombol.classList.remove('proses');
        tombol.querySelector('span').textContent = 'Simpan perubahan';
    });

    ubahNama();
    ubahKategori();
    ubahHarga();
    ubahStok();
    ubahDeskripsi();
    ubahStatus();
})();
</script>
</body>
</html>
