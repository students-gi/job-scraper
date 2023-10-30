<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMXPath;

class SsLvScraper extends Scraper
{
    private const SCRAPING_URLS = [
        'https://www.ss.com/lv/work/are-required/network-administrator/',
        'https://www.ss.com/lv/work/are-required/web-designer/',
        'https://www.ss.com/lv/work/are-required/programmer/'
    ];
    private const JOBS_URL = 'https://www.ss.lv/';

    public static function scrapeJobOffers(): array
    {
        $jobOffers = [];

        foreach (self::SCRAPING_URLS as $url) {
            $jobOffers = array_merge($jobOffers, self::httpQuery($url));
        }

        return $jobOffers;
    }

    /**
     * Executes an HTTP request to the specified URL
     * and checks if there is data/job offers to be scraped.
     *
     * @param string $url The URL to send the HTTP request to.
     * @return array[] An empty array, if the URL was not found/wasn't valid/was redirected.
     * @return self::scrapeHtml The scraped HTML jobOffers inside of an array.
     */
    private static function httpQuery(string $url): array
    {
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: text/html']);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        // Execute the cURL request
        $httpBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if we aren't getting redirected or something
        if ($responseCode != 200) {
            return [];
        }

        return self::scrapeHtml($httpBody);
    }

    private static function scrapeHtml(string $httpBody): array
    {
        // Parse convert the HTML into something parsable
        libxml_use_internal_errors(true); // Suppressing bad HTML warnings
        $html = new DOMDocument();
        $html->loadHTML($httpBody);
        libxml_use_internal_errors(false); // Supression done; we can continue

        // Selecting all the job application items
        $xpath = new DOMXPath($html);
        $jobNodesArray = $xpath->evaluate(
            "//form[@id='filter_frm']" .
            "//table[@align='center']" .
            "//tr[starts-with(@id, 'tr_') and translate(substring(@id, 4), '1234567890', '') = '']"
        );

        $jobsArray = [];

        // Translating all of these rows into JobOffers
        foreach ($jobNodesArray as $jobNode){
            // Gotta turn it into a "proper" xml object first
            $nodeDomDocument = new DOMDocument();
            $jobNode = $nodeDomDocument->importNode($jobNode, true);
            $nodeDomDocument->appendChild($jobNode);
            $jobNode = new DOMXPath($nodeDomDocument);

            // Read out the offer's ID
            $jobId = $jobNode->evaluate('string(@id)');
            $jobId = str_replace('tr_', '', $jobId);

            // Getting the company name
            $jobCompany = $jobNode->evaluate("//td[@class='msga2-o pp6']")
                ->item(0)
                ->nodeValue;

            // Adding the request to the array
            $jobsArray[] = new JobOffer(
                "ss_" . $jobId,
                $jobCompany,
                $jobNode->evaluate("string(//img/@src)"), // Company logo
                $jobNode->evaluate("string(//a[@id='dm_$jobId'])"), // Job title
                '', // Minimum job pay often isn't mentioned in SS job ads
                '', // Maximum job pay often isn't mentioned in SS job ads
                self::JOBS_URL // Link to the job offer
                    . $jobNode->evaluate("string(//a[@id='dm_$jobId']/@href)"),
                '', // A deadline for the offer is only visible within the offer
                ''
            );
        }

        return $jobsArray;
    }
}
