<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to update order status.', 'danger');
    redirect('login.php');
}

// Get order ID and status
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

if (!$order_id) {
    flashMessage('Order not found.', 'danger');
    redirect('artist_sales.php');
}

if (!in_array($status, ['pending', 'completed', 'cancelled'])) {
    flashMessage('Invalid status.', 'danger');
    redirect('artist_sales.php');
}

// Get artist ID from artist_profiles
$db = Database::getInstance();
$artistProfile = $db->selectOne("
    SELECT artist_id FROM artist_profiles WHERE user_id = :user_id
", ['user_id' => $_SESSION['user_id']]);

if (!$artistProfile) {
    flashMessage('Artist profile not found.', 'danger');
    redirect('artist_dashboard.php');
}

$artistId = $artistProfile['artist_id'];

// Check if the order belongs to the artist
$order = $db->selectOne("
    SELECT o.*, a.title, a.artist_id
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.artwork_id
    WHERE o.order_id = :order_id AND a.artist_id = :artist_id
", [
    'order_id' => $order_id,
    'artist_id' => $artistId
]);

if (!$order) {
    flashMessage('Order not found or you do not have permission to update it.', 'danger');
    redirect('artist_sales.php');
}

// Update order status
$result = $db->update('orders', 
    ['status' => $status], 
    'order_id = :order_id', 
    ['order_id' => $order_id]
);

if ($result) {
    // If the status is being set to completed, make sure the artwork is marked as sold
    if ($status === 'completed') {
        $db->update('artworks', 
            ['is_sold' => 1], 
            'artwork_id = :artwork_id', 
            ['artwork_id' => $order['artwork_id']]
        );
        
        // For cash on delivery orders, add payment to artist balance when completed
        if ($order['payment_method'] === 'cash_on_delivery' && $order['status'] !== 'completed') {
            $db->query("
                UPDATE artist_profiles 
                SET balance = balance + :price 
                WHERE artist_id = :artist_id
            ", [
                'price' => $order['price'],
                'artist_id' => $artistId
            ]);
        }
    }
    
    // If the status is being changed from completed to something else, we need to handle the commission
    if ($order['status'] === 'completed' && $status !== 'completed') {
        // If the order was previously completed, subtract the amount from the artist's balance
        $db->query("
            UPDATE artist_profiles 
            SET balance = balance - :price 
            WHERE artist_id = :artist_id
        ", [
            'price' => $order['price'],
            'artist_id' => $artistId
        ]);
    } 
    // If the status is being changed to completed from something else (except for cash on delivery which is handled above)
    else if ($order['status'] !== 'completed' && $status === 'completed' && $order['payment_method'] !== 'cash_on_delivery') {
        // Add the amount to the artist's balance
        $db->query("
            UPDATE artist_profiles 
            SET balance = balance + :price 
            WHERE artist_id = :artist_id
        ", [
            'price' => $order['price'],
            'artist_id' => $artistId
        ]);
    }
    
    flashMessage('Order status updated to ' . ucfirst($status) . '.', 'success');
} else {
    flashMessage('Failed to update order status.', 'danger');
}

// Redirect back to order details
redirect('order_details.php?id=' . $order_id);
?> 