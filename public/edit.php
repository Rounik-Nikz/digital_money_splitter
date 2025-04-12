<!DOCTYPE html>
<html>
<head>
    <title>Manage Group - <?php echo htmlspecialchars($group['name']); ?></title>
    <link rel="stylesheet" href="assets/css/manage_group.css">
</head>
<body>
    <div class="container">
        <h2 class="group-title">Group: <?php echo htmlspecialchars($group['name']); ?></h2>

        <section class="invite-section">
            <h3>Invite a User (by Email)</h3>
            <form method="POST" action="invite.php" class="invite-form">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <input type="email" name="email" placeholder="Enter user's email" required>
                <button type="submit">Invite</button>
            </form>
        </section>

        <section class="members-section">
            <h3>Members</h3>
            <ul class="member-list">
                <?php foreach ($members as $member): ?>
                    <li><?php echo htmlspecialchars($member['username']) . " (" . $member['email'] . ")"; ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <?php if (isset($_GET['msg'])): ?>
            <p class="message success"><?php echo htmlspecialchars($_GET['msg']); ?></p>
        <?php endif; ?>

        <section class="expenses-section">
            <h3>Expenses</h3>
            <ul class="expense-list">
                <?php foreach ($expenses as $expense): ?>
                    <li>
                        <?php echo htmlspecialchars($expense['username']); ?> paid ₹<?php echo $expense['amount']; ?> 
                        for "<?php echo htmlspecialchars($expense['title']); ?>" 
                        <small>(<?php echo $expense['created_at']; ?>)</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="add-expense-section">
            <h3>Add Expense</h3>
            <form method="POST" action="add_expense.php" class="expense-form">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required>
                <input type="text" name="title" placeholder="Title (e.g. Dinner, Ola Ride)" required>
                <button type="submit">Add Expense</button>
            </form> 
        </section>

        <section class="group-expenses">
            <h3>Group Expenses</h3>
            <?php
            if ($expenses) {
                echo "<ul class='expense-list'>";
                foreach ($expenses as $expense) {
                    echo "<li><strong>" . htmlspecialchars($expense['title']) . "</strong> - ₹" . $expense['amount'] . 
                         " paid by <em>" . htmlspecialchars($expense['paid_by_name']) . "</em> on " . 
                         date("M d, Y", strtotime($expense['created_at']));
                    if ($user_id == $expense['paid_by'] || $user_id == $group['created_by']) {
                        echo "
                            <form method='POST' action='delete_expense.php' style='display:inline;' class='inline-form'>
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
            <ul class="balance-list">
                <?php
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
                ?>
            </ul>
        </section>

        <section class="settle-section">
            <h3>Settle Balance</h3>
            <form method="POST" action="settle_balance.php" class="settle-form">
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
                <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required>
                <button type="submit">Settle</button>
            </form>
        </section>

        <section class="settlements">
            <h3>Settled Transactions</h3>
            <?php
            if ($settlements) {
                echo "<ul class='settlement-list'>";
                foreach ($settlements as $s) {
                    echo "<li>" . htmlspecialchars($s['from_user']) . " paid ₹" . $s['amount'] . 
                         " to " . htmlspecialchars($s['to_user']) . " on " . date("M d, Y", strtotime($s['created_at'])) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No settlements yet.</p>";
            }
            ?>
        </section>

        <p><a href="dashboard.php" class="back-link">← Back to Dashboard</a></p>
    </div>
</body>
</html>
