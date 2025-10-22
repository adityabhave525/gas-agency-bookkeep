<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Owner') {
    die("<div style='font-family: sans-serif; padding: 20px;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p></div>");
}

$message = '';
$message_type = '';

// -- Handle Form Submission: Create a new cylinder type --
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $type_name = trim($_POST['type_name']);

    if (empty($type_name)) {
        $message = "Cylinder type name cannot be empty.";
        $message_type = "danger";
    } else {
        // Check for duplicates
        $stmt_check = $conn->prepare("SELECT cylinder_type_id FROM cylinder_types WHERE type_name = ?");
        $stmt_check->bind_param("s", $type_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "A cylinder type with this name already exists.";
            $message_type = "danger";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO cylinder_types (type_name) VALUES (?)");
            $stmt_insert->bind_param("s", $type_name);
            if ($stmt_insert->execute()) {
                $message = "New cylinder type added successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating cylinder type.";
                $message_type = "danger";
            }
        }
    }
}

// -- Fetch all cylinder types for display --
$result = $conn->query("SELECT cylinder_type_id, type_name FROM cylinder_types ORDER BY type_name");
$cylinder_types = $result->fetch_all(MYSQLI_ASSOC);

// --- Phase 2: Render Page ---
require_once '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Add New Cylinder Type Form Column -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle-fill"></i> Add New Cylinder Type</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form action="cylinder_types.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="type_name" class="form-label">New Cylinder Type Name</label>
                            <input type="text" class="form-control" id="type_name" name="type_name" placeholder="e.g., 19kg Commercial" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Cylinder Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- List of Existing Cylinder Types Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-list-ul"></i> Existing Cylinder Types</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Cylinder Type Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cylinder_types)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No cylinder types found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cylinder_types as $index => $type): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($type['type_name']); ?></strong></td>
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