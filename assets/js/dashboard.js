const navLinks = document.querySelectorAll(".nav-link");
const sections = document.querySelectorAll(".section");
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const darkToggle = document.getElementById("darkToggle");
const darkToggleMobile = document.getElementById("darkToggleMobile");

// Apply dark mode on page load based on localStorage
if (localStorage.getItem("darkMode") === "true") {
  document.documentElement.classList.add("dark");
} else {
  document.documentElement.classList.remove("dark");
}

function toggleDarkMode() {
  document.documentElement.classList.toggle("dark");
  const isDark = document.documentElement.classList.contains("dark");
  localStorage.setItem("darkMode", isDark);
}

if (darkToggle) darkToggle.addEventListener("click", toggleDarkMode);
if (darkToggleMobile) darkToggleMobile.addEventListener("click", toggleDarkMode);

// Navigation
navLinks.forEach(link => {
  link.addEventListener("click", () => {
    navLinks.forEach(btn => btn.classList.remove("bg-indigo-100", "dark:bg-indigo-700", "active"));
    link.classList.add("bg-indigo-100", "dark:bg-indigo-700", "active");

    const target = link.getAttribute("data-section");
    sections.forEach(section => section.classList.add("hidden"));
    document.getElementById("section-" + target).classList.remove("hidden");

    if (window.innerWidth < 768) {
      sidebar.classList.add("-translate-x-full");
      overlay.classList.add("hidden");
    }
  });
});

hamburger.addEventListener("click", () => {
  sidebar.classList.toggle("-translate-x-full");
  overlay.classList.toggle("hidden");
});

overlay.addEventListener("click", () => {
  sidebar.classList.add("-translate-x-full");
  overlay.classList.add("hidden");
});