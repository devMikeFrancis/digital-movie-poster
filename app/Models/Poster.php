<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poster extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'object_id',
        'file_name',
        'ordinal',
        'show_in_rotation',
        'can_delete',
        'imdb_id',
        'mpaa_rating',
        'audience_rating',
        'trailer_path'
    ];
}
