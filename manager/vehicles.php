<?php
// --- Phase 1: Initialize and Process ---
// All logic runs BEFORE any HTML is outputted to prevent header errors.
require_once '../db.php';

// -- Handle Form Submissions (CREATE OR UPDATE) --
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ACTION: Create a new vehicle
    if ($action === 'create') {
        $vehicle_number = strtoupper(trim($_POST['vehicle_number']));
        $is_active = $_POST['is_active'];

        if (empty($vehicle_number)) {
            $message = "Vehicle number cannot be empty.";
            $message_type = "danger";
        } else {
            // Check for duplicates
            $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_number = ?");
            $stmt->bind_param("s", $vehicle_number);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "A vehicle with this number already exists.";
                $message_type = "danger";
            } else {
                // Insert new vehicle
                $insert_stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, is_active) VALUES (?, ?)");
                $insert_stmt->bind_param("si", $vehicle_number, $is_active);
                if ($insert_stmt->execute()) {
                    $message = "Vehicle added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding vehicle.";
                    $message_type = "danger";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        }
    }

    // ACTION: Update the status of an existing vehicle
    if ($action === 'update_status') {
        $vehicle_id = $_POST['vehicle_id'];
        $new_status = $_POST['new_status'];

        $update_stmt = $conn->prepare("UPDATE vehicles SET is_active = ? WHERE vehicle_id = ?");
        $update_stmt->bind_param("ii", $new_status, $vehicle_id);
        if ($update_stmt->execute()) {
            // Redirect to prevent form resubmission. This is the correct place for it.
            header("Location: vehicles.php?status=updated");
            exit();
        } else {
            $message = "Error updating status.";
            $message_type = "danger";
        }
        $update_stmt->close();
    }
}

// -- Security: Access Control --
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Manager') {
    die("<div style='font-family: sans-serif; padding: 20px;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p></div>");
}

// Check for status messages from redirect
if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = "Vehicle status updated successfully.";
    $message_type = "success";
}

// -- Fetch Vehicles for Display (with Filtering) --
$search_number = $_GET['search_number'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$sql = "SELECT vehicle_id, vehicle_number, is_active FROM vehicles";
$params = [];
$param_types = '';
$where_clauses = [];

if (!empty($search_number)) {
    $where_clauses[] = "vehicle_number LIKE ?";
    $params[] = "%" . $search_number . "%";
    $param_types .= 's';
}
if ($filter_status !== '' && in_array($filter_status, ['0', '1'])) {
    $where_clauses[] = "is_active = ?";
    $params[] = $filter_status;
    $param_types .= 'i';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY vehicle_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Phase 2: Render Page ---
// Now we can safely include the header and start outputting HTML.
require_once '../partials/manager_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Add Vehicle Form Column -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle-fill"></i> Add New Vehicle</h4>
                </div>
                <div class="card-body">
                    <?php if ($message && (!isset($_GET['status']) || $_GET['status'] != 'updated')): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form action="vehicles.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control text-uppercase" id="vehicle_number" name="vehicle_number" placeholder="e.g., MH12AB3456" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="is_active" id="active" value="1" checked><label class="form-check-label" for="active">Active</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="is_active" id="inactive" value="0"><label class="form-check-label" for="inactive">Inactive</label></div>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-dark">Add Vehicle</button></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manage Vehicles List Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="bi bi-truck"></i> Manage Vehicles</h4>
                </div>
                <div class="card-body">
                    <form action="vehicles.php" method="GET" class="mb-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5"><label for="search_number" class="form-label">Search by Number</label><input type="text" class="form-control" id="search_number" name="search_number" value="<?php echo htmlspecialchars($search_number); ?>"></div>
                            <div class="col-md-4"><label for="filter_status" class="form-label">Filter by Status</label><select class="form-select" id="filter_status" name="filter_status">
                                    <option value="">All</option>
                                    <option value="1" <?php echo ($filter_status === '1') ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo ($filter_status === '0') ? 'selected' : ''; ?>>Inactive</option>
                                </select></div>
                            <div class="col-md-3 d-grid gap-1"><button type="submit" class="btn btn-info">Filter</button><a href="vehicles.php" class="btn btn-secondary">Reset</a></div>
                        </div>
                    </form>

                    <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vehicles)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No vehicles found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></strong></td>
                                            <td><span class="badge bg-<?php echo $vehicle['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $vehicle['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                            <td class="text-center">
                                                <form action="vehicles.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['vehicle_id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $vehicle['is_active'] ? '0' : '1'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $vehicle['is_active'] ? 'warning' : 'success'; ?>"><?php echo $vehicle['is_active'] ? 'Set Inactive' : 'Set Active'; ?></button>
                                                </form>
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