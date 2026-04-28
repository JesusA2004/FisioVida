<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'label' => $this->label,
            'description' => $this->description,
            'enabled' => $this->enabled,
            'sort_order' => $this->sort_order,
            'settings' => $this->settings,
        ];
    }
}
