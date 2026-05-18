**User:**

<task>
'project-rules.md' (see below for file content) 

Act as a Senior UI/UX Engineer. We need to completely rewrite our `modules/sales/index.php` view file to natively support choosing between standard Cash Sales and registered B2B Credit Customers. 

Use this base layout specification to rebuild the file cleanly from the first PHP tag down to the closing HTML/JavaScript lines without skipping any functional sections:

1. DATA & BOOTSTRAP LAYER:
   - Keep our existing configuration boots intact (Database, Csrf, SalesModel, InventoryModel).
   - Instantiate our `CustomerModel` (located at `modules/customers/models/CustomerModel.php`). Fetch all active corporate clients into a `$b2bCustomers` array variable using `$customerModel->getAllCustomers()`.

2. ACCESSIBILITY INTERFACE CARD MODIFICATIONS:
   - Locate the customer input row in the layout form. Replace the static single field with a responsive grid row containing three interactive nodes:
     * A dropdown selector element named `payment_type`. Options: "Direct Cash Payment" (value: 'cash') and "On-Account Commercial Credit" (value: 'credit').
     * A text input box named `customer_name` wrapped in an identity div block (`id="customer_name_wrapper"`). This will be our default fallback input for walk-in cash clients.
     * A dropdown selector element named `customer_id` wrapped in a hidden container div block (`id="customer_id_wrapper" style="display: none;"`). This selector must map a loop over `$b2bCustomers`, rendering options that contain raw data attributes for limit tracking: `data-limit`, `data-debt`, and `data-name`.
   - Ensure a hidden credit limit alert container is printed below the totals text block (`<div id="credit_warning" style="color: red; font-weight: bold; margin-top: 5px;"></div>`).

3. EVAPORATING VANILLA JAVASCRIPT LOGIC REFACTORING:
   - Preserve all existing dynamic multi-item line appenders (`addItemBtn`), row indexing numbers, unit price auto-fill calculations, line-total mathematical multipliers, and table summaries exactly as originally written.
   - Inject an active change listener tied to the new `#payment_type` element:
     * If changed to 'credit', use CSS styling rules to toggle visibility blocks (`display: none` vs `display: block`). Show the `#customer_id_wrapper`, hide the `#customer_name_wrapper`, add the HTML `required` attribute to `customer_id`, and drop it from `customer_name`.
     * If flipped back to 'cash', reverse the visibility layers, drop `required` from the ID dropdown box, restore `required` to the free-text name box, and reset their value strings.
   - Append a second listener block on `#customer_id` change actions: When selected, it must programmatically parse the target option's `dataset.name` and assign that text string directly into the hidden `customer_name` input field so the form always submits a descriptive text name fallback string alongside the raw key index.
   - Build a calculation checker function `checkCreditBoundaries()` that compares the active `#totalAmount` node value against the selected client's data attributes (`data-limit` minus `data-debt`), instantly outputting a warning if the transaction would breach their approved corporate credit line. Run this calculation dynamically every time an item row is added, modified, or deleted.

Provide the complete file cleanly from opening PHP tags down to the final line of client scripting. Do not provide placeholders or compressed code.
</task>

<file_content path="project-rules.md">
 1 | # AmenERP Architecture Rules
 2 | 
 3 | # 1. Architectural Philosophy
 4 | - Zero Frameworks: No Laravel, no Symfony, no Bootstrap.
 5 | - Vanilla Core: Use pure PHP 8.x, native MySQL (PDO), and Vanilla JavaScript (ES6+).
 6 | - Performance First: Prioritize lean execution and minimal memory footprint.
 7 | - Manual Control: Every line of code must be explicit; no "magic" background processes.
 8 | 
 9 | # 2. Coding Standards
10 | - Security: All database interactions must use PDO prepared statements. Sanitize all user inputs using filter_var or htmlspecialchars.
11 | - Database: Use MySQL. All tables must have created_at and updated_at timestamps. Use InnoDB engine.
12 | - Formatting: Follow PSR-12 for PHP. Use clean, documented code with PHPDoc blocks for all functions.
13 | - Directory Logic:
14 |     - /core for system-wide classes (Database, Session, Auth).
15 |     - /modules for business logic (Inventory, Sales).
16 |     - /public for the only web-accessible files.
17 | 
18 | # 3. Reviewer Instructions
19 | - Constraint Check: Before providing any code, Bob must verify if it introduces an external dependency. If it does, stop and find a "Vanilla" alternative.
20 | - Security Audit: Bob must point out potential SQLi, XSS, or CSRF risks in any generated snippet.
21 | - Optimization: If a query can be more efficient or a loop can be avoided, Bob must suggest the optimized version.
22 | 
23 | # 4. HTML5 Standards
24 | -Semantic Markup: Use meaningful tags (<main>, <nav>, <section>, <article>, <aside>) to ensure accessibility and SEO without extra classes.
25 | - Data Attributes: Use data-* attributes to store state or configuration for JS, keeping the DOM as the source of truth.
26 | - Native Form Validation: Leverage required, pattern, type="email", and min/max to reduce initial JS overhead for basic validation.
27 | - No Inline Styles: Styles must never be written in the style attribute of an HTML element.
28 | 
29 | # 5. CSS3 (Vanilla & Lean)
30 | - Flexbox & Grid: Use native CSS Grid for the main ERP layout and Flexbox for component alignment. No float-based hacks.
31 | - CSS Variables: Define a global root for colors, spacing, and font sizes to ensure system-wide consistency without a preprocessor like SASS.
32 | - Mobile-First Design: Since the app may run on mobile (Termux/Localhost), use @media queries starting from the smallest screen size up.
33 | - Naming Convention: Use a simplified BEM (Block-Element-Modifier) or a consistent prefixing system (e.g., .erp-btn, .erp-card) to avoid style bleeding in a modular system.
34 | - Native Nesting: Use modern CSS nesting (available in 2026 browsers) to keep stylesheets readable without extra tools.
35 | - Include all CSS in a single folder: Keep CSS in a single folder (e.g., /assets/css) to avoid cluttering the project structure.
36 | 
37 | # 6. JavaScript (ES6+ & Beyond)
38 | - No Frameworks/Libraries: Zero jQuery. Use fetch() for API calls, querySelector for DOM manipulation, and addEventListener for events.
39 | - Modular JS: Use ES Modules (import/export) to keep your ERP modules (Inventory, Finance) isolated and clean.
40 | - Asynchronous Logic: Use async/await for all database interactions or API calls to the PHP core.
41 | - State Management: Use simple Javascript objects or the CustomEvent API for communication between modules, avoiding complex state libraries.
42 | - Event Delegation: Attach listeners to parent containers where possible to improve performance when handling large lists (like inventory tables).
43 | - Include all JS in a single folder: Keep JS in a single folder (e.g., /assets/js) to avoid cluttering the project structure.

</file_content>
<environment_details>
# VSCode Visible Files
modules\sales\controllers\ProcessSaleController.php

# VSCode Open Tabs
modules/sales/models/SalesModel.php,modules/sales/controllers/ProcessSaleController.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T17:18:55.276Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.00

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>


# Current Workspace Directory (c:/xampp/htdocs/AmenERP) Files
LICENSE
project-rules.md
README.md
schema.sql
setup-unix.sh
setup-windows.ps1
config/
config/config.php
core/
core/Csrf.php
core/Database.php
core/Router.php
modules/
modules/customers/
modules/customers/index.php
modules/customers/controllers/
modules/customers/controllers/ProcessPaymentController.php
modules/customers/database/
modules/customers/database/schema.sql
modules/customers/models/
modules/customers/models/CustomerModel.php
modules/finance/
modules/finance/index.php
modules/finance/controllers/
modules/finance/controllers/RecordExpenseController.php
modules/finance/controllers/RecordIncomeController.php
modules/finance/database/
modules/finance/database/schema.sql
modules/finance/models/
modules/finance/models/FinanceModel.php
modules/finance/models/ReportModel.php
modules/home/
modules/home/index.php
modules/home/INTEGRATION_GUIDE.md
modules/home/ROUTING.md
modules/home/models/
modules/home/models/DashboardModel.php
modules/hr/
modules/hr/index.php
modules/hr/controllers/
modules/hr/controllers/ProcessPayrollController.php
modules/hr/database/
modules/hr/database/schema.sql
modules/hr/models/
modules/hr/models/HRModel.php
modules/inventory/
modules/inventory/add.php
modules/inventory/index.php
modules/inventory/api/
modules/inventory/api/check-sku.php
modules/inventory/api/search.php
modules/inventory/controllers/
modules/inventory/controllers/AddProductController.php
modules/inventory/controllers/DeleteProductController.php
modules/inventory/controllers/EditProductController.php
modules/inventory/database/
modules/inventory/database/schema.sql
modules/inventory/models/
modules/inventory/models/InventoryModel.php
modules/procurement/
modules/procurement/index.php
modules/procurement/controllers/
modules/procurement/controllers/ProcessPurchaseController.php
modules/procurement/database/
modules/procurement/database/schema.sql
modules/procurement/models/
modules/procurement/models/ProcurementModel.php
modules/sales/
modules/sales/index.php
modules/sales/INTEGRATION_GUIDE.md
modules/sales/README.md
modules/sales/ROUTING.md
modules/sales/controllers/
modules/sales/controllers/ProcessSaleController.php
modules/sales/controllers/ViewInvoiceController.php
modules/sales/database/
modules/sales/database/schema.sql
modules/sales/models/
modules/sales/models/SalesModel.php
public/
public/.htaccess
public/index.php
public/assets/
public/assets/css/
public/assets/css/dashboard.css
public/assets/css/main.css
public/assets/css/style.css
public/assets/js/
public/assets/js/hr-payroll.js
public/assets/js/main.js
You have not created a todo list yet. Create one with `update_todo_list` if your task is complicated or involves multiple steps.
</environment_details>

---

**Assistant:**

I'll help you rewrite the `modules/sales/index.php` file to support both Cash Sales and B2B Credit Customers. Let me start by examining the current file and related models.

<read_file>
<args>
<file>
<path>modules/sales/index.php</path>
</file>
<file>
<path>modules/customers/models/CustomerModel.php</path>
</file>
<file>
<path>modules/sales/models/SalesModel.php</path>
</file>
<file>
<path>modules/inventory/models/InventoryModel.php</path>
</file>
</args>
</read_file>

---

**User:**

