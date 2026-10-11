<?php

$host = '127.0.0.1';
$namaDatabase = 'nama_database';
$username = 'username_database';
$password = 'password_database';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$namaDatabase;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
