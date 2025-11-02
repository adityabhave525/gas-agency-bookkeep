<?php
// --- Phase 1: Initialize ---
// All pages start with the database connection and session management.
require_once '../db.php';

// Include the delivery-specific header, which also starts the session.
require_once '../partials/delivery_header.php';

// --- Phase 2: Security Check ---
// Verify that the user is a logged-in Delivery Person.
// If not, redirect them to the login page.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Delivery Person') {
    header("Location: login.php");
    exit();
}

// --- Phase 3: Render Page ---
?>

<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <span class="btn btn-sm btn-outline-secondary pe-none">Today: <?php echo date('F j, Y'); ?></span>
            </div>
        </div>
    </div>

    <!-- Welcome Message Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>!</h5>
            <p class="card-text">This is your main dashboard. From here, you will be able to see your daily tasks, track deliveries, and view your performance. More features are coming soon!</p>
        </div>
    </div>

    <!-- Placeholder for future content -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Today's Deliveries
                </div>
                <div class="card-body text-center text-muted">
                    <i class="bi bi-truck" style="font-size: 3rem;"></i>
                    <p class="mt-2">Delivery tracking feature will be available here.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Performance Analytics
                </div>
                <div class="card-body text-center text-muted">
                    <i class="bi bi-bar-chart-line-fill" style="font-size: 3rem;"></i>
                    <p class="mt-2">Your personal performance charts will be shown here.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the standard footer to close the HTML document.
require_once '../partials/footer.php';
?>