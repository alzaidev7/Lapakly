<?php

session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/masuk.php');
    exit;
}

require_once __DIR__ . '/../../inti/koneksi.php';

function aman($teks): string
{
    return htmlspecialchars(
        (string) $teks,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Statistik database
$totalProduk = (int) $pdo->query(
    'SELECT COUNT(*) FROM produk'
)->fetchColumn();

$produkAktif = (int) $pdo->query(
    "SELECT COUNT(*) FROM produk WHERE status = 'aktif'"
)->fetchColumn();

$totalKategori = (int) $pdo->query(
    'SELECT COUNT(DISTINCT kategori)
     FROM produk
     WHERE kategori <> \'\''
)->fetchColumn();

// Produk terbaru
$stmt = $pdo->query(
    'SELECT id, nama, kategori, harga, foto, status, dibuat_pada
     FROM produk
     ORDER BY id DESC
     LIMIT 5'
);

$produkTerbaru = $stmt->fetchAll();

$namaAdmin = $_SESSION['admin_nama'] ?? 'Admin';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102A22">

    <title>Dashboard — Lapakly</title>

    <style>
        :root {
            --hutan: #102A22;
            --hutan-2: #1B4032;
            --lime: #D7F5A5;
            --latar: #F5F7F3;
            --putih: #FFFFFF;
            --teks: #25372D;
            --redup: #849087;
            --garis: #E7ECE5;
            --hijau: #507D3E;
            --merah: #B9433B;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--latar);
            color: var(--teks);
            font-family: Arial, Helvetica, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font: inherit;
        }

        .tata-letak {
            min-height: 100vh;
        }

        /* SIDEBAR */

        .samping {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            width: 250px;
            padding: 27px 18px 20px;
            background: var(--hutan);
            color: white;
            transition: transform .25s ease;
        }

        .merek {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 9px;
            margin-bottom: 42px;
        }

        .ikon-merek {
            display: grid;
            place-items: center;
            width: 41px;
            height: 41px;
            border-radius: 12px;
            background: var(--lime);
            color: var(--hutan);
        }

        .ikon-merek svg {
            width: 23px;
            height: 23px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nama-merek {
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -1.3px;
        }

        .nama-merek span {
            color: var(--lime);
        }

        .label-menu {
            margin: 0 10px 13px;
            color: #9CAF9F;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .daftar-menu {
            display: grid;
            gap: 7px;
        }

        .tautan-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 46px;
            padding: 0 13px;
            border: 1px solid transparent;
            border-radius: 10px;
            color: #C2CEC4;
            font-size: 13px;
            font-weight: 600;
            transition: background .2s, color .2s;
        }

        .tautan-menu:hover,
        .tautan-menu.aktif {
            background: rgba(255, 255, 255, .09);
            color: white;
        }

        .tautan-menu.aktif {
            border-color: rgba(215, 245, 165, .14);
        }

        .tautan-menu.aktif .ikon-menu {
            color: var(--lime);
        }

        .ikon-menu {
            display: grid;
            place-items: center;
            width: 21px;
            height: 21px;
            color: #9EAEA0;
        }

        .ikon-menu svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .samping-bawah {
            margin-top: auto;
        }

        .kartu-bantuan {
            padding: 16px 14px;
            margin-bottom: 17px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 12px;
            background: rgba(255, 255, 255, .045);
        }

        .kartu-bantuan strong {
            display: block;
            margin-bottom: 7px;
            color: var(--lime);
            font-size: 12px;
        }

        .kartu-bantuan p {
            margin: 0;
            color: #C0CCC1;
            font-size: 11px;
            line-height: 1.7;
        }

        .keluar {
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 44px;
            padding: 0 13px;
            border-radius: 9px;
            color: #F2B6AF;
            font-size: 13px;
            font-weight: 600;
            transition: background .2s;
        }

        .keluar:hover {
            background: rgba(255, 255, 255, .08);
        }

        /* KONTEN UTAMA */

        .utama {
            min-height: 100vh;
            margin-left: 250px;
        }

        .kepala {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            min-height: 79px;
            padding: 15px 34px;
            border-bottom: 1px solid var(--garis);
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
        }

        .judul-kecil {
            margin: 0 0 5px;
            color: var(--redup);
            font-size: 11px;
        }

        .judul-halaman {
            margin: 0;
            color: var(--hutan);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .profil {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 39px;
            height: 39px;
            border: 1px solid #DCE8D3;
            border-radius: 12px;
            background: #EDF4E7;
            color: var(--hutan);
            font-size: 14px;
            font-weight: 800;
        }

        .profil-nama {
            margin: 0 0 4px;
            font-size: 12px;
            font-weight: 700;
        }

        .profil-status {
            margin: 0;
            color: var(--redup);
            font-size: 10px;
        }

        /* DROPDOWN PROFIL */

        .wadah-profil {
            position: relative;
        }

        .profil {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 5px 9px 5px 5px;
            border: 1px solid transparent;
            border-radius: 12px;
            background: transparent;
            color: var(--teks);
            text-align: left;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }

        .profil:hover,
        .profil[aria-expanded="true"] {
            border-color: var(--garis);
            background: #F8FAF6;
        }

        .panah-profil {
            width: 15px;
            height: 15px;
            color: var(--redup);
            transition: transform .2s;
        }

        .profil[aria-expanded="true"] .panah-profil {
            transform: rotate(180deg);
        }

        .menu-profil {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 30;
            width: 220px;
            padding: 7px;
            border: 1px solid var(--garis);
            border-radius: 13px;
            background: white;
            box-shadow: 0 15px 40px rgba(16, 42, 34, .12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition: opacity .18s, transform .18s, visibility .18s;
        }

        .menu-profil.buka {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .info-menu-profil {
            padding: 11px 10px;
            border-bottom: 1px solid var(--garis);
            margin-bottom: 5px;
        }

        .info-menu-profil strong {
            display: block;
            overflow: hidden;
            color: var(--hutan);
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .info-menu-profil small {
            display: block;
            margin-top: 5px;
            color: var(--redup);
            font-size: 10px;
        }

        .tautan-profil {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 10px;
            border-radius: 8px;
            color: var(--teks);
            font-size: 12px;
            font-weight: 600;
            transition: background .15s;
        }

        .tautan-profil:hover {
            background: #F2F6EE;
        }

        .tautan-profil svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tautan-profil.keluar-profil {
            color: var(--merah);
        }

        .tautan-profil.keluar-profil:hover {
            background: #FBEDEB;
        }

        @media (max-width: 380px) {
            .profil {
                gap: 6px;
                padding-right: 4px;
            }

            .panah-profil {
                width: 12px;
                height: 12px;
            }

            .menu-profil {
                width: 205px;
            }
        }

        .status-titik {
            display: inline-block;
            width: 6px;
            height: 6px;
            margin-right: 4px;
            border-radius: 50%;
            background: #79A94C;
        }

        .isi-utama {
            width: 100%;
            max-width: 1450px;
            padding: 33px 34px 40px;
            margin: 0 auto;
            animation: muncul .35s ease-out;
        }

        .sambutan {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .label-atas {
            margin: 0 0 10px;
            color: var(--hijau);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.7px;
        }

        .sambutan h2 {
            margin: 0 0 10px;
            color: var(--hutan);
            font-size: clamp(24px, 3vw, 31px);
            letter-spacing: -1px;
        }

        .sambutan p.deskripsi {
            max-width: 520px;
            margin: 0;
            color: var(--redup);
            font-size: 13px;
            line-height: 1.8;
        }

        .tombol-katalog {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 0 16px;
            border: 1px solid var(--hutan);
            border-radius: 10px;
            background: var(--hutan);
            color: white;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            transition: background .2s, transform .2s;
        }

        .tombol-katalog:hover {
            background: var(--hutan-2);
            transform: translateY(-1px);
        }

        .tombol-katalog svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* STATISTIK */

        .grid-statistik {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 27px;
        }

        .kartu-statistik {
            position: relative;
            min-width: 0;
            padding: 22px;
            overflow: hidden;
            border: 1px solid var(--garis);
            border-radius: 15px;
            background: var(--putih);
            box-shadow: 0 4px 15px rgba(16, 42, 34, .018);
            transition: transform .2s, box-shadow .2s;
        }

        .kartu-statistik:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(16, 42, 34, .055);
        }

        .atas-statistik {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 24px;
        }

        .ikon-statistik {
            display: grid;
            place-items: center;
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #EFF5E9;
            color: #527B3F;
        }

        .ikon-statistik.hijau {
            background: #EAF6EF;
            color: #368054;
        }

        .ikon-statistik.krem {
            background: #F8F1DF;
            color: #9D7932;
        }

        .ikon-statistik svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tag-statistik {
            padding: 6px 8px;
            border-radius: 6px;
            background: #F2F5F0;
            color: #72816F;
            font-size: 9px;
            font-weight: 700;
        }

        .angka-statistik {
            margin: 0 0 7px;
            color: var(--hutan);
            font-size: clamp(26px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -1.2px;
            overflow-wrap: anywhere;
        }

        .nama-statistik {
            margin: 0;
            color: var(--redup);
            font-size: 12px;
        }

        /* TABEL PRODUK */

        .panel-produk {
            overflow: hidden;
            border: 1px solid var(--garis);
            border-radius: 15px;
            background: white;
            box-shadow: 0 4px 15px rgba(16, 42, 34, .018);
        }

        .kepala-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 22px 23px;
            border-bottom: 1px solid var(--garis);
        }

        .kepala-panel h3 {
            margin: 0 0 6px;
            color: var(--hutan);
            font-size: 15px;
            letter-spacing: -.3px;
        }

        .kepala-panel p {
            margin: 0;
            color: var(--redup);
            font-size: 11px;
        }

        .tautan-semua {
            color: var(--hijau);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .tautan-semua:hover {
            text-decoration: underline;
        }

        .bungkus-tabel {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            padding: 13px 20px;
            background: #FAFBF9;
            color: #8A958B;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        td {
            padding: 15px 20px;
            border-top: 1px solid #F0F3EE;
            color: var(--teks);
            font-size: 12px;
            white-space: nowrap;
        }

        tbody tr {
            transition: background .15s;
        }

        tbody tr:hover {
            background: #FBFCFA;
        }

        .nama-produk {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 175px;
        }

        .foto-produk {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 43px;
            height: 43px;
            overflow: hidden;
            border: 1px solid #E9EEE7;
            border-radius: 10px;
            background: #F2F5F0;
            color: #7E8C7E;
        }

        .foto-produk img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .foto-produk svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        .teks-produk strong {
            display: block;
            max-width: 220px;
            overflow: hidden;
            color: var(--hutan);
            font-size: 12px;
            text-overflow: ellipsis;
        }

        .teks-produk small {
            display: block;
            margin-top: 5px;
            color: var(--redup);
            font-size: 10px;
        }

        .kategori {
            color: #6D7D70;
        }

        .harga {
            color: var(--hutan);
            font-weight: 700;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .status.aktif {
            background: #EAF6EA;
            color: #347345;
        }

        .status.nonaktif {
            background: #FBEDEB;
            color: #A34840;
        }

        .status::before {
            content: "";
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .kosong {
            padding: 45px 20px;
            text-align: center;
        }

        .ikon-kosong {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            margin: 0 auto 15px;
            border-radius: 16px;
            background: #EFF5E9;
            color: #527B3F;
        }

        .ikon-kosong svg {
            width: 25px;
            height: 25px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .kosong h4 {
            margin: 0 0 8px;
            color: var(--hutan);
            font-size: 14px;
        }

        .kosong p {
            margin: 0;
            color: var(--redup);
            font-size: 12px;
            line-height: 1.7;
        }

        .catatan-kaki {
            margin: 24px 0 0;
            color: #929C92;
            font-size: 10px;
            text-align: center;
        }

        /* MOBILE */

        .tombol-menu-mobile {
            display: none;
            place-items: center;
            width: 39px;
            height: 39px;
            border: 1px solid var(--garis);
            border-radius: 10px;
            background: white;
            color: var(--hutan);
            cursor: pointer;
        }

        .tombol-menu-mobile svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .lapisan {
            display: none;
        }

        @keyframes muncul {
            from {
                opacity: 0;
                transform: translateY(7px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (min-width: 1400px) {
            .grid-statistik {
                gap: 22px;
            }

            .kartu-statistik {
                padding: 26px;
            }
        }

        @media (max-width: 1100px) {
            .samping {
                width: 220px;
            }

            .utama {
                margin-left: 220px;
            }

            .kepala {
                padding-right: 24px;
                padding-left: 24px;
            }

            .isi-utama {
                padding-right: 24px;
                padding-left: 24px;
            }

            .grid-statistik {
                gap: 12px;
            }

            .kartu-statistik {
                padding: 17px;
            }

            th, td {
                padding-right: 14px;
                padding-left: 14px;
            }
        }

        @media (max-width: 760px) {
            .samping {
                width: 270px;
                max-width: 84vw;
                transform: translateX(-105%);
                box-shadow: none;
            }

            .samping.buka {
                transform: translateX(0);
                box-shadow: 20px 0 60px rgba(0, 0, 0, .16);
            }

            .lapisan.buka {
                position: fixed;
                inset: 0;
                z-index: 15;
                display: block;
                background: rgba(10, 25, 18, .42);
                backdrop-filter: blur(2px);
            }

            .utama {
                margin-left: 0;
            }

            .kepala {
                min-height: 70px;
                padding: 12px 17px;
            }

            .bagian-kepala-kiri {
                display: flex;
                align-items: center;
                gap: 11px;
            }

            .tombol-menu-mobile {
                display: grid;
            }

            .judul-kecil {
                font-size: 10px;
            }

            .judul-halaman {
                font-size: 15px;
            }

            .profil {
                gap: 8px;
            }

            .avatar {
                width: 36px;
                height: 36px;
            }

            .profil-nama {
                max-width: 100px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .isi-utama {
                padding: 25px 17px 30px;
            }

            .sambutan {
                align-items: flex-start;
                flex-direction: column;
                gap: 17px;
                margin-bottom: 23px;
            }

            .sambutan h2 {
                font-size: 26px;
            }

            .tombol-katalog {
                width: 100%;
            }

            .grid-statistik {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-bottom: 21px;
            }

            .kartu-statistik {
                padding: 16px;
                border-radius: 13px;
            }

            .atas-statistik {
                margin-bottom: 18px;
            }

            .angka-statistik {
                font-size: 29px;
            }

            .nama-statistik {
                font-size: 11px;
            }

            .kepala-panel {
                padding: 18px 16px;
            }

            th, td {
                padding: 13px 14px;
            }

            .catatan-kaki {
                line-height: 1.7;
            }
        }

        @media (max-width: 380px) {
            .kepala {
                padding-right: 12px;
                padding-left: 12px;
            }

            .profil-nama {
                max-width: 74px;
            }

            .isi-utama {
                padding-right: 12px;
                padding-left: 12px;
            }

            .kartu-statistik {
                padding: 13px;
            }

            .tag-statistik {
                font-size: 8px;
            }

            .angka-statistik {
                font-size: 26px;
            }

            .kepala-panel {
                gap: 8px;
                padding-right: 12px;
                padding-left: 12px;
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

<div class="tata-letak">

    <aside class="samping" id="samping">

        <a href="/admin/index.php" class="merek">
            <span class="ikon-merek">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 4C11 4 5 7 5 14a6 6 0 0 0 6 6c7 0 9-7 9-16Z"/>
                    <path d="M4 21c3-5 7-8 12-11"/>
                </svg>
            </span>

            <p class="nama-merek">Lapakly<span>.</span></p>
        </a>

        <p class="label-menu">MENU UTAMA</p>

        <nav class="daftar-menu" aria-label="Navigasi admin">

            <a href="/admin/index.php" class="tautan-menu aktif" aria-current="page">
                <span class="ikon-menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="8" rx="1.5"/>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"/>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"/>
                        <rect x="3" y="15" width="7" height="6" rx="1.5"/>
                    </svg>
                </span>
                Dashboard
            </a>

            <a href="/admin/produk/index.php" class="tautan-menu">
                <span class="ikon-menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m4 7 8-4 8 4-8 4-8-4Z"/>
                        <path d="m4 12 8 4 8-4"/>
                        <path d="m4 17 8 4 8-4"/>
                    </svg>
                </span>
                Produk
            </a>

            <a href="/index.php" class="tautan-menu" target="_blank" rel="noopener noreferrer">
                <span class="ikon-menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14 3h7v7"/>
                        <path d="m10 14 11-11"/>
                        <path d="M19 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6"/>
                    </svg>
                </span>
                Lihat Katalog
            </a>

        </nav>

        <div class="samping-bawah">

            <div class="kartu-bantuan">
                <strong>Lapakly Workspace</strong>
                <p>Kelola produk dan jaga katalog usahamu tetap terbaru.</p>
            </div>

            <a href="/admin/keluar.php" class="keluar">
                <span class="ikon-menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M10 17l5-5-5-5"/>
                        <path d="M15 12H3"/>
                        <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                    </svg>
                </span>
                Keluar dari akun
            </a>

        </div>

    </aside>

    <div class="lapisan" id="lapisan"></div>

    <div class="utama">

        <header class="kepala">

            <div class="bagian-kepala-kiri">

                <button
                    type="button"
                    class="tombol-menu-mobile"
                    id="tombol-menu"
                    aria-label="Buka navigasi"
                    aria-expanded="false"
                    aria-controls="samping"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div>
                    <p class="judul-kecil">Lapakly Workspace / Admin</p>
                    <h1 class="judul-halaman">Dashboard</h1>
                </div>

            </div>


            <div class="wadah-profil">
                <button
                    type="button"
                    class="profil"
                    id="tombol-profil"
                    aria-expanded="false"
                    aria-controls="menu-profil"
                    aria-label="Buka menu profil admin"
                >
                    <span class="avatar">
                        <?= aman(strtoupper(substr($namaAdmin, 0, 1))) ?>
                    </span>

                    <span>
                        <span class="profil-nama" style="display:block">
                            <?= aman($namaAdmin) ?>
                        </span>

                        <span class="profil-status" style="display:block">
                            <span class="status-titik"></span>
                            Administrator
                        </span>
                    </span>

                    <svg
                        class="panah-profil"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m7 10 5 5 5-5"/>
                    </svg>
                </button>

                <div class="menu-profil" id="menu-profil">

                    <div class="info-menu-profil">
                        <strong><?= aman($namaAdmin) ?></strong>
                        <small>Pengaturan akun administrator</small>
                    </div>

                    <a href="/admin/profil.php" class="tautan-profil">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                        </svg>
                        Pengaturan profil
                    </a>

                    <a
                        href="/admin/keluar.php"
                        class="tautan-profil keluar-profil"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10 17l5-5-5-5"/>
                            <path d="M15 12H3"/>
                            <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                        </svg>
                        Keluar dari akun
                    </a>

                </div>
            </div>
        </header>

        <main class="isi-utama">

            <section class="sambutan">

                <div>
                    <p class="label-atas">RINGKASAN USAHA</p>

                    <h2>Halo, <?= aman($namaAdmin) ?>! 👋</h2>

                    <p class="deskripsi">
                        Ini ringkasan katalog usahamu.
                        Pantau produk dan kelola lapakmu dari satu tempat.
                    </p>
                </div>

                <a
                    href="/index.php"
                    class="tombol-katalog"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Lihat katalog
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14 3h7v7"/>
                        <path d="m10 14 11-11"/>
                        <path d="M19 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6"/>
                    </svg>
                </a>

            </section>

            <section class="grid-statistik" aria-label="Statistik produk">

                <article class="kartu-statistik">
                    <div class="atas-statistik">
                        <span class="ikon-statistik">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m4 7 8-4 8 4-8 4-8-4Z"/>
                                <path d="m4 12 8 4 8-4"/>
                                <path d="m4 17 8 4 8-4"/>
                            </svg>
                        </span>
                        <span class="tag-statistik">SEMUA</span>
                    </div>

                    <p class="angka-statistik">
                        <?= number_format($totalProduk, 0, ',', '.') ?>
                    </p>

                    <p class="nama-statistik">Total produk</p>
                </article>

                <article class="kartu-statistik">
                    <div class="atas-statistik">
                        <span class="ikon-statistik hijau">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m8 12 3 3 5-6"/>
                            </svg>
                        </span>
                        <span class="tag-statistik">TERSEDIA</span>
                    </div>

                    <p class="angka-statistik">
                        <?= number_format($produkAktif, 0, ',', '.') ?>
                    </p>

                    <p class="nama-statistik">Produk aktif</p>
                </article>

                <article class="kartu-statistik">
                    <div class="atas-statistik">
                        <span class="ikon-statistik krem">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="4" width="8" height="7" rx="1.5"/>
                                <rect x="13" y="4" width="8" height="7" rx="1.5"/>
                                <rect x="3" y="13" width="8" height="7" rx="1.5"/>
                                <rect x="13" y="13" width="8" height="7" rx="1.5"/>
                            </svg>
                        </span>
                        <span class="tag-statistik">UNIK</span>
                    </div>

                    <p class="angka-statistik">
                        <?= number_format($totalKategori, 0, ',', '.') ?>
                    </p>

                    <p class="nama-statistik">Kategori produk</p>
                </article>

            </section>

            <section class="panel-produk">

                <div class="kepala-panel">
                    <div>
                        <h3>Produk terbaru</h3>
                        <p>Lima produk terakhir dari database.</p>
                    </div>

                    <a href="/admin/produk/index.php" class="tautan-semua">
                    Kelola produk →</a>
                </div>

                <?php if (empty($produkTerbaru)): ?>

                    <div class="kosong">
                        <div class="ikon-kosong">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m4 7 8-4 8 4-8 4-8-4Z"/>
                                <path d="m4 12 8 4 8-4"/>
                                <path d="m4 17 8 4 8-4"/>
                            </svg>
                        </div>

                        <h4>Belum ada produk</h4>

                        <p>
                            Produk yang ditambahkan nanti akan muncul di sini.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="bungkus-tabel">
                        <table>
                            <thead>
                                <tr>
                                    <th>PRODUK</th>
                                    <th>KATEGORI</th>
                                    <th>HARGA</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($produkTerbaru as $item): ?>

                                    <tr>
                                        <td>
                                            <div class="nama-produk">

                                                <div class="foto-produk">
                                                    <?php
                                                    $namaFoto = basename(
                                                        (string) ($item['foto'] ?? '')
                                                    );

                                                    $lokasiFoto = __DIR__
                                                        . '/../foto/'
                                                        . $namaFoto;
                                                    ?>

                                                    <?php if (
                                                        $namaFoto !== ''
                                                        && is_file($lokasiFoto)
                                                    ): ?>

                                                        <img
                                                            src="/foto/<?= rawurlencode($namaFoto) ?>"
                                                            alt=""
                                                            loading="lazy"
                                                        >

                                                    <?php else: ?>

                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <rect x="3" y="3" width="18" height="18" rx="3"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <path d="m21 15-5-5L5 21"/>
                                                        </svg>

                                                    <?php endif; ?>

                                                </div>

                                                <div class="teks-produk">
                                                    <strong>
                                                        <?= aman($item['nama']) ?>
                                                    </strong>

                                                    <small>
                                                        ID #<?= (int) $item['id'] ?>
                                                    </small>
                                                </div>

                                            </div>
                                        </td>

                                        <td class="kategori">
                                            <?= aman($item['kategori']) ?>
                                        </td>

                                        <td class="harga">
                                            Rp<?= number_format(
                                                (float) $item['harga'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="status <?= $item['status'] === 'aktif' ? 'aktif' : 'nonaktif' ?>">
                                                <?= $item['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </section>

            <p class="catatan-kaki">
                Lapakly Admin · Data diperbarui setiap kali halaman dibuka.
            </p>

        </main>

    </div>

</div>

<script>
    const samping = document.getElementById('samping');
    const lapisan = document.getElementById('lapisan');
    const tombolMenu = document.getElementById('tombol-menu');

    function ubahMenu(buka) {
        samping.classList.toggle('buka', buka);
        lapisan.classList.toggle('buka', buka);

        tombolMenu.setAttribute('aria-expanded', String(buka));
        tombolMenu.setAttribute(
            'aria-label',
            buka ? 'Tutup navigasi' : 'Buka navigasi'
        );
    }

    tombolMenu.addEventListener('click', function () {
        ubahMenu(!samping.classList.contains('buka'));
    });

    lapisan.addEventListener('click', function () {
        ubahMenu(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            ubahMenu(false);
        }
    });

    const tombolProfil = document.getElementById('tombol-profil');
    const menuProfil = document.getElementById('menu-profil');

    function ubahProfil(buka) {
        menuProfil.classList.toggle('buka', buka);
        tombolProfil.setAttribute('aria-expanded', String(buka));
    }

    tombolProfil.addEventListener('click', function () {
        ubahProfil(!menuProfil.classList.contains('buka'));
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.wadah-profil')) {
            ubahProfil(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            ubahProfil(false);
        }
    });
</script>

</body>
</html>
