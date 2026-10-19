<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

final class OAuthEmailConfirmationRequiredException extends OAuthException
{
    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.emailConfirmationRequired';
    }
}
