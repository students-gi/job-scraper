<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'domains\\JobOffer' => $baseDir . '/src/domains/JobOffer.php',
    'repositories\\AbstractJobOfferRepository' => $baseDir . '/src/repositories/_AbstractJobOfferRepository.php',
    'repositories\\BlacklistedJobOfferRepository' => $baseDir . '/src/repositories/BlacklistedJobOfferRepository.php',
    'repositories\\ValidJobOfferRepository' => $baseDir . '/src/repositories/ValidJobOfferRepository.php',
    'scrapers\\CvLvScraper' => $baseDir . '/src/scrapers/cv_lv.php',
    'scrapers\\HtmlScraper' => $baseDir . '/src/scrapers/_HtmlScraper.php',
    'scrapers\\LikeItScraper' => $baseDir . '/src/scrapers/likeIT_lv.php',
    'scrapers\\NvaScraper' => $baseDir . '/src/scrapers/cvvp_nva_lv.php',
    'scrapers\\PrakseLvScraper' => $baseDir . '/src/scrapers/prakse_lv.php',
    'scrapers\\Scraper' => $baseDir . '/src/scrapers/_Scraper.php',
    'scrapers\\ScraperManager' => $baseDir . '/src/scrapers/_ScraperManager.php',
    'scrapers\\SsLvScraper' => $baseDir . '/src/scrapers/ss_lv.php',
    'services\\BlacklistService' => $baseDir . '/src/services/BlacklistService.php',
);
