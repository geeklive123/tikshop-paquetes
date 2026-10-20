const initializedButtons = new WeakSet();

const downloadBlob = (blob, filename, documentObject, urlObject) => {
    const objectUrl = urlObject.createObjectURL(blob);
    const link = documentObject.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    documentObject.body.appendChild(link);
    link.click();
    link.remove();
    urlObject.revokeObjectURL(objectUrl);
};

const showStatus = (button, message) => {
    const status = button.closest('section')?.querySelector('[data-pickup-qr-share-status]');

    if (!status) {
        return;
    }

    status.textContent = message;
    status.classList.remove('hidden');
};

const reserveWhatsAppWindow = (windowObject) => {
    try {
        const reservedWindow = windowObject.open('', '_blank');

        if (reservedWindow) {
            reservedWindow.opener = null;
        }

        return reservedWindow;
    } catch {
        return null;
    }
};

const openWhatsApp = (windowObject, reservedWindow, whatsAppUrl) => {
    if (reservedWindow && !reservedWindow.closed) {
        reservedWindow.location.replace(whatsAppUrl);

        return;
    }

    windowObject.location.assign(whatsAppUrl);
};

export const initializePackageQrShare = ({
    documentObject = document,
    windowObject = window,
    fetchObject = window.fetch.bind(window),
    urlObject = globalThis.URL,
} = {}) => {
    const shareButtons = documentObject.querySelectorAll('[data-share-pickup-qr]');

    shareButtons.forEach((button) => {
        if (initializedButtons.has(button)) {
            return;
        }

        initializedButtons.add(button);
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            if (button.disabled || button.dataset.shareProcessing === 'true') {
                return;
            }

            const downloadUrl = button.dataset.downloadUrl;
            const filename = button.dataset.filename;
            const whatsAppUrl = button.dataset.whatsappUrl;

            if (!downloadUrl || !filename || !whatsAppUrl) {
                return;
            }

            const originalLabel = button.textContent;
            // This must happen synchronously inside the click to avoid popup blockers.
            const whatsAppWindow = reserveWhatsAppWindow(windowObject);

            button.dataset.shareProcessing = 'true';
            button.disabled = true;
            button.textContent = 'Preparando...';

            try {
                const response = await fetchObject(downloadUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'image/png' },
                });
                const contentType = response.headers.get('content-type') ?? '';

                if (!response.ok || !contentType.startsWith('image/png')) {
                    throw new Error('La imagen QR ya no está disponible. Actualiza la página e intenta nuevamente.');
                }

                const blob = await response.blob();

                downloadBlob(blob, filename, documentObject, urlObject);
                showStatus(button, 'El QR fue descargado. Adjunta manualmente la imagen en el chat de WhatsApp que se abrió.');
                openWhatsApp(windowObject, whatsAppWindow, whatsAppUrl);
            } catch (error) {
                if (whatsAppWindow && !whatsAppWindow.closed) {
                    whatsAppWindow.close();
                }

                showStatus(button, error instanceof Error ? error.message : 'No se pudo preparar el QR para compartir.');
            } finally {
                delete button.dataset.shareProcessing;
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });
};

if (typeof document !== 'undefined') {
    initializePackageQrShare();
}
