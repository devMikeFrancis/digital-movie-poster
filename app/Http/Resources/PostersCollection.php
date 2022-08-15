<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostersCollection extends ResourceCollection
{
    public static $wrap = 'posters';

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            $item['show'] = false;
            $item['openOptions'] = false;
            $item['image'] = null;
            $item['music'] = null;
            $item['show_dolby_51'] = $item['show_dolby_51'] ? true : false;
            $item['show_auro_3d'] = $item['show_auro_3d'] ? true : false;
            return $item;
        });
    }
}
