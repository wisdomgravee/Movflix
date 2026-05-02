<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = "Username dan password wajib diisi!";
    } else {

        $username = htmlspecialchars(trim($_POST['username']));
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user'] = $user;

            echo "<script>alert('Login berhasil!'); window.location='../index.php';</script>";
            exit;

        } else {
            $error = "Username atau password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MovLix</title>
    <link rel="stylesheet" href="../public/css/style.css">

    <style>
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body class="login-body">
    <div class="login-box">
        <h2>Log In</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <a href="../index.php" class="back-button">⬅ Back</a>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>

            <input type="password" id="password" name="password" placeholder="Password" required>
            <label>
                <input type="checkbox" onclick="togglePassword()"> Show Password
            </label>

            <p>Don't have an account? <a href="register.php">Sign up here</a></p>

            <button type="submit">Log In</button>
        </form>
    </div>

    <script>
        function togglePassword() {
            var x = document.getElementById("password");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>