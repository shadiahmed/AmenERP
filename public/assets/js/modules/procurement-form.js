/**
 * Procurement Form Manager Module
 * 
 * Handles dynamic row management, real-time calculations, and supplier integration
 * for the procurement purchase order form. Uses vanilla JavaScript with ES6+ features.
 * 
 * Features:
 * - Dynamic item row addition/removal
 * - Real-time line total and grand total calculations
 * - Supplier credit limit validation
 * - Payment type switching (cash/credit)
 * - Form validation before submission
 * 
 * @package AmenERP
 * @author Bob - Expert Principal Frontend Engineer
 * @version 2.0.0
 */

class ProcurementFormManager {
    constructor() {
        this.itemsContainer = document.getElementById('itemsContainer');
        this.addItemBtn = document.getElementById('addItemBtn');
        this.totalAmountDisplay = document.getElementById('totalAmount');
        this.rowTemplate = document.getElementById('rowTemplate');
        this.rowCounter = 0;
        
        // Supplier integration elements
        this.paymentTypeSelect = document.getElementById('payment_type');
        this.supplierIdWrapper = document.getElementById('supplier_id_wrapper');
        this.supplierIdSelect = document.getElementById('supplier_id');
        this.supplierNameInput = document.getElementById('supplier_name');
        this.purchaseForm = document.getElementById('purchaseForm');

        this.init();
    }

