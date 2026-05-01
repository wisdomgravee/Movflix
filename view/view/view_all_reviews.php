<?php
require_once '../config/db.php';
session_start();

$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data film
$stmt = $pdo->prepare("
    SELECT movies.*, genres.Name AS genre_name
    FROM movies 
    LEFT JOIN genres ON movies.Genre_id = genres.ID_Genre
    WHERE movies.ID_Movies = ?
");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch();

// Ambil data review
$reviews = $pdo->prepare("
    SELECT reviews.*, users.Username, reviews.Comment AS Review_Text
    FROM reviews 
    JOIN users ON reviews.ID_User = users.ID_User
    WHERE reviews.ID_Movie = ?
    ORDER BY reviews.Created_at DESC
");

$profileImage = '../public/uploads/profiles/user.png'; // default image
if (isset($_SESSION['user'])) {
    $profileQuery = $pdo->prepare("SELECT image_path FROM profile_pictures WHERE user_id = ?");
    $profileQuery->execute([$_SESSION['user']['ID_User']]);
    $profile = $profileQuery->fetch();
    if ($profile && $profile['image_path']) {
        $profileImage = $profile['image_path'];
    }
}

$reviews->execute([$movie_id]);
$reviews = $reviews->fetchAll();
$total_reviews = count($reviews);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Reviews - <?= htmlspecialchars($movie['Title']) ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    .review-item {
        background: #333;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        position: relative;
    }

    .dropdown-container {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .dropdown-menu.active {
        display: block;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        background: none;
        color: rgb(255, 255, 255);
        padding: 10px;
        border-radius: 5px;
        z-index: 100;
    }

    .dropdown-menu a {
        color: #ffffff;
        text-decoration: none;
        display: block;
        margin-bottom: 5px;
    }

    .dropdown-menu a:last-child {
        margin-bottom: 0;
    }
</style>

<body>

<header>
    <div class="logo">MOVLIX</div>
    <div class="top-right">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="profile-menu">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-icon"
                     onclick="toggleProfileDropdown()"/>

                <div id="dropdown-profiles" class="dropdown-profiles hidden">
                    <a href="../view/profile.php">Profile</a>
                    <a href="../view/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="../view/login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i>Login</a>
            <a href="../view/register.php"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>
    </div>
</header>

<main class="movie-detail-container">
    <div class="movie-detail">
        <a href="../view/movie_detail.php?id=<?= $movie_id ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="reviews-section">
            <h2>Reviews (<?= $total_reviews ?>)</h2>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <span class="review-author"><?= htmlspecialchars($review['Username']) ?></span>
                    <span class="review-rating"><?= str_repeat('★', (int)$review['Rating']) ?></span>
                    <p class="review-comment"><?= nl2br(htmlspecialchars($review['Comment'])) ?></p>
                    <p class="review-date"><?= date('F j, Y', strtotime($review['Created_at'])) ?></p>

                    <div class="dropdown-container">
                        <button class="dropdown-btn" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <?php if (isset($_SESSION['user']) && ($_SESSION['user']['ID_User'] == $review['ID_User'] || $_SESSION['user']['Role'] ===                                  'Admin')): ?>
                                <a href="delete_review.php?id=<?= $review['ID_Review'] ?>&movie_id=<?= $movie_id ?>"
                                   onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                            <?php endif; ?>
                            <a href="#" onclick='copyToClipboard("<?= addslashes($review['Comment']) ?>")'>Copy Text</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<footer class="main-footer">
    <p>&copy; <?= date('Y') ?> MOVLIX. All rights reserved.</p>
</footer>

<script>
    function toggleDropdown(button) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== button.nextElementSibling) {
                menu.style.display = 'none';
            }
        });

        const menu = button.nextElementSibling;
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    window.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
        }

        const profileIcon = document.querySelector('.profile-icon');
        const dropdown = document.getElementById('dropdown-profiles');
        if (profileIcon && dropdown && !profileIcon.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    function copyToClipboard(text) {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('Teks berhasil disalin!');
        } catch (err) {
            alert('Gagal menyalin teks');
        }
        document.body.removeChild(textarea);
    }

    function toggleProfileDropdown() {
        const menu = document.getElementById('dropdown-profiles');
        menu.classList.toggle('hidden');
    }
</script>

</body>
</html>
