<?php
session_start();
require_once 'config.php';

// Set default response type
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    $user_id = $_SESSION['user_id'];

    // Get IDs from either GET (for regular requests) or POST (for AJAX)
    $expense_id = $_REQUEST['expense_id'] ?? $_REQUEST['id'] ?? null;
    $group_id = $_REQUEST['group_id'] ?? null;

    if (!$expense_id || !$group_id) {
        throw new Exception('Expense or group ID missing');
    }

    // Fetch expense info with permission check
    $stmt = $pdo->prepare("
        SELECT e.*, g.created_by 
        FROM expenses e 
        JOIN groups g ON e.group_id = g.id 
        WHERE e.id = ? AND e.group_id = ?
    ");
    $stmt->execute([$expense_id, $group_id]);
    $expense = $stmt->fetch();

    if (!$expense) {
        throw new Exception('Expense not found');
    }

    if ($expense['paid_by'] != $user_id && $expense['created_by'] != $user_id) {
        throw new Exception('You are not allowed to edit this expense');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if (empty($title)) {
            throw new Exception('Title is required');
        }

        if ($amount <= 0) {
            throw new Exception('Amount must be positive');
        }

        // Update the expense
        $stmt = $pdo->prepare("UPDATE expenses SET title = ?, amount = ? WHERE id = ?");
        $stmt->execute([$title, $amount, $expense_id]);

        $response = [
            'success' => true,
            'message' => 'Expense updated successfully'
        ];

        // For non-AJAX requests, redirect
        if (!$isAjax) {
            header("Location: group.php?id=" . $group_id . "&msg=" . urlencode($response['message']));
            exit;
        }
    } elseif (!$isAjax) {
        // Display the edit form for non-AJAX requests
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Edit Expense</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 p-6">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4">Edit Expense</h2>
                <form method="POST">
                    <input type="hidden" name="expense_id" value="<?php echo $expense_id; ?>">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Title:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($expense['title']); ?>" 
                               class="w-full px-3 py-2 border rounded" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Amount (₹):</label>
                        <input type="number" step="0.01" name="amount" value="<?php echo $expense['amount']; ?>" 
                               class="w-full px-3 py-2 border rounded" required>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Update
                        </button>
                        <a href="group.php?id=<?php echo $group_id; ?>" class="px-4 py-2 border rounded hover:bg-gray-100">
                            ← Back to Group
                        </a>
                    </div>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} catch (Exception $e) {
    $response = [
        'success' => true,
        'message' => $e->getMessage()
    ];

    if (!$isAjax) {
        die($e->getMessage());
    }
}

// For AJAX requests, return JSON response
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}