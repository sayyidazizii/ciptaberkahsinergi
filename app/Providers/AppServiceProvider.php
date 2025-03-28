<?php

namespace App\Providers;

use App\Models\User;
use App\Models\SystemMenu;
use App\Core\Adapters\Theme;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $theme = theme();

        // Share theme adapter class
        View::share('theme', $theme);

        // Set demo globally
        // $theme->setDemo(request()->input('demo', 'demo2'));
        $theme->setDemo('demo2');

        $theme->initConfig();
        Gate::define('viewPulse', function (User $user) {
            return $user->isDev();
        });
        Pulse::user(fn ($user) => [
            'name' => $user->username,
        ]);
        bootstrap()->run();

        if (isRTL()) {
            // RTL html attributes
            Theme::addHtmlAttribute('html', 'dir', 'rtl');
            Theme::addHtmlAttribute('html', 'direction', 'rtl');
            Theme::addHtmlAttribute('html', 'style', 'direction:rtl;');
        }
        RedirectResponse::macro('msg',function($string){
            return $this->with('pesan', $string);
        });
        RedirectResponse::macro('success',function($string){
            return $this->with(['pesan'=> $string,'alert'=>'success']);
        });
        RedirectResponse::macro('error',function($string){
            return $this->with(['pesan'=> $string,'alert'=>'error']);
        });
        RedirectResponse::macro('danger',function($string){
            return $this->with(['pesan'=> $string,'alert'=>'error']);
        });
        RedirectResponse::macro('warning',function($string){
            return $this->with(['pesan'=> $string,'alert'=>'warning']);
        });
        RedirectResponse::macro('info',function($string){
            return $this->with(['pesan'=> $string,'alert'=>'info']);
        });

    }
}
