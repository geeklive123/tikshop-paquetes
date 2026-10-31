import assert from 'node:assert/strict';
import test from 'node:test';

import { initializePackageQrShare } from '../../resources/js/package-qr-share.js';

const createButton = () => {
    const listeners = [];
    const status = {
        classList: { remove: () => {} },
        textContent: '',
    };
    const button = {
        dataset: {
            downloadUrl: '/packages/01/pickup-qr.png',
            filename: 'qr-TIK-1.png',
            whatsappUrl: 'https://wa.me/59170000002?text=Mensaje%20codificado',
        },
        disabled: false,
        textContent: 'Compartir QR por WhatsApp',
        addEventListener: (type, listener) => listeners.push({ type, listener }),
        closest: () => ({ querySelector: () => status }),
    };

    return { button, listeners, status };
};

const createDocument = (button) => {
    const links = [];

    return {
        links,
        body: { appendChild: (link) => links.push(link) },
        createElement: () => ({
            click() {
                this.clicked = true;
            },
            remove() {},
        }),
        querySelectorAll: () => [button],
    };
};

const pngResponse = async () => ({
    blob: async () => ({ type: 'image/png' }),
    headers: { get: () => 'image/png' },
    ok: true,
});

const click = async (listener) => {
    let defaultPrevented = false;

    await listener({ preventDefault: () => { defaultPrevented = true; } });

    return defaultPrevented;
};

test('initialization registers one click listener per button', () => {
    const { button, listeners } = createButton();
    const documentObject = createDocument(button);
    const dependencies = {
        documentObject,
        windowObject: { fetch: () => {}, open: () => null },
        fetchObject: () => {},
        urlObject: {},
    };

    initializePackageQrShare(dependencies);
    initializePackageQrShare(dependencies);

    assert.equal(listeners.length, 1);
    assert.equal(listeners[0].type, 'click');
});

test('first click shares the PNG file directly when supported', async () => {
    const { button, listeners } = createButton();
    const documentObject = createDocument(button);
    let shared;
    let fetchCalls = 0;
    class TestFile {
        constructor(parts, name, options) { Object.assign(this, { parts, name, type: options.type }); }
    }
    initializePackageQrShare({
        documentObject,
        windowObject: { open: () => { throw new Error('unexpected popup'); } },
        navigatorObject: { canShare: ({ files }) => files[0].type === 'image/png', share: async (data) => { shared = data; } },
        FileObject: TestFile,
        fetchObject: async (...args) => { fetchCalls++; assert.equal(args[0], button.dataset.downloadUrl); return pngResponse(); },
        urlObject: {},
    });

    await click(listeners[0].listener);

    assert.equal(fetchCalls, 1);
    assert.equal(shared.files[0].name, button.dataset.filename);
    assert.equal(shared.files[0].type, 'image/png');
    assert.equal(documentObject.links.length, 0);
    assert.equal(button.disabled, false);
});

test('fallback downloads the png and opens the HTTPS WhatsApp URL', async () => {
    const { button, listeners, status } = createButton();
    const documentObject = createDocument(button);
    const navigations = [];
    let webShareCalls = 0;
    const reservedWindow = {
        closed: false,
        close: () => {},
        location: { replace: (url) => navigations.push(url) },
        opener: {},
    };

    initializePackageQrShare({
        documentObject,
        navigatorObject: { share: async () => { webShareCalls += 1; } },
        windowObject: {
            fetch: () => {},
            location: { assign: (url) => navigations.push(url) },
            open: () => reservedWindow,
        },
        fetchObject: pngResponse,
        urlObject: {
            createObjectURL: () => 'blob:qr',
            revokeObjectURL: () => {},
        },
    });

    const defaultPrevented = await click(listeners[0].listener);

    assert.equal(defaultPrevented, true);
    assert.equal(webShareCalls, 0);
    assert.equal(documentObject.links.length, 1);
    assert.equal(documentObject.links[0].clicked, true);
    assert.deepEqual(navigations, [button.dataset.whatsappUrl]);
    assert.match(navigations[0], /^https:\/\/wa\.me\//);
    assert.match(status.textContent, /Adjunta manualmente/);
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Compartir QR por WhatsApp');
});

test('file sharing rejection uses the WhatsApp fallback', async () => {
    const { button, listeners } = createButton();
    const documentObject = createDocument(button);
    const navigations = [];
    class TestFile { constructor() {} }
    initializePackageQrShare({
        documentObject,
        FileObject: TestFile,
        navigatorObject: { canShare: () => false, share: () => { throw new Error('unexpected share'); } },
        windowObject: { open: () => ({ closed: false, location: { replace: (url) => navigations.push(url) } }) },
        fetchObject: pngResponse,
        urlObject: { createObjectURL: () => 'blob:qr', revokeObjectURL: () => {} },
    });

    await click(listeners[0].listener);
    assert.equal(documentObject.links[0].clicked, true);
    assert.deepEqual(navigations, [button.dataset.whatsappUrl]);
});

test('a second click is ignored while the first click is preparing the png', async () => {
    const { button, listeners } = createButton();
    const documentObject = createDocument(button);
    let resolveFetch;
    let fetchCalls = 0;
    let openedWindows = 0;

    initializePackageQrShare({
        documentObject,
        windowObject: {
            fetch: () => {},
            location: { assign: () => {} },
            open: () => {
                openedWindows += 1;

                return { closed: false, location: { replace: () => {} }, opener: {} };
            },
        },
        fetchObject: () => {
            fetchCalls += 1;

            return new Promise((resolve) => { resolveFetch = resolve; });
        },
        urlObject: {
            createObjectURL: () => 'blob:qr',
            revokeObjectURL: () => {},
        },
    });

    const firstClick = click(listeners[0].listener);
    await click(listeners[0].listener);
    resolveFetch(await pngResponse());
    await firstClick;

    assert.equal(fetchCalls, 1);
    assert.equal(openedWindows, 1);
    assert.equal(documentObject.links.length, 1);
});

test('an invalid png closes the reserved window and restores the button', async () => {
    const { button, listeners, status } = createButton();
    const documentObject = createDocument(button);
    let closed = false;

    initializePackageQrShare({
        documentObject,
        windowObject: {
            fetch: () => {},
            location: { assign: () => {} },
            open: () => ({
                closed: false,
                close: () => { closed = true; },
                location: { replace: () => {} },
                opener: {},
            }),
        },
        fetchObject: async () => ({
            blob: async () => ({}),
            headers: { get: () => 'text/html' },
            ok: false,
        }),
        urlObject: {},
    });

    await click(listeners[0].listener);

    assert.equal(closed, true);
    assert.match(status.textContent, /ya no está disponible/);
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Compartir QR por WhatsApp');
    assert.equal(documentObject.links.length, 0);
});
