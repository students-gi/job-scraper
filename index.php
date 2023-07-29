<?php
// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// Import necessary classes
use repositories\JobOfferRepository;
use scrapers\ScraperManager;

// Global constants
const DIR_PROJECT = __DIR__;
const DIR_DATABASE = DIR_PROJECT . "/database";
const DIR_PUBLIC = DIR_PROJECT . "/public";

// Data structures
$jobOfferRepository = new JobOfferRepository();
//$jobOfferRepository->readFromLatestCsv();

// Gonna launch the "background tasks"
$jobScraperManager = new ScraperManager();
include_once(DIR_PROJECT . "/backgroundTasks.php");
runBackgroundTasks($jobScraperManager, $jobOfferRepository);
//*/

// Showing off the contents
include_once(DIR_PUBLIC . "/views/jobOffersBulletin.php");
