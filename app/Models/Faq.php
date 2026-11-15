<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'sort_order', 'is_active', 'faq_type', 'is_public', 'is_ai'];

    public function scopeAudience($query, string $audience)
    {
        return match ($audience) {
            'ai' => $query->where('is_ai', true)->where(fn($q) => $q->where('is_public', false)->orWhere('faq_type','!=','general')),
            'web' => $query->where('faq_type','general')->where('is_public',true)->where('is_ai',false),
            'both' => $query->where('faq_type','general')->where('is_public',true)->where('is_ai',true),
            default => $query,
        };
    }

    public function scopeForWebsite($query)
    {
        return $query->where('is_active', true)->where('faq_type', 'general')->where('is_public', true);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_public' => 'boolean', 'is_ai'=>'boolean', 'sort_order' => 'integer'];
    }
}

