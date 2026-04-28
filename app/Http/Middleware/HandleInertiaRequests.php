<?php

namespace App\Http\Middleware;

use App\Models\ModuleSetting;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $settings = Schema::hasTable('system_settings') ? SystemSetting::keyValuePublic() : [];
        $modules = Schema::hasTable('module_settings') ? ModuleSetting::enabledMap() : [];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->roles()->select('roles.id', 'roles.name', 'roles.slug')->get() ?? [],
                'permissions' => $user?->allPermissionSlugs() ?? [],
                'is_super_admin' => $user?->isSuperAdmin() ?? false,
            ],
            'appSettings' => $settings,
            'enabledModules' => $modules,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
