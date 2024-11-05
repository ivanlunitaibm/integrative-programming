<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if session variable is not set
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

// Store the original URL parameters in session if they're present
if (isset($_GET['title']) && isset($_GET['date']) && isset($_GET['time'])) {
    $_SESSION['calendar_event'] = [
        'title' => $_GET['title'],
        'date' => $_GET['date'],
        'time' => $_GET['time']
    ];
}

// Set up Google client
$client = new Client();
$client->setApplicationName('Courtlify');
$client->setScopes(Calendar::CALENDAR_EVENTS);
$client->setAuthConfig('credentials.json');
$client->setAccessType('offline');
$client->setPrompt('consent'); // Force to show consent screen
$client->setRedirectUri('http://localhost/courtlifyy/add_to_calendar.php'); // Set absolute redirect URI

$credentialsPath = 'token.json';

// Check if we have an authorization code
if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for an access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception('Error fetching access token: ' . $token['error_description']);
        }
        
        $client->setAccessToken($token);
        
        // Store the token for future use
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        
        // Retrieve stored event details from session
        if (isset($_SESSION['calendar_event'])) {
            $eventDetails = $_SESSION['calendar_event'];
            $title = $eventDetails['title'];
            $date = $eventDetails['date'];
            $time = $eventDetails['time'];
            
            // Create and insert the calendar event
            $timeRanges = explode('-', $time);
            $eventStart = date('Y-m-d\TH:i:s', strtotime("$date {$timeRanges[0]}"));
            $eventEnd = date('Y-m-d\TH:i:s', strtotime("$date {$timeRanges[1]}"));
            
            $event = new Google\Service\Calendar\Event([
                'summary' => $title,
                'start' => [
                    'dateTime' => $eventStart,
                    'timeZone' => 'Asia/Manila',
                ],
                'end' => [
                    'dateTime' => $eventEnd,
                    'timeZone' => 'Asia/Manila',
                ],
            ]);
            
            $service = new Calendar($client);
            $event = $service->events->insert('primary', $event);
            
            // Clear the stored event details
            unset($_SESSION['calendar_event']);
            
            header("Location: success_page.php");
            exit();
        }
    } catch (Exception $e) {
        // Log the error and redirect with error message
        error_log('Google Calendar Error: ' . $e->getMessage());
        header("Location: cust_landingpage.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} elseif (!isset($_GET['code'])) {
    // No authorization code present, start the auth flow
    if (!file_exists($credentialsPath) || 
        !($client->setAccessToken(json_decode(file_get_contents($credentialsPath), true)))) {
        // Generate the authorization URL
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit();
    }
}

// If we reach here, something went wrong
header("Location: cust_landingpage.php?status=error&message=Authorization%20failed");
exit();
?>