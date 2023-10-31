<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMNodeList;
use DOMXPath;

class PrakseLvScraper extends HtmlScraper
{
    private const SCRAPING_URL = 'https://www.prakse.lv/vacancies';
    private const JOBS_URL = 'https://www.prakse.lv';

    public static function scrapeJobOffers(): array
    {
        $jobOffers = [];
        $pageNum = 0;

        while ($pageNum < 5) {
            // Allowing pagination to work
            $pageNum++;
            $completeUrl = self::SCRAPING_URL . "/$pageNum/0/9/0/0";
            $httpBody = self::httpQuery($completeUrl);

            // Extracting the job offers in this page
            $xpathExpression =
                "//div[@class='col-main']" .
                "//section[contains(@class, 'item') and not(contains(@class, 'promoted'))]";
            $htmlJobOffers = self::parseHttpResponseAsHtml($httpBody, $xpathExpression);

            $jobOffersFromPage = self::parseHtmlAsJobOffers($htmlJobOffers);
            $jobOffers = array_merge($jobOffers, $jobOffersFromPage);

            /**
             * Checking if a page is not available is a pain here,
             * since they don't do any redirects always just serving with a 200.
             * So, we need to be a bit sneakier: by counting all items.
             */
            $itemsTotalXpath = "string(//div[@id='filter']//b)";
            $itemsTotal = (int) self::parseHttpResponseAsHtml($httpBody, $itemsTotalXpath);
            if ($itemsTotal <= count($jobOffers)) {
                break;
            }
        }
        return $jobOffers;
    }

    /**
     * Parses the HTML response body and returns an array of JobOffer objects.
     *
     * @param string $httpBody The HTML response body to be parsed.
     *
     * @return array The array of valid JobOffer elements.
     */
     protected static function parseHtmlAsJobOffers(DOMNodeList $htmlJobOfferNodes): array
    {
        $jobsArray = [];

        foreach ($htmlJobOfferNodes as $jobNode) {
            // Turning each node into a proper and valid XML-compliant HTML
            $nodeDomDocument = new DOMDocument();
            $jobNode = $nodeDomDocument->importNode($jobNode, true);
            $nodeDomDocument->appendChild($jobNode);
            $jobNode = new DOMXPath($nodeDomDocument);

            // Gotta more specifically parse out certain info
            $jobMultiInfoString = $jobNode->evaluate("string(//ul/li)");

            // Check if the offer is an internship
            $internshipPattern = "/Prakse •/i";
            if (preg_match($internshipPattern, $jobMultiInfoString)) {
                // We don't need internships; skip this entry
                continue;
            }

            // Parsing the payment
            $jobPayPattern = "/((\d+) līdz )?(\d+) EUR/";
            preg_match($jobPayPattern, $jobMultiInfoString, $matches);

            if (isset($matches[3])) {
                $jobPayMin = $matches[2];
                $jobPayMax = $matches[3];
            } elseif (isset($matches[2])) {
                $jobPayMin = 0;
                $jobPayMax = $matches[2];
            } else {
                $jobPayMin = 0;
                $jobPayMax = 0;
            }

            // Parsing the deadline
            preg_match("/(\d{2}\.\d{2}\.\d{4})/", $jobMultiInfoString, $matches);
            $jobOfferDeadline = $matches[1];
            $jobOfferDeadlineFormat = "d.m.Y|";

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
}
