import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { test } from 'node:test';

const blade = readFileSync('resources/views/documents/scan.blade.php', 'utf8');
const scriptTags = [...blade.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)];
const script = scriptTags.map(x => x[1]).find(x => x.includes("manualCropStage.addEventListener('pointerdown'"));
assert.ok(script, 'Scan/Capture inline crop script must exist');

function excerpt(start, end) {
    const from = script.indexOf(start);
    const to = script.indexOf(end, from);
    assert.ok(from !== -1 && to > from, 'Crop code excerpt not found: ' + start);
    return script.slice(from, to);
}

function createCropHarness() {
    const listeners = {};
    const stage = {
        addEventListener: (event, handler) => { listeners[event] = handler; },
        setPointerCapture: () => {},
        hasPointerCapture: () => true,
        releasePointerCapture: () => {},
    };
    const context = {
        manualCropStage: stage,
        editorBusy: false,
        cropDrag: null,
        cropSelection: { x: 0, y: 0, w: 1, h: 1 },
        MIN_CROP_FRACTION: 0.12,
        clampCrop: (value, min, max) => Math.max(min, Math.min(max, value)),
        cropPointerPosition: event => ({
            x: Math.max(0, Math.min(1, event.clientX / 300)),
            y: Math.max(0, Math.min(1, event.clientY / 500)),
        }),
        drawCropSelection: () => {},
    };
    vm.runInNewContext(excerpt(
        "manualCropStage.addEventListener('pointerdown'",
        "resetCropButton.addEventListener('click'"
    ), context);
    const pointer = (kind, { id, x, y, handle = '', inside = true, type = 'touch' }) => {
        const target = {
            closest: selector => {
                if (selector === '[data-crop-handle]') {
                    return handle ? { dataset: { cropHandle: handle } } : null;
                }
                if (selector === '#manualCropSelection') {
                    return inside ? {} : null;
                }
                return null;
            },
        };
        listeners[kind]({
            pointerId: id,
            pointerType: type,
            button: 0,
            clientX: x,
            clientY: y,
            target,
            preventDefault: () => {},
            type: kind,
        });
    };
    return { context, pointer };
}

test('Crop inline JavaScript parses after Blade templates are rendered', () => {
    const js = script.replace(/\{\{[\s\S]*?\}\}/g, '/test-url');
    assert.doesNotThrow(() => new vm.Script(js, { filename: 'scan-crop.js' }));
});

test('A touch outside the selection cannot start a new tiny crop', () => {
    const { context, pointer } = createCropHarness();
    pointer('pointerdown', { id: 1, x: 170, y: 260, inside: false });
    assert.equal(context.cropDrag, null);
    assert.equal(context.cropSelection.w, 1);
});

test('Minor finger jitter does not resize a crop', () => {
    const { context, pointer } = createCropHarness();
    pointer('pointerdown', { id: 2, x: 300, y: 250, handle: 'e' });
    pointer('pointermove', { id: 2, x: 294, y: 250, handle: 'e' });
    assert.equal(context.cropSelection.w, 1);
    pointer('pointerup', { id: 2, x: 294, y: 250, handle: 'e' });
    assert.equal(context.cropSelection.w, 1);
});

test('Valid finger resize works and pointercancel restores the initial crop', () => {
    const { context, pointer } = createCropHarness();
    pointer('pointerdown', { id: 3, x: 300, y: 250, handle: 'e' });
    pointer('pointermove', { id: 3, x: 240, y: 250, handle: 'e' });
    assert.ok(Math.abs(context.cropSelection.w - 0.8) < 0.001);
    pointer('pointercancel', { id: 3, x: 240, y: 250, handle: 'e' });
    assert.equal(context.cropSelection.w, 1);
    assert.equal(context.cropDrag, null);
});

test('Mouse still supports drawing a new crop in empty space', () => {
    const { context, pointer } = createCropHarness();
    pointer('pointerdown', { id: 4, x: 30, y: 40, inside: false, type: 'mouse' });
    pointer('pointermove', { id: 4, x: 240, y: 440, inside: false, type: 'mouse' });
    assert.ok(context.cropSelection.w < 1 && context.cropSelection.w > 0.5);
    assert.ok(context.cropSelection.h < 1 && context.cropSelection.h > 0.5);
    pointer('pointerup', { id: 4, x: 240, y: 440, type: 'mouse' });
});

test('Tiny crop selection disables applying the edit', () => {
    const shapeCode = excerpt('function drawCropSelection()', 'function cropPointerPosition(event)');
    const context = {
        cropSelection: { x: 0.02, y: 0.02, w: 0.13, h: 0.13 },
        manualCropCanvas: { width: 720, height: 1276 },
        manualCropSelection: { style: {} },
        manualCropInfo: {
            textContent: '',
            classList: { toggle: () => {} },
        },
        confirmCropButton: { disabled: false },
        editorBusy: false,
        MIN_CROP_APPLY_AREA: 0.05,
        MIN_CROP_APPLY_EDGE: 0.15,
    };
    vm.runInNewContext(shapeCode, context);
    context.drawCropSelection();
    assert.equal(context.confirmCropButton.disabled, true);
    assert.match(context.manualCropInfo.textContent, /terlalu kecil/i);
    context.cropSelection = { x: 0, y: 0, w: 1, h: 1 };
    context.drawCropSelection();
    assert.equal(context.confirmCropButton.disabled, false);
});
