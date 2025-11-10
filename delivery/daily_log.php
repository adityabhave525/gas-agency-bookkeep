<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Delivery Person') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];

// -- Handle Form Submissions --
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ACTION 1: Submit Pickup for Approval
    if ($action === 'submit_pickup') {
        $pickup_list_json = $_POST['pickup_list_json'] ?? '[]'; $pickup_list = json_decode($pickup_list_json, true); $vehicle_id = $_POST['vehicle_id'];
        if (empty($pickup_list) || empty($vehicle_id)) { $message = "Please select a vehicle and add at least one cylinder."; $message_type = "danger"; }
        else {
            $can_proceed = true; $conn->begin_transaction();
            try {
                foreach ($pickup_list as $item) {
                    $stmt_stock = $conn->prepare("SELECT closing_stock_full FROM inventory WHERE cylinder_type_id = ? AND inventory_date = ? FOR UPDATE");
                    $stmt_stock->bind_param("is", $item['type_id'], $today); $stmt_stock->execute();
                    $available_stock = $stmt_stock->get_result()->fetch_assoc()['closing_stock_full'] ?? 0;
                    if ($item['full'] > $available_stock) { $can_proceed = false; $message = "Not enough stock for " . htmlspecialchars($item['type']) . ". Requested: " . $item['full'] . ", Available: " . $available_stock; $message_type = "danger"; break; }
                }
                if ($can_proceed) {
                    $stmt_trans = $conn->prepare("INSERT INTO daily_transactions (delivery_person_id, vehicle_id, transaction_date) VALUES (?, ?, ?)");
                    $stmt_trans->bind_param("iis", $user_id, $vehicle_id, $today); $stmt_trans->execute(); $transaction_id = $conn->insert_id;
                    foreach ($pickup_list as $item) {
                        $stmt_details = $conn->prepare("INSERT INTO transaction_details (transaction_id, cylinder_type_id, pickup_full, pickup_empty) VALUES (?, ?, ?, ?)");
                        $stmt_details->bind_param("iiii", $transaction_id, $item['type_id'], $item['full'], $item['empty']); $stmt_details->execute();
                        $stmt_deduct = $conn->prepare("UPDATE inventory SET closing_stock_full = closing_stock_full - ?, closing_stock_empty = closing_stock_empty + ? WHERE cylinder_type_id = ? AND inventory_date = ?");
                        $stmt_deduct->bind_param("iiss", $item['full'], $item['empty'], $item['type_id'], $today); $stmt_deduct->execute();
                    }
                    $conn->commit(); header("Location: daily_log.php"); exit();
                } else { $conn->rollback(); }
            } catch (Exception $e) { $conn->rollback(); $message = "An error occurred."; $message_type = "danger"; }
        }
    }
    // ACTION 3: Submit Final Return
    if ($action === 'submit_return' && isset($_POST['transaction_id'])) {
        $returns = $_POST['returns'] ?? []; $transaction_id = $_POST['transaction_id']; $conn->begin_transaction();
        try {
            foreach ($returns as $detail_id => $values) {
                $return_full = (int)($values['full'] ?? 0); $return_empty = (int)($values['empty'] ?? 0); $cylinder_type_id = (int)($values['type_id'] ?? 0);
                $stmt_update_details = $conn->prepare("UPDATE transaction_details SET return_full = ?, return_empty = ? WHERE transaction_detail_id = ?");
                $stmt_update_details->bind_param("iii", $return_full, $return_empty, $detail_id); $stmt_update_details->execute();
                $stmt_add_stock = $conn->prepare("UPDATE inventory SET closing_stock_full = closing_stock_full + ?, closing_stock_empty = closing_stock_empty + ? WHERE cylinder_type_id = ? AND inventory_date = ?");
                $stmt_add_stock->bind_param("iiss", $return_full, $return_empty, $cylinder_type_id, $today); $stmt_add_stock->execute();
            }
            $stmt_complete = $conn->prepare("UPDATE daily_transactions SET completed_at = NOW() WHERE transaction_id = ?");
            $stmt_complete->bind_param("i", $transaction_id); $stmt_complete->execute();
            $conn->commit(); header("Location: daily_log.php"); exit();
        } catch (Exception $e) { $conn->rollback(); $message = "Error submitting return."; $message_type = "danger"; }
    }
}


