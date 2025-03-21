<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSetting extends Settings
{
    public string $version;
    public static function get($key): string{
        return app(SystemSetting::class)->$key;
    }
    public static function select($key): string{
        return self::get($key);
    }
    public static function group(): string
    {
        return 'system';
    }
}
