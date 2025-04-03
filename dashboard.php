<?php
// Include database and auth functions
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/email_validator.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Check if user is an admin
$isAdmin = false;
try {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && isset($user['is_admin']) && $user['is_admin'];
} catch (PDOException $e) {
    // Silently handle error
}

// Initialize variables
$email = '';
$validationResult = null;
$showResult = false;

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
    
    // Save to database with user ID
    $saveSuccess = saveValidationWithUser(
        $pdo, 
        $email, 
        $isValid, 
        $validationType, 
        $errorMessage, 
        $validator->getMxRecordsJson(),
        $userId
    );
    
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

// Get user validation history
$userValidations = getUserValidationHistory($pdo, $userId, 10);

// Set page title
$pageTitle = 'Dashboard - Email Validator';

// Include header
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- User Profile Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> User Profile</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="display-1 text-primary">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h4 class="card-title"><?php echo htmlspecialchars($currentUser['username']); ?></h4>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    </div>
                    <div class="d-grid gap-2">
                        <?php if ($isAdmin): ?>
                        <a href="admin/index.php" class="btn btn-outline-primary mb-2">
                            <i class="bi bi-shield-lock"></i> Admin Dashboard
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Your Stats</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Count total validations
                    $totalValidations = count($userValidations);
                    
                    // Count valid emails
                    $validEmails = 0;
                    foreach ($userValidations as $validation) {
                        if ($validation['is_valid']) {
                            $validEmails++;
                        }
                    }
                    
                    // Calculate percentage if there are validations
                    $validPercentage = ($totalValidations > 0) ? round(($validEmails / $totalValidations) * 100) : 0;
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h2 class="display-5 text-primary"><?php echo $totalValidations; ?></h2>
                            <p class="text-muted">Total Validations</p>
                        </div>
                        <div class="col-6 mb-3">
                            <h2 class="display-5 text-success"><?php echo $validEmails; ?></h2>
                            <p class="text-muted">Valid Emails</p>
                        </div>
                    </div>
                    
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $validPercentage; ?>%;" 
                             aria-valuenow="<?php echo $validPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $validPercentage; ?>% Valid
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Email Validator Tool Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle"></i> Email Validator Tool</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Enter an email address to validate it through MX record and SMTP verification.</p>
                    
                    <form id="emailForm" method="POST" action="">
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   placeholder="Enter email address" value="<?php echo htmlspecialchars($email); ?>" required>
                            <button class="btn btn-primary btn-lg btn-validate" type="submit" id="validateBtn">
                                <i class="bi bi-check-lg"></i> Validate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($showResult): ?>
            <!-- Validation Result Card -->
            <div id="resultContainer" data-show="true" class="result-card card shadow-sm mb-4 <?php echo $validationResult['isValid'] ? 'result-valid' : 'result-invalid'; ?>">
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
                                            echo '<span class="badge bg-warning">Basic Format</span>';
                                            break;
                                        case 'mx':
                                            echo '<span class="badge bg-info">MX Record</span>';
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
            </div>
            <?php endif; ?>
            
            <!-- Validation History Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Your Validation History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($userValidations)): ?>
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
                                <?php foreach ($userValidations as $validation): ?>
                                <tr>
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
                            <i class="bi bi-inbox display-1 text-muted"></i>
                        </div>
                        <p class="text-muted">You haven't performed any email validations yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>