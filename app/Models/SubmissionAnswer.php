<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'field_key',
        'answer_value',
    ];

    /**
     * Get the submission that owns the answer.
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
