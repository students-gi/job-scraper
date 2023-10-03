<?php
// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// Import necessary classes
use services\BlacklistService;

// Global constants
const DIR_PROJECT = __DIR__;
const DIR_DATABASE = DIR_PROJECT . "/database";
const DIR_PUBLIC = DIR_PROJECT . "/public";

// Data structures
$jobOfferRepository = new BlacklistService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Show off the scraped job offers
    include_once(DIR_PUBLIC . "/views/jobOffersBulletin.php");
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'blacklist' && isset($_POST['jobOfferId'])) {
        // Call the addJobOfferToBlacklist method with the job offer ID
        $jobOfferId = (string) $_POST['jobOfferId'];
        $response = $jobOfferRepository->addJobOfferToBlacklist($jobOfferId);

        http_response_code($response ? 200 : 406);
    } else {
        // Invalid action or missing parameters
        http_response_code(400);
    }
} else {
    // Invalid request method
    http_response_code(405);
}
