<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMXPath;

class PrakseLvScraper extends Scraper
{
    private const SCRAPING_URL = 'https://www.prakse.lv/vacancies/1/3/9/0/0';
    private const JOBS_URL = 'https://www.prakse.lv';

    public static function scrapeJobOffers(): array
    {

        $httpResponse = self::readWebsiteContent();
        $jobOffers = self::parseWebsiteContent($httpResponse);
        if ($jobOffers == null) {
            return null;
        }
        return $jobOffers;
    }

    private static function readWebsiteContent(): DOMDocument
    {
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (self::SCRAPING_URL));
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
        return $htmlBody;
    }

    private static function parseWebsiteContent(DOMDocument $httpResponse)
    {
        // Selecting all the job application items
        $xpath = new DOMXPath($httpResponse);
        $jobNodesArray = $xpath->evaluate(
            "//div[@class='col-main']" .
            "//section[contains(@class, 'item') and not(contains(@class, 'promoted'))]");

        $jobsArray = [];
        foreach ($jobNodesArray as $jobNode) {
            // Gotta turn it into a "proper" xml object first
            $nodeDomDocument = new DOMDocument();
            $jobNode = $nodeDomDocument->importNode($jobNode, true);
            $nodeDomDocument->appendChild($jobNode);
            $jobNode = new DOMXPath($nodeDomDocument);

            // Gotta more specifically parse out certain info
            $jobMultiInfoString = $jobNode->evaluate("string(//ul/li)");

            // Parsing the payment
            $jobPayPattern = "/((\d+) līdz )?(\d+) EUR/";
            preg_match($jobPayPattern, $jobMultiInfoString, $matches);

            if (isset($matches[3])) {
                $jobPayMin = $matches[2];
                $jobPayMax = $matches[3];
            }
            elseif (isset($matches[2])) {
                $jobPayMin = 0;
                $jobPayMax = $matches[2];
            }
            else {
                $jobPayMin = 0;
                $jobPayMax = 0;
            }

            // Parsing the deadline
            preg_match("/(\d{2}\.\d{2}\.\d{4})/", $jobMultiInfoString, $matches);
            $jobOfferDeadline=$matches[1];
            $jobOfferDeadlineFormat="d.m.Y|";

            // Parsing the URL
            $urlString = self::JOBS_URL . $jobNode->evaluate("string(//h2/a/@href)");
            $idPattern = '/\/vacancy\/(\d+)/';
            preg_match($idPattern, $urlString, $idMatches);
            $idString = $idMatches[1];

            $jobsArray[] = new JobOffer(
                "prakse_" . $idString,
                $jobNode->evaluate("string(//h5)"), // Company title
                $jobNode->evaluate("string(//img/@src)"), // Company logo
                $jobNode->evaluate("string(//h2/a)"), // Job title
                $jobPayMin,
                $jobPayMax,
                $urlString, // Job link,
                $jobOfferDeadline,
                $jobOfferDeadlineFormat
            );
        }

        return $jobsArray;
    }

    public static function scraperTest()
    {
        return self::parseWebsiteContent(self::readWebsiteContent());
    }
}
