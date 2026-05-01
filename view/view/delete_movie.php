<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movie_id > 0) {
    try {
        $pdo->prepare("DELETE FROM reviews WHERE ID_Movie = ?")->execute([$movie_id]);

        $stmt = $pdo->prepare("SELECT Poster_url FROM movies WHERE ID_Movies = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch();

        if ($movie) {
            if (strpos($movie['Poster_url'], 'default_poster.jpg') === false) {
                $filePath = '../' . ltrim($movie['Poster_url'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM movies WHERE ID_Movies = ?");
            $stmt->execute([$movie_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = 'Movie deleted successfully!';
            } else {
                $_SESSION['error'] = 'Movie not found or could not be deleted.';
            }
        } else {
            $_SESSION['error'] = 'Movie not found.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting movie: ' . $e->getMessage();
    }
}

header("Location: ../index.php");
exit;
