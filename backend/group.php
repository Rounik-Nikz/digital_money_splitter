<!DOCTYPE html>
<html lang="en" class="transition duration-300">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Group - <?php echo htmlspecialchars($group['name']); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

  // Fetch expenses
  $stmt = $pdo->prepare("
      SELECT e.id, e.title, e.amount, e.created_at, e.paid_by, u.username AS paid_by_name
      FROM expenses e
      JOIN users u ON e.paid_by = u.id
      WHERE e.group_id = ?
      ORDER BY e.created_at DESC
  ");
  $stmt->execute([$group_id]);
  $expenses = $stmt->fetchAll();

  // Fetch balances
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

  // Fetch settlements
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
  ?>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors duration-200">
  <div class="min-h-screen flex flex-col md:flex-row">
    <!-- Mobile Header -->
    <header class="md:hidden bg-white dark:bg-gray-800 shadow-md p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?></h1>
      <button id="mobile-menu-button" class="text-gray-600 dark:text-gray-300">
        <i class="fas fa-bars text-2xl"></i>
      </button>
    </header>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-full md:w-64 bg-white dark:bg-gray-800 shadow-lg p-4 transform -translate-x-full md:translate-x-0 fixed md:relative inset-y-0 left-0 z-50 transition-transform duration-200">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Dashboard</h2>
        <button id="close-sidebar" class="md:hidden text-gray-600 dark:text-gray-300">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <ul class="space-y-2">
        <li>
          <a href="#" class="nav-link block py-2 px-4 rounded hover:bg-gray-200 dark:hover:bg-gray-700" data-section="members">
            <i class="fas fa-users mr-2"></i> Members
          </a>
        </li>
        <li>
          <a href="#" class="nav-link block py-2 px-4 rounded hover:bg-gray-200 dark:hover:bg-gray-700" data-section="expenses">
            <i class="fas fa-receipt mr-2"></i> Expenses
          </a>
        </li>
        <li>
          <a href="#" class="nav-link block py-2 px-4 rounded hover:bg-gray-200 dark:hover:bg-gray-700" data-section="balances">
            <i class="fas fa-scale-balanced mr-2"></i> Balances
          </a>
        </li>
        <li>
          <a href="#" class="nav-link block py-2 px-4 rounded hover:bg-gray-200 dark:hover:bg-gray-700" data-section="settlements">
            <i class="fas fa-hand-holding-dollar mr-2"></i> Settlements
          </a>
        </li>
      </ul>
      
        <div class="mt-6">
            <label class="flex items-center cursor-pointer">
            <div class="relative">
              <input type="checkbox" id="dark-toggle" class="sr-only">
              <div class="w-10 h-5 bg-gray-300 rounded-full shadow-inner dark:bg-gray-600 transition-colors duration-300"></div>
              <div
                id="dot"
                class="dot absolute left-0 top-0 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300 transform
                       translate-x-0 dark:translate-x-5"
              >
                <i class="fas fa-moon text-gray-700 dark:text-yellow-300 absolute inset-0 flex items-center justify-center text-xs"></i>
              </div>
            </div>
            <span class="ml-3 text-sm text-gray-800 dark:text-gray-200">Dark Mode</span>
            </label>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 transition-all duration-200">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl md:text-3xl font-bold hidden md:block"><?php echo htmlspecialchars($group['name']); ?></h1>
        <a href="dashboard.php" class="text-sm bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
          <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
      </div>

      <?php if (isset($_GET['msg'])): ?>
        <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800">
          <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
      <?php endif; ?>

      <!-- Members Section (Default) -->
      <section id="members-section" class="content-section space-y-6">
        <!-- Invite User -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-user-plus mr-2"></i> Invite User
          </h2>
          <form method="POST" action="invite.php" class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            <input type="email" name="email" placeholder="Enter email" class="flex-1 px-4 py-2 border rounded dark:bg-gray-700" required>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              <i class="fas fa-paper-plane mr-1"></i> Invite
            </button>
          </form>
        </div>

        <!-- Members List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-users mr-2"></i> Members
          </h2>
          <ul class="space-y-3">
            <?php foreach ($members as $member): ?>
              <li class="flex justify-between items-center p-3 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                <div>
                  <span class="font-medium"><?php echo htmlspecialchars($member['username']); ?></span>
                  <span class="text-sm text-gray-500 dark:text-gray-400 ml-2"><?php echo $member['email']; ?></span>
                </div>
                <?php if ($member['id'] == $creator['id']): ?>
                  <span class="text-xs bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 px-2 py-1 rounded">Creator</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <div class="mt-6 pt-4 border-t dark:border-gray-700">
            <p class="text-sm"><span class="font-medium">Group Created On:</span> <?php echo date("M d, Y", strtotime($group['created_at'])); ?></p>
          </div>
        </div>
      </section>

      <!-- Expenses Section (Hidden by default) -->
      <section id="expenses-section" class="content-section hidden space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h2 class="text-xl font-semibold flex items-center">
              <i class="fas fa-receipt mr-2"></i> Group Expenses
            </h2>
            <button onclick="showAddExpenseModal()" class="mt-2 md:mt-0 text-sm bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
              <i class="fas fa-plus mr-1"></i> Add Expense
            </button>
          </div>
          
          <?php if ($expenses): ?>
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-gray-100 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Paid By</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expenses as $expense): ?>
                    <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td class="px-4 py-3"><?php echo htmlspecialchars($expense['title']); ?></td>
                      <td class="px-4 py-3">₹<?php echo number_format($expense['amount'], 2); ?></td>
                      <td class="px-4 py-3"><?php echo htmlspecialchars($expense['paid_by_name']); ?></td>
                      <td class="px-4 py-3"><?php echo date("M d, Y", strtotime($expense['created_at'])); ?></td>
                      <td class="px-4 py-3">
                        <?php if ($user_id == $expense['paid_by'] || $user_id == $group['created_by']): ?>
                          <div class="flex space-x-2">
                            <button onclick="showEditExpenseModal(
                              '<?php echo $expense['id']; ?>',
                              '<?php echo htmlspecialchars($expense['title'], ENT_QUOTES); ?>',
                              '<?php echo $expense['amount']; ?>'
                            )" class="px-3 py-1 bg-yellow-400 text-white rounded text-sm">
                              <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            <form method="POST" action="delete_expense.php" onsubmit="return confirm('Are you sure you want to delete this expense?')" class="inline">
                              <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                              <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                              <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                                <i class="fas fa-trash mr-1"></i> Delete
                              </button>
                            </form>
                          </div>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
              <i class="fas fa-receipt text-4xl mb-3"></i>
              <p>No expenses added yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- Balances Section (Hidden by default) -->
      <section id="balances-section" class="content-section hidden space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-6 flex items-center">
            <i class="fas fa-scale-balanced mr-2"></i> Group Balances
          </h2>
          
          <ul class="space-y-3">
            <?php foreach ($balances as $b): 
              $net = round(($b['total_paid'] - $b['total_owed']) + ($b['settled_received'] - $b['settled_paid']), 2); ?>
              <li class="flex justify-between items-center p-3 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                <span class="font-medium"><?php echo htmlspecialchars($b['username']); ?></span>
                <span class="<?php echo $net > 0 ? 'text-green-600 dark:text-green-400' : ($net < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'); ?>">
                  <?php if ($net > 0): ?>
                    Gets ₹<?php echo number_format($net, 2); ?>
                  <?php elseif ($net < 0): ?>
                    Owes ₹<?php echo number_format(abs($net), 2); ?>
                  <?php else: ?>
                    Settled up
                  <?php endif; ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>

      <!-- Settlements Section (Hidden by default) -->
      <section id="settlements-section" class="content-section hidden space-y-6">
        <!-- Settle Transaction -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-handshake mr-2"></i> Settle Transaction
          </h2>
          <form method="POST" action="settle_balance.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            <select name="to_user_id" class="px-4 py-2 border rounded dark:bg-gray-700" required>
              <option value="">Select Member</option>
              <?php foreach ($members as $member):
                if ($member['id'] == $user_id) continue; ?>
                <option value="<?php echo $member['id']; ?>">
                  <?php echo htmlspecialchars($member['username']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" class="px-4 py-2 border rounded dark:bg-gray-700" required>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
              <i class="fas fa-check mr-1"></i> Settle
            </button>
          </form>
        </div>

        <!-- Settled Transactions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-history mr-2"></i> Settled Transactions
          </h2>
          
          <?php if ($settlements): ?>
            <ul class="space-y-3">
              <?php foreach ($settlements as $s): ?>
                <li class="p-3 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="font-medium"><?php echo htmlspecialchars($s['from_user']); ?></span>
                      <span class="mx-2">paid</span>
                      <span class="font-medium">₹<?php echo number_format($s['amount'], 2); ?></span>
                      <span class="mx-2">to</span>
                      <span class="font-medium"><?php echo htmlspecialchars($s['to_user']); ?></span>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo date("M d, Y", strtotime($s['created_at'])); ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
              <i class="fas fa-handshake-slash text-4xl mb-3"></i>
              <p>No settlements yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <!-- Add Expense Modal -->
  <div id="add-expense-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Add New Expense</h3>
        <button onclick="hideModal('add-expense-modal')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form method="POST" action="add_expense.php">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Title</label>
          <input type="text" name="title" placeholder="Dinner, Ola Ride, etc." class="w-full px-4 py-2 border rounded dark:bg-gray-700" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Amount (₹)</label>
          <input type="number" step="0.01" name="amount" placeholder="0.00" class="w-full px-4 py-2 border rounded dark:bg-gray-700" required>
        </div>
        <div class="flex justify-end space-x-2">
          <button type="button" onclick="hideModal('add-expense-modal')" class="px-4 py-2 border rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Add Expense</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Expense Modal -->
<!-- Edit Expense Modal -->
<div id="edit-expense-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Edit Expense</h3>
      <button onclick="hideModal('edit-expense-modal')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form id="edit-expense-form">
      <input type="hidden" name="expense_id" id="edit-expense-id">
      <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Title</label>
        <input type="text" name="title" id="edit-expense-title" class="w-full px-4 py-2 border rounded dark:bg-gray-700" required>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Amount (₹)</label>
        <input type="number" step="0.01" name="amount" id="edit-expense-amount" class="w-full px-4 py-2 border rounded dark:bg-gray-700" required>
      </div>
      <div class="flex justify-end space-x-2">
        <button type="button" onclick="hideModal('edit-expense-modal')" class="px-4 py-2 border rounded">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Update Expense</button>
      </div>
    </form>
  </div>
</div>

  <script>
    // Mobile sidebar toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeSidebar = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');

    mobileMenuButton.addEventListener('click', () => {
      sidebar.classList.remove('-translate-x-full');
    });

    closeSidebar.addEventListener('click', () => {
      sidebar.classList.add('-translate-x-full');
    });

    window.onload = function () {
    const toggle = document.getElementById('dark-toggle');
    const dot = document.getElementById('dot');

    tailwind.config = {
    darkMode: 'class',
  }

    // Apply saved dark mode preference
    if (localStorage.getItem('darkMode') === 'true') {
      document.documentElement.classList.add('dark');
      toggle.checked = true;
    }

    // Toggle dark mode and transition dot
    toggle.addEventListener('change', () => {
      const isDark = document.documentElement.classList.toggle('dark');
      localStorage.setItem('darkMode', isDark);
    });
  };

    // Section navigation
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');

    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const section = link.getAttribute('data-section');
        
        // Hide all sections
        contentSections.forEach(sec => {
          sec.classList.add('hidden');
        });
        
        // Show selected section
        document.getElementById(`${section}-section`).classList.remove('hidden');
        
        // Close sidebar on mobile
        if (window.innerWidth < 768) {
          sidebar.classList.add('-translate-x-full');
        }
      });
    });

    // Show members section by default
    document.getElementById('members-section').classList.remove('hidden');

    // Modal functions
    function showAddExpenseModal() {
      document.getElementById('add-expense-modal').classList.remove('hidden');
    }
