<?php

namespace repositories;

use scrapers\ScraperManager;

class ValidJobOfferRepository extends AbstractJobOfferRepository
{
    private const CSV_FILENAME_BASE = "job_offers-";

    // Filling in the repo with stuff
    public function __construct()
    {
        parent::__construct();

        // Load data
        if (!$this->importFromCsv()) {
            // There was no existing database, gotta build a new one
            $offerScraper = new ScraperManager;
            foreach (($offerScraper->launchScrapers()) as $offer) {
                $this->addJobOffer($offer);
            }

            // Save the data
            // $this->exportToCsv();
        }
    }

    // The adjusted file reading and writing methods
    protected function importFromCsv(): bool
    {
        // Create the complete filename
        $currentDate = date('Y_m_d');
        $filename = self::CSV_FILENAME_BASE . $currentDate . '.csv';

        return $this->readFromCsv($filename);
    }

    protected function exportToCsv(): bool
    {
        // Create the complete filename
        $currentDate = date('Y_m_d');
        $filename = self::CSV_FILENAME_BASE . $currentDate . '.csv';

        // Check if we're not overwriting stuff
        if (file_exists(self::CSV_DIRECTORY . '/' . $filename)) {
            return false;
        }

        // If we're not, writing can happen
        return $this->writeToCsv($filename);
    }
}
