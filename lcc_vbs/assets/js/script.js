// Session timeout warning
let timeoutWarning;
const timeoutDuration = 25 * 60 * 1000; // 25 minutes (5 minutes before actual timeout)

function startTimeoutWarning() {
    timeoutWarning = setTimeout(() => {
        // Show warning modal
        const modal = new bootstrap.Modal(document.getElementById('timeoutModal'));
        modal.show();
        
        // Countdown to logout
        let seconds = 300; // 5 minutes
        const countdown = setInterval(() => {
            document.getElementById('timeoutSeconds').textContent = seconds;
            seconds--;
            
            if (seconds < 0) {
                clearInterval(countdown);
                window.location.href = 'logout.php?timeout=1';
            }
        }, 1000);
    }, timeoutDuration);
}

// Reset timer on activity
document.addEventListener('mousemove', resetTimeout);
document.addEventListener('keypress', resetTimeout);

function resetTimeout() {
    clearTimeout(timeoutWarning);
    startTimeoutWarning();
}

// Start the timer when page loads
document.addEventListener('DOMContentLoaded', startTimeoutWarning);