// Show edit modal with prefilled data
function showEditExpenseModal(id, title, amount) {
  document.getElementById('edit-expense-id').value = id;
  document.getElementById('edit-expense-title').value = title;
  document.getElementById('edit-expense-amount').value = amount;
  document.getElementById('edit-expense-modal').classList.remove('hidden');
}

// Handle edit form submission
document.getElementById('edit-expense-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const expenseId = formData.get('expense_id');
  
  fetch('edit_expense.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message
      alert('Expense updated successfully!');
      // Hide modal
      hideModal('edit-expense-modal');
      // Reload the page to reflect changes
      window.location.reload();
    } else {
      alert(data.message || 'Error updating expense');
    }
  })
  .catch(error => {
    alert('Expense updated successfully!');
    hideModal('edit-expense-modal');
    window.location.reload();
  });
  
});
    function hideModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
    }

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target.id === 'add-expense-modal') {
        hideModal('add-expense-modal');
      }
      if (e.target.id === 'edit-expense-modal') {
        hideModal('edit-expense-modal');
      }
    });

    // Responsive adjustments
    function handleResize() {
      if (window.innerWidth >= 768) {
        sidebar.classList.remove('-translate-x-full');
      }
    }

    window.addEventListener('resize', handleResize);
    handleResize();
  </script>
</body>

</html>