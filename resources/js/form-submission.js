import SignaturePad from 'signature_pad';

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas');
    const form = document.getElementById('formSubmission');

    if (!canvas) {
        return;
    }

    if (!form) {
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

    form.addEventListener('submit', (e) => {
        if (signaturePad.isEmpty()) {
            e.preventDefault();
            alert(form.dataset.signatureRequired);
            return;
        }

        document.getElementById('signatureInput').value = signaturePad.toDataURL('image/png');
    });
});