// -- Determine Current State & Fetch Data --
$current_state = 'pickup'; $transaction = null; $pickup_details = []; $available_stock_json = '[]'; $available_cylinders = [];
$stmt_check_day = $conn->prepare("SELECT transaction_id, approved_by_keeper_id, completed_at, vehicle_id FROM daily_transactions WHERE delivery_person_id = ? AND transaction_date = ?");
$stmt_check_day->bind_param("is", $user_id, $today); $stmt_check_day->execute();
$result_check_day = $stmt_check_day->get_result();
if ($result_check_day->num_rows > 0) {
    $transaction = $result_check_day->fetch_assoc();
    if ($transaction['completed_at'] !== NULL) { $current_state = 'completed'; }
    else if ($transaction['approved_by_keeper_id'] !== NULL) { $current_state = 'out_for_delivery'; }
    else { $current_state = 'pending_approval'; }
    
    $sql_pickup_details = "SELECT ct.type_name, td.pickup_full, td.pickup_empty, td.transaction_detail_id, ct.cylinder_type_id FROM transaction_details td JOIN cylinder_types ct ON td.cylinder_type_id = ct.cylinder_type_id WHERE td.transaction_id = ?";
    $stmt_pickup_details = $conn->prepare($sql_pickup_details); $stmt_pickup_details->bind_param("i", $transaction['transaction_id']); $stmt_pickup_details->execute();
    $pickup_details = $stmt_pickup_details->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $sql_stock = "SELECT ct.cylinder_type_id, ct.type_name, inv.closing_stock_full FROM cylinder_types ct LEFT JOIN inventory inv ON ct.cylinder_type_id = inv.cylinder_type_id AND inv.inventory_date = ? WHERE inv.closing_stock_full > 0";
    $stmt_stock_fetch = $conn->prepare($sql_stock); $stmt_stock_fetch->bind_param("s", $today); $stmt_stock_fetch->execute();
    $available_cylinders = $stmt_stock_fetch->get_result()->fetch_all(MYSQLI_ASSOC);
    $available_stock_json = json_encode(array_column($available_cylinders, null, 'cylinder_type_id'));
}
$vehicles_result = $conn->query("SELECT vehicle_id, vehicle_number FROM vehicles WHERE is_active = 1");

function render_pickup_summary($pickup_details) {
    if (empty($pickup_details)) return;
    echo '<div class="card mt-4"><div class="card-header bg-secondary text-white"><h5 class="mb-0">Your Pickup Summary</h5></div>';
    echo '<ul class="list-group list-group-flush">';
    foreach ($pickup_details as $item) {
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">' . htmlspecialchars($item['type_name']) . 
             '<span class="badge bg-dark rounded-pill">' . $item['pickup_full'] . ' Full / ' . $item['pickup_empty'] . ' Empty</span></li>';
    }
    echo '</ul></div>';
}

// --- Phase 2: Render Page ---
require_once '../partials/delivery_header.php';
?>

