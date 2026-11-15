<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'sort_order', 'is_active', 'faq_type', 'is_public'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_public' => 'boolean', 'sort_order' => 'integer'];
    }
}

