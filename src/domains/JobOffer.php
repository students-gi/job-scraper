<?php

namespace domains;

use DateTime;
use InvalidArgumentException;

class JobOffer
{
    private string $offerId;
    private string $companyName;
    private string $companyLogo;
    private string $jobTitle;
    private int $jobPayMin;
    private int $jobPayMax;
    private string $offerLink;
    private DateTime $offerDeadline;

    public function __construct(
        string $offerId,
        string $companyName,
        string $companyLogo,
        string $jobTitle,
        string $jobPayMin,
        string $jobPayMax,
        string $offerLink,
        string $offerDeadline,
        string $offerDeadlineFormat
    ) {
        $this->setOfferId($offerId);
        $this->setCompanyName($companyName);
        $this->setCompanyLogo($companyLogo);
        $this->setJobTitle($jobTitle);
        $this->setJobPayMin($jobPayMin);
        $this->setJobPayMax($jobPayMax);
        $this->setOfferLink($offerLink);
        $this->setOfferDeadline($offerDeadline, $offerDeadlineFormat);
    }

    // Setters
    private function setOfferId(string $offerId): void
    {
        $this->offerId = self::decodeUnicode($offerId);
    }

    private function setCompanyName(string $companyName): void
    {
        $parsedCompanyName = self::decodeUnicode($companyName);

        // Replacing company descriptors with acronyms
        $parsedCompanyName =  str_replace([
            "Akciju sabiedrība",
            "akciju sabiedrība"
        ], "AS", $parsedCompanyName);
        $parsedCompanyName =  str_replace([
            "Sabiedrība ar ierobežotu atbildību",
            "sabiedrība ar ierobežotu atbildību"
        ], "SIA", $parsedCompanyName);

        // Relocating the company names to the beginning for consistency
        $acronymArray = ["AS", "SIA"];
        foreach ($acronymArray as $acronym) {
            $lowerAcronym   = strtolower($acronym);
            $lowerCompany   = strtolower($parsedCompanyName);
            $phrasePosition = strrpos($lowerCompany, ' ' . $lowerAcronym);
            $acronymLength  = strlen($lowerAcronym) + 1;
            $companyLength  = strlen($lowerCompany);

            // Check if the phrase is at the end of the text
            if ($phrasePosition !== false && ($phrasePosition + $acronymLength) === $companyLength) {
                // Move the phrase to the front
                $parsedCompanyName = $acronym . ' '
                    . substr($parsedCompanyName, 0, $companyLength - $acronymLength);
                break; // We move only one phrase to the front if found
            }
        }

        // Trimming leftover characters
        $parsedCompanyName = trim($parsedCompanyName,"\t\n\r\0\x0b ,");

        $this->companyName = $parsedCompanyName;
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

    private function setJobPayMin(string $jobPayMin): void
    {
        $this->jobPayMin = self::parseJobPay($jobPayMin);
    }

    private function setJobPayMax(string $jobPayMax): void
    {
        $this->jobPayMax = self::parseJobPay($jobPayMax);
    }

    private static function parseJobPay(string $jobPay): int
    {
        $jobPay = self::decodeUnicode($jobPay);
        return (int) trim($jobPay, '$€£ ');
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

    private function setOfferDeadline(string $offerDeadline, string $offerDeadlineFormat): void
    {
        $offerDeadline = self::decodeUnicode($offerDeadline);

        $nullDeadline = DateTime::createFromFormat("Y-m-d|", "0000-01-01");
        $validDeadline = DateTime::createFromFormat($offerDeadlineFormat, $offerDeadline);

        $this->offerDeadline = ($validDeadline === false) ? $nullDeadline : $validDeadline;
    }


    // Getters
    public function getOfferId(): string
    {
        return $this->offerId;
    }

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
            $this->getOfferId()     === $other->getOfferId() &&
            $this->getCompanyName() === $other->getCompanyName() &&
            $this->getJobTitle()    === $other->getJobTitle()    &&
            $this->getJobPayMin()   === $other->getJobPayMin()   &&
            $this->getJobPayMax()   === $other->getJobPayMax();
    }

    public function __hash(): string
    {
        return md5(
                $this->getOfferId() .
                $this->getCompanyName() .
                $this->getCompanyLogo() .
                $this->getJobTitle() .
                $this->getJobPayMin() .
                $this->getOfferLink() .
                $this->getFormattedOfferDeadline()
        );
    }

    public function __toString(): string
    {
        $output = "Job offer object {" . PHP_EOL;
        $output .= "Offer ID: "         . $this->getOfferId()       . PHP_EOL;
        $output .= "Company Name: "     . $this->getCompanyName()   . PHP_EOL;
        $output .= "Company Logo URL: " . $this->getCompanyLogo()   . PHP_EOL;
        $output .= "Job Title: "        . $this->getJobTitle()      . PHP_EOL;
        $output .= "Min pay offered: "  . $this->getJobPayMin()     . PHP_EOL;
        $output .= "Max pay offered: "  . $this->getJobPayMax()     . PHP_EOL;
        $output .= "Offer Link: "       . $this->getOfferLink()     . PHP_EOL;
        $output .= "Offer Deadline: "   . $this->getFormattedOfferDeadline() . PHP_EOL;
        $output .= "}";

        return $output;
    }
}
