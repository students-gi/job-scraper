<?php namespace scrapers;

abstract class Scraper
{
    /****
     * Should return an array of JobOffer elements to be added into JobOfferRepository
    **/
    abstract public static function scrapeJobOffers(): array;
}
