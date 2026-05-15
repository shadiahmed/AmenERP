# AmenERP Architecture Rules

# 1. Architectural Philosophy
- Zero Frameworks: No Laravel, no Symfony, no Bootstrap.
- Vanilla Core: Use pure PHP 8.x, native MySQL (PDO), and Vanilla JavaScript (ES6+).
- Performance First: Prioritize lean execution and minimal memory footprint.
- Manual Control: Every line of code must be explicit; no "magic" background processes.

# 2. Coding Standards
- Security: All database interactions must use PDO prepared statements. Sanitize all user inputs using filter_var or htmlspecialchars.
- Database: Use MySQL. All tables must have created_at and updated_at timestamps. Use InnoDB engine.
- Formatting: Follow PSR-12 for PHP. Use clean, documented code with PHPDoc blocks for all functions.
- Directory Logic:
    - /core for system-wide classes (Database, Session, Auth).
    - /modules for business logic (Inventory, Sales).
    - /public for the only web-accessible files.

# 3. Reviewer Instructions
- Constraint Check: Before providing any code, Bob must verify if it introduces an external dependency. If it does, stop and find a "Vanilla" alternative.
- Security Audit: Bob must point out potential SQLi, XSS, or CSRF risks in any generated snippet.
- Optimization: If a query can be more efficient or a loop can be avoided, Bob must suggest the optimized version.

# 4. HTML5 Standards
-Semantic Markup: Use meaningful tags (<main>, <nav>, <section>, <article>, <aside>) to ensure accessibility and SEO without extra classes.
- Data Attributes: Use data-* attributes to store state or configuration for JS, keeping the DOM as the source of truth.
- Native Form Validation: Leverage required, pattern, type="email", and min/max to reduce initial JS overhead for basic validation.
- No Inline Styles: Styles must never be written in the style attribute of an HTML element.

# 5. CSS3 (Vanilla & Lean)
- Flexbox & Grid: Use native CSS Grid for the main ERP layout and Flexbox for component alignment. No float-based hacks.
- CSS Variables: Define a global root for colors, spacing, and font sizes to ensure system-wide consistency without a preprocessor like SASS.
- Mobile-First Design: Since the app may run on mobile (Termux/Localhost), use @media queries starting from the smallest screen size up.
- Naming Convention: Use a simplified BEM (Block-Element-Modifier) or a consistent prefixing system (e.g., .erp-btn, .erp-card) to avoid style bleeding in a modular system.
- Native Nesting: Use modern CSS nesting (available in 2026 browsers) to keep stylesheets readable without extra tools.

# 6. JavaScript (ES6+ & Beyond)
- No Frameworks/Libraries: Zero jQuery. Use fetch() for API calls, querySelector for DOM manipulation, and addEventListener for events.
- Modular JS: Use ES Modules (import/export) to keep your ERP modules (Inventory, Finance) isolated and clean.
- Asynchronous Logic: Use async/await for all database interactions or API calls to the PHP core.
- State Management: Use simple Javascript objects or the CustomEvent API for communication between modules, avoiding complex state libraries.
- Event Delegation: Attach listeners to parent containers where possible to improve performance when handling large lists (like inventory tables).