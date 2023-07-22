<?php

namespace repositories;

use SplObjectStorage;
use domains\JobOffer;

class JobOfferBlacklistRepository
{
    private static SplObjectStorage $jobOffersBlacklist;

    private const BLACKLIST_CSV = DIR_DATABASE . "/blacklist.csv";

    // Setters
    private static function readCsvDatabase(): array | NULL
    {
        if (!file_exists(self::BLACKLIST_CSV)) {
            return null;
        }

        $blacklistedOffers = [];
        $csvData = array_map('str_getcsv', file(self::BLACKLIST_CSV));
        foreach ($csvData as $row) {
            array_push($blacklistedOffers, array(
                'company_name' => $row[0],
                'job_title' => $row[1],
                'minimum_pay' => $row[2],
                'maximum_pay' => $row[3],
            ));
        }

        return $blacklistedOffers;
    }

    private static function addJobOffer(array $jobOffer): void
    {
        self::$jobOffersBlacklist->attach(new JobOffer(
            $jobOffer['company_name'],
            "",
            $jobOffer['job_title'],
            $jobOffer['minimum_pay'] . '-' . $jobOffer['maximum_pay'],
            "",
            "0000-01-01",
            "Y-m-d|"
        ));
    }

    // Getters
    public static function getJobOffersBlacklist(): SplObjectStorage | NULL
    {
        self::$jobOffersBlacklist = new SplObjectStorage();
        $blacklist = self::readCsvDatabase();
        if ($blacklist === null) return null;

        foreach ($blacklist as $blacklistedOffer) {
            self::addJobOffer($blacklistedOffer);
        }

        self::$jobOffersBlacklist->rewind();
        return self::$jobOffersBlacklist;
    }
}
