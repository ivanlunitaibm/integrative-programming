<?php
date_default_timezone_set('Asia/Manila');

session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection
require 'connection_admin.php';

// Get today's date
$today = date('Y-m-d');

// Fetch total transactions for today
$sql = "SELECT COUNT(*) as confirmedCount 
        FROM Reservation 
        WHERE rStatus = 'confirmed' AND rResDate = :today";
$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$totalTransactionsToday = $stmt->fetchColumn();

// Calculate current sales for today
$sql = "SELECT 
            SUM(pAmount) AS totalSales
        FROM (
            SELECT DISTINCT uUserID, pAmount, pTransDate
            FROM PaymentTransaction
            WHERE DATE(pTransDate) = :today
        ) AS pt
        UNION ALL
        SELECT 
            COUNT(*) * 200 AS reservationSales
        FROM Reservation
        WHERE rStatus = 'confirmed' 
            AND uUserID IS NULL 
            AND DATE(rResDate) = :today";

$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSales = 0;
foreach ($result as $row) {
    $totalSales += $row['totalSales'];
}

$currentSalesToday = $totalSales;

// Fetch number of reservations grouped by user ID and reservation date
$sql = "SELECT uUserID, COUNT(*) as reservationCount 
        FROM Reservation 
        WHERE rStatus = 'confirmed' AND rResDate = :today 
        GROUP BY uUserID, rResDate";
$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$reservationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total and available timeslots for today grouped by facility
$sql = "SELECT 
            Facility.fFacilityID,
            Facility.fType,
            COUNT(Reservation.rResID) as totalSlots,
            SUM(CASE WHEN Reservation.rStatus = 'confirmed' THEN 1 ELSE 0 END) as confirmedSlots
        FROM Facility
        LEFT JOIN Reservation 
        ON Facility.fFacilityID = Reservation.fFacilityID 
        AND Reservation.rResDate = :today
        GROUP BY Facility.fFacilityID, Facility.fType";
$stmt = $pdo->prepare($sql);
$stmt->execute(['today' => $today]);
$slotsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatTime($time) {
    return date("h:i A", strtotime($time)); // Format time as 12-hour format with AM/PM
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sales</title>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Corrected path to Bootstrap CSS -->
    <link href="http://localhost/Courtlifyy/bootstrapfile/bootstrap-5.3.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .active {
            font-weight: bolder;
        }
        .row h3{
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3">Citywalk Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="process_logout.php">Sign out</a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="admin_statistics.php">
                                <span data-feather="home"></span>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <span data-feather="home"></span>
                                Manage Timeslots
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_sales.php">
                                <span data-feather="file"></span>
                                Sales Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reservation_summary.php">
                                <span data-feather="file"></span>
                                Reservation Summary Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_audittrail.php">
                                <span data-feather="users"></span>
                                Customer Activity Report
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="row">
                    <h3>Showing Statistics for <?php echo date('F d, Y', strtotime($today)); ?></h3>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">Total Court Hours Filled</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $totalTransactionsToday; ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">Current Sales</div>
                            <div class="card-body">
                                <h5 class="card-title">₱<?php echo number_format($currentSalesToday, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <h3>Number of Available Timeslots per each court</h3>
                    </div>
                    <?php foreach ($slotsData as $slot) { ?>
                        <div class="col-md-4">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-header">Court <?php echo $slot['fFacilityID']; ?></div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo ($slot['totalSlots'] - $slot['confirmedSlots']) . ' court hours' ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
