<?php
date_default_timezone_set('Asia/Manila');

session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection
require 'connection_admin.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$facilityIDFilter = isset($_GET['facilityID']) ? intval($_GET['facilityID']) : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 13;
$offset = ($page - 1) * $recordsPerPage;

// Handle adding reservation
if (isset($_POST['add_reservation']) && isset($_POST['reservation_id'])) {
    $reservationId = $_POST['reservation_id'];
    $addSql = "UPDATE Reservation SET rStatus = 'confirmed' WHERE rResID = :reservationId";
    $addStmt = $pdo->prepare($addSql);
    $addStmt->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
    $addStmt->execute();
    $_SESSION['alert_message'] = 'Timeslot filled.';
    $_SESSION['alert_type'] = 'success';
    header("Location: admin_dashboard.php?date=$date&facilityID=$facilityIDFilter&status=$statusFilter&page=$page");
    exit();
}

// Handle canceling reservation
if (isset($_POST['cancel_reservation']) && isset($_POST['reservation_id'])) {
    $reservationId = $_POST['reservation_id'];
    $cancelSql = "UPDATE Reservation SET rStatus = 'available' WHERE rResID = :reservationId";
    $cancelStmt = $pdo->prepare($cancelSql);
    $cancelStmt->bindParam(':reservationId', $reservationId, PDO::PARAM_INT);
    $cancelStmt->execute();
    $_SESSION['alert_message'] = 'Timeslot cancelled.';
    $_SESSION['alert_type'] = 'warning';
    header("Location: admin_dashboard.php?date=$date&facilityID=$facilityIDFilter&status=$statusFilter&page=$page");
    exit();
}

// Check if there are any confirmed or disabled reservations for the selected date
$checkSql = "SELECT COUNT(*) FROM Reservation WHERE rResDate = :date AND (rStatus = 'confirmed' OR rStatus = 'disabled')";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->bindParam(':date', $date);
$checkStmt->execute();
$confirmedCount = $checkStmt->fetchColumn();
$allAvailable = $confirmedCount == 0;

// Check if all time slots are disabled for the selected date
$checkDisabledSql = "SELECT COUNT(*) FROM Reservation WHERE rResDate = :date AND rStatus = 'disabled'";
$checkDisabledStmt = $pdo->prepare($checkDisabledSql);
$checkDisabledStmt->bindParam(':date', $date);
$checkDisabledStmt->execute();
$disabledCount = $checkDisabledStmt->fetchColumn();
$allDisabled = $disabledCount > 0;

// Fetch all time slots for the selected date using PDO
$sql = "SELECT rResID, rStartTime, rEndTime, fFacilityID, rStatus, u.uFName, u.uLName, u.uPhoneNumber 
        FROM Reservation r 
        LEFT JOIN User u ON r.uUserID = u.uUserID
        WHERE rResDate = :date";
if ($facilityIDFilter) {
    $sql .= " AND fFacilityID = :facilityID";
}
if ($statusFilter) {
    $sql .= " AND rStatus = :status";
}
$sql .= " ORDER BY fFacilityID, rStartTime LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':date', $date);
if ($facilityIDFilter) {
    $stmt->bindParam(':facilityID', $facilityIDFilter);
}
if ($statusFilter) {
    $stmt->bindParam(':status', $statusFilter);
}
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->execute();
$timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total number of records for pagination
$sqlCount = "SELECT COUNT(*) FROM Reservation WHERE rResDate = :date";
if ($facilityIDFilter) {
    $sqlCount .= " AND fFacilityID = :facilityID";
}
if ($statusFilter) {
    $sqlCount .= " AND rStatus = :status";
}
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->bindParam(':date', $date);
if ($facilityIDFilter) {
    $stmtCount->bindParam(':facilityID', $facilityIDFilter);
}
if ($statusFilter) {
    $stmtCount->bindParam(':status', $statusFilter);
}
$stmtCount->execute();
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

function formatTime($time) {
    return date("h:i A", strtotime($time)); // Format time as 12-hour format with AM/PM
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <script src="../assets/js/color-modes.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.122.0">
    <title>Admin Dashboard</title>
    <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/dashboard/">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .alert {
            width: 300px;
            margin-top: 10px;
        }
        .active{
            font-weight: bolder;
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
                            <a class="nav-link" href="admin_statistics.php">
                                <span data-feather="home"></span>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="#">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                </div>

                <form method="get" action="admin_dashboard.php" class="mb-3 row g-3 align-items-center">
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" value="<?php echo $date; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <select name="facilityID" class="form-control" onchange="this.form.submit()">
                            <option value="">All Courts</option>
                            <?php
                            // Fetch distinct facility IDs
                            $facilities = $pdo->query("SELECT DISTINCT fFacilityID FROM Facility ORDER BY fFacilityID")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($facilities as $facility) {
                                echo "<option value='{$facility['fFacilityID']}'" . ($facility['fFacilityID'] == $facilityIDFilter ? " selected" : "") . ">Court {$facility['fFacilityID']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?php if ($statusFilter == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                            <option value="available" <?php if ($statusFilter == 'available') echo 'selected'; ?>>Available</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <?php if (isset($_SESSION['alert_message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['alert_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['alert_message']); unset($_SESSION['alert_type']); ?>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="row">
                    <h3>Showing Time Slots for <?php echo date('F d, Y', strtotime($date)); ?></h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Court</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Customer Name</th>
                                <th>Customer Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($timeSlots) > 0): ?>
                                <?php foreach ($timeSlots as $slot): ?>
                                    <tr>
                                        <td><?php echo $slot['fFacilityID']; ?></td>
                                        <td><?php echo formatTime($slot['rStartTime']); ?></td>
                                        <td><?php echo formatTime($slot['rEndTime']); ?></td>
                                        <td><?php echo $slot['rStatus']; ?></td>
                                        <td><?php echo $slot['uFName'] . ' ' . $slot['uLName']; ?></td>
                                        <td><?php echo $slot['uPhoneNumber']; ?></td>
                                        <td>
                                            <?php if ($slot['rStatus'] == 'available'): ?>
                                                <form method="post" action="admin_dashboard.php?page=<?php echo $page; ?>" class="d-inline-block">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $slot['rResID']; ?>">
                                                    <button type="submit" name="add_reservation" class="btn btn-primary btn-sm">Add</button>
                                                </form>
                                            <?php elseif ($slot['rStatus'] == 'confirmed' && $slot['uFName'] == null): ?>
                                                <form method="post" action="admin_dashboard.php?page=<?php echo $page; ?>" class="d-inline-block">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $slot['rResID']; ?>">
                                                    <button type="submit" name="cancel_reservation" class="btn btn-warning btn-sm">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No available time slots for the selected date.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?date=<?php echo $date; ?>&facilityID=<?php echo $facilityIDFilter; ?>&status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2"></script>
    <script src="../assets/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.31.1/feather.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', (event) => {
            setTimeout(() => {
                const alert = document.querySelector('.alert-dismissible');
                if (alert) {
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(() => {
                        alert.remove();
                    }, 150); // 150ms matches the Bootstrap fade transition duration
                }
            }, 2000); // 2000ms = 2 seconds
        });
    </script>
</body>
</html>
