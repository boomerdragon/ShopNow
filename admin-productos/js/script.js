/**
 * Client-side JavaScript Utilities
 * Used for form validation and UI interactions
 */

// Initialize common functionality on page load
document.addEventListener('DOMContentLoaded', function() {
    // Price number formatter
    const priceInputs = document.querySelectorAll('input[name="precio"]');
    priceInputs.forEach(input => {
        formatNumberInput(input, 2);
    });
    
    // Quantity number formatter
    const quantityInputs = document.querySelectorAll('input[name="cantidad"]');
    quantityInputs.forEach(input => {
        formatNumberInput(input, 0);
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Format number input to allow decimals
function formatNumberInput(input, decimals) {
    input.addEventListener('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(decimals);
        }
    });
}