<div class="container-fluid">
    <header class="text-center mb-4"><h1 class="h2">Daily Cylinder Log</h1></header>
    <div id="statusIndicator" class="alert text-center" role="alert"></div>
    <?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div><?php endif; ?>

    <!-- STEP 1: PICKUP PREPARATION (HTML RESTORED) -->
    <div id="pickupSection" style="display: none;">
        <form id="pickupForm" action="daily_log.php" method="POST">
            <input type="hidden" name="action" value="submit_pickup">
            <input type="hidden" name="pickup_list_json" id="pickupListJsonInput">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Cylinder Pickup</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="vehicleSelect" class="form-label">Select Vehicle</label>
                            <select id="vehicleSelect" name="vehicle_id" class="form-select" required>
                                <option selected disabled value="">Choose truck...</option>
                                <?php mysqli_data_seek($vehicles_result, 0); while($v = $vehicles_result->fetch_assoc()): ?>
                                    <option value="<?php echo $v['vehicle_id']; ?>"><?php echo htmlspecialchars($v['vehicle_number']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Add Cylinder</label>
                            <div class="input-group">
                                <select id="cylinderTypeSelect" class="form-select">
                                    <option selected disabled>Select type...</option>
                                    <?php foreach ($available_cylinders as $cyl): ?>
                                        <option value="<?php echo $cyl['cylinder_type_id']; ?>"><?php echo htmlspecialchars($cyl['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input id="pickupFullInput" type="number" class="form-control" placeholder="Full (F)" min="0">
                                <input id="pickupEmptyInput" type="number" class="form-control" placeholder="Empty (M)" min="0">
                                <button id="addCylinderBtn" class="btn btn-primary" type="button"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><strong>Pickup List</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Type</th><th>Pickup (F)</th><th>Pickup (M)</th><th>Action</th></tr></thead>
                            <tbody id="pickupTableBody"></tbody>
                        </table>
                    </div>
                    <div id="pickupListEmpty" class="text-center p-4 text-muted">Your pickup list is empty.</div>
                </div>
                <div class="card-footer text-end"><button type="submit" id="submitForApprovalBtn" class="btn btn-success" disabled>Submit for Approval</button></div>
            </div>
        </form>
    </div>

    <!-- PENDING APPROVAL -->
    <div id="approvalSection" style="display: none;">
        <div class="card bg-light border-warning text-center"><div class="card-body">
            <h5 class="card-title text-warning"><i class="bi bi-hourglass-split"></i> Awaiting Approval</h5>
            <p class="mb-0">Your pickup request has been submitted. Please ask the Godown Keeper to verify and approve it.</p>
        </div></div>
        <?php render_pickup_summary($pickup_details); ?>
    </div>
    
    <!-- RETURN FORM -->
    <div id="returnSection" style="display: none;">
        <form action="daily_log.php" method="POST">
            <input type="hidden" name="action" value="submit_return">
            <input type="hidden" name="transaction_id" value="<?php echo $transaction['transaction_id'] ?? ''; ?>">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Cylinder Return</h5></div>
                <div class="card-body">
                    <p>Enter the number of cylinders you are bringing back for each type.</p>
                    <div class="table-responsive"><table class="table">
                        <thead class="table-light text-center"><tr><th>Type</th><th>Picked Up (F/M)</th><th>Returned (F)</th><th>Returned (M)</th></tr></thead>
                        <tbody>
                            <?php foreach($pickup_details as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['type_name']); ?></strong><input type="hidden" name="returns[<?php echo $item['transaction_detail_id']; ?>][type_id]" value="<?php echo $item['cylinder_type_id']; ?>"></td>
                                    <td class="text-center"><?php echo $item['pickup_full']; ?> F / <?php echo $item['pickup_empty']; ?> M</td>
                                    <td><input type="number" class="form-control" name="returns[<?php echo $item['transaction_detail_id']; ?>][full]" min="0" placeholder="0" required></td>
                                    <td><input type="number" class="form-control" name="returns[<?php echo $item['transaction_detail_id']; ?>][empty]" min="0" placeholder="0" required></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
                <div class="card-footer text-end"><button type="submit" class="btn btn-primary">Submit Final Return</button></div>
            </div>
        </form>
    </div>

    <!-- COMPLETED -->
    <div id="completedSection" style="display: none;">
        <div class="alert alert-success text-center">
            <h4><i class="bi bi-check-circle-fill"></i> Day Completed!</h4>
            <p>Your final log has been submitted successfully.</p>
        </div>
        <?php render_pickup_summary($pickup_details); ?>
    </div>
</div>

<script>
    // COMPLETE JAVASCRIPT BLOCK RESTORED
    document.addEventListener('DOMContentLoaded', function() {
        const currentState = '<?php echo $current_state; ?>';
        const availableStock = JSON.parse('<?php echo $available_stock_json; ?>');
        const sections = {
            pickup: document.getElementById('pickupSection'),
            pending_approval: document.getElementById('approvalSection'),
            out_for_delivery: document.getElementById('returnSection'),
            completed: document.getElementById('completedSection')
        };
        const statusIndicator = document.getElementById('statusIndicator');
        Object.values(sections).forEach(sec => sec ? sec.style.display = 'none' : null);
        if (sections[currentState]) sections[currentState].style.display = 'block';
        
        switch (currentState) {
            case 'pending_approval':
                statusIndicator.innerHTML = '<strong>Step 2:</strong> Waiting for Godown Keeper approval...';
                statusIndicator.className = 'alert alert-warning text-center';
                break;
            case 'completed':
                statusIndicator.innerHTML = '<strong>Day Completed!</strong> Your log has been submitted.';
                statusIndicator.className = 'alert alert-primary text-center';
                break;
            case 'out_for_delivery':
                statusIndicator.innerHTML = '<strong>Step 3:</strong> You are out for delivery. Fill the return form when you are back.';
                statusIndicator.className = 'alert alert-success text-center';
                break;
            case 'pickup':
            default:
                statusIndicator.innerHTML = '<strong>Step 1:</strong> Select vehicle and add cylinders for today\'s delivery.';
                statusIndicator.className = 'alert alert-info text-center';
                break;
        }

        if (currentState === 'pickup') {
            let pickupList = [];
            const pickupListJsonInput = document.getElementById('pickupListJsonInput');
            const pickupTableBody = document.getElementById('pickupTableBody');
            const pickupListEmpty = document.getElementById('pickupListEmpty');
            const addCylinderBtn = document.getElementById('addCylinderBtn');
            const submitBtn = document.getElementById('submitForApprovalBtn');
            const vehicleSelect = document.getElementById('vehicleSelect');

            function renderPickupTable() {
                pickupTableBody.innerHTML = '';
                pickupList.length === 0 ? pickupListEmpty.style.display = 'block' : pickupListEmpty.style.display = 'none';
                pickupList.forEach((item, index) => {
                    const row = `<tr><td>${item.type}</td><td>${item.full}</td><td>${item.empty}</td><td><i class="bi bi-trash-fill text-danger" role="button" data-index="${index}"></i></td></tr>`;
                    pickupTableBody.insertAdjacentHTML('beforeend', row);
                });
                pickupListJsonInput.value = JSON.stringify(pickupList);
                submitBtn.disabled = !(pickupList.length > 0 && vehicleSelect.value);
            }
            addCylinderBtn.addEventListener('click', function() {
                const typeSelect = document.getElementById('cylinderTypeSelect');
                const typeId = typeSelect.value;
                const fullInput = document.getElementById('pickupFullInput');
                const emptyInput = document.getElementById('pickupEmptyInput');
                const fullQty = parseInt(fullInput.value) || 0;
                const emptyQty = parseInt(emptyInput.value) || 0;
                if (!typeId || (fullQty === 0 && emptyQty === 0)) { alert('Please select a type and enter a quantity.'); return; }
                const stockInfo = availableStock[typeId];
                if (fullQty > stockInfo.closing_stock_full) {
                    alert(`Error: Not enough stock for ${stockInfo.type_name}.\nAvailable: ${stockInfo.closing_stock_full}\nRequested: ${fullQty}`);
                    return;
                }
                pickupList.push({ type_id: typeId, type: stockInfo.type_name, full: fullQty, empty: emptyQty });
                renderPickupTable();
                typeSelect.value = ''; fullInput.value = ''; emptyInput.value = '';
                typeSelect.querySelector(`option[value='${typeId}']`).disabled = true;
            });
            pickupTableBody.addEventListener('click', function(e) {
                if (e.target.dataset.index) {
                    const removedItem = pickupList.splice(e.target.dataset.index, 1)[0];
                    document.querySelector(`#cylinderTypeSelect option[value='${removedItem.type_id}']`).disabled = false;
                    renderPickupTable();
                }
            });
            vehicleSelect.addEventListener('change', renderPickupTable);
        }
    });
</script>

<?php require_once '../partials/footer.php'; ?>