<?php

namespace scrapers;

use domains\JobOffer;

class LikeItScraper extends Scraper
{
    private const SCRAPING_URL = 'https://api.likeit.lv/api/v1/offers?search=&category%5B%5D=10&category%5B%5D=40%5B%5D=&type%5B%5D=&ordering=Date+added&tag%5B%5D=';
    private const SCRAPING_ITEMS = 50;
    private const JOBS_URL = 'https://likeit.lv/job/';

    public static function scrapeJobOffers(): array
    {
        $currentPage = 0;
        $jobOffers = [];
        do {
            // Get the API JSON contents
            $currentPage++;
            $apiResponse = self::fetchJsonData($currentPage);
            if ($apiResponse == null) {
                break;
            }

            // Fill in the jobOffers array
            foreach ($apiResponse['data'] as $offer) {
                $jobOffers[] = new JobOffer(
                    $offer['employer']['name'],
                    $offer['employer']['logo'],
                    $offer['job_position'],
                    $offer['salary_min'] . '-' . $offer['salary_max'],
                    self::generateJobOfferLink($offer['job_position'], $offer['id']),
                    $offer['deadline'],
                    "Y-m-d|"
                );
            }
        }
        while ($apiResponse['current_page'] != $apiResponse['last_page']);

        return $jobOffers;
    }

    private static function fetchJsonData(int $pageNumber): ?array
    {
        $pageQuery = "&per_page=" . self::SCRAPING_ITEMS
            . "&page=" . $pageNumber;
        // Perform API request and fetch JSON response
        $json = @file_get_contents(self::SCRAPING_URL . $pageQuery);
        if ($json === false) {
            return null;
        }

        return json_decode($json, true);
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
