<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentLoop extends Model
{
    protected $fillable = [
        'test_failure_content',
        'ai_analysis',
        'status',
        'failure_reason',
        'retry_count',
        'next_retry_at',
        'completed_at',
        'processing_started_at',
        'provider',
        'model',
    ];

    protected $casts = [
        'next_retry_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'retry_count' => 'integer',
    ];
}
