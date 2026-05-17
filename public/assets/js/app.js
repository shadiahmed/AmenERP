/**
 * AmenERP Global Application Controller
 * 
 * This is the central JavaScript module that orchestrates all client-side functionality
 * for the AmenERP system. It provides a framework-free, vanilla ES6+ implementation
 * with zero external dependencies.
 * 
 * Core Features:
 * - Modal dialog management with keyboard and overlay controls
 * - Mobile navigation toggle for responsive sidebar
 * - Form validation and submission handling
 * - Global event delegation for dynamic content
 * - Utility functions for common operations
 * 
 * Architecture:
 * - Pure ES6+ JavaScript (no jQuery, no frameworks)
 * - Module pattern for encapsulation
 * - Event delegation for performance
 * - Accessible keyboard navigation
 * - Mobile-first responsive behavior
 * 
 * @package AmenERP
 * @author Bob - Expert Principal Frontend Engineer
 * @version 2.0.0
 */

/* ============================================================================
   INITIALIZATION - DOM Ready Handler
   ============================================================================ */

/**
 * Initialize application when DOM is fully loaded
 */
document.addEventListener('DOMContentLoaded', () => {
  initializeMobileNavigation();
  initializeModalSystem();
  initializeFormEnhancements();
  initializeTableEnhancements();
  initializeAccessibility();
  
  console.log('✅ AmenERP Application Initialized');
});

/* ============================================================================
   MOBILE NAVIGATION - Responsive Sidebar Control
   ============================================================================ */

/**
 * Initialize mobile navigation toggle functionality
 * Handles sidebar open/close on mobile devices with backdrop
 */
function initializeMobileNavigation() {
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (!mobileToggle || !sidebar) {
    return;
  }
  
  // Create backdrop element if it doesn't exist
  let backdrop = document.querySelector('.sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }
  
  /**
   * Toggle sidebar visibility
   */
  const toggleSidebar = () => {
    const isOpen = sidebar.classList.contains('mobile-open');
    
    if (isOpen) {
      closeSidebar();
    } else {
      openSidebar();
    }
  };
  
  /**
   * Open sidebar on mobile
   */
  const openSidebar = () => {
    sidebar.classList.add('mobile-open');
    backdrop.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent body scroll
    
    // Set focus to first navigation link for accessibility
    const firstLink = sidebar.querySelector('.nav-link');
    if (firstLink) {
      firstLink.focus();
    }
  };
  
  /**
   * Close sidebar on mobile
   */
  const closeSidebar = () => {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('active');
    document.body.style.overflow = ''; // Restore body scroll
    
    // Return focus to toggle button
    mobileToggle.focus();
  };
  
  // Toggle button click handler
  mobileToggle.addEventListener('click', toggleSidebar);
  
  // Backdrop click handler - close sidebar
  backdrop.addEventListener('click', closeSidebar);
  
  // Close sidebar on navigation link click (mobile)
  const navLinks = sidebar.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        closeSidebar();
      }
    });
  });
  
  // Close sidebar on window resize to desktop size
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (window.innerWidth > 1024) {
        closeSidebar();
      }
    }, 250);
  });
  
  // Keyboard navigation - ESC to close sidebar
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
      closeSidebar();
    }
  });
}

/* ============================================================================
   MODAL SYSTEM - Dialog Management
   ============================================================================ */

/**
 * Initialize modal dialog system
 * Provides global functions for opening and closing modals
 */
