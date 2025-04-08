/**
 * Common JavaScript for VBS Application
 * Handles mobile responsiveness, sidebar toggle, and other common functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // Mobile Sidebar Handling
    // =============================================
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarToggle = document.getElementById('sidebarCollapse');
    
    // Check if mobile device
    function isMobile() {
        return window.matchMedia("(max-width: 768px)").matches;
    }
    
    // Create mobile overlay
    function createOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.style.display = 'none';
        document.body.appendChild(overlay);
        return overlay;
    }
    
    // Initialize overlay
    const overlay = createOverlay();
    
    // Toggle sidebar function
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        content.classList.toggle('active');
        
        if (isMobile()) {
            if (!sidebar.classList.contains('active')) {
                overlay.style.display = 'block';
            } else {
                overlay.style.display = 'none';
            }
        }
    }
    
    // Set initial state for mobile
    if (isMobile()) {
        sidebar.classList.add('active');
        content.classList.add('active');
    }
    
    // Sidebar toggle event
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Overlay click event
    overlay.addEventListener('click', toggleSidebar);
    
    // Close sidebar when clicking on menu items (mobile)
    const menuItems = document.querySelectorAll('#sidebar ul li a');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (isMobile()) {
                toggleSidebar();
            }
        });
    });
    
    // =============================================
    // Mobile Viewport Height Fix
    // =============================================
    function setViewportHeight() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    
    // =============================================
    // Form Handling
    // =============================================
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Prevent multiple submissions
        form.addEventListener('submit', function() {
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(button => {
                button.disabled = true;
                if (!button.querySelector('.spinner-border')) {
                    button.innerHTML = `
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Processing...
                    `;
                }
            });
        });
    });
    
    // =============================================
    // Auto-dismiss alerts
    // =============================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s, margin 0.5s';
            alert.style.opacity = '0';
            alert.style.margin = '0';
            alert.style.padding = '0';
            alert.style.height = '0';
            alert.style.border = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});