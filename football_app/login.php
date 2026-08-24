<?php
require_once 'config.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === "" || $password === "") {
        $error = "Please enter both username and password.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password (supports both SHA-256 hashed and plain text)
            $hashed_input = hash('sha256', $password);
            if ($user['Password'] === $hashed_input || $user['Password'] === $password) {
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $user['UserID'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid Username or Password!";
            }
        } else {
            $error = "Invalid Username or Password!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-container">
        <img src="assets/logo.png" alt="Logo" class="logo-center">
        <h2>Welcome Back</h2>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" class="input-field" placeholder="Username" required autofocus>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" name="login" class="login-btn">Login</button>
        </form>

        <div class="login-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

</body>
</html>
