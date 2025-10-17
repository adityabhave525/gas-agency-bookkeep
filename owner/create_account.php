<?php
// Include the header partial
require_once '../partials/header.php';

// --- ACCESS CONTROL ---
// For now, we will simulate a logged-in owner.
// In a real login system, this would be set upon successful login.
// !! IMPORTANT: Remove this line once you build the login page.
$_SESSION['user_role_id'] = 1;
$_SESSION['user_role_name'] = 'Owner';
// --------------------

// Check if the user is logged in and is an Owner.
// If not, show an error and stop the script.
if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_name'] !== 'Owner') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once '../partials/footer.php';
    exit();
}

// --- FORM SUBMISSION LOGIC ---
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve form data
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];

    // Basic Validation
    if (empty($full_name) || empty($username) || empty($password) || empty($role_id)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } else {
        // Check if username already exists using a prepared statement
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists. Please choose another.";
            $message_type = "danger";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database using a prepared statement
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
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Create New User Account</h4>
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
                            // Fetch roles from the database to populate the dropdown
                            // The owner should not be able to create another owner
                            $result = $conn->query("SELECT role_id, role_name FROM roles WHERE role_name != 'Owner'");
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
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
</div>

<?php
// Include the footer partial
require_once '../partials/footer.php';
?>