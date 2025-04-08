<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$expense_id = $_POST['expense_id'];
$group_id = $_POST['group_id'];
$user_id = $_SESSION['user_id'];

// Check if user is allowed
$stmt = $pdo->prepare("SELECT e.paid_by, g.created_by FROM expenses e 
    JOIN groups g ON e.group_id = g.id 
    WHERE e.id = ?");
$stmt->execute([$expense_id]);
$data = $stmt->fetch();

if (!$data || ($data['paid_by'] != $user_id && $data['created_by'] != $user_id)) {
    die("You are not allowed to delete this expense.");
}

// Delete (will auto-remove from expense_shares)
$stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);

header("Location: group.php?id=$group_id&msg=" . urlencode("Expense deleted."));
exit;
