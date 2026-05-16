/**
 * AmenERP - Main JavaScript
 * 
 * Core JavaScript functionality for the ERP dashboard
 * 
 * @package AmenERP
 * @author Bob
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    /**
     * Initialize application when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        console.log('AmenERP Dashboard Initialized');
        
        // Initialize components
        initMobileMenu();
        initTooltips();
        initFormValidation();
    });
    
    /**
     * Mobile menu functionality
     */
    function initMobileMenu() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (!mobileMenuToggle || !sidebar) return;
        
        // Toggle sidebar on button click
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('mobile-open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
        
        // Close sidebar on window resize if switching to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
            }
        });
    }
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(function(element) {
            element.addEventListener('mouseenter', function() {
                const tooltipText = this.getAttribute('data-tooltip');
                const tooltip = createTooltip(tooltipText);
                document.body.appendChild(tooltip);
                positionTooltip(tooltip, this);
            });
            
            element.addEventListener('mouseleave', function() {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
    }
    
    /**
     * Create tooltip element
     */
    function createTooltip(text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background-color: var(--color-bg-dark);
            color: var(--color-text-inverse);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            z-index: 9999;
            pointer-events: none;
            box-shadow: var(--shadow-lg);
        `;
        return tooltip;
    }
    
    /**
     * Position tooltip relative to element
     */
    function positionTooltip(tooltip, element) {
        const rect = element.getBoundingClientRect();
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
        tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
    }
    
    /**
     * Basic form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    /**
     * Validate form fields
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                showFieldError(field, 'This field is required');
            } else {
                field.classList.remove('error');
                removeFieldError(field);
            }
        });
        
        return isValid;
    }
    
    /**
     * Show field error message
     */
    function showFieldError(field, message) {
        removeFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: var(--color-error);
            font-size: var(--font-size-sm);
            margin-top: var(--space-xs);
        `;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    /**
     * Remove field error message
     */
    function removeFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Utility: Show notification
     */
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: var(--space-md) var(--space-lg);
            background-color: var(--color-bg-primary);
            border-left: 4px solid var(--color-${type});
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    };
    
    /**
     * Utility: Confirm dialog
     */
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
    
    /**
     * Add CSS animations
     */
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
})();

// Made with Bob