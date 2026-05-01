<?php
require_once '../config/db.php';
session_start();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: ../index.php");

    exit;
}

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE ID_Review = ?");
$stmt->execute([$id]);

$review = $stmt->fetch();

if (!$review) {
    echo "Review not found.";
    exit;
}

if (!isset($_SESSION['user']) ||
    ($_SESSION['user']['Role'] !== 'Admin' &&
    $_SESSION['user']['ID_User'] !== $review['ID_User'])) {
    header("Location: ../index.php");

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newRating = $_POST['rating'];
    $newComment = $_POST['comment'];

    $update = $pdo->prepare("UPDATE reviews SET Rating = ?, Comment = ? WHERE ID_Review = ?");
    $update->execute([$newRating, $newComment, $id]);

    header("Location: movie_detail.php?id=" . $review['ID_Movie']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Review</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="edit-body">
    <div class="edit-container">
        <h2 class="edit-heading"><i class="fas fa-edit"></i> Edit Review</h2>

        <form method="POST" class="edit-form">
            <label for="rating">Rating</label>
            <div class="star-rating">
                <input id="star5" name="rating" type="radio" value="5" <?= $review['Rating']==5 ? 'checked' : '' ?>><label for="star5"><i class="fas fa-star"></i></label>
                <input id="star4" name="rating" type="radio" value="4" <?= $review['Rating']==4 ? 'checked' : '' ?>><label for="star4"><i class="fas fa-star"></i></label>
                <input id="star3" name="rating" type="radio" value="3" <?= $review['Rating']==3 ? 'checked' : '' ?>><label for="star3"><i class="fas fa-star"></i></label>
                <input id="star2" name="rating" type="radio" value="2" <?= $review['Rating']==2 ? 'checked' : '' ?>><label for="star2"><i class="fas fa-star"></i></label>
                <input id="star1" name="rating" type="radio" value="1" <?= $review['Rating']==1 ? 'checked' : '' ?>><label for="star1"><i class="fas fa-star"></i></label>
            </div>

            <label for="comment">Comment</label>
            <textarea name="comment" id="comment" rows="5"><?= htmlspecialchars($review['Comment']) ?> </textarea>

            <button type="submit" class="btn-update"><i class="fas fa-paper-plane-alt fa-fw mr-2"></i> Update Review</button>
            <a href="movie_detail.php?id=<?= $review['ID_Movie'] ?>" class="back-btn"><i class="fas fa-arrow-left fa-fw mr-2"></i> Back</a>
        </form>
    </div>
</body>
</html>
