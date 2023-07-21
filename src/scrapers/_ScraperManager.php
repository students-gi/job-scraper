<?php

namespace scrapers;

use SplObjectStorage;

class ScraperManager
{
    private SplObjectStorage $scrapers;

    public function __construct()
    {
        $this->scrapers = new SplObjectStorage();

        foreach ([
            new LikeItScraper(),
            new CvLvScraper(),
            new NvaScraper(),
            new PrakseLvScraper()
        ] as $scraper) {
            $this->scrapers->attach($scraper);
        }
    }

    public function launchScrapers(): array
    {
        $jobArray = [];
        foreach ($this->scrapers as $scraper) {
            $jobArray = array_merge($jobArray, $scraper->scrapeJobOffers());
        }
        return $jobArray;
    }
}
