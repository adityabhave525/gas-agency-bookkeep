<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel - Gas Agency</title>
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/manager/dashboard.php">Manager Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'Godown Manager'): ?>
                        <li class="nav-item">
                            <span class="navbar-text text-white me-3">
                                Welcome, <?php echo htmlspecialchars($_SESSION['user_full_name']); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/manager/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/manager/create_account.php">Create Staff Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/manager/inventory.php">Manage Inventory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/manager/vehicles.php">Manage Vehicles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger text-white px-3" href="/manager/logout.php">Logout</a>
                        </li>

                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">