<?php
// Include database connection and required files
require_once 'config/database.php';
require_once 'includes/email_validator.php';
require_once 'includes/auth.php';

// Initialize variables
$email = '';
$validationResult = null;
$showResult = false;
$currentUser = null;

// Check if user is logged in
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Create validator instance
    $validator = new EmailValidator($email);
    
    // Perform validation
    $isValid = $validator->validate();
    
    // Get validation details
    $errorMessage = $validator->getErrorMessage();
    $mxRecords = $validator->getMxRecords();
    $validationType = $validator->getValidationType();
    
    // Save to database with optional user ID
    $userId = $currentUser ? $currentUser['id'] : null;
    
    if ($currentUser) {
        // Save with user ID if logged in
        $saveSuccess = saveValidationWithUser(
            $pdo, 
            $email, 
            $isValid, 
            $validationType, 
            $errorMessage, 
            $validator->getMxRecordsJson(),
            $userId
        );
    } else {
        // Save without user ID if not logged in
        $saveSuccess = saveValidationToDatabase(
            $pdo, 
            $email, 
            $isValid, 
            $validationType, 
            $errorMessage, 
            $validator->getMxRecordsJson()
        );
    }
    
    // Prepare result data
    $validationResult = [
        'email' => $email,
        'isValid' => $isValid,
        'errorMessage' => $errorMessage,
        'mxRecords' => $mxRecords,
        'validationType' => $validationType
    ];
    
    $showResult = true;
}

