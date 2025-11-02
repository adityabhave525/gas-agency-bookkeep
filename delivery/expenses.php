<?php
// --- Phase 1: Initialize and Process ---
require_once '../db.php';

// Include the new delivery-specific header
require_once '../partials/delivery_header.php';

// -- Security: Access Control for Delivery Person role --
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Delivery Person') {
    echo "<div class='alert alert-danger'>Access Denied. Please <a href='login.php'>login</a> to continue.</div>";
    require_once '../partials/footer.php';
    exit();
}

$message = '';
$message_type = '';
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];

// -- Handle Form Submission: Add a new expense --
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = trim($_POST['description']);
    $amount = $_POST['amount'];

    // Basic Validation
    if (empty($description) || !is_numeric($amount) || $amount <= 0) {
        $message = "Please enter a valid description and a positive amount.";
        $message_type = "danger";
    } else {
        // All good, insert into the database
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, expense_date, description, amount) VALUES (?, ?, ?, ?)");
        // Use "isdd" for integer, string, double, double if needed, but "isds" works fine as MySQL handles casting. Let's use d for amount.
        $stmt->bind_param("issd", $user_id, $today, $description, $amount);

        if ($stmt->execute()) {
            $message = "Expense added successfully.";
            $message_type = "success";
        } else {
            $message = "Error adding expense. Please try again.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// -- Fetch today's expenses for the logged-in user --
$stmt_fetch = $conn->prepare("SELECT expense_id, description, amount FROM expenses WHERE user_id = ? AND expense_date = ? ORDER BY expense_id DESC");
$stmt_fetch->bind_param("is", $user_id, $today);
$stmt_fetch->execute();
$expenses_today = $stmt_fetch->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total for the day
$total_expenses = array_sum(array_column($expenses_today, 'amount'));

?>

<div class="container-fluid">
    <div class="row">
        <!-- Add Expense Form Column -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-dark">
                    <h4 class="mb-0"><i class="bi bi-plus-circle-fill"></i> Add Daily Expense</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form action="expenses.php" method="POST">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="e.g., Fuel for truck, Lunch" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₹)</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" placeholder="e.g., 500.50" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-info">Add Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Today's Expenses List Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-receipt"></i> Expenses for <?php echo date('F j, Y'); ?></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses_today)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No expenses recorded for today.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenses_today as $expense): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                            <td class="text-end"><?php echo number_format($expense['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-group-divider fw-bold">
                                <tr>
                                    <td>Total Expenses</td>
                                    <td class="text-end fs-5">₹<?php echo number_format($total_expenses, 2); ?></td>
                                </tr>
                            </tfoot>
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