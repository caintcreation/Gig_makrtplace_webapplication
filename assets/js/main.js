// assets/js/main.js - Interactive Controls & Helpers
document.addEventListener('DOMContentLoaded', function () {
    // Mobile navigation toggle
    const toggleBtn = document.getElementById('navToggleBtn');
    const mainNav = document.getElementById('mainNav');

    if (toggleBtn && mainNav) {
        toggleBtn.addEventListener('click', function () {
            mainNav.classList.toggle('mobile-active');
            const expanded = mainNav.classList.contains('mobile-active');
            toggleBtn.setAttribute('aria-expanded', expanded);
        });

        // Close on link click
        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mainNav.classList.remove('mobile-active');
            });
        });
    }

    // Modal behavior
    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close on overlay backdrop click
    document.querySelectorAll('.modal-overlay').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(function (m) {
                closeModal(m.id);
            });
        }
    });

    // Image lightbox preview
    document.querySelectorAll('.proof-thumb img').forEach(function (img) {
        img.addEventListener('click', function () {
            const src = this.getAttribute('src');
            const previewModal = document.getElementById('imageLightboxModal');
            const previewImg = document.getElementById('lightboxImage');
            if (previewModal && previewImg) {
                previewImg.src = src;
                openModal('imageLightboxModal');
            }
        });
    });
});
