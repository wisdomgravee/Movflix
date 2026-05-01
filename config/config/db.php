<?php
$host = 'sql100.infinityfree.com';
$dbname = 'if0_41758739_movlix';
$username = 'if0_41758739'; // ganti jika username berbeda
$password = 'Fp7LQcKuXPGI';     // ganti jika ada password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Koneksi berhasil!";
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
