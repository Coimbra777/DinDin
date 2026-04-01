<?php

namespace App\Providers;

use App\Contracts\ModuleAccessContract;
use App\Services\Saas\SaasModuleAccessService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        Schema::defaultStringLength(255); // Increase string length
        \Carbon\Carbon::setLocale($this->app->getLocale());

        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        ResetPassword::toMailUsing(function ($notifiable, string $token) use ($expire) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Redefinir senha — '.config('app.name'))
                ->line('Recebemos um pedido para redefinir a senha da sua conta.')
                ->action('Definir nova senha', $url)
                ->line("Este link expira em {$expire} minutos.")
                ->line('Se você não pediu isso, ignore este e-mail.');
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input('username', '')).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey ?: $request->ip());
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->input('email', '').'|'.$request->ip());
        });

        RateLimiter::for('reset-password', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->input('email', '').'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('finance-api-mutations', function (Request $request) {
            $perMinute = max(1, (int) config('finance.api_mutations_per_minute', 60));
            $user = $request->user();
            $key = $user !== null ? 'finance-mut:'.$user->id : 'finance-mut:guest:'.$request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleAccessContract::class, SaasModuleAccessService::class);
    }
}
