<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Godown Keeper') {
    echo "<div class='alert alert-danger'>Access Denied. Please <a href='login.php'>login</a> to continue.</div>";
    require_once '../partials/footer.php';
    exit();
}

$message = '';
$message_type = '';
$today = date('Y-m-d');
$keeper_id = $_SESSION['user_id'];

// -- Handle Form Submission: Approve a Pickup --
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    $transaction_id_to_approve = $_POST['transaction_id'];

    // Update the transaction, setting the keeper's ID as the approver
    $stmt_approve = $conn->prepare("UPDATE daily_transactions SET approved_by_keeper_id = ? WHERE transaction_id = ? AND approved_by_keeper_id IS NULL");
    $stmt_approve->bind_param("ii", $keeper_id, $transaction_id_to_approve);

    if ($stmt_approve->execute() && $stmt_approve->affected_rows > 0) {
        header("Location: approve_pickup.php?status=approved&tid=" . $transaction_id_to_approve);
        exit();
    } else {
        $message = "Error approving the request or it was already approved.";
        $message_type = "danger";
    }
}

// Check for status messages from redirect
if (isset($_GET['status']) && $_GET['status'] == 'approved') {
    $message = "Pickup request #" . htmlspecialchars($_GET['tid']) . " has been successfully approved.";
    $message_type = "success";
}

// --- Fetch All Pending Pickups for Today ---
// This query joins multiple tables to get all the necessary info
$sql = "SELECT 
            dt.transaction_id, 
            u.full_name as delivery_person_name,
            v.vehicle_number,
            td.pickup_full, 
            td.pickup_empty, 
            ct.type_name
        FROM daily_transactions dt
        JOIN users u ON dt.delivery_person_id = u.user_id
        JOIN vehicles v ON dt.vehicle_id = v.vehicle_id
        JOIN transaction_details td ON dt.transaction_id = td.transaction_id
        JOIN cylinder_types ct ON td.cylinder_type_id = ct.cylinder_type_id
        WHERE dt.transaction_date = ? AND dt.approved_by_keeper_id IS NULL
        ORDER BY dt.transaction_id ASC";

$stmt_fetch = $conn->prepare($sql);
$stmt_fetch->bind_param("s", $today);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();

// Group the results by transaction_id in a PHP array for easy display
$pending_requests = [];
while ($row = $result->fetch_assoc()) {
    $tid = $row['transaction_id'];
    if (!isset($pending_requests[$tid])) {
        $pending_requests[$tid] = [
            'delivery_person_name' => $row['delivery_person_name'],
            'vehicle_number' => $row['vehicle_number'],
            'items' => []
        ];
    }
    $pending_requests[$tid]['items'][] = [
        'type_name' => $row['type_name'],
        'pickup_full' => $row['pickup_full'],
        'pickup_empty' => $row['pickup_empty']
    ];
}

require_once '../partials/keeper_header.php';

?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Approve Delivery Pickups</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Pending Requests for <?php echo date('F j, Y'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($pending_requests)): ?>
                <div class="alert alert-info text-center">There are no pending pickup requests at this time.</div>
            <?php else: ?>
                <div class="accordion" id="pickupAccordion">
                    <?php foreach ($pending_requests as $tid => $request): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $tid; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $tid; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $tid; ?>">
                                    <strong><?php echo htmlspecialchars($request['delivery_person_name']); ?></strong>&nbsp;(Vehicle: <?php echo htmlspecialchars($request['vehicle_number']); ?>)
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $tid; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $tid; ?>" data-bs-parent="#pickupAccordion">
                                <div class="accordion-body">
                                    <h6>Cylinders Requested:</h6>
                                    <ul class="list-group mb-3">
                                        <?php foreach ($request['items'] as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($item['type_name']); ?>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo $item['pickup_full']; ?> Full, <?php echo $item['pickup_empty']; ?> Empty
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <form action="approve_pickup.php" method="POST" class="text-end">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="transaction_id" value="<?php echo $tid; ?>">
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle-fill"></i> Cross-check & Approve</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../partials/footer.php';
?>