<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'media_type' => 'required|string',
            'imdb_id' => 'nullable|string',
            'name' => 'required_without:imdb_id|nullable|string',
            'audience_rating' => 'nullable',
            'mpaa_rating' => 'nullable|string',
            'trailer_path' => 'nullable|string',
            'runtime' => 'nullable|integer',
            'image' => 'nullable|sometimes|image|max:8192|dimensions:max_width=3000,max_height=6000',
            'show_trailer' => 'required|boolean',
            'show_runtime' => 'required|boolean',
            'show_in_rotation' => 'required|boolean',
            'play_theme_music' => 'required|boolean',
            'show_dolby_atmos' => 'required|boolean',
            'show_dolby_51' => 'required|boolean',
            'show_dolby_vision' => 'required|boolean',
            'show_dtsx' => 'required|boolean',
            'show_imax' => 'required|boolean',
            'show_auro_3d' => 'required|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
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
        ]);
    }
}
