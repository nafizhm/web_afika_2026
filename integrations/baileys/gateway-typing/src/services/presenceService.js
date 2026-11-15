// Lightweight endpoint: no queue job and no outgoing chat message.
export async function sendChatPresence(sessionManager, input = {}) {
  const { clientId, to, state } = input;
  if (typeof clientId !== 'string' || !/^[a-zA-Z0-9_-]+$/.test(clientId)
      || typeof to !== 'string' || !/^[0-9]{8,20}$/.test(to)
      || !['composing', 'paused'].includes(state)) {
    return { status: 422, body: { ok: false, error: 'clientId, phone number and composing/paused state are required.' } };
  }
  const session = sessionManager.get(clientId);
  if (session?.status !== 'open' || typeof session.sock?.sendPresenceUpdate !== 'function') {
    return { status: 409, body: { ok: false, error: 'WhatsApp session is not connected.' } };
  }
  await session.sock.sendPresenceUpdate(state, `${to}@s.whatsapp.net`);
  return { status: 200, body: { ok: true } };
}
