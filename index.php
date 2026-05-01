<?php
require_once 'config/db.php';
session_start();

$moviesPerPage = 10;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($currentPage - 1) * $moviesPerPage;

// Query Movie + Rating
$baseQuery = "
    SELECT movies.*, genres.Name AS genre_name, AVG(reviews.Rating) AS avg_rating
    FROM movies 
    LEFT JOIN genres ON movies.Genre_id = genres.ID_Genre
    LEFT JOIN reviews ON movies.ID_Movies = reviews.ID_Movie
";

$genreFilter = !empty($_GET['genre']) ? " WHERE movies.Genre_id = " . (int) $_GET['genre'] : "";
$ratingFilter = "";
if (!empty($_GET['rating'])) {
    $rating = (int) $_GET['rating'];
    $ratingFilter = $genreFilter ? " AND" : " WHERE";
    $ratingFilter .= " (SELECT AVG(Rating) FROM reviews WHERE ID_Movie = movies.ID_Movies) >= $rating";
}

$searchFilter = "";
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $searchFilter = ($genreFilter || $ratingFilter) ? " AND" : " WHERE";
    $searchFilter .= " movies.Title LIKE " . $pdo->quote($search);
}

$countQuery = "SELECT COUNT(*) as total FROM movies" . $genreFilter . $ratingFilter . $searchFilter;
$totalMovies = $pdo->query($countQuery)->fetch()['total'];
$totalPages = ceil($totalMovies / $moviesPerPage);

$query = $baseQuery . $genreFilter . $ratingFilter . $searchFilter . "
    GROUP BY movies.ID_Movies 
    ORDER BY movies.Title ASC 
    LIMIT $moviesPerPage OFFSET $offset
";

$carouselQuery = "
    SELECT movies.ID_Movies, movies.Title, movies.Poster_url, movies.Release_year, AVG(reviews.Rating) AS avg_rating
    FROM movies
    LEFT JOIN reviews ON movies.ID_Movies = reviews.ID_Movie
    GROUP BY movies.ID_Movies
    ORDER BY movies.Release_year DESC 
    LIMIT 20
";

$carouselMovies = $pdo->query($carouselQuery)->fetchAll();
$movies = $pdo->query($query)->fetchAll();

$profileImage = 'public/uploads/profiles/user.png';
if (isset($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT image_path FROM profile_pictures WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['ID_User']]);
    $profileData = $stmt->fetch();
    if ($profileData && $profileData['image_path']) {
        $profileImage = htmlspecialchars($profileData['image_path']);
    }
}

$genres = $pdo->query("SELECT * FROM genres")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOVLIX - Movie Collection</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="home-body">
<header>
    <div class="logo">MOVLIX</div>
    <div class="top-right">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="profile-menu">
                <img src="<?= $profileImage ?>" alt="Profile" class="profile-icon" onclick="toggleDropdown()">
                <div id="dropdown-profiles" class="dropdown-profiles hidden">
                    <a href="view/profile.php">Profile</a>
                    <a href="view/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="view/login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="view/register.php" class="register-btn">Register</a>
        <?php endif; ?>
    </div>
</header>

<section class="carousel">
    <div class="carousel-container">
        <?php foreach ($carouselMovies as $cmovie): ?>
            <div class="carousel-slide">
                <div class="carousel-blur"
                     style="background-image: url('<?= htmlspecialchars($cmovie['Poster_url']) ?>');">
                </div>
                <div class="carousel-content">
                    <div class="carousel-caption">
                        <div class="carousel-title"><?= htmlspecialchars($cmovie['Title']) ?> </div>
                        <div class="carousel-meta-row">
                            <span class="carousel-year"><?= htmlspecialchars($cmovie['Release_year']) ?></span>
                            <div class="carousel-separator">|</div>
                            <span class="carousel-rating">
                                <?= number_format($cmovie['avg_rating'] ?? 0, 1) ?>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                    </div>
                    <a href="view/movie_detail.php?id=<?= $cmovie['ID_Movies'] ?>" class="poster-link">
                        <img src="<?= htmlspecialchars($cmovie['Poster_url']) ?>" alt="<?= htmlspecialchars($cmovie['Title']) ?>" 
                             onerror="this.src='public/uploads/posters/e0d64153529554569b50be2e4dc40c87.jpg'">
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="filter-bar">
    <form method="get" class="filter-form">
        <select name="genre" class="filter-select">
            <option value="">All Genres</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['ID_Genre'] ?>" <?= ($_GET['genre'] ?? '') == $genre['ID_Genre'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($genre['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="rating" class="filter-select">
            <option value="">Rating</option>
            <option value="5" <?= ($_GET['rating'] ?? '') == '5' ? 'selected' : '' ?>>★★★★★</option>
            <option value="4" <?= ($_GET['rating'] ?? '') == '4' ? 'selected' : '' ?>>★★★★+</option>
            <option value="3" <?= ($_GET['rating'] ?? '') == '3' ? 'selected' : '' ?>>★★★+</option>
        </select>

        <input type="text" name="search" placeholder="Search movies…" class="search-input"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

        <button type="submit" class="btn-apply-filters">Search</button>

        <?php if (isset($_SESSION['user']) && $_SESSION['user']['Role'] === 'Admin'): ?>
            <a href="view/add_movies.php" class="btn-add-movie">
                <i class="fas fa-plus-circle"></i> Add Movie
            </a>
        <?php endif; ?>
    </form>
