<?php
session_start();
require_once(__DIR__ . '/../config/config.php');

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            setcookie("user_id", $user['id'], time() + (86400 * 7), "/");
            setcookie("username", $user['username'], time() + (86400 * 7), "/");
            setcookie("email", $user['email'], time() + (86400 * 7), "/");

            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen flex items-center justify-center px-4 transition-colors duration-300">

  <!-- Dark Mode Toggle -->
  <div class="absolute top-4 right-4">
    <label class="flex items-center cursor-pointer">
      <input type="checkbox" id="dark-toggle" class="sr-only">
      <div class="w-10 h-5 bg-gray-300 rounded-full shadow-inner dark:bg-gray-600 relative">
        <div class="dot absolute left-0 top-0 w-5 h-5 bg-white rounded-full shadow transition transform dark:translate-x-full">
          <i class="fas fa-moon text-gray-700 dark:text-yellow-300 text-xs absolute inset-0 flex items-center justify-center"></i>
        </div>
      </div>
      <span class="ml-3 text-sm">Dark Mode</span>
    </label>
  </div>

  <!-- Login Form -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Login to Your Account</h2>

    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
      <input type="email" name="email" placeholder="Email"
        class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <input type="password" name="password" placeholder="Password" id="password-input"
        class="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <div class="flex items-center justify-between">
        <label class="text-sm flex items-center">
          <input type="checkbox" id="show-password" class="mr-2">
          Show Password
        </label>
      </div>

      <button type="submit"
        class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition font-medium">
        Login
      </button>
    </form>

    <p class="text-sm text-center mt-4">Don’t have an account?
      <a href="register.php" class="text-blue-500 hover:underline">Register here</a>.
    </p>
  </div>

  <script src="../assets/js/login.js"></script>

</body>
</html>
