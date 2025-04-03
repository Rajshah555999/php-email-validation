<?php
// Include database and auth functions
require_once '../config/database.php';
require_once '../includes/auth.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Check if user is an admin
$isAdmin = false;
try {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :id");
    $stmt->bindParam(':id', $currentUser['id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && isset($user['is_admin']) && $user['is_admin'];
} catch (PDOException $e) {
    // Silently handle error
}

// Redirect to home if not an admin
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// Count total users
$totalUsers = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers = $result['count'];
} catch (PDOException $e) {
    // Silently handle error
}

// Count total validations
$totalValidations = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_validations");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalValidations = $result['count'];
} catch (PDOException $e) {
    // Silently handle error
}

// Get count of valid emails
$validEmails = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_validations WHERE is_valid = TRUE");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $validEmails = $result['count'];
} catch (PDOException $e) {
    // Silently handle error
}

// Get recent users
$recentUsers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently handle error
}

// Get recent validations
$recentValidations = [];
try {
    $stmt = $pdo->query("
        SELECT ev.*, u.username 
        FROM email_validations ev
        LEFT JOIN users u ON ev.user_id = u.id
        ORDER BY validation_date DESC 
        LIMIT 10
    ");
    $recentValidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently handle error
}

// Process admin actions
$actionMessage = '';
$actionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'make_admin' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = TRUE WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            if ($result) {
                $actionMessage = 'User successfully granted admin privileges.';
            } else {
                $actionError = 'Failed to update user.';
            }
        } catch (PDOException $e) {
            $actionError = 'Database error: ' . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'remove_admin' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        // Don't allow removing your own admin privileges
        if ($userId === $currentUser['id']) {
            $actionError = 'You cannot remove your own admin privileges.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET is_admin = FALSE WHERE id = :id");
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $result = $stmt->execute();
                if ($result) {
                    $actionMessage = 'Admin privileges successfully removed.';
                } else {
                    $actionError = 'Failed to update user.';
                }
            } catch (PDOException $e) {
                $actionError = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_validation' && isset($_POST['validation_id'])) {
        $validationId = (int)$_POST['validation_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM email_validations WHERE id = :id");
            $stmt->bindParam(':id', $validationId, PDO::PARAM_INT);
            $result = $stmt->execute();
            if ($result) {
                $actionMessage = 'Validation record successfully deleted.';
            } else {
                $actionError = 'Failed to delete validation record.';
            }
        } catch (PDOException $e) {
            $actionError = 'Database error: ' . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Admin Dashboard - Email Validator';
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
        /* Admin-specific styles */
        .admin-header {
            background-color: #343a40;
            color: white;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .admin-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-header">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-shield-lock fs-3 me-2"></i>
                <span class="fw-bold">Admin Dashboard</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> Main Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="bi bi-speedometer2"></i> My Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
            <!-- Admin Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="admin-icon bg-primary bg-opacity-10">
                            <i class="bi bi-person-badge fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h1 class="mb-0">Admin Dashboard</h1>
                            <p class="text-muted mb-0">Manage your Email Validator application</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Messages -->
            <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($actionMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($actionError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($actionError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Users</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalUsers); ?></h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-people fs-3 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100 stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Validations</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalValidations); ?></h2>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-check-circle fs-3 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100 stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Valid Emails</h6>
                                    <h2 class="mb-0"><?php echo number_format($validEmails); ?></h2>
                                    <p class="text-muted small mb-0">
                                        <?php 
                                        $percentage = ($totalValidations > 0) ? round(($validEmails / $totalValidations) * 100) : 0;
                                        echo "{$percentage}% of all validations";
                                        ?>
                                    </p>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-envelope-check fs-3 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users and Validations Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-content" 
                                            type="button" role="tab" aria-controls="users-content" aria-selected="true">
                                        <i class="bi bi-people"></i> Manage Users
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="validations-tab" data-bs-toggle="tab" data-bs-target="#validations-content" 
                                            type="button" role="tab" aria-controls="validations-content" aria-selected="false">
                                        <i class="bi bi-list-check"></i> Validation Records
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="adminTabsContent">
                                <!-- Users Tab -->
                                <div class="tab-pane fade show active" id="users-content" role="tabpanel" aria-labelledby="users-tab">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Admin</th>
                                                    <th>Created</th>
                                                    <th>Last Login</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                        <?php if ($user['id'] === $currentUser['id']): ?>
                                                            <span class="badge bg-primary ms-1">You</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <?php if (isset($user['is_admin']) && $user['is_admin']): ?>
                                                            <span class="badge bg-success">Admin</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">User</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        if (!empty($user['last_login'])) {
                                                            echo date('M j, Y g:i a', strtotime($user['last_login']));
                                                        } else {
                                                            echo '<span class="text-muted">Never</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($user['is_admin']) && $user['is_admin']): ?>
                                                            <?php if ($user['id'] !== $currentUser['id']): ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove admin privileges?');">
                                                                <input type="hidden" name="action" value="remove_admin">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="bi bi-shield-lock-fill"></i> Remove Admin
                                                                </button>
                                                            </form>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to grant admin privileges?');">
                                                                <input type="hidden" name="action" value="make_admin">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-shield-lock"></i> Make Admin
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <?php if (empty($recentUsers)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-3">No users found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Validations Tab -->
                                <div class="tab-pane fade" id="validations-content" role="tabpanel" aria-labelledby="validations-tab">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Email</th>
                                                    <th>Status</th>
                                                    <th>Type</th>
                                                    <th>User</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentValidations as $validation): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($validation['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($validation['email']); ?></td>
                                                    <td>
                                                        <?php if ($validation['is_valid']): ?>
                                                            <span class="badge bg-success">Valid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Invalid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        switch ($validation['validation_type']) {
                                                            case 'format':
                                                                echo '<span class="badge bg-warning text-dark">Format</span>';
                                                                break;
                                                            case 'mx':
                                                                echo '<span class="badge bg-info text-dark">MX</span>';
                                                                break;
                                                            case 'smtp':
                                                                echo '<span class="badge bg-secondary">SMTP</span>';
                                                                break;
                                                            case 'complete':
                                                                echo '<span class="badge bg-success">Complete</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="badge bg-secondary">Unknown</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (!empty($validation['username'])) {
                                                            echo htmlspecialchars($validation['username']);
                                                        } else {
                                                            echo '<span class="text-muted">Anonymous</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y g:i a', strtotime($validation['validation_date'])); ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this validation record?');">
                                                            <input type="hidden" name="action" value="delete_validation">
                                                            <input type="hidden" name="validation_id" value="<?php echo $validation['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <?php if (empty($recentValidations)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-3">No validation records found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Email Validator Tool - Admin Panel</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-muted">Logged in as: <?php echo htmlspecialchars($currentUser['username']); ?></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>