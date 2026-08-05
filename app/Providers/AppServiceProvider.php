<?php

namespace App\Providers;

use App\Contracts\OidcIdentityProvider;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\PetCareConsultationPolicy;
use App\Policies\PetProfilePolicy;
use App\Policies\ShelterCasePolicy;
use App\Policies\SupportTicketPolicy;
use App\Services\ApplicationAuthorizationGate;
use App\Services\JumbojettOidcIdentityProvider;
use App\Support\ReleaseHistoryRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OidcIdentityProvider::class, JumbojettOidcIdentityProvider::class);
        $this->app->singleton(ReleaseHistoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(ShelterCase::class, ShelterCasePolicy::class);
        Gate::policy(PetProfile::class, PetProfilePolicy::class);
        Gate::policy(
            PetCareConsultation::class,
            PetCareConsultationPolicy::class,
        );

        Gate::before(
            static fn (User $user, string $ability): ?bool => app(
                ApplicationAuthorizationGate::class,
            )->authorize($user, $ability),
        );

        View::composer('layouts.app', function (IlluminateView $view): void {
            $view->with('appVersion', app(ReleaseHistoryRepository::class)->version());
        });

        RateLimiter::for('public-login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });
    }
}
