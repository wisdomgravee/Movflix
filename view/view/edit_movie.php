<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM movies WHERE ID_Movies = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) {
    echo "Movie not found.";
    exit;
}

$genres = $pdo->query("SELECT * FROM genres")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $genre_id = $_POST['genre'];
    $release_year = $_POST['release_year'];
    $description = $_POST['description'];
    $trailer_url = $_POST['trailer_url'];
    $poster_url = $movie['Poster_url'];

    // Cek jika ada poster baru
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['poster']['tmp_name'];
        $fileName = $_FILES['poster']['name'];
        $fileSize = $_FILES['poster']['size'];
        $fileType = $_FILES['poster']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024;

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            $newFileName = uniqid() . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $uploadDir = '../public/uploads/posters/';
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $poster_url = '/public/uploads/posters/' . $newFileName;
            } else {
                $error = 'Failed to upload poster.';
            }
        } else {
            $error = 'Invalid file type or size.';
        }
    }

    if (empty($error)) {
        $updateStmt = $pdo->prepare("
            UPDATE movies 
            SET Title = ?, Genre_id = ?, Release_year = ?, Description = ?, Poster_url = ?, Trailer_url = ?
            WHERE ID_Movies = ?
        ");
        $updateStmt->execute([$title, $genre_id, $release_year, $description, $poster_url, $trailer_url, $id]);

        header("Location: ../index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie - MOVLIX Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../public/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { max-width: 800px; margin: 30px auto; padding: 20px; background: #222; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #444; }
        .admin-title { color: #d32f2f; margin: 0; }
        .back-btn { color: #fff; text-decoration: none; background: #444; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .back-btn:hover { background: #555; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: #ddd; }
        .form-control { width: 100%; padding: 10px; background: #333; border: 1px solid #444; border-radius: 5px; color: #fff; font-size: 16px; }
        .form-control:focus { outline: none; border-color: #d32f2f; }
        textarea.form-control { min-height: 100px; resize: vertical; }
        .btn-submit { background: #d32f2f; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { background: #b71c1c; }
        .poster-preview { max-width: 200px; max-height: 300px; border-radius: 5px; border: 2px solid #444; display: block; margin: 10px auto 20px auto; }
        .alert-error { background: #c62828; padding: 10px; color: white; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-edit"></i> Edit Movie</h1>
            <a href="../index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title" class="form-label">Title *</label>
                <input type="text" name="title" id="title" class="form-control" required value="<?= htmlspecialchars($movie['Title']) ?>">
            </div>

            <div class="form-group">
                <label for="genre" class="form-label">Genre *</label>
                <select name="genre" id="genre" class="form-control" required>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= $genre['ID_Genre'] ?>" <?= $genre['ID_Genre'] == $movie['Genre_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($genre['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="release_year" class="form-label">Release Year *</label>
                <input type="number" name="release_year" id="release_year" class="form-control"
                       required value="<?= htmlspecialchars($movie['Release_year']) ?>">
            </div>

            <div class="form-group">
                <label for="poster" class="form-label">Poster Image (jpg/png/jpeg, max 2MB)</label>
                <input type="file" name="poster" id="poster" class="form-control" accept="image/*">
            </div>

            <img id="posterPreview" class="poster-preview" src="<?= htmlspecialchars($movie['Poster_url']) ?>" alt="Poster Preview">

            <div class="form-group">
                <label for="trailer_url" class="form-label">Trailer URL (YouTube)</label>
                <input type="url" name="trailer_url" id="trailer_url" class="form-control"
                       value="<?= htmlspecialchars($movie['Trailer_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=...">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($movie['Description']) ?></textarea>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Movie</button>
        </form>
    </div>

    <script>
        const posterInput = document.getElementById('poster');
        const posterPreview = document.getElementById('posterPreview');

        posterInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    posterPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
