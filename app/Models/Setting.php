<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plex_service',
        'random_order',
        'plex_ip_address',
        'plex_token',
        'poster_display_speed',
        'poster_display_limit',
        'coming_soon_text',
        'now_playing_text',
        'show_mpaa_rating',
        'show_audience_rating',
        'show_processing_logos',
        'show_dolby_atmos_horizontal',
        'show_dolby_atmos_vertical',
        'show_dolby_vision_horizontal',
        'show_dolby_vision_vertical',
        'show_dts',
        'show_dolby_51',
        'show_imax',
        'show_auro_3d',
        'use_cec_power',
        'show_runtime',
        'play_theme_music',
        'use_global_prologos',
        'use_global_prologos_if_no_poster_prologos',
        'transition_type',
    ];
}
