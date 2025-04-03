<?php
// Include auth functions
require_once 'includes/auth.php';

// Logout the user
logoutUser();

// Redirect to login page
header('Location: login.php');
exit;
?>