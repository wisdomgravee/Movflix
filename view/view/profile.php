<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['ID_User'];
$username = $_SESSION['user']['Username'];
$email = $_SESSION['user']['Email'];

// Fetch profile picture
$profileQuery = $pdo->prepare("SELECT image_path FROM profile_pictures WHERE user_id = ?");
$profileQuery->execute([$userId]);
$profile = $profileQuery->fetch();
$profileImage = $profile && $profile['image_path'] ? $profile['image_path'] : '../public/uploads/profiles/user.png';

// Fetch recent reviews by user
$commentsQuery = $pdo->prepare("
    SELECT reviews.*, movies.Title, movies.ID_Movies, users.Username
    FROM reviews
    JOIN movies ON reviews.ID_Movie = movies.ID_Movies
    JOIN users ON reviews.ID_User = users.ID_User
    WHERE reviews.ID_User = ?
    ORDER BY reviews.Created_at DESC
    LIMIT 10
");
$commentsQuery->execute([$userId]);
$display_reviews = $commentsQuery->fetchAll();
$total_reviews = count($display_reviews);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile - MOVLIX</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
</head>

<body>
<header>
    <div class="logo">MOVLIX</div>
    <div class="top-right">
        <div class="profile-menu">
            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-icon"
                 onclick="toggleProfileDropdown()" />
            <div id="dropdown-profiles" class="dropdown-profiles hidden">
                <a href="../view/profile.php">Profile</a>
                <a href="../view/logout.php">Logout</a>
            </div>
        </div>
    </div>
</header>

<main class="movie-detail-container">
    <div class="movie-detail">
        <a href="../index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Movies
        </a>

        <div class="profile-container">
            <div class="profile-header">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-details">
                    <h2 class="username"><?= htmlspecialchars($username) ?></h2>
                    <p class="email"><?= htmlspecialchars($email) ?></p>
                </div>
            </div>

            <div class="recent-comments">
                <h5>My Recent Comments (<?= $total_reviews ?>)</h5>
                <?php if ($total_reviews > 0): ?>
                    <?php foreach ($display_reviews as $review): ?>
                        <div class="review-item">
                            <h4 style="margin-bottom: 8px; text-decoration: underline;">
                                <?= htmlspecialchars($review['Title']) ?>
                            </h4>
                            <span class="review-author"><?= htmlspecialchars($review['Username']) ?></span>
                            <span class="review-rating"><?= str_repeat('★', (int) $review['Rating']) ?></span>
                            <p class="review-comment"><?= nl2br(htmlspecialchars($review['Comment'])) ?></p>
                            <p class="review-date"><?= date('F j, Y', strtotime($review['Created_at'])) ?></p>

                            <div class="dropdown-container">
                                <button class="dropdown-btn" onclick="toggleDropdown(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <?php if ($_SESSION['user']['ID_User'] == $review['ID_User'] || $_SESSION['user']['Role'] === 'Admin'): ?>
                                        <a href="delete_review.php?id=<?= $review['ID_Review'] ?>&movie_id=<?= $review['ID_Movie'] ?>"
                                           onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                                    <?php endif; ?>
                                    <a href="#" onclick='copyToClipboard("<?= addslashes($review['Comment']) ?>")'>Copy Text</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-comments">You haven’t commented on any movies yet.</p>
                <?php endif; ?>
            </div>
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
