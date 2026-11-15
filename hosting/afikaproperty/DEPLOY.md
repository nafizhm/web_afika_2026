# Deploy Afika Property: website + webhook + one database

1. Activate domain afikaproperty.com and SSL on hosting. Use HTTPS (worker requires it).
2. Pull the updated repository. Set domain document root to <Laravel project>/public. Keep .env, vendor and storage above the public document root. Do not move only index.php; the webhook resolves .env from the parent of public.
3. Preserve existing hosting APP_KEY and database credentials. Merge values from production.env.example into hosting .env; it is a reference, not a ready credential file. Set APP_URL=https://afikaproperty.com and FAQ_SHARED_DATABASE=true. Use the existing combined database. Set WA_AI_WORKER_TOKEN to the existing AFIKA_WORKER_TOKEN on VPS. WHATSAPP_WEBHOOK_SECRET must match gateway callback Authorization (empty is compatible with the current unauthenticated callback).
4. Run from Laravel project directory:
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --class=AssistantFaqSeeder --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache
   php artisan view:cache
   Do not use migrate:fresh, rollback, or DatabaseSeeder on existing hosting data.
5. Transfer storage/app/public images/logo from the previous hosting. Ensure storage and bootstrap/cache are writable by hosting PHP.
6. Check https://afikaproperty.com/ and /admin. GET /wa-webhook-test.php should return ok; this does not verify MySQL. The public/wa-webhook-config.php file reads DB_* and worker config directly from the same Laravel .env; do not put credentials in tracked files.
7. On VPS, update the actual incomingCallbackUrl of Baileys session cobadong to https://afikaproperty.com/wa-webhook-test.php using your existing session configuration method. Keep incomingCallbackHeaders consistent with WHATSAPP_WEBHOOK_SECRET. Do not use /webhooks/whatsapp for AI; that legacy receiver only stores messages.
8. Update AFIKA_WEBHOOK_URL=https://afikaproperty.com/wa-webhook-test.php in the worker's .env.afika-ai. Preserve token, model and API key. Copy the updated integrations/baileys/afika-ai-worker.mjs to the worker's current script path on VPS and run:
   pm2 restart afika-ai-worker --update-env
   pm2 logs afika-ai-worker --lines 30 --nostream
   If gateway config needs restart, use its existing running process (wa-gateway-v7).
9. Test a new incoming message, AI enable/disable, a changed FAQ and changed assistant name. Verify database rows and actual WhatsApp delivery. Keep the old webhook available until callback and worker both use the new URL; avoid running two worker processes.

FAQ edits apply to new jobs, without synchronization. Internal identity/handoff FAQ entries stay hidden on the public website. Changing the domain does not require changing APP_KEY. These changes are prepared locally; no Git push, hosting deployment or VPS configuration was executed by Codex.
