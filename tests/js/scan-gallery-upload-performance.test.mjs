import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { test } from 'node:test';

const blade = readFileSync('resources/views/documents/scan.blade.php', 'utf8');
const scripts = [...blade.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)].map(x => x[1]);
const script = scripts.find(x => x.includes('async function prepareGalleryPhotos(files)'));
assert.ok(script, 'Gallery normalization script must exist');

function extract(startText, endText) {
    const start = script.indexOf(startText);
    const end = script.indexOf(endText, start);
    assert.ok(start !== -1 && end > start, 'Missing JavaScript: ' + startText);
    return script.slice(start, end);
}

class FakeFile {
    constructor(parts, name, options = {}) {
        this.name = name;
        this.type = options.type || 'image/jpeg';
        this.lastModified = options.lastModified || 1234;
        this.size = parts.reduce((sum, part) => sum + (part?.size || 0), 0);
    }
}
function classList() {
    const classes = new Set();
    return {
        add: x => classes.add(x),
        remove: x => classes.delete(x),
        contains: x => classes.has(x),
    };
}
function ui() {
    return {
        classList: classList(),
        disabled: false,
        value: 0,
        textContent: '',
        setAttribute: () => {},
        removeAttribute: () => {},
    };
}

function galleryHarness() {
    const context = {
        CAMERA_MAX_SOURCE_BYTES: 80 * 1048576,
        GALLERY_MAX_OUTPUT_BYTES: Math.floor(1.6 * 1048576),
        galleryPhotoProcessing: false,
        cameraPhotoProcessing: false,
        scanUploadInProgress: false,
        selectedItems: [],
        MAX_PHOTOS: 100,
        File: FakeFile,
        window: { setTimeout },
        console,
        alert: () => {},
        galleryProcessingStatus: ui(),
        galleryProcessingText: ui(),
        galleryProcessingProgress: ui(),
        uploadInput: ui(),
        openCameraButton: ui(),
        retakeButton: ui(),
        saveButton: ui(),
        isCameraImageCandidate: file => file.type?.startsWith('image/'),
        updateMultiPhotoState: () => {},
        addPhotos: files => {
            context.selectedItems.push(...files.map(file => ({ file, source: 'upload' })));
            return true;
        },
        normalizeNativeCameraPhoto: async () => new FakeFile([{ size: 360 * 1024 }], 'camera.jpg'),
    };
    vm.runInNewContext(extract(
        'async function normalizeGalleryPhoto(file)',
        "uploadInput.addEventListener('change'"
    ), context);
    return context;
}

test('50 large Android-style JPEGs are processed sequentially and page order is stable', async () => {
    const h = galleryHarness();
    let inFlight = 0;
    let highestConcurrent = 0;
    h.normalizeNativeCameraPhoto = async () => {
        inFlight++;
        highestConcurrent = Math.max(highestConcurrent, inFlight);
        await Promise.resolve();
        inFlight--;
        return new FakeFile([{ size: 360 * 1024 }], 'camera.jpg');
    };
    const source = Array.from({ length: 50 }, (_, i) => ({
        name: 'page-' + String(i + 1).padStart(2, '0') + '.jpg',
        type: 'image/jpeg',
        size: 4 * 1048576,
        lastModified: 1000,
    }));

    await h.prepareGalleryPhotos(source);

    assert.equal(highestConcurrent, 1, 'Do not decode 50 large images in parallel');
    assert.equal(h.selectedItems.length, 50);
    assert.equal(h.selectedItems[0].file.name, 'page-01-scan.jpg');
    assert.equal(h.selectedItems[49].file.name, 'page-50-scan.jpg');
    assert.equal(h.galleryProcessingProgress.value, 100);
    assert.match(h.galleryProcessingText.textContent, /50 foto disiapkan/);
    assert.equal(h.galleryPhotoProcessing, false);
    assert.equal(h.saveButton.disabled, false);
    assert.equal(h.uploadInput.disabled, false);
});

