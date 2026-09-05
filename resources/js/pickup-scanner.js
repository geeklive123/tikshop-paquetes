const scanner = document.querySelector('[data-qr-scanner]');

if (scanner) {
    const video = scanner.querySelector('[data-scanner-video]');
    const status = scanner.querySelector('[data-scanner-status]');
    const startButton = scanner.querySelector('[data-scanner-start]');
    const resolveForm = scanner.querySelector('[data-scanner-form]');
    const codeInput = scanner.querySelector('[data-scanner-code]');
    let codeReader;
    let controls;
    let processing = false;

    const stopScanner = () => {
        controls?.stop();
        controls = undefined;
    };

    const showStatus = (message, isError = false) => {
        status.textContent = message;
        status.classList.toggle('text-tik-red-dark', isError);
        status.classList.toggle('text-gray-600', !isError);
    };

    const startScanner = async () => {
        stopScanner();
        processing = false;
        startButton.disabled = true;
        showStatus('Iniciando cámara…');

        try {
            const { BrowserQRCodeReader } = await import('@zxing/browser');
            codeReader ??= new BrowserQRCodeReader(undefined, { delayBetweenScanAttempts: 250 });
            controls = await codeReader.decodeFromConstraints(
                { video: { facingMode: { ideal: 'environment' } }, audio: false },
                video,
                (result) => {
                    if (!result || processing) {
                        return;
                    }

                    processing = true;
                    stopScanner();
                    showStatus('Código detectado. Validando…');
                    codeInput.value = result.getText();
                    resolveForm.requestSubmit();
                },
            );

            showStatus('Leyendo… apunta la cámara al código QR.');
        } catch (error) {
            showStatus('No se pudo acceder a la cámara. Revisa los permisos e inténtalo nuevamente.', true);
        } finally {
            startButton.disabled = false;
        }
    };

    startButton.addEventListener('click', startScanner);
    window.addEventListener('pagehide', stopScanner);
    startScanner();
}
