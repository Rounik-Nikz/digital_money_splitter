<?php
require_once 'config.php';

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validations
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if user already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = "Email or Username already taken.";
        }
    }

    // Insert new user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            $success = "Account created successfully. You can now <a href='login.php'>login</a>.";
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<!-- HTML Part -->
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="./assests/css/register.css">
</head>
<body>
    <div class="container">
        <h2 class="title">Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <p><?= $success ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="register-form">
            <input type="text" name="username" placeholder="Username" required class="form-input">
            <input type="email" name="email" placeholder="Email" required class="form-input">
            <input type="password" name="password" placeholder="Password" required class="form-input">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required class="form-input">
            <button type="submit" class="form-button">Register</button>
        </form>

        <p class="login-link">Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>
