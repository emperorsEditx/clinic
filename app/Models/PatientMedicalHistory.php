<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPatientRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientMedicalHistory extends Model
{
    use BelongsToPatientRecord, SoftDeletes;

    protected $fillable = ['entry_type', 'title', 'notes'];
}
