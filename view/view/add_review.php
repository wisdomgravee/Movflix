<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../view/login.php");

    exit;
}

$movie_id = $_GET['id'] ?? 0;

if (!$movie_id) {
    header("Location: ../index.php");

    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int) $_POST['rating'];
    $comment = trim($_POST['comment']);
    $userId = $_SESSION['user']['ID_User'];

    $stmt = $pdo->prepare("INSERT INTO reviews (ID_Movie, ID_User, Rating, Comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$movie_id, $userId, $rating, $comment]);

    header("Location: movie_detail.php?id=$movie_id");

    exit;
}

header("Location: movie_detail.php?id=$movie_id");

exit;

?>
