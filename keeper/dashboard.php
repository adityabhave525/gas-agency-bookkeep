<?php
// --- Phase 1: Initialize ---
require_once '../db.php';
// Include the new keeper-specific header, which also starts the session.
require_once '../partials/keeper_header.php';

// --- Phase 2: Security Check ---
// Verify that the user is a logged-in Godown Keeper.
// If not, redirect them to the login page.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Keeper') {
    header("Location: login.php");
    exit();
}

// --- Phase 3: Render Page ---
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Godown Keeper Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <span class="btn btn-sm btn-outline-primary disabled">Today: <?php echo date('F j, Y'); ?></span>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>!</h5>
            <p class="card-text">This is your main dashboard. Your primary tasks, like approving delivery pickups and managing attendance, will be accessible from here.</p>
        </div>
    </div>

    <!-- Placeholders for Future Features -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Approve Delivery Pickups
                </div>
                <div class="card-body text-center text-muted">
                    <i class="bi bi-clipboard2-check-fill" style="font-size: 3rem;"></i>
                    <p class="mt-2">The list of delivery logs awaiting your approval will appear here.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Staff Attendance
                </div>
                <div class="card-body text-center text-muted">
                    <i class="bi bi-qr-code-scan" style="font-size: 3rem;"></i>
                    <p class="mt-2">The QR code scanner and attendance log will be available here.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the standard footer to close the HTML document.
require_once '../partials/footer.php';
?>