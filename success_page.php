<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Added</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .card-clickable {
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card text-center card-clickable" style="width: 24rem;" onclick="redirectToLandingPage()">
        <img src="logowide.png" class="card-img-top" alt="Success">
        <div class="card-body">
            <h5 class="card-title text-success">Successfully Added to Calendar!</h5>
            <p class="card-text">You will be redirected in <span id="countdown">5</span> seconds.</p>
        </div>
    </div>
</div>

<!-- JavaScript for countdown and redirect -->
<script>
    let countdown = 5;
    const countdownElement = document.getElementById("countdown");

    function redirectToLandingPage() {
        window.location.href = "cust_landingpage.php";
    }

    const countdownInterval = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;
        if (countdown === 0) {
            clearInterval(countdownInterval);
            redirectToLandingPage();
        }
    }, 1000);
</script>

<!-- Optional JavaScript and Bootstrap Bundle -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
