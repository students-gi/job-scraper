<?php

namespace services;

use Generator;
use repositories\ValidJobOfferRepository;
use repositories\BlacklistedJobOfferRepository;
use repositories\FilteredJobOfferRepository;

class BlacklistService
{
    private static ValidJobOfferRepository $completeList;
    private static BlacklistedJobOfferRepository $blacklist;

    public function __construct()
    {
        self::$completeList = new ValidJobOfferRepository;
        self::$blacklist = new BlacklistedJobOfferRepository();
    }

    public function getFilteredJobOffers(): Generator
    {
        foreach (self::$completeList as $jobOffer) {
            $checkedOffer =
                self::$blacklist->getJobOfferById(
                    $jobOffer->getOfferId()
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
        $blacklistedOffer = self::$blacklist->getJobOfferById($jobOfferId);
        if (!isset($blacklistedOffer)) {
            return;
        }

        // Check if this job offer exists
        $validOffers = new ValidJobOfferRepository();
        $blacklistedOffer = $validOffers->getJobOfferById($jobOfferId);
        if (!isset($blacklistedOffer)) {
            return;
        }

        // Add the job offer to the blacklist
        self::$blacklist->addJobOffer($blacklistedOffer);
    }
}
