<?php

namespace scrapers;

use domains\JobOffer;
use DOMDocument;
use DOMNodeList;
use DOMXPath;

class SsLvScraper extends HtmlScraper
{
    private const SCRAPING_URLS = [
        'https://www.ss.com/lv/work/are-required/network-administrator',
        'https://www.ss.com/lv/work/are-required/web-designer',
        'https://www.ss.com/lv/work/are-required/programmer'
    ];
    private const JOBS_URL = 'https://www.ss.lv';

    public static function scrapeJobOffers(): array
    {
        $jobOffers = [];

        foreach (self::SCRAPING_URLS as $url) {
            $pageNum = 0;
            while (true) {
                // Allowing pagination to work
                $pageNum++;
                $completeUrl = "$url/page$pageNum.html";
                $httpBody = self::httpQuery($completeUrl);

                if ($httpBody === false) {
                    // There is no next page; we got redirected to the 1st one.
                    // We don't need to continue scraping this category
                    break;
                }

                // Extracting the job offers in this page
                $xpathExpression =
                    "//form[@id='filter_frm']" .
                    "//table[@align='center']" .
                    "//tr[starts-with(@id, 'tr_') and translate(substring(@id, 4), '1234567890', '') = '']";
                $htmlJobOffers = self::parseHttpResponseAsHtml($httpBody, $xpathExpression);

                $jobOffersFromPage = self::parseHtmlAsJobOffers($htmlJobOffers);
                $jobOffers = array_merge($jobOffers, $jobOffersFromPage);
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

            // Find the job offer ID
            $jobId = $jobNode->evaluate('string(@id)');
            $jobId = str_replace('tr_', '', $jobId);

            // Adding the request to the array
            $jobsArray[] = new JobOffer(
                "ss_" . $jobId, // Job offer ID
                $jobNode->evaluate("string(//td[@class='msga2-o pp6'])"), // Company title (if one was placed)
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
