<?php

namespace scrapers;

use domains\JobOffer;

class LikeItScraper extends JsonScraper
{
    private const SCRAPING_URL =
    'https://api.likeit.lv/api/v1/offers?' .
        'search=&category%5B%5D=10' .
        '&category%5B%5D=40%5B%5D=' .
        '&type%5B%5D=' .
        '&ordering=Date+added' .
        '&tag%5B%5D=';
    private const SCRAPING_ITEMS = 50;
    private const JOBS_URL = 'https://likeit.lv/job/';

    public static function scrapeJobOffers(): array
    {
        $jobOffers = [];
        $pageNum = 0;

        do {
            // Get the API's response
            $pageNum++;
            $completeUrl = self::SCRAPING_URL
                . "&per_page=" . self::SCRAPING_ITEMS
                . "&page=" . $pageNum;
            $httpBody = self::httpQuery($completeUrl);

            if ($httpBody === false) {
                break;
            }

            $jsonArray = self::parseHttpResponseAsJson($httpBody);

            // Extracting the job offers in this JSON
            $jobOffers = array_merge(
                $jobOffers,
                self::parseJsonAsJobOffers($jsonArray)
            );
        } while ($jsonArray['current_page'] != $jsonArray['last_page']);

        return $jobOffers;
    }

    protected static function parseJsonAsJobOffers(array $jsonArray): array
    {
        // Extract the data we require
        $jsonJobOffersKeys = [
            'id',
            'employer>name',
            'employer>logo',
            'job_position',
            'salary_min',
            'salary_max',
            'deadline'
        ];
        $jsonJobOffers = self::extractRequiredArrayKeys(
            $jsonArray,
            $jsonJobOffersKeys,
            'data'
        );

        $jobOffers = [];

        // Fill in the jobOffers array
        foreach ($jsonJobOffers as $jsonJobOffer) {
            $jobOffers[] = new JobOffer(
                "likeit_" . $jsonJobOffer['id'],
                $jsonJobOffer['employer>name'],
                $jsonJobOffer['employer>logo'],
                $jsonJobOffer['job_position'],
                $jsonJobOffer['salary_min'],
                $jsonJobOffer['salary_max'],
                self::generateJobOfferLink(
                    $jsonJobOffer['job_position'],
                    $jsonJobOffer['id']
                ),
                $jsonJobOffer['deadline'],
                "Y-m-d|"
            );
        }

        return $jobOffers;
    }

    private static function generateJobOfferLink(string $jobPosition, int $postingId): string
    {
        // Convert the job position to lowercase
        $jobPosition = strtolower($jobPosition);
        // Replace spaces with dashes
        $jobPosition = str_replace(' ', '-', $jobPosition);
        // Handling special characters by hacking around with url-encoding
        $jobPosition = urlencode($jobPosition);
        $jobPosition = preg_replace('/(%[0-9a-fA-F]{2})/', '-', $jobPosition);
        // Replace consecutive dashes with a single dash
        $jobPosition = preg_replace('/-+/', '-', $jobPosition);

        return self::JOBS_URL . $jobPosition . '-' . $postingId;
    }
}
