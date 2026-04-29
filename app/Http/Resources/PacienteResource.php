<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacienteResource extends JsonResource {

    public function toArray(Request $request): array
    {
        $nombres = $this->nombres;
        $apellidoPaterno = $this->apellido_paterno;
        $apellidoMaterno = $this->apellido_materno;

        return [
            'id' => $this->id,

            'nombres' => $nombres,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,

            'full_name' => trim(implode(' ', array_filter([
                $nombres,
                $apellidoPaterno,
                $apellidoMaterno,
            ]))),

            'tipo' => $this->tipo,
            'status' => $this->status,
            'telefono' => $this->telefono,
            'email' => $this->resource->email ?? null,
            'sexo' => $this->sexo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'direccion' => $this->direccion,
            'contacto_emergencia_nombre' => $this->contacto_emergencia_nombre,
            'contacto_emergencia_telefono' => $this->contacto_emergencia_telefono,
            'notas' => $this->notas,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

}