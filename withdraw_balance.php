<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to withdraw balance.', 'danger');
    redirect('login.php');
}

// Get current user
$artist = getCurrentUser();

// Get artist ID and balance from artist_profiles
$db = Database::getInstance();
$artistProfile = $db->selectOne("
    SELECT artist_id, balance FROM artist_profiles WHERE user_id = :user_id
", ['user_id' => $artist->getId()]);

if (!$artistProfile) {
    flashMessage('Artist profile not found.', 'danger');
    redirect('artist_dashboard.php');
}

$artistId = $artistProfile['artist_id'];
$balance = $artistProfile['balance'];
$minWithdrawAmount = 50; // Minimum withdrawal amount

// Get payment methods
$paymentMethods = $db->select("
    SELECT * FROM payment_methods 
    WHERE user_id = :user_id 
    ORDER BY is_default DESC, created_at DESC
", ['user_id' => $artist->getId()]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $paymentMethodId = isset($_POST['payment_method']) ? (int)$_POST['payment_method'] : 0;
    
    // Validate input
    $errors = [];
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    } else if ($amount < $minWithdrawAmount) {
        $errors[] = 'Minimum withdrawal amount is $' . $minWithdrawAmount;
    } else if ($amount > $balance) {
        $errors[] = 'Amount exceeds your available balance';
    }
    
    if ($paymentMethodId <= 0 && empty($_POST['add_new_method'])) {
        $errors[] = 'Please select a payment method or add a new one';
    }
    
    // Process new payment method if selected
    $newPaymentMethodId = null;
    if (isset($_POST['add_new_method']) && $_POST['add_new_method'] === '1') {
        $methodType = isset($_POST['method_type']) ? sanitizeInput($_POST['method_type']) : '';
        $accountName = isset($_POST['account_name']) ? sanitizeInput($_POST['account_name']) : '';
        $accountNumber = isset($_POST['account_number']) ? sanitizeInput($_POST['account_number']) : '';
        $routingNumber = isset($_POST['routing_number']) ? sanitizeInput($_POST['routing_number']) : '';
        $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
        $makeDefault = isset($_POST['make_default']) && $_POST['make_default'] === '1';
        
        if (empty($methodType)) {
            $errors[] = 'Payment method type is required';
        }
        
        if (empty($accountName)) {
            $errors[] = 'Account name is required';
        }
        
        if (empty($accountNumber)) {
            $errors[] = 'Account number is required';
        }
        
        if ($methodType === 'bank' && empty($routingNumber)) {
            $errors[] = 'Routing number is required for bank transfers';
        }
        
        if (empty($errors)) {
            // If making this the default, reset all other methods
            if ($makeDefault) {
                $db->query(
                    "UPDATE payment_methods SET is_default = 0 WHERE user_id = :user_id",
                    ['user_id' => $artist->getId()]
                );
            }
            
            // Insert new payment method
            $newPaymentMethodId = $db->insert('payment_methods', [
                'user_id' => $artist->getId(),
                'method_type' => $methodType,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'routing_number' => $routingNumber,
                'address' => $address,
                'is_default' => $makeDefault ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$newPaymentMethodId) {
                $errors[] = 'Failed to add payment method';
            } else {
                $paymentMethodId = $newPaymentMethodId;
            }
        }
    }
    
    if (empty($errors)) {
        // Create withdrawal record
        $withdrawalId = $db->insert('withdrawals', [
            'user_id' => $artist->getId(),
            'payment_method_id' => $paymentMethodId,
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($withdrawalId) {
            // Update artist balance
            $db->query(
                "UPDATE artist_profiles SET balance = balance - :amount WHERE artist_id = :artist_id",
                ['amount' => $amount, 'artist_id' => $artistId]
            );
            
            flashMessage('Withdrawal request submitted successfully. Your funds will be processed within 3-5 business days.', 'success');
            redirect('artist_dashboard.php');
        } else {
            flashMessage('Failed to process withdrawal request.', 'danger');
        }
    }
}

// Include header
include 'views/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $artist->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $artist->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($artist->getUserType()); ?></p>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="artist_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="artist_artworks.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-palette me-2"></i> My Artworks
                </a>
                <a href="artist_sales.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Sales
                </a>
                <a href="artist_subscribers.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> My Subscribers
                </a>
                <a href="artist_galleries.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-images me-2"></i> Virtual Galleries
                </a>
                <a href="artist_reviews.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="artist_art_fairs.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marker-alt me-2"></i> Art Fairs
                </a>
            </div>
            
            <!-- Balance card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Available Balance</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-primary"><?php echo formatPrice($balance); ?></h2>
                    <p class="text-muted small">Minimum withdrawal: <?php echo formatPrice($minWithdrawAmount); ?></p>
                    
                    <?php if ($balance < $minWithdrawAmount): ?>
                        <div class="alert alert-warning small">
                            You need at least <?php echo formatPrice($minWithdrawAmount); ?> to withdraw.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Withdraw Funds</h4>
                    <a href="artist_dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($balance < $minWithdrawAmount): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You need a minimum balance of <?php echo formatPrice($minWithdrawAmount); ?> to withdraw funds. Continue selling your artwork to increase your balance.
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Withdrawal Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="<?php echo $minWithdrawAmount; ?>" max="<?php echo $balance; ?>" class="form-control" id="amount" name="amount" value="<?php echo isset($amount) ? $amount : $balance; ?>" required>
                                    </div>
                                    <div class="form-text">Minimum: <?php echo formatPrice($minWithdrawAmount); ?>, Maximum: <?php echo formatPrice($balance); ?></div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Maximum Available</label>
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('amount').value='<?php echo $balance; ?>'">
                                            Withdraw Full Amount (<?php echo formatPrice($balance); ?>)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Payment Method <span class="text-danger">*</span></label>
                                
                                <?php if (count($paymentMethods) > 0): ?>
                                    <div class="mb-3">
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <div class="card mb-2 border <?php echo isset($paymentMethodId) && $paymentMethodId === $method['payment_method_id'] ? 'border-primary' : ''; ?>">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" value="<?php echo $method['payment_method_id']; ?>" id="method-<?php echo $method['payment_method_id']; ?>" <?php echo isset($paymentMethodId) && $paymentMethodId === $method['payment_method_id'] ? 'checked' : ''; ?> onclick="document.getElementById('newMethodSection').style.display='none';">
                                                        <label class="form-check-label w-100" for="method-<?php echo $method['payment_method_id']; ?>">
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    <strong>
                                                                        <?php 
                                                                        if ($method['method_type'] === 'paypal') {
                                                                            echo '<i class="fab fa-paypal text-primary me-2"></i> PayPal';
                                                                        } else if ($method['method_type'] === 'bank') {
                                                                            echo '<i class="fas fa-university text-primary me-2"></i> Bank Transfer';
                                                                        } else {
                                                                            echo '<i class="fas fa-credit-card text-primary me-2"></i> ' . ucfirst($method['method_type']);
                                                                        }
                                                                        ?>
                                                                    </strong>
                                                                    <span class="ms-2"><?php echo $method['account_name']; ?></span>
                                                                </div>
                                                                <?php if ($method['is_default']): ?>
                                                                    <span class="badge bg-primary">Default</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-muted small mt-1">
                                                                <?php echo hideAccountNumber($method['account_number']); ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="add_new_method" value="1" id="new-method" onclick="document.getElementById('newMethodSection').style.display='block';">
                                                    <label class="form-check-label" for="new-method">
                                                        <i class="fas fa-plus-circle text-success me-2"></i> Add a New Payment Method
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="add_new_method" value="1">
                                <?php endif; ?>
                                
                                <!-- New Payment Method Form -->
                                <div id="newMethodSection" style="display: <?php echo (count($paymentMethods) === 0 || (isset($_POST['add_new_method']) && $_POST['add_new_method'] === '1')) ? 'block' : 'none'; ?>;">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">New Payment Method</h5>
                                            
                                            <div class="mb-3">
                                                <label for="method_type" class="form-label">Payment Method Type <span class="text-danger">*</span></label>
                                                <select class="form-select" id="method_type" name="method_type" required>
                                                    <option value="">Select a method</option>
                                                    <option value="paypal" <?php echo (isset($methodType) && $methodType === 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                                                    <option value="bank" <?php echo (isset($methodType) && $methodType === 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                                                    <option value="venmo" <?php echo (isset($methodType) && $methodType === 'venmo') ? 'selected' : ''; ?>>Venmo</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="account_name" name="account_name" value="<?php echo isset($accountName) ? $accountName : ''; ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="account_number" class="form-label">Account Number/Email <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo isset($accountNumber) ? $accountNumber : ''; ?>" required>
                                                <div class="form-text">For PayPal or Venmo, enter your email address or username.</div>
                                            </div>
                                            
                                            <div class="mb-3 bank-field">
                                                <label for="routing_number" class="form-label">Routing Number</label>
                                                <input type="text" class="form-control" id="routing_number" name="routing_number" value="<?php echo isset($routingNumber) ? $routingNumber : ''; ?>">
                                                <div class="form-text">Required for bank transfers only.</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="address" class="form-label">Address (Optional)</label>
                                                <input type="text" class="form-control" id="address" name="address" value="<?php echo isset($address) ? $address : ''; ?>">
                                            </div>
                                            
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="make_default" name="make_default" value="1" <?php echo (isset($makeDefault) && $makeDefault) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="make_default">Set as default payment method</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Withdrawal requests are processed within 3-5 business days. A small processing fee may apply depending on your payment method.
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Submit Withdrawal Request</button>
                                <a href="artist_dashboard.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle display of bank-specific fields based on method type
    const methodTypeSelect = document.getElementById('method_type');
    if (methodTypeSelect) {
        methodTypeSelect.addEventListener('change', function() {
            const bankFields = document.querySelectorAll('.bank-field');
            bankFields.forEach(function(field) {
                if (methodTypeSelect.value === 'bank') {
                    field.style.display = 'block';
                    field.querySelector('input').setAttribute('required', 'required');
                } else {
                    field.style.display = 'none';
                    field.querySelector('input').removeAttribute('required');
                }
            });
        });
        
        // Trigger on load
        methodTypeSelect.dispatchEvent(new Event('change'));
    }
});

// Helper function to display partially hidden account numbers
function hideAccountNumber(number) {
    if (!number) return '';
    if (number.includes('@')) return number; // It's an email, don't hide
    
    // Show only the last 4 characters
    const length = number.length;
    const visiblePart = number.substring(length - 4);
    const hiddenPart = '*'.repeat(Math.min(length - 4, 8));
    
    return hiddenPart + visiblePart;
}
</script>

<?php include 'views/footer.php'; ?> 