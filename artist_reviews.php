<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isUserType('artist')) {
    flashMessage('You must be logged in as an artist to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$artist = getCurrentUser();

// Get artist ID from artist_profiles
$db = Database::getInstance();
$artistProfile = $db->selectOne("
    SELECT artist_id FROM artist_profiles WHERE user_id = :user_id
", ['user_id' => $artist->getId()]);

if (!$artistProfile) {
    flashMessage('Artist profile not found.', 'danger');
    redirect('artist_dashboard.php');
}

$artistId = $artistProfile['artist_id'];

// Get reviews
$reviews = $db->select("
    SELECT r.*, u.username, u.profile_picture
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.artist_id = :artist_id
    ORDER BY r.created_at DESC
", ['artist_id' => $artistId]);

// Calculate average rating
$totalRating = 0;
$fiveStarCount = 0;
$fourStarCount = 0;
$threeStarCount = 0;
$twoStarCount = 0;
$oneStarCount = 0;

if (count($reviews) > 0) {
    foreach ($reviews as $review) {
        $totalRating += $review['rating'];
        
        switch ($review['rating']) {
            case 5:
                $fiveStarCount++;
                break;
            case 4:
                $fourStarCount++;
                break;
            case 3:
                $threeStarCount++;
                break;
            case 2:
                $twoStarCount++;
                break;
            case 1:
                $oneStarCount++;
                break;
        }
    }
    
    $averageRating = $totalRating / count($reviews);
} else {
    $averageRating = 0;
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
                <a href="artist_reviews.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="artist_art_fairs.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marker-alt me-2"></i> Art Fairs
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Rating Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 border-end text-center">
                            <h1 class="display-4 fw-bold text-primary"><?php echo number_format($averageRating, 1); ?></h1>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($averageRating)): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted"><?php echo count($reviews); ?> reviews</p>
                        </div>
                        <div class="col-md-8">
                            <div class="rating-bars">
                                <div class="row align-items-center mb-1">
                                    <div class="col-2">5 stars</div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <?php $fiveStarPercentage = count($reviews) > 0 ? ($fiveStarCount / count($reviews) * 100) : 0; ?>
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $fiveStarPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end"><?php echo $fiveStarCount; ?></div>
                                </div>
                                <div class="row align-items-center mb-1">
                                    <div class="col-2">4 stars</div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <?php $fourStarPercentage = count($reviews) > 0 ? ($fourStarCount / count($reviews) * 100) : 0; ?>
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $fourStarPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end"><?php echo $fourStarCount; ?></div>
                                </div>
                                <div class="row align-items-center mb-1">
                                    <div class="col-2">3 stars</div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <?php $threeStarPercentage = count($reviews) > 0 ? ($threeStarCount / count($reviews) * 100) : 0; ?>
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $threeStarPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end"><?php echo $threeStarCount; ?></div>
                                </div>
                                <div class="row align-items-center mb-1">
                                    <div class="col-2">2 stars</div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <?php $twoStarPercentage = count($reviews) > 0 ? ($twoStarCount / count($reviews) * 100) : 0; ?>
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $twoStarPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end"><?php echo $twoStarCount; ?></div>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-2">1 star</div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <?php $oneStarPercentage = count($reviews) > 0 ? ($oneStarCount / count($reviews) * 100) : 0; ?>
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $oneStarPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end"><?php echo $oneStarCount; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reviews List -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Customer Reviews</h4>
                </div>
                <div class="card-body">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review mb-4 pb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $review['profile_picture']; ?>" alt="<?php echo $review['username']; ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?php echo $review['username']; ?></h6>
                                            <div class="small text-muted"><?php echo formatDate($review['created_at']); ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="review-content"><?php echo $review['comment']; ?></p>
                                <div class="d-flex justify-content-end">
                                    <a href="#" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#replyModal" data-review-id="<?php echo $review['review_id']; ?>" data-username="<?php echo $review['username']; ?>">
                                        <i class="fas fa-reply me-1"></i> Reply
                                    </a>
                                    <?php if ($review['rating'] <= 3): ?>
                                        <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reportModal" data-review-id="<?php echo $review['review_id']; ?>">
                                            <i class="fas fa-flag me-1"></i> Report
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-4x text-muted mb-3"></i>
                            <h5>No Reviews Yet</h5>
                            <p class="text-muted">You don't have any reviews yet. As you sell more artwork, customers will leave reviews.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel">Reply to Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="artist_reply_review.php">
                <div class="modal-body">
                    <input type="hidden" name="review_id" id="replyReviewId">
                    <div class="mb-3">
                        <label for="replyMessage" class="form-label">Your Reply to <span id="replyUsername"></span></label>
                        <textarea class="form-control" id="replyMessage" name="reply" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Report Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="artist_report_review.php">
                <div class="modal-body">
                    <input type="hidden" name="review_id" id="reportReviewId">
                    <div class="mb-3">
                        <label for="reportReason" class="form-label">Reason for Reporting</label>
                        <select class="form-select" id="reportReason" name="reason" required>
                            <option value="">Select a reason</option>
                            <option value="inappropriate">Inappropriate Content</option>
                            <option value="spam">Spam</option>
                            <option value="irrelevant">Irrelevant Review</option>
                            <option value="fake">Fake Review</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reportDetails" class="form-label">Additional Details</label>
                        <textarea class="form-control" id="reportDetails" name="details" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Report Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up reply modal
    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reviewId = button.getAttribute('data-review-id');
            const username = button.getAttribute('data-username');
            
            document.getElementById('replyReviewId').value = reviewId;
            document.getElementById('replyUsername').textContent = username;
        });
    }
    
    // Set up report modal
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reviewId = button.getAttribute('data-review-id');
            
            document.getElementById('reportReviewId').value = reviewId;
        });
    }
});
</script>

<?php include 'views/footer.php'; ?> 