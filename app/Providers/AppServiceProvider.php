<?php

namespace App\Providers;

use App\Contracts\ModuleAccessContract;
use App\Services\Saas\SaasModuleAccessService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleAccessContract::class, SaasModuleAccessService::class);
    }
}
