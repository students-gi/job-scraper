<?php

namespace scrapers;

use domains\JobOffer;
use Error;

class NvaScraper extends Scraper
{
    private const SCRAPING_URL = 'https://cvvp.nva.gov.lv/data/pub_vakance_list?kla_darbibas_joma_id=35073957&nosaukums=progr';
    private const SCRAPING_ITEMS = 25;
    private const JOBS_URL = 'https://cvvp.nva.gov.lv/#/pub/vakances/';

    public static function scrapeJobOffers(): array
    {
        $currentPage = 0;
        $jobOffers = [];
        do {
            // Get the API JSON contents
            $currentPage++;
            $apiResponse = self::fetchJsonData($currentPage);
            if ($apiResponse == null) {
                break;
            }

            // Fill in the jobOffers array
            foreach ($apiResponse as $offer) {
                // Parse pay
                $pay = explode('-', $offer['alga_no_lidz']);
                if (isset($pay[1])) {
                    $payMin = $pay[0];
                    $payMax = $pay[1];
                } else {
                    $payMin = 0;
                    $payMax = $pay[0];
                }

                $jobOffers[] = new JobOffer(
                    "nva_" . $offer['id'],
                    $offer['uzn_uznemums_nosaukums'],
                    "", // They don't show company logos off in any way
                    $offer['kla_profesija_nosaukums'],
                    $payMin,
                    $payMax,
                    self::generateJobOfferLink($offer['id']),
                    $offer['aktuala_lidz'],
                    "Y-m-d|"
                );
            }
        } while (sizeof($apiResponse) > 0);

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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        // Execute the cURL request
        $json = curl_exec($ch);
        curl_close($ch);

        return json_decode($json, true);
    }

    private static function generateJobOfferLink(int $vacancyId): string
    {
        return self::JOBS_URL . $vacancyId;
    }
}
