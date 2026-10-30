/**
 * PARSU-GPass: Application Wizard Actions & Flow Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // Final submit form confirmation
    const finalSubmitForm = document.getElementById('finalSubmitForm');
    if (finalSubmitForm) {
        finalSubmitForm.addEventListener('submit', function (e) {
            const agreeCheckbox = document.getElementById('undertakingAgree');
            if (agreeCheckbox && !agreeCheckbox.checked) {
                e.preventDefault();
                alert('You must check the certification and undertaking box before submitting.');
                return;
            }

            const confirmed = confirm('Are you sure you want to submit your gate pass application? All information and documents will be reviewed by Campus Security.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    // Plate number auto-uppercase
    const plateInput = document.getElementById('plate_number');
    if (plateInput) {
        plateInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }

    // License number auto-uppercase
    const licenseInput = document.getElementById('license_number');
    if (licenseInput) {
        licenseInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
});

