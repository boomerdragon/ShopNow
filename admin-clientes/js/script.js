<?php
/**
 * Client-side JavaScript Utilities
 * Used for form validation and UI interactions
 */

// Phone number formatter
function formatPhoneNumber(input) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').substring(0, 10);
    });
}

// Initialize phone inputs
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        formatPhoneNumber(input);
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
