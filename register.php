<?php
// Include database and auth functions
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize variables
$error = '';
$success = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill out all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Attempt to register
        $result = registerUser($pdo, $username, $email, $password);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['message'];
        }
    }
}

// Set page title
$pageTitle = 'Register - Email Validator';

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
                                    <h2 class="display-6 fw-bold mb-4">Join Our Community</h2>
                                    <p class="lead mb-5">Create an account to save your validation history and access advanced email tools.</p>
                                    
                                    <div class="auth-features mt-4">
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-person-badge text-white"></i>
                                            </div>
                                            <div>Personal Dashboard</div>
                                        </div>
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-envelope-check text-white"></i>
                                            </div>
                                            <div>Save Validation History</div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="auth-icon-circle bg-white bg-opacity-25 me-3">
                                                <i class="bi bi-graph-up text-white"></i>
                                            </div>
                                            <div>Detailed Analytics</div>
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
                                    <h3 class="fw-bold">Create New Account</h3>
                                    <p class="text-muted">Fill out the form to get started with your account</p>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success d-flex align-items-center" role="alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <div>
                                            <?php echo htmlspecialchars($success); ?>
                                            <a href="login.php" class="alert-link">Login now</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="register.php" class="form-floating">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                                        <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                        <label for="email"><i class="bi bi-envelope me-2"></i>Email address</label>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                                        <div class="form-text mt-1">Password must be at least 6 characters long</div>
                                    </div>
                                    
                                    <div class="form-floating mb-4">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                        <label for="confirm_password"><i class="bi bi-lock-fill me-2"></i>Confirm password</label>
                                    </div>
                                    
                                    <div class="d-grid mb-4">
                                        <button type="submit" name="register" class="btn btn-primary btn-lg py-3">
                                            <i class="bi bi-person-plus me-2"></i> Create Account
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold">Sign in</a></p>
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