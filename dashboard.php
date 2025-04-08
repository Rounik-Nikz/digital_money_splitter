<?php
session_start();


require_once 'config.php';

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
<html>
<head>
    <title>Dashboard - Money Splitter</title>
    <link rel="stylesheet" href="./assests/css/dashboard.css">
</head>
<body>

<div class="container">
    <a href="logout.php" class="logout">Logout</a>
    <h2 class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <p class="user-email">Your email: <?php echo htmlspecialchars($email); ?></p>

    <hr>

    <h3 class="section-title">Create a New Group</h3>

    <?php if (isset($_GET['success'])): ?>
        <p class="success-message">✅ Group created successfully!</p>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
        <p class="error-message">⚠️ Group name cannot be empty.</p>
    <?php endif; ?>

    <form class="group-form" action="create_group.php" method="post">
        <input type="text" name="group_name" placeholder="Enter group name" required class="form-input">
        <input type="submit" value="Create Group" class="form-button">
    </form>

    <hr>

    <h3 class="section-title">Your Groups</h3>
    <?php if (count($groups) > 0): ?>
        <ul class="group-list">
        <?php foreach ($groups as $group): ?>
            <li class="group-item">
                <strong><?php echo htmlspecialchars($group['name']); ?></strong><br>
                <small class="group-date">Created on <?php echo date('d M Y', strtotime($group['created_at'])); ?></small>
                <a href="group.php?id=<?php echo $group['id']; ?>" class="manage-link">Manage</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="no-groups-message">You haven't created any groups yet.</p>
    <?php endif; ?>

    <hr>

    <h3 class="section-title">Groups You’re Invited To</h3>
    <?php if (count($joined_groups) > 0): ?>
        <ul class="invited-group-list">
            <?php foreach ($joined_groups as $group): ?>
                <li class="invited-group-item">
                    <strong><?php echo htmlspecialchars($group['name']); ?></strong> 
                    <a href="group.php?id=<?php echo $group['id']; ?>" class="view-link">View</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="no-invited-groups-message">You haven’t been invited to any groups yet.</p>
    <?php endif; ?>
</div>

</body>
</html>