<?php
require_once '../config/db.php';
session_start();

// Ensure only admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $genre_id = intval($_POST['genre_id']);
    $release_year = intval($_POST['release_year']);
    $description = htmlspecialchars(trim($_POST['description']));
    $trailer_url = htmlspecialchars(trim($_POST['trailer_url']));
    $poster_url = '';

    // Handle poster upload
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = $_FILES['poster']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024;

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            $newFileName = md5(time() . $fileName) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $uploadDir = '../public/uploads/posters/';
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = '/public/uploads/posters/' . $newFileName;
            } else {
                $error = 'Error uploading the file.';
            }
        } else {
            $error = 'Invalid file type or size.';
        }
    } else {
        $error = 'Please select a poster image.';
    }

    if (empty($error)) {
        if (empty($title) || empty($genre_id) || empty($release_year) || empty($poster_url)) {
            $error = 'Please fill all required fields!';
        } elseif ($release_year < 1900 || $release_year > date('Y') + 5) {
            $error = 'Invalid release year!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO movies (Title, Genre_id, Release_year, Poster_url, Description, Trailer_url)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $genre_id, $release_year, $poster_url, $description, $trailer_url]);

                $success = 'Movie added successfully!';
                $_POST = array();
            } catch (PDOException $e) {
                $error = 'Error adding movie: ' . $e->getMessage();
            }
        }
    }
}

$genres = $pdo->query("SELECT * FROM genres ORDER BY Name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Movie - MOVLIX Admin</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styling form seperti sebelumnya */
        .admin-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #222;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .form-group { margin-bottom: 20px; }
        .form-label { color: #ddd; margin-bottom: 5px; display: block; }
        .form-control {
            width: 100%;
            padding: 10px;
            background: #333;
            color: #fff;
            border: 1px solid #444;
            border-radius: 5px;
        }
        .btn-submit {
            background: #d32f2f;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .poster-preview {
            max-width: 200px;
            margin-top: 10px;
            display: none;
            border: 2px solid #444;
            border-radius: 5px;
        }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #2e7d32; color: white; }
        .alert-error { background: #c62828; color: white; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <h1> <i class="fas fa-plus-circle"></i> Add New Movie</h1>
        <a href="../index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input class="form-control" type="text" name="title" id="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="genre_id">Genre *</label>
                <select class="form-control" name="genre_id" id="genre_id" required>
                    <option value="">-- Select Genre --</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= $genre['ID_Genre'] ?>" <?= ($_POST['genre_id'] ?? '') == $genre['ID_Genre'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($genre['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="release_year">Release Year *</label>
                <input class="form-control" type="number" name="release_year" id="release_year"
                       value="<?= htmlspecialchars($_POST['release_year'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="poster">Poster Image *</label>
                <input class="form-control" type="file" name="poster" id="poster" accept="image/*" required>
                <img id="posterPreview" class="poster-preview" src="" alt="Poster Preview">
            </div>

            <div class="form-group">
                <label class="form-label" for="trailer_url">Trailer URL (YouTube)</label>
                <input class="form-control" type="url" name="trailer_url" id="trailer_url"
                       value="<?= htmlspecialchars($_POST['trailer_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=...">
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" name="description" id="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Movie</button>
        </form>
    </div>

    <script>
        document.getElementById('poster').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('posterPreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>
