<?php
// Autoload Composer
require_once __DIR__ . '/vendor/autoload.php';

// Import necessary classes
use repositories\ValidJobOfferRepository;

// Global constants
const DIR_PROJECT = __DIR__;
const DIR_DATABASE = DIR_PROJECT . "/database";
const DIR_PUBLIC = DIR_PROJECT . "/public";

// Data structures
$jobOfferRepository = new ValidJobOfferRepository();
$jobOfferRepository->getLatestOffers();

// Showing off the contents
include_once(DIR_PUBLIC . "/views/jobOffersBulletin.php");
