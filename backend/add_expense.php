<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $amount = $_POST['amount'];
    $title = trim($_POST['title']);
    $paid_by = $_SESSION['user_id'];

    // Insert expense
    $stmt = $pdo->prepare("INSERT INTO expenses (group_id, paid_by, amount, title) VALUES (?, ?, ?, ?)");
    $stmt->execute([$group_id, $paid_by, $amount, $title]);
    $expense_id = $pdo->lastInsertId();

    // Fetch all group members
    $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Also include the group creator if not already present
    $stmt = $pdo->prepare("SELECT created_by FROM groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $creator_id = $stmt->fetchColumn();
    if (!in_array($creator_id, $members)) {
        $members[] = $creator_id;
    }

    $split_amount = round($amount / count($members), 2);

    // Insert into expense_shares
    $stmt = $pdo->prepare("INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)");
    foreach ($members as $member_id) {
        $stmt->execute([$expense_id, $member_id, $split_amount]);
    }

    header("Location: group.php?id=$group_id&msg=" . urlencode("Expense added and split successfully."));
    exit;
}
