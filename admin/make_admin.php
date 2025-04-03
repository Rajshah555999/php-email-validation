<?php
// This script is used to create the first admin user
// Include database connection
require_once '../config/database.php';

// Set up variables
$message = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_admin'])) {
    $email = trim($_POST['email']);
    $adminKey = trim($_POST['admin_key']);
    
    // Check for the special admin key
    // In a real-world scenario, you would use an environment variable
    // or a more secure method, but for demonstration purposes we'll use a hardcoded value
    $correctAdminKey = 'admin123';
    
    if ($adminKey !== $correctAdminKey) {
        $error = 'Invalid admin key provided.';
    } else {
        try {
            // Find user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $error = 'User with this email does not exist.';
            } else {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update user to be an admin
                $updateStmt = $pdo->prepare("UPDATE users SET is_admin = TRUE WHERE id = :id");
                $updateStmt->bindParam(':id', $user['id']);
                
                if ($updateStmt->execute()) {
                    $message = 'User ' . htmlspecialchars($user['username']) . ' has been granted admin privileges successfully!';
                } else {
                    $error = 'Failed to update user privileges.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Create Admin User - Email Validator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../generated-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/styles.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-key-form {
            max-width: 500px;
            margin: 0 auto;
        }
        .admin-header {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-header">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-shield-lock fs-3 me-2"></i>
                <span class="fw-bold">Admin Setup</span>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> Main Site
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2 text-center mb-5">
                    <div class="display-5 mb-3">
                        <i class="bi bi-shield-check text-primary"></i>
                    </div>
                    <h1 class="mb-3">Create First Admin User</h1>
                    <p class="lead text-muted">
                        This page allows you to designate a user as an administrator.
                        You must know the admin key to perform this action.
                    </p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            
                            <?php if (!empty($message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $message; ?>
                                <div class="mt-3">
                                    <a href="index.php" class="btn btn-primary">Go to Admin Dashboard</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($message)): ?>
                            <form method="POST" action="make_admin.php" class="admin-key-form">
                                <div class="mb-3">
                                    <label for="email" class="form-label">User Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="Enter user email" required>
                                    </div>
                                    <div class="form-text">Enter the email of the user you want to make an admin</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="admin_key" class="form-label">Admin Key</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" class="form-control" id="admin_key" name="admin_key" 
                                               placeholder="Enter admin key" required>
                                    </div>
                                    <div class="form-text">Enter the admin key to authorize this action</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="make_admin" class="btn btn-primary">
                                        <i class="bi bi-shield-lock"></i> Create Admin User
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Email Validator Tool - Admin Setup</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>