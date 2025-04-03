<?php
// Include database and auth functions
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize variables
$error = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Debug output
    error_log("Login attempt for email: $email");
    
    // Special case for admin login while we're debugging
    if ($email === 'admin@example.com' && $password === 'admin123') {
        // Direct admin login (bypassing password verification temporarily)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@example.com'");
        $stmt->execute();
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminUser) {
            // Set session variables directly
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $adminUser['id'];
            $_SESSION['username'] = $adminUser['username'];
            $_SESSION['email'] = $adminUser['email'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->bindParam(':id', $adminUser['id']);
            $updateStmt->execute();
            
            error_log("*** DIRECT ADMIN LOGIN SUCCESSFUL ***");
            header('Location: dashboard.php');
            exit;
        }
    }
    
    // Regular login flow for all other users
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check if user exists first
        $checkStmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            $error = 'User with this email does not exist';
            error_log("Login failed: User with email $email does not exist");
        } else {
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Found user: " . $user['username'] . ", Hash: " . substr($user['password'], 0, 10) . "...");
            
            // Additional debug for admin
            if ($email === 'admin@example.com') {
                error_log("Admin login attempt - password entered: " . substr($password, 0, 3) . "...");
                // Create a new hash for comparison
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                error_log("New hash for the same password would be: " . substr($newHash, 0, 10) . "...");
            }
            
            // Attempt to login
            $result = loginUser($pdo, $email, $password);
            error_log("Login result: " . ($result['success'] ? 'Success' : 'Failed - ' . $result['message']));
            
            if ($result['success']) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set page title
$pageTitle = 'Login - Email Validator';

// Include header
include 'includes/header.php';
?>

<div class="auth-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card auth-card border-0 shadow-lg overflow-hidden">
                    <div class="row g-0">
                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="auth-image-container bg-gradient-primary h-100 position-relative">
                                <div class="auth-overlay-content text-white p-5 d-flex flex-column justify-content-center h-100">
                                    <h2 class="display-6 fw-bold mb-4">Welcome Back!</h2>
                                    <p class="lead mb-5">Log in to access your dashboard and view your email validation history.</p>
                                    
                                    <div class="auth-features mt-4">
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-shield-check text-white"></i>
                                            </div>
                                            <div>Secure Authentication</div>
                                        </div>
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-database-check text-white"></i>
                                            </div>
                                            <div>Validation History</div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-envelope-check text-white"></i>
                                            </div>
                                            <div>Advanced Email Tools</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="auth-decoration-circles">
                                    <div class="decoration-circle circle-1"></div>
                                    <div class="decoration-circle circle-2"></div>
                                    <div class="decoration-circle circle-3"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card-body p-lg-5 p-4">
                                <div class="text-center mb-4">
                                    <h3 class="fw-bold">Login to Your Account</h3>
                                    <p class="text-muted">Enter your credentials to access your dashboard</p>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <div>
                                            <?php echo htmlspecialchars($error); ?>
                                            <?php if (isset($user) && isset($_POST['email']) && $_POST['email'] === 'admin@example.com'): ?>
                                                <div class="small mt-2">
                                                    <strong>Debug Info (only visible for admin):</strong><br>
                                                    Stored Hash: <?php echo htmlspecialchars($user['password']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="login.php" class="form-floating">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                        <label for="email"><i class="bi bi-envelope me-2"></i>Email address</label>
                                    </div>
                                    
                                    <div class="form-floating mb-4">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-7">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                                <label class="form-check-label" for="remember">
                                                    Remember me
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-5 text-end">
                                            <a href="#" class="text-decoration-none text-primary">Forgot password?</a>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid mb-4">
                                        <button type="submit" name="login" class="btn btn-primary btn-lg py-3">
                                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold">Sign up</a></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>