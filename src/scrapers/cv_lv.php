<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMXPath;

class CvLvScraper extends Scraper
{
    private const SCRAPING_URL = 'https://cv.lv/en/search?categories%5B0%5D=INFORMATION_TECHNOLOGY&keywords%5B0%5D=progamm%C4%93t%C4%81js&fuzzy=true';
    private const SCRAPING_ITEMS = 5;
    private const JOBS_URL = 'https://cv.lv/lv/vacancy/';
    private const ICONS_URL = 'https://cv.lv/api/v1/files-service/';

    public static function scrapeJobOffers(): array
    {
        $currentPage = 0;
        $jobOffers = [];

        do {
            $currentPage++;
            $jsonResponse = self::fetchJsonData($currentPage);
            if ($jsonResponse == null) {
                break;
            }

            // Fill in the jobOffers array
            foreach ($jsonResponse['props']['initialReduxState']['search']['vacancies'] as $offer) {
                $jobOffers[] = new JobOffer(
                    "cvlv_" . $offer['id'],
                    $offer['employerName'],
                    self::generateLogoLink($offer['logoId']),
                    $offer['positionTitle'],
                    ($offer['salaryFrom'] == null) ? 0 : $offer['salaryFrom'],
                    ($offer['salaryTo'] == null) ? 0 : $offer['salaryTo'],
                    self::generateJobOfferLink($offer['id']),
                    $offer['expirationDate'],
                    "Y-m-d\TH:i:s.uP"
                );
            }

            // Waiting 2-6 sec to not overload the site and avoid a ban
            usleep((mt_rand(2000, 6000) / 1000) * 1000000);
        } while ($jsonResponse['props']['initialReduxState']['search']['total'] >= $currentPage * 5);

        return $jobOffers;
    }

    private static function fetchJsonData(int $pageNumber): ?array
    {
        $pageQuery = "&limit=" . self::SCRAPING_ITEMS
            . "&offset=" . (($pageNumber - 1) * self::SCRAPING_ITEMS);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (self::SCRAPING_URL . $pageQuery));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html']);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        // Execute the cURL request
        $httpBody = curl_exec($ch);
        curl_close($ch);

        // Parse the JSON from the HTML response
        libxml_use_internal_errors(true); // Suppressing bad HTML warnings
        $htmlBody = new DOMDocument();
        $htmlBody->loadHTML($httpBody);
        $xpath = new DOMXPath($htmlBody);

        $scriptNodes = $xpath->evaluate('//script[@id="__NEXT_DATA__"]');
        $jsonData = $scriptNodes[0]->textContent;

        return json_decode($jsonData, true);
    }

    private static function generateLogoLink(string $logoId): string
    {
        return self::ICONS_URL . $logoId;
    }

    private static function generateJobOfferLink(int $vacancyId): string
    {
        return self::JOBS_URL . $vacancyId;
    }
}
