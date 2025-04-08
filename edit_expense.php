<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !isset($_GET['group_id'])) {
    die("Expense or group ID missing.");
}

$expense_id = $_GET['id'];
$group_id = $_GET['group_id'];

// Fetch expense info
$stmt = $pdo->prepare("
    SELECT e.*, g.created_by 
    FROM expenses e 
    JOIN groups g ON e.group_id = g.id 
    WHERE e.id = ? AND e.group_id = ?
");
$stmt->execute([$expense_id, $group_id]);
$expense = $stmt->fetch();

if (!$expense || ($expense['paid_by'] != $user_id && $expense['created_by'] != $user_id)) {
    die("You are not allowed to edit this expense.");
}

// On form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $amount = floatval($_POST['amount']);

    if ($title === '' || $amount <= 0) {
        die("Invalid input.");
    }

    // Update the expense
    $stmt = $pdo->prepare("UPDATE expenses SET title = ?, amount = ? WHERE id = ?");
    $stmt->execute([$title, $amount, $expense_id]);

    header("Location: group.php?id=" . $group_id . "&msg=" . urlencode("Expense updated successfully."));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Expense</title>
</head>
<body>
    <h2>Edit Expense</h2>
    <form method="POST">
        <label>Title:</label><br>
        <input type="text" name="title" value="<?php echo htmlspecialchars($expense['title']); ?>" required><br><br>

        <label>Amount (₹):</label><br>
        <input type="number" step="0.01" name="amount" value="<?php echo $expense['amount']; ?>" required><br><br>

        <button type="submit">Update</button>
    </form>

    <p><a href="group.php?id=<?php echo $group_id; ?>">← Back to Group</a></p>
</body>
</html>
