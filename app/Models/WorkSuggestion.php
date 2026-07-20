<?php

namespace App\Models;

use App\Models\VRACore\VRACWork;

class WorkSuggestion extends Suggestion
{
    protected $table = 'work_suggestions';

    protected $fillable = [
        'id',
        'work_id',
        'user_id',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    public function work()
    {
        return $this->belongsTo(VRACWork::class, 'work_id');
    }
}
