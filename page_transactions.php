<?php
session_start();

// Redirect if session variables are not set
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require 'connection_customer.php';

$userID = $_SESSION['userId'];
$transactions = [];

// Pagination setup
$recordsPerPage = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Fetch user transactions with pagination
try {
    $sql = "SELECT pt.pTransID, pt.pTransDate, pt.rResID, r.rResDate, r.rStartTime, r.rEndTime, r.fFacilityID, pt.pAmount
            FROM PaymentTransaction pt
            JOIN Reservation r ON pt.rResID = r.rResID
            WHERE pt.uUserID = :userID
            ORDER BY pt.pTransDate DESC
            LIMIT :offset, :recordsPerPage";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of transactions for pagination
    $totalSql = "SELECT COUNT(*) FROM PaymentTransaction WHERE uUserID = :userID";
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Group transactions by transaction date, reservation date, and user ID
$groupedTransactions = [];
foreach ($transactions as $transaction) {
    $key = $transaction['pTransDate'] . '|' . $transaction['rResDate'];
    if (!isset($groupedTransactions[$key])) {
        $groupedTransactions[$key] = [
            'pTransIDs' => [],
            'pTransDate' => $transaction['pTransDate'],
            'rResDate' => $transaction['rResDate'],
            'pAmount' => $transaction['pAmount'],
            'slots' => []
        ];
    }
    $groupedTransactions[$key]['pTransIDs'][] = $transaction['pTransID'];
    $groupedTransactions[$key]['slots'][] = [
        'fFacilityID' => $transaction['fFacilityID'],
        'rStartTime' => $transaction['rStartTime'],
        'rEndTime' => $transaction['rEndTime']
    ];
}

// Function to format date and time
function formatDateTime($dateTime) {
    return date('F d, Y h:i A', strtotime($dateTime));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Function to get the first transaction ID
function getFirstTransactionID($transactionIDs) {
    sort($transactionIDs, SORT_NUMERIC);
    return 'TXNID-' . $transactionIDs[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            color: white;
        }
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
        h1 {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="header-container mb-4">
            <h1>Recent Transactions</h1>
            <a href="cust_landingpage.php" class="btn btn-success">Back to Dashboard</a>
        </div>
        <?php if (!empty($groupedTransactions)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Transaction Date</th>
                            <th>Reservation Date</th>
                            <th>Court Timeslots</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedTransactions as $key => $group): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(getFirstTransactionID($group['pTransIDs'])); ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($group['pTransDate'])); ?></td>
                                <td><?php echo htmlspecialchars($group['rResDate']); ?></td>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($group['slots'] as $slot): ?>
                                            <li>Court <?php echo htmlspecialchars($slot['fFacilityID']); ?>: <?php echo htmlspecialchars(formatTime($slot['rStartTime']) . ' - ' . formatTime($slot['rEndTime'])); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>₱<?php echo htmlspecialchars(number_format($group['pAmount'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                No transactions found.
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
