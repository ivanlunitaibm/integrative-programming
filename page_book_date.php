<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}
?>

<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Date - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .cover-container {
            max-width: 1000px;
            height: 50%;
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
        .px-3 h1 {
            color: black;
        }
        .citywalk {
            color: green;
        }
        .lead {
            color: black;
        }
        footer {
            color: black;
        }
        footer a {
            color: green;
        }
        .btn {
            background-color: green;
            color: white;
        }
        .btn:hover {
            background-color: #013220;
            color: white;
        }
        form {
            padding: 20px;
        }
        .flatpickr-input {
            max-width: 310px;
            margin: 0 auto;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
            max-width: 400px;
            margin: 0px auto;
        }
    </style>
</head>
<body class="d-flex h-100 text-center text-bg-dark">    
    <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <main class="px-3">
            <h1>Select Date</h1>
            <div id="infoAlert" class="alert alert-info" role="alert">
                Note: Same day bookings are not allowed. Customers can only book 7 days ahead.
            </div>
            <form id="bookingForm" action="page_book_time.php" method="get">
                <div class="mb-3">
                    <input type="text" class="form-control flatpickr-input" id="date" name="date" required>
                </div>
                <a href="cust_landingpage.php" class="btn btn-lg btn-green fw-bold">Back</a>
                <button type="submit" class="btn btn-lg btn-green fw-bold">Next</button>
                <div id="alertContainer" class="mt-3"></div>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- Include Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('#date', {
                dateFormat: 'Y-m-d',
                minDate: new Date().fp_incr(1), // Start from tomorrow
                maxDate: new Date().fp_incr(7), // 7 days from today
                defaultDate: new Date().fp_incr(1), // Default date is tomorrow
                onReady: function(selectedDates, dateStr, instance) {
                    instance.open();
                }
            });

            document.getElementById('bookingForm').addEventListener('submit', function(event) {
                const selectedDate = document.getElementById('date').value;
                const selectedDateObj = new Date(selectedDate);
                selectedDateObj.setHours(0, 0, 0, 0); // Normalize time to midnight for comparison

                const currentDate = new Date();
                currentDate.setHours(0, 0, 0, 0); // Normalize time to midnight for comparison

                const maxDate = new Date(currentDate);
                maxDate.setDate(currentDate.getDate() + 7);

                if (selectedDateObj <= currentDate || selectedDateObj > maxDate) {
                    event.preventDefault();
                    showAlert('Selected date must be from tomorrow up to 7 days in advance.', 'danger');
                }
            });

            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                alertContainer.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
