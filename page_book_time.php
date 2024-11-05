<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['date'])) {
    header("Location: page_book_date.php");
    exit();
}

$date = $_GET['date'];
function formatTime($time) {
    return date("h:i A", strtotime($time)); // Format time as 12-hour format with AM/PM
}

// Include the database connection
require 'connection_customer.php';

// Fetch available time slots using PDO
$sql = "SELECT rStartTime, rEndTime, fFacilityID FROM Reservation 
        WHERE rResDate = :date AND rStatus = 'available' ORDER BY fFacilityID, rStartTime";
$stmt = $pdo->prepare($sql);
$stmt->execute(['date' => $date]);
$timeSlots = $stmt->fetchAll();

// Organize time slots by facility ID
$courtAvailability = [];
foreach ($timeSlots as $slot) {
    $facilityID = $slot['fFacilityID'];
    $courtAvailability[$facilityID][] = [
        'start' => $slot['rStartTime'],
        'end' => $slot['rEndTime']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Time - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cover-container {
            max-width: 1000px;
            padding: 40px;
            margin: 50px auto; /* Center the cover-container */
        }
        body {
            background-image: url('bgimg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }
        .time-slot {
            margin-bottom: 10px;
        }
        .carousel {
            background-color: rgba(0, 0, 0, .1);
            border-radius: 10px;
            border-width: 1px 0;
            max-width: 900px;
            height: 410px;
        }
        .carousel-inner {
            padding: 20px;
        }
        .text-center, form h3, form h4 {
            color: #013220;
        }
        .carousel-control-prev, .carousel-control-next {
            color: #013220;
        }
        .btn-primary {
            background-color: green;
            border-color: green;
        }
        .btn-primary:hover {
            background-color: #013220;
            border-color: #013220;
        }
        .btn-outline-success {
            color: green;
            border-color: green;
        }
        .btn-outline-success:hover {
            background-color: green;
            color: white;
            border-color: green;
        }
        .alert {
            max-width: 900px;
            margin: 20px auto;
        }
        .alert ul li {
            text-align: left;
            padding: 5px;
        }
    </style>
</head>
<body class="d-flex h-100 text-center text-bg-dark">    
    <div class="cover-container">
        <main class="px-3">
            <?php if (count($courtAvailability) > 0): ?>
                <form action="page_reservation_details.php" method="post">
                    <input type="hidden" name="userID" value="<?php echo $_SESSION['userId']; ?>">
                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                    <input type="hidden" id="selectedSlots" name="selectedSlots" value="">
                    <h4>Showing available courts for <?php echo date('F d, Y', strtotime($date)); ?></h4>
                    <div id="courtCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="carousel-item <?php echo ($i === 1) ? 'active' : ''; ?>">
                                    <div class="row justify-content-center">
                                        <div class="col-md-6">
                                            <h3 class="text-center">Court <?php echo $i; ?></h3>
                                            <?php if (!empty($courtAvailability[$i])): ?>
                                                <?php foreach ($courtAvailability[$i] as $slot): ?>
                                                    <button type="button" class="btn btn-outline-success btn-block time-slot" onclick="toggleTimeSlot(this, '<?php echo $i . ',' . $slot['start'] . '-' . $slot['end']; ?>')">
                                                        <?php echo formatTime($slot['start']) . ' to ' . formatTime($slot['end']); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-center">No available slots</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#courtCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#courtCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                    <a href="page_book_date.php" class="btn btn-primary mt-3">Back</a>
                    <input type="submit" id="nextButton" class="btn btn-primary mt-3" value="Next" disabled>
                </form>
                <div class="alert alert-info" role="alert">
                    <ul>
                        <li>Court rental rate is 200 PHP / hour.</li>
                        <li>Online Reservations can only be made at LEAST 24 hours and at MOST 7 days before the intended reservation.</li>
                        <li>Customers must prepay court reservation to secure their slot. Rental starts based on the paid reservation time.</li>
                        <li>Paid reservations are non-refundable.</li>
                        <li>Notice of late arrivals will not be honored.</li>
                    </ul>
                </div>
            <?php else: ?>
                <p class="lead">No available time slots for the selected date.</p>
                <a href="page_book_date.php" class="btn btn-lg btn-green fw-bold">Back</a>
            <?php endif; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectedSlots = [];
            var nextButton = document.getElementById('nextButton');

            window.toggleTimeSlot = function(button, slot) {
                button.classList.toggle('btn-outline-success');
                button.classList.toggle('btn-success');

                if (button.classList.contains('btn-success')) {
                    selectedSlots.push(slot);
                } else {
                    selectedSlots = selectedSlots.filter(function(item) {
                        return item !== slot;
                    });
                }

                document.getElementById('selectedSlots').value = selectedSlots.join('|');
                nextButton.disabled = selectedSlots.length === 0;
            };
        });
    </script>
</body>
</html>
