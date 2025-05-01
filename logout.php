<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Logout the user
session_start(); // Ensure session is started
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
setcookie(session_name(), '', time() - 3600); // Destroy the cookie

// Redirect to home page with success message
flashMessage('You have been successfully logged out.', 'success');
redirect('index.php');
?> 