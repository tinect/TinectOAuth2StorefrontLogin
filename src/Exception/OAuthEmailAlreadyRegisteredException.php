<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

final class OAuthEmailAlreadyRegisteredException extends OAuthException
{
    public function __construct()
    {
        parent::__construct('An account with this email address exists and automatic linking is disabled.');
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.emailAlreadyRegistered';
    }
}
