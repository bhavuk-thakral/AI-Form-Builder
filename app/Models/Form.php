<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'schema',
        'views_count',
        'share_token',
    ];

    protected $casts = [
        'schema' => 'array',
        'views_count' => 'integer',
    ];

    /**
     * Get the user that owns the form.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the versions for the form.
     */
    public function versions()
    {
        return $this->hasMany(FormVersion::class);
    }

    /**
     * Get the submissions for the form.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get the activity logs for the form.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
