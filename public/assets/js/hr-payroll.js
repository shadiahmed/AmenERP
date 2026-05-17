/**
 * HR Payroll Calculator Module
 * 
 * Handles real-time calculation of net pay and total payout for the HR payroll
 * processing interface. Uses event delegation for optimal performance with
 * dynamic employee rows.
 * 
 * Features:
 * - Real-time net pay calculation (base + allowances - deductions)
 * - Total company payout aggregation
 * - Form validation before submission
 * - Confirmation modal with detailed breakdown
 * - Touch-optimized for mobile devices
 * 
 * @package AmenERP
 * @author Bob - Expert Principal Frontend Engineer
 * @version 2.0.0
 */

class PayrollCalculator {
    constructor() {
        this.table = document.getElementById('payrollTable');
        this.totalPayoutElement = document.getElementById('totalPayout');
        this.payrollForm = document.getElementById('payrollForm');
        
        if (!this.table) return;
        
        this.init();
    }

    /**
     * Initialize the calculator
     */
    init() {
        // Use event delegation on table body for better performance
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;

        tbody.addEventListener('input', (e) => {
            if (e.target.matches('.allowance-input, .deduction-input')) {
                this.handleInputChange(e.target);
            }
        });

        // Initial calculation
        this.updateTotalPayout();

        // Form submission handler
        if (this.payrollForm) {
            this.payrollForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }

    /**
     * Handle input change events
     */
    handleInputChange(input) {
        const employeeId = input.dataset.employeeId;
        const row = input.closest('tr');
        
        if (!row) return;

        this.calculateRowNet(row, employeeId);
        this.updateTotalPayout();
    }

    /**
     * Calculate net pay for a specific employee row
     */
    calculateRowNet(row, employeeId) {
        const baseSalary = parseFloat(row.dataset.baseSalary) || 0;
        const allowanceInput = row.querySelector(`.allowance-input[data-employee-id="${employeeId}"]`);
        const deductionInput = row.querySelector(`.deduction-input[data-employee-id="${employeeId}"]`);
        const netPayCell = row.querySelector(`[data-net-pay="${employeeId}"]`);

        if (!allowanceInput || !deductionInput || !netPayCell) return;

        const allowances = parseFloat(allowanceInput.value) || 0;
        const deductions = parseFloat(deductionInput.value) || 0;

        // Calculate net: base + allowances - deductions
        const netPay = baseSalary + allowances - deductions;

        // Ensure net pay is not negative
        const finalNetPay = Math.max(0, netPay);

        // Update display
        netPayCell.textContent = `$${this.formatCurrency(finalNetPay)}`;

        // Store calculated value for total calculation
        netPayCell.dataset.calculatedNet = finalNetPay.toString();
    }

    /**
     * Update the total company payout display
     */
    updateTotalPayout() {
        if (!this.totalPayoutElement) return;

        const rows = this.table.querySelectorAll('tbody tr');
        let total = 0;

        rows.forEach(row => {
            const employeeId = row.dataset.employeeId;
            const netPayCell = row.querySelector(`[data-net-pay="${employeeId}"]`);
            
            if (netPayCell) {
                // If calculated value exists, use it; otherwise calculate from base
                const netValue = netPayCell.dataset.calculatedNet 
                    ? parseFloat(netPayCell.dataset.calculatedNet)
                    : parseFloat(row.dataset.baseSalary) || 0;
                
                total += netValue;
            }
        });

        this.totalPayoutElement.textContent = `$${this.formatCurrency(total)}`;
    }

    /**
     * Handle form submission with validation and confirmation
     */
    handleFormSubmit(e) {
        const payrollMonth = document.getElementById('payroll_month');
        
        // Validate payroll month is selected
        if (!payrollMonth || !payrollMonth.value) {
            e.preventDefault();
            alert('Please select a payroll month.');
            return false;
        }

        // Get total payout for confirmation
        const totalPayout = this.calculateTotalPayout();
        const employeeCount = this.table.querySelectorAll('tbody tr').length;

        // Format month for display
        const monthDate = new Date(payrollMonth.value + '-01');
        const monthName = monthDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });

        // Confirmation dialog with detailed information
        const confirmMessage = `
═══════════════════════════════════════
    PAYROLL PROCESSING CONFIRMATION
═══════════════════════════════════════

Period: ${monthName}
Employees: ${employeeCount}
Total Payout: $${this.formatCurrency(totalPayout)}

⚠️  WARNING: This action will:
• Create financial transactions
• Update employee payment records
• Affect accounting balances
• Cannot be easily undone

═══════════════════════════════════════

Are you sure you want to proceed?
        `.trim();

        const confirmed = confirm(confirmMessage);

        if (!confirmed) {
            e.preventDefault();
            return false;
        }

        return true;
    }

    /**
     * Calculate total payout (helper method)
     */
    calculateTotalPayout() {
        const rows = this.table.querySelectorAll('tbody tr');
        let total = 0;

        rows.forEach(row => {
            const employeeId = row.dataset.employeeId;
            const netPayCell = row.querySelector(`[data-net-pay="${employeeId}"]`);
            
            if (netPayCell) {
                const netValue = netPayCell.dataset.calculatedNet 
                    ? parseFloat(netPayCell.dataset.calculatedNet)
                    : parseFloat(row.dataset.baseSalary) || 0;
                
                total += netValue;
            }
        });

        return total;
    }

    /**
     * Format currency with proper locale formatting
     */
    formatCurrency(value) {
        return value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

/**
 * Initialize calculator when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    new PayrollCalculator();
});

/**
 * Export for potential use in other modules
 */
export default PayrollCalculator;

// Made with Bob
