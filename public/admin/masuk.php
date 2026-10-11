<?php

session_start();

require_once __DIR__ . '/../../inti/koneksi.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nama, username, password
             FROM admin
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (
            $admin &&
            password_verify($password, $admin['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];

            header('Location: /admin/index.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102A22">
    <meta name="description" content="Login admin Lapakly untuk mengelola katalog usaha.">

    <title>Masuk — Lapakly Admin</title>

    <style>
        :root {
            --hutan: #102A22;
            --hutan-terang: #1D4635;
            --lime: #D7F5A5;
            --lime-gelap: #8FB65D;
            --latar: #F3F6F2;
            --putih: #FFFFFF;
            --teks: #26372F;
            --redup: #77837A;
            --garis: #E1E8DF;
            --merah: #A12C25;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            background:
                radial-gradient(
                    circle at 10% 15%,
                    rgba(215, 245, 165, .38),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 90% 85%,
                    rgba(176, 207, 157, .25),
                    transparent 30%
                ),
                var(--latar);
            color: var(--teks);
            font-family: Arial, Helvetica, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Ornamen latar */

        .dekorasi {
            position: fixed;
            z-index: 0;
            pointer-events: none;
            border: 1px solid rgba(16, 42, 34, .07);
            border-radius: 50%;
        }

        .dekorasi-satu {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
        }

        .dekorasi-dua {
            width: 220px;
            height: 220px;
            bottom: -110px;
            left: -70px;
        }

        /* Kartu login */

        .kotak-login {
            position: relative;
            z-index: 1;
            isolation: isolate;
            width: 100%;
            max-width: 440px;
            padding: 38px;
            overflow: hidden;
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(16, 42, 34, .08);
            border-radius: 24px;
            box-shadow:
                0 30px 80px rgba(16, 42, 34, .08),
                0 4px 12px rgba(16, 42, 34, .025);
            animation: masuk .6s cubic-bezier(.2, .7, .2, 1) both;
        }

        .kotak-login::before {
            content: "";
            position: absolute;
            z-index: -1;
            top: -75px;
            right: -65px;
            width: 180px;
            height: 180px;
            border-radius: 48px;
            background: var(--lime);
            opacity: .48;
            transform: rotate(35deg);
        }

        .kotak-login::after {
            content: "";
            position: absolute;
            z-index: -1;
            top: -42px;
            right: -28px;
            width: 120px;
            height: 120px;
            border: 1px solid rgba(16, 42, 34, .09);
            border-radius: 35px;
            transform: rotate(35deg);
        }

        /* Branding */

        .baris-logo {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 38px;
        }

        .ikon-logo {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: var(--hutan);
            color: var(--lime);
        }

        .ikon-logo svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .logo {
            margin: 0;
            color: var(--hutan);
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1.5px;
        }

        .logo span {
            color: var(--lime-gelap);
        }

        .label-login {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 15px;
            padding: 8px 11px;
            border: 1px solid #E0EBD6;
            border-radius: 7px;
            background: #F1F7EB;
            color: #52723E;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
        }

        .titik-status {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #79A94C;
            box-shadow: 0 0 0 3px rgba(121, 169, 76, .14);
        }

        /* Judul */

        h1 {
            margin: 0 0 12px;
            color: var(--hutan);
            font-size: clamp(27px, 5vw, 32px);
            line-height: 1.2;
            letter-spacing: -1.25px;
        }

        .deskripsi {
            margin: 0 0 30px;
            color: var(--redup);
            font-size: 14px;
            line-height: 1.8;
        }

        /* Form */

        .grup-input {
            margin-bottom: 20px;
        }

        label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 9px;
            color: var(--teks);
            font-size: 13px;
            font-weight: 700;
        }

        .input-bungkus {
            position: relative;
        }

        .ikon-input {
            position: absolute;
            top: 50%;
            left: 14px;
            width: 19px;
            height: 19px;
            color: #89968B;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .ikon-input svg {
            display: block;
            width: 100%;
            height: 100%;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        input {
            display: block;
            width: 100%;
            min-height: 53px;
            padding: 14px 15px 14px 43px;
            border: 1px solid var(--garis);
            border-radius: 11px;
            outline: none;
            background: #FBFCFA;
            color: var(--hutan);
            font-family: inherit;
            font-size: 15px;
            transition:
                border-color .2s,
                box-shadow .2s,
                background .2s;
        }

        input::placeholder {
            color: #A1AAA2;
            font-size: 13px;
        }

        input:hover {
            border-color: #C7D4C3;
        }

        input:focus {
            border-color: #80A75E;
            background: var(--putih);
            box-shadow: 0 0 0 4px rgba(128, 167, 94, .14);
        }

        .input-bungkus:focus-within .ikon-input {
            color: #527A3A;
        }

        .input-password {
            padding-right: 51px;
        }

        .tombol-mata {
            position: absolute;
            top: 50%;
            right: 8px;
            display: grid;
            place-items: center;
            width: 37px;
            height: 37px;
            padding: 8px;
            margin: 0;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #7A897D;
            cursor: pointer;
            transform: translateY(-50%);
            transition: background .2s, color .2s;
        }

        .tombol-mata:hover {
            background: #EDF3E8;
            color: var(--hutan);
            transform: translateY(-50%);
        }

        .tombol-mata svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Pesan error */

        .error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0 0 20px;
            padding: 13px;
            border: 1px solid #F1D5D1;
            border-radius: 10px;
            background: #FFF5F3;
            color: var(--merah);
            font-size: 13px;
            line-height: 1.6;
            animation: muncul .25s ease-out;
        }

        .error svg {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            margin-top: 1px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
        }

        /* Tombol masuk */

        .tombol-masuk {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 54px;
            margin-top: 8px;
            padding: 14px 18px;
            overflow: hidden;
            border: 1px solid var(--hutan);
            border-radius: 11px;
            background: var(--hutan);
            color: var(--putih);
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition:
                background .2s,
                box-shadow .2s,
                transform .2s;
        }

        .tombol-masuk:hover {
            background: var(--hutan-terang);
            box-shadow: 0 7px 18px rgba(16, 42, 34, .15);
        }

        .tombol-masuk:active {
            transform: scale(.985);
        }

        .tombol-masuk svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: transform .2s;
        }

        .tombol-masuk:hover svg {
            transform: translateX(3px);
        }

        .tombol-masuk:focus-visible,
        .tombol-mata:focus-visible,
        .kembali:focus-visible {
            outline: 3px solid #A8CF7C;
            outline-offset: 3px;
        }

        /* Bagian bawah */

        .pemisah {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
            color: #9AA49A;
            font-size: 11px;
        }

        .pemisah::before,
        .pemisah::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #E9EEE7;
        }

        .kembali {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            color: #547442;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: color .2s, gap .2s;
        }

        .kembali:hover {
            gap: 12px;
            color: var(--hutan);
        }

        .kembali svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .catatan-keamanan {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin: 27px 0 0;
            color: #89948A;
            font-size: 11px;
        }

        .catatan-keamanan svg {
            width: 15px;
            height: 15px;
            fill: none;
            stroke: #76936A;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Animasi */

        @keyframes masuk {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes muncul {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsif */

        @media (max-width: 480px) {
            body {
                padding: 22px 17px;
            }

            .kotak-login {
                max-width: 420px;
                padding: 29px 23px;
                border-radius: 20px;
            }

            .baris-logo {
                margin-bottom: 32px;
            }

            .logo {
                font-size: 27px;
            }

            h1 {
                font-size: 28px;
            }

            .deskripsi {
                margin-bottom: 27px;
            }

            input {
                min-height: 52px;
            }

            .tombol-masuk {
                min-height: 53px;
            }
        }

        @media (max-height: 650px) {
            body {
                align-items: flex-start;
            }

            .kotak-login {
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .baris-logo {
                margin-bottom: 22px;
            }

            .catatan-keamanan {
                margin-top: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="dekorasi dekorasi-satu" aria-hidden="true"></div>
    <div class="dekorasi dekorasi-dua" aria-hidden="true"></div>

    <main class="kotak-login">

        <div class="baris-logo">
            <div class="ikon-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M20 4C11 4 5 7 5 14a6 6 0 0 0 6 6c7 0 9-7 9-16Z"/>
                    <path d="M4 21c3-5 7-8 12-11"/>
                </svg>
            </div>

            <p class="logo">Lapakly<span>.</span></p>
        </div>

        <p class="label-login">
            <span class="titik-status"></span>
            RUANG PEMILIK USAHA
        </p>

        <h1>Lapakmu, kendalimu.</h1>

        <p class="deskripsi">
            Selamat datang kembali. Masuk untuk mengelola
            produk, harga, dan katalog usahamu.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5"/>
                    <path d="M12 16h.01"/>
                </svg>

                <span>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="form-masuk">

            <div class="grup-input">
                <label for="username">Username</label>

                <div class="input-bungkus">
                    <span class="ikon-input" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21v-2a8 8 0 0 1 16 0v2Z"/>
                        </svg>
                    </span>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="50"
                        autocomplete="username"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="grup-input">
                <label for="password">Password</label>

                <div class="input-bungkus">
                    <span class="ikon-input" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="4" y="10" width="16" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            <path d="M12 14v3"/>
                        </svg>
                    </span>

                    <input
                        class="input-password"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                    >

                    <button
                        type="button"
                        class="tombol-mata"
                        id="lihat-password"
                        aria-label="Tampilkan password"
                        aria-pressed="false"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="tombol-masuk" id="tombol-masuk">
                <span id="teks-tombol">Masuk ke Dashboard</span>

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 12h14"/>
                    <path d="m12 5 7 7-7 7"/>
                </svg>
            </button>

        </form>

        <div class="pemisah">ATAU</div>

        <a href="/" class="kembali">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m12 19-7-7 7-7"/>
                <path d="M5 12h14"/>
            </svg>
            Kembali ke katalog
        </a>

        <p class="catatan-keamanan">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="10" width="16" height="11" rx="2"/>
                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
            </svg>
            Area khusus pemilik usaha
        </p>

    </main>

    <script>
        const kolomPassword = document.getElementById('password');
        const tombolMata = document.getElementById('lihat-password');

        tombolMata.addEventListener('click', function () {
            const tampilkan = kolomPassword.type === 'password';

            kolomPassword.type = tampilkan ? 'text' : 'password';

            tombolMata.setAttribute(
                'aria-pressed',
                String(tampilkan)
            );

            tombolMata.setAttribute(
                'aria-label',
                tampilkan ? 'Sembunyikan password' : 'Tampilkan password'
            );

            kolomPassword.focus();
        });

        const formMasuk = document.getElementById('form-masuk');
        const tombolMasuk = document.getElementById('tombol-masuk');
        const teksTombol = document.getElementById('teks-tombol');

        formMasuk.addEventListener('submit', function () {
            if (!formMasuk.checkValidity()) {
                return;
            }

            tombolMasuk.disabled = true;
            tombolMasuk.style.opacity = '0.8';
            teksTombol.textContent = 'Memeriksa akun...';
        });
    </script>

</body>
</html>
