<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$userLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $userLoggedIn ? $_SESSION['username'] : '';

// Set page title if not already set
if (!isset($pageTitle)) {
    $pageTitle = 'Email Validator';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/generated-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/styles.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <div class="brand-icon bg-white bg-opacity-25 rounded-circle p-2 me-2">
                    <i class="bi bi-envelope-check fs-4 text-white"></i>
                </div>
                <span class="fw-bold">Email Validator</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="/">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <?php if ($userLoggedIn): ?>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown mx-1">
                        <a class="nav-link dropdown-toggle d-flex align-items-center px-3" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar bg-white bg-opacity-25 rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item py-2" href="/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>" href="/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-1">
                        <a class="btn btn-light rounded-pill px-3 py-2 <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>" href="/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>
