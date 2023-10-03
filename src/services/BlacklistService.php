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

    public function getSizesOfOfferRepos(): array
    {
        $sizes = [
            'all' => self::$completeList->getJobOfferCount(),
            'blacklist' => self::$blacklist->getJobOfferCount()
        ];
        return array_merge($sizes, [
            'whitelist' => ($sizes['all'] - $sizes['blacklist'])
        ]);
    }

    public function getFilteredJobOffers(): Generator
    {
        foreach (self::$completeList->getJobOffers() as $jobOffer) {
            $checkedOffer = self::$blacklist->searchIfJobOfferExists(
                [
                    // Since there can be the same offer on multiple sites,
                    // we check for more 'general' job descriptions, so to say
                    'jobTitle'      => $jobOffer->getJobTitle(),
                    'companyName'   => $jobOffer->getCompanyName()
                ]
            );
            if ($checkedOffer !== false) {
                // We skip the blacklisted offers
                continue;
            }

            // Offer wasn't blacklisted; return it
            yield $jobOffer;
        }
    }

    /**
     * Adds a job offer to the blacklist.
     *
     * This method checks if the provided job offer ID exists in the complete
     * job offer list.
     * If it does, and the offer is not already blacklisted, it adds the job
     * offer to the blacklist. This prevents duplicates and ensures that only
     * valid job offers are blacklisted.
     *
     * @param string $jobOfferId
     * The unique identifier of the job offer to blacklist.
     * @return boolean
     * Response whether the provided offer was blacklisted.
     * NOTE: true is returned even if the ID provided was a duplicate!
     */
    public function addJobOfferToBlacklist($jobOfferId): bool
    {
        // Check if there is an offer like this blacklisted already
        if (self::$blacklist->searchIfJobOfferExists(['offerId' => $jobOfferId]) !== false) {
            // Prevent duplicates
            return true;
        }

        // Check if this job offer exists
        $blacklistedOffer = self::$completeList->searchIfJobOfferExists(['offerId' => $jobOfferId]);
        if ($blacklistedOffer === false) {
            // Prevent fakes
            return false;
        }

        // Add the job offer to the blacklist
        self::$blacklist->addJobOffer($blacklistedOffer);
        return true;
    }
}
