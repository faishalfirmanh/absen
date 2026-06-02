<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LiburResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'keterangan' => $this->keterangan,
            'date_holiday' => $this->date_holiday,
            // Bisa tambahkan format tanggal yang lebih rapi jika perlu:
            // 'date_formatted' => \Carbon\Carbon::parse($this->date_holiday)->translatedFormat('d F Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
