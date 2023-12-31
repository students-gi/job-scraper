<?php

const DIR_SVG = DIR_PUBLIC . "/images/svg";

function embedSvgElement($svgFilePath): string
{
    if (!file_exists($svgFilePath)) {
        return "<p>Error: SVG file not found.</p>";
    }
    return file_get_contents($svgFilePath);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Job Offers</title>
    <link rel="stylesheet" href="/public/style/css/main.min.css">
    <link rel="stylesheet" href="/public/style/css/offers.min.css">
    <script type="text/javascript" src="/public/scripts/js/sort.js" defer></script>
    <script type="text/javascript">
        let sortAscending = true;

        function custom_querySelector(query) {
            let returnElement = null;
            for (const element of this) {
                if (element.matches(query)) {
                    returnElement = element;
                    break;
                }
                returnElement = element.querySelector(query);
                if (returnElement !== null) {
                    break;
                }
            }

            return returnElement;
        }
        NodeList.prototype.querySelector = custom_querySelector;

        function handleSortClick(sortType) {
            const icons = document.querySelectorAll('#sortContainer .icon');
            let clickedIcon = icons.querySelector(`.${sortType}`);

            // Hiding the unsorted indicator
            icons.querySelector('.unsorted').style.display = 'none';

            // Check if the sort direction is toggled
            if (clickedIcon.classList.contains('active')) {
                sortAscending = !sortAscending;
            }

            // Reassign the 'active' class
            icons.forEach((icon) => {
                icon.classList.remove('active');
            });
            clickedIcon.classList.add('active');

            // Call the sorting function based on the sortType
            if (sortType === 'payment') {
                sortJobOffers(compareByPay, sortAscending);
            } else if (sortType === 'deadline') {
                sortJobOffers(compareByDeadline, sortAscending);
            }

            // Update the sort direction indicator icons
            const ascendingIcon = icons.querySelector('.ascending');
            const descendingIcon = icons.querySelector('.descending');
            ascendingIcon.style.display = sortAscending ? 'none' : 'block';
            descendingIcon.style.display = sortAscending ? 'block' : 'none';
        }

        function blacklistJobOffer(buttonElement) {
            // Get the job offer ID from the element's ID attribute
            const jobOfferContainer = buttonElement.parentElement;
            const jobOfferId = jobOfferContainer.id;

            // Make the request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            var data = 'action=blacklist&jobOfferId=' + encodeURIComponent(jobOfferId);
            xhr.send(data);

            // Handle the server response
            if (xhr.status === 200) {
                // Remove the job offer container from the DOM
                jobOfferContainer.remove();
            } else {
                // Display an error message
                alert('Failed to blacklist the job offer.');
            }
        }

        // Highlight the '.job-offer' container when it's visited
        function highlightJobOffer(event) {
            var closestJobOffer = event.target.closest('.job-offer');
            closestJobOffer.classList.add('visited');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add a click event listener to each link
            var links = document.querySelectorAll('#jobContainer .job-title a');
            links.forEach(function(link) {
                link.addEventListener('click', highlightJobOffer);
            });
        });
    </script>
</head>

<body>
    <?php $jobCounts = $jobOfferRepository->getSizesofOfferRepos(); ?>
    <h1>Found Job Offers (<?= $jobCounts['whitelist'] ?> / <?= $jobCounts['all'] ?>)</h1>
    <nav id="sortContainer">
        <div id="sorting">
            <button class="icon payment" onclick="handleSortClick('payment')">
                <?= embedSvgElement(DIR_SVG . "/hand-coins.svg") . PHP_EOL ?>
            </button>
            <button class="icon deadline" onclick="handleSortClick('deadline')">
                <?= embedSvgElement(DIR_SVG . "/calendar-x.svg") . PHP_EOL ?>
            </button>
        </div>
        <div id="order-indicator">
            <div class="icon unsorted">
                <?= embedSvgElement(DIR_SVG . "/sort-unsorted.svg") . PHP_EOL ?>
            </div>
            <div class="icon ascending" style="display:none;">
                <?= embedSvgElement(DIR_SVG . "/sort-ascending.svg") . PHP_EOL ?>
            </div>
            <div class="icon descending" style="display:none;">
                <?= embedSvgElement(DIR_SVG . "/sort-descending.svg") . PHP_EOL ?>
            </div>
        </div>
    </nav>
    <div id="jobContainer">
        <?php
        // Display saved job offers
        foreach ($jobOfferRepository->getFilteredJobOffers() as $jobOffer) : ?>
            <div id="<?= $jobOffer->getOfferId() ?>" class="job-offer">
                <div class="job-details">
                    <div class="company-logo">
                        <img src="<?= $jobOffer->getCompanyLogo() ?>" alt="<?= $jobOffer->getCompanyName() ?> logo" />
                    </div>
                    <div class="job-overview">
                        <div class="job-title">
                            <a href="<?= $jobOffer->getOfferLink() ?>" target="_blank">
                                <?= $jobOffer->getJobTitle() . PHP_EOL ?>
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
                        }
                        echo PHP_EOL;
                        ?>
                    </div>
                </div>
                <div class="offer-deadline">
                    <?= $jobOffer->getFormattedOfferDeadline() . PHP_EOL  ?>
                </div>
                <button class="icon offer-blacklist" onclick="blacklistJobOffer(this)">
                    <?= embedSvgElement(DIR_SVG . "/trash.svg") . PHP_EOL ?>
                </button>
            </div>
        <?php endforeach; ?>

    </div>
</body>

</html>