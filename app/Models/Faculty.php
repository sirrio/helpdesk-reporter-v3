<?php

namespace App\Models;

use Database\Factories\FacultyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name'])]
class Faculty extends Model
{
    /** @use HasFactory<FacultyFactory> */
    use HasFactory, SoftDeletes;

    public function degrees(): HasMany
    {
        return $this->hasMany(Degree::class);
    }

    /**
     * Get the attendances associated with the faculty name.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'faculty', 'name');
    }
}
