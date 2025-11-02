<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// -- Security: Access Control --
// This must run before any HTML is outputted.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Owner') {
    // Redirect to login if not an authenticated owner
    header("Location: login.php");
    exit();
}

// -- Handle Form Submission: Create a new user --
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];

    if (empty($full_name) || empty($username) || empty($password) || empty($role_id)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // Check for duplicate username
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists. Please choose another.";
            $message_type = "danger";
        } else {
            // All good, create the user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role_id) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("sssi", $full_name, $username, $hashed_password, $role_id);

            if ($insert_stmt->execute()) {
                $message = "Account created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating account: " . $conn->error;
                $message_type = "danger";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// -- Fetch Data for Display --
// 1. Fetch all existing users EXCEPT the currently logged-in owner
$current_user_id = $_SESSION['user_id'];
$sql_users = "SELECT u.full_name, u.username, u.created_at, r.role_name 
              FROM users u
              JOIN roles r ON u.role_id = r.role_id
              WHERE u.user_id != ?
              ORDER BY u.created_at DESC";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("i", $current_user_id);
$stmt_users->execute();
$users_list = $stmt_users->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch roles for the creation form dropdown
$sql_roles = "SELECT role_id, role_name FROM roles WHERE role_name != 'Owner' ORDER BY role_name";
$roles_result = $conn->query($sql_roles);


// --- Phase 2: Render Page ---
require_once '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Create Account Form Column -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Create New User Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form action="create_account.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="" disabled selected>Select a role...</option>
                                <?php
                                if ($roles_result->num_rows > 0) {
                                    while ($row = $roles_result->fetch_assoc()) {
                                        echo "<option value='" . $row['role_id'] . "'>" . htmlspecialchars($row['role_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Accounts List Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-people-fill"></i> Existing Staff Accounts</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Date Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users_list)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No other user accounts found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users_list as $user): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // Format the timestamp for better readability
                                                $date = new DateTime($user['created_at']);
                                                echo $date->format('M j, Y, g:i A');
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../partials/footer.php';
?>