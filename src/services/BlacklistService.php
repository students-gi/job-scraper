<?php

namespace services;

use Generator;
use repositories\ValidJobOfferRepository;
use repositories\BlacklistedJobOfferRepository;

class BlacklistService
{
    private static ValidJobOfferRepository $completeList;
    private static BlacklistedJobOfferRepository $blacklist;

    public function __construct()
    {
        self::$completeList = new ValidJobOfferRepository;
        self::$blacklist = new BlacklistedJobOfferRepository;
    }

    public function getFilteredJobOffers(): Generator
    {
        foreach (self::$completeList->getJobOffers() as $jobOffer) {
            error_log("Checking " . $jobOffer->getOfferId());
            $checkedOffer = self::$blacklist->searchJobOffers(
                [
                    // Since there can be the same offer on multiple sites,
                    // we check for more 'general' job descriptions, so to say
                    'jobTitle'      => $jobOffer->getJobTitle(),
                    'companyName'   => $jobOffer->getCompanyName()
                ]
            );
            if (!isset($checkedOffer)) {
                // We skip the blacklisted offers
                continue;
            }

            // Offer wasn't blacklisted; return it
            yield $jobOffer;
        }
    }

    public function addJobOfferToBlacklist($jobOfferId)
    {
        // Check if there is an offer like this blacklisted already
        $blacklistedOffer = self::$blacklist->searchJobOffers(
            ['offerId' => $jobOfferId]
        );
        if (isset($blacklistedOffer)) {
            // Prevent duplicates
            return;
        }

        // Check if this job offer exists
        $blacklistedOffer = self::$completeList->searchJobOffers(
            ['offerId' => $jobOfferId]
        );
        if (!isset($blacklistedOffer)) {
            // Prevent fakes
            return;
        }

        // Add the job offer to the blacklist
        self::$blacklist->addJobOffer($blacklistedOffer);
    }
}
