import assert from 'node:assert/strict';
import test from 'node:test';
import { filterAdminSearchEntries, matchesAdminSearchEntry } from './admin-search.js';

const entries = [
    { label: 'Recruitment', description: 'Roles', keywords: ['jobs', 'vacancies'], url: '/a' },
    { label: 'Tickets', description: 'Support', keywords: ['helpdesk'], url: '/b' },
    { label: 'Access', description: 'RBAC', keywords: ['permissions'], url: '/c' },
];

test('jobs finds Recruitment', () => {
    assert.equal(matchesAdminSearchEntry(entries[0], 'jobs'), true);
    assert.deepEqual(filterAdminSearchEntries(entries, 'jobs').map((entry) => entry.label), ['Recruitment']);
});

test('helpdesk finds Tickets', () => {
    assert.deepEqual(filterAdminSearchEntries(entries, 'helpdesk').map((entry) => entry.label), ['Tickets']);
});

test('permissions finds Access', () => {
    assert.deepEqual(filterAdminSearchEntries(entries, 'permissions').map((entry) => entry.label), ['Access']);
});

test('empty query matches nothing', () => {
    assert.deepEqual(filterAdminSearchEntries(entries, ''), []);
});
