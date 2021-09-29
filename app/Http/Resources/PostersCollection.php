<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostersCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->collection->transform(function ($poster) {
            return [
                'id' => $poster->id,
                'name' => $poster->name,
                'file_name' => $poster->file_name,
                'show_in_rotation' => $poster->show_in_rotation,
                'ordinal' => $poster->ordinal,
                'can_delete' => $poster->can_delete,
                'imdb_id' => $poster->imdb_id,
                'mpaa_rating' => $poster->mpaa_rating,
                'audience_rating' => $poster->audience_rating,
                'trailer_path' => $poster->trailer_path,
                'show_runtime' => $poster->show_runtime,
                'show_trailer' => $poster->show_trailer,
                'runtime' => $poster->runtime,
                'play_theme_music' => $poster->play_theme_music,
                'theme_music_path' => $poster->theme_music_path,
                'show' => false,
                'image' => null,
                'music' => null,
            ];
        });
    }
}
