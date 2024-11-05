    <?php
    date_default_timezone_set('Asia/Manila');

    session_start();
    if (!isset($_SESSION['userId'])) {
        header("Location: index.php");
        exit();
    }

    // Include the database connection
    require 'connection_admin.php';

    // Fetch daily sales data
    $dailySales = [];
$sql = "SELECT period, SUM(totalSales) as totalSales
        FROM (
            SELECT DATE_FORMAT(pTransDate, '%Y-%m-%d') as period, SUM(pAmount) as totalSales 
            FROM (
                SELECT DISTINCT pTransDate, uUserID, pAmount
                FROM PaymentTransaction
            ) AS pt
            GROUP BY DATE_FORMAT(pTransDate, '%Y-%m-%d') 

            UNION ALL

            SELECT DATE(rResDate) as period, COUNT(*) * 200 as totalSales
            FROM Reservation
            WHERE rStatus = 'confirmed' AND uUserID IS NULL
            GROUP BY DATE(rResDate)
        ) AS combined_sales
        GROUP BY period
        ORDER BY period";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dailyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dailyResults as $result) {
        $dailySales[] = [
            'period' => $result['period'],
            'totalSales' => floatval($result['totalSales'])
        ];
    }

    // Fetch weekly sales data (summed per week)
    $weeklySales = [];
    $sql = "SELECT DATE_FORMAT(pTransDate, '%X-%V') as week, SUM(pAmount) as totalSales 
            FROM (
                SELECT DISTINCT pTransDate, uUserID, pAmount
                FROM PaymentTransaction
            ) AS pt
            GROUP BY DATE_FORMAT(pTransDate, '%X-%V') 
            ORDER BY DATE_FORMAT(pTransDate, '%X-%V')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $weeklyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeklyResults as $result) {
        $yearWeek = $result['week'];
        $weekNumber = substr($yearWeek, -2); // Extract week number from YEAR-WEEK format
        $weeklySales[] = [
            'period' => 'Week ' . $weekNumber,
            'totalSales' => floatval($result['totalSales'])
        ];
    }

    // Fetch monthly sales data (summed per month)
    $monthlySales = [];
    $sql = "SELECT DATE_FORMAT(pTransDate, '%Y-%m') as month, SUM(pAmount) as totalSales 
            FROM (
                SELECT DISTINCT pTransDate, uUserID, pAmount
                FROM PaymentTransaction
            ) AS pt
            GROUP BY DATE_FORMAT(pTransDate, '%Y-%m') 
            ORDER BY DATE_FORMAT(pTransDate, '%Y-%m')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $monthlyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monthlyResults as $result) {
        $monthlySales[] = [
            'period' => $result['month'],
            'totalSales' => floatval($result['totalSales'])
        ];
    }

    // Ensure all periods (daily, weekly, monthly) have data, even if empty
    if (empty($dailySales)) {
        $dailySales[] = ['period' => date('Y-m-d'), 'totalSales' => 0];
    }
    if (empty($weeklySales)) {
        $weeklySales[] = ['period' => 'Week ' . date('W'), 'totalSales' => 0];
    }
    if (empty($monthlySales)) {
        $monthlySales[] = ['period' => date('Y-m'), 'totalSales' => 0];
    }

    // Convert PHP arrays to JSON for JavaScript consumption
    $dailySalesJson = json_encode($dailySales);
    $weeklySalesJson = json_encode($weeklySales);
    $monthlySalesJson = json_encode($monthlySales);

    ?>


    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Sales Report</title>
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
                                <a class="nav-link" href="admin_statistics.php">
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
                                <a class="nav-link active" aria-current="page" href="admin_sales.php">
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
                        <h1 class="h2">Sales Report</h1>
                        <div>
                            <button id="dailyButton" class="btn btn-primary">Daily</button>
                            <button id="weeklyButton" class="btn btn-secondary">Weekly</button>
                            <button id="monthlyButton" class="btn btn-info">Monthly</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h3>Total Sales: ₱<span id="total-sales">0.00</span></h3>
                    </div>
                    <canvas id="salesChart"></canvas>

                    <script>
        // Sales data from PHP
        const dailySales = <?php echo $dailySalesJson; ?>;
        const weeklySales = <?php echo $weeklySalesJson; ?>;
        const monthlySales = <?php echo $monthlySalesJson; ?>;

        // Function to calculate total sales
        function calculateTotalSales(data) {
            return data.reduce((total, item) => total + parseFloat(item.totalSales), 0);
        }

        // Initial chart data (default to daily)
        let chartData = dailySales.map(item => ({
            label: item.period,
            sales: item.totalSales
        }));

        // Update total sales
        document.getElementById('total-sales').innerText = calculateTotalSales(dailySales).toFixed(2);

        // Function to update chart data
        function updateChart(data) {
            salesChart.data.labels = data.map(item => item.period);
            salesChart.data.datasets[0].data = data.map(item => item.totalSales);
            salesChart.update();
        }

        // Chart initialization
        const ctx = document.getElementById('salesChart').getContext('2d');
        let salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(item => item.label),
                datasets: [{
                    label: 'Sales (₱)',
                    data: chartData.map(item => item.sales),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });

        // Event listeners for buttons
        document.getElementById('dailyButton').addEventListener('click', () => {
            updateChart(dailySales);
            document.getElementById('total-sales').innerText = calculateTotalSales(dailySales).toFixed(2);
        });

        document.getElementById('weeklyButton').addEventListener('click', () => {
            updateChart(weeklySales);
            document.getElementById('total-sales').innerText = calculateTotalSales(weeklySales).toFixed(2);
        });

        document.getElementById('monthlyButton').addEventListener('click', () => {
            updateChart(monthlySales);
            document.getElementById('total-sales').innerText = calculateTotalSales(monthlySales).toFixed(2);
        });
    </script>

                </main>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
