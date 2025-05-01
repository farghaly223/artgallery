<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a regular user
if (!isLoggedIn() || !isUserType('user')) {
    flashMessage('You must be logged in as a user to subscribe to artists.', 'danger');
    redirect('login.php');
}

// Check if artist_id is provided
if (!isset($_GET['artist_id']) || !is_numeric($_GET['artist_id'])) {
    flashMessage('Invalid artist specified.', 'danger');
    redirect('artists.php');
}

$userId = $_SESSION['user_id'];
$artistUserId = (int)$_GET['artist_id'];

// Get current user
$user = getCurrentUser();

// Get database instance
$db = Database::getInstance();

// Check if artist exists and is an active artist and get their artist_id
$artist = $db->selectOne("
    SELECT u.user_id, u.username, u.user_type, u.is_banned, ap.artist_id
    FROM users u
    JOIN artist_profiles ap ON u.user_id = ap.user_id
    WHERE u.user_id = :artist_user_id AND u.user_type = 'artist' AND u.is_banned = 0
", ['artist_user_id' => $artistUserId]);

if (!$artist) {
    flashMessage('Artist not found or is not active.', 'danger');
    redirect('artists.php');
}

// Get the actual artist_id from the artist_profiles table
$artistId = $artist['artist_id'];

// Check if already subscribed
$existingSubscription = $db->selectOne("
    SELECT * FROM subscriptions 
    WHERE user_id = :user_id AND artist_id = :artist_id
", [
    'user_id' => $userId,
    'artist_id' => $artistId
]);

if ($existingSubscription) {
    flashMessage('You are already subscribed to this artist.', 'info');
    redirect('artist_profile.php?id=' . $artistUserId);
}

// Create subscription
$result = $db->insert('subscriptions', [
    'user_id' => $userId,
    'artist_id' => $artistId,
    'created_at' => date('Y-m-d H:i:s')
]);

if ($result) {
    // Update artist subscribers count
    $db->query(
        "UPDATE artist_profiles SET subscribers_count = subscribers_count + 1 WHERE artist_id = :artist_id",
        ['artist_id' => $artistId]
    );
    
    flashMessage('Successfully subscribed to ' . $artist['username'] . '.', 'success');
} else {
    flashMessage('Failed to subscribe to artist. Please try again.', 'danger');
}

// Redirect back to artist profile
redirect('artist_profile.php?id=' . $artistUserId);
?> 