<?php

namespace scrapers;

abstract class JsonScraper extends Scraper
{
    /**
     * The following function is meant to create an array of JobOffer objects
     * that need to be added into the complete JobOfferRepository
     *
     * It should receive the array from parseHttpResponseAsJson().
     *
     * It should return an array of valid JobOffer elements.
     **/
    abstract protected static function parseJsonAsJobOffers(array $jsonArray): array;

    /**
     * Executes an HTTP request to the passed URL
     * and checks if there is data/job offers to be scraped.
     * By default adds the expectation header "Accept: application/json"
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
    ): string | false
    {
        $header[] = 'Accept: application/json';
        $httpBody = parent::httpQuery($url, $header);
        return $httpBody;
    }

    /**
     * Interprets an HTTP response body as JSON and returns an asociated array.
     * Used to parse the response from the httpQuery() method
     * and select only the key-value pairs required for a jobOffer object.
     *
     * @param string $httpBody The plaintext JSON needing to be interpreted.
     *
     * @return array The JSON interpreted as an associative array
     */
    protected static function parseHttpResponseAsJson(string $httpBody): array
    {
        // Interpret the plaintext as JSON
        // Yeah, we probably don't actually need a separate function for this, but eh
        return json_decode($httpBody, true);
    }

    /**
     * Extracts a subset of key-value pairs from a JSON array.
     * Used to minimize and organize the output of parseHttpResponseAsJson(),
     * compacting data to that which is required for the creation of a jobOffer.
     *
     * @param array $jsonArray The JSON array to extract data from
     * @param array $jsonKeys The keys that contain job info details, relative to the parent.
     * @param string $parentJsonKey The parent container that contains all job info.
     *
     * Child keys are separated with '>'.
     * So in order to access the 'value' in {'parent':{'child':'value'}},
     * you would submit 'parent>child' into the $jsonKeys array.
     */
    protected static function extractRequiredArrayKeys(
        array $jsonArray,
        array $subsetJsonKeys,
        string $parentJsonKey = ''
    ): array
    {

        // Select the parent container, if a non-root one is defined
        if ($parentJsonKey !== '') {
            $parentKeys = explode('>', $parentJsonKey);
            foreach ($parentKeys as $key) {
                $jsonArray = $jsonArray[$key];
            }
        }

        $jsonSubset = [];

        // Now the triple loop to strip out only what we need
        foreach ($jsonArray as $jobOfferData) {
            // This contains the data for just 1 job offer
            $jobOfferSortedData = [];

            foreach ($subsetJsonKeys as $key) {
                $parts = explode('>', $key);

                // Pull out the proper value we need
                $value = $jobOfferData;
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        $value = null;
                        break;
                    }
                }

                // Save the value to the given key
                $jobOfferSortedData[$key] = $value;
            }

            // Add this cleaned-up job offer data to the return array
            $jsonSubset[] = $jobOfferSortedData;
        }

        return $jsonSubset;
    }
}
