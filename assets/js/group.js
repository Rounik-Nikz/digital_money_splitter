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