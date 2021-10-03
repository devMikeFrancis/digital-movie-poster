<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PosterRequest extends FormRequest
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
            'image' => 'sometimes|image|max:8192|dimensions:max_width=3000,max_height=6000'
        ];
    }

    public function formatted()
    {
        $name = Str::slug($this->title);
        $fileName = $name.'.jpg';

        return [
            'file_name' => $fileName,
            'name' => $name,
            'imdb_id' => $this->imdb_id,
            'audience_rating' => $this->audience_rating,
            'mpaa_rating' => $this->mpaa_rating,
            'trailer_path' => $this->trailer_path,
            'runtime' => $this->runtime,
            'show_trailer' => $this->boolean('show_trailer'),
            'show_runtime' => $this->boolean('show_runtime'),
            'show_in_rotation' => $this->boolean('show_in_rotation'),
            'play_theme_music' => $this->boolean('play_theme_music'),
            'show_dolby_atmos' => $this->boolean('show_dolby_atmos'),
            'show_dolby_51' => $this->boolean('show_dolby_51'),
            'show_dolby_vision' => $this->boolean('show_dolby_vision'),
            'show_dtsx' => $this->boolean('show_dtsx'),
            'show_imax' => $this->boolean('show_imax'),
            'show_auro_3d' => $this->boolean('show_auro_3d'),
        ];
    }
}
