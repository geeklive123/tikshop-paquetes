const shareButtons = document.querySelectorAll('[data-share-pickup-qr]');

const downloadBlob = (blob, filename) => {
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
};

const showStatus = (button, message) => {
    const status = button.closest('section')?.querySelector('[data-pickup-qr-share-status]');

    if (!status) {
        return;
    }

    status.textContent = message;
    status.classList.remove('hidden');
};

shareButtons.forEach((button) => {
    button.addEventListener('click', async () => {
        const downloadUrl = button.dataset.downloadUrl;
        const filename = button.dataset.filename;
        const whatsAppUrl = button.dataset.whatsappUrl;

        if (!downloadUrl || !filename || !whatsAppUrl) {
            return;
        }

        const canCreateFile = typeof File === 'function';
        const fileShareProbe = canCreateFile ? new File([''], filename, { type: 'image/png' }) : null;
        const canShareFile = fileShareProbe !== null
            && typeof navigator.share === 'function'
            && typeof navigator.canShare === 'function'
            && navigator.canShare({ files: [fileShareProbe] });
        const whatsAppWindow = canShareFile ? null : window.open(whatsAppUrl, '_blank', 'noopener,noreferrer');
        button.disabled = true;

        try {
            const response = await fetch(downloadUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'image/png' },
            });
            const contentType = response.headers.get('content-type') ?? '';

            if (!response.ok || !contentType.startsWith('image/png')) {
                throw new Error('La imagen QR ya no está disponible. Actualiza la página e intenta nuevamente.');
            }

            const blob = await response.blob();

            if (canShareFile) {
                const file = new File([blob], filename, { type: 'image/png' });

                await navigator.share({
                    files: [file],
                    title: 'QR de recojo Tik Shop',
                    text: 'QR para recoger tu paquete en Tik Shop.',
                });
                showStatus(button, 'QR enviado al menú de compartir del dispositivo.');
            } else {
                downloadBlob(blob, filename);

                if (!whatsAppWindow) {
                    window.open(whatsAppUrl, '_blank', 'noopener,noreferrer');
                }

                showStatus(button, 'El QR fue descargado. Adjunta manualmente la imagen en el chat de WhatsApp que se abrió.');
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                showStatus(button, 'No se compartió el QR porque cancelaste la operación.');
            } else {
                showStatus(button, error instanceof Error ? error.message : 'No se pudo preparar el QR para compartir.');
            }
        } finally {
            button.disabled = false;
        }
    });
});