test('Already compact JPEGs stay byte-for-byte unchanged', async () => {
    const h = galleryHarness();
    const photo = { name: 'small.jpg', size: 200000, type: 'image/jpeg' };
    let normalized = false;
    h.normalizeNativeCameraPhoto = async () => { normalized = true; };
    assert.equal(await h.normalizeGalleryPhoto(photo), photo);
    assert.equal(normalized, false);
});

test('Invalid gallery file leaves the previous queue untouched', async () => {
    const h = galleryHarness();
    const previous = { file: { name: 'old.jpg', size: 1000 }, source: 'upload' };
    h.selectedItems.push(previous);
    await h.prepareGalleryPhotos([{ name: 'not-image.pdf', type: 'application/pdf', size: 1024 }]);

    assert.equal(h.selectedItems.length, 1);
    assert.equal(h.selectedItems[0], previous);
    assert.match(h.galleryProcessingText.textContent, /gagal diproses/i);
    assert.equal(h.galleryPhotoProcessing, false);
    assert.equal(h.uploadInput.disabled, false);
});

function uploadHarness() {
    let handler;
    const context = {
        selectedItems: [{ file: { name: 'one.jpg' } }, { file: { name: 'two.jpg' } }],
        scanUploadInProgress: false,
        cameraPhotoProcessing: false,
        galleryPhotoProcessing: false,
        saveButton: ui(),
        openCameraButton: ui(),
        retakeButton: ui(),
        uploadInput: ui(),
        scanUploadStatus: ui(),
        scanUploadText: ui(),
        scanUploadProgress: ui(),
        scanUploadNote: ui(),
        expectedPhotoCount: ui(),
        scanDocumentForm: {
            action: '/documents/scan',
            addEventListener: (_, cb) => { handler = cb; },
        },
        syncFilesToForm: () => true,
        updateMultiPhotoState: () => {},
        alert: () => {},
        FormData: class { constructor(form) { this.form = form; } },
        window: { location: { reload: () => { context.reloads++; } } },
        reloads: 0,
    };
    class FakeXHR {
        constructor() {
            this.events = {};
            this.uploadEvents = {};
            this.upload = {
                addEventListener: (name, cb) => { this.uploadEvents[name] = cb; },
            };
            this.headers = {};
            context.xhr = this;
        }
        open(method, url) { this.method = method; this.url = url; }
        setRequestHeader(name, value) { this.headers[name] = value; }
        addEventListener(name, cb) { this.events[name] = cb; }
        send(payload) { this.sent = payload; }
        emit(name, event = {}) { this.events[name]?.(event); }
        emitUpload(name, event = {}) { this.uploadEvents[name]?.(event); }
    }
    context.XMLHttpRequest = FakeXHR;
    vm.runInNewContext(extract(
        'function setUploadProgress(',
        '// CLEANUP CAMERA WHEN LEAVING PAGE'
    ), context);
    return {
        context,
        submit: () => handler({ preventDefault: () => {} }),
    };
}

test('XHR reports actual uploaded bytes and distinguishes PDF server work', () => {
    const h = uploadHarness();
    h.submit();
    const x = h.context.xhr;
    assert.equal(x.method, 'POST');
    assert.equal(x.headers.Accept, 'application/json');
    assert.ok(x.sent);
    x.emitUpload('progress', { loaded: 42, total: 100, lengthComputable: true });
    assert.equal(h.context.scanUploadProgress.value, 42);
    x.emitUpload('load');
    assert.match(h.context.scanUploadText.textContent, /Server sedang membuat PDF/);

    x.status = 200;
    x.responseText = JSON.stringify({ ok: true });
    x.emit('load');
    assert.equal(h.context.reloads, 1);
});

test('Failed server validation preserves photo queue for retry', () => {
    const h = uploadHarness();
    h.submit();
    const x = h.context.xhr;
    x.status = 422;
    x.responseText = JSON.stringify({ message: 'Invalid', errors: { files: ['Foto tidak valid.'] } });
    x.emit('load');

    assert.equal(h.context.selectedItems.length, 2);
    assert.match(h.context.scanUploadText.textContent, /Foto tidak valid/);
    assert.equal(h.context.scanUploadInProgress, false);
    assert.equal(h.context.saveButton.textContent, 'Simpan Berkas QC');
});
