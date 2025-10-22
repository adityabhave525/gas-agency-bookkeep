<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// Include the new manager-specific header
require_once '../partials/manager_header.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Manager') {
    // We already included the header, so we can show a nice error message before the footer.
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once '../partials/footer.php';
    exit();
}

$message = '';
$message_type = '';

// -- Handle Form Submission --
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve form data
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];

    // --- Server-Side Validation ---
    // 1. Check for empty fields
    if (empty($full_name) || empty($username) || empty($password) || empty($role_id)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // 2. CRITICAL: Verify that the submitted role_id is one the manager is allowed to create.
        // This prevents a malicious user from manipulating the form to create a manager or owner.
        $allowed_roles = [3, 4]; // Assuming 3=Godown Keeper, 4=Delivery Person
        if (!in_array($role_id, $allowed_roles)) {
            $message = "Invalid role selected. Access denied.";
            $message_type = "danger";
        } else {
            // 3. Check if username already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Username already exists. Please choose another.";
                $message_type = "danger";
            } else {
                // All checks passed, proceed with creation
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role_id) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("sssi", $full_name, $username, $hashed_password, $role_id);

                if ($insert_stmt->execute()) {
                    $message = "Account for " . htmlspecialchars($full_name) . " created successfully!";
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
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Create Staff Account</h4>
            </div>
            <div class="card-body">

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="create_account.php" method="POST">
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
                            // Fetch ONLY the roles a manager is allowed to create
                            $sql = "SELECT role_id, role_name FROM roles WHERE role_name IN ('Godown Keeper', 'Delivery Person')";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['role_id'] . "'>" . htmlspecialchars($row['role_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-dark">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include the standard footer
require_once '../partials/footer.php';
?>