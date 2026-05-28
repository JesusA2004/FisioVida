<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'slug' => 'administrador', 'description' => 'Acceso completo al sistema.', 'status' => 'active'],
            ['name' => 'Recepción', 'slug' => 'recepcion', 'description' => 'Gestión de agenda y pacientes.', 'status' => 'active'],
            ['name' => 'Terapeuta', 'slug' => 'terapeuta', 'description' => 'Atención clínica y sesiones.', 'status' => 'active'],
            ['name' => 'Cobranza', 'slug' => 'cobranza', 'description' => 'Seguimiento de pagos e ingresos.', 'status' => 'active'],
            ['name' => 'Dirección', 'slug' => 'direccion', 'description' => 'Consulta de indicadores y reportes.', 'status' => 'active'],
            ['name' => 'Paciente', 'slug' => 'paciente', 'description' => 'Acceso al portal personal del paciente.', 'status' => 'active'],
        ];

        $permissionGroups = [
            'administrador' => Permission::query()->pluck('id')->all(),
            'recepcion' => Permission::query()->whereIn('slug', [
                'dashboard.view',
                'patients.view', 'patients.create', 'patients.update',
                'appointments.view', 'appointments.create', 'appointments.update', 'appointments.cancel',
                'activities.view', 'activities.create', 'activities.update',
                'files.view',
                'appointment_requests.view', 'appointment_requests.update', 'appointment_requests.approve',
            ])->pluck('id')->all(),
            'terapeuta' => Permission::query()->whereIn('slug', [
                'dashboard.view',
                'appointments.view',
                'patients.view',
                'sessions.view', 'sessions.create', 'sessions.update',
                'exercises.view', 'exercises.create', 'exercises.update',
                'files.view', 'files.upload',
                'activities.view', 'activities.create', 'activities.update', 'activities.complete',
                'appointment_requests.view',
            ])->pluck('id')->all(),
            'cobranza' => Permission::query()->whereIn('slug', [
                'dashboard.view',
                'patients.view',
                'payments.view', 'payments.create', 'payments.update',
                'reports.view',
            ])->pluck('id')->all(),
            'direccion' => Permission::query()->whereIn('slug', [
                'dashboard.view',
                'reports.view',
                'logs.view',
                'patients.view',
                'appointments.view',
                'sessions.view',
                'payments.view',
                'appointment_requests.view',
            ])->pluck('id')->all(),
            'paciente' => Permission::query()->whereIn('slug', [
                'dashboard.view',
                'patient_portal.view',
                'appointment_requests.create', 'appointment_requests.view',
            ])->pluck('id')->all(),
        ];

        foreach ($roles as $attrs) {
            $role = Role::query()->updateOrCreate(['slug' => $attrs['slug']], $attrs);
            $role->permissions()->sync($permissionGroups[$attrs['slug']] ?? []);
        }
    }
}
