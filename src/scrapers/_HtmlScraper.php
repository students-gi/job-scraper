<?php

namespace scrapers;

use DOMDocument;
use DOMNodeList;
use DOMXPath;

abstract class HtmlScraper extends Scraper
{
    /**
     * The following function is meant to create an array of JobOffer objects
     * that need to be added into the complete JobOfferRepository
     *
     * It should receive the DOMNodeList from parseHttpResponseAsHtml().
     *
     * It should return an array of valid JobOffer elements.
     **/
    abstract protected static function parseHtmlAsJobOffers(DOMNodeList $htmlNodes): array;

    /**
     * Executes an HTTP request to the passed URL
     * and checks if there is data/job offers to be scraped.
     * By default adds the expectation header "Accept: text/html"
     *
     * @param string $url The URL to send the HTTP request to.
     * @param array $header The HTTP headers to send.
     *
     * @return false if the URL was not found/wasn't valid/was redirected.
     * @return string The HTTP response that needs to be parsed
     */
    protected static function httpQuery(
        string $url,
        array $header = []
    ): array | bool {
        $header[] = 'Accept: text/html';
        return parent::httpQuery($url, $header);
    }

    /**
     * Parses an HTTP response body as HTML by using a passed XPath expression.
     * Used to parse the response from the httpQuery() method
     * and select all nodes containing job info.
     *
     * @param string $httpBody The plaintext HTML to be parsed.
     * @param string $xpathExpression The XPath expression to select desired elements.
     *
     * @return DOMNodeList The list of valid elements
     */
    protected static function parseHttpResponseAsHtml(
        string $httpBody,
        string $xpathExpression
    ): DOMNodeList
    {
        // Convert the plaintext HTML into something parsable
        libxml_use_internal_errors(true); // Suppressing bad HTML warnings
        $html = new DOMDocument();
        $html->loadHTML($httpBody);
        libxml_use_internal_errors(false); // Supression done; we can continue

        // Selecting all the job application items
        $xpath = new DOMXPath($html);
        return $xpath->evaluate($xpathExpression);
    }
}
