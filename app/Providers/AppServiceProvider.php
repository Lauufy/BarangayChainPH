<?php

namespace App\Providers;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Observers\AuditableObserver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ServiceRequest::observe(AuditableObserver::class);
        IssuedDocument::observe(AuditableObserver::class);

        if (app()->environment('production')) {
            URL::forceScheme('https');
            Artisan::call('storage:link');
        }

        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });
    }
}
