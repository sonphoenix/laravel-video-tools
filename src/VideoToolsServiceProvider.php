<?php

namespace SonPhoenix\VideoTools;

use Illuminate\Support\ServiceProvider;

class VideoToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind VideoTools class to the container
        $this->app->singleton(VideoTools::class, function ($app) {
            return new VideoTools();
        });

        $this->app->alias(VideoTools::class, 'video-tools');
    }

    public function boot()
    {
        // Only publish config if we're running in a full Laravel app
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/video-tools.php' => config_path('video-tools.php'),
            ], 'config');
        }
    }
}