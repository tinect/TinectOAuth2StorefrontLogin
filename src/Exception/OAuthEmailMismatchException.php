<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

class OAuthEmailMismatchException extends OAuthException
{
    public function __construct()
    {
        parent::__construct('The email address provided by the OAuth provider does not match your account email.');
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.emailMismatch';
    }
}
