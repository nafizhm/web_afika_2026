import { test } from 'node:test';
import assert from 'node:assert/strict';
import { processOne, readConfig, isWorkerEntrypoint, fikaIntroduction, fikaMessages, exactFaqAnswer, startTyping } from './afika-ai-worker.mjs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

test('starts under PM2 even when argv points to its wrapper', () => {
  const script = resolve('afika-ai-worker.mjs');
  const url = pathToFileURL(script).href;
  assert.equal(isWorkerEntrypoint(url, { pm_exec_path: script }, ['node', '/pm2/ProcessContainerFork.js']), true);
  assert.equal(isWorkerEntrypoint(url, {}, ['node', script]), true);
  assert.equal(isWorkerEntrypoint(url, {}, ['node', resolve('some-test.mjs')]), false);
});

const config = readConfig({ AFIKA_WORKER_TOKEN: 'a'.repeat(64), KENARI_API_KEY: 'test-only', KENARI_MODEL: 'test-model', WA_TYPING_ENABLED: 'false' });
const job = { id: 1, lease_token: 'b'.repeat(64), recipient: '628111111111', incoming_text: 'Halo', reply_text: null };
const ok = data => ({ status: 200, data: { ok: true, ...data } });

test('typing refreshes while waiting and clears when stopped', async () => {
  const states=[]; let tick; let cancelled=false;
  const stop=startTyping({...config,typingEnabled:true},job.recipient,{
    http:async(url,data)=>{states.push(data.state);return ok({});},
    schedule:callback=>{tick=callback;return 1;},cancel:()=>{cancelled=true;},log() {},
  });
  await new Promise(resolve=>setImmediate(resolve));
  tick(); await new Promise(resolve=>setImmediate(resolve));
  await stop(); tick();
  assert.deepEqual(states,['composing','composing','paused']);
  assert.equal(cancelled,true);
});

test('missing presence endpoint does not block the AI answer', async () => {
  const calls=[];
  const http=async(url,data)=>{
    calls.push({url,data});
    if(data.action==='claim')return ok({job:{...job,reply_text:'Jawaban tersedia'}});
    if(url===config.presenceUrl)return {status:404,data:null};
    if(url===config.gatewayUrl)return {status:202,data:{ok:true,data:{id:'typing-fallback'}}};
    return ok({});
  };
  await processOne({...config,typingEnabled:true},{http,log() {}});
  assert.equal(calls.filter(c=>c.url===config.gatewayUrl).length,1);
  assert.equal(calls.find(c=>c.data.action==='complete').data.status,'queued');
});

test('FAQ facts reach the AI even when the customer phrases the question differently', () => {
  const messages = fikaMessages({ incoming_text: 'Jam buka kantor jam berapa ya?', faqs: [
    { question: 'Kapan jam operasional kantor?', answer: 'Senin sampai Sabtu pukul 08.00–16.00 WIB.' },
    { question: 'Draft', answer: '[isi jawaban]' },
    { question: 'Rahasia', answer: 'Jangan ditampilkan', is_active: false },
  ] });
  assert.match(messages[1].content, /08.00–16.00 WIB/);
  assert.doesNotMatch(messages[1].content, /isi jawaban|Jangan ditampilkan/);
  assert.doesNotMatch(messages[0].content, /Data properti belum tersedia/);
});

test('exact FAQ question uses its official answer without an AI request', async () => {
  const current = { ...job, incoming_text: 'Bagaimana cara memesan rumah?', faqs: [
    { question: 'Bagaimana cara memesan rumah', answer: 'Jadwalkan survei dengan tim pemasaran.' },
  ] };
  assert.equal(exactFaqAnswer(current), 'Jadwalkan survei dengan tim pemasaran.');
  let sent;
  const http = async (url, data) => {
    assert.notEqual(url, config.aiUrl);
    if (data.action === 'claim') return ok({ job: current });
    if (url === config.gatewayUrl) { sent = data.text; return { status: 202, data: { ok: true, data: { id: 'faq-job' } } }; }
    return ok({});
  };
  await processOne(config, { http, log() {} });
  assert.equal(sent, current.faqs[0].answer);
});

