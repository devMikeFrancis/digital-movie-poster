<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'plex_service' => 'required|boolean',
            'plex_sync_movies' => 'required|boolean',
            'plex_sync_tv' => 'required|boolean',
            'plex_show_movie_now_playing' => 'required|boolean',
            'plex_show_tv_now_playing' => 'required|boolean',
            'random_order' => 'required|boolean',
            'plex_ip_address' => 'required_if:plex_service,true|nullable|string',
            'plex_token' => 'required_if:plex_service,true|nullable|string',
            'poster_display_speed' => 'required|integer|min:2000',
            'poster_display_limit' => 'nullable',
            'coming_soon_text' => 'nullable|string|max:30',
            'now_playing_text' => 'nullable|string|max:30',
            'show_mpaa_rating' => 'required|boolean',
            'show_audience_rating' => 'required|boolean',
            'show_processing_logos' => 'required|boolean',
            'show_dolby_atmos_horizontal' => 'required|boolean',
            'show_dolby_atmos_vertical' => 'required|boolean',
            'show_dolby_vision_horizontal' => 'required|boolean',
            'show_dolby_vision_vertical' => 'required|boolean',
            'show_dts' => 'required|boolean',
            'show_dolby_51' => 'required|boolean',
            'show_imax' => 'required|boolean',
            'show_auro_3d' => 'required|boolean',
            'use_cec_power' => 'required|boolean',
            'show_runtime' => 'required|boolean',
            'play_theme_music' => 'required|boolean',
            'use_global_prologos' => 'required|boolean',
            'use_global_prologos_if_no_poster_prologos' => 'required|boolean',
            'transition_type' => 'required|string',
            'show_bottom_text' => 'required|boolean',
            'bottom_text' => 'nullable|string',
            'jellyfin_service' => 'required|boolean',
            'jellyfin_ip_address' => 'required_if:jellyfin_service,true|nullable|string',
            'jellyfin_token' => 'required_if:jellyfin_service,true|nullable|string',
            'kodi_service' => 'required|boolean',
            'kodi_url' => 'required_if:kodi_service,true|nullable|string',
            'kodi_port' => 'required_if:kodi_service,true|nullable|integer',
            'kodi_username' => 'required_if:kodi_service,true|nullable|string',
            'kodi_password' => 'required_if:kodi_service,true|nullable|string',
            'tmdb_api_key_v3' => 'nullable|string',
            'tmdb_api_key_v4' => 'nullable|string',
            'header_bg_color' => 'nullable|string',
            'header_text_color' => 'nullable|string',
            'show_header_border' => 'required|boolean',
            'header_border_color' => 'nullable|string',
            'header_font' => 'required|string',
            'header_font_size' => 'required|string',
            'footer_bg_color' => 'nullable|string',
            'footer_text_color' => 'nullable|string',
            'poster_bg_color' => 'nullable|string',
            'validate_movie_titles' => 'required|boolean',
            'remove_black_bars' => 'required|boolean',
            'mpaa_limit' => 'nullable|string',
            'tv_limit' => 'nullable|string',
            'plex_movie_sections' => 'nullable',
            'plex_tv_sections' => 'nullable',
            'speaker_config' => 'nullable|string|max:20',
            'speaker_config_location' => 'nullable|string',
            'show_speaker_config' => 'required|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'plex_service' => $this->boolean('plex_service'),
            'show_mpaa_rating' => $this->boolean('show_mpaa_rating'),
            'show_audience_rating' => $this->boolean('show_audience_rating'),
            'show_processing_logos' => $this->boolean('show_processing_logos'),
            'show_dolby_vision_horizontal' => $this->boolean('show_dolby_vision_horizontal'),
            'show_dolby_atmos_horizontal' => $this->boolean('show_dolby_atmos_horizontal'),
            'show_dolby_atmos_vertical' => $this->boolean('show_dolby_atmos_vertical'),
            'show_dolby_vision_vertical' => $this->boolean('show_dolby_vision_vertical'),
            'show_dts' => $this->boolean('show_dts'),
            'show_dolby_51' => $this->boolean('show_dolby_51'),
            'show_imax' => $this->boolean('show_imax'),
            'show_auro_3d' => $this->boolean('show_auro_3d'),
            'use_cec_power' => $this->boolean('use_cec_power'),
            'play_theme_music' => $this->boolean('play_theme_music'),
            'use_global_prologos' => $this->boolean('use_global_prologos'),
            'use_global_prologos_if_no_poster_prologos' => $this->boolean('use_global_prologos_if_no_poster_prologos'),
            'show_bottom_text' => $this->boolean('show_bottom_text'),
            'jellyfin_service' => $this->boolean('jellyfin_service'),
            'show_header_border' => $this->boolean('show_header_border'),
            'validate_movie_titles' => $this->boolean('validate_movie_titles'),
            'remove_black_bars' => $this->boolean('remove_black_bars'),
            'plex_sync_movies' => $this->boolean('plex_sync_movies'),
            'plex_sync_tv' => $this->boolean('plex_sync_tv'),
            'plex_show_movie_now_playing' => $this->boolean('plex_show_movie_now_playing'),
            'plex_show_tv_now_playing' => $this->boolean('plex_show_tv_now_playing'),
            'show_speaker_config' => $this->boolean('show_speaker_config'),
        ]);
    }
}
