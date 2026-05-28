<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard' => ['dashboard.view'],
            'patients' => ['patients.view', 'patients.create', 'patients.update', 'patients.delete'],
            'appointments' => ['appointments.view', 'appointments.create', 'appointments.update', 'appointments.cancel'],
            'sessions' => ['sessions.view', 'sessions.create', 'sessions.update', 'sessions.delete'],
            'exercises' => ['exercises.view', 'exercises.create', 'exercises.update', 'exercises.delete'],
            'files' => ['files.view', 'files.upload', 'files.delete'],
            'payments' => ['payments.view', 'payments.create', 'payments.update', 'payments.delete'],
            'activities' => ['activities.view', 'activities.create', 'activities.update', 'activities.complete', 'activities.delete'],
            'roles' => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
            'permissions' => ['permissions.view', 'permissions.create', 'permissions.update'],
            'users' => ['users.view', 'users.create', 'users.update'],
            'settings' => ['settings.view', 'settings.update'],
            'reports' => ['reports.view'],
            'logs' => ['logs.view'],
            'patient_portal' => ['patient_portal.view'],
            'appointment_requests' => [
                'appointment_requests.view',
                'appointment_requests.create',
                'appointment_requests.update',
                'appointment_requests.approve',
            ],
            'legal_documents' => [
                'legal_documents.view',
                'legal_documents.accept',
                'legal_documents.revoke',
                'legal_documents.print',
                'legal_documents.configure',
            ],
            'compliance' => [
                'compliance.view',
                'compliance.manage',
            ],
        ];

        foreach ($permissions as $module => $slugs) {
            foreach ($slugs as $slug) {
                $action = strtoupper((string) str($slug)->after('.'));
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => "{$action} {$module}",
                        'module' => $module,
                        'description' => "Permiso {$slug}",
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
