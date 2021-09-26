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
                'imdb_id' => $poster->imdb,
                'mpaa_rating' => $poster->mpaa_rating,
                'audience_rating' => $poster->audience_rating,
                'youtube_id' => $poster->trailer_path,
                'show' => false,
            ];
        });
    }
}
