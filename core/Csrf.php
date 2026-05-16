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

// Made with Bob
