<?php

namespace repositories;

use SplObjectStorage;
use domains\JobOffer;
use Generator;

abstract class AbstractJobOfferRepository
{
    private SplObjectStorage $jobOffers;

    protected const CSV_DIRECTORY = DIR_DATABASE;
    private const CSV_HEADERS = [
        "Job offer ID",
        "Company name",
        "Company logo URL",
        "Job position title",
        "Minimum pay",
        "Maximum pay",
        "Link to offer",
        "Deadline of offer"
    ];

    public function __construct()
    {
        $this->jobOffers = new SplObjectStorage();
    }

    public function __destruct()
    {
        $this->exportToCsv();
    }

    // Setters
    public function addJobOffer(JobOffer $jobOffer): void
    {
        // Preventing duplicates
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->attach($jobOffer);
        }
    }

    // Getters
    public function getJobOffers(): Generator
    {
        $this->jobOffers->rewind();

        while ($this->jobOffers->valid()) {
            yield $this->jobOffers->current();
            $this->jobOffers->next();
        }
    }

    /**
     * Search for jobOffers that match the specified criteria.
     *
     * @param array $searchParameters
     * An associative array of search criteria and their expected values.
     * Keys are JobOffer object property values, appropriately capitalized.
     * @return Generator
     * A generator yielding job offers that meet all the specified criteria.
     */
    public function searchJobOffers(array $searchParameters): Generator
    {
        foreach ($this->getJobOffers() as $jobOffer) {
            $matchesAllCriteria = true;

            foreach ($searchParameters as $parameter => $value) {
                $getter = 'get' . ucfirst($parameter);
                if (!method_exists($jobOffer, $getter)) {
                    // Invalid param was passed, skipping
                    continue;
                }
                if ($jobOffer->$getter() !== $value) {
                    // Value checked was not the one wanted, breaking the loop
                    $matchesAllCriteria = false;
                    break;
                }
            }

            if ($matchesAllCriteria) {
                yield $jobOffer;
            }
        }
    }

    // In-memory element manipulations
    public function removeJobOffer(JobOffer $jobOffer): void
    {
        // Preventing duplicates
        if ($this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->detach($jobOffer);
        }
    }

    private function clearJobOffers(): void
    {
        $this->jobOffers->removeAll($this->jobOffers);
    }

    // Database interactions
    protected function writeToCsv(string $fileName): bool
    {
        $filePath = self::CSV_DIRECTORY . '/' . $fileName;
        $csvFile = fopen($filePath, 'w');
        if (!$csvFile) {
            return false;
        }

        // Gotta save in some headers first, to explain what each column means
        fputcsv($csvFile, self::CSV_HEADERS);
        foreach ($this->getJobOffers() as $jobOffer) {
            fputcsv($csvFile, [
                $jobOffer->getOfferId(),
                $jobOffer->getCompanyName(),
                $jobOffer->getCompanyLogo(),
                $jobOffer->getJobTitle(),
                $jobOffer->getJobPayMin(),
                $jobOffer->getJobPayMax(),
                $jobOffer->getOfferLink(),
                $jobOffer->getFormattedOfferDeadline()
            ]);
        }
        fclose($csvFile);

        // Everything happened correctly
        return true;
    }

    protected function readFromCsv(string $fileName): bool
    {
        $filePath = self::CSV_DIRECTORY . '/' . $fileName;
        $csvFile = fopen($filePath, 'r');
        if (!$csvFile) {
            return false;
        }

        // Clear out whatever is stored in-memory atm
        $this->clearJobOffers();

        // This is purely to skip the initial header line
        fgetcsv($csvFile);
        while (($row = fgetcsv($csvFile)) !== false) {
            self::addJobOffer(new JobOffer(
                $row[0], // jobId
                $row[1], // companyName
                $row[2], // companyLogo
                $row[3], // jobTitle
                $row[4], // jobPayMin
                $row[5], // jobPayMax
                $row[6], // offerLink
                $row[7], // offerDeadline (formatted string)
                "Y-m-d"  // offerDeadlineFormat
            ));
        }

        // Everything happened correctly
        return true;
    }

    abstract protected function importFromCsv(): bool;
    abstract protected function exportToCsv(): bool;
}
