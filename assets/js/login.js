const toggle = document.getElementById('dark-toggle');
const passwordInput = document.getElementById('password-input');
const showPassword = document.getElementById('show-password');

// Check local storage for dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
  document.documentElement.classList.add('dark');
  toggle.checked = true;
}

toggle.addEventListener('change', () => {
  document.documentElement.classList.toggle('dark');
  localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
});

showPassword.addEventListener('change', () => {
  passwordInput.type = showPassword.checked ? 'text' : 'password';
});