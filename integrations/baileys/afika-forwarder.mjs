import { createHash, randomUUID } from 'node:crypto';
import { mkdir, readFile, readdir, rename, unlink, writeFile } from 'node:fs/promises';
import { resolve, join } from 'node:path';

/** Node 20+. Attach to your EXISTING Baileys socket; no extra WhatsApp session. */
export async function createAfikaForwarder({
  url = process.env.AFIKA_WEBHOOK_URL,
  secret = process.env.AFIKA_WEBHOOK_SECRET,
  account = process.env.AFIKA_WHATSAPP_NUMBER || '6282221992911',
  outbox = process.env.AFIKA_OUTBOX_DIR || './afika-outbox',
  intervalMs = 15_000,
  fetchImpl = globalThis.fetch,
  logger = console,
} = {}) {
  const endpoint = new URL(url);
  if (endpoint.protocol !== 'https:' && !(endpoint.protocol === 'http:' && ['127.0.0.1', 'localhost'].includes(endpoint.hostname))) {
    throw new Error('AFIKA_WEBHOOK_URL must use HTTPS (HTTP allowed only for localhost testing).');
  }
  if (typeof secret !== 'string' || secret.length < 32) throw new Error('AFIKA_WEBHOOK_SECRET requires at least 32 characters.');
  if (!/^\d{9,15}$/.test(account)) throw new Error('Invalid AFIKA_WHATSAPP_NUMBER.');
  const directory = resolve(outbox);
  await mkdir(directory, { recursive: true, mode: 0o700 });
  let running;
  let detach;

  async function drain() {
    const names = (await readdir(directory)).filter(name => /^[a-f0-9]{64}\.json$/.test(name)).sort();
    for (const name of names) {
      const path = join(directory, name);
      try {
        const body = await readFile(path, 'utf8');
        const response = await fetchImpl(endpoint.href, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${secret}` },
          body,
          signal: AbortSignal.timeout(15_000),
          redirect: 'error',
        });
        // A web server may return an HTML login page with HTTP 200. Only our JSON ACK counts.
        const ack = await response.json().catch(() => null);
        if (!response.ok || ack?.ok !== true) {
          logger.error(`Afika webhook rejected delivery (HTTP ${response.status}); file kept for retry.`);
          continue;
        }
        await unlink(path);
      } catch {
        logger.error('Afika webhook unavailable; outbox file kept for retry.');
      }
    }
  }

  function flush() {
    if (!running) running = drain().finally(() => { running = undefined; });
    return running;
  }

  async function enqueueUpsert({ messages, type }) {
    if (!['notify', 'append'].includes(type) || !Array.isArray(messages)) return;
    for (const message of messages) {
      if (message?.key?.fromMe !== false || !message.key.id || !message.key.remoteJid || !message.message || message.key.remoteJid === 'status@broadcast') continue;
      // Baileys may expose protobuf Long timestamps; send an integer in Unix seconds.
      const timestamp = message.messageTimestamp == null ? null : Number(message.messageTimestamp.toString());
      if (timestamp !== null && !Number.isSafeInteger(timestamp)) throw new Error('Invalid Baileys message timestamp.');
      const body = JSON.stringify({ account, event_type: type, message: { ...message, messageTimestamp: timestamp } });
      const id = createHash('sha256').update(JSON.stringify([account, message.key.remoteJid, message.key.id])).digest('hex');
      // Persist before making a network request. Incomplete temporary files are never sent.
      const temporary = join(directory, `${id}.${randomUUID()}.tmp`);
      await writeFile(temporary, body, { mode: 0o600, flag: 'wx' });
      await rename(temporary, join(directory, `${id}.json`));
    }
    await flush();
  }

  const timer = setInterval(() => {
    flush().catch(() => logger.error('Afika outbox cannot be read. Check directory permissions/disk space.'));
  }, intervalMs);
  timer.unref();
  // Replay pending messages after process restart as well as socket reconnections.
  void flush().catch(() => logger.error('Afika outbox startup replay failed.'));

  return {
    enqueueUpsert,
    flush,
    attach(sock) {
      // Reuse this forwarder on reconnect; do not keep listeners on the old socket.
      detach?.();
      const listener = event => {
        void enqueueUpsert(event).catch(() => logger.error('Afika could not persist incoming messages. Check disk space/permissions immediately.'));
      };
      sock.ev.on('messages.upsert', listener);
      detach = () => sock.ev.off('messages.upsert', listener);
    },
    async stop() {
      clearInterval(timer);
      detach?.();
      await flush();
    },
  };
}
