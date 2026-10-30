/**
 * PARSU-GPass: Dynamic Ownership Fields Handler
 */

document.addEventListener('DOMContentLoaded', function () {
    const radioOptions = document.querySelectorAll('input[name="ownership_category"]');
    const cards = document.querySelectorAll('.ownership-card-option');

    const fieldOwnerName = document.getElementById('fieldOwnerName');
    const fieldRelationship = document.getElementById('fieldRelationship');
    const fieldPreviousOwner = document.getElementById('fieldPreviousOwner');
    const fieldBuyerName = document.getElementById('fieldBuyerName');

    const inputOwner = document.getElementById('owner_name');
    const inputRel = document.getElementById('relationship_to_applicant');
    const inputPrev = document.getElementById('previous_owner_name');
    const inputBuyer = document.getElementById('buyer_name');

    function updateFields(selectedCategory) {
        // Update card visual selection
        cards.forEach(card => card.classList.remove('selected'));
        const checkedRadio = document.querySelector(`input[name="ownership_category"][value="${selectedCategory}"]`);
        if (checkedRadio) {
            checkedRadio.closest('.ownership-card-option').classList.add('selected');
        }

        // Reset visibility and required state
        if (fieldOwnerName) fieldOwnerName.style.display = 'none';
        if (fieldRelationship) fieldRelationship.style.display = 'none';
        if (fieldPreviousOwner) fieldPreviousOwner.style.display = 'none';
        if (fieldBuyerName) fieldBuyerName.style.display = 'none';

        if (inputOwner) inputOwner.required = false;
        if (inputRel) inputRel.required = false;
        if (inputPrev) inputPrev.required = false;
        if (inputBuyer) inputBuyer.required = false;

        switch (selectedCategory) {
            case 'first_owner_own_name':
                // 3 documents: OR, CR, APPLICANT_DL
                // No extra owner fields required
                break;

            case 'first_owner_relative_name':
                // 5 documents: OR, CR, VEHICLE_OWNER_DL, APPLICANT_DL, AUTHORIZATION_LETTER
                if (fieldOwnerName) fieldOwnerName.style.display = 'block';
                if (fieldRelationship) fieldRelationship.style.display = 'block';
                if (inputOwner) inputOwner.required = true;
                if (inputRel) inputRel.required = true;
                break;

            case 'secondhand_applicant_buyer':
                // 5 documents: OR, CR, DEED_OF_SALE, PREVIOUS_OWNER_DL, APPLICANT_DL
                if (fieldPreviousOwner) fieldPreviousOwner.style.display = 'block';
                if (inputPrev) inputPrev.required = true;
                break;

            case 'secondhand_applicant_not_buyer':
                // 7 documents: OR, CR, DEED_OF_SALE, PREVIOUS_OWNER_DL, APPLICANT_DL, AUTHORIZATION_LETTER, BUYER_DL
                if (fieldPreviousOwner) fieldPreviousOwner.style.display = 'block';
                if (fieldBuyerName) fieldBuyerName.style.display = 'block';
                if (fieldRelationship) fieldRelationship.style.display = 'block';
                if (inputPrev) inputPrev.required = true;
                if (inputBuyer) inputBuyer.required = true;
                if (inputRel) inputRel.required = true;
                break;
        }
    }

    radioOptions.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                updateFields(this.value);
            }
        });
    });

    // Initialize with currently checked radio
    const currentlyChecked = document.querySelector('input[name="ownership_category"]:checked');
    if (currentlyChecked) {
        updateFields(currentlyChecked.value);
    }
});

