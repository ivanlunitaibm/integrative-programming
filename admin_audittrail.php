<?php
session_start();

// Redirect if session variables are not set
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require 'connection_admin.php';

// Fetch filters
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchUser = isset($_GET['search_user']) ? $_GET['search_user'] : '';

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Function to convert date format from dd/mm/yyyy to yyyy-mm-dd
function convertDateFormat($date) {
    $dateArray = explode('/', $date);
    if (count($dateArray) == 3) {
        return $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
    }
    return $date;
}

// Convert dates if they are set and add 1 day to end date only once
if ($startDate) {
    $startDate = convertDateFormat($startDate);
}
if ($endDate && !isset($_GET['page'])) { // Add 1 day only if it's the initial page load
    $endDate = convertDateFormat($endDate);
    $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
}

// Fetch audit trail data with filters
try {
    $auditSql = "SELECT au.aAudID, au.aAction, au.aTimestamp, au.aUserID, u.uFName, u.uLName, u.uEmail
                 FROM AuditUser au
                 JOIN User u ON au.aUserID = u.uUserID
                 WHERE (:actionFilter = '' OR au.aAction = :actionFilter)
                 AND (:startDate = '' OR au.aTimestamp >= :startDate)
                 AND (:endDate = '' OR au.aTimestamp <= :endDate)
                 AND (:searchUser = '' OR u.uFName LIKE :searchUser OR u.uLName LIKE :searchUser OR u.uEmail LIKE :searchUser)
                 ORDER BY au.aTimestamp DESC
                 LIMIT :offset, :recordsPerPage";
    $auditStmt = $pdo->prepare($auditSql);
    $searchUserParam = '%' . $searchUser . '%';
    $auditStmt->bindParam(':actionFilter', $actionFilter);
    $auditStmt->bindParam(':startDate', $startDate);
    $auditStmt->bindParam(':endDate', $endDate);
    $auditStmt->bindParam(':searchUser', $searchUserParam);
    $auditStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $auditStmt->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
    $auditStmt->execute();
    $auditRecords = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of records for pagination
    $totalSql = "SELECT COUNT(*) FROM AuditUser au
                 JOIN User u ON au.aUserID = u.uUserID
                 WHERE (:actionFilter = '' OR au.aAction = :actionFilter)
                 AND (:startDate = '' OR au.aTimestamp >= :startDate)
                 AND (:endDate = '' OR au.aTimestamp <= :endDate)
                 AND (:searchUser = '' OR u.uFName LIKE :searchUser OR u.uLName LIKE :searchUser OR u.uEmail LIKE :searchUser)";
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->bindParam(':actionFilter', $actionFilter);
    $totalStmt->bindParam(':startDate', $startDate);
    $totalStmt->bindParam(':endDate', $endDate);
    $totalStmt->bindParam(':searchUser', $searchUserParam);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

function formatDateTime($dateTime) {
    return date('F d, Y h:i A', strtotime($dateTime));
}
// Define maximum number of pagination links to display
$maxPagesToShow = 5; // Adjust this value as needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Audit Trail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pagination .page-link {
            color: green;
        }
        .pagination .page-item.active .page-link {
            background-color: white;
            color: green;
            border-color: green;
        }
        .pagination .page-link:hover {
            background-color: white;
            color: green;
            border-color: green;
        }
        .table {
            background-color: white;
            color: black;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .table th, .table td {
            color: black;
            border-color: rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: green;
            color: white;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-container h1 {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
    <div class="header-container mb-4">
            <h1>Customer Activity Report</h1>
            <a href="admin_dashboard.php" class="btn btn-success">Back to Dashboard</a>
        </div>
        <form method="GET" action="admin_audittrail.php" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">All</option>
                        <option value="Confirmed transaction" <?php if ($actionFilter == 'create') echo 'selected'; ?>>Confirmed transaction</option>
                        <option value="Cancelled transaction" <?php if ($actionFilter == 'update') echo 'selected'; ?>>Cancelled transaction</option>
                        <option value="Logged in" <?php if ($actionFilter == 'login') echo 'selected'; ?>>Logged in</option>
                        <option value="Logged out" <?php if ($actionFilter == 'logout') echo 'selected'; ?>>Logged out</option>
                        <option value="Log in attempt failed" <?php if ($actionFilter == 'delete') echo 'selected'; ?>>Log in attempt failed</option>
                        <option value="Updated Profile" <?php if ($actionFilter == 'delete') echo 'selected'; ?>>Updated Profile</option>
                        <option value="Account created" <?php if ($actionFilter == 'delete') echo 'selected'; ?>>Account created</option>
                        <!-- Add more actions as needed -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="search_user" class="form-label">Search User</label>
                    <input type="text" class="form-control" id="search_user" name="search_user" value="<?php echo htmlspecialchars($searchUser); ?>" placeholder="Search by email">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="admin_audittrail.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <?php if (!empty($auditRecords)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Record Number</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                            <th>User Name</th>
                            <th>User Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recordNumber = 1 + $offset;
                        foreach ($auditRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recordNumber++); ?></td>
                                <td><?php echo htmlspecialchars($record['aAction']); ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($record['aTimestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($record['uFName'] . ' ' . $record['uLName']); ?></td>
                                <td><?php echo htmlspecialchars($record['uEmail']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($actionFilter); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&search_user=<?php echo urlencode($searchUser); ?>">Previous</a></li>
                    <?php endif; ?>
                    
                    <?php
                    // Logic to limit the number of pagination links displayed
                    $startPage = max($page - floor($maxPagesToShow / 2), 1);
                    $endPage = min($startPage + $maxPagesToShow - 1, $totalPages);

                    for ($i = $startPage; $i <= $endPage; $i++) {
                        ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&action=<?php echo urlencode($actionFilter); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&search_user=<?php echo urlencode($searchUser); ?>"><?php echo $i; ?></a></li>
                        <?php
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($actionFilter); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&search_user=<?php echo urlencode($searchUser); ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                No audit records found.
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