</section>

<main class="movie-grid-container">
    <?php if (empty($movies)): ?>
        <div class="no-movies">
            <i class="fas fa-film fa-5x"></i>
            <p> No movies found. Try different filters. </p>
        </div>
    <?php else: ?>
        <h4 class="movie-section-title">Movies</h4>
        <div class="movie-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <a href="view/movie_detail.php?id=<?= $movie['ID_Movies'] ?>" class="movie-link">
                        <div class="movie-poster-container">
                            <img src="<?= htmlspecialchars($movie['Poster_url']) ?>"
                                alt="<?= htmlspecialchars($movie['Title']) ?>"
                                class="movie-poster"
                                onerror="this.src='public/uploads/posters/e0d64153529554569b50be2e4dc40c87.jpg'">
                            <div class="movie-rating">
                                <?= number_format($movie['avg_rating'] ?? 0, 1) ?> <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['Title']) ?> </h3>
                            <p class="movie-meta">
                                <span class="movie-year"><?= htmlspecialchars($movie['Release_year']) ?> </span>
                                <span class="movie-genre"><?= htmlspecialchars($movie['genre_name']) ?> </span>
                            </p>
                        </div>
                    </a>

                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['Role'] === 'Admin'): ?>
                        <div class="movie-actions">
                            <a href="view/edit_movie.php?id=<?= $movie['ID_Movies'] ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="view/delete_movie.php?id=<?= $movie['ID_Movies'] ?>" class="btn-delete"
                                onclick="return confirm('Are you sure you want to delete this movie?')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">&laquo; First</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">&lsaquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; First</span>
                <span class="disabled">&lsaquo; Prev</span>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            if ($startPage > 1) {
                echo "<a href='?".http_build_query(array_merge($_GET, ['page' => 1]))."'>1</a>";
                if ($startPage > 2) echo "<span>...</span>";
            }
            for ($i = $startPage; $i <= $endPage; $i++):
                if ($i == $currentPage) : ?>
                    <span class="current"><?= $i ?> </span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif;
            endfor;

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) echo "<span>...</span>";
                echo "<a href='?".http_build_query(array_merge($_GET, ['page' => $totalPages]))."'>{$totalPages}</a>";
            }
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">Next &rsaquo;</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">Last &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &rsaquo;</span>
                <span class="disabled">Last &raquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<section class="about-us-section">
    <section class="about-us-section">
    <h2 class="about-us-logo">MOVLIX</h2>
    <p>Movlix adalah platform ulasan dan rating film nomor satu di Indonesia. Dengan Movlix, pengguna dapat memberikan ulasan, memberi peringkat, dan menemukan film yang sesuai selera. Movlix juga menyediakan informasi lengkap mengenai film, mulai dari sinopsis, tahun rilis, hingga trailer — sehingga kamu dapat mencari dan memilih film yang paling cocok untuk ditonton.</p
    </section>

    <div class="about-us-categories">
        <div class="about-us-item">
            <h3>Layanan Movlix</h3>
            <ul>
                <li>Reviews Film dan Series</li>
                <li>Pilihan Genre Lengkap</li>
                <li>Koleksi Film dan Series Terbaru</li>
                <li>Kualitas Gambar Tinggi</li>
            </ul>
        </div>

        <div class="about-us-item">
            <h3>Keuntungan Menggunakan Movlix</h3>
            <ul>
                <li>Tampilan Sederhana dan Mudah Digunakan</li>
                <li>Memberikan Pengalaman Review yang Mengasyikkan</li>
                <li>Dapat Mencari Rekomendasi Film yang Cocok</li>
            </ul>
        </div>

        <div class="about-us-item">
            <h3>Dukungan dan Kontak</h3>
            <ul>
                <li>Instagram: @movlix</li>
                <li>Twitter: @movlix</li>
                <li>Email: support@movlix.com</li>
                <li>Website: movlix.com</li>
            </ul>
        </div>
    </div>
</section>


<footer class="main-footer">
    <p>&copy; <?= date('Y') ?> MOVLIX. All rights reserved.</p>
</footer>

<script>
    function toggleDropdown(){
        document.getElementById('dropdown-profiles').classList.toggle('hidden');
    }

    document.addEventListener('click',(e)=>{
        const profileIcon = document.querySelector('.profile-icon');
        const profileMenu = document.getElementById('dropdown-profiles');
        if (profileIcon && !profileIcon.contains(e.target) && !profileMenu.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });
</script>

</body>
</html>
