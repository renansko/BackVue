<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    // public function boot()
    // {
    //     $rules = [
    //         'cpf_formater_to_db'      => \App\Rules\FormatoCpf::class,
    //     ];

    //     foreach ($rules as $name => $class) {
    //         $rule = new $class();

    //         $extension = static function ($attribute, $value) use ($rule) {
    //             return $rule->passes($attribute, $value);
    //         };

    //         $this->app['validator']->extend($name, $extension, $rule->message());
    //     }
    // }
}
