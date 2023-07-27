<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Job Offers</title>
    <link rel="stylesheet" href="style/css/main.min.css">
    <link rel="stylesheet" href="style/css/offers.min.css">
</head>

<body>
    <div class="container">
        <h1>Saved Job Offers</h1>
        <?php
        // Include code to fetch saved job offers from the database/data files
        $savedJobOffers = $jobOfferRepository->getJobOffers();

        // Display saved job offers
        foreach ($savedJobOffers as $jobOffer) : ?>
            <div id="<?= $jobOffer->getOfferId() ?>" class="job-offer">
                <div class="job-details">
                    <div class="company-logo">
                        <img src="<?= $jobOffer->getCompanyLogo() ?>" alt="<?= $jobOffer->getCompanyName() ?> logo">
                    </div>
                    <div class="job-overview">
                        <div class="job-title">
                            <a href="<?= $jobOffer->getOfferLink() ?>" target="_blank">
                                <?= $jobOffer->getJobTitle() ?>
                            </a>
                        </div>
                        <div class="company-name"><?= $jobOffer->getCompanyName() ?></div>
                    </div>
                    <div class="job-payment">
                        <?php if ($jobOffer->getJobPayMax() == 0) {
                            echo $jobOffer->getJobPayMin();
                        } elseif ($jobOffer->getJobPayMin() == 0) {
                            echo $jobOffer->getJobPayMax();
                        } else {
                            echo $jobOffer->getJobPayMin() . '-' . $jobOffer->getJobPayMax();
                        } ?>
                    </div>
                </div>
                    <div class="offer-deadline">
                        <?= $jobOffer->getFormattedOfferDeadline() ?>
                    </div>
            </div>
        <?php endforeach; ?>

    </div>
</body>

<script id="REPO_DUMP"><?php var_dump($savedJobOffers) ?></script>

</html>