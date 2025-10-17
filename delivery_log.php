<?php
// --- MOCK DATA ---
// This would be fetched from your database in the real application.
$cylinder_types = [
    "Sub.Cy",
    "N/S Cy.",
    "N/S Cy. EDRH",
    "19kg Cy",
    "35kg Cy",
    "05kg Cy",
    "47kg Vot. Cy",
    "47kg Lot. Cy"
];

// Mock vehicle list for the delivery person
$vehicles = [
    "MH-12-AB-3456",
    "MH-14-CD-7890",
    "MH-01-EF-1234"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Person - Daily Log</title>
    <!-- Bootstrap 5 CSS & Icons -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="./css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #e9ecef;
        }

        .card {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .step-header {
            font-weight: bold;
            color: #0d6efd;
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table input[type="number"] {
            max-width: 80px;
            text-align: center;
        }

        /* Hide number input spinners for a cleaner look on mobile */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .action-button-group {
            display: flex;
            gap: 10px;
        }

        .remove-btn {
            color: #dc3545;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="container my-3 my-md-4">
        <header class="text-center mb-4">
            <h1 class="display-6">Daily Cylinder Log</h1>
            <p class="lead">For Delivery Person: <span class="fw-bold">Ramesh Kumar</span></p>
        </header>

        <!-- Main Status Indicator -->
        <div id="statusIndicator" class="alert alert-info text-center" role="alert">
            <i class="bi bi-info-circle-fill"></i> <strong>Step 1:</strong> Select your vehicle and add cylinders for today's delivery.
        </div>

        <!-- ===== STEP 1: PICKUP PREPARATION ===== -->
        <div id="pickupSection">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title step-header">Cylinder Pickup</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="vehicleSelect" class="form-label">Select Vehicle</label>
                            <select id="vehicleSelect" class="form-select">
                                <option selected disabled>Choose your truck...</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo htmlspecialchars($vehicle); ?>"><?php echo htmlspecialchars($vehicle); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Add Cylinder to List</label>
                            <div class="input-group">
                                <select id="cylinderTypeSelect" class="form-select">
                                    <option selected disabled>Select type...</option>
                                    <?php foreach ($cylinder_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input id="pickupFullInput" type="number" class="form-control" placeholder="Full (F)" min="0">
                                <input id="pickupEmptyInput" type="number" class="form-control" placeholder="Empty (M)" min="0">
                                <button id="addCylinderBtn" class="btn btn-primary" type="button"><i class="bi bi-plus-lg"></i> Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Pickup List</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Cylinder Type</th>
                                    <th>Pickup (F)</th>
                                    <th>Pickup (M)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pickupTableBody">
                                <!-- Rows will be added here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div id="pickupListEmpty" class="text-center p-4 text-muted">Your pickup list is empty.</div>
                </div>
                <div class="card-footer text-end">
                    <button id="submitForApprovalBtn" class="btn btn-success" disabled>Submit for Godown Keeper Approval</button>
                </div>
            </div>
        </div>
        <!-- ===== END STEP 1 ===== -->

        <!-- ===== GODOWN KEEPER APPROVAL SIMULATION ===== -->
        <div id="approvalSection" class="text-center my-4" style="display: none;">
            <div class="card bg-light border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning"><i class="bi bi-person-check-fill"></i> Godown Keeper's View (Simulation)</h5>
                    <p>Please physically verify the cylinder counts below against the delivery person's cart.</p>
                    <button id="approveBtn" class="btn btn-warning btn-lg">Approve Pickup</button>
                </div>
            </div>
        </div>
        <!-- ===== END SIMULATION ===== -->

        <!-- ===== STEP 2: CYLINDER RETURN ===== -->
        <div id="returnSection" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 step-header">Cylinder Return</h5>
                </div>
                <div class="card-body">
                    <p>After completing your deliveries, enter the number of full and empty cylinders you are bringing back.</p>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Cylinder Type</th>
                                    <th>Picked Up (F/M)</th>
                                    <th>Returned (F)</th>
                                    <th>Returned (M)</th>
                                </tr>
                            </thead>
                            <tbody id="returnTableBody">
                                <!-- Return rows will be populated here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button id="submitReturnBtn" class="btn btn-primary">Submit Final Return & End Day</button>
                </div>
            </div>
        </div>
        <!-- ===== END STEP 2 ===== -->

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->
    <script src="./js//bootstrap.bundle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // State object to hold the data
            const deliveryLog = {
                pickupList: []
            };

            // --- Element Selectors ---
            const pickupSection = document.getElementById('pickupSection');
            const approvalSection = document.getElementById('approvalSection');
            const returnSection = document.getElementById('returnSection');
            const statusIndicator = document.getElementById('statusIndicator');

            const vehicleSelect = document.getElementById('vehicleSelect');
            const cylinderTypeSelect = document.getElementById('cylinderTypeSelect');
            const pickupFullInput = document.getElementById('pickupFullInput');
            const pickupEmptyInput = document.getElementById('pickupEmptyInput');
            const addCylinderBtn = document.getElementById('addCylinderBtn');

            const pickupTableBody = document.getElementById('pickupTableBody');
            const pickupListEmpty = document.getElementById('pickupListEmpty');
            const submitForApprovalBtn = document.getElementById('submitForApprovalBtn');
            const approveBtn = document.getElementById('approveBtn');

            const returnTableBody = document.getElementById('returnTableBody');
            const submitReturnBtn = document.getElementById('submitReturnBtn');

            // --- Functions ---
            const updateStatus = (message, type = 'info') => {
                statusIndicator.className = `alert alert-${type} text-center`;
                statusIndicator.innerHTML = message;
            };

            const renderPickupTable = () => {
                pickupTableBody.innerHTML = '';
                if (deliveryLog.pickupList.length === 0) {
                    pickupListEmpty.style.display = 'block';
                    submitForApprovalBtn.disabled = true;
                } else {
                    pickupListEmpty.style.display = 'none';
                    submitForApprovalBtn.disabled = (vehicleSelect.value === 'Choose your truck...');
                    deliveryLog.pickupList.forEach((item, index) => {
                        const row = `
                    <tr>
                        <td>${item.type}</td>
                        <td>${item.full}</td>
                        <td>${item.empty}</td>
                        <td><i class="bi bi-trash-fill remove-btn" data-index="${index}"></i></td>
                    </tr>`;
                        pickupTableBody.insertAdjacentHTML('beforeend', row);
                    });
                }
            };

            const handleAddCylinder = () => {
                const type = cylinderTypeSelect.value;
                const full = parseInt(pickupFullInput.value) || 0;
                const empty = parseInt(pickupEmptyInput.value) || 0;

                if (type === 'Select type...' || (full === 0 && empty === 0)) {
                    alert('Please select a cylinder type and enter a quantity for Full or Empty.');
                    return;
                }

                // Add to state
                deliveryLog.pickupList.push({
                    type,
                    full,
                    empty
                });

                // Update UI
                renderPickupTable();

                // Reset inputs
                cylinderTypeSelect.value = 'Select type...';
                pickupFullInput.value = '';
                pickupEmptyInput.value = '';

                // Disable the option that was just added
                document.querySelector(`#cylinderTypeSelect option[value="${type}"]`).disabled = true;
            };

            const handleRemoveCylinder = (index) => {
                const removedItem = deliveryLog.pickupList.splice(index, 1)[0];
                // Re-enable the option in the dropdown
                document.querySelector(`#cylinderTypeSelect option[value="${removedItem.type}"]`).disabled = false;
                renderPickupTable();
            };

            const handleSubmitForApproval = () => {
                pickupSection.style.display = 'none';
                updateStatus('<i class="bi bi-hourglass-split"></i> <strong>Step 2:</strong> Waiting for Godown Keeper approval...', 'warning');

                // Simulate Godown Keeper interaction
                setTimeout(() => {
                    approvalSection.style.display = 'block';
                }, 1500); // 1.5 second delay for realism
            };

            const handleApproval = () => {
                approvalSection.style.display = 'none';
                updateStatus('<i class="bi bi-truck"></i> <strong>Step 3:</strong> Approved! You are now out for delivery. Fill the return form when you are back.', 'success');

                // Populate and show the return section
                returnSection.style.display = 'block';
                returnTableBody.innerHTML = '';
                deliveryLog.pickupList.forEach(item => {
                    const row = `
                <tr>
                    <td>${item.type}</td>
                    <td><strong>${item.full} F / ${item.empty} M</strong></td>
                    <td><input type="number" class="form-control" placeholder="0" min="0"></td>
                    <td><input type="number" class="form-control" placeholder="0" min="0"></td>
                </tr>`;
                    returnTableBody.insertAdjacentHTML('beforeend', row);
                });
            };

            const handleFinalSubmit = () => {
                if (confirm('Are you sure you want to submit your final return? This will end your day.')) {
                    returnSection.style.display = 'none';
                    updateStatus('<i class="bi bi-check-circle-fill"></i> <strong>Day Completed!</strong> Your log has been submitted successfully.', 'primary');
                }
            };

            // --- Event Listeners ---
            addCylinderBtn.addEventListener('click', handleAddCylinder);

            pickupTableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-btn')) {
                    const index = e.target.getAttribute('data-index');
                    handleRemoveCylinder(index);
                }
            });

            vehicleSelect.addEventListener('change', function() {
                if (deliveryLog.pickupList.length > 0) {
                    submitForApprovalBtn.disabled = (this.value === 'Choose your truck...');
                }
            });

            submitForApprovalBtn.addEventListener('click', handleSubmitForApproval);
            approveBtn.addEventListener('click', handleApproval);
            submitReturnBtn.addEventListener('click', handleFinalSubmit);

            // --- Initial Render ---
            renderPickupTable();
        });
    </script>

</body>

</html>