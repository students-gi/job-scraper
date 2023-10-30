<?php

namespace scrapers;

abstract class Scraper
{
    /**
     * The following function is meant to be a general "main" method,
     * from which all other private/custom methods can be called.
     *
     * It should return an array of JobOffer elements
     * that need to be added into the complete JobOfferRepository
     **/
    abstract public static function scrapeJobOffers(): array;

    /**
     * Executes an HTTP request to the passed URL
     * and checks if there is data/job offers to be scraped.
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
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        // Execute the cURL request
        $httpBody = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check if we aren't getting redirected or something
        if ($responseCode != 200) {
            return false;
        }

        // Execute the cURL request
        return $httpBody;
    }
}