function initializeModalSystem() {
  /**
   * Open a modal by ID
   * @param {string} modalId - The ID of the modal to open
   */
  window.openErpModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (!modal) {
      console.warn(`Modal with ID "${modalId}" not found`);
      return;
    }
    
    // Find or create backdrop
    let backdrop = modal.closest('.modal-backdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop';
      backdrop.id = `${modalId}-backdrop`;
      
      // Move modal inside backdrop
      const modalElement = modal.cloneNode(true);
      modal.remove();
      backdrop.appendChild(modalElement);
      document.body.appendChild(backdrop);
    }
    
    // Show modal with animation
    requestAnimationFrame(() => {
      backdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
      
      // Set focus to first focusable element in modal
      const firstFocusable = backdrop.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (firstFocusable) {
        setTimeout(() => firstFocusable.focus(), 100);
      }
    });
    
    // Store last focused element to restore later
    backdrop.dataset.lastFocus = document.activeElement?.id || '';
  };
  
  /**
   * Close a modal by ID
   * @param {string} modalId - The ID of the modal to close
   */
  window.closeErpModal = (modalId) => {
    const backdrop = document.getElementById(`${modalId}-backdrop`);
    if (!backdrop) {
      console.warn(`Modal backdrop for "${modalId}" not found`);
      return;
    }
    
    backdrop.classList.remove('active');
    document.body.style.overflow = '';
    
    // Restore focus to element that opened the modal
    const lastFocusId = backdrop.dataset.lastFocus;
    if (lastFocusId) {
      const lastFocusElement = document.getElementById(lastFocusId);
      if (lastFocusElement) {
        lastFocusElement.focus();
      }
    }
    
    // Remove backdrop after animation completes
    setTimeout(() => {
      backdrop.remove();
    }, 300);
  };
  
  /**
   * Close modal on backdrop click
   */
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
      const modal = e.target.querySelector('.modal, .erp-modal');
      if (modal && modal.id) {
        window.closeErpModal(modal.id);
      }
    }
  });
  
  /**
   * Close modal on close button click
   */
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-close') || 
        e.target.closest('.modal-close')) {
      const modal = e.target.closest('.modal, .erp-modal');
      if (modal && modal.id) {
        window.closeErpModal(modal.id);
      }
    }
  });
  
  /**
   * Close modal on ESC key press
   */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const activeBackdrop = document.querySelector('.modal-backdrop.active');
      if (activeBackdrop) {
        const modal = activeBackdrop.querySelector('.modal, .erp-modal');
        if (modal && modal.id) {
          window.closeErpModal(modal.id);
        }
      }
    }
  });
  
  /**
   * Trap focus within modal when open
   */
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab') return;
    
    const activeBackdrop = document.querySelector('.modal-backdrop.active');
    if (!activeBackdrop) return;
    
    const focusableElements = activeBackdrop.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    if (e.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    }
  });
}

/* ============================================================================
   FORM ENHANCEMENTS - Validation & UX Improvements
   ============================================================================ */

/**
 * Initialize form enhancements
 * Adds real-time validation, loading states, and better UX
 */
function initializeFormEnhancements() {
  /**
   * Add loading state to form submission
   */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.matches('form')) return;
    
    // Check if form has data-no-loading attribute
    if (form.hasAttribute('data-no-loading')) return;
    
    // Find submit button
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.originalText = submitBtn.textContent;
      
      // Add spinner if button doesn't have one
      if (!submitBtn.querySelector('.spinner')) {
        const spinner = document.createElement('span');
        spinner.className = 'spinner spinner-sm';
        submitBtn.prepend(spinner);
      }
      
      submitBtn.textContent = ' Processing...';
    }
  });
  
  /**
   * Real-time input validation
   */
  document.addEventListener('blur', (e) => {
    const input = e.target;
    if (!input.matches('input, select, textarea')) return;
    
    validateInput(input);
  }, true);
  
  /**
   * Clear validation on input
   */
  document.addEventListener('input', (e) => {
    const input = e.target;
    if (!input.matches('input, select, textarea')) return;
    
    if (input.classList.contains('is-invalid')) {
      input.classList.remove('is-invalid');
      const feedback = input.parentElement.querySelector('.invalid-feedback');
      if (feedback) {
        feedback.remove();
      }
    }
  });
  
  /**
   * Validate individual input
   * @param {HTMLElement} input - The input element to validate
   */
  function validateInput(input) {
    // Skip if input doesn't have validation attributes
    if (!input.hasAttribute('required') && 
        !input.hasAttribute('pattern') && 
        !input.hasAttribute('minlength') &&
        !input.hasAttribute('maxlength') &&
        !input.hasAttribute('min') &&
        !input.hasAttribute('max')) {
      return;
    }
    
    const isValid = input.checkValidity();
    
    if (!isValid) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      
      // Add error message if not exists
      let feedback = input.parentElement.querySelector('.invalid-feedback');
      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentElement.appendChild(feedback);
      }
      
      feedback.textContent = input.validationMessage;
    } else {
      input.classList.remove('is-invalid');
      input.classList.add('is-valid');
      
      const feedback = input.parentElement.querySelector('.invalid-feedback');
      if (feedback) {
        feedback.remove();
      }
    }
  }
  
  /**
   * Auto-format number inputs
   */
  document.addEventListener('input', (e) => {
    const input = e.target;
    if (input.type !== 'number' || !input.hasAttribute('data-format')) return;
    
    // Format number with commas
    const value = input.value.replace(/,/g, '');
    if (!isNaN(value) && value !== '') {
      input.value = Number(value).toLocaleString();
    }
  });
}

/* ============================================================================
   TABLE ENHANCEMENTS - Interactive Data Grids
   ============================================================================ */

/**
 * Initialize table enhancements
 * Adds sorting, filtering, and responsive behavior
 */