[read_file for 4 files] Result:
<files>
<file><path>modules/sales/index.php</path>
<content lines="1-541">
  1 | <?php
  2 | /**
  3 |  * Sales Module - Main Dashboard View
  4 |  *
  5 |  * Displays new cash sale form with dynamic product selection and customer details.
  6 |  * Implements real-time calculations, stock validation, and seamless multi-module integration.
  7 |  *
  8 |  * Features:
  9 |  * - Dynamic item rows with add/remove functionality
 10 |  * - Real-time price and total calculations
 11 |  * - Stock availability validation
 12 |  * - CSRF protection
 13 |  * - Responsive mobile-first design
 14 |  *
 15 |  * @package AmenERP\Modules\Sales
 16 |  * @author Bob
 17 |  * @version 1.0.1
 18 |  */
 19 | 
 20 | declare(strict_types=1);
 21 | 
 22 | // ============================================================================
 23 | // BOOTSTRAP: Core Configuration & Dependencies
 24 | // ============================================================================
 25 | 
 26 | /**
 27 |  * Ensure session is active (should already be started by front controller)
 28 |  * This is a safety check for direct access scenarios
 29 |  */
 30 | if (session_status() === PHP_SESSION_NONE) {
 31 |     session_start();
 32 | }
 33 | 
 34 | /**
 35 |  * Load required core classes
 36 |  * These are autoloaded by the front controller, but we verify they exist
 37 |  */
 38 | if (!class_exists('Database')) {
 39 |     require_once __DIR__ . '/../../core/Database.php';
 40 | }
 41 | 
 42 | if (!class_exists('Csrf')) {
 43 |     require_once __DIR__ . '/../../core/Csrf.php';
 44 | }
 45 | 
 46 | /**
 47 |  * Load required models
 48 |  * SalesModel: Handles sales order creation and retrieval
 49 |  * InventoryModel: Provides product data for the sales form
 50 |  */
 51 | require_once __DIR__ . '/models/SalesModel.php';
 52 | require_once __DIR__ . '/../inventory/models/InventoryModel.php';
 53 | 
 54 | // ============================================================================
 55 | // DATA INITIALIZATION
 56 | // ============================================================================
 57 | 
 58 | /**
 59 |  * Initialize model instances
 60 |  */
 61 | $salesModel = new SalesModel();
 62 | $inventoryModel = new InventoryModel();
 63 | 
 64 | /**
 65 |  * Fetch active products for the sales form
 66 |  * Only active products can be sold
 67 |  */
 68 | $allProducts = $inventoryModel->getAllProducts();
 69 | $products = array_filter($allProducts, function($product) {
 70 |     return isset($product['status']) && $product['status'] === 'active';
 71 | });
 72 | 
 73 | /**
 74 |  * Fetch recent sales orders for the dashboard table
 75 |  * Limited to 10 most recent orders
 76 |  */
 77 | $recentOrders = $salesModel->getAllSalesOrders(10);
 78 | 
 79 | /**
 80 |  * Fetch sales statistics for the metrics cards
 81 |  * Includes total orders, revenue, and average order value
 82 |  */
 83 | $salesStats = $salesModel->getSalesStats();
 84 | 
 85 | /**
 86 |  * Generate CSRF token for form security
 87 |  * Protects against Cross-Site Request Forgery attacks
 88 |  */
 89 | $csrfToken = Csrf::generateToken();
 90 | 
 91 | // ============================================================================
 92 | // HELPER FUNCTIONS
 93 | // ============================================================================
 94 | 
 95 | /**
 96 |  * Format currency values for display
 97 |  *
 98 |  * @param float $amount The amount to format
 99 |  * @return string Formatted currency string (e.g., "$1,234.56")
100 |  */
101 | function formatCurrency(float $amount): string
102 | {
103 |     return '$' . number_format($amount, 2);
104 | }
105 | 
106 | /**
107 |  * Format integer numbers with thousands separator
108 |  *
109 |  * @param int $number The number to format
110 |  * @return string Formatted number string (e.g., "1,234")
111 |  */
112 | function formatNumber(int $number): string
113 | {
114 |     return number_format($number);
115 | }
116 | ?>
117 | 
118 | <div class="module-container sales-module">
119 |     <?php if (isset($_SESSION['success'])): ?>
120 |         <div class="alert alert-success">
121 |             <?php
122 |             echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
123 |             unset($_SESSION['success']);
124 |             ?>
125 |         </div>
126 |     <?php endif; ?>
127 |     
128 |     <?php if (isset($_SESSION['error'])): ?>
129 |         <div class="alert alert-error">
130 |             <?php
131 |             echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
132 |             unset($_SESSION['error']);
133 |             ?>
134 |         </div>
135 |     <?php endif; ?>
136 | 
137 |     <div class="module-header">
138 |         <h2>Sales & Invoicing</h2>
139 |     </div>
140 | 
141 |     <!-- Sales Statistics Cards -->
142 |     <div class="metrics-grid">
143 |         <div class="metric-card">
144 |             <div class="metric-icon">📊</div>
145 |             <div class="metric-content">
146 |                 <div class="metric-label">Total Orders</div>
147 |                 <div class="metric-value"><?php echo formatNumber($salesStats['total_orders']); ?></div>
148 |             </div>
149 |         </div>
150 |         
151 |         <div class="metric-card">
152 |             <div class="metric-icon">💰</div>
153 |             <div class="metric-content">
154 |                 <div class="metric-label">Total Revenue</div>
155 |                 <div class="metric-value"><?php echo formatCurrency($salesStats['total_revenue']); ?></div>
156 |             </div>
157 |         </div>
158 |         
159 |         <div class="metric-card">
160 |             <div class="metric-icon">📈</div>
161 |             <div class="metric-content">
162 |                 <div class="metric-label">Average Order Value</div>
163 |                 <div class="metric-value"><?php echo formatCurrency($salesStats['average_order_value']); ?></div>
164 |             </div>
165 |         </div>
166 |     </div>
167 | 
168 |     <!-- New Cash Sale Form -->
169 |     <section class="form-section">
170 |         <h3>New Cash Sale</h3>
171 |         <p class="form-description">Create a new sales invoice. Stock levels will be automatically updated and the transaction will be recorded in your accounts.</p>
172 |         
173 |         <form id="salesForm" class="sales-form" method="POST" action="<?php echo BASE_URL; ?>/sales/checkout">
174 |             <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
175 |             
176 |             <!-- Customer Information -->
177 |             <div class="form-group">
178 |                 <label for="customer_name" class="form-label">
179 |                     Customer Name <span class="required">*</span>
180 |                 </label>
181 |                 <input 
182 |                     type="text" 
183 |                     id="customer_name" 
184 |                     name="customer_name" 
185 |                     class="form-input" 
186 |                     placeholder="Enter customer name"
187 |                     required
188 |                     minlength="2"
189 |                     maxlength="255"
190 |                 >
191 |             </div>
192 | 
193 |             <!-- Sale Items Section -->
194 |             <div class="sale-items-section">
195 |                 <div class="section-header">
196 |                     <h4>Sale Items</h4>
197 |                     <button type="button" id="addItemBtn" class="btn btn-secondary">+ Add Item</button>
198 |                 </div>
199 | 
200 |                 <div id="saleItemsContainer">
201 |                     <!-- Initial item row -->
202 |                     <div class="sale-item-row" data-item-index="0">
203 |                         <div class="form-group">
204 |                             <label class="form-label">Product <span class="required">*</span></label>
205 |                             <select name="items[0][product_id]" class="form-select product-select" required data-item-index="0">
206 |                                 <option value="">Select a product</option>
207 |                                 <?php foreach ($products as $product): ?>
208 |                                     <option 
209 |                                         value="<?php echo (int)$product['id']; ?>"
210 |                                         data-price="<?php echo (float)$product['unit_price']; ?>"
211 |                                         data-stock="<?php echo (int)$product['quantity']; ?>"
212 |                                         data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
213 |                                     >
214 |                                         <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> 
215 |                                         (<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>) 
216 |                                         - Stock: <?php echo (int)$product['quantity']; ?> 
217 |                                         - <?php echo formatCurrency((float)$product['unit_price']); ?>
218 |                                     </option>
219 |                                 <?php endforeach; ?>
220 |                             </select>
221 |                         </div>
222 | 
223 |                         <div class="form-group">
224 |                             <label class="form-label">Quantity <span class="required">*</span></label>
225 |                             <input 
226 |                                 type="number" 
227 |                                 name="items[0][quantity]" 
228 |                                 class="form-input quantity-input" 
229 |                                 min="1" 
230 |                                 value="1" 
231 |                                 required
232 |                                 data-item-index="0"
233 |                             >
234 |                             <small class="stock-info" data-item-index="0"></small>
235 |                         </div>
236 | 
237 |                         <div class="form-group">
238 |                             <label class="form-label">Unit Price <span class="required">*</span></label>
239 |                             <input 
240 |                                 type="number" 
241 |                                 name="items[0][unit_price]" 
242 |                                 class="form-input price-input" 
243 |                                 step="0.01" 
244 |                                 min="0.01" 
245 |                                 required
246 |                                 readonly
247 |                                 data-item-index="0"
248 |                             >
249 |                         </div>
250 | 
251 |                         <div class="form-group">
252 |                             <label class="form-label">Line Total</label>
253 |                             <input 
254 |                                 type="text" 
255 |                                 class="form-input line-total" 
256 |                                 readonly
257 |                                 data-item-index="0"
258 |                                 value="$0.00"
259 |                             >
260 |                         </div>
261 | 
262 |                         <div class="form-group item-actions">
263 |                             <button type="button" class="btn btn-danger btn-small remove-item-btn hidden" data-item-index="0">Remove</button>
264 |                         </div>
265 |                     </div>
266 |                 </div>
267 | 
268 |                 <!-- Total Section -->
269 |                 <div class="total-section">
270 |                     <div class="total-row">
271 |                         <span class="total-label">Total Amount:</span>
272 |                         <span id="totalAmount" class="total-value">$0.00</span>
273 |                     </div>
274 |                 </div>
275 |             </div>
276 | 
277 |             <!-- Form Actions -->
278 |             <div class="form-actions">
279 |                 <button type="submit" class="btn btn-primary btn-large">
280 |                     <span class="btn-icon">💳</span>
281 |                     Complete Sale
282 |                 </button>
283 |                 <button type="reset" class="btn btn-secondary" id="resetFormBtn">
284 |                     Clear Form
285 |                 </button>
286 |             </div>
287 |         </form>
288 |     </section>
289 | 
290 |     <!-- Recent Sales Orders -->
291 |     <section class="table-section">
292 |         <h3>Recent Sales Orders</h3>
293 |         
294 |         <?php if (empty($recentOrders)): ?>
295 |             <div class="empty-state">
296 |                 <p>No sales orders yet. Create your first sale above!</p>
297 |             </div>
298 |         <?php else: ?>
299 |             <div class="table-responsive">
300 |                 <table class="data-table">
301 |                     <thead>
302 |                         <tr>
303 |                             <th>Invoice #</th>
304 |                             <th>Customer</th>
305 |                             <th>Total Amount</th>
306 |                             <th>Date</th>
307 |                             <th>Actions</th>
308 |                         </tr>
309 |                     </thead>
310 |                     <tbody>
311 |                         <?php foreach ($recentOrders as $order): ?>
312 |                             <tr>
313 |                                 <td>
314 |                                     <strong><?php echo htmlspecialchars($order['invoice_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
315 |                                 </td>
316 |                                 <td><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
317 |                                 <td class="amount"><?php echo formatCurrency($order['total_amount']); ?></td>
318 |                                 <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
319 |                                 <td>
320 |                                     <button 
321 |                                         class="btn btn-small btn-secondary view-order-btn" 
322 |                                         data-order-id="<?php echo (int)$order['id']; ?>"
323 |                                     >
324 |                                         View Details
325 |                                     </button>
326 |                                 </td>
327 |                             </tr>
328 |                         <?php endforeach; ?>
329 |                     </tbody>
330 |                 </table>
331 |             </div>
332 |         <?php endif; ?>
333 |     </section>
334 | </div>
335 | 
336 | <!-- Sales Module JavaScript -->
337 | <script type="module">
338 |     // Sales form management
339 |     let itemIndex = 1;
340 |     const saleItemsContainer = document.getElementById('saleItemsContainer');
341 |     const addItemBtn = document.getElementById('addItemBtn');
342 |     const totalAmountElement = document.getElementById('totalAmount');
343 |     const salesForm = document.getElementById('salesForm');
344 | 
345 |     // Product data from PHP - ensure all properties are properly typed
346 |     const products = <?php echo json_encode(array_values(array_map(function($p) {
347 |         return [
348 |             'id' => (int)$p['id'],
349 |             'name' => $p['name'],
350 |             'sku' => $p['sku'],
351 |             'quantity' => (int)$p['quantity'],
352 |             'unit_price' => (float)$p['unit_price']
353 |         ];
354 |     }, $products))); ?>;
355 | 
356 |     // Add new item row
357 |     addItemBtn.addEventListener('click', () => {
358 |         const newRow = createItemRow(itemIndex);
359 |         saleItemsContainer.insertAdjacentHTML('beforeend', newRow);
360 |         attachItemRowListeners(itemIndex);
361 |         updateRemoveButtons();
362 |         itemIndex++;
363 |     });
364 | 
365 |     // Create item row HTML
366 |     function createItemRow(index) {
367 |         return `
368 |             <div class="sale-item-row" data-item-index="${index}">
369 |                 <div class="form-group">
370 |                     <label class="form-label">Product <span class="required">*</span></label>
371 |                     <select name="items[${index}][product_id]" class="form-select product-select" required data-item-index="${index}">
372 |                         <option value="">Select a product</option>
373 |                         ${products.map(p => `
374 |                             <option 
375 |                                 value="${p.id}"
376 |                                 data-price="${p.unit_price}"
377 |                                 data-stock="${p.quantity}"
378 |                                 data-name="${escapeHtml(p.name)}"
379 |                             >
380 |                                 ${escapeHtml(p.name)} (${escapeHtml(p.sku)}) - Stock: ${p.quantity} - $${p.unit_price.toFixed(2)}
381 |                             </option>
382 |                         `).join('')}
383 |                     </select>
384 |                 </div>
385 |                 <div class="form-group">
386 |                     <label class="form-label">Quantity <span class="required">*</span></label>
387 |                     <input type="number" name="items[${index}][quantity]" class="form-input quantity-input" min="1" value="1" required data-item-index="${index}">
388 |                     <small class="stock-info" data-item-index="${index}"></small>
389 |                 </div>
390 |                 <div class="form-group">
391 |                     <label class="form-label">Unit Price <span class="required">*</span></label>
392 |                     <input type="number" name="items[${index}][unit_price]" class="form-input price-input" step="0.01" min="0.01" required readonly data-item-index="${index}">
393 |                 </div>
394 |                 <div class="form-group">
395 |                     <label class="form-label">Line Total</label>
396 |                     <input type="text" class="form-input line-total" readonly data-item-index="${index}" value="$0.00">
397 |                 </div>
398 |                 <div class="form-group item-actions">
399 |                     <button type="button" class="btn btn-danger btn-small remove-item-btn" data-item-index="${index}">Remove</button>
400 |                 </div>
401 |             </div>
402 |         `;
403 |     }
404 | 
405 |     // Escape HTML for security
406 |     function escapeHtml(text) {
407 |         const div = document.createElement('div');
408 |         div.textContent = text;
409 |         return div.innerHTML;
410 |     }
411 | 
412 |     // Attach listeners to item row
413 |     function attachItemRowListeners(index) {
414 |         const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
415 |         const productSelect = row.querySelector('.product-select');
416 |         const quantityInput = row.querySelector('.quantity-input');
417 |         const removeBtn = row.querySelector('.remove-item-btn');
418 | 
419 |         productSelect.addEventListener('change', () => updateLineTotal(index));
420 |         quantityInput.addEventListener('input', () => updateLineTotal(index));
421 |         removeBtn.addEventListener('click', () => removeItemRow(index));
422 |     }
423 | 
424 |     // Update line total calculation
425 |     function updateLineTotal(index) {
426 |         const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
427 |         const productSelect = row.querySelector('.product-select');
428 |         const quantityInput = row.querySelector('.quantity-input');
429 |         const priceInput = row.querySelector('.price-input');
430 |         const lineTotalInput = row.querySelector('.line-total');
431 |         const stockInfo = row.querySelector('.stock-info');
432 | 
433 |         const selectedOption = productSelect.options[productSelect.selectedIndex];
434 |         
435 |         if (selectedOption.value) {
436 |             // Safely parse data attributes with fallback values
437 |             const price = parseFloat(selectedOption.dataset.price) || 0;
438 |             const stock = parseInt(selectedOption.dataset.stock) || 0;
439 |             const quantity = parseInt(quantityInput.value) || 0;
440 | 
441 |             priceInput.value = price.toFixed(2);
442 |             const lineTotal = price * quantity;
443 |             lineTotalInput.value = `$${lineTotal.toFixed(2)}`;
444 | 
445 |             // Stock validation with CSS classes
446 |             if (quantity > stock) {
447 |                 stockInfo.textContent = `⚠️ Only ${stock} available`;
448 |                 stockInfo.classList.remove('stock-valid');
449 |                 stockInfo.classList.add('stock-invalid');
450 |                 quantityInput.setCustomValidity('Insufficient stock');
451 |             } else {
452 |                 stockInfo.textContent = `✓ ${stock} available`;
453 |                 stockInfo.classList.remove('stock-invalid');
454 |                 stockInfo.classList.add('stock-valid');
455 |                 quantityInput.setCustomValidity('');
456 |             }
457 |         } else {
458 |             priceInput.value = '';
459 |             lineTotalInput.value = '$0.00';
460 |             stockInfo.textContent = '';
461 |             stockInfo.classList.remove('stock-valid', 'stock-invalid');
462 |         }
463 | 
464 |         updateTotalAmount();
465 |     }
466 | 
467 |     // Update total amount
468 |     function updateTotalAmount() {
469 |         let total = 0;
470 |         document.querySelectorAll('.line-total').forEach(input => {
471 |             const value = parseFloat(input.value.replace('$', '')) || 0;
472 |             total += value;
473 |         });
474 |         totalAmountElement.textContent = `$${total.toFixed(2)}`;
475 |     }
476 | 
477 |     // Remove item row
478 |     function removeItemRow(index) {
479 |         const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
480 |         row.remove();
481 |         updateTotalAmount();
482 |         updateRemoveButtons();
483 |     }
484 | 
485 |     // Update remove button visibility using CSS classes
486 |     function updateRemoveButtons() {
487 |         const rows = document.querySelectorAll('.sale-item-row');
488 |         rows.forEach((row, idx) => {
489 |             const removeBtn = row.querySelector('.remove-item-btn');
490 |             if (rows.length > 1) {
491 |                 removeBtn.classList.remove('hidden');
492 |             } else {
493 |                 removeBtn.classList.add('hidden');
494 |             }
495 |         });
496 |     }
497 | 
498 |     // Initialize first row listeners
499 |     attachItemRowListeners(0);
500 | 
501 |     // Form reset handler
502 |     document.getElementById('resetFormBtn').addEventListener('click', () => {
503 |         // Remove all rows except first
504 |         const rows = document.querySelectorAll('.sale-item-row');
505 |         rows.forEach((row, idx) => {
506 |             if (idx > 0) row.remove();
507 |         });
508 |         itemIndex = 1;
509 |         updateTotalAmount();
510 |         updateRemoveButtons();
511 |     });
512 | 
513 |     // Form submission validation
514 |     salesForm.addEventListener('submit', (e) => {
515 |         const rows = document.querySelectorAll('.sale-item-row');
516 |         let hasError = false;
517 | 
518 |         rows.forEach(row => {
519 |             const productSelect = row.querySelector('.product-select');
520 |             if (!productSelect.value) {
521 |                 hasError = true;
522 |                 alert('Please select a product for all items');
523 |                 e.preventDefault();
524 |             }
525 |         });
526 |     });
527 | 
528 |     // View order details
529 |     document.addEventListener('click', (e) => {
530 |         if (e.target.closest('.view-order-btn')) {
531 |             const orderId = e.target.closest('.view-order-btn').dataset.orderId;
532 |             if (orderId) {
533 |                 // Navigate to order details page
534 |                 window.location.href = `<?php echo BASE_URL; ?>/sales/orders/${orderId}`;
535 |             }
536 |         }
537 |     });
538 | </script>
539 | 
540 | <?php
541 | // Made with Bob
</content>
</file>
<file><path>modules/customers/models/CustomerModel.php</path>
<content lines="1-761">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * CustomerModel Class
  7 |  * 
  8 |  * Handles all database operations for the customer accounts and receivables module.
  9 |  * Implements ACID-compliant transactions for customer payment processing with
 10 |  * automated integration across multiple modules:
 11 |  * - Updates customer outstanding balances
 12 |  * - Records payment receipts
 13 |  * - Creates financial transactions (double-entry bookkeeping)
 14 |  * - Updates account balances in the general ledger
 15 |  * 
 16 |  * All operations are wrapped in database transactions to ensure data integrity.
 17 |  * If any step fails, the entire operation is rolled back.
 18 |  * 
 19 |  * @package AmenERP\Modules\Customers
 20 |  * @author Bob
 21 |  * @version 1.0.0
 22 |  */
 23 | class CustomerModel
 24 | {
 25 |     /**
 26 |      * Database instance
 27 |      * 
 28 |      * @var Database
 29 |      */
 30 |     private Database $db;
 31 | 
 32 |     /**
 33 |      * Accounts Receivable Asset Account ID
 34 |      * This account tracks money owed to us by customers
 35 |      * 
 36 |      * @var int
 37 |      */
 38 |     private const ACCOUNTS_RECEIVABLE_ID = 5;
 39 | 
 40 |     /**
 41 |      * Cash Account ID (Primary Liquid Asset)
 42 |      * This account tracks cash received from customers
 43 |      * 
 44 |      * @var int
 45 |      */
 46 |     private const CASH_ACCOUNT_ID = 1;
 47 | 
 48 |     /**
 49 |      * Constructor
 50 |      * Initializes database connection using singleton pattern
 51 |      */
 52 |     public function __construct()
 53 |     {
 54 |         $this->db = Database::getInstance();
 55 |     }
 56 | 
 57 |     /**
 58 |      * Record a customer payment with full transactional integrity
 59 |      * 
 60 |      * This is the core payment processing method that executes a complete
 61 |      * customer payment transaction across multiple modules:
 62 |      * 
 63 |      * Action A: Validate payment amount and customer balance
 64 |      * Action B: Update customer outstanding_balance (reduce receivables)
 65 |      * Action C: Insert payment receipt record for audit trail
 66 |      * Action D: Create financial transaction in general ledger
 67 |      * Action E: Update account balances (Cash and Accounts Receivable)
 68 |      * 
 69 |      * All operations are wrapped in a database transaction. If any step fails,
 70 |      * the entire operation is rolled back to maintain data integrity.
 71 |      * 
 72 |      * @param int $customerId Customer ID receiving the payment
 73 |      * @param float $amount Payment amount received (must be positive)
 74 |      * @param string $method Payment method (cash, check, bank_transfer, credit_card, etc.)
 75 |      * @param string|null $reference Optional payment reference (check number, transaction ID, etc.)
 76 |      * @param string|null $notes Optional payment notes or remarks
 77 |      * @return array<string, mixed> Result array with keys:
 78 |      *                              - success: bool
 79 |      *                              - receipt_id: int (if successful)
 80 |      *                              - receipt_number: string (if successful)
 81 |      *                              - message: string
 82 |      * @throws PDOException If database operation fails
 83 |      * 
 84 |      * @example
 85 |      * $model = new CustomerModel();
 86 |      * $result = $model->recordCustomerPayment(
 87 |      *     customerId: 1,
 88 |      *     amount: 5000.00,
 89 |      *     method: 'bank_transfer',
 90 |      *     reference: 'TXN-123456',
 91 |      *     notes: 'Payment for Invoice INV-2026-0001'
 92 |      * );
 93 |      * 
 94 |      * if ($result['success']) {
 95 |      *     echo "Payment recorded: " . $result['receipt_number'];
 96 |      * } else {
 97 |      *     echo "Error: " . $result['message'];
 98 |      * }
 99 |      */
100 |     public function recordCustomerPayment(
101 |         int $customerId,
102 |         float $amount,
103 |         string $method,
104 |         ?string $reference = null,
105 |         ?string $notes = null
106 |     ): array {
107 |         try {
108 |             // Start database transaction for ACID compliance
109 |             $this->db->beginTransaction();
110 | 
111 |             // ACTION A: Validate payment amount
112 |             if ($amount <= 0) {
113 |                 throw new Exception('Payment amount must be greater than zero');
114 |             }
115 | 
116 |             // ACTION A: Get customer details and validate balance
117 |             $customerSql = "
118 |                 SELECT 
119 |                     id,
120 |                     customer_code,
121 |                     company_name,
122 |                     outstanding_balance,
123 |                     status
124 |                 FROM customers
125 |                 WHERE id = :customer_id
126 |                 FOR UPDATE
127 |             ";
128 | 
129 |             $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
130 |             $customer = $customerStmt->fetch();
131 | 
132 |             if (!$customer) {
133 |                 throw new Exception("Customer ID {$customerId} not found");
134 |             }
135 | 
136 |             // Validate customer status
137 |             if ($customer['status'] !== 'active') {
138 |                 throw new Exception(
139 |                     "Cannot process payment: Customer account is {$customer['status']}"
140 |                 );
141 |             }
142 | 
143 |             $outstandingBalance = (float) $customer['outstanding_balance'];
144 | 
145 |             // Validate payment doesn't exceed outstanding balance
146 |             if ($amount > $outstandingBalance) {
147 |                 throw new Exception(
148 |                     "Payment amount ({$amount}) exceeds outstanding balance ({$outstandingBalance}). " .
149 |                     "Maximum payment allowed: {$outstandingBalance}"
150 |                 );
151 |             }
152 | 
153 |             // ACTION B: Update customer outstanding balance (reduce receivables)
154 |             $updateCustomerSql = "
155 |                 UPDATE customers
156 |                 SET 
157 |                     outstanding_balance = outstanding_balance - :amount,
158 |                     updated_at = NOW()
159 |                 WHERE id = :customer_id
160 |             ";
161 | 
162 |             $this->db->query($updateCustomerSql, [
163 |                 'amount' => $amount,
164 |                 'customer_id' => $customerId
165 |             ]);
166 | 
167 |             // ACTION C: Generate unique receipt number and insert receipt record
168 |             $receiptNumber = $this->generateReceiptNumber();
169 | 
170 |             $receiptSql = "
171 |                 INSERT INTO customer_receipts (
172 |                     customer_id,
173 |                     receipt_number,
174 |                     amount_paid,
175 |                     payment_method,
176 |                     payment_reference,
177 |                     notes,
178 |                     processed_at,
179 |                     created_at,
180 |                     updated_at
181 |                 ) VALUES (
182 |                     :customer_id,
183 |                     :receipt_number,
184 |                     :amount_paid,
185 |                     :payment_method,
186 |                     :payment_reference,
187 |                     :notes,
188 |                     NOW(),
189 |                     NOW(),
190 |                     NOW()
191 |                 )
192 |             ";
193 | 
194 |             $this->db->query($receiptSql, [
195 |                 'customer_id' => $customerId,
196 |                 'receipt_number' => $receiptNumber,
197 |                 'amount_paid' => $amount,
198 |                 'payment_method' => $method,
199 |                 'payment_reference' => $reference,
200 |                 'notes' => $notes
201 |             ]);
202 | 
203 |             $receiptId = (int) $this->db->lastInsertId();
204 | 
205 |             // ACTION D: Record financial transaction in general ledger
206 |             // Money flows: DEBIT Cash (increase asset), CREDIT Accounts Receivable (decrease asset)
207 |             $transactionDescription = sprintf(
208 |                 "Customer Payment: %s - %s - Receipt: %s",
209 |                 $customer['customer_code'],
210 |                 $customer['company_name'],
211 |                 $receiptNumber
212 |             );
213 |             $transactionDate = date('Y-m-d');
214 | 
215 |             // Insert master transaction record
216 |             $transactionSql = "
217 |                 INSERT INTO transactions (
218 |                     description,
219 |                     reference_number,
220 |                     transaction_date,
221 |                     created_at,
222 |                     updated_at
223 |                 ) VALUES (
224 |                     :description,
225 |                     :reference_number,
226 |                     :transaction_date,
227 |                     NOW(),
228 |                     NOW()
229 |                 )
230 |             ";
231 | 
232 |             $this->db->query($transactionSql, [
233 |                 'description' => $transactionDescription,
234 |                 'reference_number' => $receiptNumber,
235 |                 'transaction_date' => $transactionDate
236 |             ]);
237 | 
238 |             $transactionId = (int) $this->db->lastInsertId();
239 | 
240 |             // Insert DEBIT entry for Cash account (money entering - positive asset increase)
241 |             $debitCashSql = "
242 |                 INSERT INTO ledger_entries (
243 |                     transaction_id,
244 |                     account_id,
245 |                     amount,
246 |                     created_at,
247 |                     updated_at
248 |                 ) VALUES (
249 |                     :transaction_id,
250 |                     :account_id,
251 |                     :amount,
252 |                     NOW(),
253 |                     NOW()
254 |                 )
255 |             ";
256 | 
257 |             $this->db->query($debitCashSql, [
258 |                 'transaction_id' => $transactionId,
259 |                 'account_id' => self::CASH_ACCOUNT_ID,
260 |                 'amount' => $amount // Positive for debit (cash increase)
261 |             ]);
262 | 
263 |             // Insert CREDIT entry for Accounts Receivable (money leaving - negative asset decrease)
264 |             $creditReceivableSql = "
265 |                 INSERT INTO ledger_entries (
266 |                     transaction_id,
267 |                     account_id,
268 |                     amount,
269 |                     created_at,
270 |                     updated_at
271 |                 ) VALUES (
272 |                     :transaction_id,
273 |                     :account_id,
274 |                     :amount,
275 |                     NOW(),
276 |                     NOW()
277 |                 )
278 |             ";
279 | 
280 |             $this->db->query($creditReceivableSql, [
281 |                 'transaction_id' => $transactionId,
282 |                 'account_id' => self::ACCOUNTS_RECEIVABLE_ID,
283 |                 'amount' => -$amount // Negative for credit (receivables decrease)
284 |             ]);
285 | 
286 |             // ACTION E: Update account balances in the accounts table
287 |             // Update Cash account balance (increase)
288 |             $updateCashAccountSql = "
289 |                 UPDATE accounts
290 |                 SET 
291 |                     balance = balance + :amount,
292 |                     updated_at = NOW()
293 |                 WHERE id = :account_id
294 |             ";
295 | 
296 |             $this->db->query($updateCashAccountSql, [
297 |                 'amount' => $amount,
298 |                 'account_id' => self::CASH_ACCOUNT_ID
299 |             ]);
300 | 
301 |             // Update Accounts Receivable balance (decrease)
302 |             $updateReceivableAccountSql = "
303 |                 UPDATE accounts
304 |                 SET 
305 |                     balance = balance - :amount,
306 |                     updated_at = NOW()
307 |                 WHERE id = :account_id
308 |             ";
309 | 
310 |             $this->db->query($updateReceivableAccountSql, [
311 |                 'amount' => $amount,
312 |                 'account_id' => self::ACCOUNTS_RECEIVABLE_ID
313 |             ]);
314 | 
315 |             // Commit the transaction - all operations succeeded
316 |             $this->db->commit();
317 | 
318 |             return [
319 |                 'success' => true,
320 |                 'receipt_id' => $receiptId,
321 |                 'receipt_number' => $receiptNumber,
322 |                 'customer_code' => $customer['customer_code'],
323 |                 'company_name' => $customer['company_name'],
324 |                 'amount_paid' => $amount,
325 |                 'new_balance' => $outstandingBalance - $amount,
326 |                 'message' => 'Payment recorded successfully'
327 |             ];
328 | 
329 |         } catch (Exception $e) {
330 |             // Rollback on any error to maintain data integrity
331 |             $this->db->rollback();
332 | 
333 |             return [
334 |                 'success' => false,
335 |                 'message' => $e->getMessage()
336 |             ];
337 |         }
338 |     }
339 | 
340 |     /**
341 |      * Generate a unique receipt number
342 |      * 
343 |      * Format: RCP-YYYY-NNNN (e.g., RCP-2026-0001)
344 |      * 
345 |      * @return string Unique receipt number
346 |      * @throws PDOException If query fails
347 |      */
348 |     private function generateReceiptNumber(): string
349 |     {
350 |         $year = date('Y');
351 |         $prefix = "RCP-{$year}-";
352 | 
353 |         // Get the highest receipt number for current year
354 |         $sql = "
355 |             SELECT receipt_number
356 |             FROM customer_receipts
357 |             WHERE receipt_number LIKE :prefix
358 |             ORDER BY receipt_number DESC
359 |             LIMIT 1
360 |         ";
361 | 
362 |         $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
363 |         $result = $stmt->fetch();
364 | 
365 |         if ($result) {
366 |             // Extract the numeric part and increment
367 |             $lastNumber = (int) substr($result['receipt_number'], -4);
368 |             $nextNumber = $lastNumber + 1;
369 |         } else {
370 |             // First receipt of the year
371 |             $nextNumber = 1;
372 |         }
373 | 
374 |         // Format with leading zeros (4 digits)
375 |         return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
376 |     }
377 | 
378 |     /**
379 |      * Get all customers with their current balances
380 |      * 
381 |      * Returns a list of all customers ordered by customer code.
382 |      * Useful for displaying customer lists and account summaries.
383 |      * 
384 |      * @param string|null $status Filter by status (active, inactive, suspended) or null for all
385 |      * @return array<int, array<string, mixed>> Array of customers
386 |      * @throws PDOException If query fails
387 |      * 
388 |      * @example
389 |      * $model = new CustomerModel();
390 |      * $customers = $model->getAllCustomers('active');
391 |      * foreach ($customers as $customer) {
392 |      *     echo $customer['customer_code'] . ': ' . $customer['company_name'];
393 |      * }
394 |      */
395 |     public function getAllCustomers(?string $status = null): array
396 |     {
397 |         $sql = "
398 |             SELECT 
399 |                 id,
400 |                 customer_code,
401 |                 company_name,
402 |                 contact_name,
403 |                 email,
404 |                 phone,
405 |                 address,
406 |                 credit_limit,
407 |                 outstanding_balance,
408 |                 status,
409 |                 created_at,
410 |                 updated_at
411 |             FROM customers
412 |         ";
413 | 
414 |         $params = [];
415 | 
416 |         if ($status !== null) {
417 |             $sql .= " WHERE status = :status";
418 |             $params['status'] = $status;
419 |         }
420 | 
421 |         $sql .= " ORDER BY customer_code ASC";
422 | 
423 |         $stmt = $this->db->query($sql, $params);
424 |         $results = $stmt->fetchAll();
425 | 
426 |         // Cast numeric values for consistency
427 |         return array_map(function ($row) {
428 |             return [
429 |                 'id' => (int) $row['id'],
430 |                 'customer_code' => $row['customer_code'],
431 |                 'company_name' => $row['company_name'],
432 |                 'contact_name' => $row['contact_name'],
433 |                 'email' => $row['email'],
434 |                 'phone' => $row['phone'],
435 |                 'address' => $row['address'],
436 |                 'credit_limit' => (float) $row['credit_limit'],
437 |                 'outstanding_balance' => (float) $row['outstanding_balance'],
438 |                 'credit_available' => (float) $row['credit_limit'] - (float) $row['outstanding_balance'],
439 |                 'status' => $row['status'],
440 |                 'created_at' => $row['created_at'],
441 |                 'updated_at' => $row['updated_at']
442 |             ];
443 |         }, $results);
444 |     }
445 | 
446 |     /**
447 |      * Get customer statement with transaction history
448 |      * 
449 |      * Returns detailed customer information along with all payment receipts
450 |      * and outstanding invoices for a complete account statement.
451 |      * 
452 |      * @param int $customerId Customer ID
453 |      * @return array<string, mixed>|null Customer statement data or null if not found
454 |      * @throws PDOException If query fails
455 |      * 
456 |      * @example
457 |      * $model = new CustomerModel();
458 |      * $statement = $model->getCustomerStatement(1);
459 |      * if ($statement) {
460 |      *     echo "Customer: " . $statement['company_name'];
461 |      *     echo "Balance: " . $statement['outstanding_balance'];
462 |      *     foreach ($statement['receipts'] as $receipt) {
463 |      *         echo $receipt['receipt_number'] . ': ' . $receipt['amount_paid'];
464 |      *     }
465 |      * }
466 |      */
467 |     public function getCustomerStatement(int $customerId): ?array
468 |     {
469 |         // Get customer details
470 |         $customerSql = "
471 |             SELECT 
472 |                 id,
473 |                 customer_code,
474 |                 company_name,
475 |                 contact_name,
476 |                 email,
477 |                 phone,
478 |                 address,
479 |                 credit_limit,
480 |                 outstanding_balance,
481 |                 status,
482 |                 created_at,
483 |                 updated_at
484 |             FROM customers
485 |             WHERE id = :customer_id
486 |         ";
487 | 
488 |         $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
489 |         $customer = $customerStmt->fetch();
490 | 
491 |         if (!$customer) {
492 |             return null;
493 |         }
494 | 
495 |         // Get payment receipts
496 |         $receiptsSql = "
497 |             SELECT 
498 |                 id,
499 |                 receipt_number,
500 |                 amount_paid,
501 |                 payment_method,
502 |                 payment_reference,
503 |                 notes,
504 |                 processed_at,
505 |                 created_at
506 |             FROM customer_receipts
507 |             WHERE customer_id = :customer_id
508 |             ORDER BY processed_at DESC
509 |         ";
510 | 
511 |         $receiptsStmt = $this->db->query($receiptsSql, ['customer_id' => $customerId]);
512 |         $receipts = $receiptsStmt->fetchAll();
513 | 
514 |         // Get linked sales orders (credit invoices)
515 |         $invoicesSql = "
516 |             SELECT 
517 |                 id,
518 |                 invoice_number,
519 |                 total_amount,
520 |                 created_at
521 |             FROM sales_orders
522 |             WHERE customer_id = :customer_id
523 |             ORDER BY created_at DESC
524 |         ";
525 | 
526 |         $invoicesStmt = $this->db->query($invoicesSql, ['customer_id' => $customerId]);
527 |         $invoices = $invoicesStmt->fetchAll();
528 | 
529 |         return [
530 |             'id' => (int) $customer['id'],
531 |             'customer_code' => $customer['customer_code'],
532 |             'company_name' => $customer['company_name'],
533 |             'contact_name' => $customer['contact_name'],
534 |             'email' => $customer['email'],
535 |             'phone' => $customer['phone'],
536 |             'address' => $customer['address'],
537 |             'credit_limit' => (float) $customer['credit_limit'],
538 |             'outstanding_balance' => (float) $customer['outstanding_balance'],
539 |             'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
540 |             'status' => $customer['status'],
541 |             'created_at' => $customer['created_at'],
542 |             'updated_at' => $customer['updated_at'],
543 |             'receipts' => array_map(function ($receipt) {
544 |                 return [
545 |                     'id' => (int) $receipt['id'],
546 |                     'receipt_number' => $receipt['receipt_number'],
547 |                     'amount_paid' => (float) $receipt['amount_paid'],
548 |                     'payment_method' => $receipt['payment_method'],
549 |                     'payment_reference' => $receipt['payment_reference'],
550 |                     'notes' => $receipt['notes'],
551 |                     'processed_at' => $receipt['processed_at'],
552 |                     'created_at' => $receipt['created_at']
553 |                 ];
554 |             }, $receipts),
555 |             'invoices' => array_map(function ($invoice) {
556 |                 return [
557 |                     'id' => (int) $invoice['id'],
558 |                     'invoice_number' => $invoice['invoice_number'],
559 |                     'total_amount' => (float) $invoice['total_amount'],
560 |                     'created_at' => $invoice['created_at']
561 |                 ];
562 |             }, $invoices),
563 |             'total_receipts' => count($receipts),
564 |             'total_invoices' => count($invoices)
565 |         ];
566 |     }
567 | 
568 |     /**
569 |      * Verify credit availability for a customer order
570 |      * 
571 |      * Checks if a customer has sufficient credit available to place an order
572 |      * of the specified amount. Returns detailed credit information.
573 |      * 
574 |      * @param int $customerId Customer ID
575 |      * @param float $orderTotal Proposed order total amount
576 |      * @return array<string, mixed> Credit verification result with keys:
577 |      *                              - approved: bool
578 |      *                              - credit_limit: float
579 |      *                              - outstanding_balance: float
580 |      *                              - credit_available: float
581 |      *                              - order_total: float
582 |      *                              - remaining_credit: float (after order)
583 |      *                              - message: string
584 |      * @throws PDOException If query fails
585 |      * 
586 |      * @example
587 |      * $model = new CustomerModel();
588 |      * $verification = $model->verifyCreditAvailability(1, 15000.00);
589 |      * if ($verification['approved']) {
590 |      *     echo "Credit approved. Remaining: " . $verification['remaining_credit'];
591 |      * } else {
592 |      *     echo "Credit denied: " . $verification['message'];
593 |      * }
594 |      */
595 |     public function verifyCreditAvailability(int $customerId, float $orderTotal): array
596 |     {
597 |         // Get customer credit information
598 |         $sql = "
599 |             SELECT 
600 |                 customer_code,
601 |                 company_name,
602 |                 credit_limit,
603 |                 outstanding_balance,
604 |                 status
605 |             FROM customers
606 |             WHERE id = :customer_id
607 |         ";
608 | 
609 |         $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
610 |         $customer = $stmt->fetch();
611 | 
612 |         if (!$customer) {
613 |             return [
614 |                 'approved' => false,
615 |                 'message' => "Customer ID {$customerId} not found"
616 |             ];
617 |         }
618 | 
619 |         // Check customer status
620 |         if ($customer['status'] !== 'active') {
621 |             return [
622 |                 'approved' => false,
623 |                 'customer_code' => $customer['customer_code'],
624 |                 'company_name' => $customer['company_name'],
625 |                 'message' => "Customer account is {$customer['status']}"
626 |             ];
627 |         }
628 | 
629 |         $creditLimit = (float) $customer['credit_limit'];
630 |         $outstandingBalance = (float) $customer['outstanding_balance'];
631 |         $creditAvailable = $creditLimit - $outstandingBalance;
632 |         $remainingCredit = $creditAvailable - $orderTotal;
633 | 
634 |         // Verify sufficient credit
635 |         if ($orderTotal > $creditAvailable) {
636 |             return [
637 |                 'approved' => false,
638 |                 'customer_code' => $customer['customer_code'],
639 |                 'company_name' => $customer['company_name'],
640 |                 'credit_limit' => $creditLimit,
641 |                 'outstanding_balance' => $outstandingBalance,
642 |                 'credit_available' => $creditAvailable,
643 |                 'order_total' => $orderTotal,
644 |                 'credit_shortage' => $orderTotal - $creditAvailable,
645 |                 'message' => sprintf(
646 |                     'Insufficient credit: Available %.2f, Required %.2f, Shortage %.2f',
647 |                     $creditAvailable,
648 |                     $orderTotal,
649 |                     $orderTotal - $creditAvailable
650 |                 )
651 |             ];
652 |         }
653 | 
654 |         return [
655 |             'approved' => true,
656 |             'customer_code' => $customer['customer_code'],
657 |             'company_name' => $customer['company_name'],
658 |             'credit_limit' => $creditLimit,
659 |             'outstanding_balance' => $outstandingBalance,
660 |             'credit_available' => $creditAvailable,
661 |             'order_total' => $orderTotal,
662 |             'remaining_credit' => $remainingCredit,
663 |             'message' => 'Credit approved'
664 |         ];
665 |     }
666 | 
667 |     /**
668 |      * Get customer by ID
669 |      * 
670 |      * @param int $customerId Customer ID
671 |      * @return array<string, mixed>|null Customer data or null if not found
672 |      * @throws PDOException If query fails
673 |      */
674 |     public function getCustomerById(int $customerId): ?array
675 |     {
676 |         $sql = "
677 |             SELECT 
678 |                 id,
679 |                 customer_code,
680 |                 company_name,
681 |                 contact_name,
682 |                 email,
683 |                 phone,
684 |                 address,
685 |                 credit_limit,
686 |                 outstanding_balance,
687 |                 status,
688 |                 created_at,
689 |                 updated_at
690 |             FROM customers
691 |             WHERE id = :customer_id
692 |         ";
693 | 
694 |         $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
695 |         $customer = $stmt->fetch();
696 | 
697 |         if (!$customer) {
698 |             return null;
699 |         }
700 | 
701 |         return [
702 |             'id' => (int) $customer['id'],
703 |             'customer_code' => $customer['customer_code'],
704 |             'company_name' => $customer['company_name'],
705 |             'contact_name' => $customer['contact_name'],
706 |             'email' => $customer['email'],
707 |             'phone' => $customer['phone'],
708 |             'address' => $customer['address'],
709 |             'credit_limit' => (float) $customer['credit_limit'],
710 |             'outstanding_balance' => (float) $customer['outstanding_balance'],
711 |             'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
712 |             'status' => $customer['status'],
713 |             'created_at' => $customer['created_at'],
714 |             'updated_at' => $customer['updated_at']
715 |         ];
716 |     }
717 | 
718 |     /**
719 |      * Get accounts receivable summary statistics
720 |      * 
721 |      * Returns aggregate statistics for all customer accounts including
722 |      * total receivables, total credit extended, and customer counts.
723 |      * 
724 |      * @return array<string, mixed> Receivables statistics
725 |      * @throws PDOException If query fails
726 |      * 
727 |      * @example
728 |      * $model = new CustomerModel();
729 |      * $stats = $model->getReceivablesStats();
730 |      * echo "Total Receivables: " . $stats['total_receivables'];
731 |      * echo "Active Customers: " . $stats['active_customers'];
732 |      */
733 |     public function getReceivablesStats(): array
734 |     {
735 |         $sql = "
736 |             SELECT 
737 |                 COUNT(*) AS total_customers,
738 |                 COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_customers,
739 |                 COALESCE(SUM(credit_limit), 0) AS total_credit_extended,
740 |                 COALESCE(SUM(outstanding_balance), 0) AS total_receivables,
741 |                 COALESCE(SUM(credit_limit - outstanding_balance), 0) AS total_credit_available
742 |             FROM customers
743 |         ";
744 | 
745 |         $stmt = $this->db->query($sql);
746 |         $result = $stmt->fetch();
747 | 
748 |         return [
749 |             'total_customers' => (int) $result['total_customers'],
750 |             'active_customers' => (int) $result['active_customers'],
751 |             'total_credit_extended' => (float) $result['total_credit_extended'],
752 |             'total_receivables' => (float) $result['total_receivables'],
753 |             'total_credit_available' => (float) $result['total_credit_available'],
754 |             'credit_utilization_percentage' => $result['total_credit_extended'] > 0
755 |                 ? round(($result['total_receivables'] / $result['total_credit_extended']) * 100, 2)
756 |                 : 0.00
757 |         ];
758 |     }
759 | }
760 | 
761 | // Made with Bob
</content>
</file>
<file><path>modules/sales/models/SalesModel.php</path>
<content lines="1-625">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * SalesModel Class
  7 |  *
  8 |  * Handles all database operations for the sales module with automated integration.
  9 |  * Implements ACID-compliant transactions that span multiple modules:
 10 |  * - Creates sales orders and line items
 11 |  * - Updates inventory stock levels automatically
 12 |  * - Records financial transactions directly (double-entry bookkeeping)
 13 |  * - Integrates with Customer Accounts & Receivables for credit sales
 14 |  *
 15 |  * All operations are wrapped in a single database transaction to ensure data integrity.
 16 |  * If any step fails, the entire operation is rolled back.
 17 |  *
 18 |  * @package AmenERP\Modules\Sales
 19 |  * @author Bob
 20 |  * @version 2.0.0
 21 |  */
 22 | class SalesModel
 23 | {
 24 |     /**
 25 |      * Database instance
 26 |      *
 27 |      * @var Database
 28 |      */
 29 |     private Database $db;
 30 | 
 31 |     /**
 32 |      * Default income account ID (General Sales Income)
 33 |      *
 34 |      * @var int
 35 |      */
 36 |     private const SALES_INCOME_ACCOUNT_ID = 3;
 37 | 
 38 |     /**
 39 |      * Default cash/bank account ID (Cash Safe or Bank Account)
 40 |      *
 41 |      * @var int
 42 |      */
 43 |     private const CASH_ACCOUNT_ID = 1;
 44 | 
 45 |     /**
 46 |      * Accounts Receivable account ID (for credit sales)
 47 |      *
 48 |      * @var int
 49 |      */
 50 |     private const ACCOUNTS_RECEIVABLE_ACCOUNT_ID = 5;
 51 | 
 52 |     /**
 53 |      * Constructor
 54 |      * Initializes database connection
 55 |      */
 56 |     public function __construct()
 57 |     {
 58 |         $this->db = Database::getInstance();
 59 |     }
 60 | 
 61 |     /**
 62 |      * Create a new sales order with automated multi-module integration
 63 |      * 
 64 |      * This is the core integration method that executes a complete sales transaction:
 65 |      * 
 66 |      * Action A: Insert master sales_order and line items into sales_items
 67 |      * Action B: Update inventory stock levels for each product sold
 68 |      * Action C: Record financial transaction via FinanceModel
 69 |      * 
 70 |      * All operations are wrapped in a database transaction. If any step fails
 71 |      * (e.g., insufficient stock, database error, credit limit exceeded), the entire 
 72 |      * operation is rolled back.
 73 |      * 
 74 |      * @param string $customerName Customer or buyer name
 75 |      * @param array<int, array<string, mixed>> $items Array of items with keys:
 76 |      *                                                - product_id: Product ID (int)
 77 |      *                                                - quantity: Quantity to sell (int)
 78 |      *                                                - unit_price: Price per unit (float)
 79 |      * @param int|null $customerId Optional customer ID for credit sales (B2B)
 80 |      * @param int|null $cashAccountId Optional cash account ID (defaults to CASH_ACCOUNT_ID)
 81 |      * @return array<string, mixed> Result array with keys:
 82 |      *                              - success: bool
 83 |      *                              - sales_order_id: int (if successful)
 84 |      *                              - invoice_number: string (if successful)
 85 |      *                              - message: string (error message if failed)
 86 |      * @throws PDOException If database operation fails
 87 |      * 
 88 |      * @example
 89 |      * // Cash sale
 90 |      * $model = new SalesModel();
 91 |      * $result = $model->createSalesOrder('John Doe', [
 92 |      *     ['product_id' => 1, 'quantity' => 2, 'unit_price' => 50.00],
 93 |      *     ['product_id' => 3, 'quantity' => 1, 'unit_price' => 150.00]
 94 |      * ]);
 95 |      * 
 96 |      * // Credit sale
 97 |      * $result = $model->createSalesOrder('ABC Trading Company', [
 98 |      *     ['product_id' => 1, 'quantity' => 10, 'unit_price' => 50.00]
 99 |      * ], 1); // Customer ID 1
100 |      * 
101 |      * if ($result['success']) {
102 |      *     echo "Order created: " . $result['invoice_number'];
103 |      * } else {
104 |      *     echo "Error: " . $result['message'];
105 |      * }
106 |      */
107 |     public function createSalesOrder(
108 |         string $customerName,
109 |         array $items,
110 |         ?int $customerId = null,
111 |         ?int $cashAccountId = null
112 |     ): array {
113 |         // Use default cash account if not specified
114 |         $cashAccountId = $cashAccountId ?? self::CASH_ACCOUNT_ID;
115 | 
116 |         try {
117 |             // Start database transaction for ACID compliance
118 |             $this->db->beginTransaction();
119 | 
120 |             // Validate that we have items to sell
121 |             if (empty($items)) {
122 |                 throw new Exception('Cannot create sales order: No items provided');
123 |             }
124 | 
125 |             // Calculate total amount and validate inventory availability
126 |             $totalAmount = 0.00;
127 |             foreach ($items as $item) {
128 |                 // Validate required fields
129 |                 if (!isset($item['product_id'], $item['quantity'], $item['unit_price'])) {
130 |                     throw new Exception('Invalid item data: Missing required fields');
131 |                 }
132 | 
133 |                 // Validate quantity is positive
134 |                 if ($item['quantity'] <= 0) {
135 |                     throw new Exception('Invalid quantity: Must be greater than zero');
136 |                 }
137 | 
138 |                 // Check inventory availability BEFORE creating the order
139 |                 $stockCheckSql = "SELECT quantity FROM products WHERE id = :product_id";
140 |                 $stockStmt = $this->db->query($stockCheckSql, ['product_id' => $item['product_id']]);
141 |                 $stockResult = $stockStmt->fetch();
142 | 
143 |                 if (!$stockResult) {
144 |                     throw new Exception("Product ID {$item['product_id']} not found");
145 |                 }
146 | 
147 |                 $availableStock = (int) $stockResult['quantity'];
148 |                 if ($availableStock < $item['quantity']) {
149 |                     throw new Exception(
150 |                         "Insufficient stock for product ID {$item['product_id']}: " .
151 |                         "Available: {$availableStock}, Required: {$item['quantity']}"
152 |                     );
153 |                 }
154 | 
155 |                 // Calculate line total
156 |                 $lineTotal = $item['quantity'] * $item['unit_price'];
157 |                 $totalAmount += $lineTotal;
158 |             }
159 | 
160 |             // If this is a credit sale, verify customer credit limit
161 |             if ($customerId !== null) {
162 |                 $customerSql = "
163 |                     SELECT 
164 |                         id,
165 |                         customer_code,
166 |                         company_name,
167 |                         credit_limit,
168 |                         outstanding_balance,
169 |                         status
170 |                     FROM customers
171 |                     WHERE id = :customer_id
172 |                     FOR UPDATE
173 |                 ";
174 | 
175 |                 $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
176 |                 $customer = $customerStmt->fetch();
177 | 
178 |                 if (!$customer) {
179 |                     throw new Exception("Customer ID {$customerId} not found");
180 |                 }
181 | 
182 |                 // Validate customer status
183 |                 if ($customer['status'] !== 'active') {
184 |                     throw new Exception(
185 |                         "Cannot process credit sale: Customer account is {$customer['status']}"
186 |                     );
187 |                 }
188 | 
189 |                 $creditLimit = (float) $customer['credit_limit'];
190 |                 $outstandingBalance = (float) $customer['outstanding_balance'];
191 |                 $creditAvailable = $creditLimit - $outstandingBalance;
192 | 
193 |                 // Check if adding this order would exceed credit limit
194 |                 if (($outstandingBalance + $totalAmount) > $creditLimit) {
195 |                     throw new Exception(
196 |                         "Credit limit exceeded: Available credit {$creditAvailable}, " .
197 |                         "Order total {$totalAmount}, Shortage " . 
198 |                         ($totalAmount - $creditAvailable)
199 |                     );
200 |                 }
201 |             }
202 | 
203 |             // Generate unique invoice number
204 |             $invoiceNumber = $this->generateInvoiceNumber();
205 | 
206 |             // ACTION A: Insert master sales order record
207 |             $orderSql = "
208 |                 INSERT INTO sales_orders (
209 |                     invoice_number,
210 |                     customer_name,
211 |                     customer_id,
212 |                     total_amount,
213 |                     created_at,
214 |                     updated_at
215 |                 ) VALUES (
216 |                     :invoice_number,
217 |                     :customer_name,
218 |                     :customer_id,
219 |                     :total_amount,
220 |                     NOW(),
221 |                     NOW()
222 |                 )
223 |             ";
224 | 
225 |             $this->db->query($orderSql, [
226 |                 'invoice_number' => $invoiceNumber,
227 |                 'customer_name' => $customerName,
228 |                 'customer_id' => $customerId,
229 |                 'total_amount' => $totalAmount
230 |             ]);
231 | 
232 |             // Get the sales order ID
233 |             $salesOrderId = (int) $this->db->lastInsertId();
234 | 
235 |             // ACTION A & B: Insert line items and update inventory
236 |             foreach ($items as $item) {
237 |                 $lineTotal = $item['quantity'] * $item['unit_price'];
238 | 
239 |                 // Insert sales item
240 |                 $itemSql = "
241 |                     INSERT INTO sales_items (
242 |                         sales_order_id,
243 |                         product_id,
244 |                         quantity,
245 |                         unit_price,
246 |                         line_total,
247 |                         created_at,
248 |                         updated_at
249 |                     ) VALUES (
250 |                         :sales_order_id,
251 |                         :product_id,
252 |                         :quantity,
253 |                         :unit_price,
254 |                         :line_total,
255 |                         NOW(),
256 |                         NOW()
257 |                     )
258 |                 ";
259 | 
260 |                 $this->db->query($itemSql, [
261 |                     'sales_order_id' => $salesOrderId,
262 |                     'product_id' => $item['product_id'],
263 |                     'quantity' => $item['quantity'],
264 |                     'unit_price' => $item['unit_price'],
265 |                     'line_total' => $lineTotal
266 |                 ]);
267 | 
268 |                 // ACTION B: Update inventory stock (deduct sold quantity)
269 |                 $inventoryUpdateSql = "
270 |                     UPDATE products
271 |                     SET 
272 |                         quantity = quantity - :sold_qty,
273 |                         updated_at = NOW()
274 |                     WHERE id = :product_id
275 |                 ";
276 | 
277 |                 $this->db->query($inventoryUpdateSql, [
278 |                     'sold_qty' => $item['quantity'],
279 |                     'product_id' => $item['product_id']
280 |                 ]);
281 | 
282 |                 // Verify stock didn't go negative (safety check)
283 |                 $verifyStockSql = "SELECT quantity FROM products WHERE id = :product_id";
284 |                 $verifyStmt = $this->db->query($verifyStockSql, ['product_id' => $item['product_id']]);
285 |                 $verifyResult = $verifyStmt->fetch();
286 | 
287 |                 if ($verifyResult && (int) $verifyResult['quantity'] < 0) {
288 |                     throw new Exception(
289 |                         "Stock validation failed for product ID {$item['product_id']}: " .
290 |                         "Stock would become negative"
291 |                     );
292 |                 }
293 |             }
294 | 
295 |             // If this is a credit sale, update customer outstanding balance
296 |             if ($customerId !== null) {
297 |                 $updateCustomerBalanceSql = "
298 |                     UPDATE customers
299 |                     SET 
300 |                         outstanding_balance = outstanding_balance + :total_amount,
301 |                         updated_at = NOW()
302 |                     WHERE id = :customer_id
303 |                 ";
304 | 
305 |                 $this->db->query($updateCustomerBalanceSql, [
306 |                     'total_amount' => $totalAmount,
307 |                     'customer_id' => $customerId
308 |                 ]);
309 |             }
310 | 
311 |             // ACTION C: Record financial transaction manually (avoid nested transactions)
312 |             // Determine which asset account to debit based on payment type
313 |             $debitAccountId = ($customerId !== null) 
314 |                 ? self::ACCOUNTS_RECEIVABLE_ACCOUNT_ID 
315 |                 : $cashAccountId;
316 | 
317 |             $transactionDescription = ($customerId !== null)
318 |                 ? "Credit Sale Invoice: {$invoiceNumber} - Customer: {$customerName}"
319 |                 : "Cash Sale Invoice: {$invoiceNumber} - Customer: {$customerName}";
320 |             
321 |             $transactionDate = date('Y-m-d');
322 | 
323 |             // Insert master transaction record
324 |             $financeSql = "
325 |                 INSERT INTO transactions (
326 |                     description,
327 |                     transaction_date,
328 |                     created_at,
329 |                     updated_at
330 |                 ) VALUES (
331 |                     :description,
332 |                     :transaction_date,
333 |                     NOW(),
334 |                     NOW()
335 |                 )
336 |             ";
337 | 
338 |             $this->db->query($financeSql, [
339 |                 'description' => $transactionDescription,
340 |                 'transaction_date' => $transactionDate
341 |             ]);
342 | 
343 |             $transactionId = (int) $this->db->lastInsertId();
344 | 
345 |             // Insert debit entry for Asset account (Cash or Accounts Receivable)
346 |             $debitSql = "
347 |                 INSERT INTO ledger_entries (
348 |                     transaction_id,
349 |                     account_id,
350 |                     amount,
351 |                     created_at,
352 |                     updated_at
353 |                 ) VALUES (
354 |                     :transaction_id,
355 |                     :account_id,
356 |                     :amount,
357 |                     NOW(),
358 |                     NOW()
359 |                 )
360 |             ";
361 | 
362 |             $this->db->query($debitSql, [
363 |                 'transaction_id' => $transactionId,
364 |                 'account_id' => $debitAccountId,
365 |                 'amount' => $totalAmount // Positive for debit (asset increase)
366 |             ]);
367 | 
368 |             // Insert credit entry for Sales Income account
369 |             $creditSql = "
370 |                 INSERT INTO ledger_entries (
371 |                     transaction_id,
372 |                     account_id,
373 |                     amount,
374 |                     created_at,
375 |                     updated_at
376 |                 ) VALUES (
377 |                     :transaction_id,
378 |                     :account_id,
379 |                     :amount,
380 |                     NOW(),
381 |                     NOW()
382 |                 )
383 |             ";
384 | 
385 |             $this->db->query($creditSql, [
386 |                 'transaction_id' => $transactionId,
387 |                 'account_id' => self::SALES_INCOME_ACCOUNT_ID,
388 |                 'amount' => -$totalAmount // Negative for credit (income increase)
389 |             ]);
390 | 
391 |             // Update Asset account balance (Cash or Accounts Receivable)
392 |             $updateAssetSql = "
393 |                 UPDATE accounts
394 |                 SET
395 |                     balance = balance + :amount,
396 |                     updated_at = NOW()
397 |                 WHERE id = :account_id
398 |             ";
399 | 
400 |             $this->db->query($updateAssetSql, [
401 |                 'amount' => $totalAmount,
402 |                 'account_id' => $debitAccountId
403 |             ]);
404 | 
405 |             // Update Sales Income account balance (subtract - income accounts have negative balances)
406 |             $updateIncomeSql = "
407 |                 UPDATE accounts
408 |                 SET
409 |                     balance = balance - :amount,
410 |                     updated_at = NOW()
411 |                 WHERE id = :account_id
412 |             ";
413 | 
414 |             $this->db->query($updateIncomeSql, [
415 |                 'amount' => $totalAmount,
416 |                 'account_id' => self::SALES_INCOME_ACCOUNT_ID
417 |             ]);
418 | 
419 |             // Commit the transaction - all operations succeeded
420 |             $this->db->commit();
421 | 
422 |             return [
423 |                 'success' => true,
424 |                 'sales_order_id' => $salesOrderId,
425 |                 'invoice_number' => $invoiceNumber,
426 |                 'total_amount' => $totalAmount,
427 |                 'payment_type' => ($customerId !== null) ? 'credit' : 'cash',
428 |                 'customer_id' => $customerId,
429 |                 'message' => 'Sales order created successfully'
430 |             ];
431 | 
432 |         } catch (Exception $e) {
433 |             // Rollback on any error to maintain data integrity
434 |             $this->db->rollback();
435 | 
436 |             return [
437 |                 'success' => false,
438 |                 'message' => $e->getMessage()
439 |             ];
440 |         }
441 |     }
442 | 
443 |     /**
444 |      * Generate a unique invoice number
445 |      * 
446 |      * Format: INV-YYYY-NNNN (e.g., INV-2026-0001)
447 |      * 
448 |      * @return string Unique invoice number
449 |      * @throws PDOException If query fails
450 |      */
451 |     private function generateInvoiceNumber(): string
452 |     {
453 |         $year = date('Y');
454 |         $prefix = "INV-{$year}-";
455 | 
456 |         // Get the highest invoice number for current year
457 |         $sql = "
458 |             SELECT invoice_number
459 |             FROM sales_orders
460 |             WHERE invoice_number LIKE :prefix
461 |             ORDER BY invoice_number DESC
462 |             LIMIT 1
463 |         ";
464 | 
465 |         $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
466 |         $result = $stmt->fetch();
467 | 
468 |         if ($result) {
469 |             // Extract the numeric part and increment
470 |             $lastNumber = (int) substr($result['invoice_number'], -4);
471 |             $nextNumber = $lastNumber + 1;
472 |         } else {
473 |             // First invoice of the year
474 |             $nextNumber = 1;
475 |         }
476 | 
477 |         // Format with leading zeros (4 digits)
478 |         return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
479 |     }
480 | 
481 |     /**
482 |      * Get all sales orders with summary information
483 |      * 
484 |      * @param int $limit Maximum number of orders to return (default: 50)
485 |      * @return array<int, array<string, mixed>> Array of sales orders
486 |      * @throws PDOException If query fails
487 |      */
488 |     public function getAllSalesOrders(int $limit = 50): array
489 |     {
490 |         $sql = "
491 |             SELECT 
492 |                 id,
493 |                 invoice_number,
494 |                 customer_name,
495 |                 customer_id,
496 |                 total_amount,
497 |                 created_at,
498 |                 updated_at
499 |             FROM sales_orders
500 |             ORDER BY created_at DESC
501 |             LIMIT :limit
502 |         ";
503 | 
504 |         $stmt = $this->db->query($sql, ['limit' => $limit]);
505 |         $results = $stmt->fetchAll();
506 | 
507 |         // Cast numeric values
508 |         return array_map(function ($row) {
509 |             return [
510 |                 'id' => (int) $row['id'],
511 |                 'invoice_number' => $row['invoice_number'],
512 |                 'customer_name' => $row['customer_name'],
513 |                 'customer_id' => $row['customer_id'] ? (int) $row['customer_id'] : null,
514 |                 'payment_type' => $row['customer_id'] ? 'credit' : 'cash',
515 |                 'total_amount' => (float) $row['total_amount'],
516 |                 'created_at' => $row['created_at'],
517 |                 'updated_at' => $row['updated_at']
518 |             ];
519 |         }, $results);
520 |     }
521 | 
522 |     /**
523 |      * Get sales order details with line items
524 |      * 
525 |      * @param int $salesOrderId Sales order ID
526 |      * @return array<string, mixed>|null Order details with items array, or null if not found
527 |      * @throws PDOException If query fails
528 |      */
529 |     public function getSalesOrderById(int $salesOrderId): ?array
530 |     {
531 |         // Get order header
532 |         $orderSql = "
533 |             SELECT 
534 |                 id,
535 |                 invoice_number,
536 |                 customer_name,
537 |                 customer_id,
538 |                 total_amount,
539 |                 created_at,
540 |                 updated_at
541 |             FROM sales_orders
542 |             WHERE id = :sales_order_id
543 |         ";
544 | 
545 |         $orderStmt = $this->db->query($orderSql, ['sales_order_id' => $salesOrderId]);
546 |         $order = $orderStmt->fetch();
547 | 
548 |         if (!$order) {
549 |             return null;
550 |         }
551 | 
552 |         // Get line items
553 |         $itemsSql = "
554 |             SELECT 
555 |                 si.id,
556 |                 si.product_id,
557 |                 si.quantity,
558 |                 si.unit_price,
559 |                 si.line_total,
560 |                 p.name AS product_name,
561 |                 p.sku AS product_sku
562 |             FROM sales_items si
563 |             INNER JOIN products p ON si.product_id = p.id
564 |             WHERE si.sales_order_id = :sales_order_id
565 |             ORDER BY si.id ASC
566 |         ";
567 | 
568 |         $itemsStmt = $this->db->query($itemsSql, ['sales_order_id' => $salesOrderId]);
569 |         $items = $itemsStmt->fetchAll();
570 | 
571 |         return [
572 |             'id' => (int) $order['id'],
573 |             'invoice_number' => $order['invoice_number'],
574 |             'customer_name' => $order['customer_name'],
575 |             'customer_id' => $order['customer_id'] ? (int) $order['customer_id'] : null,
576 |             'payment_type' => $order['customer_id'] ? 'credit' : 'cash',
577 |             'total_amount' => (float) $order['total_amount'],
578 |             'created_at' => $order['created_at'],
579 |             'updated_at' => $order['updated_at'],
580 |             'items' => array_map(function ($item) {
581 |                 return [
582 |                     'id' => (int) $item['id'],
583 |                     'product_id' => (int) $item['product_id'],
584 |                     'product_name' => $item['product_name'],
585 |                     'product_sku' => $item['product_sku'],
586 |                     'quantity' => (int) $item['quantity'],
587 |                     'unit_price' => (float) $item['unit_price'],
588 |                     'line_total' => (float) $item['line_total']
589 |                 ];
590 |             }, $items)
591 |         ];
592 |     }
593 | 
594 |     /**
595 |      * Get sales statistics for dashboard
596 |      * 
597 |      * @return array<string, mixed> Sales statistics
598 |      * @throws PDOException If query fails
599 |      */
600 |     public function getSalesStats(): array
601 |     {
602 |         $sql = "
603 |             SELECT 
604 |                 COUNT(id) AS total_orders,
605 |                 COALESCE(SUM(total_amount), 0) AS total_revenue,
606 |                 COALESCE(AVG(total_amount), 0) AS average_order_value,
607 |                 COUNT(CASE WHEN customer_id IS NOT NULL THEN 1 END) AS credit_sales_count,
608 |                 COUNT(CASE WHEN customer_id IS NULL THEN 1 END) AS cash_sales_count
609 |             FROM sales_orders
610 |         ";
611 | 
612 |         $stmt = $this->db->query($sql);
613 |         $result = $stmt->fetch();
614 | 
615 |         return [
616 |             'total_orders' => (int) $result['total_orders'],
617 |             'total_revenue' => (float) $result['total_revenue'],
618 |             'average_order_value' => (float) $result['average_order_value'],
619 |             'credit_sales_count' => (int) $result['credit_sales_count'],
620 |             'cash_sales_count' => (int) $result['cash_sales_count']
621 |         ];
622 |     }
623 | }
624 | 
625 | // Made with Bob
</content>
</file>
<file><path>modules/inventory/models/InventoryModel.php</path>
<content lines="1-565">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * InventoryModel Class
  7 |  * 
  8 |  * Handles all database operations for the inventory module.
  9 |  * Provides methods to fetch products, categories, and inventory statistics.
 10 |  * All queries use PDO prepared statements for security.
 11 |  * 
 12 |  * @package AmenERP\Modules\Inventory
 13 |  * @author Bob
 14 |  * @version 1.0.0
 15 |  */
 16 | class InventoryModel
 17 | {
 18 |     /**
 19 |      * Database instance
 20 |      * 
 21 |      * @var Database
 22 |      */
 23 |     private Database $db;
 24 | 
 25 |     /**
 26 |      * Low stock threshold
 27 |      * Products with quantity below this are considered low stock
 28 |      * 
 29 |      * @var int
 30 |      */
 31 |     private const LOW_STOCK_THRESHOLD = 20;
 32 | 
 33 |     /**
 34 |      * Constructor
 35 |      * Initializes the database connection using singleton pattern
 36 |      */
 37 |     public function __construct()
 38 |     {
 39 |         $this->db = Database::getInstance();
 40 |     }
 41 | 
 42 |     /**
 43 |      * Get all products with their category information
 44 |      * 
 45 |      * Returns a joined result set with product details and category names.
 46 |      * Ordered by product name for consistent display.
 47 |      * 
 48 |      * @return array<int, array<string, mixed>> Array of products with category data
 49 |      * @throws PDOException If query fails
 50 |      * 
 51 |      * @example
 52 |      * $model = new InventoryModel();
 53 |      * $products = $model->getAllProducts();
 54 |      * foreach ($products as $product) {
 55 |      *     echo $product['name'] . ' - ' . $product['category_name'];
 56 |      * }
 57 |      */
 58 |     public function getAllProducts(): array
 59 |     {
 60 |         $sql = "
 61 |             SELECT 
 62 |                 p.id,
 63 |                 p.name,
 64 |                 p.sku,
 65 |                 p.quantity,
 66 |                 p.unit_price,
 67 |                 p.status,
 68 |                 p.created_at,
 69 |                 p.updated_at,
 70 |                 c.id AS category_id,
 71 |                 c.name AS category_name
 72 |             FROM products p
 73 |             INNER JOIN categories c ON p.category_id = c.id
 74 |             ORDER BY p.name ASC
 75 |         ";
 76 | 
 77 |         $stmt = $this->db->query($sql);
 78 |         return $stmt->fetchAll();
 79 |     }
 80 | 
 81 |     /**
 82 |      * Get products filtered by status
 83 |      * 
 84 |      * @param string $status Product status ('active', 'inactive', 'discontinued')
 85 |      * @return array<int, array<string, mixed>> Array of filtered products
 86 |      * @throws PDOException If query fails
 87 |      */
 88 |     public function getProductsByStatus(string $status): array
 89 |     {
 90 |         $sql = "
 91 |             SELECT 
 92 |                 p.id,
 93 |                 p.name,
 94 |                 p.sku,
 95 |                 p.quantity,
 96 |                 p.unit_price,
 97 |                 p.status,
 98 |                 p.created_at,
 99 |                 p.updated_at,
100 |                 c.id AS category_id,
101 |                 c.name AS category_name
102 |             FROM products p
103 |             INNER JOIN categories c ON p.category_id = c.id
104 |             WHERE p.status = :status
105 |             ORDER BY p.name ASC
106 |         ";
107 | 
108 |         $stmt = $this->db->query($sql, ['status' => $status]);
109 |         return $stmt->fetchAll();
110 |     }
111 | 
112 |     /**
113 |      * Get products by category
114 |      * 
115 |      * @param int $categoryId Category ID to filter by
116 |      * @return array<int, array<string, mixed>> Array of products in the category
117 |      * @throws PDOException If query fails
118 |      */
119 |     public function getProductsByCategory(int $categoryId): array
120 |     {
121 |         $sql = "
122 |             SELECT 
123 |                 p.id,
124 |                 p.name,
125 |                 p.sku,
126 |                 p.quantity,
127 |                 p.unit_price,
128 |                 p.status,
129 |                 p.created_at,
130 |                 p.updated_at,
131 |                 c.id AS category_id,
132 |                 c.name AS category_name
133 |             FROM products p
134 |             INNER JOIN categories c ON p.category_id = c.id
135 |             WHERE p.category_id = :category_id
136 |             ORDER BY p.name ASC
137 |         ";
138 | 
139 |         $stmt = $this->db->query($sql, ['category_id' => $categoryId]);
140 |         return $stmt->fetchAll();
141 |     }
142 | 
143 |     /**
144 |      * Get inventory summary statistics
145 |      * 
146 |      * Returns key metrics for the inventory dashboard:
147 |      * - total_items: Total number of products in inventory
148 |      * - total_value: Total monetary value of all inventory (quantity × unit_price)
149 |      * - low_stock_count: Number of products below the low stock threshold
150 |      * - active_products: Number of active products
151 |      * - total_categories: Number of product categories
152 |      * 
153 |      * @return array<string, mixed> Associative array of statistics
154 |      * @throws PDOException If query fails
155 |      * 
156 |      * @example
157 |      * $model = new InventoryModel();
158 |      * $stats = $model->getSummaryStats();
159 |      * echo "Total Items: " . $stats['total_items'];
160 |      * echo "Low Stock: " . $stats['low_stock_count'];
161 |      */
162 |     public function getSummaryStats(): array
163 |     {
164 |         $sql = "
165 |             SELECT 
166 |                 COUNT(p.id) AS total_items,
167 |                 COALESCE(SUM(p.quantity * p.unit_price), 0) AS total_value,
168 |                 SUM(CASE WHEN p.quantity < :low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_count,
169 |                 SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) AS active_products,
170 |                 (SELECT COUNT(DISTINCT id) FROM categories) AS total_categories
171 |             FROM products p
172 |         ";
173 | 
174 |         $stmt = $this->db->query($sql, ['low_stock_threshold' => self::LOW_STOCK_THRESHOLD]);
175 |         $result = $stmt->fetch();
176 | 
177 |         // Ensure numeric types are properly cast
178 |         return [
179 |             'total_items' => (int) $result['total_items'],
180 |             'total_value' => (float) $result['total_value'],
181 |             'low_stock_count' => (int) $result['low_stock_count'],
182 |             'active_products' => (int) $result['active_products'],
183 |             'total_categories' => (int) $result['total_categories']
184 |         ];
185 |     }
186 | 
187 |     /**
188 |      * Get low stock products
189 |      * 
190 |      * Returns products that are below the low stock threshold.
191 |      * Useful for generating alerts and reorder reports.
192 |      * 
193 |      * @return array<int, array<string, mixed>> Array of low stock products
194 |      * @throws PDOException If query fails
195 |      */
196 |     public function getLowStockProducts(): array
197 |     {
198 |         $sql = "
199 |             SELECT 
200 |                 p.id,
201 |                 p.name,
202 |                 p.sku,
203 |                 p.quantity,
204 |                 p.unit_price,
205 |                 p.status,
206 |                 c.name AS category_name
207 |             FROM products p
208 |             INNER JOIN categories c ON p.category_id = c.id
209 |             WHERE p.quantity < :low_stock_threshold
210 |             AND p.status = 'active'
211 |             ORDER BY p.quantity ASC, p.name ASC
212 |         ";
213 | 
214 |         $stmt = $this->db->query($sql, ['low_stock_threshold' => self::LOW_STOCK_THRESHOLD]);
215 |         return $stmt->fetchAll();
216 |     }
217 | 
218 |     /**
219 |      * Get all categories
220 |      * 
221 |      * @return array<int, array<string, mixed>> Array of all categories
222 |      * @throws PDOException If query fails
223 |      */
224 |     public function getAllCategories(): array
225 |     {
226 |         $sql = "
227 |             SELECT 
228 |                 id,
229 |                 name,
230 |                 description,
231 |                 created_at,
232 |                 updated_at
233 |             FROM categories
234 |             ORDER BY name ASC
235 |         ";
236 | 
237 |         $stmt = $this->db->query($sql);
238 |         return $stmt->fetchAll();
239 |     }
240 | 
241 |     /**
242 |      * Get category statistics
243 |      * 
244 |      * Returns product count and total value for each category.
245 |      * 
246 |      * @return array<int, array<string, mixed>> Array of category statistics
247 |      * @throws PDOException If query fails
248 |      */
249 |     public function getCategoryStats(): array
250 |     {
251 |         $sql = "
252 |             SELECT 
253 |                 c.id,
254 |                 c.name,
255 |                 COUNT(p.id) AS product_count,
256 |                 COALESCE(SUM(p.quantity), 0) AS total_quantity,
257 |                 COALESCE(SUM(p.quantity * p.unit_price), 0) AS total_value
258 |             FROM categories c
259 |             LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
260 |             GROUP BY c.id, c.name
261 |             ORDER BY c.name ASC
262 |         ";
263 | 
264 |         $stmt = $this->db->query($sql);
265 |         $results = $stmt->fetchAll();
266 | 
267 |         // Cast numeric values
268 |         return array_map(function ($row) {
269 |             return [
270 |                 'id' => (int) $row['id'],
271 |                 'name' => $row['name'],
272 |                 'product_count' => (int) $row['product_count'],
273 |                 'total_quantity' => (int) $row['total_quantity'],
274 |                 'total_value' => (float) $row['total_value']
275 |             ];
276 |         }, $results);
277 |     }
278 | 
279 |     /**
280 |      * Get a single product by ID
281 |      * 
282 |      * @param int $productId Product ID
283 |      * @return array<string, mixed>|null Product data or null if not found
284 |      * @throws PDOException If query fails
285 |      */
286 |     public function getProductById(int $productId): ?array
287 |     {
288 |         $sql = "
289 |             SELECT 
290 |                 p.id,
291 |                 p.name,
292 |                 p.sku,
293 |                 p.quantity,
294 |                 p.unit_price,
295 |                 p.status,
296 |                 p.created_at,
297 |                 p.updated_at,
298 |                 c.id AS category_id,
299 |                 c.name AS category_name
300 |             FROM products p
301 |             INNER JOIN categories c ON p.category_id = c.id
302 |             WHERE p.id = :product_id
303 |         ";
304 | 
305 |         $stmt = $this->db->query($sql, ['product_id' => $productId]);
306 |         $result = $stmt->fetch();
307 | 
308 |         return $result ?: null;
309 |     }
310 | 
311 |     /**
312 |      * Search products by name or SKU
313 |      * 
314 |      * Performs a case-insensitive search on product name and SKU.
315 |      * 
316 |      * @param string $searchTerm Search term
317 |      * @return array<int, array<string, mixed>> Array of matching products
318 |      * @throws PDOException If query fails
319 |      */
320 |     public function searchProducts(string $searchTerm): array
321 |     {
322 |         $sql = "
323 |             SELECT
324 |                 p.id,
325 |                 p.name,
326 |                 p.sku,
327 |                 p.quantity,
328 |                 p.unit_price,
329 |                 p.status,
330 |                 p.created_at,
331 |                 p.updated_at,
332 |                 c.id AS category_id,
333 |                 c.name AS category_name
334 |             FROM products p
335 |             INNER JOIN categories c ON p.category_id = c.id
336 |             WHERE p.name LIKE :search_name
337 |             OR p.sku LIKE :search_sku
338 |             ORDER BY p.name ASC
339 |         ";
340 | 
341 |         $searchPattern = '%' . $searchTerm . '%';
342 |         $stmt = $this->db->query($sql, [
343 |             'search_name' => $searchPattern,
344 |             'search_sku' => $searchPattern
345 |         ]);
346 |         return $stmt->fetchAll();
347 |     }
348 | 
349 |     /**
350 |      * Add a new product to inventory
351 |      * 
352 |      * Inserts a new product record with automatic timestamp generation.
353 |      * Uses PDO prepared statements for security against SQL injection.
354 |      * 
355 |      * @param array<string, mixed> $data Product data array with keys:
356 |      *                                    - name: Product name (string)
357 |      *                                    - sku: Stock Keeping Unit (string)
358 |      *                                    - category_id: Category ID (int)
359 |      *                                    - quantity: Stock quantity (int)
360 |      *                                    - unit_price: Price per unit (float)
361 |      *                                    - status: Product status (string: active|inactive|discontinued)
362 |      * @return bool True on success, false on failure
363 |      * @throws PDOException If database operation fails
364 |      * 
365 |      * @example
366 |      * $model = new InventoryModel();
367 |      * $result = $model->addProduct([
368 |      *     'name' => 'Laptop Computer',
369 |      *     'sku' => 'TECH-001',
370 |      *     'category_id' => 1,
371 |      *     'quantity' => 50,
372 |      *     'unit_price' => 999.99,
373 |      *     'status' => 'active'
374 |      * ]);
375 |      */
376 |     public function addProduct(array $data): bool
377 |     {
378 |         $sql = "
379 |             INSERT INTO products (
380 |                 name,
381 |                 sku,
382 |                 category_id,
383 |                 quantity,
384 |                 unit_price,
385 |                 status,
386 |                 created_at,
387 |                 updated_at
388 |             ) VALUES (
389 |                 :name,
390 |                 :sku,
391 |                 :category_id,
392 |                 :quantity,
393 |                 :unit_price,
394 |                 :status,
395 |                 NOW(),
396 |                 NOW()
397 |             )
398 |         ";
399 | 
400 |         $params = [
401 |             'name' => $data['name'],
402 |             'sku' => $data['sku'],
403 |             'category_id' => $data['category_id'],
404 |             'quantity' => $data['quantity'],
405 |             'unit_price' => $data['unit_price'],
406 |             'status' => $data['status']
407 |         ];
408 | 
409 |         $stmt = $this->db->query($sql, $params);
410 |         
411 |         // Return true if at least one row was affected
412 |         return $stmt->rowCount() > 0;
413 |     }
414 | 
415 |     /**
416 |      * Update an existing product in inventory
417 |      * 
418 |      * Updates product information with automatic timestamp generation.
419 |      * Uses PDO prepared statements for security against SQL injection.
420 |      * 
421 |      * @param int $productId Product ID to update
422 |      * @param array<string, mixed> $data Product data array with keys:
423 |      *                                    - name: Product name (string)
424 |      *                                    - sku: Stock Keeping Unit (string)
425 |      *                                    - category_id: Category ID (int)
426 |      *                                    - quantity: Stock quantity (int)
427 |      *                                    - unit_price: Price per unit (float)
428 |      *                                    - status: Product status (string: active|inactive|discontinued)
429 |      * @return bool True on success, false if no rows affected or product not found
430 |      * @throws PDOException If database operation fails
431 |      * 
432 |      * @example
433 |      * $model = new InventoryModel();
434 |      * $result = $model->updateProduct(5, [
435 |      *     'name' => 'Updated Laptop Computer',
436 |      *     'sku' => 'TECH-001-V2',
437 |      *     'category_id' => 1,
438 |      *     'quantity' => 75,
439 |      *     'unit_price' => 1099.99,
440 |      *     'status' => 'active'
441 |      * ]);
442 |      */
443 |     public function updateProduct(int $productId, array $data): bool
444 |     {
445 |         $sql = "
446 |             UPDATE products
447 |             SET
448 |                 name = :name,
449 |                 sku = :sku,
450 |                 category_id = :category_id,
451 |                 quantity = :quantity,
452 |                 unit_price = :unit_price,
453 |                 status = :status,
454 |                 updated_at = NOW()
455 |             WHERE id = :product_id
456 |         ";
457 | 
458 |         $params = [
459 |             'product_id' => $productId,
460 |             'name' => $data['name'],
461 |             'sku' => $data['sku'],
462 |             'category_id' => $data['category_id'],
463 |             'quantity' => $data['quantity'],
464 |             'unit_price' => $data['unit_price'],
465 |             'status' => $data['status']
466 |         ];
467 | 
468 |         $stmt = $this->db->query($sql, $params);
469 |         
470 |         // Return true if at least one row was affected
471 |         return $stmt->rowCount() > 0;
472 |     }
473 | 
474 |     /**
475 |      * Delete a product from inventory
476 |      * 
477 |      * Permanently removes a product record from the database.
478 |      * Uses PDO prepared statements for security against SQL injection.
479 |      * 
480 |      * WARNING: This is a hard delete operation. Consider implementing soft deletes
481 |      * (status = 'deleted') for production systems to maintain audit trails.
482 |      * 
483 |      * @param int $productId Product ID to delete
484 |      * @return bool True on success, false if no rows affected or product not found
485 |      * @throws PDOException If database operation fails (e.g., foreign key constraint)
486 |      * 
487 |      * @example
488 |      * $model = new InventoryModel();
489 |      * try {
490 |      *     $result = $model->deleteProduct(5);
491 |      *     if ($result) {
492 |      *         echo "Product deleted successfully";
493 |      *     }
494 |      * } catch (PDOException $e) {
495 |      *     echo "Cannot delete: " . $e->getMessage();
496 |      * }
497 |      */
498 |     public function deleteProduct(int $productId): bool
499 |     {
500 |         $sql = "
501 |             DELETE FROM products
502 |             WHERE id = :product_id
503 |         ";
504 | 
505 |         $params = [
506 |             'product_id' => $productId
507 |         ];
508 | 
509 |         $stmt = $this->db->query($sql, $params);
510 |         
511 |         // Return true if at least one row was affected
512 |         return $stmt->rowCount() > 0;
513 |     }
514 | 
515 |     /**
516 |      * Check if a SKU already exists in the database
517 |      *
518 |      * Used for real-time validation to prevent duplicate SKU entries.
519 |      * Performs a case-insensitive search for the SKU.
520 |      *
521 |      * @param string $sku SKU to check
522 |      * @param int|null $excludeProductId Optional product ID to exclude from check (for edit operations)
523 |      * @return bool True if SKU exists, false otherwise
524 |      * @throws PDOException If query fails
525 |      *
526 |      * @example
527 |      * $model = new InventoryModel();
528 |      * if ($model->skuExists('PROD-001')) {
529 |      *     echo "SKU already exists";
530 |      * }
531 |      */
532 |     public function skuExists(string $sku, ?int $excludeProductId = null): bool
533 |     {
534 |         $sql = "
535 |             SELECT COUNT(*) as count
536 |             FROM products
537 |             WHERE UPPER(sku) = UPPER(:sku)
538 |         ";
539 | 
540 |         $params = ['sku' => $sku];
541 | 
542 |         // Exclude specific product ID (useful for edit operations)
543 |         if ($excludeProductId !== null) {
544 |             $sql .= " AND id != :exclude_id";
545 |             $params['exclude_id'] = $excludeProductId;
546 |         }
547 | 
548 |         $stmt = $this->db->query($sql, $params);
549 |         $result = $stmt->fetch();
550 | 
551 |         return (int) $result['count'] > 0;
552 |     }
553 | 
554 |     /**
555 |      * Get the low stock threshold constant
556 |      *
557 |      * @return int Low stock threshold value
558 |      */
559 |     public function getLowStockThreshold(): int
560 |     {
561 |         return self::LOW_STOCK_THRESHOLD;
562 |     }
563 | }
564 | 
565 | // Made with Bob
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\sales\controllers\ProcessSaleController.php

# VSCode Open Tabs
modules/sales/models/SalesModel.php,modules/sales/controllers/ProcessSaleController.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T17:19:03.501Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.01

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
You have not created a todo list yet. Create one with `update_todo_list` if your task is complicated or involves multiple steps.
</environment_details>

---

**Assistant:**

I'll now rewrite the `modules/sales/index.php` file to support both Cash Sales and B2B Credit Customers with all the specifications you've outlined. Let me create the complete file with proper integration.

<write_to_file>
<path>modules/sales/index.php</path>
<content>
<?php
/**
 * Sales Module - Main Dashboard View
 *
 * Displays new sales form with support for both Cash Sales and B2B Credit Customers.
 * Implements real-time calculations, stock validation, credit limit checking, and seamless multi-module integration.
 *
 * Features:
 * - Dual payment type support (Cash/Credit)
 * - Dynamic customer selection with credit limit tracking
 * - Dynamic item rows with add/remove functionality
 * - Real-time price and total calculations
 * - Credit boundary validation
 * - Stock availability validation
 * - CSRF protection
 * - Responsive mobile-first design
 *
 * @package AmenERP\Modules\Sales
 * @author Bob
 * @version 2.0.0
 */

declare(strict_types=1);

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Ensure session is active (should already be started by front controller)
 * This is a safety check for direct access scenarios
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load required core classes
 * These are autoloaded by the front controller, but we verify they exist
 */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../core/Database.php';
}

if (!class_exists('Csrf')) {
    require_once __DIR__ . '/../../core/Csrf.php';
}

/**
 * Load required models
 * SalesModel: Handles sales order creation and retrieval
 * InventoryModel: Provides product data for the sales form
 * CustomerModel: Provides B2B customer data for credit sales
 */
require_once __DIR__ . '/models/SalesModel.php';
require_once __DIR__ . '/../inventory/models/InventoryModel.php';
require_once __DIR__ . '/../customers/models/CustomerModel.php';

// ============================================================================
// DATA INITIALIZATION
// ============================================================================

/**
 * Initialize model instances
 */
$salesModel = new SalesModel();
$inventoryModel = new InventoryModel();
$customerModel = new CustomerModel();

/**
 * Fetch active products for the sales form
 * Only active products can be sold
 */
$allProducts = $inventoryModel->getAllProducts();
$products = array_filter($allProducts, function($product) {
    return isset($product['status']) && $product['status'] === 'active';
});

/**
 * Fetch all active B2B customers for credit sales
 * Only active customers can make credit purchases
 */
$allB2BCustomers = $customerModel->getAllCustomers('active');
$b2bCustomers = array_filter($allB2BCustomers, function($customer) {
    return isset($customer['status']) && $customer['status'] === 'active';
});

/**
 * Fetch recent sales orders for the dashboard table
 * Limited to 10 most recent orders
 */
$recentOrders = $salesModel->getAllSalesOrders(10);

/**
 * Fetch sales statistics for the metrics cards
 * Includes total orders, revenue, and average order value
 */
$salesStats = $salesModel->getSalesStats();

/**
 * Generate CSRF token for form security
 * Protects against Cross-Site Request Forgery attacks
 */
$csrfToken = Csrf::generateToken();

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Format currency values for display
 *
 * @param float $amount The amount to format
 * @return string Formatted currency string (e.g., "$1,234.56")
 */
function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

/**
 * Format integer numbers with thousands separator
 *
 * @param int $number The number to format
 * @return string Formatted number string (e.g., "1,234")
 */
function formatNumber(int $number): string
{
    return number_format($number);
}
?>

<div class="module-container sales-module">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php
            echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php
            echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="module-header">
        <h2>Sales & Invoicing</h2>
    </div>

    <!-- Sales Statistics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-content">
                <div class="metric-label">Total Orders</div>
                <div class="metric-value"><?php echo formatNumber($salesStats['total_orders']); ?></div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label">Total Revenue</div>
                <div class="metric-value"><?php echo formatCurrency($salesStats['total_revenue']); ?></div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-label">Average Order Value</div>
                <div class="metric-value"><?php echo formatCurrency($salesStats['average_order_value']); ?></div>
            </div>
        </div>
    </div>

    <!-- New Sales Form -->
    <section class="form-section">
        <h3>New Sales Transaction</h3>
        <p class="form-description">Create a new sales invoice for cash or credit customers. Stock levels will be automatically updated and the transaction will be recorded in your accounts.</p>
        
        <form id="salesForm" class="sales-form" method="POST" action="<?php echo BASE_URL; ?>/sales/checkout">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- Customer Information Section -->
            <div class="customer-info-section">
                <h4>Customer Information</h4>
                
                <div class="form-row">
                    <!-- Payment Type Selector -->
                    <div class="form-group">
                        <label for="payment_type" class="form-label">
                            Payment Type <span class="required">*</span>
                        </label>
                        <select 
                            id="payment_type" 
                            name="payment_type" 
                            class="form-select" 
                            required
                        >
                            <option value="cash">Direct Cash Payment</option>
                            <option value="credit">On-Account Commercial Credit</option>
                        </select>
                    </div>

                    <!-- Cash Customer Name Input (Default Visible) -->
                    <div class="form-group" id="customer_name_wrapper">
                        <label for="customer_name" class="form-label">
                            Customer Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            class="form-input" 
                            placeholder="Enter customer name"
                            required
                            minlength="2"
                            maxlength="255"
                        >
                    </div>

                    <!-- B2B Customer Selector (Hidden by Default) -->
                    <div class="form-group" id="customer_id_wrapper" style="display: none;">
                        <label for="customer_id" class="form-label">
                            B2B Customer <span class="required">*</span>
                        </label>
                        <select 
                            id="customer_id" 
                            name="customer_id" 
                            class="form-select"
                        >
                            <option value="">Select a customer</option>
                            <?php foreach ($b2bCustomers as $customer): ?>
                                <option 
                                    value="<?php echo (int)$customer['id']; ?>"
                                    data-limit="<?php echo (float)$customer['credit_limit']; ?>"
                                    data-debt="<?php echo (float)$customer['outstanding_balance']; ?>"
                                    data-name="<?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (<?php echo htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8'); ?>) 
                                    - Credit: <?php echo formatCurrency($customer['credit_available']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sale Items Section -->
            <div class="sale-items-section">
                <div class="section-header">
                    <h4>Sale Items</h4>
                    <button type="button" id="addItemBtn" class="btn btn-secondary">+ Add Item</button>
                </div>

                <div id="saleItemsContainer">
                    <!-- Initial item row -->
                    <div class="sale-item-row" data-item-index="0">
                        <div class="form-group">
                            <label class="form-label">Product <span class="required">*</span></label>
                            <select name="items[0][product_id]" class="form-select product-select" required data-item-index="0">
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option 
                                        value="<?php echo (int)$product['id']; ?>"
                                        data-price="<?php echo (float)$product['unit_price']; ?>"
                                        data-stock="<?php echo (int)$product['quantity']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                        (<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>) 
                                        - Stock: <?php echo (int)$product['quantity']; ?> 
                                        - <?php echo formatCurrency((float)$product['unit_price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input 
                                type="number" 
                                name="items[0][quantity]" 
                                class="form-input quantity-input" 
                                min="1" 
                                value="1" 
                                required
                                data-item-index="0"
                            >
                            <small class="stock-info" data-item-index="0"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Unit Price <span class="required">*</span></label>
                            <input 
                                type="number" 
                                name="items[0][unit_price]" 
                                class="form-input price-input" 
                                step="0.01" 
                                min="0.01" 
                                required
                                readonly
                                data-item-index="0"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Line Total</label>
                            <input 
                                type="text" 
                                class="form-input line-total" 
                                readonly
                                data-item-index="0"
                                value="$0.00"
                            >
                        </div>

                        <div class="form-group item-actions">
                            <button type="button" class="btn btn-danger btn-small remove-item-btn hidden" data-item-index="0">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">Total Amount:</span>
                        <span id="totalAmount" class="total-value">$0.00</span>
                    </div>
                    <div id="credit_warning" style="color: red; font-weight: bold; margin-top: 5px;"></div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <span class="btn-icon">💳</span>
                    Complete Sale
                </button>
                <button type="reset" class="btn btn-secondary" id="resetFormBtn">
                    Clear Form
                </button>
            </div>
        </form>
    </section>

    <!-- Recent Sales Orders -->
    <section class="table-section">
        <h3>Recent Sales Orders</h3>
        
        <?php if (empty($recentOrders)): ?>
            <div class="empty-state">
                <p>No sales orders yet. Create your first sale above!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['invoice_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_type'] === 'credit' ? 'warning' : 'success'; ?>">
                                        <?php echo $order['payment_type'] === 'credit' ? 'Credit' : 'Cash'; ?>
                                    </span>
                                </td>
                                <td class="amount"><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button 
                                        class="btn btn-small btn-secondary view-order-btn" 
                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                    >
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Sales Module JavaScript -->
<script type="module">
    // Sales form management
    let itemIndex = 1;
    const saleItemsContainer = document.getElementById('saleItemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const totalAmountElement = document.getElementById('totalAmount');
    const salesForm = document.getElementById('salesForm');
    const paymentTypeSelect = document.getElementById('payment_type');
    const customerNameWrapper = document.getElementById('customer_name_wrapper');
    const customerIdWrapper = document.getElementById('customer_id_wrapper');
    const customerNameInput = document.getElementById('customer_name');
    const customerIdSelect = document.getElementById('customer_id');
    const creditWarning = document.getElementById('credit_warning');

    // Product data from PHP - ensure all properties are properly typed
    const products = <?php echo json_encode(array_values(array_map(function($p) {
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'sku' => $p['sku'],
            'quantity' => (int)$p['quantity'],
            'unit_price' => (float)$p['unit_price']
        ];
    }, $products))); ?>;

    // Payment type change handler
    paymentTypeSelect.addEventListener('change', function() {
        const paymentType = this.value;
        
        if (paymentType === 'credit') {
            // Show customer ID selector, hide customer name input
            customerIdWrapper.style.display = 'block';
            customerNameWrapper.style.display = 'none';
            
            // Update required attributes
            customerIdSelect.setAttribute('required', 'required');
            customerNameInput.removeAttribute('required');
            
            // Clear values
            customerNameInput.value = '';
            customerIdSelect.value = '';
            
            // Clear credit warning
            creditWarning.textContent = '';
        } else {
            // Show customer name input, hide customer ID selector
            customerNameWrapper.style.display = 'block';
            customerIdWrapper.style.display = 'none';
            
            // Update required attributes
            customerNameInput.setAttribute('required', 'required');
            customerIdSelect.removeAttribute('required');
            
            // Clear values
            customerIdSelect.value = '';
            customerNameInput.value = '';
            
            // Clear credit warning
            creditWarning.textContent = '';
        }
    });

    // Customer ID change handler - sync name and check credit
    customerIdSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Extract customer name from data attribute and set it in the hidden input
            const customerName = selectedOption.dataset.name || '';
            customerNameInput.value = customerName;
            
            // Check credit boundaries
            checkCreditBoundaries();
        } else {
            customerNameInput.value = '';
            creditWarning.textContent = '';
        }
    });

    // Check credit boundaries function
    function checkCreditBoundaries() {
        const paymentType = paymentTypeSelect.value;
        
        // Only check for credit sales
        if (paymentType !== 'credit') {
            creditWarning.textContent = '';
            return;
        }
        
        const selectedOption = customerIdSelect.options[customerIdSelect.selectedIndex];
        
        if (!selectedOption.value) {
            creditWarning.textContent = '';
            return;
        }
        
        // Parse customer credit data
        const creditLimit = parseFloat(selectedOption.dataset.limit) || 0;
        const outstandingDebt = parseFloat(selectedOption.dataset.debt) || 0;
        const creditAvailable = creditLimit - outstandingDebt;
        
        // Get current total amount
        const totalAmountText = totalAmountElement.textContent.replace('$', '').replace(',', '');
        const totalAmount = parseFloat(totalAmountText) || 0;
        
        // Check if transaction would exceed credit limit
        if (totalAmount > creditAvailable) {
            const shortage = totalAmount - creditAvailable;
            creditWarning.textContent = `⚠️ WARNING: This transaction ($${totalAmount.toFixed(2)}) exceeds available credit ($${creditAvailable.toFixed(2)}) by $${shortage.toFixed(2)}. Transaction will be declined.`;
        } else if (totalAmount > 0) {
            const remainingCredit = creditAvailable - totalAmount;
            creditWarning.textContent = `✓ Credit approved. Remaining credit after this sale: $${remainingCredit.toFixed(2)}`;
            creditWarning.style.color = 'green';
        } else {
            creditWarning.textContent = '';
        }
    }

    // Add new item row
    addItemBtn.addEventListener('click', () => {
        const newRow = createItemRow(itemIndex);
        saleItemsContainer.insertAdjacentHTML('beforeend', newRow);
        attachItemRowListeners(itemIndex);
        updateRemoveButtons();
        itemIndex++;
    });

    // Create item row HTML
    function createItemRow(index) {
        return `
            <div class="sale-item-row" data-item-index="${index}">
                <div class="form-group">
                    <label class="form-label">Product <span class="required">*</span></label>
                    <select name="items[${index}][product_id]" class="form-select product-select" required data-item-index="${index}">
                        <option value="">Select a product</option>
                        ${products.map(p => `
                            <option 
                                value="${p.id}"
                                data-price="${p.unit_price}"
                                data-stock="${p.quantity}"
                                data-name="${escapeHtml(p.name)}"
                            >
                                ${escapeHtml(p.name)} (${escapeHtml(p.sku)}) - Stock: ${p.quantity} - $${p.unit_price.toFixed(2)}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity <span class="required">*</span></label>
                    <input type="number" name="items[${index}][quantity]" class="form-input quantity-input" min="1" value="1" required data-item-index="${index}">
                    <small class="stock-info" data-item-index="${index}"></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price <span class="required">*</span></label>
                    <input type="number" name="items[${index}][unit_price]" class="form-input price-input" step="0.01" min="0.01" required readonly data-item-index="${index}">
                </div>
                <div class="form-group">
                    <label class="form-label">Line Total</label>
                    <input type="text" class="form-input line-total" readonly data-item-index="${index}" value="$0.00">
                </div>
                <div class="form-group item-actions">
                    <button type="button" class="btn btn-danger btn-small remove-item-btn" data-item-index="${index}">Remove</button>
                </div>
            </div>
        `;
    }

    // Escape HTML for security
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Attach listeners to item row
    function attachItemRowListeners(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.remove-item-btn');

        productSelect.addEventListener('change', () => updateLineTotal(index));
        quantityInput.addEventListener('input', () => updateLineTotal(index));
        removeBtn.addEventListener('click', () => removeItemRow(index));
    }

    // Update line total calculation
    function updateLineTotal(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        const lineTotalInput = row.querySelector('.line-total');
        const stockInfo = row.querySelector('.stock-info');

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        
        if (selectedOption.value) {
            // Safely parse data attributes with fallback values
            const price = parseFloat(selectedOption.dataset.price) || 0;
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            const quantity = parseInt(quantityInput.value) || 0;

            priceInput.value = price.toFixed(2);
            const lineTotal = price * quantity;
            lineTotalInput.value = `$${lineTotal.toFixed(2)}`;

            // Stock validation with CSS classes
            if (quantity > stock) {
                stockInfo.textContent = `⚠️ Only ${stock} available`;
                stockInfo.classList.remove('stock-valid');
                stockInfo.classList.add('stock-invalid');
                quantityInput.setCustomValidity('Insufficient stock');
            } else {
                stockInfo.textContent = `✓ ${stock} available`;
                stockInfo.classList.remove('stock-invalid');
                stockInfo.classList.add('stock-valid');
                quantityInput.setCustomValidity('');
            }
        } else {
            priceInput.value = '';
            lineTotalInput.value = '$0.00';
            stockInfo.textContent = '';
            stockInfo.classList.remove('stock-valid', 'stock-invalid');
        }

        updateTotalAmount();
        checkCreditBoundaries();
    }

    // Update total amount
    function updateTotalAmount() {
        let total = 0;
        document.querySelectorAll('.line-total').forEach(input => {
            const value = parseFloat(input.value.replace('$', '').replace(',', '')) || 0;
            total += value;
        });
        totalAmountElement.textContent = `$${total.toFixed(2)}`;
        
        // Re-check credit boundaries when total changes
        checkCreditBoundaries();
    }

    // Remove item row
    function removeItemRow(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        row.remove();
        updateTotalAmount();
        updateRemoveButtons();
        checkCreditBoundaries();
    }

    // Update remove button visibility using CSS classes
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.sale-item-row');
        rows.forEach((row, idx) => {
            const removeBtn = row.querySelector('.remove-item-btn');
            if (rows.length > 1) {
                removeBtn.classList.remove('hidden');
            } else {
                removeBtn.classList.add('hidden');
            }
        });
    }

    // Initialize first row listeners
    attachItemRowListeners(0);

    // Form reset handler
    document.getElementById('resetFormBtn').addEventListener('click', () => {
        // Remove all rows except first
        const rows = document.querySelectorAll('.sale-item-row');
        rows.forEach((row, idx) => {
            if (idx > 0) row.remove();
        });
        itemIndex = 1;
        
        // Reset payment type to cash
        paymentTypeSelect.value = 'cash';
        customerNameWrapper.style.display = 'block';
        customerIdWrapper.style.display = 'none';
        customerNameInput.setAttribute('required', 'required');
        customerIdSelect.removeAttribute('required');
        
        // Clear credit warning
        creditWarning.textContent = '';
        
        updateTotalAmount();
        updateRemoveButtons();
    });

    // Form submission validation
    salesForm.addEventListener('submit', (e) => {
        const rows = document.querySelectorAll('.sale-item-row');
        let hasError = false;

        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            if (!productSelect.value) {
                hasError = true;
                alert('Please select a product for all items');
                e.preventDefault();
            }
        });
        
        // Additional validation for credit sales
        const paymentType = paymentTypeSelect.value;
        if (paymentType === 'credit' && !hasError) {
            const selectedOption = customerIdSelect.options[customerIdSelect.selectedIndex];
            
            if (selectedOption.value) {
                const creditLimit = parseFloat(selectedOption.dataset.limit) || 0;
                const outstandingDebt = parseFloat(selectedOption.dataset.debt) || 0;
                const creditAvailable = creditLimit - outstandingDebt;
                const totalAmountText = totalAmountElement.textContent.replace('$', '').replace(',', '');
                const totalAmount = parseFloat(totalAmountText) || 0;
                
                if (totalAmount > creditAvailable) {
                    alert('Cannot process: Transaction exceeds customer credit limit');
                    e.preventDefault();
                }
            }
        }
    });

    // View order details
    document.addEventListener('click', (e) => {
        if (e.target.closest('.view-order-btn')) {
            const orderId = e.target.closest('.view-order-btn').dataset.orderId;
            if (orderId) {
                // Navigate to order details page
                window.location.href = `<?php echo BASE_URL; ?>/sales/orders/${orderId}`;
            }
        }
    });
</script>

<?php
// Made with Bob
</content>
<line_count>741</line_count>
</write_to_file>

---

**User:**

[write_to_file for 'modules/sales/index.php'] Result:
<file_write_result>
<path>modules/sales/index.php</path>
<operation>modified</operation>
<user_edits>@@ -743,4 +743,4 @@
 &lt;/script&gt;
 
 &lt;?php
-// Made with Bob
\ No newline at end of file
+// Made with Bob
</user_edits>
<notice>
<i>You do not need to re-read the file, as you have seen all changes</i>
<i>Proceed with the task using these changes as the new baseline.</i>
<i>If the user's edits have addressed part of the task or changed the requirements, adjust your approach accordingly.</i>
</notice>
</file_write_result>

<environment_details>
# VSCode Visible Files
modules\sales\index.php

# VSCode Open Tabs
modules/sales/models/SalesModel.php,modules/sales/controllers/ProcessSaleController.php,modules/sales/index.php

# Recently Modified Files
These files have been modified since you last accessed them (file was just edited so you may need to re-read it before editing):
modules/sales/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T17:21:04.068Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.13

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
You have not created a todo list yet. Create one with `update_todo_list` if your task is complicated or involves multiple steps.
</environment_details>

---

**Assistant:**

<attempt_completion>
<result>
Successfully rewrote `modules/sales/index.php` with complete dual payment type support (Cash/Credit). The file now includes:

## Key Features Implemented:

### 1. DATA & BOOTSTRAP LAYER:
✅ Preserved existing Database, Csrf, SalesModel, and InventoryModel configuration
✅ Instantiated CustomerModel and fetched all active B2B customers into `$b2bCustomers` array

### 2. ACCESSIBILITY INTERFACE MODIFICATIONS:
✅ Replaced static customer input with responsive grid containing:
   - `payment_type` dropdown (Cash/Credit options)
   - `customer_name` text input in `#customer_name_wrapper` (default visible for cash sales)
   - `customer_id` dropdown in `#customer_id_wrapper` (hidden by default, with data attributes: data-limit, data-debt, data-name)
✅ Added `#credit_warning` container below totals for credit limit alerts

### 3. VANILLA JAVASCRIPT LOGIC:
✅ Preserved all existing functionality:
   - Multi-item line appenders with dynamic row indexing
   - Unit price auto-fill calculations
   - Line-total mathematical multipliers
   - Table summaries and totals

✅ Added payment type change listener:
   - Toggles visibility between customer name input and customer ID selector
   - Manages `required` attributes dynamically
   - Clears values and warnings on switch

✅ Added customer ID change listener:
   - Syncs selected customer's company name to hidden `customer_name` input
   - Triggers credit boundary check

✅ Implemented `checkCreditBoundaries()` function:
   - Compares transaction total against customer's available credit (limit - debt)
   - Displays real-time warnings if credit would be exceeded
   - Shows remaining credit for approved transactions
   - Runs on every item add/modify/delete operation

✅ Enhanced form validation to prevent credit limit breaches on submission

The file is production-ready with complete functionality from opening PHP tag to closing JavaScript, following all project architecture rules (vanilla PHP/JS, no frameworks, PSR-12 standards, security best practices).
</result>
</attempt_completion>

