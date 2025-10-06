<?php
// --- MOCK DATA (for UI structure) ---
// This array simulates the data you would fetch from the database.
// In a real application, you would query the 'users' table to get this list.
$employees = [
    ['id' => 2, 'name' => 'Sanjay Gupta', 'role' => 'Godown Manager'],
    ['id' => 3, 'name' => 'Priya Sharma', 'role' => 'Godown Keeper'],
    ['id' => 4, 'name' => 'Ramesh Kumar', 'role' => 'Delivery Person'],
    ['id' => 5, 'name' => 'Suresh Singh', 'role' => 'Delivery Person'],
    ['id' => 6, 'name' => 'Amit Patel', 'role' => 'Delivery Person'],
    ['id' => 7, 'name' => 'Vikas Sharma', 'role' => 'Delivery Person']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner - Attendance Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <!-- Bootstrap Icons for a nicer look -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .filter-card,
        .attendance-table-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>

    <div class="container-fluid main-container">
        <header class="text-center mb-4">
            <h1>Employee Attendance Records</h1>
            <p class="lead">Monitor and filter attendance records for all staff.</p>
        </header>

        <!-- Filters Section -->
        <div class="card filter-card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-funnel-fill"></i> Filter Options</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <!-- Date Filter -->
                    <div class="col-md-6 col-lg-4">
                        <label for="filterDate" class="form-label"><strong>Date</strong></label>
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                    <!-- User Filter -->
                    <div class="col-md-6 col-lg-4">
                        <label for="filterUser" class="form-label"><strong>Employee</strong></label>
                        <select id="filterUser" class="form-select">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo htmlspecialchars($employee['name']); ?>">
                                    <?php echo htmlspecialchars($employee['name']) . ' (' . htmlspecialchars($employee['role']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Status Filter -->
                    <div class="col-md-6 col-lg-2">
                        <label for="filterStatus" class="form-label"><strong>Status</strong></label>
                        <select id="filterStatus" class="form-select">
                            <option value="">All</option>
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <!-- Action Buttons -->
                    <div class="col-md-6 col-lg-2 d-flex">
                        <button class="btn btn-primary w-100 me-2" id="applyFilterBtn">Filter</button>
                        <button class="btn btn-secondary w-100" id="resetFilterBtn">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table Section -->
        <div class="card attendance-table-card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-calendar-check-fill"></i> Attendance Log</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee Name</th>
                                <th>Role</th>
                                <th>Date</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th>Scanned By</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <!-- JS will populate this section -->
                        </tbody>
                    </table>
                </div>
                <div id="noResults" class="text-center p-4" style="display: none;">
                    <p class="text-muted fs-5">No records found matching your criteria.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script> -->
    <script src="./js/bootstrap.bundle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // --- MOCK DATA for JavaScript Filtering ---
            // This simulates the full dataset from your 'attendance' table joined with 'users' and 'roles'.
            const attendanceData = [{
                    name: 'Sanjay Gupta',
                    role: 'Godown Manager',
                    date: '2025-10-06',
                    checkInTime: '09:05 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Priya Sharma',
                    role: 'Godown Keeper',
                    date: '2025-10-06',
                    checkInTime: '08:55 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Ramesh Kumar',
                    role: 'Delivery Person',
                    date: '2025-10-06',
                    checkInTime: '09:15 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Suresh Singh',
                    role: 'Delivery Person',
                    date: '2025-10-06',
                    checkInTime: null,
                    status: 'Absent',
                    scannedBy: null
                },
                {
                    name: 'Amit Patel',
                    role: 'Delivery Person',
                    date: '2025-10-06',
                    checkInTime: '09:20 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Sanjay Gupta',
                    role: 'Godown Manager',
                    date: '2025-10-05',
                    checkInTime: '09:02 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Priya Sharma',
                    role: 'Godown Keeper',
                    date: '2025-10-05',
                    checkInTime: '08:58 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Ramesh Kumar',
                    role: 'Delivery Person',
                    date: '2025-10-05',
                    checkInTime: null,
                    status: 'Absent',
                    scannedBy: null
                },
                {
                    name: 'Suresh Singh',
                    role: 'Delivery Person',
                    date: '2025-10-05',
                    checkInTime: '09:10 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Amit Patel',
                    role: 'Delivery Person',
                    date: '2025-10-05',
                    checkInTime: '09:25 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
                {
                    name: 'Vikas Sharma',
                    role: 'Delivery Person',
                    date: '2025-10-06',
                    checkInTime: null,
                    status: 'Absent',
                    scannedBy: null
                },
                {
                    name: 'Vikas Sharma',
                    role: 'Delivery Person',
                    date: '2025-10-05',
                    checkInTime: '09:18 AM',
                    status: 'Present',
                    scannedBy: 'Priya Sharma'
                },
            ];

            const tableBody = document.getElementById('attendanceTableBody');
            const noResultsDiv = document.getElementById('noResults');
            const applyFilterBtn = document.getElementById('applyFilterBtn');
            const resetFilterBtn = document.getElementById('resetFilterBtn');

            // Function to render the table rows based on the provided data
            const renderTable = (data) => {
                tableBody.innerHTML = ''; // Clear existing rows
                noResultsDiv.style.display = 'none';

                if (data.length === 0) {
                    noResultsDiv.style.display = 'block';
                    return;
                }

                data.forEach(record => {
                    const statusBadge = record.status === 'Present' ?
                        '<span class="badge bg-success">Present</span>' :
                        '<span class="badge bg-danger">Absent</span>';

                    const row = `
                    <tr>
                        <td>${record.name}</td>
                        <td>${record.role}</td>
                        <td>${record.date}</td>
                        <td>${record.checkInTime || 'N/A'}</td>
                        <td>${statusBadge}</td>
                        <td>${record.scannedBy || 'N/A'}</td>
                    </tr>
                `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            };

            // Function to handle filtering
            const handleFilter = () => {
                const date = document.getElementById('filterDate').value;
                const user = document.getElementById('filterUser').value;
                const status = document.getElementById('filterStatus').value;

                let filteredData = attendanceData;

                if (date) {
                    filteredData = filteredData.filter(record => record.date === date);
                }
                if (user) {
                    filteredData = filteredData.filter(record => record.name === user);
                }
                if (status) {
                    filteredData = filteredData.filter(record => record.status === status);
                }

                renderTable(filteredData);
            };

            // Function to handle reset
            const handleReset = () => {
                document.getElementById('filterDate').value = '';
                document.getElementById('filterUser').value = '';
                document.getElementById('filterStatus').value = '';
                renderTable(attendanceData);
            };

            // Add event listeners to buttons
            applyFilterBtn.addEventListener('click', handleFilter);
            resetFilterBtn.addEventListener('click', handleReset);

            // Initial render of the full table
            renderTable(attendanceData);
        });
    </script>
</body>

</html>