<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Tinect\OAuth2StorefrontLogin\Controller\OAuthIntent;

final class OAuthIntentTest extends TestCase
{
    public function testLoginSuccessRoute(): void
    {
        self::assertSame('frontend.account.home.page', OAuthIntent::LOGIN->successRoute());
    }

    public function testLoginErrorRoute(): void
    {
        self::assertSame('frontend.account.login.page', OAuthIntent::LOGIN->errorRoute());
    }

    public function testConnectSuccessRoute(): void
    {
        self::assertSame('frontend.account.profile.page', OAuthIntent::CONNECT->successRoute());
    }

    public function testConnectErrorRoute(): void
    {
        self::assertSame('frontend.account.profile.page', OAuthIntent::CONNECT->errorRoute());
    }
}