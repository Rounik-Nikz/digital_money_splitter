<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $to_user_id = $_POST['to_user_id'];
    $amount = floatval($_POST['amount']);

    if ($amount <= 0 || !$group_id || !$to_user_id) {
        die("Invalid input.");
    }

    // Check if both users (sender and receiver) are part of the group (either in group_members or as the creator)
    $stmt = $pdo->prepare("
        SELECT user_id FROM group_members WHERE group_id = ?
        UNION
        SELECT created_by AS user_id FROM groups WHERE id = ?
    ");
    $stmt->execute([$group_id, $group_id]);
    $group_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array($user_id, $group_users) || !in_array($to_user_id, $group_users)) {
        die("Both users must be in the group.");
    }

    // Record settlement
    $stmt = $pdo->prepare("
        INSERT INTO settlements (group_id, from_user_id, to_user_id, amount)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$group_id, $user_id, $to_user_id, $amount]);

    header("Location: group.php?id=$group_id&msg=" . urlencode("Settlement recorded!"));
    exit;
}
?>
