<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Get search/filter parameters
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$upcoming = isset($_GET['upcoming']) && $_GET['upcoming'] == '1';

// Get all art fairs
$db = Database::getInstance();
$sql = "
    SELECT af.*, u.username
    FROM art_fairs af
    LEFT JOIN artist_profiles ap ON af.artist_id = ap.artist_id
    LEFT JOIN users u ON ap.user_id = u.user_id
";

$whereClauses = [];
$params = [];

if (!empty($location)) {
    $whereClauses[] = "af.location LIKE :location";
    $params['location'] = '%' . $location . '%';
}

if ($upcoming) {
    $whereClauses[] = "af.start_date >= CURDATE()";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY af.start_date";
$artFairs = $db->select($sql, $params);

include 'views/header.php';
?>

<div class="container">
    <h1 class="my-4">Art Fairs & Exhibitions</h1>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="art_fairs.php" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="City, country..." value="<?php echo $location; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="upcoming" name="upcoming" value="1" <?php echo $upcoming ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="upcoming">Upcoming events only</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Art Fairs List -->
    <div class="row">
        <?php if (count($artFairs) > 0): ?>
            <?php foreach ($artFairs as $fair): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo $fair['name']; ?></h4>
                            <div class="badge bg-primary mb-2"><?php echo $fair['location']; ?></div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <i class="fas fa-calendar-alt me-1"></i> 
                                    <?php echo formatDate($fair['start_date'], 'd M Y'); ?> - 
                                    <?php echo formatDate($fair['end_date'], 'd M Y'); ?>
                                </div>
                                <?php if (!empty($fair['username'])): ?>
                                    <div>
                                        <i class="fas fa-user me-1"></i> Organized by: <?php echo $fair['username']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text">
                                <?php 
                                $description = !empty($fair['description']) ? $fair['description'] : 'No description available.';
                                echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description;
                                ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <a href="art_fair_details.php?id=<?php echo $fair['fair_id']; ?>" class="btn btn-outline-primary">View Details</a>
                            <?php if (isLoggedIn()): ?>
                                <a href="register_for_fair.php?id=<?php echo $fair['fair_id']; ?>" class="btn btn-outline-success">Register to Attend</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No art fairs found matching your criteria.</p>
                <a href="art_fairs.php" class="btn btn-outline-primary">Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isLoggedIn() && $_SESSION['user_type'] == 'artist'): ?>
        <div class="mt-4 text-center">
            <a href="register_art_fair.php" class="btn btn-success btn-lg">Register Your Art Fair</a>
        </div>
    <?php endif; ?>
</div>

<?php
include 'views/footer.php';
?> 