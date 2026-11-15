<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected function casts(): array
    {
        return ['raw_payload' => 'array', 'sent_at' => 'datetime'];
    }
}

