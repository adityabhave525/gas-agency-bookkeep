<?php
// This will be our new manager-specific header
require_once '../partials/manager_header.php';

// Security check: Only managers can access this page
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Manager') {
    // Redirect to the manager login page if not authorized
    header("Location: login.php");
    exit();
}
?>
<div class="container-fluid">
    <h1 class="h2 mb-4">Manager Dashboard</h1>
    <div class="alert alert-info">
        Welcome, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>. More widgets and insights will be added here soon.
    </div>
    <!-- We can add KPI cards and charts here later -->
</div>
<?php
require_once '../partials/footer.php';
?>