<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Exception;

class OAuthNoAccountFoundException extends OAuthException
{
    public function __construct()
    {
        parent::__construct('No existing customer account found for this OAuth identity.');
    }

    public function getSnippetKey(): string
    {
        return 'tinect-oauth.error.noAccountFound';
    }
}
