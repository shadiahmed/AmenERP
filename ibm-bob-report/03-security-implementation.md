# 🔐 Security Implementation
## Defense-in-Depth Security Architecture

---

## Overview

Security was the **primary design constraint** for AmenERP. Every architectural decision was evaluated through a security lens, resulting in a multi-layered defense system that protects against common web vulnerabilities. IBM Bob provided security guidance throughout development, ensuring best practices were followed at every layer.

---

## Security Philosophy

### Zero-Trust Approach

**Core Principle**: Never trust any input, from any source, at any time.

```
┌─────────────────────────────────────────────────────────┐
│              "Trust Nothing, Verify Everything"         │
│                                                          │
│  • All user input is potentially malicious              │
│  • All output must be escaped                           │
│  • All database queries must use prepared statements    │
│  • All state-changing operations require CSRF tokens    │
│  • All sessions must be hardened                        │
└─────────────────────────────────────────────────────────┘
```

---

## Security Layers

### Layer 1: Input Validation

#### Client-Side Validation (First Line of Defense)

**HTML5 Attributes**:
```html
<!-- Required fields -->
<input type="text" name="product_name" required>

<!-- Type validation -->
<input type="email" name="email" required>
<input type="number" name="quantity" min="0" required>

<!-- Pattern matching -->
<input type="text" name="sku" pattern="[A-Z0-9-]+" required>

<!-- Length constraints -->
<input type="text" name="name" minlength="2" maxlength="100" required>
```

**Benefits**:
- ✅ Immediate user feedback
- ✅ Reduces server load
- ✅ Better user experience
- ⚠️ **NOT a security measure** (easily bypassed)

**JavaScript Validation**:
```javascript
// Additional validation before submission
form.addEventListener('submit', (e) => {
    const quantity = parseInt(form.quantity.value);
    if (quantity < 0) {
        e.preventDefault();
        alert('Quantity cannot be negative');
        return false;
    }
});
```

#### Server-Side Validation (Security Boundary)

**Type Casting**:
```php
// Force type conversion
$quantity = (int)($_POST['quantity'] ?? 0);
$price = (float)($_POST['unit_price'] ?? 0);
$name = (string)($_POST['name'] ?? '');
```

**Range Validation**:
```php
// Validate ranges
if ($quantity < 0) {
    throw new Exception('Quantity cannot be negative');
}

if ($price < 0) {
    throw new Exception('Price cannot be negative');
}
```

**Length Validation**:
```php
// Validate string lengths
$name = trim($_POST['name'] ?? '');
if (strlen($name) < 2 || strlen($name) > 100) {
    throw new Exception('Name must be 2-100 characters');
}
```

**Format Validation**:
```php
// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email format');
}

// Validate SKU format
if (!preg_match('/^[A-Z0-9-]+$/', $sku)) {
    throw new Exception('Invalid SKU format');
}
```

---

### Layer 2: CSRF Protection

#### Token Generation

**File**: `core/Csrf.php`

```php
declare(strict_types=1);

class Csrf
{
    /**
     * Generate cryptographically secure CSRF token
     */
    public static function generateToken(): string
    {
        // Generate 32 random bytes (256 bits)
        $token = bin2hex(random_bytes(32));
        
        // Store in session
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }
}
```

**Security Features**:
- ✅ Uses `random_bytes()` (cryptographically secure)
- ✅ 256-bit token (2^256 possible values)
- ✅ Server-side storage (not in cookies)
- ✅ Session-bound (unique per user)

#### Token Validation

```php
public static function validateToken(string $token): bool
{
    // Get stored token
    $storedToken = $_SESSION['csrf_token'] ?? '';
    
    // Timing-safe comparison (prevents timing attacks)
    $valid = hash_equals($storedToken, $token);
    
    // Regenerate token after validation (one-time use)
    if ($valid) {
        self::generateToken();
    }
    
    return $valid;
}
```

**Security Features**:
- ✅ `hash_equals()` prevents timing attacks
- ✅ Token rotation after use (prevents replay)
- ✅ Empty string default (prevents null comparison issues)

#### Implementation Pattern

