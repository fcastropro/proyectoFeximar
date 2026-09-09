<?php

namespace App\Support;

class FeximarMailBranding
{
    public const LOGO_PUBLIC_PATH = 'home/images/flores/logo-oscuro.png';

    public const TAGLINE = 'Premium Flowers From Ecuador';

    public static function logoAbsoluteUrl(): string
    {
        return asset(self::LOGO_PUBLIC_PATH);
    }

    public static function logoFilesystemPath(): string
    {
        return public_path(self::LOGO_PUBLIC_PATH);
    }

    public static function logoExists(): bool
    {
        return is_file(self::logoFilesystemPath());
    }

    /**
     * @return array{logoUrl:string,brand:string,tagline:string,appUrl:string}
     */
    public static function sharedViewData(): array
    {
        return [
            'logoUrl' => self::logoAbsoluteUrl(),
            'brand' => 'FEXIMAR',
            'tagline' => self::TAGLINE,
            'appUrl' => rtrim((string) config('app.url'), '/'),
        ];
    }
}