    /**
     * Initialize the form manager
     */
    init() {
        // Add initial row
        this.addItemRow();

        // Event listeners for items
        this.addItemBtn.addEventListener('click', () => this.addItemRow());

        // Event delegation for dynamic elements
        this.itemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item-btn')) {
                this.removeItemRow(e.target);
            }
        });

        this.itemsContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('item-quantity') ||
                e.target.classList.contains('item-cost')) {
                this.updateLineTotal(e.target);
                this.updateGrandTotal();
            }
        });

        this.itemsContainer.addEventListener('change', (e) => {
            if (e.target.classList.contains('item-product')) {
                this.handleProductChange(e.target);
            }
        });

        // Event listeners for supplier integration
        this.paymentTypeSelect.addEventListener('change', () => this.handlePaymentTypeChange());
        this.supplierIdSelect.addEventListener('change', () => this.handleSupplierChange());
        
        // Form submission validation
        this.purchaseForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    /**
     * Handle payment type change (cash vs credit)
     */
    handlePaymentTypeChange() {
        const paymentType = this.paymentTypeSelect.value;
        
        if (paymentType === 'credit') {
            // Show supplier dropdown and make it required
            this.supplierIdWrapper.style.display = 'block';
            this.supplierIdSelect.setAttribute('required', 'required');
        } else {
            // Hide supplier dropdown and remove required attribute
            this.supplierIdWrapper.style.display = 'none';
            this.supplierIdSelect.removeAttribute('required');
            this.supplierIdSelect.value = '';
            this.supplierNameInput.value = '';
        }
    }

    /**
     * Handle supplier selection change
     */
    handleSupplierChange() {
        const selectedOption = this.supplierIdSelect.options[this.supplierIdSelect.selectedIndex];
        
        if (selectedOption.value) {
            // Update hidden supplier name field
            const supplierName = selectedOption.dataset.name || '';
            this.supplierNameInput.value = supplierName;
            
            // Optional: Display supplier credit information
            const creditAvailable = parseFloat(selectedOption.dataset.creditAvailable) || 0;
            const currentBalance = parseFloat(selectedOption.dataset.balance) || 0;
            
            console.log('Supplier selected:', {
                name: supplierName,
                creditAvailable: creditAvailable,
                currentBalance: currentBalance
            });
        } else {
            this.supplierNameInput.value = '';
        }
    }

    /**
     * Handle form submission validation
     */
    handleFormSubmit(e) {
        const paymentType = this.paymentTypeSelect.value;
        const totalAmount = this.calculateGrandTotal();
        
        // Validate credit purchase requirements
        if (paymentType === 'credit') {
            const supplierId = this.supplierIdSelect.value;
            
            if (!supplierId) {
                e.preventDefault();
                alert('Please select a supplier for credit purchases.');
                return false;
            }
            
            // Optional: Check credit limit
            const selectedOption = this.supplierIdSelect.options[this.supplierIdSelect.selectedIndex];
            const creditAvailable = parseFloat(selectedOption.dataset.creditAvailable) || 0;
            
            if (creditAvailable > 0 && totalAmount > creditAvailable) {
                const confirmMsg = `Warning: Purchase amount ($${totalAmount.toFixed(2)}) exceeds available credit ($${creditAvailable.toFixed(2)}). Continue anyway?`;
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }
            }
        }
        
        // Validate at least one item
        const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one item to the purchase order.');
            return false;
        }
        
        return true;
    }

    /**
     * Add a new item row to the form
     */
    addItemRow() {
        const template = this.rowTemplate.content.cloneNode(true);
        const row = template.querySelector('.erp-item-row');
        
        // Set unique index
        row.dataset.rowIndex = this.rowCounter;
        
        // Update name attributes with actual index
        const inputs = row.querySelectorAll('[name*="INDEX"]');
        inputs.forEach(input => {
            input.name = input.name.replace('INDEX', this.rowCounter.toString());
        });

        this.itemsContainer.appendChild(row);
        this.rowCounter++;
        
        // Update remove button visibility
        this.updateRemoveButtonsVisibility();
    }

    /**
     * Remove an item row from the form
     */
    removeItemRow(button) {
        const row = button.closest('.erp-item-row');
        
        // Prevent removing the last row
        const rowCount = this.itemsContainer.querySelectorAll('.erp-item-row').length;
        if (rowCount <= 1) {
            alert('At least one item is required.');
            return;
        }

        row.remove();
        this.updateGrandTotal();
        this.updateRemoveButtonsVisibility();
    }

    /**
     * Update visibility of remove buttons
     * Hide remove button when only one row exists
     */
    updateRemoveButtonsVisibility() {
        const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
        const removeButtons = this.itemsContainer.querySelectorAll('.remove-item-btn');
        
        if (rows.length === 1) {
            removeButtons.forEach(btn => btn.style.visibility = 'hidden');
        } else {
            removeButtons.forEach(btn => btn.style.visibility = 'visible');
        }
    }

    /**
     * Handle product selection change
     */
    handleProductChange(selectElement) {
        const row = selectElement.closest('.erp-item-row');
        const costInput = row.querySelector('.item-cost');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        if (selectedOption.value) {
            const price = parseFloat(selectedOption.dataset.price) || 0;
            costInput.value = price.toFixed(2);
            this.updateLineTotal(costInput);
            this.updateGrandTotal();
        }
    }

    /**
     * Update line total for a specific row
     */
    updateLineTotal(inputElement) {
        const row = inputElement.closest('.erp-item-row');
        const quantityInput = row.querySelector('.item-quantity');
        const costInput = row.querySelector('.item-cost');
        const lineTotalDisplay = row.querySelector('.item-line-total');

        const quantity = parseFloat(quantityInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;
        const lineTotal = quantity * cost;

        lineTotalDisplay.textContent = '$' + lineTotal.toFixed(2);
    }

    /**
     * Calculate the grand total (helper method)
     */
    calculateGrandTotal() {
        const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
        let grandTotal = 0;

        rows.forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            grandTotal += quantity * cost;
        });

        return grandTotal;
    }

    /**
     * Update the grand total display
     */
    updateGrandTotal() {
        const grandTotal = this.calculateGrandTotal();
        this.totalAmountDisplay.textContent = '$' + grandTotal.toFixed(2);
    }
}

// Initialize the form manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ProcurementFormManager();
});

// Export for potential use in other modules
export default ProcurementFormManager;

// Made with Bob
