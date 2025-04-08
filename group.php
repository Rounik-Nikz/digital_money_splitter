<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Group ID not provided.");
}

$group_id = $_GET['id'];

// Fetch group info and validate ownership
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

$is_creator = $group['created_by'] == $user_id;

$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
$is_member = $stmt->fetch() ? true : false;

if (!$is_creator && !$is_member) {
    die("You do not have access to this group.");
}

// Fetch current members (including their IDs)
$stmt = $pdo->prepare("SELECT u.id, u.username, u.email FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ?");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll();

// Add group creator if not in members list
$creator_stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
$creator_stmt->execute([$group['created_by']]);
$creator = $creator_stmt->fetch();

$found_creator = false;
foreach ($members as $m) {
    if ($m['id'] == $creator['id']) {
        $found_creator = true;
        break;
    }
}
if (!$found_creator) $members[] = $creator;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Group - <?php echo htmlspecialchars($group['name']); ?></title>
    <link rel="stylesheet" href="./assests/css/manage_group.css">
</head>

<body>
    <div class="container">
        <h2 class="group-title">Group: <?php echo htmlspecialchars($group['name']); ?></h2>

            <section class="invite-section">
                <h3>Invite a User (by Email)</h3>
                <form method="POST" action="invite.php">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <input type="email" name="email" placeholder="Enter user's email" required>
                    <button type="submit">Invite</button>
                </form>
            </section>

            <section class="members-section">
                
                <h3>Members</h3>
                <div>
                <?php if (isset($_GET['msg'])): ?>
                    <p style="color: green;" class="message success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
                <?php endif; ?>
                </div>

                <ul class="members-section">
                    <?php foreach ($members as $member): ?>
                        <li><?php echo htmlspecialchars($member['username']) . " (" . $member['email'] . ")"; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Group Creator:</strong> <?php echo htmlspecialchars($creator['username']); ?></p>
                <p><strong>Group Created On:</strong> <?php echo date("M d, Y", strtotime($group['created_at'])); ?></p>
                

            </section>

            

            <section class="expenses-section">
                <h3>Expenses</h3>
                <?php
                $stmt = $pdo->prepare("
                    SELECT e.*, u.username 
                    FROM expenses e 
                    JOIN users u ON e.paid_by = u.id 
                    WHERE e.group_id = ?
                    ORDER BY e.created_at DESC
                ");
                $stmt->execute([$group_id]);
                $expenses = $stmt->fetchAll();
                ?>
            </section>

            <ul class="expense-list">
                <?php foreach ($expenses as $expense): ?>
                    <li>
                        <?php echo htmlspecialchars($expense['username']); ?> paid ₹<?php echo $expense['amount']; ?> for "<?php echo htmlspecialchars($expense['title']); ?>"
                        <small>(<?php echo $expense['created_at']; ?>)</small>
                    </li>
                <?php endforeach; ?>
            </ul>

            <section class="add-expense-section">
                <h3>Add Expense</h3>
                <form method="POST" action="add_expense.php">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required>
                    <input type="text" name="title" placeholder="Title (e.g. Dinner, Ola Ride)" required>
                    <button type="submit">Add Expense</button>
                </form>
            </section>

            <section class="group-expenses">
                <h3>Group Expenses</h3>
                <?php
                $stmt = $pdo->prepare("
                    SELECT e.id, e.title, e.amount, e.created_at, e.paid_by, u.username AS paid_by_name
                    FROM expenses e
                    JOIN users u ON e.paid_by = u.id
                    WHERE e.group_id = ?
                    ORDER BY e.created_at DESC
                ");
                $stmt->execute([$group_id]);
                $expenses = $stmt->fetchAll();

                if ($expenses) {
                    echo "<ul>";
                    foreach ($expenses as $expense) {
                        echo "<li><strong>" . htmlspecialchars($expense['title']) . "</strong> - ₹" . $expense['amount'] . " paid by <em>" . htmlspecialchars($expense['paid_by_name']) . "</em> on " . date("M d, Y", strtotime($expense['created_at']));
                        if ($user_id == $expense['paid_by'] || $user_id == $group['created_by']) {
                            echo "
                            <form method='POST' action='delete_expense.php' style='display:inline;'>
                                <input type='hidden' name='expense_id' value='" . $expense['id'] . "'>
                                <input type='hidden' name='group_id' value='" . $group_id . "'>
                                <button type='submit' onclick='return confirm(\"Delete this expense?\")'>Delete</button>
                            </form>
                            <a href='edit_expense.php?id=" . $expense['id'] . "&group_id=" . $group_id . "'>Edit</a>
                        ";
                        }
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No expenses added yet.</p>";
                }
                ?>
            </section>

            <section class="balance-section">
                <h3>Group Balances</h3>
                <?php
                $stmt = $pdo->prepare("
                    SELECT 
                        u.username,
                        u.id AS user_id,
                        COALESCE(SUM(CASE WHEN e.paid_by = u.id THEN e.amount ELSE 0 END), 0) AS total_paid,
                        COALESCE(SUM(sh.share_amount), 0) AS total_owed,
                        COALESCE(SUM(CASE WHEN se.from_user_id = u.id THEN se.amount ELSE 0 END), 0) AS settled_paid,
                        COALESCE(SUM(CASE WHEN se.to_user_id = u.id THEN se.amount ELSE 0 END), 0) AS settled_received
                    FROM
                        (SELECT user_id FROM group_members WHERE group_id = ?
                         UNION SELECT created_by AS user_id FROM groups WHERE id = ?) gm
                    JOIN users u ON u.id = gm.user_id
                    LEFT JOIN expenses e ON e.group_id = ? AND e.paid_by = u.id
                    LEFT JOIN expense_shares sh ON sh.expense_id IN (SELECT id FROM expenses WHERE group_id = ?) AND sh.user_id = u.id
                    LEFT JOIN settlements se ON se.group_id = ? AND (se.from_user_id = u.id OR se.to_user_id = u.id)
                    GROUP BY u.id
                    ");
                $stmt->execute([$group_id, $group_id, $group_id, $group_id, $group_id]);
                $balances = $stmt->fetchAll();


                echo "<ul>";
                foreach ($balances as $b) {
                    $net = round(($b['total_paid'] - $b['total_owed']) + ($b['settled_received'] - $b['settled_paid']), 2);
                    echo "<li><strong>" . htmlspecialchars($b['username']) . "</strong>: ";
                    if ($net > 0) {
                        echo "gets ₹$net";
                    } elseif ($net < 0) {
                        echo "owes ₹" . abs($net);
                    } else {
                        echo "is settled";
                    }
                    echo "</li>";
                }
                echo "</ul>";
                ?>
            </section>

            <section class="settle-section">
                <h3>Settle Balance</h3>
                <form method="POST" action="settle_balance.php">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <label>To (select member):</label>
                    <select name="to_user_id" required>
                        <?php foreach ($members as $member):
                            if ($member['id'] == $user_id) continue; ?>
                            <option value="<?php echo htmlspecialchars($member['id']); ?>">
                                <?php echo htmlspecialchars($member['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required>
                    <button type="submit">Settle</button>
                </form>
            </section>

            <section class="settlements">
                <h3>Settled Transactions</h3>
                <?php
                $stmt = $pdo->prepare("
                    SELECT s.amount, s.created_at, 
                           u1.username AS from_user, 
                           u2.username AS to_user
                    FROM settlements s
                    JOIN users u1 ON s.from_user_id = u1.id
                    JOIN users u2 ON s.to_user_id = u2.id
                    WHERE s.group_id = ?
                    ORDER BY s.created_at DESC
                ");
                $stmt->execute([$group_id]);
                $settlements = $stmt->fetchAll();

                if ($settlements) {
                    echo "<ul>";
                    foreach ($settlements as $s) {
                        echo "<li>" . htmlspecialchars($s['from_user']) . " paid ₹" . $s['amount'] . " to " . htmlspecialchars($s['to_user']) . " on " . date("M d, Y", strtotime($s['created_at'])) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No settlements yet.</p>";
                }
                ?>
            </section>

            <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>

</body>

</html>