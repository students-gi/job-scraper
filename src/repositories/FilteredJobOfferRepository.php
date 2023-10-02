<?php

namespace repositories;

use scrapers\ScraperManager;

class FilteredJobOfferRepository extends AbstractJobOfferRepository
{
    // The adjusted file reading and writing methods
    protected function importFromCsv(): bool
    {
        // We don't save filtered output
        return false;
    }

    protected function exportToCsv(): bool
    {
        // We don't save filtered output
        return false;
    }
}
