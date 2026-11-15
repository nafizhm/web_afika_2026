<?php

namespace Afika\WhatsApp;

use PDO;
use PDOException;

/**
 * Shared by Laravel and the standalone hosting endpoint. PHP 8.2+, PDO.
 * Accepts one incoming Baileys WAMessage per request, never outgoing messages.
 */
final class Receiver
{
    public static function handle(PDO $pdo, string $raw, string $authorization, string $secret, string $account): array
    {
        $validation = self::validate($raw, $authorization, $secret, $account);
        if (isset($validation['response'])) return $validation['response'];

        $row = $validation['row'];
        $columns = array_keys($row);
        $statement = $pdo->prepare('INSERT INTO whatsapp_messages ('.implode(',', $columns).') VALUES ('.implode(',', array_fill(0, count($columns), '?')).')');
        try {
            $statement->execute(array_values($row));
        } catch (PDOException $exception) {
            // Only acknowledge an actual existing message; unrelated DB failures must retry.
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                $existing = $pdo->prepare('SELECT id FROM whatsapp_messages WHERE dedup_key = ?');
                $existing->execute([$row['dedup_key']]);
                if ($existing->fetchColumn() !== false) return [200, ['ok' => true, 'duplicate' => true]];
            }
            throw $exception;
        }
        return [201, ['ok' => true, 'duplicate' => false]];
    }

    public static function validate(string $raw, string $authorization, string $secret, string $account): array
    {
        $respond = fn ($status, $error) => ['response' => [$status, ['ok' => false, 'error' => $error]]];
        if (strlen($secret) < 32) return $respond(503, 'Webhook secret is not configured.');
        if (!hash_equals('Bearer '.$secret, $authorization)) return $respond(401, 'Unauthorized.');
        if (strlen($raw) > 2 * 1024 * 1024) return $respond(413, 'Payload too large.');
        try { $payload = json_decode($raw, true, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return $respond(422, 'Invalid JSON.'); }
        if (!is_array($payload) || !is_string($payload['account'] ?? null) || $payload['account'] !== $account) {
            return $respond(422, 'Invalid receiving account.');
        }
        $message = $payload['message'] ?? null;
        $key = is_array($message) ? ($message['key'] ?? null) : null;
        if (!is_array($key) || !is_bool($key['fromMe'] ?? null)) return $respond(422, 'Invalid message key.');
        foreach (['id', 'remoteJid'] as $field) {
            if (!is_string($key[$field] ?? null) || $key[$field] === '' || strlen($key[$field]) > 191) {
                return $respond(422, 'Invalid message identifier.');
            }
        }
        if ($key['fromMe'] || $key['remoteJid'] === 'status@broadcast') {
            return ['response' => [202, ['ok' => true, 'ignored' => true]]];
        }
        $content = $message['message'] ?? null;
        if (!is_array($content) || !$content) return $respond(422, 'Missing message content.');
        // Unwrap common ephemeral/view-once containers without changing the stored original.
        for ($i = 0; $i < 8; $i++) {
            $nested = null;
            foreach (['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2', 'viewOnceMessageV2Extension', 'documentWithCaptionMessage'] as $wrapper) {
                if (isset($content[$wrapper]['message']) && is_array($content[$wrapper]['message'])) {
                    $nested = $content[$wrapper]['message']; break;
                }
            }
            if ($nested === null) break;
            $content = $nested;
        }
        $type = null;
        foreach (array_keys($content) as $candidate) {
            if ($candidate === 'conversation' || (is_string($candidate) && str_ends_with($candidate, 'Message') && $candidate !== 'senderKeyDistributionMessage')) {
                $type = $candidate; break;
            }
        }
        if ($type === null || strlen($type) > 80) $type = 'unknown';
        $node = is_array($content[$type] ?? null) ? $content[$type] : [];
        $body = $content['conversation'] ?? $node['text'] ?? $node['caption'] ?? $node['selectedDisplayText'] ?? $node['title'] ?? null;
        if (!is_string($body)) $body = null;
        $sender = $key['participant'] ?? $key['remoteJid'];
        if (!is_string($sender) || strlen($sender) > 191) return $respond(422, 'Invalid sender.');
        $senderAlt = str_ends_with($key['remoteJid'], '@g.us') ? ($key['participantAlt'] ?? null) : ($key['remoteJidAlt'] ?? null);
        $phone = null;
        foreach ([$senderAlt, $sender] as $jid) {
            if (is_string($jid) && preg_match('/^([0-9]{5,20})(?::[0-9]+)?@s[.]whatsapp[.]net$/', $jid, $match)) {
                $phone = $match[1]; break;
            }
        }
        $name = $message['pushName'] ?? null;
        if ($name !== null && (!is_string($name) || mb_strlen($name) > 255)) return $respond(422, 'Invalid sender name.');
        $timestamp = $message['messageTimestamp'] ?? null;
        if ($timestamp !== null && (!is_int($timestamp) || $timestamp < 0 || $timestamp > 4102444800)) {
            return $respond(422, 'Invalid timestamp; use Unix seconds.');
        }
        $now = gmdate('Y-m-d H:i:s');
        return ['row' => [
            'dedup_key' => hash('sha256', json_encode([$account, $key['remoteJid'], $key['id']], JSON_THROW_ON_ERROR)),
            'message_id' => $key['id'], 'account' => $account,
            'chat_jid' => $key['remoteJid'], 'sender_jid' => $sender,
            'sender_phone' => $phone, 'sender_name' => $name, 'message_type' => $type,
            'body' => $body, 'raw_payload' => $raw,
            'sent_at' => $timestamp === null ? null : gmdate('Y-m-d H:i:s', $timestamp),
            'created_at' => $now, 'updated_at' => $now,
        ]];
    }
}

