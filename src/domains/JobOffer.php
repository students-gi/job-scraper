<?php

namespace domains;

use DateTime;
use InvalidArgumentException;

class JobOffer
{
    private string $companyName;
    private string $companyLogo;
    private string $jobTitle;
    private int $jobPayMin;
    private int $jobPayMax;
    private string $offerLink;
    private DateTime $offerDeadline;

    public function __construct(
        string $companyName,
        string $companyLogo,
        string $jobTitle,
        string $jobPayInterval,
        string $offerLink,
        string $offerDeadline,
        string $offerDeadlineFormat
    ) {
        $this->setCompanyName($companyName);
        $this->setCompanyLogo($companyLogo);
        $this->setJobTitle($jobTitle);
        $this->setJobPay($jobPayInterval);
        $this->setOfferLink($offerLink);
        $this->setOfferDeadline($offerDeadline, $offerDeadlineFormat);
    }

    // Setters
    private function setCompanyName(string $companyName): void
    {
        $this->companyName = self::decodeUnicode($companyName);
    }

    private function setCompanyLogo(string $companyLogo): void
    {
        $companyLogo = self::decodeUnicode($companyLogo);

        // Validate if it's a valid URL
        $companyLogo = JobOffer::validateUrl($companyLogo);
        if ($companyLogo === false) {
            throw new InvalidArgumentException("Invalid URL for companyLogo: {$companyLogo}");
        }

        $this->companyLogo = $companyLogo;
    }

    private function setJobTitle(string $jobTitle): void
    {
        $this->jobTitle = self::decodeUnicode($jobTitle);
    }

    private function setJobPay(string $jobPay): void
    {
        $jobPay = self::decodeUnicode($jobPay);

        // Extract the lowest estimate from the jobPay string (e.g., "1000$-3000$")
        $jobPayParts = explode('-', $jobPay);

        // Remove currency signs and any leading/trailing whitespace
        if (isset($jobPayParts[1])) {
            $minPayStr = trim($jobPayParts[0], '$€£ ');
            $maxPayStr = trim($jobPayParts[1], '$€£ ');
        } else {
            $minPayStr = 0;
            $maxPayStr = trim($jobPayParts[0], '$€£ ');
        }

        // Convert to integer
        $this->jobPayMin = (int)$minPayStr;
        $this->jobPayMax = (int)$maxPayStr;
    }

    private function setOfferLink(string $offerLink): void
    {
        $offerLink = self::decodeUnicode($offerLink);

        // Validate if it's a valid URL
        $offerLink = JobOffer::validateUrl($offerLink);
        if ($offerLink === false) {
            throw new InvalidArgumentException("Invalid URL for offerLink: {$offerLink}");
        }
        $this->offerLink = $offerLink;
    }

    private function setOfferDeadline(string $offerDeadline, $offerDeadlineFormat): void
    {
        $offerDeadline = self::decodeUnicode($offerDeadline);

        $nullDeadline = DateTime::createFromFormat("Y-m-d|", "0000-01-01");
        $validDeadline = DateTime::createFromFormat($offerDeadlineFormat, $offerDeadline);

        $this->offerDeadline = ($validDeadline === false) ? $nullDeadline : $validDeadline;
    }


    // Getters
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getCompanyLogo(): string
    {
        return $this->companyLogo;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function getJobPayMin(): int
    {
        return $this->jobPayMin;
    }

    public function getJobPayMax(): int
    {
        return $this->jobPayMax;
    }

    public function getOfferLink(): string
    {
        return $this->offerLink;
    }

    public function getOfferDeadline(): DateTime
    {
        return $this->offerDeadline;
    }

    public function getFormattedOfferDeadline(): String
    {
        return $this->getOfferDeadline()->format("Y-m-d");
    }

    // Class-specific validation functions
    private static function validateUrl(string $url): string | FALSE
    {
        // Validate URL using a basic regular expression pattern
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    private static function decodeUnicode(string $encodedString): string
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $encodedString);
    }

    // Default object class overrides
    public function __equals($other): bool
    {
        if (!$other instanceof JobOffer) {
            return false;
        }

        return
            $this->getCompanyName() === $other->getCompanyName() &&
            $this->getJobTitle()    === $other->getJobTitle()    &&
            $this->getJobPayMin()   === $other->getJobPayMin()   &&
            $this->getJobPayMax()   === $other->getJobPayMax();
    }

    public function __hash(): string
    {
        return md5(
            $this->getCompanyName() .
                $this->getCompanyLogo() .
                $this->getJobTitle() .
                $this->getJobPayMin() .
                $this->getOfferLink() .
                $this->getFormattedOfferDeadline()
        );
    }
}
