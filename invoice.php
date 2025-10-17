<?php
// --- MOCK DATA ---
// In the future, this data will come from your MySQL database.
// For now, we use these arrays to dynamically generate the table.

// List of delivery personnel
$delivery_boys = [
    "Ramesh Kumar",
    "Suresh Singh",
    "Amit Patel",
    "Vikas Sharma",
    "Manoj Verma"
    // ... you can add up to 20-30 names here
];

// List of all cylinder types
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gas Agency - Daily Records</title>
    <!-- Bootstrap 5 CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <style>
        /* Custom styles for better presentation */
        body {
            background-color: #f8f9fa;
        }

        .invoice-header {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .table-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        /* Style for table header to make it more readable */
        thead.table-dark th {
            text-align: center;
            vertical-align: middle;
        }

        /* Ensure input fields in table cells are consistent */
        .table td input {
            width: 100%;
            min-width: 60px;
            /* Minimum width for inputs on small screens */
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.5rem;
            text-align: center;
        }

        /* Align delivery boy names to the left */
        .delivery-boy-name {
            text-align: left;
            font-weight: bold;
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <div class="container-fluid my-4">

        <!-- Invoice Header Section -->
        <div class="invoice-header">
            <h1 class="text-center mb-4">Daily Cylinder Records</h1>
            <form>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label for="invoiceNo" class="form-label">Invoice No.</label>
                        <input type="text" class="form-control" id="invoiceNo" placeholder="e.g., INV-12345">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="invoiceDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="invoiceDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="truckNo" class="form-label">Truck No.</label>
                        <input type="text" class="form-control" id="truckNo" placeholder="e.g., MH-12-AB-3456">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="totalCylinders" class="form-label">Total No. of Cylinders</label>
                        <input type="number" class="form-control" id="totalCylinders" placeholder="Calculated automatically" readonly>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Invoice Table for Cylinder Data -->
        <div class="table-container">
            <h3 class="mb-3">Cylinder Details</h3>
            <!-- The 'table-responsive' class is key for responsiveness on small screens -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2">Delivery Boy Name</th>
                            <?php foreach ($cylinder_types as $type): ?>
                                <th colspan="2"><?php echo htmlspecialchars($type); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($cylinder_types as $type): ?>
                                <th>F</th>
                                <th>M</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delivery_boys as $boy): ?>
                            <tr>
                                <td class="delivery-boy-name"><?php echo htmlspecialchars($boy); ?></td>
                                <?php foreach ($cylinder_types as $index => $type): ?>
                                    <td>
                                        <!-- Input for Full cylinders -->
                                        <input type="number" name="cylinders[<?php echo htmlspecialchars($boy); ?>][<?php echo $index; ?>][F]" min="0" placeholder="0">
                                    </td>
                                    <td>
                                        <!-- Input for Empty (M) cylinders -->
                                        <input type="number" name="cylinders[<?php echo htmlspecialchars($boy); ?>][<?php echo $index; ?>][M]" min="0" placeholder="0">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Amount and Submission Section -->
        <div class="invoice-header mt-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label for="totalAmount" class="form-label fs-5"><strong>Total Amount Earned (₹)</strong></label>
                    <input type="number" class="form-control form-control-lg" id="totalAmount" placeholder="Enter total amount for the day">
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg">Save Records</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script> -->
    <script src="./js/bootstrap.bundle.js"></script>
</body>

</html>