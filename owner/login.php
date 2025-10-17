<?php
// The db.php file is one directory up, so we use ../
require_once '../db.php';

// If an owner is already logged in, redirect them to a dashboard page
// This prevents them from seeing the login page again.
if (isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'Owner') {
    // Redirect to the create_account page or a future dashboard page
    header("Location: create_account.php");
    exit();
}

$error_message = '';

// --- FORM SUBMISSION PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Prepare a statement to securely fetch user and role info
        $stmt = $conn->prepare(
            "SELECT u.user_id, u.full_name, u.password, r.role_id, r.role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.role_id 
             WHERE u.username = ?"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Step 1: Verify the password
            if (password_verify($password, $user['password'])) {

                // Step 2: CRITICAL - Verify the user's role is 'Owner'
                if ($user['role_name'] === 'Owner') {
                    // Success! Password and role are correct.
                    // Set session variables to establish the login state.
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_role_id'] = $user['role_id'];
                    $_SESSION['user_role_name'] = $user['role_name'];

                    // Redirect to the owner's main page
                    header("Location: create_account.php");
                    exit();
                } else {
                    // Password was correct, but the user is not an owner. Deny access.
                    $error_message = "Access Denied. This login is for owners only.";
                }
            } else {
                // Password was incorrect. Show a generic error.
                $error_message = "Invalid username or password.";
            }
        } else {
            // Username was not found. Show a generic error.
            $error_message = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login - Gas Agency</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* This CSS ensures the form is vertically and horizontally centered on the page */
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f2f5;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            /* Optimal width for login forms */
        }
    </style>
</head>

<body>

    <div class="login-card p-3">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white text-center">
                <h3 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Owner Portal Login</h3>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- The form action is empty, so it posts to itself (owner/login.php) -->
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-dark btn-lg">Log In</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                &copy; <?php echo date("Y"); ?> Gas Agency Management
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.js"></script>
</body>

</html>