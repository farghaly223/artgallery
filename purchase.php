<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['user_type'] !== 'user') {
    flashMessage('You must be logged in as a user to purchase artwork.', 'danger');
    redirect('login.php');
}

// Get artwork ID
$artwork_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$artwork_id) {
    flashMessage('Artwork not found.', 'danger');
    redirect('browse.php');
}

// Get artwork details
$db = Database::getInstance();
$artwork = $db->selectOne("
    SELECT a.*, u.username as artist_name, ap.artist_id
    FROM artworks a
    JOIN artist_profiles ap ON a.artist_id = ap.artist_id
    JOIN users u ON ap.user_id = u.user_id
    WHERE a.artwork_id = :artwork_id AND a.is_sold = 0
", ['artwork_id' => $artwork_id]);

if (!$artwork) {
    flashMessage('Artwork not found or already sold.', 'danger');
    redirect('browse.php');
}

// Get current user
$user = getCurrentUser();

// Handle purchase submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form input
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitizeInput($_POST['city']) : '';
    $state = isset($_POST['state']) ? sanitizeInput($_POST['state']) : '';
    $zip = isset($_POST['zip']) ? sanitizeInput($_POST['zip']) : '';
    $country = isset($_POST['country']) ? sanitizeInput($_POST['country']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitizeInput($_POST['payment_method']) : '';
    
    $errors = [];
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($city)) {
        $errors[] = 'City is required';
    }
    
    if (empty($state)) {
        $errors[] = 'State/Province is required';
    }
    
    if (empty($zip)) {
        $errors[] = 'ZIP/Postal code is required';
    }
    
    if (empty($country)) {
        $errors[] = 'Country is required';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'Payment method is required';
    }
    
    // If no errors, process purchase
    if (empty($errors)) {
        // Format shipping address
        $shipping_address = $address . ', ' . $city . ', ' . $state . ' ' . $zip . ', ' . $country;
        
        // Create order
        $orderId = $db->insert('orders', [
            'user_id' => $user->getId(),
            'artwork_id' => $artwork_id,
            'price' => $artwork['price'],
            'payment_method' => $payment_method,
            'shipping_address' => $shipping_address,
            'status' => $payment_method === 'cash_on_delivery' ? 'pending' : 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($orderId) {
            // Mark artwork as sold
            $db->update('artworks', 
                ['is_sold' => 1], 
                'artwork_id = :artwork_id', 
                ['artwork_id' => $artwork_id]
            );
            
            // Only update artist's balance for non-cash payments (artist gets paid when order is completed)
            if ($payment_method !== 'cash_on_delivery') {
                $db->query("
                    UPDATE artist_profiles 
                    SET balance = balance + :price 
                    WHERE artist_id = :artist_id
                ", [
                    'price' => $artwork['price'],
                    'artist_id' => $artwork['artist_id']
                ]);
            }
            
            // Redirect to success page
            flashMessage('Artwork purchased successfully! Your order is being processed.', 'success');
            redirect('user_purchases.php');
        } else {
            flashMessage('Failed to process your purchase. Please try again.', 'danger');
        }
    }
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Purchase Artwork</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <img src="<?php echo $artwork['image_path']; ?>" alt="<?php echo $artwork['title']; ?>" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo $artwork['title']; ?></h4>
                            <h6 class="text-muted">By <?php echo $artwork['artist_name']; ?></h6>
                            <p class="h3 text-primary mt-3"><?php echo formatPrice($artwork['price']); ?></p>
                            <p><span class="badge bg-secondary"><?php echo $artwork['category']; ?></span></p>
                        </div>
                    </div>
                    
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <h5 class="mb-3">Shipping Information</h5>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo isset($address) ? $address : ''; ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($city) ? $city : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo isset($state) ? $state : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="zip" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" value="<?php echo isset($zip) ? $zip : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo isset($country) ? $country : ''; ?>" required>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Payment Method</h5>
                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                <label class="form-check-label" for="credit_card">
                                    <i class="fas fa-credit-card me-2"></i> Credit Card
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal me-2"></i> PayPal
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                <label class="form-check-label" for="bank_transfer">
                                    <i class="fas fa-university me-2"></i> Bank Transfer
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery">
                                <label class="form-check-label" for="cash_on_delivery">
                                    <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i> This is a demonstration. No actual payment will be processed.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Complete Purchase</button>
                            <a href="artwork.php?id=<?php echo $artwork_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 