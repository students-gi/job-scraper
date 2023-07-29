/***********************************************************
******                DOM  "CONSTANTS"                ******
************************************************************/
const containerId = "jobContainer";
const jobOffersContainer = document.getElementById(containerId);
if (!jobOffersContainer) {
    console.error(`Container with id '${containerId}' not found.`);
}
let jobOfferArray = null;

console.log("Sorting file got loaded");



/***********************************************************
******              VARIABLE  EXTRACTION              ******
************************************************************/
function refreshJobOffers() {
    const jobOfferElements = jobOffersContainer.getElementsByClassName("job-offer");
    jobOfferArray = Array.from(jobOfferElements); // For easier manipulation
}

function extractDeadlineFromJob(jobElement) {
    let deadlineElement = jobElement.querySelector(".offer-deadline");
    let deadlineText = deadlineElement ? deadlineElement.textContent.trim() : "";
    return Date.parse(deadlineText); // Convert the deadline text to a timestamp
}

function extractPayFromJob(jobElement) {
    let payElement = jobElement.querySelector(".job-payment");
    let payText = payElement ? payElement.textContent.trim() : "";
    let payParts = payText.split("-");
    let minPay = parseInt(payParts[0].trim());
    let maxPay = payParts.length === 2 ? parseInt(payParts[1].trim()) : minPay;
    return { min: minPay, max: maxPay };
}



/***********************************************************
******                     SORTING                    ******
************************************************************/
function sortJobOffers(compareFunction, descending = false) {
    refreshJobOffers();

    jobOfferArray.sort((a, b) => {
        const result = compareFunction(a, b);
        return descending ? -result : result;
    });

    // Append the sorted job offer elements back to the container in the new order
    jobOfferArray.forEach(element => {
        jobOffersContainer.appendChild(element);
    });
}

function compareByDeadline(a, b) {
    const deadlineA = extractDeadlineFromJob(a);
    const deadlineB = extractDeadlineFromJob(b);
    // Times are different in what counts as "ascending"
    return deadlineB - deadlineA;
}

function compareByPay(a, b) {
    const payA = extractPayFromJob(a);
    const payB = extractPayFromJob(b);

    // Compare by the minimum pay first
    if (payA.min !== payB.min) {
        return payA.min - payB.min;
    }

    // If the minimum pay is the same, compare by the maximum pay
    return payA.max - payB.max;
}
