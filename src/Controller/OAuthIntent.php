<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Controller;

enum OAuthIntent: string
{
    case LOGIN = 'login';
    case CONNECT = 'connect';

    public function successRoute(): string
    {
        return match ($this) {
            self::LOGIN => 'frontend.account.home.page',
            self::CONNECT => 'frontend.account.profile.page',
        };
    }

    public function errorRoute(): string
    {
        return match ($this) {
            self::LOGIN => 'frontend.account.login.page',
            self::CONNECT => 'frontend.account.profile.page',
        };
    }
}
