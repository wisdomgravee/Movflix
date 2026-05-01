<?php
require_once '../config/db.php';
session_start();

$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT movies.*, genres.Name AS genre_name, AVG(reviews.Rating) AS avg_rating
    FROM movies 
    LEFT JOIN genres ON movies.Genre_id = genres.ID_Genre
    LEFT JOIN reviews ON movies.ID_Movies = reviews.ID_Movie
    WHERE movies.ID_Movies = ?
");

$stmt->execute([$movie_id]);
$movie = $stmt->fetch();

if (!$movie) {
    header("Location: ../index.php");

    exit;
}

$reviewsStmt = $pdo->prepare("
    SELECT reviews.*, users.Username 
    FROM reviews 
    JOIN users ON reviews.ID_User = users.ID_User
    WHERE reviews.ID_Movie = ?
    ORDER BY reviews.Created_at DESC
");

$profileImage = '../public/uploads/profiles/user.png';
if (isset($_SESSION['user'])) {
    $profileQuery = $pdo->prepare("SELECT image_path FROM profile_pictures WHERE user_id = ?");
    $profileQuery->execute([$_SESSION['user']['ID_User']]);
    $profile = $profileQuery->fetch();
    if ($profile && $profile['image_path']) {
        $profileImage = $profile['image_path'];
    }
}

$reviewsStmt->execute([$movie_id]);
$reviews = $reviewsStmt->fetchAll();
$max_display = 3;
$total_reviews = count($reviews);
$display_reviews = array_slice($reviews, 0, $max_display);

$trailerId = '';
if (!empty($movie['Trailer_url'])) {
    if (preg_match('/youtube\\.com.*v=([^&n]+)/', $movie['Trailer_url'], $match)) {
        $trailerId = $match[1];
    } elseif (preg_match('/youtu\\.be\/([^&n]+)/', $movie['Trailer_url'], $match)) {
        $trailerId = $match[1];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($movie['Title']) ?> - MOVLIX</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .add-review-section p {
            background: #fff5cc;
            color: #fff;
            padding: 5px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: -85px;
            font-size: 16px;
            font-weight: bold;
            background-image: linear-gradient(359deg, #000000, #f32d2e);
            box-shadow: 0 4px 14px rgb(0 0 0 / 0.1);
        }
        .add-review-section p a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            background: #d32f2f;
            border-radius: 30px;
            box-shadow: 0 4px 14px rgb(0 0 0 / 0.5);
            transition: background 0.3s ease, transform 0.3s ease;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .add-review-section p a:hover {
            background: #b71c1c;
            transform: translateY(-2px);
        }


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
        .star-rating {
            direction: row-reverse;
            font-size: 24px;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ccc;
            padding: 0 5px;
            cursor: pointer;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffd700;
        }
        .view-all-link {
            color: #d32f2f;
            font-weight: bold;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body class="home-body">
<header>
    <div class="logo">MOVLIX</div>
    <div class="top-right">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="profile-menu">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-icon" onclick="toggleProfileDropdown()">
                <div id="dropdown-profiles" class="dropdown-profiles hidden">
                    <a href="../view/profile.php">Profile</a>
                    <a href="../view/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="../view/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="../view/register.php"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>
    </div>
</header>

<main class="movie-detail-container">
    <div class="movie-detail">
        <a href="../index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="movie-content-wrapper">
            <div class="movie-poster-container">
                <img src="<?= htmlspecialchars($movie['Poster_url']) ?>" alt="<?= htmlspecialchars($movie['Title']) ?>" 
                     class="movie-poster" 
                     onerror="this.src='/public/uploads/posters/e0d64153529554569b50be2e4dc40c87.jpg'">
            </div>

            <div class="movie-info">
                <h1><?= htmlspecialchars($movie['Title']) ?> </h1>
                <div class="movie-meta">
                    <span><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($movie['Release_year']) ?> </span>
                    <span><i class="fas fa-film"></i> <?= htmlspecialchars($movie['genre_name']) ?> </span>
                </div>
                <div class="movie-rating">
                    <i class="fas fa-star"></i> <?= number_format($movie['avg_rating'] ?? 0, 1) ?> / 5
                </div>
                <div class="movie-description">
                    <?= nl2br(htmlspecialchars($movie['Description'])) ?>
                </div>

                <?php if (isset($_SESSION['user']) && $_SESSION['user']['Role'] === 'Admin'): ?>
                    <div class="admin-actions">
                        <a href="edit_movie.php?id=<?= $movie['ID_Movies'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit Movie</a>
                        <a href="delete_movie.php?id=<?= $movie['ID_Movies'] ?>" class="btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this movie?')">
                            <i class="fas fa-trash-alt"></i> Delete Movie
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($trailerId)): ?>
            <div class="trailer-box">
                <h2 class="trailer-title"><i class="fas fa-play-circle"></i> Watch Trailer</h2>
                <div class="trailer-frame-container">
                    <iframe src="https://www.youtube.com/embed/<?= $trailerId ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
            </div>
        <?php endif; ?>

        <div class="add-review-section">
            <?php if (isset($_SESSION['user'])): ?>
                <h2>Add Your Review</h2>
                <form action="add_review.php?id=<?= $movie['ID_Movies'] ?>" method="POST" class="review-form">
                    <label for="rating">Rating</label>
                    <div class="star-rating">
                        <input id="star5" name="rating" type="radio" value="5"><label for="star5"><i class="fas fa-star"></i></label>
                        <input id="star4" name="rating" type="radio" value="4"><label for="star4"><i class="fas fa-star"></i></label>
                        <input id="star3" name="rating" type="radio" value="3"><label for="star3"><i class="fas fa-star"></i></label>
                        <input id="star2" name="rating" type="radio" value="2"><label for="star2"><i class="fas fa-star"></i></label>
                        <input id="star1" name="rating" type="radio" value="1"><label for="star1"><i class="fas fa-star"></i></label>
                    </div>

                    <label for="comment">Comment</label>
                    <textarea name="comment" id="comment" rows="5" placeholder="Write your opinion here..."></textarea>

                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane-alt"></i> Submit Review</button>
                </form>
            <?php else: ?>
                <p><a href="../view/login.php"><i class="fas fa-sign-in-alt"></i> Login</a> to add a review</p>
            <?php endif; ?>
        </div>

        <div class="reviews-section">
            <?php if (empty($reviews)): ?>
                <p>No reviews yet.</p>
            <?php else: ?>
            <h2 style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Reviews</span>
                        <?php if ($total_reviews > $max_display): ?>
                            <a href="view_all_reviews.php?id=<?= $movie_id ?>" class="view-all-link">View All
                                (<?= $total_reviews ?>)</a>
                        <?php endif; ?>
                    </h2>

                        <?php foreach ($display_reviews as $idx => $review): ?>
                            <div class="review-item">
                                <span class="review-author"><?= htmlspecialchars($review['Username']) ?> </span>
                                <span class="review-rating"><?= str_repeat('★', (int) $review['Rating']) ?> </span>
                                <p class="review-comment"><?= nl2br(htmlspecialchars($review['Comment'])) ?> </p>
                                <p class="review-date"><?= date('F j, Y', strtotime($review['Created_at'])) ?> </p>

                            <div class="dropdown-container">
                                <button class="dropdown-btn" onclick="toggleDropdown(this)">
                                <i class="fas fa-ellipsis-v"></i></button>
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
            <?php endif; ?>
        </div>
    </div>
</main>



<footer class="main-footer">
    <p>&copy; <?= date('Y') ?> MOVLIX. All rights reserved.</p>
</footer>

<script>
    function toggleProfileDropdown(){
        document.getElementById('dropdown-profiles').classList.toggle('hidden');
    }

    document.addEventListener('click',(e)=>{
        const profileIcon = document.querySelector('.profile-icon');
        const profileMenu = document.getElementById('dropdown-profiles');
        if (profileIcon && !profileIcon.contains(e.target) && !profileMenu.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });

    document.querySelectorAll('.dropdown-btn').forEach(function (btn) {
        btn.addEventListener('click',(e)=>{
            e.stopPropagation();

            // Tutup yang lain dahulu
            document.querySelectorAll('.dropdown-menu.active').forEach(function (activeMenu) {
                activeMenu.classListRemove('active');
            });

            // Buka yang sesuai
            btn.nextElementSibling.classList.toggle('active');
        });
    });

    document.addEventListener('click',(e)=>{
        document.querySelectorAll('.dropdown-menu.active').forEach(function (activeMenu) {
            if (!activeMenu.contains(e.target) &&
                !activeMenu.previousElementSibling.contains(e.target)) {
                activeMenu.classList.remove('active');
            }
        });
    });

    document.getElementById('loadMoreReviews')?.addEventListener('click',(e)=>{
        document.querySelectorAll('.additional-reviews').forEach(function(el){
            el.style.display = '';
        });
        e.target.style.display = 'none';
    });

   function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert("Comment successfully copied!");
        }).catch(function(err) {
            alert("Failed to copy comment.");
        });
    }
</script>

</body>
</html>
