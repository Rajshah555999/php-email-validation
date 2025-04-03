<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Register a new user
 * 
 * @param PDO $pdo Database connection
 * @param string $username User's username
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @return array Result with status and message
 */
function registerUser(PDO $pdo, string $username, string $email, string $password): array {
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Email already registered'
            ];
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Username already taken'
            ];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, created_at) 
            VALUES (:username, :email, :password, NOW())
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Registration successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * Log in a user
 * 
 * @param PDO $pdo Database connection
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @return array Result with status and message
 */
function loginUser(PDO $pdo, string $email, string $password): array {
    try {
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        $passwordMatches = password_verify($password, $user['password']);
        error_log("Password verification for user {$user['email']}: " . ($passwordMatches ? 'Success' : 'Failed'));
        
        if (!$passwordMatches) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();
        
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        
        return [
            'success' => true,
            'message' => 'Login successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user information
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email']
    ];
}

/**
 * Log out the current user
 */
function logoutUser(): void {
    // Unset all session variables
    $_SESSION = [];
    
    // If a session cookie is used, delete it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

/**
 * Get user validation history
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Maximum number of records to return
 * @return array Array of validation records
 */
function getUserValidationHistory(PDO $pdo, int $userId, int $limit = 10): array {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM email_validations 
            WHERE user_id = :user_id 
            ORDER BY validation_date DESC 
            LIMIT :limit
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Return empty array on error
        return [];
    }
}

/**
 * Save validation result with user ID
 * 
 * @param PDO $pdo Database connection
 * @param string $email Email address
 * @param bool $isValid Is the email valid
 * @param string $validationType Type of validation performed
 * @param string $errorMessage Error message if validation failed
 * @param string $mxRecords JSON string of MX records
 * @param int|null $userId User ID or null for anonymous validation
 * @return bool Success status
 */
function saveValidationWithUser(
    PDO $pdo, 
    string $email, 
    bool $isValid, 
    string $validationType, 
    string $errorMessage, 
    string $mxRecords,
    ?int $userId = null
): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_validations (
                email, 
                is_valid, 
                validation_type, 
                error_message, 
                mx_records, 
                user_id,
                validation_date
            ) VALUES (
                :email, 
                :is_valid, 
                :validation_type, 
                :error_message, 
                :mx_records, 
                :user_id,
                NOW()
            )
        ");
        
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':is_valid', $isValid, PDO::PARAM_BOOL);
        $stmt->bindParam(':validation_type', $validationType);
        $stmt->bindParam(':error_message', $errorMessage);
        $stmt->bindParam(':mx_records', $mxRecords);
        $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}
?>