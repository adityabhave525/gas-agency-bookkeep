<?php
// We must include the database connection, which also starts the session
require_once 'db.php';

// If the user is already logged in as an owner, redirect them away from the login page
if (isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'Owner') {
    header("Location: owner/create_account.php");
    exit();
}

$error_message = '';

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Prepare a statement to prevent SQL injection
        // We join with the roles table to get the role name directly
        $stmt = $conn->prepare("SELECT u.user_id, u.full_name, u.password, r.role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the password against the stored hash
            if (password_verify($password, $user['password'])) {
                // Check if the user's role is 'Owner'
                if ($user['role_name'] === 'Owner') {
                    // Password and role are correct, set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_role_id'] = $user['role_id'];
                    $_SESSION['user_role_name'] = $user['role_name'];

                    // Redirect to the owner's dashboard/main page
                    header("Location: owner/create_account.php");
                    exit();
                } else {
                    // User is valid, but not an owner
                    $error_message = "Access Denied. Only owners can log in from this page.";
                }
            } else {
                // Password is incorrect
                $error_message = "Invalid username or password.";
            }
        } else {
            // Username not found
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
    <link rel="stylesheet" href="./css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0"><i class="bi bi-person-circle"></i> Owner Login</h3>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="./js/bootstrap.bundle.js"></script>
</body>

</html>