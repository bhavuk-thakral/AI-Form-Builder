<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'version_number',
        'schema',
        'description',
        'created_by',
    ];

    protected $casts = [
        'schema' => 'array',
        'version_number' => 'integer',
    ];

    /**
     * Get the form associated with the version.
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the user who created this version.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get submissions made against this version.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
