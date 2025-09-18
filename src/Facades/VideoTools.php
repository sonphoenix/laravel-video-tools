<?php

namespace sonphoenix\VideoTools\Facades;

use Illuminate\Support\Facades\Facade;

class VideoTools extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'video-tools';
    }
}
