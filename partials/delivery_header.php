<?php
// Start the session if not already started, as this is included on every page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Panel - Gas Agency</title>
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Delivery Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'Delivery Person'): ?>
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_full_name'])[0]); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/delivery/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/delivery/daily_log.php">Daily Log</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/delivery/expenses.php">Daily Expenses</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger text-white px-3" href="/logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">