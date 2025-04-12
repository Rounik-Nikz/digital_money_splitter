<?php
require_once(__DIR__ . '/../config/config.php');

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

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">

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

  <!-- Main Container -->
  <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 space-y-6 transition-colors duration-300">
    
    <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white">Create Account</h2>

    <!-- Error Messages -->
    <div class="error-messages">
      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded-lg text-sm mb-4 dark:bg-red-200 dark:text-red-900">
          <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Success Message -->
    <div class="success-message">
    <?php if (isset($success) && !empty(trim($success))): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg text-sm mb-4 dark:bg-green-200 dark:text-green-900">
    <?php echo $success; ?>
    </div>
    <?php endif; ?>
    </div>

    <!-- Registration Form -->
    <form class="register-form space-y-4" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <input type="text" name="username" placeholder="Username" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <input type="email" name="email" placeholder="Email" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <input type="password" name="confirm_password" placeholder="Confirm Password" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

      <button type="submit" name="register" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200 shadow-sm">
        Register
      </button>
    </form>

    <p class="text-sm text-center text-gray-600 dark:text-gray-300">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline dark:text-blue-400">Login here</a>
    </p>
  </div>

  <!-- Dark Mode Script -->
  <script src="../assets/js/register.js"></script>

</body>
</html>
