/**
 * PARSU-GPass: Digital Canvas Signature Pad
 * Full Touch & Mouse Support, Undo, Clear, Base64 PNG Export
 */

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('clearSignatureBtn');
    const undoBtn = document.getElementById('undoSignatureBtn');
    const signatureForm = document.getElementById('signatureForm');
    const signatureDataInput = document.getElementById('signatureData');
    const statusLabel = document.getElementById('signatureStatus');

    let isDrawing = false;
    let strokes = []; // Array of strokes for undo support
    let currentStroke = [];

    // Setup canvas resolution and styling
    function setupCanvas() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * 2;
        canvas.height = 200 * 2;
        ctx.scale(2, 2);

        ctx.strokeStyle = '#000035';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    setupCanvas();
    window.addEventListener('resize', () => {
        // Redraw on resize
        const currentData = strokes.slice();
        setupCanvas();
        redrawAll(currentData);
    });

    function getCoords(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        const coords = getCoords(e);
        currentStroke = [coords];
        ctx.beginPath();
        ctx.moveTo(coords.x, coords.y);
    }

    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const coords = getCoords(e);
        currentStroke.push(coords);
        ctx.lineTo(coords.x, coords.y);
        ctx.stroke();
    }

    function stopDrawing(e) {
        if (!isDrawing) return;
        isDrawing = false;
        if (currentStroke.length > 0) {
            strokes.push(currentStroke);
            currentStroke = [];
            if (statusLabel) {
                statusLabel.textContent = '✓ Specimen signature captured.';
                statusLabel.style.color = '#000035';
            }
        }
    }

    // Mouse Listeners
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    window.addEventListener('mouseup', stopDrawing);

    // Touch Listeners
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    window.addEventListener('touchend', stopDrawing);

    // Clear Pad
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            strokes = [];
            if (statusLabel) {
                statusLabel.textContent = 'Pad cleared. Draw signature to proceed.';
                statusLabel.style.color = '#6b7280';
            }
        });
    }

    // Undo Stroke
    if (undoBtn) {
        undoBtn.addEventListener('click', function () {
            if (strokes.length > 0) {
                strokes.pop();
                redrawAll(strokes);
                if (strokes.length === 0 && statusLabel) {
                    statusLabel.textContent = 'Pad is empty. Draw signature to proceed.';
                    statusLabel.style.color = '#6b7280';
                }
            }
        });
    }

    function redrawAll(strokeList) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        strokeList.forEach(stroke => {
            if (stroke.length === 0) return;
            ctx.beginPath();
            ctx.moveTo(stroke[0].x, stroke[0].y);
            for (let i = 1; i < stroke.length; i++) {
                ctx.lineTo(stroke[i].x, stroke[i].y);
            }
            ctx.stroke();
        });
    }

    // Check if canvas is blank
    function isCanvasBlank() {
        return strokes.length === 0;
    }

    // Handle Form Submit
    if (signatureForm) {
        signatureForm.addEventListener('submit', function (e) {
            if (isCanvasBlank()) {
                // If there was already a saved signature in draft, allow proceeding
                const hasExisting = document.querySelector('.saved-signature-preview');
                if (!hasExisting) {
                    e.preventDefault();
                    alert('Please draw your digital signature before saving.');
                    return;
                }
            } else {
                // Export base64 PNG data URL
                const dataURL = canvas.toDataURL('image/png');
                if (signatureDataInput) {
                    signatureDataInput.value = dataURL;
                }
            }
        });
    }
});


