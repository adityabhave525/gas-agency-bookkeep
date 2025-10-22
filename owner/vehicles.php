<?php
// Include the header which contains the session start, DB connection, and navigation.
require_once '../db.php';


// --- BACKEND LOGIC ---
$message = '';
$message_type = '';

// -- HANDLE FORM SUBMISSIONS (CREATE OR UPDATE) --
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check which action is being performed
    $action = $_POST['action'] ?? '';

    // ACTION: Create a new vehicle
    if ($action === 'create') {
        $vehicle_number = strtoupper(trim($_POST['vehicle_number']));
        $is_active = $_POST['is_active'];

        if (empty($vehicle_number)) {
            $message = "Vehicle number cannot be empty.";
            $message_type = "danger";
        } else {
            // Check if vehicle number already exists
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
                    $message = "Error adding vehicle: " . $conn->error;
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
            // Use a redirect to prevent form resubmission on refresh
            header("Location: vehicles.php?status=updated");
            exit();
        } else {
            $message = "Error updating status: " . $conn->error;
            $message_type = "danger";
        }
        $update_stmt->close();
    }
}


// --- ACCESS CONTROL ---
// Ensure the user is logged in and is an Owner.
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Owner') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once '../partials/footer.php';
    exit();
}

// Check for status messages from redirect
if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $message = "Vehicle status updated successfully.";
    $message_type = "success";
}


// -- FETCH VEHICLES FOR DISPLAY (WITH FILTERING) --
$search_number = $_GET['search_number'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$sql = "SELECT vehicle_id, vehicle_number, is_active FROM vehicles";
$where_clauses = [];
$params = [];
$param_types = '';

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

$sql .= " ORDER BY created_at DESC"; // Assuming you add a created_at timestamp column later

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vehicles = $result->fetch_all(MYSQLI_ASSOC);

require_once '../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Add Vehicle Form Column -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle-fill"></i> Add New Vehicle</h4>
                </div>
                <div class="card-body">
                    <?php if ($message && $_SERVER['REQUEST_METHOD'] != 'POST' || (isset($_POST['action']) && $_POST['action'] == 'create')): ?>
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
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="active" value="1" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="inactive" value="0">
                                <label class="form-check-label" for="inactive">Inactive</label>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manage Vehicles List Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-truck"></i> Manage Vehicles</h4>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form action="vehicles.php" method="GET" class="mb-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label for="search_number" class="form-label">Search by Number</label>
                                <input type="text" class="form-control" id="search_number" name="search_number" placeholder="Enter number..." value="<?php echo htmlspecialchars($search_number); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="filter_status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filter_status" name="filter_status">
                                    <option value="">All</option>
                                    <option value="1" <?php echo ($filter_status === '1') ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo ($filter_status === '0') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-info w-100">Filter</button>
                                <a href="vehicles.php" class="btn btn-secondary w-100 mt-1">Reset</a>
                            </div>
                        </div>
                    </form>

                    <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <!-- Vehicle List Table -->
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
                                            <td>
                                                <?php if ($vehicle['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <form action="vehicles.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['vehicle_id']; ?>">
                                                    <?php if ($vehicle['is_active']): ?>
                                                        <input type="hidden" name="new_status" value="0">
                                                        <button type="submit" class="btn btn-warning btn-sm">Set to Inactive</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="new_status" value="1">
                                                        <button type="submit" class="btn btn-success btn-sm">Set to Active</button>
                                                    <?php endif; ?>
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
// Include the footer which contains the closing HTML tags and JS scripts.
require_once '../partials/footer.php';
?>