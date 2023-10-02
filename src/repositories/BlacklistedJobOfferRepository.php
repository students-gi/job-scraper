<?php

namespace repositories;

class BlacklistedJobOfferRepository extends AbstractJobOfferRepository
{
    private const CSV_FILENAME_BASE = "blacklist-";

    public function __construct()
    {
        parent::__construct();
        $this->importFromCsv();
    }

    // The adjusted file reading and writing methods
    protected function importFromCsv(): bool
    {
        // Get all the blacklists registered
        $blacklistPattern = self::CSV_DIRECTORY . '/' . self::CSV_FILENAME_BASE . '*' . '.csv';
        $blacklists = glob($blacklistPattern, GLOB_NOSORT);
        if (empty($blacklists)) {
            // There wasn't one, skipping this
            return false;
        }

        // Find the latest blacklist there is
        array_multisort(
            array_map('filemtime', $blacklists),
            SORT_DESC,
            SORT_NUMERIC,
            $blacklists
        );
        $latestBlacklist = $blacklists[0];

        // Read it in memory
        return $this->readFromCsv($latestBlacklist);
    }

    protected function exportToCsv(): bool
    {
        // Create the complete filename
        $currentDate = date('Y_m_d');
        $filename = self::CSV_FILENAME_BASE . $currentDate . '.csv';

        // Always assume that there are new changes to the blacklist
        return $this->writeToCsv($filename);
    }
}
