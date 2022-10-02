<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'show_bottom_text',
        'bottom_text',
        'jellyfin_service',
        'jellyfin_ip_address',
        'jellyfin_token',
        'kodi_service',
        'kodi_url',
        'kodi_port',
        'kodi_username',
        'kodi_password',
        'tmdb_api_key_v3',
        'tmdb_api_key_v4',
        'header_bg_color',
        'header_text_color',
        'show_header_border',
        'header_border_color',
        'header_font',
        'header_font_size',
        'footer_bg_color',
        'footer_text_color',
        'poster_bg_color',
        'validate_movie_titles',
        'remove_black_bars',
        'mpaa_limit'
    ];

    protected function plexService(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function jellyfinService(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function kodiService(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function randomOrder(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showHeaderBorder(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showMpaaRating(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showAudienceRating(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showProcessingLogos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyAtmosHorizontal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyAtmosVertical(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyVisionHorizontal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolbyVisionVertical(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDts(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showDolby51(): Attribute
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

    protected function showAuro3d(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function useCecPower(): Attribute
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

    protected function useGlobalPrologos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function useGlobalPrologosIfNoPosterPrologos(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function showBottomText(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function validateMovieTitles(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }

    protected function removeBlackBars(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? true : false,
        );
    }
}
