<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// Include the manager-specific header
require_once '../partials/manager_header.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Manager') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    require_once '../partials/footer.php';
    exit();
}

$message = '';
$message_type = '';
$today = date('Y-m-d');

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // -- ACTION 1: Add an existing cylinder type to today's inventory sheet --
    if ($action === 'add_to_sheet') {
        $cylinder_type_id = $_POST['cylinder_type_id'] ?? null;
        if (!empty($cylinder_type_id)) {
            // Use INSERT IGNORE to prevent errors if the record somehow already exists
            $stmt = $conn->prepare("INSERT IGNORE INTO inventory (cylinder_type_id, inventory_date, opening_stock_full, opening_stock_empty, closing_stock_full, closing_stock_empty) VALUES (?, ?, 0, 0, 0, 0)");
            $stmt->bind_param("is", $cylinder_type_id, $today);
            if ($stmt->execute()) {
                header("Location: inventory.php?status=added");
                exit();
            }
        }
    }

    // -- ACTION 2: Update the stock for all cylinders on the sheet --
    if ($action === 'update_stock') {
        $inventory_data = $_POST['inventory'] ?? [];
        $conn->begin_transaction();
        try {
            foreach ($inventory_data as $cylinder_type_id => $counts) {
                $full_stock = (int)($counts['full'] ?? 0);
                $empty_stock = (int)($counts['empty'] ?? 0);
                $stmt = $conn->prepare("UPDATE inventory SET closing_stock_full = ?, closing_stock_empty = ? WHERE cylinder_type_id = ? AND inventory_date = ?");
                $stmt->bind_param("iiss", $full_stock, $empty_stock, $cylinder_type_id, $today);
                $stmt->execute();
            }
            $conn->commit();
            $message = "Inventory stock updated successfully!";
            $message_type = "success";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "Error updating stock. Operation rolled back.";
            $message_type = "danger";
        }
    }
}

// Check for redirect status message
if (isset($_GET['status']) && $_GET['status'] == 'added') {
    $message = "Cylinder added to today's sheet. You can now set its stock value.";
    $message_type = "info";
}

// --- Fetch Data for Display ---
// 1. Get inventory records for today's sheet
$sql_today = "SELECT ct.cylinder_type_id, ct.type_name, inv.closing_stock_full, inv.closing_stock_empty 
              FROM inventory inv
              JOIN cylinder_types ct ON inv.cylinder_type_id = ct.cylinder_type_id
              WHERE inv.inventory_date = ? ORDER BY ct.type_name";
$stmt_today = $conn->prepare($sql_today);
$stmt_today->bind_param("s", $today);
$stmt_today->execute();
$inventory_list = $stmt_today->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Get cylinder types that are NOT on today's sheet (for the dropdown)
$ids_on_sheet = !empty($inventory_list) ? array_column($inventory_list, 'cylinder_type_id') : [0];
$placeholders = implode(',', array_fill(0, count($ids_on_sheet), '?'));
$sql_dropdown = "SELECT cylinder_type_id, type_name FROM cylinder_types WHERE cylinder_type_id NOT IN ($placeholders) ORDER BY type_name";
$stmt_dropdown = $conn->prepare($sql_dropdown);
$stmt_dropdown->bind_param(str_repeat('i', count($ids_on_sheet)), ...$ids_on_sheet);
$stmt_dropdown->execute();
$cylinders_to_add = $stmt_dropdown->get_result()->fetch_all(MYSQLI_ASSOC);

$total_full = array_sum(array_column($inventory_list, 'closing_stock_full'));
$total_empty = array_sum(array_column($inventory_list, 'closing_stock_empty'));

?>

<div class="container-fluid">
    <!-- Section to add existing cylinders to today's sheet -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-journal-plus"></i> Add Cylinder to Today's Sheet</h5>
        </div>
        <div class="card-body">
            <form action="inventory.php" method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add_to_sheet">
                <div class="col-md-8">
                    <label for="cylinder_type_id" class="form-label">Select from Master List</label>
                    <select class="form-select" name="cylinder_type_id" id="cylinder_type_id" required>
                        <option value="" disabled selected>Choose a cylinder type...</option>
                        <?php foreach ($cylinders_to_add as $cyl): ?>
                            <option value="<?php echo $cyl['cylinder_type_id']; ?>"><?php echo htmlspecialchars($cyl['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-info w-100" <?php echo empty($cylinders_to_add) ? 'disabled' : ''; ?>>
                        <?php echo empty($cylinders_to_add) ? 'All Types on Sheet' : 'Add to Sheet'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section to manage daily stock -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-box-seam-fill"></i> Daily Stock for <?php echo date('F j, Y'); ?></h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="update_stock">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Cylinder Type</th>
                                <th>Full Stock (F)</th>
                                <th>Empty Stock (M)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory_list)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted p-4">Today's inventory sheet is empty. Add a cylinder type above to begin.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory_list as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['type_name']); ?></strong></td>
                                        <td><input type="number" class="form-control text-center" name="inventory[<?php echo $item['cylinder_type_id']; ?>][full]" value="<?php echo $item['closing_stock_full'] ?? 0; ?>" min="0" required></td>
                                        <td><input type="number" class="form-control text-center" name="inventory[<?php echo $item['cylinder_type_id']; ?>][empty]" value="<?php echo $item['closing_stock_empty'] ?? 0; ?>" min="0" required></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-group-divider fw-bold text-center">
                            <tr>
                                <td>Totals</td>
                                <td id="totalFull"><?php echo $total_full; ?></td>
                                <td id="totalEmpty"><?php echo $total_empty; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary btn-lg" <?php echo empty($inventory_list) ? 'disabled' : ''; ?>><i class="bi bi-save-fill"></i> Save Stock Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // This JavaScript for live totals is identical to the owner's page and works perfectly here.
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[name^="inventory"]');
        if (inputs.length > 0) {
            const totalFullCell = document.getElementById('totalFull');
            const totalEmptyCell = document.getElementById('totalEmpty');

            function updateTotals() {
                let full = 0,
                    empty = 0;
                document.querySelectorAll('input[name*="[full]"]').forEach(i => {
                    full += parseInt(i.value) || 0;
                });
                document.querySelectorAll('input[name*="[empty]"]').forEach(i => {
                    empty += parseInt(i.value) || 0;
                });
                totalFullCell.textContent = full;
                totalEmptyCell.textContent = empty;
            }
            inputs.forEach(input => input.addEventListener('input', updateTotals));
        }
    });
</script>

<?php
require_once '../partials/footer.php';
?>