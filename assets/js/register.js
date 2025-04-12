const toggle = document.getElementById('dark-toggle');
toggle.addEventListener('change', () => {
  document.documentElement.classList.toggle('dark');
  localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
});

if (localStorage.getItem('darkMode') === 'true') {
  document.documentElement.classList.add('dark');
  toggle.checked = true;
}