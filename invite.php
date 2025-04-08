<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $email = trim($_POST['email']);
    $redirect_url = "group.php?id=" . $group_id;

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: $redirect_url&msg=" . urlencode("User not found."));
        exit;
    }

    $invited_user_id = $user['id'];

    // Check if already a member
    $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $invited_user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        header("Location: $redirect_url&msg=" . urlencode("User is already in the group."));
        exit;
    }

    // Add user
    $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->execute([$group_id, $invited_user_id]);

    header("Location: $redirect_url&msg=" . urlencode("User invited successfully!"));
    exit;
}
