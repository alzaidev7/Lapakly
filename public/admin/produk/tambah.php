<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/masuk.php');
    exit;
}

require_once __DIR__ . '/../../../inti/koneksi.php';

$error = '';
$berhasil = isset($_GET['sukses']) && $_GET['sukses'] === '1';

const FOTO_MAKS = 3 * 1024 * 1024;
const FOTO_DIR = __DIR__ . '/../../foto';

function isi($kunci, $bawaan = '')
{
    global $berhasil;

    if ($berhasil) {
        return htmlspecialchars(
            (string) $bawaan,
            ENT_QUOTES,
            'UTF-8'
        );
    }

    return htmlspecialchars(
        (string) ($_POST[$kunci] ?? $bawaan),
        ENT_QUOTES,
        'UTF-8'
    );
}

function simpanFoto($berkas)
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

    if (!is_uploaded_file($berkas['tmp_name'])) {
        return ['', 'File foto tidak valid.'];
    }

    $info = @getimagesize($berkas['tmp_name']);

    $jenis = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = $info['mime'] ?? '';

    if (!$info || !isset($jenis[$mime])) {
        return ['', 'Format foto harus JPG, PNG, atau WebP.'];
    }

    if (
        !is_dir(FOTO_DIR) &&
        !mkdir(FOTO_DIR, 0775, true) &&
        !is_dir(FOTO_DIR)
    ) {
        return ['', 'Folder foto tidak bisa dibuat.'];
    }

    $nama = bin2hex(random_bytes(16)) . '.' . $jenis[$mime];
    $tujuan = FOTO_DIR . '/' . $nama;

    if (!move_uploaded_file($berkas['tmp_name'], $tujuan)) {
        return ['', 'Foto gagal disimpan.'];
    }

    return [$nama, ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $hargaInput = trim($_POST['harga'] ?? '');
    $stokInput = trim($_POST['stok'] ?? '0');

    if ($nama === '' || mb_strlen($nama) > 150) {
        $error = 'Nama wajib diisi dan maksimal 150 karakter.';
    } elseif ($kategori === '' || mb_strlen($kategori) > 80) {
        $error = 'Kategori wajib diisi dan maksimal 80 karakter.';
    } elseif (
        $hargaInput === '' ||
        !is_numeric($hargaInput) ||
        !is_finite((float) $hargaInput) ||
        (float) $hargaInput < 0 ||
        (float) $hargaInput > 9999999999.99
    ) {
        $error = 'Masukkan harga yang valid.';
    } elseif (
        $stokInput === '' ||
        !preg_match('/^\d+$/', $stokInput) ||
        (float) $stokInput > 4294967295
    ) {
        $error = 'Stok harus berupa bilangan bulat minimal 1.';
    } elseif (mb_strlen($deskripsi) > 10000) {
        $error = 'Deskripsi terlalu panjang.';
    } else {
        $foto = '';
        $fotoBaru = false;

        try {
            [$foto, $error] = simpanFoto($_FILES['foto'] ?? null);

            if ($error === '') {
                $fotoBaru = ($foto !== '');

                $sql = 'INSERT INTO produk
                        (nama, kategori, deskripsi, harga, stok, foto)
                        VALUES
                        (:nama, :kategori, :deskripsi, :harga, :stok, :foto)';

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':nama' => $nama,
                    ':kategori' => $kategori,
                    ':deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                    ':harga' => number_format(
                        (float) $hargaInput,
                        2,
                        '.',
                        ''
                    ),
                    ':stok' => (int) $stokInput,
                    ':foto' => $foto !== '' ? $foto : null,
                ]);

                header('Location: /admin/produk/tambah.php?sukses=1');
                exit;
            }
        } catch (Throwable $e) {
            if ($fotoBaru && $foto !== '') {
                $path = FOTO_DIR . '/' . $foto;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            error_log('Lapakly tambah produk: ' . $e->getMessage());
            $error = 'Produk gagal disimpan. Periksa koneksi database lalu coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Tambah Produk | Lapakly</title>

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
            --isi: 'Instrument Sans', system-ui, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--kertas);
            color: var(--tinta);
            font-family: var(--isi);
            font-size: 16px;
            line-height: 1.5;
        }

        .halaman {
            max-width: 1040px;
            margin: auto;
            padding: 1rem 1.25rem 0;
        }

        .atas {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .25rem 0 1.5rem;
        }

        .kembali {
            color: var(--tinta);
            text-decoration: none;
            font-weight: 600;
        }

        .merek {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 1.3rem;
        }

        h1, h2, p { overflow-wrap: anywhere; }

        h1 {
            margin: 0 0 .6rem;
            font-family: var(--tampil);
            font-size: clamp(2rem, 7vw, 3.4rem);
            line-height: 1.05;
        }

        .judul p {
            margin: 0;
            color: var(--redup);
        }

        .isi {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
            margin-top: 1.75rem;
            align-items: start;
        }

        .pratinjau { order: -1; }

        .papan {
            padding: 1rem;
            background-color: var(--tinta);
            background-image: radial-gradient(
                rgba(255,255,255,.13) 1.5px,
                transparent 1.6px
            );
            background-size: 22px 22px;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            overflow: hidden;
        }

        .ayun {
            width: min(100%, 280px);
            filter: drop-shadow(0 12px 12px rgba(0,0,0,.25));
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
            height: 34px;
            width: 2px;
            background: rgba(255,255,255,.6);
        }

        .label {
            background: var(--putih);
            padding: 1.5rem 1rem 1rem;
            border-radius: 10px;
        }

        .gambar {
            position: relative;
            aspect-ratio: 16 / 10;
            border-radius: 8px;
            background: var(--warna, #6f8d96);
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .inisial {
            font-family: var(--tampil);
            font-weight: 800;
            font-size: 3rem;
            color: white;
        }

        .gambar img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .gambar.ada img { display: block; }
        .gambar.ada .inisial { display: none; }

        .teks { padding-top: .8rem; }

        .p-nama {
            margin: 0;
            font-family: var(--tampil);
            font-size: 1.3rem;
            font-weight: 700;
        }

        .p-kat {
            display: inline-block;
            margin-top: .35rem;
            padding: .05rem .5rem;
            border-radius: 6px;
            background: rgba(20,49,58,.08);
            font-size: .85rem;
        }

        .p-harga {
            margin: .5rem 0 .25rem;
            font-family: var(--tampil);
            font-size: 1.8rem;
            font-weight: 800;
        }

        .p-stok {
            margin: .25rem 0;
            font-weight: 600;
            color: var(--daun);
        }

        .p-desk {
            margin: .35rem 0 0;
            color: var(--redup);
            font-size: .9rem;
        }

        .kosong { color: #98aeb4; }

        .bidang { margin-bottom: 1.35rem; }

        .nama-bidang {
            display: block;
            margin-bottom: .4rem;
            font-weight: 600;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: .8rem .9rem;
            border: 1.5px solid var(--garis);
            border-radius: 10px;
            background: var(--putih);
            color: var(--tinta);
            font: inherit;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--tinta);
            box-shadow: 0 0 0 4px rgba(244,180,26,.4);
        }

        textarea {
            min-height: 5.5rem;
            resize: vertical;
        }

        .rp { position: relative; }

        .rp span {
            position: absolute;
            top: 50%;
            left: .9rem;
            transform: translateY(-50%);
            color: var(--redup);
            font-weight: 600;
            pointer-events: none;
        }

        .rp input { padding-left: 2.7rem; }

        .pilihan {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .55rem;
        }

        .pilihan button {
            padding: .3rem .8rem;
            border: 1.5px solid var(--garis);
            border-radius: 999px;
            background: transparent;
            color: var(--tinta);
            font: inherit;
            font-size: .88rem;
            cursor: pointer;
        }

        .pilihan button[aria-pressed="true"] {
            background: var(--tinta);
            color: white;
            border-color: var(--tinta);
        }

        .petunjuk {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: .4rem;
            color: var(--redup);
            font-size: .85rem;
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

        .unggah.ada { border-style: solid; border-color: var(--daun); }

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
        }

        .sembunyi {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .hapus-foto {
            border: 0;
            padding: 0;
            background: none;
            color: var(--bata);
            cursor: pointer;
        }

        .galat {
            margin-bottom: 1.35rem;
            padding: .8rem 1rem;
            border-left: 5px solid var(--bata);
            border-radius: 4px 10px 10px 4px;
            background: #fbe9e5;
            color: #7d2515;
        }

        .aksi {
            position: sticky;
            bottom: 0;
            display: flex;
            gap: .75rem;
            padding: 1rem 0 calc(1rem + env(safe-area-inset-bottom, 0px));
            background: linear-gradient(to top, var(--kertas) 72%, transparent);
        }

        .simpan {
            flex: 1;
            padding: .95rem 1.25rem;
            border: 0;
            border-radius: 12px;
            background: var(--kuning);
            color: var(--tinta);
            font: 700 1.05rem var(--tampil);
            cursor: pointer;
        }

        .batal {
            display: grid;
            place-items: center;
            padding: 0 1.1rem;
            border: 1.5px solid var(--garis);
            border-radius: 12px;
            color: var(--tinta);
            text-decoration: none;
            font-weight: 600;
        }

        .toast {
            position: fixed;
            z-index: 10;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            padding: .8rem 1.2rem;
            border-radius: 999px;
            background: var(--tinta);
            color: white;
            font-weight: 600;
            animation: hilang 4s forwards;
        }

        @keyframes hilang {
            0%, 75% { opacity: 1; }
            100% { opacity: 0; }
        }

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

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
</head>
<body>

<?php if ($berhasil): ?>
    <div class="toast" role="status">✓ Produk berhasil disimpan</div>
<?php endif; ?>

<div class="halaman">

    <header class="atas">
        <a class="kembali" href="/admin/produk/index.php">← Produk</a>
        <span class="merek">Lapakly</span>
    </header>

    <div class="judul">
        <h1>Tambah produk</h1>
        <p>Isi detail produk baru untuk katalog. Pratinjau akan berubah saat kamu mengetik.</p>
    </div>

    <div class="isi">

        <aside class="pratinjau" aria-label="Pratinjau produk">
            <div class="papan">
                <div class="ayun">
                    <div class="tali">
                        <span class="paku"></span>
                        <span class="garis-tali"></span>
                    </div>

                    <div class="label">
                        <div class="gambar" id="p-gambar">
                            <span class="inisial" id="p-inisial">?</span>
                            <img id="p-foto" alt="">
                        </div>

                        <div class="teks">
                            <p class="p-nama kosong" id="p-nama">Nama produk</p>
                            <span class="p-kat kosong" id="p-kat">Kategori</span>
                            <p class="p-harga kosong" id="p-harga">Rp 0</p>
                            <p class="p-stok" id="p-stok">Stok: 0</p>
                            <p class="p-desk kosong" id="p-desk">Deskripsi akan tampil di sini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <form method="POST" action="" id="form" enctype="multipart/form-data">

            <?php if ($error !== ''): ?>
                <div class="galat" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="bidang">
                <label class="nama-bidang" for="nama">Nama produk</label>
                <input
                    type="text"
                    id="nama"
                    name="nama"
                    maxlength="150"
                    placeholder="Contoh: Kopi Susu"
                    value="<?= isi('nama') ?>"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="kategori">Kategori</label>
                <input
                    type="text"
                    id="kategori"
                    name="kategori"
                    maxlength="80"
                    placeholder="Contoh: Minuman"
                    value="<?= isi('kategori') ?>"
                    autocomplete="off"
                    required
                >

                <div class="pilihan" id="pilihan-kategori">
                    <button type="button">Minuman</button>
                    <button type="button">Makanan</button>
                    <button type="button">Camilan</button>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="harga">Harga</label>
                <div class="rp">
                    <span>Rp</span>
                    <input
                        type="number"
                        id="harga"
                        name="harga"
                        min="0"
                        max="9999999999.99"
                        step="any"
                        inputmode="decimal"
                        placeholder="15000"
                        value="<?= isi('harga') ?>"
                        required
                    >
                </div>

                <div class="pilihan" id="pilihan-harga">
                    <button type="button" data-nilai="5000">5.000</button>
                    <button type="button" data-nilai="10000">10.000</button>
                    <button type="button" data-nilai="15000">15.000</button>
                    <button type="button" data-nilai="25000">25.000</button>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="stok">Stok produk</label>
                <input
                    type="number"
                    id="stok"
                    name="stok"
                    min="1"
                    max="4294967295"
                    step="1"
                    inputmode="numeric"
                    placeholder="Contoh: 25"
                    value="<?= isi('stok', '0') ?>"
                    required
                >
                <div class="petunjuk">
                    <span>Jumlah barang tersedia</span>
                    <span>Minimal 1</span>
                </div>
            </div>

            <div class="bidang">
                <label class="nama-bidang" for="deskripsi">Deskripsi</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="3"
                    maxlength="10000"
                    placeholder="Ceritakan singkat soal produk ini..."
                ><?= isi('deskripsi') ?></textarea>
                <div class="petunjuk">
                    <span>Opsional</span>
                    <span id="hitung">0 karakter</span>
                </div>
            </div>

            <div class="bidang">
                <span class="nama-bidang" id="judul-foto">Foto produk</span>

                <input
                    type="file"
                    id="foto"
                    name="foto"
                    class="sembunyi"
                    accept="image/jpeg,image/png,image/webp"
                    aria-labelledby="judul-foto"
                    aria-describedby="foto-info"
                >

                <label class="unggah" for="foto" id="unggah">
                    <span class="unggah-ikon">
                        <span id="ikon-foto">＋</span>
                        <img id="foto-thumb" alt="">
                    </span>

                    <span class="unggah-teks">
                        <strong id="foto-judul">Pilih foto</strong>
                        <small id="foto-detail">JPG, PNG, atau WebP. Maksimal 3 MB.</small>
                    </span>
                </label>

                <div class="petunjuk">
                    <span id="foto-info" role="status"></span>
                    <button type="button" class="hapus-foto" id="foto-hapus" hidden>
                        Hapus foto
                    </button>
                </div>
            </div>

            <div class="aksi">
                <button type="submit" class="simpan" id="simpan">
                    <span>Simpan produk</span>
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
        gambar: $('p-gambar'),
        inisial: $('p-inisial'),
        foto: $('p-foto'),
        nama: $('p-nama'),
        kat: $('p-kat'),
        harga: $('p-harga'),
        stok: $('p-stok'),
        desk: $('p-desk')
    };

    function tulis(node, teks, teksKosong) {
        const bersih = teks.trim();
        node.textContent = bersih || teksKosong;
        node.classList.toggle('kosong', !bersih);
    }

    function ubahNama() {
        const v = f.nama.value.trim();
        tulis(p.nama, v, 'Nama produk');

        const huruf = v.split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(w => w[0])
            .join('')
            .toUpperCase();

        p.inisial.textContent = huruf || '?';

        if (v) {
            let h = 0;
            for (const c of v) {
                h = (h * 31 + c.charCodeAt(0)) % 360;
            }
            p.gambar.style.setProperty('--warna', `hsl(${h} 38% 40%)`);
        } else {
            p.gambar.style.removeProperty('--warna');
        }
    }

    function sinkronChip(wadah, cocok) {
        wadah.querySelectorAll('button').forEach(button => {
            button.setAttribute(
                'aria-pressed',
                cocok(button) ? 'true' : 'false'
            );
        });
    }

    function ubahKategori() {
        const v = f.kategori.value.trim();
        tulis(p.kat, v, 'Kategori');

        sinkronChip(
            $('pilihan-kategori'),
            button => button.textContent.toLowerCase() === v.toLowerCase()
        );
    }

    function ubahHarga() {
        const nilai = f.harga.value;
        const angka = Number(nilai);

        tulis(
            p.harga,
            nilai !== '' && Number.isFinite(angka) && angka >= 0
                ? 'Rp ' + rupiah.format(angka)
                : '',
            'Rp 0'
        );

        sinkronChip(
            $('pilihan-harga'),
            button => Number(button.dataset.nilai) === angka && nilai !== ''
        );
    }

    function ubahStok() {
        const nilai = f.stok.value;
        const valid = /^\d+$/.test(nilai)
            && Number(nilai) <= 4294967295;

        p.stok.textContent = valid
            ? 'Stok: ' + rupiah.format(Number(nilai))
            : 'Stok: —';

        p.stok.style.color = valid && Number(nilai) === 0
            ? '#c4452f'
            : '#2b8a68';
    }

    function ubahDeskripsi() {
        const v = f.deskripsi.value;
        tulis(p.desk, v, 'Deskripsi akan tampil di sini.');
        $('hitung').textContent = v.trim().length + ' karakter';
    }

    const MAKS = 3 * 1024 * 1024;
    const JENIS = ['image/jpeg', 'image/png', 'image/webp'];

    const unggah = $('unggah');
    const thumb = $('foto-thumb');
    const hapus = $('foto-hapus');

    const detailAwal = $('foto-detail').textContent;
    let urlFoto = null;

    function info(teks) {
        $('foto-info').textContent = teks;
    }

    function resetFoto(pesan = '') {
        if (urlFoto) {
            URL.revokeObjectURL(urlFoto);
            urlFoto = null;
        }

        f.foto.value = '';
        thumb.removeAttribute('src');
        p.foto.removeAttribute('src');

        unggah.classList.remove('ada');
        p.gambar.classList.remove('ada');

        $('ikon-foto').hidden = false;
        $('foto-judul').textContent = 'Pilih foto';
        $('foto-detail').textContent = detailAwal;

        hapus.hidden = true;
        info(pesan);
    }

    function ukuran(bytes) {
        return bytes < 1048576
            ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
            : (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
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

        thumb.src = urlFoto;
        p.foto.src = urlFoto;

        unggah.classList.add('ada');
        p.gambar.classList.add('ada');

        $('ikon-foto').hidden = true;
        $('foto-judul').textContent = berkas.name;
        $('foto-detail').textContent =
            ukuran(berkas.size) + '. Ketuk untuk mengganti.';

        hapus.hidden = false;
        info('Foto siap diunggah.');
    }

    f.nama.addEventListener('input', ubahNama);
    f.kategori.addEventListener('input', ubahKategori);
    f.harga.addEventListener('input', ubahHarga);
    f.stok.addEventListener('input', ubahStok);
    f.deskripsi.addEventListener('input', ubahDeskripsi);
    f.foto.addEventListener('change', ubahFoto);

    hapus.addEventListener('click', () => resetFoto());

    $('pilihan-kategori').addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;

        f.kategori.value = button.textContent;
        ubahKategori();
    });

    $('pilihan-harga').addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;

        f.harga.value = button.dataset.nilai;
        ubahHarga();
    });

    const form = $('form');
    const tombol = $('simpan');
    let terkirim = false;

    form.addEventListener('submit', event => {
        if (terkirim) {
            event.preventDefault();
            return;
        }

        if (f.foto.files[0]) {
            const berkas = f.foto.files[0];

            if (!JENIS.includes(berkas.type) || berkas.size > MAKS) {
                event.preventDefault();
                alert('Periksa format dan ukuran foto terlebih dahulu.');
                return;
            }
        }

        terkirim = true;
        tombol.querySelector('span').textContent = 'Menyimpan...';
    });

    addEventListener('pageshow', () => {
        terkirim = false;
        tombol.querySelector('span').textContent = 'Simpan produk';
    });

    ubahNama();
    ubahKategori();
    ubahHarga();
    ubahStok();
    ubahDeskripsi();
})();
</script>

</body>
</html>
