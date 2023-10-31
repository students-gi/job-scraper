<?php

namespace scrapers;

use domains\JobOffer;

class NvaScraper extends JsonScraper
{
    private const SCRAPING_URL = 'https://cvvp.nva.gov.lv/data/pub_vakance_list?kla_darbibas_joma_id=35073957&nosaukums=progr';
    private const SCRAPING_ITEMS = 25;
    private const JOBS_URL = 'https://cvvp.nva.gov.lv/#/pub/vakances/';

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

            $jsonArray = self::parseHttpResponseAsJson($httpBody);

            // Extracting the job offers in this JSON
            $jobOffers = array_merge(
                $jobOffers,
                self::parseJsonAsJobOffers($jsonArray)
            );

        } while (sizeof($jsonArray) > 0);

        return $jobOffers;
    }

    protected static function parseJsonAsJobOffers(array $jsonArray): array
    {
        // Extract the data we require
        $jsonJobOffersKeys = [
            'id',
            'uzn_uznemums_nosaukums',
            'kla_profesija_nosaukums',
            'alga_no_lidz',
            'aktuala_lidz'
        ];
        $jsonJobOffers = self::extractRequiredArrayKeys(
            $jsonArray,
            $jsonJobOffersKeys,
            ''
        );

        $jobOffers = [];

        // Fill in the jobOffers array
        foreach ($jsonJobOffers as $jsonJobOffer) {
            // Parse the pay
            $pay = explode('-', $jsonJobOffer['alga_no_lidz']);
            if (isset($pay[1])) {
                $payMin = $pay[0];
                $payMax = $pay[1];
            }
            else {
                $payMin = 0;
                $payMax = $pay[0];
            }

            $jobOffers[] = new JobOffer(
                'nva_' . $jsonJobOffer['id'],
                $jsonJobOffer['uzn_uznemums_nosaukums'],
                '', // They don't store company logos off in any way
                $jsonJobOffer['kla_profesija_nosaukums'],
                $payMin,
                $payMax,
                self::generateJobOfferLink($jsonJobOffer['id']),
                $jsonJobOffer['aktuala_lidz'],
                'Y-m-d|'
            );
        }

        return $jobOffers;
    }

    private static function generateJobOfferLink(int $vacancyId): string
    {
        return self::JOBS_URL . $vacancyId;
    }
}
