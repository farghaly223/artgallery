<?php
require_once 'config/config.php';
require_once 'includes/autoloader.php';
require_once 'includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isUserType('admin')) {
    flashMessage('You must be logged in as an admin to view this page.', 'danger');
    redirect('login.php');
}

// Get current user
$admin = getCurrentUser();

// Get filter status if set
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;
if (!in_array($filterStatus, ['pending', 'resolved', 'dismissed', null])) {
    $filterStatus = null;
}

// Get all reports
$reports = $admin->getReports($filterStatus);

// Handle resolve report action
if (isset($_GET['resolve_report']) && isset($_GET['action'])) {
    $reportId = (int)$_GET['resolve_report'];
    $action = $_GET['action'];
    
    if ($action == 'ban' || $action == 'dismiss') {
        $result = $admin->resolveReport($reportId, $action);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    redirect('admin_reports.php' . ($filterStatus ? "?status=$filterStatus" : ''));
}

// Include header
include 'views/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?php echo $admin->getProfilePicture(); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-0"><?php echo $admin->getUsername(); ?></h5>
                    <p class="text-muted mb-3"><?php echo getUserTypeName($admin->getUserType()); ?></p>
                </div>
            </div>
            
            <div class="list-group mb-4 shadow-sm">
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_users.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage Users
                </a>
                <a href="admin_artworks.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-palette me-2"></i> Manage Artworks
                </a>
                <a href="admin_reports.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-flag me-2"></i> Reports
                </a>
                <a href="admin_banned.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ban me-2"></i> Banned Users
                </a>
        
                <a href="admin_register.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-plus me-2"></i> Create Admin
                </a>
          
                <a href="admin_settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Reports Management</h3>
                    <div>
                        <div class="btn-group" role="group">
                            <a href="admin_reports.php" class="btn btn-outline-primary <?php echo $filterStatus === null ? 'active' : ''; ?>">All</a>
                            <a href="admin_reports.php?status=pending" class="btn btn-outline-primary <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">Pending</a>
                            <a href="admin_reports.php?status=resolved" class="btn btn-outline-primary <?php echo $filterStatus === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                            <a href="admin_reports.php?status=dismissed" class="btn btn-outline-primary <?php echo $filterStatus === 'dismissed' ? 'active' : ''; ?>">Dismissed</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-flag fa-4x text-muted mb-3"></i>
                            <h5>No Reports Found</h5>
                            <p class="text-muted">
                                <?php if ($filterStatus): ?>
                                    No <?php echo $filterStatus; ?> reports found.
                                <?php else: ?>
                                    There are no reports in the system.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Reporter</th>
                                        <th>Reported User</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo $report['report_id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $report['reporter_name']; ?></span>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($report['reporter_type']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $report['reported_name']; ?></span>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($report['reported_type']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo substr($report['reason'], 0, 100) . (strlen($report['reason']) > 100 ? '...' : ''); ?></td>
                                            <td><?php echo formatDate($report['created_at']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $report['status'] == 'pending' ? 'warning' : 
                                                        ($report['status'] == 'resolved' ? 'success' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($report['status'] == 'pending'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewReportModal" 
                                                           data-report-id="<?php echo $report['report_id']; ?>"
                                                           data-reporter="<?php echo $report['reporter_name']; ?>"
                                                           data-reported="<?php echo $report['reported_name']; ?>"
                                                           data-reason="<?php echo htmlspecialchars($report['reason']); ?>"
                                                           data-date="<?php echo formatDate($report['created_at']); ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="?resolve_report=<?php echo $report['report_id']; ?>&action=ban<?php echo $filterStatus ? "&status=$filterStatus" : ''; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to ban <?php echo $report['reported_name']; ?>?')">
                                                            Ban User
                                                        </a>
                                                        <a href="?resolve_report=<?php echo $report['report_id']; ?>&action=dismiss<?php echo $filterStatus ? "&status=$filterStatus" : ''; ?>" class="btn btn-outline-secondary" onclick="return confirm('Are you sure you want to dismiss this report?')">
                                                            Dismiss
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewReportModal" 
                                                       data-report-id="<?php echo $report['report_id']; ?>"
                                                       data-reporter="<?php echo $report['reporter_name']; ?>"
                                                       data-reported="<?php echo $report['reported_name']; ?>"
                                                       data-reason="<?php echo htmlspecialchars($report['reason']); ?>"
                                                       data-date="<?php echo formatDate($report['created_at']); ?>">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Report Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewReportModalLabel">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-3">Report ID:</dt>
                    <dd class="col-sm-9" id="reportId"></dd>
                    
                    <dt class="col-sm-3">Reporter:</dt>
                    <dd class="col-sm-9" id="reporterName"></dd>
                    
                    <dt class="col-sm-3">Reported User:</dt>
                    <dd class="col-sm-9" id="reportedName"></dd>
                    
                    <dt class="col-sm-3">Date Reported:</dt>
                    <dd class="col-sm-9" id="reportDate"></dd>
                    
                    <dt class="col-sm-3">Reason:</dt>
                    <dd class="col-sm-9">
                        <div class="p-3 bg-light rounded" id="reportReason"></div>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for report details modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewReportModal = document.getElementById('viewReportModal');
    if (viewReportModal) {
        viewReportModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // Extract info from data-* attributes
            const reportId = button.getAttribute('data-report-id');
            const reporter = button.getAttribute('data-reporter');
            const reported = button.getAttribute('data-reported');
            const reason = button.getAttribute('data-reason');
            const date = button.getAttribute('data-date');
            
            // Update the modal's content
            document.getElementById('reportId').textContent = reportId;
            document.getElementById('reporterName').textContent = reporter;
            document.getElementById('reportedName').textContent = reported;
            document.getElementById('reportDate').textContent = date;
            document.getElementById('reportReason').textContent = reason;
        });
    }
});
</script>

<?php
// Include footer
include 'views/footer.php';
?> 