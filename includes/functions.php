<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is of a specific type
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == $type;
}

// Get current user object based on type
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = (int)$_SESSION['user_id']; // Ensure ID is an integer
    $user_type = $_SESSION['user_type'];
    
    switch ($user_type) {
        case 'user':
            return new Viewer($user_id);
        case 'artist':
            return new Artist($user_id);
        case 'admin':
            return new Admin($user_id);
        case 'advisor':
            return new Advisor($user_id);
        default:
            return null;
    }
}

// Redirect user to a specific page
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Display flash message
function flashMessage($message = null, $type = 'info') {
    if (isset($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    } else if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }
    
    return '';
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Clean input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Format price with currency
function formatPrice($price, $currency = '$') {
    return $currency . number_format($price, 2);
}

// Format date
function formatDate($date, $format = 'd M Y, H:i') {
    return date($format, strtotime($date));
}

// Check if a file is an image
function isImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowedTypes);
}

// Upload image
function uploadImage($file, $directory = 'assets/img/uploads/') {
    if (!isImage($file)) {
        return false;
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = generateRandomString() . '_' . basename($file['name']);
    $targetPath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return false;
}

// Get user type name
function getUserTypeName($type) {
    switch ($type) {
        case 'user':
            return 'Viewer';
        case 'artist':
            return 'Artist';
        case 'admin':
            return 'Administrator';
        case 'advisor':
            return 'Art Advisor';
        default:
            return 'User';
    }
}

// Calculate average rating
function calculateAverageRating($ratings) {
    if (empty($ratings)) {
        return 0;
    }
    
    $sum = array_sum($ratings);
    return $sum / count($ratings);
} 