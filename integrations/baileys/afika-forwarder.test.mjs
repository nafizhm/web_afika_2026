import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readdir, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { EventEmitter } from 'node:events';
import { createAfikaForwarder } from './afika-forwarder.mjs';

const secret = 'test-secret-with-more-than-thirty-two-characters';
const message = id => ({ key: { id, remoteJid: '628123456789@s.whatsapp.net', fromMe: false }, messageTimestamp: { toString: () => '1794614400' }, message: { conversation: 'Halo Afika' } });
const settings = { url: 'https://afika.example/webhooks/whatsapp', secret, intervalMs: 60_000, logger: { error() {} } };

test('forwards all incoming messages and append events, skips outgoing/status, authenticates', async () => {
  const outbox = await mkdtemp(join(tmpdir(), 'afika-test-'));
  const deliveries = [];
  const forwarder = await createAfikaForwarder({ ...settings, outbox, fetchImpl: async (url, request) => {
    assert.equal(request.headers.Authorization, `Bearer ${secret}`);
    deliveries.push(JSON.parse(request.body));
    return { ok: true, status: 201, json: async () => ({ ok: true }) };
  } });
  try {
    await forwarder.flush();
    await forwarder.enqueueUpsert({ type: 'notify', messages: [message('a'), message('b'), { ...message('out'), key: { ...message('out').key, fromMe: true } }, { ...message('status'), key: { ...message('status').key, remoteJid: 'status@broadcast' } }] });
    await forwarder.enqueueUpsert({ type: 'append', messages: [message('c')] });
    assert.equal(deliveries.length, 3);
    assert.equal(deliveries[0].account, '6282221992911');
    assert.equal(deliveries[0].message.messageTimestamp, 1794614400);
    assert.deepEqual(await readdir(outbox), []);
    const first = { ev: new EventEmitter() }, second = { ev: new EventEmitter() };
    forwarder.attach(first); forwarder.attach(second);
    assert.equal(first.ev.listenerCount('messages.upsert'), 0);
    assert.equal(second.ev.listenerCount('messages.upsert'), 1);
  } finally { await forwarder.stop(); await rm(outbox, { recursive: true }); }
});

test('keeps failed deliveries on disk and replays after restart; accepts duplicate ACK', async () => {
  const outbox = await mkdtemp(join(tmpdir(), 'afika-retry-'));
  let forwarder = await createAfikaForwarder({ ...settings, outbox, fetchImpl: async () => { throw new Error('offline'); } });
  try {
    await forwarder.flush();
    await forwarder.enqueueUpsert({ type: 'notify', messages: [message('persisted')] });
    assert.equal((await readdir(outbox)).filter(n => n.endsWith('.json')).length, 1);
    await forwarder.stop();
    let replayed = 0;
    forwarder = await createAfikaForwarder({ ...settings, outbox, fetchImpl: async () => {
      replayed++;
      return { ok: true, status: 200, json: async () => ({ ok: true, duplicate: true }) };
    } });
    await forwarder.flush();
    assert.equal(replayed, 1);
    assert.deepEqual(await readdir(outbox), []);
  } finally { await forwarder.stop(); await rm(outbox, { recursive: true }); }
});

test('HTML success responses cannot silently discard messages', async () => {
  const outbox = await mkdtemp(join(tmpdir(), 'afika-ack-'));
  const forwarder = await createAfikaForwarder({ ...settings, outbox, fetchImpl: async () => ({ ok: true, status: 200, json: async () => { throw new Error('HTML'); } }) });
  try {
    await forwarder.flush();
    await forwarder.enqueueUpsert({ type: 'notify', messages: [message('kept')] });
    assert.equal((await readdir(outbox)).filter(n => n.endsWith('.json')).length, 1);
  } finally { await forwarder.stop(); await rm(outbox, { recursive: true }); }
});
