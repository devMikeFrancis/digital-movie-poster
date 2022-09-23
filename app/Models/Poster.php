<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'trailer_path',
        'runtime',
        'show_runtime',
        'show_trailer',
        'play_theme_music',
        'theme_music_path',
        'show_dolby_atmos',
        'show_dolby_51',
        'show_dolby_vision',
        'show_dtsx',
        'show_auro_3d',
        'show_imax',
    ];

    protected $appends = ['title'];

    protected function getTitleAttribute()
    {
        $lowers = [
        'of','a','the','and','an','or','nor','but','is','if','then','else','when',
        'at','from','by','on','off','for','in','out','over','to','into','with'
        ];
        $title = Str::headline($this->name);
        $title = str_replace(" Iii", " III", $title);
        $title = str_replace(" IIi", " III", $title);
        $title = str_replace(" Ii", " II", $title);

        return $title;
    }

    protected function showInRotation(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function canDelete(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyAtmos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyVision(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDtsx(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showImax(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showTrailer(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showRuntime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function playThemeMusic(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }
}
