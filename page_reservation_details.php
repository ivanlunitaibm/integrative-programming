<?php
session_start();
if (!isset($_SESSION['userId']) || !isset($_POST['selectedSlots']) || !isset($_POST['date'])) {
    header("Location: index.php");
    exit();
}

require 'connection_customer.php';

$userID = $_SESSION['userId'];
$date = $_POST['date'];
$selectedSlots = explode('|', $_POST['selectedSlots']);

// Fetch user information
$sql = "SELECT uFName, uLName FROM User WHERE uUserID = :userID";
$stmt = $pdo->prepare($sql);
$stmt->execute(['userID' => $userID]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

// Calculate total payment
$totalPayment = 0;
foreach ($selectedSlots as $slot) {
    list($facilityID, $timeRange) = explode(',', $slot);
    $sql = "SELECT fPricePerHour FROM Facility WHERE fFacilityID = :facilityID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['facilityID' => $facilityID]);
    $facility = $stmt->fetch();

    if ($facility) {
        list($startTime, $endTime) = explode('-', $timeRange);
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $hours = ($endTime - $startTime) / 3600;
        $totalPayment += $hours * $facility['fPricePerHour'];
    }
}

// Function to format time to 12-hour format with AM/PM
function formatTime($time) {
    return date("h:i A", strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            background-image: url('bgimg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .cover-container {
            max-width: 1000px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.8); /* Add a background color to make text readable */
            border-radius: 10px;
            margin-top: 30px; /* Adjust to move the container upwards */
        }
        .details-table th, .details-table td {
            color: black;
        }
        .btn-primary:hover {
            background-color: #007DFE;
            border-color: #007DFE;
        }
        .btn-primary:hover {
            background-color: #00008b;
            border-color: #00008b;
        }
        .btn-success:hover {
            background-color: #023020;
            border-color: #023020;
        }
    </style>
</head>
<body class="d-flex justify-content-center">
    <div class="cover-container">
        <main class="px-3">
            <h3>Reservation Details</h3>
            <table class="table table-striped details-table">
                <tr>
                    <th>Name</th>
                    <td><?php echo htmlspecialchars($user['uFName'] . ' ' . $user['uLName']); ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?php echo htmlspecialchars(date('F d, Y', strtotime($date))); ?></td>
                </tr>
                <tr>
                    <th>Time Slots</th>
                    <td>
                        <?php foreach ($selectedSlots as $slot): ?>
                            <?php
                            list($facilityID, $timeRange) = explode(',', $slot);
                            list($startTime, $endTime) = explode('-', $timeRange);
                            echo "Court $facilityID: " . htmlspecialchars(formatTime($startTime) . ' to ' . formatTime($endTime)) . "<br>";
                            ?>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th>Total Payment</th>
                    <td><?php echo htmlspecialchars('₱' . number_format($totalPayment, 2)); ?></td>
                </tr>
            </table>
            <form id="paymentForm" action="page_gcash.php" method="post">
                <input type="hidden" name="userID" value="<?php echo $_SESSION['userId']; ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <input type="hidden" name="selectedSlots" value="<?php echo htmlspecialchars(implode('|', $selectedSlots)); ?>">
                <input type="hidden" name="totalPayment" value="<?php echo $totalPayment; ?>">

                <button type="submit" class="btn btn-success" onclick="setPaymentMethod('page_maya.php')">Pay with MAYA</button>
                <button type="submit" class="btn btn-primary" onclick="setPaymentMethod('page_gcash.php')">Pay with GCash</button>
                <a href="process_reservation_cancel.php" class="btn btn-secondary">Cancel</a>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setPaymentMethod(action) {
            document.getElementById('paymentForm').action = action;
        }
    </script>
</body>
</html>
