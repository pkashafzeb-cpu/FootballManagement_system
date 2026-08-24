<?php
require_once 'config.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($username === "" || $password === "" || $confirm_password === "") {
        $error = "Please fill in all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose a different one.";
        } else {
            // Hash the password with SHA-256 (matching the existing system)
            $hashed_password = hash('sha256', $password);

            $stmt = $conn->prepare("INSERT INTO Users (Username, Password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
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
    <title>Register | Pakistan Football Tournament Manager</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-container">
        <img src="assets/logo.png" alt="Logo" class="logo-center">
        <h2>Create Account</h2>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" class="input-field" placeholder="Choose a Username" required minlength="3" autofocus>
            <input type="password" name="password" class="input-field" placeholder="Create Password (min 6 chars)" required minlength="6">
            <input type="password" name="confirm_password" class="input-field" placeholder="Confirm Password" required minlength="6">
            <button type="submit" name="register" class="login-btn">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

</body>
</html>
