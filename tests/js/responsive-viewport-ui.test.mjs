import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const category = readFileSync(new URL('../../resources/js/category-combobox.js', import.meta.url), 'utf8');
const search = readFileSync(new URL('../../resources/views/documents/index.blade.php', import.meta.url), 'utf8');
const scan = readFileSync(new URL('../../resources/views/documents/scan.blade.php', import.meta.url), 'utf8');

test('category popup is portalled to body and positioned according to available room', () => {
    assert.match(category, /document\.body\.appendChild\(listbox\)/);
    assert.match(category, /listbox\.style\.position = 'fixed'/);
    assert.match(category, /listbox\.style\.maxHeight = height \+ 'px'/);
    assert.match(category, /openAbove = below < 220 && above > below/);
    assert.match(category, /!root\.contains\(event\.target\) && !listbox\.contains\(event\.target\)/);
    assert.match(category, /window\.addEventListener\('scroll', positionListbox, true\)/);
});

test('live document search can open upward and limits its rendered height', () => {
    assert.match(search, /liveSearchAbove: false/);
    assert.match(search, /this\.liveSearchAbove = below < 300 && above > below/);
    assert.match(search, /panel\.style\.maxHeight = Math\.max/);
    assert.match(search, /:class="liveSearchAbove \? 'bottom-full mb-2' : 'top-full mt-2'"/);
});

test('add scan type dialog is portalled and closable without data mutation', () => {
    assert.match(scan, /document\.body\.appendChild\(scanTypeModal\)/);
    assert.match(scan, /scanTypeCreateReturnFocus\.focus\(\)/);
    assert.match(scan, /event\.key === 'Escape' && scanTypeModal/);
    assert.match(scan, /max-h-\[calc\(100dvh-24px\)\]/);
});
