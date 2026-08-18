<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosterResource extends JsonResource
{
    public static $wrap = 'poster';

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['show_dolby_51'] = $data['show_dolby_51'] ? true : false;
        $data['show_auro_3d'] = $data['show_auro_3d'] ? true : false;

        return $data;
    }

    public function with($request)
    {
        return [
            'poster' => [
                'image' => null,
            ],
        ];
    }
}
