<?php

use repositories\JobOfferRepository;
use repositories\JobOfferBlacklistRepository;
use scrapers\ScraperManager;

function runBackgroundTasks(ScraperManager $scraperManager, JobOfferRepository $jobOfferRepository)
{
    // Refresh the new job offers daily
    $newJobOffers = $scraperManager->launchScrapers();
    foreach ($newJobOffers as $jobOffer) {
        $jobOfferRepository->addJobOffer($jobOffer);
    }

    $jobOfferRepository->removeExpiredJobOffers();

    // Run saveToCsv() once every 3 days
    //$jobOfferRepository->saveToCsv();
}
