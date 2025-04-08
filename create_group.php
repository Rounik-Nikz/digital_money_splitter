<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST['group_name']);

    if (!empty($group_name)) {
        $created_by = $_SESSION['user_id'];

        $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (?, ?)");
        $stmt->execute([$group_name, $created_by]);

        // Redirect back to dashboard
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        header("Location: dashboard.php?error=empty");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
