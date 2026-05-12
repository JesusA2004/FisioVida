<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Schema;
use App\Models\StoredFile;
use App\Models\Persona;
use App\Policies\FilePolicy;
use App\Policies\PatientPolicy;

class AppServiceProvider extends ServiceProvider {

    // Register any application services.
    public function register(): void {
        //
    }

    // Bootstrap any application services.
    public function boot(): void {
        $this->configureDefaults();
        Schema::defaultStringLength(191);

        Gate::policy(StoredFile::class, FilePolicy::class);
        Gate::policy(Persona::class, PatientPolicy::class);
    }

    // Configure default behaviors for production-ready applications.
    protected function configureDefaults(): void {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

}
