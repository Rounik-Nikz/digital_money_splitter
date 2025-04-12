<?php
session_start();

require_once(__DIR__ . '/../config/config.php');

// Restore session from cookies if not set
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
    $_SESSION['email'] = $_COOKIE['email'];
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user info from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];



$user_id = $_SESSION['user_id'];

// Fetch groups created by the user
$stmt = $pdo->prepare("SELECT * FROM groups WHERE created_by = ?");
$stmt->execute([$user_id]);
$groups = $stmt->fetchAll();
// if ($groups) {
//     foreach ($groups as $group) {
//         echo "<p>Group Name: " . htmlspecialchars($group['name']) . "</p>";
//     }
// } else {
//     echo "<p>No groups created yet.</p>";
// }



// 2. Groups the user is a member of (excluding ones they created)
$stmt = $pdo->prepare("
    SELECT g.* FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ? AND g.created_by != ?
");
$stmt->execute([$user_id, $user_id]);
$joined_groups = $stmt->fetchAll();


?>

<!DOCTYPE html>
<html lang="en" class="transition duration-300">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Money Splitter</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
    }
  </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition duration-300">

  <!-- Header -->
  <header class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 shadow-md md:ml-64 fixed top-0 left-0 right-0 z-20">
  <!-- Hamburger (only visible on small screens) -->
  <button id="hamburger" class="text-gray-800 dark:text-white text-2xl md:hidden">
    ☰
  </button>

  <!-- Logo -->
  <div class="text-2xl font-bold text-lg text-indigo-600 dark:text-indigo-400">Money Splitter</div>

  <!-- Dark Mode Toggle -->
  <button id="darkToggle" class="text-lg px-3 py-1 border rounded dark:border-gray-300">
    🌓
  </button>
</header>

  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white dark:bg-gray-800 w-64 p-4 shadow-md md:translate-x-0 transform -translate-x-full fixed md:relative top-0 left-0 h-full z-40 transition-transform duration-300 ease-in-out">
      <div class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 mb-6 hidden md:block">Menu</div>
      <nav class="space-y-2">
        <button class="nav-link active w-full text-left px-4 py-2 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-700" data-section="create">
          🏠 Create Group
        </button>
        <button class="nav-link w-full text-left px-4 py-2 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-700" data-section="groups">
          👥 Your Groups
        </button>
        <button class="nav-link w-full text-left px-4 py-2 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-700" data-section="invited">
          📩 Invited Groups
        </button>
        <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-800 rounded-lg mt-8">
          🚪 Logout
        </a>
        <!-- <div class="mt-6 md:hidden">
          <button id="darkToggleMobile" class="text-sm px-3 py-1 border rounded dark:border-gray-300 w-full">
            🌓 Toggle Dark Mode
          </button>
        </div> -->
      </nav>
    </aside>

    <!-- Overlay for mobile when sidebar is open -->
    <div id="overlay" class="fixed inset-0 bg-black opacity-40 hidden z-30 md:hidden"></div>

    <!-- Main Content -->
    <main class="flex-1 p-6 overflow-y-auto pt-20 md:ml-55">
      <!-- Create Group -->
      <section id="section-create" class="section">
        <h2 class="text-2xl font-semibold mb-4">👋 Welcome, <?php echo htmlspecialchars($username); ?></h2>
        <p class="mb-4 text-gray-600 dark:text-gray-300">Your email: <?php echo htmlspecialchars($email); ?></p>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
          <h3 class="text-xl font-medium mb-2">Create a New Group</h3>
          <?php if (isset($_GET['success'])): ?>
              <p class="text-green-600">✅ Group created successfully!</p>
          <?php elseif (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
              <p class="text-red-600">⚠️ Group name cannot be empty.</p>
          <?php endif; ?>
          <form action="create_group.php" method="POST" class="mt-4 space-y-3">
            <input type="text" name="group_name" placeholder="Group name" required class="w-full border dark:border-gray-600 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:bg-gray-700 dark:text-white" />
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
              Create Group
            </button>
          </form>
        </div>
      </section>

      <!-- Your Groups -->
      <section id="section-groups" class="section hidden">
        <h3 class="text-2xl font-medium mb-4">📂 Your Groups</h3>
        <?php if (count($groups) > 0): ?>
          <ul class="space-y-4">
            <?php foreach ($groups as $group): ?>
              <li class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="flex justify-between items-center">
                  <div>
                    <strong class="text-lg"><?php echo htmlspecialchars($group['name']); ?></strong>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Created on <?php echo date('d M Y', strtotime($group['created_at'])); ?></p>
                  </div>
                  <a href="group.php?id=<?php echo $group['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline">Manage</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-gray-600 dark:text-gray-400">You haven't created any groups yet.</p>
        <?php endif; ?>
      </section>

      <!-- Invited Groups -->
      <section id="section-invited" class="section hidden">
        <h3 class="text-2xl font-medium mb-4">📨 Groups You’re Invited To</h3>
        <?php if (count($joined_groups) > 0): ?>
          <ul class="space-y-4">
            <?php foreach ($joined_groups as $group): ?>
              <li class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow flex justify-between items-center">
                <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                <a href="group.php?id=<?php echo $group['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline">View</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-gray-600 dark:text-gray-400">You haven’t been invited to any groups yet.</p>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <!-- JS for interactions -->
  <script src="../assets/js/dashboard.js"></script>


</body>
</html>
