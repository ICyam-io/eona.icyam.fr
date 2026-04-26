// Scripts globaux EonA — chargé sur toutes les pages
// Global EonA scripts — loaded on all pages

document.addEventListener('DOMContentLoaded', () => {
    // Initialiser les icônes Lucide sur toute la page
    // Initialize Lucide icons across the page
    lucide.createIcons();

    // Marquer le lien actif dans la bottom nav selon l'URL courante
    // Mark the active link in the bottom nav based on the current URL
    const path = window.location.pathname;
    document.querySelectorAll('.bottom-nav a').forEach(link => {
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });
});
