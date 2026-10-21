<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'usage_date',
        'request_count',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'error_details',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'request_count' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];
}
