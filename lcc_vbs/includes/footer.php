                <!-- Timeout Warning Modal -->
                <div class="modal fade" id="timeoutModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">Session Timeout Warning</h5>
                            </div>
                            <div class="modal-body">
                                <p>Your session is about to expire due to inactivity. You will be logged out in <span id="timeoutSeconds">300</span> seconds.</p>
                                <p>Move your mouse or press any key to continue working.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continue Session</button>
                                <a href="logout.php" class="btn btn-secondary">Logout Now</a>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="../assets/js/script.js"></script>
                <script>
                    // Session timeout warning
                    let timeoutWarning;
                    const timeoutDuration = 10 * 60 * 1000; // 25 minutes (5 minutes before actual timeout)

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
                </script>
            </main>
        </div>
    </div>
</body>
</html>