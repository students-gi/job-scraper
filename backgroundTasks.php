<?php
// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// Import necessary classes
use repositories\JobOfferRepository;
use repositories\JobOfferBlacklistRepository;
use scrapers\ScraperManager;

// Global constants
const DIR_PROJECT = __DIR__;
const DIR_DATABASE = DIR_PROJECT . "/database";

// Initialize the functional parts
$scraperManager = new ScraperManager;
$jobOfferRepository = new JobOfferRepository;

// Refresh the new job offers daily
$newJobOffers = $scraperManager->launchScrapers();
foreach ($newJobOffers as $jobOffer) {
    $jobOfferRepository->addJobOffer($jobOffer);
}

// Do some simple filtering
$jobOfferBlacklistRepo = new JobOfferBlacklistRepository;
$jobOfferRepository->filterBlacklistedJobOffers($jobOfferBlacklistRepo);
$jobOfferRepository->removeExpiredJobOffers();

// Save the updated database
$jobOfferRepository->saveToCsv();
