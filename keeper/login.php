<?php
// The db.php file is one directory up, so we use ../
require_once '../db.php';

// If a godown keeper is already logged in, redirect them to their dashboard.
if (isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'Godown Keeper') {
    // We will create dashboard.php in the next step.
    header("Location: dashboard.php");
    exit();
}

$error_message = '';

// --- FORM SUBMISSION PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Prepare a secure statement to fetch user and role info
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

            // Step 1: Verify the password against the stored hash
            if (password_verify($password, $user['password'])) {

                // Step 2: CRITICAL - Verify the user's role is 'Godown Keeper'
                if ($user['role_name'] === 'Godown Keeper') {
                    // Success! Role and password match.
                    // Set session variables to establish the login state.
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_role_id'] = $user['role_id'];
                    $_SESSION['user_role_name'] = $user['role_name'];

                    // Redirect to the keeper's main page
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // The user exists, but does not have the correct role.
                    $error_message = "Access Denied. This portal is for godown keepers only.";
                }
            } else {
                // The password was incorrect.
                $error_message = "Invalid username or password.";
            }
        } else {
            // The username was not found.
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
    <title>Godown Keeper Login - Gas Agency</title>
    <!-- Bootstrap 5 CSS -->
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* This CSS centers the form perfectly on all screen sizes */
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>

<body>

    <div class="login-card p-3">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-secondary text-white text-center">
                <h3 class="mb-0"><i class="bi bi-person-badge"></i> Godown Keeper Login</h3>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- The form posts to itself (keeper/login.php) -->
                <form action="login.php" method="POST">
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
                        <button type="submit" class="btn btn-secondary btn-lg">Log In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="../js/bootstrap.bundle.js"></script>
</body>

</html>