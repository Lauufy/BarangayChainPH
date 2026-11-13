<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

/** Registers application-wide framework integrations and audit hooks. */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('service-submissions', function (Request $request) {
            $key = $request->user()
                ? 'user:'.$request->user()->getAuthIdentifier()
                : 'ip:'.$request->ip();
            $response = fn (Request $request, array $headers) => response()->view(
                'citizen.service-requests.throttled',
                [],
                429,
                $headers
            );

            return [
                Limit::perMinute(5)->by($key)->response($response),
                Limit::perHour(30)->by($key)->response($response),
            ];
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof Model) {
                app(AuditLogger::class)->log('login', $event->user, actor: $event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof Model) {
                app(AuditLogger::class)->log('logout', $event->user, actor: $event->user);
            }
        });

        Queue::before(fn (JobProcessing $event) => AuditLogger::beginBackgroundExecution());
        Queue::after(fn (JobProcessed $event) => AuditLogger::endBackgroundExecution());
        Queue::exceptionOccurred(fn (JobExceptionOccurred $event) => AuditLogger::endBackgroundExecution());

        Event::listen(ScheduledTaskStarting::class, fn () => AuditLogger::beginBackgroundExecution());
        Event::listen(ScheduledTaskFinished::class, fn () => AuditLogger::endBackgroundExecution());
        Event::listen(ScheduledTaskFailed::class, fn () => AuditLogger::endBackgroundExecution());

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
