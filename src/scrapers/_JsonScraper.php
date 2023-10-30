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
    abstract protected static function parseJsonAsJobOffers(array $jobOfferJson): array;

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
     * Interprets an HTTP response body as JSON and returns a subset of data.
     * Used to parse the response from the httpQuery() method
     * and select only the key-value pairs required for a jobOffer object.
     *
     * @param string $httpBody The plaintext JSON needing to be interpreted.
     * @param array $jsonKeys The keys that contain job info details, relative to the parent.
     * @param string $parentJsonKeys The parent container that contains all job info.
     *
     * Child keys are separated with '>'.
     * So in order to access the 'value' in {'parent':{'child':'value'}},
     * you would submit 'parent>child' into the $jsonKeys array.
     *
     * @return DOMNodeList The list of valid elements
     */
    protected static function parseHttpResponseAsJson(
        string $httpBody,
        array $jobJsonKeys,
        string $parentJsonKeys = ''
    ): array
    {
        // Interpret the plaintext as JSON
        $jsonArray = json_decode($httpBody, true);

        // Select the parent container, if a non-root one is defined
        if ($parentJsonKeys !== '') {
            $parentKeys = explode('>', $parentJsonKeys);
            foreach ($parentKeys as $key) {
                $jsonArray = $jsonArray[$key];
            }
        }

        $subset = [];

        // Now the loop within the loop withn the loop to strip out what we need
        foreach ($jsonArray as $jobOfferData) {
            $jobOfferSortedData = [];

            foreach ($jobJsonKeys as $key) {
                $parts = explode('>', $key);

                // Pull out the proper value we need
                $value = $jobOfferData;
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    }
                    else {
                        $value = null;
                        break;
                    }
                }

                // Save the value to the given key
                $jobOfferSortedData[$key] = $value;
            }

            // Add this cleaned-up job offer data to the return array
            $subset[] = $jobOfferSortedData;
        }

        return $subset;
    }
}
