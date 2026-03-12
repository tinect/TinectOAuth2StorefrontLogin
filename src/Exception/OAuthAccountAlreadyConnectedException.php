<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

class OAuthAccountAlreadyConnectedException extends OAuthException
{
    public function __construct()
    {
        parent::__construct('This OAuth identity is already connected to a different customer account.');
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.accountAlreadyConnected';
    }
}
