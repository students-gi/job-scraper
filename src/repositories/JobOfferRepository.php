<?php

namespace repositories;

use DateTime;
use SplObjectStorage;
use domains\JobOffer;

class JobOfferRepository
{
    private SplObjectStorage $jobOffers;

    private const CSV_DIRECTORY = DIR_DATABASE;
    private const CSV_FILENAME_BASE = "job_offers-";
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

    // Setters
    public function addJobOffer(JobOffer $jobOffer): void
    {
        // Keeping duplicates outside
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->attach($jobOffer);
        }
    }

    // Getters
    public function getJobOffers(): array
    {
        $this->jobOffers->rewind();
        $jobOffersArray = [];
        while ($this->jobOffers->valid()) {
            $jobOffersArray[] = $this->jobOffers->current();
            $this->jobOffers->next();
        }
        return $jobOffersArray;
    }

    // In-memory element manipulations
    private function clearJobOffers(): void
    {
        $this->jobOffers->removeAll($this->jobOffers);
    }

    public function removeExpiredJobOffers(): void
    {
        $currentDate = new DateTime();
        foreach ($this->jobOffers as $jobOffer) {
            if ($jobOffer->getOfferDeadline() <= $currentDate) {
                $this->jobOffers->detach($jobOffer);
            }
        }
    }

    public function filterBlacklistedJobOffers(JobOfferBlacklistRepository $blacklist): void
    {
        foreach ($blacklist as $blacklistedJob) {
            foreach ($this->jobOffers as $jobOffer) {
                if ($blacklistedJob->filterBlacklistedJobOffers($jobOffer)) {
                    $this->jobOffers->detach($jobOffer);
                }
            }
        }
    }

    // Database interactions
    public function saveToCsv(string $currentDate = ""): void
    {
        $currentDate = DateTime::createFromFormat('Y_m_d', $currentDate);
        $currentDate = ($currentDate === false) ?
            $currentDate = date("Y_m_d") :
            $currentDate->format('Y_m_d');

        $fileName = self::CSV_DIRECTORY . '/' . self::CSV_FILENAME_BASE . $currentDate . ".csv";
        $csvFile = fopen($fileName, 'w');

        if ($csvFile === false) {
            return;
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
    }

    public function readFromLatestCsv(): bool
    {
        $latestCsvFile = $this->getLatestCsvFile();
        if ($latestCsvFile == null) {
            return false;
        }
        $this->readFromCsv($latestCsvFile);
        return true;
    }

    private function getLatestCsvFile(): ?string
    {
        $latestFile = null;
        $latestDate = null;

        $csvFiles = glob(self::CSV_DIRECTORY . '/' . self::CSV_FILENAME_BASE . "*.csv");
        foreach ($csvFiles as $csvFile) {
            $matches = [];
            if (preg_match('/job_offers-(\d{4}_\d{2}_\d{2}).csv/', basename($csvFile), $matches)) {
                $fileDate = DateTime::createFromFormat('Y_m_d', $matches[1]);
                if ($fileDate instanceof DateTime && ($latestDate === null || $fileDate > $latestDate)) {
                    $latestFile = $csvFile;
                    $latestDate = $fileDate;
                }
            }
        }

        return $latestFile;
    }

    private function readFromCsv(string $fileName): bool
    {
        if (!file_exists($fileName)) {
            return false;
        }

        $this->clearJobOffers();
        $csvData = array_map('str_getcsv', file($fileName));
        foreach ($csvData as $row) {
            // Gotta exclude headers from the database
            if ($row == self::CSV_HEADERS) {
                continue;
            }

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

        return true;
    }
}
