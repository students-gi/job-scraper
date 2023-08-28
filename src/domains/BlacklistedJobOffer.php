<?php

namespace domains;

class BlacklistedJobOffer
{
    private string $companyName;
    private string $jobTitle;
    private int $jobPayMin;
    private int $jobPayMax;

    public function __construct(
        ?string $companyName = null,
        ?string $jobTitle = null,
        ?string $jobPayMin = null,
        ?string $jobPayMax = null
    ) {
        $this->setCompanyName($companyName);
        $this->setJobTitle($jobTitle);
        $this->setJobPayMin($jobPayMin);
        $this->setJobPayMax($jobPayMax);
    }


    // Setters
    private function setCompanyName(string $companyName): void
    {
        $this->companyName = self::decodeUnicode($companyName);
    }

    private function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = self::decodeUnicode($jobTitle);
    }

    private function setJobPayMin(?string $jobPayMin): void
    {
        $this->jobPayMin = (int) self::decodeUnicode($jobPayMin);
    }

    private function setJobPayMax(?string $jobPayMax): void
    {
        $this->jobPayMax = (int) self::decodeUnicode($jobPayMax);
    }



    // Getters
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function getJobPayMin(): ?int
    {
        return $this->jobPayMin;
    }

    public function getJobPayMax(): ?int
    {
        return $this->jobPayMax;
    }

    // Class-specific functions
    public function filtersJobOffer(JobOffer $comparedOffer): bool
    {
        // Comparisons
        if ((($this->getCompanyName() !== null)
                && ($this->getCompanyName() !== $comparedOffer->getCompanyName()))
            ||
            (($this->getJobTitle() !== null)
                && ($this->getJobTitle() !== $comparedOffer->getJobTitle()))
            ||
            ((($this->getJobPayMin() !== null) || ($this->getJobPayMin() !== 0))
                && ($this->getJobPayMin() !== $comparedOffer->getJobPayMin()))
            ||
            ((($this->getJobPayMax() !== null) || ($this->getJobPayMax() !== 0))
                && ($this->getJobPayMax() !== $comparedOffer->getJobPayMax()))
        ) {
            return false;
        }

        return true;
    }

    // Class-specific validation functions
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
                $this->getJobTitle() .
                $this->getJobPayMin() .
                $this->getJobPayMax()
        );
    }

    public function __toString(): string
    {
        $output = "Job offer object {" . PHP_EOL;
        $output .= "Company Name: "     . $this->getCompanyName()   . PHP_EOL;
        $output .= "Job Title: "        . $this->getJobTitle()      . PHP_EOL;
        $output .= "Min pay offered: "  . $this->getJobPayMin()     . PHP_EOL;
        $output .= "Max pay offered: "  . $this->getJobPayMax()     . PHP_EOL;
        $output .= "}";

        return $output;
    }
}