// Get recent validations
$recentValidations = [];
try {
    // If logged in, show only the user's most recent validations
    // Otherwise, show the most recent public validations
    if ($currentUser) {
        $stmt = $pdo->prepare("
            SELECT * FROM email_validations 
            WHERE user_id = :user_id 
            ORDER BY validation_date DESC 
            LIMIT 5
        ");
        $stmt->bindParam(':user_id', $currentUser['id'], PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // For public view, only show anonymous validations (no user_id)
        $stmt = $pdo->query("
            SELECT * FROM email_validations 
            WHERE user_id IS NULL
            ORDER BY validation_date DESC 
            LIMIT 5
        ");
    }
    $recentValidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently handle error
    $recentValidations = [];
}

// Set page title
$pageTitle = 'Email Validator - Professional Email Validation Tool';

// Include header
include 'includes/header.php';
?>

<div class="hero-section bg-gradient-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 py-5">
                <h1 class="display-3 fw-bold mb-3">Professional Email Validation</h1>
                <p class="lead fs-4 mb-4">Validate emails with precision using advanced MX record lookup and SMTP verification technology</p>
                <div class="d-flex gap-3 mb-4">
                    <div class="d-flex align-items-center">
                        <div class="badge bg-white text-primary p-2 rounded-circle me-2">
                            <i class="bi bi-check-lg fs-5"></i>
                        </div>
                        <span class="fs-5">99.9% Accuracy</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="badge bg-white text-primary p-2 rounded-circle me-2">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <span class="fs-5">Secure</span>
                    </div>
                </div>
                <a href="#validator-tool" class="btn btn-light btn-lg px-4 me-2">
                    <i class="bi bi-envelope-check me-2"></i>Try it Now
                </a>
                <?php if (!$currentUser): ?>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="text-center position-relative">
                    <div class="hero-image-container">
                        <div class="hero-image-elements">
                            <div class="floating-email floating-email-1">
                                <i class="bi bi-envelope-check text-success"></i>
                                <span>valid@example.com</span>
                            </div>
                            <div class="floating-email floating-email-2">
                                <i class="bi bi-envelope-x text-danger"></i>
                                <span>invalid@nowhere</span>
                            </div>
                            <div class="floating-email floating-email-3">
                                <i class="bi bi-envelope-check text-success"></i>
                                <span>contact@company.com</span>
                            </div>
                            <div class="floating-circle-1"></div>
                            <div class="floating-circle-2"></div>
                            <div class="floating-circle-3"></div>
                        </div>
                        <div class="hero-image-base">
                            <i class="bi bi-envelope-check display-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="section-heading text-center mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill">Features</span>
        <h2 class="display-5 fw-bold">Comprehensive Email Validation</h2>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">Our tool performs multiple validation steps to ensure maximum accuracy</p>
    </div>
    
    <div class="row g-4 justify-content-center mb-5">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto mb-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h5 class="card-title">Format Validation</h5>
                    <p class="card-text text-muted">Checks email syntax and formatting according to RFC standards</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto mb-4">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <h5 class="card-title">MX Record Check</h5>
                    <p class="card-text text-muted">Verifies domain has valid mail exchange servers configured</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto mb-4">
                        <i class="bi bi-envelope-check"></i>
                    </div>
                    <h5 class="card-title">SMTP Verification</h5>
                    <p class="card-text text-muted">Ensures email address actually exists at the mail server</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto mb-4">
                        <i class="bi bi-database-check"></i>
                    </div>
                    <h5 class="card-title">History Tracking</h5>
                    <p class="card-text text-muted">Store and access your past email validation results</p>
                </div>
            </div>
        </div>
    </div>
            
            <?php if (!$currentUser): ?>
            <div class="alert alert-info d-inline-flex align-items-center shadow-sm mb-5">
                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                <div>
                    <strong>Want to save your validation history?</strong> 
                    <a href="register.php" class="alert-link">Create an account</a> or 
                    <a href="login.php" class="alert-link">log in</a> to access your dashboard.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="validator-tool" class="validation-tool-section py-5 my-5">
        <div class="container">
            <div class="section-heading text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill">Email Validator</span>
                <h2 class="display-5 fw-bold">Validate Any Email</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">Enter an email address below to check its validity</p>
            </div>
            
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card shadow border-0 rounded-3 validator-card">
                        <div class="card-body p-md-5 p-4">
                            <form id="emailForm" method="POST" action="">
                                <div class="large-input-group">
                                    <div class="input-group input-group-lg mb-3">
                                        <span class="input-group-text bg-light border-0"><i class="bi bi-envelope-fill text-primary"></i></span>
                                        <input type="email" class="form-control form-control-lg border-0 shadow-none" id="email" name="email" 
                                               placeholder="Enter email address" value="<?php echo htmlspecialchars($email); ?>" required>
                                        <button class="btn btn-primary btn-lg px-4 btn-validate" type="submit" id="validateBtn">
                                            <i class="bi bi-check-lg me-2"></i> Validate
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4 validation-features">
                                    <div class="d-flex flex-wrap justify-content-center gap-3">
                                        <div class="badge bg-light text-dark py-2 px-3 d-flex align-items-center">
                                            <i class="bi bi-shield-check text-primary me-2"></i> RFC Format
                                        </div>
                                        <div class="badge bg-light text-dark py-2 px-3 d-flex align-items-center">
                                            <i class="bi bi-diagram-3 text-primary me-2"></i> MX Records
                                        </div>
                                        <div class="badge bg-light text-dark py-2 px-3 d-flex align-items-center">
                                            <i class="bi bi-server text-primary me-2"></i> SMTP Validation
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($showResult): ?>
    <div id="resultContainer" data-show="true" class="row mb-5 fade-in">
        <div class="col-lg-8 offset-lg-2">
            <div class="result-card card shadow-sm border-0 <?php echo $validationResult['isValid'] ? 'result-valid' : 'result-invalid'; ?>">
                <div class="card-header <?php echo $validationResult['isValid'] ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <h5 class="mb-0">Validation Result</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if ($validationResult['isValid']): ?>
                            <div class="status-badge bg-success text-white">
                                <i class="bi bi-check-circle-fill"></i> Valid Email
                            </div>
                        <?php else: ?>
                            <div class="status-badge bg-danger text-white">
                                <i class="bi bi-exclamation-circle-fill"></i> Invalid Email
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <h6 class="text-muted">Email Checked:</h6>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($validationResult['email']); ?></p>
                            </div>
                            
                            <?php if (!$validationResult['isValid']): ?>
                            <div class="mb-3">
                                <h6 class="text-muted">Reason:</h6>
                                <p class="text-danger"><?php echo htmlspecialchars($validationResult['errorMessage']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <h6 class="text-muted">Validation Type:</h6>
                                <p>
                                    <?php 
                                    switch ($validationResult['validationType']) {
                                        case 'format':
                                            echo '<span class="badge bg-warning text-dark">Basic Format</span>';
                                            break;
                                        case 'mx':
                                            echo '<span class="badge bg-info text-dark">MX Record</span>';
                                            break;
                                        case 'smtp':
                                            echo '<span class="badge bg-secondary">SMTP Verification</span>';
                                            break;
                                        case 'complete':
                                            echo '<span class="badge bg-success">Complete Validation</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <?php if (!empty($validationResult['mxRecords'])): ?>
                            <div class="mb-3">
                                <button id="toggleMxRecords" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Show MX Records
                                </button>
                                
                                <div id="mxRecordsSection" style="display: none;" class="mt-3">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mx-records-table">
                                            <thead>
                                                <tr>
                                                    <th>Host</th>
                                                    <th>Priority</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($validationResult['mxRecords'] as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['host']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['weight']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!$currentUser): ?>
                <div class="card-footer bg-light">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-save me-2"></i>
                        <span>
                            <a href="register.php" class="text-decoration-none">Create an account</a> 
                            to save your validation history.
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($currentUser): /* Only show recent validations for logged-in users */ ?>
    <div class="row mb-5">
        <div class="col-lg-8 offset-lg-2">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Your Recent Validations
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentValidations)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Validation Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentValidations as $validation): ?>
                                <tr class="recent-validation">
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
                                    <td><?php echo date('M j, Y g:i a', strtotime($validation['validation_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-inbox display-3 text-muted"></i>
                        </div>
                        <p class="text-muted">No validations performed yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($currentUser): ?>
                <div class="card-footer bg-light">
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-speedometer2"></i> View All in Dashboard
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Benefits Section -->
    <div class="benefits-section py-5 my-5">
        <div class="container">
            <div class="section-heading text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill">Benefits</span>
                <h2 class="display-5 fw-bold">Why Choose Our Email Validator?</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">Our professional tool offers multiple advantages for email verification</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-10">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="benefit-card p-4 bg-white rounded-4 shadow-sm h-100 border-start border-5 border-primary">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-shield-check text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold">Advanced Validation</h5>
                                        <p class="text-muted">Our tool performs comprehensive checks at multiple levels, ensuring accurate results with both MX and SMTP verification.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="benefit-card p-4 bg-white rounded-4 shadow-sm h-100 border-start border-5 border-primary">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-speedometer text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold">Lightning Fast</h5>
                                        <p class="text-muted">Get validation results in seconds, with optimized backend processing for quick turnaround of even complex verification requests.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="benefit-card p-4 bg-white rounded-4 shadow-sm h-100 border-start border-5 border-primary">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-eye text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold">Complete Transparency</h5>
                                        <p class="text-muted">View detailed MX records and validation steps to understand exactly why an email is considered valid or invalid.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="benefit-card p-4 bg-white rounded-4 shadow-sm h-100 border-start border-5 border-primary">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-person-check text-primary fs-3"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold">Personal Dashboard</h5>
                                        <p class="text-muted">Create an account to save your validation history and access a comprehensive dashboard with all your past verification results.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="#validator-tool" class="btn btn-primary btn-lg px-5 py-3 rounded-pill">
                            <i class="bi bi-arrow-right-circle me-2"></i> Try It Now
                        </a>
                        <?php if (!$currentUser): ?>
                        <a href="register.php" class="btn btn-outline-primary btn-lg ms-3 px-5 py-3 rounded-pill">
                            <i class="bi bi-person-plus me-2"></i> Sign Up Free
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; /* End of "if ($currentUser)" section for recent validations */ ?>

<?php
// Include footer
include 'includes/footer.php';
?>