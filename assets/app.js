// Fade-in smooth optimized
function fadeInElements() {
  const elements = document.querySelectorAll('.fade');
  const windowHeight = window.innerHeight;

  elements.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < windowHeight - 60) {
      el.classList.add('show');
    }
  });
}

window.addEventListener('load', () => {
  setTimeout(fadeInElements, 80); // memberi waktu halaman stabil
});

window.addEventListener('scroll', fadeInElements);
