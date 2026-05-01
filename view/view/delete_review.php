<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../view/login.php");

    exit;
}

$reviewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE ID_Review = ?");
$stmt->execute([$reviewId]);

$review = $stmt->fetch();

if (!$review) {
    header("Location: ../index.php");

    exit;
}

if ($review['ID_User'] == $_SESSION['user']['ID_User'] ||
    $_SESSION['user']['Role'] == 'Admin') {

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE ID_Review = ?");
    $stmt->execute([$reviewId]);

    // Setelah delete, kembali ke movie_detail yang sesuai
    header("Location: ../view/movie_detail.php?id=" . $review['ID_Movie']);
    exit;

} else {
    header("Location: ../index.php");

    exit;
}

?>
