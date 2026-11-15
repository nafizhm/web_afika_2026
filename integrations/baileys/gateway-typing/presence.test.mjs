import { test } from 'node:test';
import assert from 'node:assert/strict';
import { sendChatPresence } from './src/services/presenceService.js';

test('only sends composing/paused presence to requested chat', async () => {
  const calls=[];
  const manager={get:()=>({status:'open',sock:{sendPresenceUpdate:async(...args)=>calls.push(args)}})};
  assert.equal((await sendChatPresence(manager,{clientId:'cobadong',to:'628123456789',state:'composing'})).status,200);
  assert.equal((await sendChatPresence(manager,{clientId:'cobadong',to:'628123456789',state:'paused'})).status,200);
  assert.deepEqual(calls,[['composing','628123456789@s.whatsapp.net'],['paused','628123456789@s.whatsapp.net']]);
});

test('rejects invalid recipients/states and disconnected session', async () => {
  const manager={get:()=>undefined};
  assert.equal((await sendChatPresence(manager,{clientId:'cobadong',to:'123@g.us',state:'composing'})).status,422);
  assert.equal((await sendChatPresence(manager,{clientId:'cobadong',to:'628123456789',state:'recording'})).status,422);
  assert.equal((await sendChatPresence(manager,{clientId:'cobadong',to:'628123456789',state:'composing'})).status,409);
});
