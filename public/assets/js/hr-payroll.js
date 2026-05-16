/**
 * Payroll Calculator Module
 * Handles real-time calculation of net pay and total payout
 * Uses event delegation for optimal performance
 */

class PayrollCalculator {
    constructor() {
        this.table = document.getElementById('payrollTable');
        this.totalPayoutElement = document.getElementById('totalPayout');
        
        if (!this.table) return;
        
        this.init();
    }

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
    }

    handleInputChange(input) {
        const employeeId = input.dataset.employeeId;
        const row = input.closest('tr');
        
        if (!row) return;

        this.calculateRowNet(row, employeeId);
        this.updateTotalPayout();
    }

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
 * Form validation before submission
 */
const payrollForm = document.getElementById('payrollForm');
if (payrollForm) {
    payrollForm.addEventListener('submit', (e) => {
        const payrollMonth = document.getElementById('payroll_month');
        
        if (!payrollMonth || !payrollMonth.value) {
            e.preventDefault();
            alert('Please select a payroll month.');
            return false;
        }

        // Confirm before processing
        const confirmed = confirm(
            `Are you sure you want to process payroll for ${payrollMonth.value}?\n\n` +
            'This action will create financial transactions and cannot be easily undone.'
        );

        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
}