test('introduces Fika for name questions, without mistaking a property name question', () => {
  assert.match(fikaIntroduction('Siapa nama kamu?'), /Fika/);
  assert.match(fikaIntroduction('Siapa kamu?'), /asisten AI/);
  assert.match(fikaIntroduction('Boleh kenalan?'), /Fika/);
  assert.equal(fikaIntroduction('Apa nama perumahannya?'), null);
  assert.match(fikaMessages({ incoming_text: 'Halo', is_first_contact: true })[0].content, /Ini interaksi pertama/);
  assert.doesNotMatch(fikaMessages({ incoming_text: 'Lalu?', is_first_contact: false })[0].content, /Ini interaksi pertama/);
});

test('name question is answered as Fika without invoking Kenari', async () => {
  let sentText;
  const http = async (url, data) => {
    assert.notEqual(url, config.aiUrl);
    if (data.action === 'claim') return ok({ job: { ...job, incoming_text: 'Siapa nama kamu?' } });
    if (url === config.gatewayUrl) { sentText = data.text; return { status: 202, data: { ok: true, data: { id: 'name-reply' } } }; }
    return ok({});
  };
  await processOne(config, { http, log() {} });
  assert.match(sentText, /saya Fika, asisten AI Afika Residence/);
});

test('empty queue does not call AI or gateway', async () => {
  let calls = 0;
  assert.equal(await processOne(config, { http: async () => { calls++; return ok({ job: null }); } }), false);
  assert.equal(calls, 1);
});

test('disabling AI while answer is generated prevents the gateway send', async () => {
  let sends=0;
  const http=async(url,data)=>{
    if(data.action==='claim')return ok({job:{...job,reply_text:'Jawaban siap'}});
    if(data.action==='prepare_send')return ok({allowed:false,ai_enabled:false});
    if(url===config.gatewayUrl)sends++;
    return ok({});
  };
  await processOne(config,{http,log(){}});
  assert.equal(sends,0);
});

test('persists answer before sending through local session cobadong', async () => {
  const calls = [];
  const http = async (url, data) => {
    calls.push({ url, data });
    if (data.action === 'claim') return ok({ job });
    if (url === config.aiUrl) return { status: 200, data: { choices: [{ message: { content: 'Halo kembali' } }] } };
    if (url === config.gatewayUrl) return { status: 202, data: { ok: true, data: { id: 'gateway-id' } } };
    return ok({});
  };
  await processOne(config, { http, log() {} });
  assert.equal(calls[2].data.action, 'prepare_send');
  assert.equal(calls[3].url, 'http://127.0.0.1:3000/messages/text');
  assert.equal(calls[3].data.clientId, 'cobadong');
  assert.equal(calls[4].data.status, 'queued');
  assert.equal(calls[4].data.gateway_job_id, 'gateway-id');
});

test('failed preparation never sends WhatsApp', async () => {
  let sends = 0;
  const http = async (url, data) => {
    if (data.action === 'claim') return ok({ job: { ...job, reply_text: 'Cached answer' } });
    if (data.action === 'prepare_send') return { status: 409, data: { ok: false } };
    if (url === config.gatewayUrl) sends++;
    return ok({});
  };
  await assert.rejects(processOne(config, { http }));
  assert.equal(sends, 0);
});

test('gateway timeout records unknown and never repeats send', async () => {
  const completions = [];
  let sends = 0;
  const http = async (url, data) => {
    if (data.action === 'claim') return ok({ job: { ...job, reply_text: 'Cached answer' } });
    if (url === config.gatewayUrl) { sends++; throw new Error('timeout'); }
    if (data.action === 'complete') completions.push(data);
    return ok({});
  };
  await processOne(config, { http, log() {} });
  assert.equal(sends, 1);
  assert.equal(completions[0].status, 'unknown');
});

test('AI failure requests bounded retry without sending', async () => {
  const actions = [];
  const http = async (url, data) => {
    if (data.action) actions.push(data.action);
    if (data.action === 'claim') return ok({ job });
    if (url === config.aiUrl) return { status: 401, data: null };
    assert.notEqual(url, config.gatewayUrl);
    return ok({});
  };
  await processOne(config, { http, log() {} });
  assert.deepEqual(actions, ['claim', 'ai_error']);
});