**In Views**:
```php
<?php
$csrfToken = Csrf::generateToken();
?>
<form method="POST" action="/inventory/add">
    <input type="hidden" name="csrf_token" 
           value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Other fields -->
</form>
```

**In Controllers**:
```php
// Validate CSRF token FIRST
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Continue with request processing...
```

**AI Contribution**: Bob designed the token rotation mechanism to prevent replay attacks.

---

### Layer 3: XSS Prevention

#### Universal Output Escaping

**The Golden Rule**: **ALWAYS** escape output with `htmlspecialchars()`

```php
// ✅ CORRECT - Escaped output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ INCORRECT - Raw output (DANGEROUS!)
echo $userInput;
```

**Parameters Explained**:
- `ENT_QUOTES`: Escapes both single (') and double (") quotes
- `UTF-8`: Specifies character encoding (prevents charset attacks)

#### Context-Specific Escaping

**HTML Context**:
```php
<div class="product-name">
    <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
</div>
```

**Attribute Context**:
```php
<input type="text" 
       value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
```

**JavaScript Context** (avoid if possible):
```php
<script>
const productName = <?php echo json_encode($product['name']); ?>;
</script>
```

**URL Context**:
```php
<a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">Link</a>
```

#### What Gets Escaped

```php
// Input: <script>alert('XSS')</script>
// Output: <script>alert(&#039;XSS&#039;)</script>

// Input: " onclick="alert('XSS')
// Output: " onclick="alert(&#039;XSS&#039;)

// Input: ' OR '1'='1
// Output: &#039; OR &#039;1&#039;=&#039;1
```

**Result**: Malicious code is rendered as harmless text.

---

### Layer 4: SQL Injection Prevention

#### PDO Prepared Statements

**The Golden Rule**: **NEVER** concatenate user input into SQL queries.

```php
// ✅ CORRECT - Prepared statement
$sql = "SELECT * FROM products WHERE id = :id AND status = :status";
$stmt = $db->query($sql, [
    'id' => $productId,
    'status' => 'active'
]);

// ❌ INCORRECT - String concatenation (DANGEROUS!)
$sql = "SELECT * FROM products WHERE id = $productId";
$stmt = $db->query($sql);
```

#### How Prepared Statements Work

```
┌─────────────────────────────────────────────────────────┐
│ Step 1: Prepare Statement                               │
│ SQL: SELECT * FROM products WHERE id = :id              │
│ Database parses and compiles query plan                 │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ Step 2: Bind Parameters                                 │
│ :id => 123 (treated as INTEGER, not SQL code)           │
│ Database knows this is DATA, not INSTRUCTIONS           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ Step 3: Execute                                         │
│ Query runs with bound parameters                        │
│ No SQL injection possible                               │
└─────────────────────────────────────────────────────────┘
```

#### Attack Prevention Example

**Malicious Input**:
```php
$productId = "1 OR 1=1"; // Attempt to bypass WHERE clause
```

**With String Concatenation** (VULNERABLE):
```php
$sql = "SELECT * FROM products WHERE id = $productId";
// Becomes: SELECT * FROM products WHERE id = 1 OR 1=1
// Returns ALL products (security breach!)
```

**With Prepared Statements** (SECURE):
```php
$sql = "SELECT * FROM products WHERE id = :id";
$stmt = $db->query($sql, ['id' => $productId]);
// Database treats "1 OR 1=1" as a literal string
// No products found (attack prevented!)
```

#### Database Class Implementation

```php
public function query(string $sql, array $params = []): PDOStatement
{
    try {
        // Prepare statement
        $stmt = $this->pdo->prepare($sql);
        
        // Bind and execute
        $stmt->execute($params);
        
        return $stmt;
        
    } catch (PDOException $e) {
        // Log error (don't expose to user)
        error_log('Database error: ' . $e->getMessage());
        throw new Exception('Database operation failed');
    }
}
```

**Security Features**:
- ✅ Automatic parameter binding
- ✅ Type-safe parameter handling
- ✅ Error message sanitization
- ✅ Exception handling

---

### Layer 5: Session Security

#### Session Configuration

**File**: `config/config.php`

```php
// Prevent JavaScript access to session cookie
ini_set('session.cookie_httponly', '1');

// HTTPS only (production)
ini_set('session.cookie_secure', '1');

// CSRF protection at cookie level
ini_set('session.cookie_samesite', 'Strict');

// Reject uninitialized session IDs
ini_set('session.use_strict_mode', '1');

// No URL-based sessions
ini_set('session.use_only_cookies', '1');

// Start session
session_start();
```

#### Configuration Explained

**`session.cookie_httponly`**:
- Prevents JavaScript from accessing session cookie
- Mitigates XSS-based session theft
- Cookie only sent via HTTP(S), not accessible to `document.cookie`

**`session.cookie_secure`**:
- Cookie only sent over HTTPS
- Prevents session hijacking on insecure networks
- Should be enabled in production

**`session.cookie_samesite`**:
- `Strict`: Cookie only sent for same-site requests
- Prevents CSRF attacks at cookie level
- Blocks cross-origin session usage

**`session.use_strict_mode`**:
- Rejects session IDs not created by server
- Prevents session fixation attacks
- Forces regeneration of suspicious IDs

**`session.use_only_cookies`**:
- Disables URL-based session IDs
- Prevents session ID leakage in URLs
- Blocks session ID in referrer headers

#### Session Regeneration

```php
// Regenerate session ID after login
session_regenerate_id(true);

// Regenerate after privilege escalation
if ($userPromotedToAdmin) {
    session_regenerate_id(true);
}
```

**Why**: Prevents session fixation attacks.

---

### Layer 6: Type Safety

#### Strict Types Declaration

**Every PHP file starts with**:
```php
declare(strict_types=1);
```

**Benefits**:
- ✅ Prevents type coercion vulnerabilities
- ✅ Catches type errors at runtime
- ✅ Enforces function signatures
- ✅ Predictable behavior

#### Type Coercion Vulnerabilities (Prevented)

**Without Strict Types**:
```php
function processPayment(int $amount) {
    // Process payment
}

processPayment("100"); // Silently converts string to int
processPayment("100abc"); // Converts to 100 (data loss!)
processPayment("abc"); // Converts to 0 (security issue!)
```

**With Strict Types**:
```php
declare(strict_types=1);

function processPayment(int $amount) {
    // Process payment
}

processPayment("100"); // TypeError exception
processPayment(100);   // ✅ Works correctly
```

#### Type Hints

```php
// Function parameters
public function createProduct(
    string $name,
    string $sku,
    int $quantity,
    float $price
): bool {
    // Implementation
}

// Return types
public function getProductById(int $id): ?array {
    // Returns array or null
}

// Property types (PHP 7.4+)
class Product {
    private int $id;
    private string $name;
    private float $price;
}
```

---

## Attack Prevention Matrix

| Attack Type | Prevention Mechanism | Implementation |
|-------------|---------------------|----------------|
| **SQL Injection** | PDO Prepared Statements | All queries use parameter binding |
| **XSS** | Output Escaping | `htmlspecialchars()` on all output |
| **CSRF** | Token Validation | Required on all POST/PUT/DELETE |
| **Session Hijacking** | Secure Cookies | `httponly`, `secure`, `samesite` |
| **Session Fixation** | ID Regeneration | After login and privilege changes |
| **Type Confusion** | Strict Types | `declare(strict_types=1)` everywhere |
| **Path Traversal** | Input Validation | Whitelist allowed paths |
| **File Upload** | Type/Size Validation | (Future feature) |
| **Brute Force** | Rate Limiting | (Future feature) |
| **Timing Attacks** | Constant-Time Comparison | `hash_equals()` for tokens |

---

## Security Testing Checklist

### Manual Testing

- [ ] Submit forms without CSRF token → Should reject
- [ ] Submit forms with invalid CSRF token → Should reject
- [ ] Submit forms with expired CSRF token → Should reject
- [ ] Try SQL injection in all input fields → Should be escaped
- [ ] Try XSS payloads in all input fields → Should be escaped
- [ ] Try negative numbers where not allowed → Should reject
- [ ] Try strings where numbers expected → Should reject
- [ ] Access session cookie via JavaScript → Should fail
- [ ] Submit form from different domain → Should reject (SameSite)

### Automated Testing (Future)

```php
// Example security test
public function testSqlInjectionPrevention()
{
    $maliciousInput = "1' OR '1'='1";
    $result = $model->getProductById($maliciousInput);
    $this->assertNull($result); // Should find nothing
}

public function testXssPrevention()
{
    $maliciousInput = "<script>alert('XSS')</script>";
    $output = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');
    $this->assertStringNotContainsString('<script>', $output);
}
```

---

## Security Best Practices

### Input Handling

```php
// ✅ DO: Validate, sanitize, type-cast
$quantity = (int)($_POST['quantity'] ?? 0);
if ($quantity < 0) {
    throw new Exception('Invalid quantity');
}

// ❌ DON'T: Trust input directly
$quantity = $_POST['quantity'];
```

### Output Handling

```php
// ✅ DO: Always escape
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

// ❌ DON'T: Output raw data
echo $data;
```

### Database Queries

```php
// ✅ DO: Use prepared statements
$stmt = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $id]);

// ❌ DON'T: Concatenate user input
$stmt = $db->query("SELECT * FROM products WHERE id = $id");
```

### CSRF Protection

```php
// ✅ DO: Validate on every state-changing operation
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    die('Invalid token');
}

// ❌ DON'T: Skip validation
// Process request without checking token
```

### Error Handling

```php
// ✅ DO: Log technical details, show generic message
try {
    // Operation
} catch (Exception $e) {
    error_log($e->getMessage()); // Log for debugging
    $_SESSION['error'] = 'An error occurred'; // Generic message
}

// ❌ DON'T: Expose technical details to users
catch (Exception $e) {
    echo $e->getMessage(); // Reveals system information
}
```

---

## AI Collaboration in Security

### IBM Bob's Security Contributions

1. **CSRF Token Design**: Suggested cryptographically secure generation
2. **Timing Attack Prevention**: Recommended `hash_equals()` for token comparison
3. **Session Hardening**: Provided complete session configuration
4. **Output Escaping**: Emphasized universal escaping requirement
5. **Prepared Statements**: Enforced zero-concatenation policy
6. **Type Safety**: Advocated for strict types everywhere

### Security Review Process

```
1. Developer implements feature
   ↓
2. Bob reviews for security issues
   ↓
3. Bob identifies vulnerabilities
   ↓
4. Bob suggests fixes
   ↓
5. Developer implements fixes
   ↓
6. Bob verifies security
```

---

## Future Security Enhancements

### Planned Improvements

1. **Authentication System**
   - Password hashing with `password_hash()`
   - Account lockout after failed attempts
   - Two-factor authentication (2FA)

2. **Authorization System**
   - Role-based access control (RBAC)
   - Permission checking on all operations
   - Audit trail for sensitive actions

3. **Rate Limiting**
   - Prevent brute force attacks
   - Limit API requests per user
   - Throttle failed login attempts

4. **Content Security Policy (CSP)**
   - Restrict script sources
   - Prevent inline scripts
   - Block unsafe-eval

5. **Security Headers**
   - X-Frame-Options (clickjacking prevention)
   - X-Content-Type-Options (MIME sniffing prevention)
   - Strict-Transport-Security (HSTS)

6. **Input Sanitization Library**
   - Centralized sanitization functions
   - Context-aware escaping
   - Whitelist-based validation

---

## Conclusion

AmenERP's security architecture demonstrates that **security doesn't require frameworks**. By implementing defense-in-depth with native PHP features, we've created a system that:

- ✅ Prevents SQL injection through prepared statements
- ✅ Prevents XSS through universal output escaping
- ✅ Prevents CSRF through cryptographic tokens
- ✅ Prevents session attacks through hardened configuration
- ✅ Prevents type confusion through strict types

The collaboration with IBM Bob ensured security was considered at every stage, not bolted on as an afterthought.

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-05-17  
**AI Assistant**: IBM Bob