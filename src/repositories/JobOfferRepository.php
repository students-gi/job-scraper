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

    public function __construct()
    {
        $this->jobOffers = new SplObjectStorage();
    }

    public function __destruct()
    {
        $this->removeExpiredJobOffers();
        $this->saveToCsv();
    }

    // Setters
    public function addJobOffer(JobOffer $jobOffer): void
    {
        $this->jobOffers->attach($jobOffer);
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
                unset($jobOffer);
            }
        }
    }

    public function removeDuplicateJobOffers(): void
    {
        $uniqueJobOffers = new SplObjectStorage();
        foreach ($this->jobOffers as $jobOffer) {
            $uniqueJobOffers->attach($jobOffer);
        }
        $this->jobOffers = $uniqueJobOffers;
    }

    // Database interactions
    public function saveToCsv(string $currentDate = null): void
    {
        if ($currentDate === null) {
            $currentDate = date("Y_m_d");
        } else {
            $currentDate = DateTime::createFromFormat('Y_m_d', $currentDate)->format('Y_m_d');
        }

        $fileName = self::CSV_DIRECTORY . '/' . self::CSV_FILENAME_BASE . $currentDate . ".csv";
        $csvFile = fopen($fileName, 'w');

        if ($csvFile === false) {
            return;
        }

        // Gotta save in some headers first, to explain what each column means
        fputcsv($csvFile, [
            "Company name",
            "Company logo URL",
            "Job position title",
            "Minimum pay",
            "Maximum pay",
            "Link to offer",
            "Deadline of offer"
        ]);
        foreach ($this->getJobOffers() as $jobOffer) {
            fputcsv($csvFile, [
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
        if ($latestCsvFile !== null) {
            $this->readFromCsv($latestCsvFile);
            return true;
        }
        return false;
    }

    private function readFromCsv(string $fileName): void
    {
        if (!file_exists($fileName)) {
            return;
        }

        $this->clearJobOffers();
        $csvData = array_map('str_getcsv', file($fileName));
        foreach ($csvData as $row) {
            self::addJobOffer(new JobOffer(
                $row[0], // companyName
                $row[1], // companyLogo
                $row[2], // jobTitle
                $row[3], // jobPayMin
                $row[4], // jobPayMax
                $row[5], // offerLink
                $row[6]  // offerDeadline (formatted string)
            ));
        }
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
}
