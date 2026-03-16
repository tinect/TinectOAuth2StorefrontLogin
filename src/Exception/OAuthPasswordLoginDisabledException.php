<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class OAuthPasswordLoginDisabledException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('Password login is disabled for this account.');
    }

    public function getErrorCode(): string
    {
        return 'TINECT_OAUTH__PASSWORD_LOGIN_DISABLED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
