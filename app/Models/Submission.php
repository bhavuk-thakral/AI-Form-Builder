<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'form_version_id',
        'ip_address',
        'user_agent',
        'duration_seconds',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the form associated with the submission.
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the specific version of the form this submission maps to.
     */
    public function version()
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    /**
     * Get the answers for the submission.
     */
    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class);
    }
}
