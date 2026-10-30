/**
 * PARSU-GPass: Dynamic Document Requirements Upload Validator
 */

document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('.file-input-check');
    const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
    const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'pdf'];

    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const ext = file.name.split('.').pop().toLowerCase();

                // Extension check
                if (!ALLOWED_EXTS.includes(ext)) {
                    alert(`Invalid file extension (.${ext}). Only JPG, JPEG, PNG, or PDF files are accepted.`);
                    this.value = '';
                    return;
                }

                // Size check
                if (file.size > MAX_SIZE) {
                    alert(`The file "${file.name}" exceeds the 5MB size limit (${(file.size / (1024 * 1024)).toFixed(2)} MB). Please select a compressed file.`);
                    this.value = '';
                    return;
                }

                // Visual card feedback
                const parentCard = this.closest('.upload-item-card');
                if (parentCard) {
                    parentCard.classList.add('has-uploaded');
                    parentCard.classList.remove('pending-upload');
                }
            }
        });
    });
});

