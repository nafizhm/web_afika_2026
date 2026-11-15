<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SharedAiControl
{
    public function handle(array $payload): array
    {
        $connection = DB::connection();
        $locked = false;
        if ($connection->getDriverName() === 'mysql') {
            $locked = (int) $connection->selectOne("SELECT GET_LOCK('afika_whatsapp_ai_worker', 5) AS acquired")->acquired === 1;
            if (!$locked) throw new \RuntimeException('Worker busy.');
        }
        try {
            return $connection->transaction(function () use ($payload, $connection) {
                $row = $connection->table('whatsapp_ai_control')->where('id', 1)->lockForUpdate()->first();
                if (!$row) throw new \RuntimeException('AI control not initialized. Run migrations.');
                $previous = (bool) $row->enabled;
                $enabled = $previous;
                if ($payload['action'] === 'set_ai_enabled') {
                    $enabled = $payload['enabled'];
                    $connection->table('whatsapp_ai_control')->where('id', 1)->update([
                        'enabled' => (int) $enabled, 'updated_at' => gmdate('Y-m-d H:i:s'),
                    ]);
                    if (!$enabled || !$previous) {
                        if (Schema::hasTable('whatsapp_ai_replies')) {
                            $connection->table('whatsapp_ai_replies')->whereIn('status', ['pending', 'processing'])
                                ->update(['status' => 'cancelled', 'last_error' => 'AI disabled by administrator.']);
                        }
                    }
                }
                return ['ok' => true, 'ai_enabled' => $enabled];
            });
        } finally {
            if ($locked) $connection->select("SELECT RELEASE_LOCK('afika_whatsapp_ai_worker')");
        }
    }
}
