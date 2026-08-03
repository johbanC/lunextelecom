import SignaturePad from 'signature_pad';

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas');
    const form = document.getElementById('agreementForm');

    if (!canvas || !form) {
        return;
    }

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear();
    }

    const signaturePad = new SignaturePad(canvas, {
        penColor: 'rgb(0, 36, 81)',
    });

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    document.getElementById('clearSignature')?.addEventListener('click', () => {
        signaturePad.clear();
    });

    const qtyInputs = document.querySelectorAll('.qty-input');
    const grandTotalElement = document.getElementById('grandTotal');

    function calculateTotals() {
        let grandTotal = 0;

        document.querySelectorAll('.item-row').forEach((row) => {
            const rate = parseFloat(row.dataset.rate);
            const qtyInput = row.querySelector('.qty-input');
            const qty = parseInt(qtyInput.value, 10) || 0;
            const rowTotal = rate * qty;

            row.querySelector('.row-total').textContent = `$${rowTotal.toFixed(2)}`;
            grandTotal += rowTotal;
        });

        grandTotalElement.textContent = `$${grandTotal.toFixed(2)}`;
    }

    qtyInputs.forEach((input) => input.addEventListener('input', calculateTotals));
    calculateTotals();

    form.addEventListener('submit', (e) => {
        if (signaturePad.isEmpty()) {
            e.preventDefault();
            alert('Por favor agrega tu firma antes de enviar.');
            return;
        }

        document.getElementById('signatureInput').value = signaturePad.toDataURL('image/png');
    });
});
