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
        'current_loop',
        'max_loops',
        'test_path',
        'last_test_exit_code',
        'last_test_output',
        'last_test_summary',
        'modified_files',
        'patch_history',
        'auto_apply_fixes',
        'loop_started_at',
        'loop_completed_at',
        'termination_reason',
        'total_execution_time',
        'allowed_files',
    ];

    protected $casts = [
        'next_retry_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'loop_started_at' => 'datetime',
        'loop_completed_at' => 'datetime',
        'retry_count' => 'integer',
        'current_loop' => 'integer',
        'max_loops' => 'integer',
        'last_test_exit_code' => 'integer',
        'total_execution_time' => 'integer',
        'auto_apply_fixes' => 'boolean',
        'modified_files' => 'array',
        'patch_history' => 'array',
        'allowed_files' => 'array',
    ];
}
