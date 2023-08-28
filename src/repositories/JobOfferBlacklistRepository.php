<?php

namespace repositories;

use domains\BlacklistedJobOffer;
use SplObjectStorage;

class JobOfferBlacklistRepository
{
    private SplObjectStorage $jobOffersBlacklist;

    private const BLACKLIST_CSV = DIR_DATABASE . "/blacklist.csv";
    private const CSV_HEADERS = [
        "Company name",
        "Job position title",
        "Minimum pay",
        "Maximum pay"
    ];

    // Constructor
    public function __construct()
    {
        $this->jobOffersBlacklist = new SplObjectStorage();
        $this->readBlacklistCsv();
    }

    private function readBlacklistCsv(): bool
    {
        if (!file_exists(self::BLACKLIST_CSV)) {
            return false;
        }

        $this->clearJobOffersBlacklist();
        $csvData = array_map('str_getcsv', file(self::BLACKLIST_CSV));
        foreach ($csvData as $row) {
            // Gotta exclude headers from the database
            if ($row == self::CSV_HEADERS) {
                continue;
            }

            self::addJobOffer(
                $row[0], // companyName
                $row[1], // jobTitle
                $row[2], // jobPayMin
                $row[3]  // jobPayMax
            );
        }

        return true;
    }

    private function clearJobOffersBlacklist(): void
    {
        $this->jobOffersBlacklist->removeAll($this->jobOffersBlacklist);
    }

    // Setters
    private function addJobOffer(
        ?string $companyName = null,
        ?string $jobTitle = null,
        ?string $jobPayMin = null,
        ?string $jobPayMax = null
    ): void {
        $this->jobOffersBlacklist->attach(new BlacklistedJobOffer(
            $companyName,
            $jobTitle,
            $jobPayMin,
            $jobPayMax
        ));
    }

    // Getters
    public function getJobOffersBlacklist(): SplObjectStorage
    {
        return $this->jobOffersBlacklist;
    }

    // Destructor
    public function __destruct()
    {
        $this->saveBlacklistCsv();
    }

    private function saveBlacklistCsv(): bool
    {
        $csvFile = fopen(self::BLACKLIST_CSV, 'w');

        if ($csvFile === false) {
            return false;
        }

        // Gotta save in some headers first, to explain what each column means
        fputcsv($csvFile, self::CSV_HEADERS);

        foreach ($this->jobOffersBlacklist as $blacklistedJobOffer) {
            fputcsv($csvFile, [
                $blacklistedJobOffer->getCompanyName(),
                $blacklistedJobOffer->getJobTitle(),
                $blacklistedJobOffer->getJobPayMin(),
                $blacklistedJobOffer->getJobPayMax(),
            ]);
        }
        fclose($csvFile);

        return true;
    }
}
