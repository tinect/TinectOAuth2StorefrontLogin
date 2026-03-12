<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

class OAuthClientNotFoundException extends OAuthException
{
    public function __construct(string $clientId)
    {
        parent::__construct(\sprintf('OAuth client "%s" not found.', $clientId));
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.clientNotFound';
    }
}
