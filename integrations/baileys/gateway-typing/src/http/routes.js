import express from 'express';
import { sendChatPresence } from '../services/presenceService.js';
import { asyncHandler } from './errors.js';
import { sessionManager } from '../services/sessionManager.js';
import { queueService } from '../services/queueService.js';
import { store } from '../stores/fileStore.js';

export const router = express.Router();

router.get('/health', asyncHandler(async (req, res) => {
  const sessions = await store.getSessions();
  const queue = await store.getQueue();

  res.json({
    ok: true,
    service: 'wa-gateway-service',
    uptime: process.uptime(),
    sessions: {
      total: Object.keys(sessions).length,
      runtime: sessionManager.list().length
    },
    queue: {
      pending: queue.length
    }
  });
}));

router.post('/sessions', asyncHandler(async (req, res) => {
  const session = await sessionManager.create(req.body.clientId, {
    incomingCallbackUrl: req.body.incomingCallbackUrl,
    incomingCallbackHeaders: req.body.incomingCallbackHeaders
  });
  res.status(201).json({ ok: true, data: session });
}));

router.get('/sessions', asyncHandler(async (req, res) => {
  const persisted = await store.getSessions();
  res.json({
    ok: true,
    data: {
      persisted: Object.values(persisted),
      runtime: sessionManager.list()
    }
  });
}));

router.get('/sessions/:clientId', asyncHandler(async (req, res) => {
  const sessions = await store.getSessions();
  const runtime = sessionManager.get(req.params.clientId);
  const persisted = sessions[req.params.clientId] || null;

  if (!persisted && !runtime) {
    res.status(404).json({ ok: false, error: 'Session tidak ditemukan' });
    return;
  }

  res.json({
    ok: true,
    data: {
      persisted,
      runtime: runtime ? sessionManager.publicSession(req.params.clientId, runtime) : null
    }
  });
}));

router.get('/sessions/:clientId/qr', asyncHandler(async (req, res) => {
  const qr = await sessionManager.getQr(req.params.clientId);
  res.json({ ok: true, data: qr });
}));

router.post('/sessions/:clientId/logout', asyncHandler(async (req, res) => {
  await sessionManager.logout(req.params.clientId);
  res.json({ ok: true });
}));

router.delete('/sessions/:clientId', asyncHandler(async (req, res) => {
  await sessionManager.delete(req.params.clientId);
  res.json({ ok: true });
}));

router.post('/presence', asyncHandler(async (req, res) => {
  const result = await sendChatPresence(sessionManager, req.body);
  res.status(result.status).json(result.body);
}));

router.post('/messages/text', asyncHandler(async (req, res) => {
  if (!req.body.text) {
    throw new Error('text wajib diisi');
  }

  const job = await queueService.enqueue({
    clientId: req.body.clientId,
    to: req.body.to,
    type: 'text',
    payload: { text: req.body.text },
    callbackUrl: req.body.callbackUrl
  });

  res.status(202).json({ ok: true, data: job });
}));

router.post('/messages/image', asyncHandler(async (req, res) => {
  if (!req.body.imageUrl && !req.body.imagePath) {
    throw new Error('imageUrl atau imagePath wajib diisi');
  }

  const job = await queueService.enqueue({
    clientId: req.body.clientId,
    to: req.body.to,
    type: 'image',
    payload: {
      imageUrl: req.body.imageUrl,
      imagePath: req.body.imagePath,
      caption: req.body.caption
    },
    callbackUrl: req.body.callbackUrl
  });

  res.status(202).json({ ok: true, data: job });
}));

router.post('/messages/pdf', asyncHandler(async (req, res) => {
  if (!req.body.documentUrl && !req.body.documentPath) {
    throw new Error('documentUrl atau documentPath wajib diisi');
  }

  const job = await queueService.enqueue({
    clientId: req.body.clientId,
    to: req.body.to,
    type: 'pdf',
    payload: {
      documentUrl: req.body.documentUrl,
      documentPath: req.body.documentPath,
      fileName: req.body.fileName,
      mimetype: req.body.mimetype,
      caption: req.body.caption
    },
    callbackUrl: req.body.callbackUrl
  });

  res.status(202).json({ ok: true, data: job });
}));

router.get('/messages/:messageId', asyncHandler(async (req, res) => {
  const message = await queueService.findMessage(req.params.messageId);
  if (!message) {
    res.status(404).json({ ok: false, error: 'Message tidak ditemukan' });
    return;
  }
  res.json({ ok: true, data: message });
}));

router.get('/queue', asyncHandler(async (req, res) => {
  const queue = await queueService.listQueue();
  res.json({ ok: true, data: queue });
}));
