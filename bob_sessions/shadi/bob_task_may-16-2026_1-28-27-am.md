**User:**

<task>
Reference our project-rules.md. We need a native CSRF security class in /core/Csrf.php. It should contain two simple static methods:
- Csrf::generateToken(): Generates a cryptographically secure token using bin2hex(random_bytes(32)), stores it in $_SESSION['csrf_token'], and returns it so we can drop it into a hidden form input.
- Csrf::validateToken(string $token): Compares a submitted token with the one in the session using hash_equals() to prevent timing attacks, returning a boolean.
- Do not use external packages.
</task>
<environment_details>
# VSCode Visible Files
modules\inventory\index.php

# VSCode Open Tabs
core/Router.php,modules/home/index.php,public/assets/js/main.js,public/assets/css/style.css,schema.sql,public/assets/css/main.css,public/.htaccess,project-rules.md,modules/inventory/models/InventoryModel.php,modules/inventory/index.php,config/config.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-15T22:27:58.039Z
User time zone: Africa/Cairo, UTC+3:00

# Current Cost
$0.00

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>


# Current Workspace Directory (c:/xampp/htdocs/AmenERP) Files
project-rules.md
schema.sql
setup-unix.sh
setup-windows.ps1
config/
config/config.php
core/
core/Database.php
core/Router.php
modules/
modules/finance/
modules/home/
modules/home/index.php
modules/inventory/
modules/inventory/index.php
modules/inventory/models/
modules/inventory/models/InventoryModel.php
public/
public/.htaccess
public/index.php
public/assets/
public/assets/css/
public/assets/css/main.css
public/assets/css/style.css
public/assets/images/
public/assets/js/
public/assets/js/main.js
You have not created a todo list yet. Create one with `update_todo_list` if your task is complicated or involves multiple steps.
</environment_details>

---

**Assistant:**

I'll start by reading the project-rules.md file to understand the project structure and coding standards, then create the CSRF security class.

<read_file>
<args>
<file>
<path>project-rules.md</path>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'project-rules.md'. Reading multiple files at once is more efficient for the LLM. If other files are relevant to your current task, please read them simultaneously.] Result:
<files>
<file><path>project-rules.md</path>
<content lines="1-41">
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
35 | 
36 | # 6. JavaScript (ES6+ & Beyond)
37 | - No Frameworks/Libraries: Zero jQuery. Use fetch() for API calls, querySelector for DOM manipulation, and addEventListener for events.
38 | - Modular JS: Use ES Modules (import/export) to keep your ERP modules (Inventory, Finance) isolated and clean.
39 | - Asynchronous Logic: Use async/await for all database interactions or API calls to the PHP core.
40 | - State Management: Use simple Javascript objects or the CustomEvent API for communication between modules, avoiding complex state libraries.
41 | - Event Delegation: Attach listeners to parent containers where possible to improve performance when handling large lists (like inventory tables).
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\inventory\index.php

# VSCode Open Tabs
core/Router.php,modules/home/index.php,public/assets/js/main.js,public/assets/css/style.css,schema.sql,public/assets/css/main.css,public/.htaccess,project-rules.md,modules/inventory/models/InventoryModel.php,modules/inventory/index.php,config/config.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-15T22:28:07.521Z
User time zone: Africa/Cairo, UTC+3:00

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

Now I'll create the CSRF security class in /core/Csrf.php following the project rules. The class will be native PHP with no external dependencies, following PSR-12 standards with proper PHPDoc blocks.

<write_to_file>
<path>core/Csrf.php</path>
<content>
<?php

/**
 * CSRF Protection Class
 * 
 * Provides cryptographically secure CSRF token generation and validation
 * for form submissions to prevent Cross-Site Request Forgery attacks.
 * 
 * @package AmenERP\Core
 * @author Bob
 * @version 1.0.0
 */
class Csrf
{
    /**
     * Session key for storing CSRF token
     */
    private const SESSION_KEY = 'csrf_token';

    /**
     * Generate a cryptographically secure CSRF token
     * 
     * Creates a random 64-character hexadecimal token using random_bytes(),
     * stores it in the session, and returns it for use in forms.
     * 
     * @return string The generated CSRF token (64 hex characters)
     * @throws Exception If random_bytes() fails to generate random data
     */
    public static function generateToken(): string
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate cryptographically secure random token (32 bytes = 64 hex chars)
        $token = bin2hex(random_bytes(32));

        // Store token in session
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    /**
     * Validate a submitted CSRF token against the session token
     * 
     * Uses hash_equals() for constant-time comparison to prevent timing attacks.
     * Returns false if session is not started, token is missing, or tokens don't match.
     * 
     * @param string $token The token to validate (from form submission)
     * @return bool True if token is valid, false otherwise
     */
    public static function validateToken(string $token): bool
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if session token exists
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // Get stored token from session
        $sessionToken = $_SESSION[self::SESSION_KEY];

        // Use hash_equals() for timing-attack-safe comparison
        return hash_equals($sessionToken, $token);
    }
}

</content>
<line_count>75</line_count>
</write_to_file>

---

**User:**

[write_to_file for 'core/Csrf.php'] Result:
<file_write_result>
<path>core/Csrf.php</path>
<operation>created</operation>
<notice>
<i>You do not need to re-read the file, as you have seen all changes</i>
<i>Proceed with the task using these changes as the new baseline.</i>
</notice>
</file_write_result>

<environment_details>
# VSCode Visible Files
core\Csrf.php

# VSCode Open Tabs
core/Router.php,modules/home/index.php,public/assets/js/main.js,public/assets/css/style.css,schema.sql,public/assets/css/main.css,public/.htaccess,project-rules.md,modules/inventory/models/InventoryModel.php,modules/inventory/index.php,core/Csrf.php,config/config.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-15T22:28:21.392Z
User time zone: Africa/Cairo, UTC+3:00

# Current Cost
$0.02

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
Created native CSRF security class at /core/Csrf.php with the following features:

**Security Implementation:**
- `Csrf::generateToken()`: Generates a cryptographically secure 64-character token using `bin2hex(random_bytes(32))`, stores it in `$_SESSION['csrf_token']`, and returns it for form inputs
- `Csrf::validateToken(string $token)`: Validates submitted tokens using `hash_equals()` for timing-attack-safe comparison, returns boolean

**Key Features:**
- Zero external dependencies (pure PHP 8.x)
- Follows PSR-12 coding standards
- Complete PHPDoc documentation
- Automatic session handling
- Constant-time comparison to prevent timing attacks
- Proper error handling for missing tokens

**Usage Example:**
```php
// In form:
$token = Csrf::generateToken();
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';

// On form submission:
if (Csrf::validateToken($_POST['csrf_token'])) {
    // Process form
} else {
    // Invalid token - potential CSRF attack
}
```

The class is ready for immediate use in protecting forms throughout the AmenERP application.
</result>
</attempt_completion>