function initializeTableEnhancements() {
  /**
   * Add row click handlers for tables with data-clickable attribute
   */
  document.addEventListener('click', (e) => {
    const row = e.target.closest('tr[data-href]');
    if (!row) return;
    
    // Don't navigate if clicking on a button or link
    if (e.target.closest('button, a')) return;
    
    const href = row.dataset.href;
    if (href) {
      window.location.href = href;
    }
  });
  
  /**
   * Add hover effect to clickable rows
   */
  const clickableRows = document.querySelectorAll('tr[data-href]');
  clickableRows.forEach(row => {
    row.style.cursor = 'pointer';
  });
  
  /**
   * Responsive table wrapper
   * Ensure tables are scrollable on mobile
   */
  const tables = document.querySelectorAll('table:not(.table-container table)');
  tables.forEach(table => {
    if (!table.parentElement.classList.contains('table-container')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'table-container';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    }
  });
}

/* ============================================================================
   ACCESSIBILITY ENHANCEMENTS
   ============================================================================ */

/**
 * Initialize accessibility features
 * Improves keyboard navigation and screen reader support
 */
function initializeAccessibility() {
  /**
   * Add skip to main content link
   */
  const mainContent = document.querySelector('.main-content');
  if (mainContent && !document.getElementById('skip-to-main')) {
    const skipLink = document.createElement('a');
    skipLink.id = 'skip-to-main';
    skipLink.href = '#main-content';
    skipLink.className = 'sr-only';
    skipLink.textContent = 'Skip to main content';
    skipLink.style.cssText = `
      position: absolute;
      top: -40px;
      left: 0;
      background: var(--primary);
      color: white;
      padding: 8px;
      text-decoration: none;
      z-index: 100;
    `;
    
    skipLink.addEventListener('focus', () => {
      skipLink.style.top = '0';
    });
    
    skipLink.addEventListener('blur', () => {
      skipLink.style.top = '-40px';
    });
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    mainContent.id = 'main-content';
    mainContent.setAttribute('tabindex', '-1');
  }
  
  /**
   * Announce dynamic content changes to screen readers
   */
  window.announceToScreenReader = (message) => {
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    setTimeout(() => {
      announcement.remove();
    }, 1000);
  };
  
  /**
   * Add aria-current to active navigation links
   */
  const activeNavLink = document.querySelector('.nav-link.active');
  if (activeNavLink) {
    activeNavLink.setAttribute('aria-current', 'page');
  }
}

/* ============================================================================
   UTILITY FUNCTIONS - Common Operations
   ============================================================================ */

/**
 * Debounce function for performance optimization
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
window.debounce = (func, wait = 300) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

/**
 * Throttle function for performance optimization
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} Throttled function
 */
window.throttle = (func, limit = 300) => {
  let inThrottle;
  return function executedFunction(...args) {
    if (!inThrottle) {
      func(...args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

/**
 * Format currency for display
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (default: USD)
 * @returns {string} Formatted currency string
 */
window.formatCurrency = (amount, currency = 'USD') => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount);
};

/**
 * Format date for display
 * @param {string|Date} date - Date to format
 * @param {string} format - Format style (default: 'short')
 * @returns {string} Formatted date string
 */
window.formatDate = (date, format = 'short') => {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  
  const options = {
    short: { year: 'numeric', month: 'short', day: 'numeric' },
    long: { year: 'numeric', month: 'long', day: 'numeric' },
    full: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  };
  
  return new Intl.DateTimeFormat('en-US', options[format] || options.short).format(dateObj);
};

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of notification (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
window.showToast = (message, type = 'info', duration = 3000) => {
  const toast = document.createElement('div');
  toast.className = `alert alert-${type}`;
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 500px;
    animation: slideInRight 0.3s ease-out;
  `;
  
  const icon = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  }[type] || 'ℹ';
  
  toast.innerHTML = `
    <div class="alert-icon">${icon}</div>
    <div class="alert-content">
      <div class="alert-message">${message}</div>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  // Announce to screen readers
  window.announceToScreenReader(message);
  
  setTimeout(() => {
    toast.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => toast.remove(), 300);
  }, duration);
};

/**
 * Confirm dialog with promise
 * @param {string} message - Confirmation message
 * @returns {Promise<boolean>} Promise that resolves to true if confirmed
 */
window.confirmAction = (message) => {
  return new Promise((resolve) => {
    const confirmed = window.confirm(message);
    resolve(confirmed);
  });
};

/* ============================================================================
   ANIMATIONS - CSS Keyframes
   ============================================================================ */

// Add animation styles dynamically
const style = document.createElement('style');
style.textContent = `
  @keyframes slideInRight {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOutRight {
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

/* ============================================================================
   EXPORT - Make functions available globally
   ============================================================================ */

// All functions are already attached to window object for global access
console.log('📦 AmenERP Global Functions Available:', {
  openErpModal: 'Open modal dialog',
  closeErpModal: 'Close modal dialog',
  debounce: 'Debounce function calls',
  throttle: 'Throttle function calls',
  formatCurrency: 'Format currency values',
  formatDate: 'Format date values',
  showToast: 'Show toast notification',
  confirmAction: 'Show confirmation dialog',
  announceToScreenReader: 'Announce to screen readers'
});

// End of app.js

// Made with Bob
