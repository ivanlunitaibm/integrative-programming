<?php
session_start();

// Redirect if session variables or POST data are not set
if (!isset($_SESSION['userId']) || !isset($_POST['selectedSlots']) || !isset($_POST['date']) || !isset($_POST['totalPayment'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require 'connection_customer.php';

$userID = $_SESSION['userId'];
$date = $_POST['date'];
$selectedSlots = explode('|', $_POST['selectedSlots']);
$totalPayment = $_POST['totalPayment'];

// Function to format time to 12-hour format with AM/PM
function formatTime($time) {
    return date("h:i A", strtotime($time));
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if the selected slots are still available
    foreach ($selectedSlots as $slot) {
        list($facilityID, $timeRange) = explode(',', $slot);
        list($startTime, $endTime) = explode('-', $timeRange);

        // Check availability
        $sql = "SELECT rResID FROM Reservation
                WHERE fFacilityID = :facilityID AND rStartTime = :startTime AND rEndTime = :endTime AND rResDate = :date AND rStatus = 'available'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'facilityID' => $facilityID,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'date' => $date
        ]);
        $availableSlot = $stmt->fetchColumn();

        if (!$availableSlot) {
            // If any slot is not available, rollback and show the modal
            $pdo->rollBack();
            $slotUnavailable = true;
            break;
        }
    }

    if (!isset($slotUnavailable)) {
        // Initialize an array to store reservation IDs
        $reservationIDs = [];
        $transactionIDs = [];

        // Update reservation records and retrieve reservation IDs
        foreach ($selectedSlots as $slot) {
            list($facilityID, $timeRange) = explode(',', $slot);
            list($startTime, $endTime) = explode('-', $timeRange);

            // Update reservation
            $sql = "UPDATE Reservation SET uUserID = :userID, rStatus = 'confirmed'
                    WHERE fFacilityID = :facilityID AND rStartTime = :startTime AND rEndTime = :endTime AND rResDate = :date";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'userID' => $userID,
                'facilityID' => $facilityID,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'date' => $date
            ]);

            // Retrieve reservation ID
            $sql = "SELECT rResID FROM Reservation
                    WHERE fFacilityID = :facilityID AND rStartTime = :startTime AND rEndTime = :endTime AND rResDate = :date AND uUserID = :userID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'facilityID' => $facilityID,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'date' => $date,
                'userID' => $userID
            ]);
            $reservationID = $stmt->fetchColumn();

            // Add reservation ID to the array
            if ($reservationID) {
                $reservationIDs[] = $reservationID;
            } else {
                throw new Exception("Failed to retrieve reservation ID.");
            }
        }

        // Insert records into PaymentTransaction table and fetch the last inserted transaction ID
        foreach ($reservationIDs as $reservationID) {
            $sql = "INSERT INTO PaymentTransaction (pAmount, pTransDate, pStatus, uUserID, rResID)
                    VALUES (:amount, NOW(), 'completed', :userID, :reservationID)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'amount' => $totalPayment,
                'userID' => $userID,
                'reservationID' => $reservationID
            ]);

            // Fetch the last inserted transaction ID
            $lastInsertID = $pdo->lastInsertId();
            if ($lastInsertID) {
                $transactionIDs[] = $lastInsertID;
            } else {
                throw new Exception("Failed to retrieve transaction ID.");
            }
        }

        // Insert record into AuditUser table
        $sql = "INSERT INTO AuditUser (aAction, aUserID) VALUES (:action, :userID)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'action' => 'Confirmed transaction',
            'userID' => $userID
        ]);

        // Commit transaction
        $pdo->commit();

        // Fetch user information for the receipt
        $sql = "SELECT uFName, uLName FROM User WHERE uUserID = :userID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userID' => $userID]);
        $user = $stmt->fetch();
    }
} catch (Exception $e) {
    // Roll back transaction and display error message
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
    exit();
}

if (isset($slotUnavailable)): ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Error - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade show" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" style="display: block;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                </div>
                <div class="modal-body">
                    <p>Timeslot/s chosen was already taken.</p>
                </div>
                <div class="modal-footer">
                    <a href="page_book_time.php" class="btn btn-primary">Go Back to Booking</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Transaction Receipt</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Transaction ID:</strong> TXNID-<?php echo $transactionIDs[0]; ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['uFName'] . ' ' . $user['uLName']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars(date('F d, Y', strtotime($date))); ?></p>
                    <p><strong>Time Slots:</strong></p>
                    <ul>
                    <?php foreach ($selectedSlots as $slot): ?>
                        <?php
                        list($facilityID, $timeRange) = explode(',', $slot);
                        list($startTime, $endTime) = explode('-', $timeRange);
                        ?>
                        <li>Court <?php echo $facilityID; ?>: <?php echo htmlspecialchars(formatTime($startTime) . ' to ' . formatTime($endTime)); ?></li>
                    <?php endforeach; ?>
                    </ul>
                    <p><strong>Total Payment:</strong> ₱<?php echo number_format($totalPayment, 2); ?></p>
                </div>
                <div class="modal-footer">
                    <a href="cust_landingpage.php" class="btn btn-primary">Close</a>
                    <a href="add_to_calendar.php?title=Court%20Booking&date=<?php echo urlencode($date); ?>&time=<?php echo urlencode(implode(', ', array_map(function($slot) {
                        list(, $timeRange) = explode(',', $slot);
                        return $timeRange;
                    }, $selectedSlots))); ?>" class="btn btn-secondary">Add to Calendar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the receipt modal on page load
        window.onload = function() {
            var receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
        };
    </script>
</body>
</html>
<?php endif; ?>
