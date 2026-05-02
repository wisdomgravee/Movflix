<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['username']) ||
        empty($_POST['email']) ||
        empty($_POST['password'])
    ) {
        $error = "Semua field wajib diisi!";
    } else {

        $username = htmlspecialchars(trim($_POST['username']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $role = ucfirst(strtolower($_POST['role']));

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid!";
        }

        // Validasi password
        elseif (strlen($password) < 6) {
            $error = "Password minimal 6 karakter!";
        }

        // Validasi konfirmasi password
        elseif ($password !== $confirm) {
            $error = "Konfirmasi password tidak cocok!";
        }

        // Validasi role
        elseif (!in_array($role, ['Admin', 'User'])) {
            $error = "Role tidak valid.";
        }

        else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Cek user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ? OR Email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $error = "Username atau email sudah terdaftar.";
            } else {

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (Username, Email, Password, Role) VALUES (?, ?, ?, ?)");

                if ($stmt->execute([$username, $email, $hashedPassword, $role])) {

                    echo "<script>alert('Registrasi berhasil!'); window.location='login.php';</script>";
                    exit;

                } else {
                    $error = "Registrasi gagal. Silakan coba lagi.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - MovLix</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>Register</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <a href="../index.php" class="back-button">⬅ Back</a>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required><br>

            <input type="email" name="email" placeholder="Email" required><br>

            <input type="password" id="password" name="password" placeholder="Password" required><br>

            <input type="password" id="confirm" name="confirm_password" placeholder="Confirm Password" required><br>

            <label>
                <input type="checkbox" onclick="togglePassword()"> Show Password
            </label><br><br>

            <select name="role" required>
                <option value="user">User</option>
            </select><br><br>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here!</a></p>
    </div>

    <script>
        function togglePassword() {
            let p1 = document.getElementById("password");
            let p2 = document.getElementById("confirm");

            p1.type = p1.type === "password" ? "text" : "password";
            p2.type = p2.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>