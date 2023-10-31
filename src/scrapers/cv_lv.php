<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMXPath;

class CvLvScraper extends JsonScraper
{
    private const SCRAPING_URL =
        'https://cv.lv/lv/search'
        . '?categories[0]=INFORMATION_TECHNOLOGY'
        . '&keywords[0]=Programm'
        . '&fuzzy=true';
    private const SCRAPING_ITEMS = 50;
    private const JOBS_URL = 'https://cv.lv/lv/vacancy/';
    private const ICONS_URL = 'https://cv.lv/api/v1/files-service/';

    public static function scrapeJobOffers(): array
    {
        $jobOffers = [];
        $pageNum = 0;

        do {
            // Get the API's response
            $pageNum++;
            $completeUrl = self::SCRAPING_URL
                . '&limit=' . self::SCRAPING_ITEMS
                . '&offset=' . (($pageNum - 1) * self::SCRAPING_ITEMS);
            $httpBody = self::httpQuery($completeUrl);

            if ($httpBody === false) {
                break;
            }

            // Extract the JSON section we need to parse
            $jsonArray = self::extractJsonData($httpBody);

            // Extracting the job offers in this JSON
            $jobOffers = array_merge(
                $jobOffers,
                self::parseJsonAsJobOffers($jsonArray)
            );

            // Checking item counts n such
            $maxItems = $jsonArray['props']['initialReduxState']['search']['total'];
        } while ($maxItems >= $pageNum * self::SCRAPING_ITEMS);

        return $jobOffers;
    }

    protected static function parseJsonAsJobOffers(array $jsonArray): array
    {
        // Extract the data we require
        $jsonJobOffersKeys = [
            'id',
            'employerName',
            'logoId',
            'positionTitle',
            'salaryFrom',
            'salaryTo',
            'expirationDate'
        ];
        $jsonJobOffers = self::extractRequiredArrayKeys(
            $jsonArray,
            $jsonJobOffersKeys,
            'props>initialReduxState>search>vacancies'
        );

        $jobOffers = [];

        // Fill in the jobOffers array
        foreach ($jsonJobOffers as $jsonJobOffer) {
            $jobOffers[] = new JobOffer(
                "cvlv_" . $jsonJobOffer['id'],
                $jsonJobOffer['employerName'],
                self::generateLogoLink($jsonJobOffer['logoId']),
                $jsonJobOffer['positionTitle'],
                $jsonJobOffer['salaryFrom'] ?? 0,
                $jsonJobOffer['salaryTo'] ?? 0,
                self::generateJobOfferLink($jsonJobOffer['id']),
                $jsonJobOffer['expirationDate'],
                "Y-m-d\TH:i:s.uP"
            );
        }

        return $jobOffers;
    }

    /**
     * Extracts the page's JSON data about the page contents
     * from a plaintext HTML string.
     *
     * @param string $httpBody The plaintext HTML needing JSON data extraction.
     *
     * @return array|null The extracted JSON data as an associated array,
     * or null if no data was found.
     */
    private static function extractJsonData(string $httpBody): array
    {
        // Convert the plaintext HTML into something parsable
        libxml_use_internal_errors(true); // Suppressing bad HTML warnings
        $html = new DOMDocument();
        $html->loadHTML($httpBody);
        libxml_use_internal_errors(false); // Supression done; we can continue

        // Extracting the JSON section
        $xpathExpression = '//script[@id="__NEXT_DATA__"]';
        $xpath = new DOMXPath($html);
        $scriptNodes = $xpath->evaluate($xpathExpression);
        $jsonData = $scriptNodes[0]->textContent;

        // Convert the plaintext JSON into an associated array
        return self::parseHttpResponseAsJson($jsonData);
    }

    private static function generateLogoLink(?string $logoId): string
    {
        if ($logoId === null) {
            return '';
        }
        return self::ICONS_URL . $logoId;
    }

    private static function generateJobOfferLink(int $vacancyId): string
    {
        return self::JOBS_URL . $vacancyId;
    }
}
