<?php declare(strict_types=1);

namespace Tinect\OAuth2StorefrontLogin\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tinect\OAuth2StorefrontLogin\Contract\RedirectBehaviour;
use Tinect\OAuth2StorefrontLogin\Exception\OAuthException;
use Tinect\OAuth2StorefrontLogin\Service\ClientLoader;
use Tinect\OAuth2StorefrontLogin\Service\CustomerResolver;

#[AutoconfigureTag('monolog.logger', ['channel' => 'tinect-oauth2-storefront-login'])]
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => ['storefront']])]
class OAuthController extends StorefrontController
{
    public function __construct(
        private readonly ClientLoader $clientLoader,
        private readonly CustomerResolver $customerResolver,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $tinectOauth2StorefrontLoginLogger,
    ) {
    }

    #[Route(
        path: '/account/oauth/{clientId}',
        name: 'widgets.tinect.oauth.redirect',
        defaults: [PlatformRequest::ATTRIBUTE_NO_STORE => true],
        methods: ['GET'],
    )]
    public function oauthRedirect(string $clientId, Request $request, SalesChannelContext $context): Response
    {
        $routes = OAuthIntent::LOGIN;

        try {
            $client = $this->clientLoader->load($clientId, $context->getContext());
        } catch (OAuthException $e) {
            $this->addOAuthFlash($e);

            return $this->redirectToRoute($routes->errorRoute(), $request->query->all());
        }

        $session = $request->getSession();

        $this->storeOAuthSession($session, $clientId, OAuthIntent::LOGIN, $request->getLocale());
        $session->set($this->getSuccessUrlKey($clientId), $this->getSuccessLoginUrl($request, $routes));
        $session->set($this->getErrorUrlKey($clientId), $this->generateUrl($routes->errorRoute(), $request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL));

        return new RedirectResponse($client->getLoginUrl(
            $session->get($this->getStateKey($clientId)),
            $this->buildRedirectBehaviour($clientId),
        ));
    }

    #[Route(
        path: '/account/oauth/{clientId}/connect',
        name: 'widgets.tinect.oauth.connect',
        defaults: [PlatformRequest::ATTRIBUTE_NO_STORE => true, PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true],
        methods: ['GET'],
    )]
    public function oauthConnect(string $clientId, Request $request, SalesChannelContext $context): Response
    {
        $routes = OAuthIntent::CONNECT;

        try {
            $client = $this->clientLoader->load($clientId, $context->getContext());
        } catch (OAuthException $e) {
            $this->addOAuthFlash($e);

            return $this->redirectToRoute($routes->errorRoute());
        }

        $session = $request->getSession();

        $this->storeOAuthSession($session, $clientId, OAuthIntent::CONNECT, $request->getLocale());
        $session->set($this->getSuccessUrlKey($clientId), $this->generateUrl($routes->successRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL));
        $session->set($this->getErrorUrlKey($clientId), $this->generateUrl($routes->errorRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL));

        return new RedirectResponse($client->getLoginUrl(
            $session->get($this->getStateKey($clientId)),
            $this->buildRedirectBehaviour($clientId),
        ));
    }

    #[Route(
        path: '/account/oauth/{clientId}/callback',
        name: 'tinect.oauth.callback',
        defaults: [PlatformRequest::ATTRIBUTE_NO_STORE => true],
        methods: ['GET'],
    )]
    public function callback(string $clientId, Request $request, SalesChannelContext $context): Response
    {
        $stateKey = $this->getStateKey($clientId);
        $intentKey = $this->getIntentKey($clientId);
        $session = $request->getSession();

        $locale = $session->get($this->getLocaleKey($clientId));
        $session->remove($this->getLocaleKey($clientId));
        if ($locale !== null && $this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }

        $sessionState = $session->get($stateKey);
        $intent = OAuthIntent::from($session->get($intentKey, OAuthIntent::LOGIN->value));

        $successUrl = $session->get($this->getSuccessUrlKey($clientId)) ?? $this->generateUrl($intent->successRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        $errorUrl = $session->get($this->getErrorUrlKey($clientId)) ?? $this->generateUrl($intent->errorRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);

        $session->remove($this->getSuccessUrlKey($clientId));
        $session->remove($this->getErrorUrlKey($clientId));

        if (!$sessionState || $sessionState !== $request->query->getString('state')) {
            $this->tinectOauth2StorefrontLoginLogger->warning('OAuth state validation failed', ['clientId' => $clientId]);
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.invalidState'));

            return new RedirectResponse($errorUrl);
        }

        $session->remove($stateKey);
        $session->remove($intentKey);

        $error = $request->query->getString('error');
        if ($error !== '') {
            $description = $request->query->getString('error_description', $error);
            $this->tinectOauth2StorefrontLoginLogger->warning('OAuth provider returned an error', ['clientId' => $clientId, 'error' => $error]);
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.providerError', ['%error%' => $description]));

            return new RedirectResponse($errorUrl);
        }

        $code = $request->query->getString('code');
        if ($code === '') {
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.missingCode'));

            return new RedirectResponse($errorUrl);
        }

        if ($intent === OAuthIntent::CONNECT) {
            return $this->handleConnect($clientId, $sessionState, $code, $context, $successUrl, $errorUrl);
        }

        return $this->handleLogin($clientId, $sessionState, $code, $context, $successUrl, $errorUrl);
    }

    #[Route(
        path: '/account/oauth/{clientId}/disconnect',
        name: 'widgets.tinect.oauth.disconnect',
        defaults: [PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true],
        methods: ['POST'],
    )]
    public function disconnect(string $clientId, SalesChannelContext $context): Response
    {
        try {
            $entity = $this->clientLoader->getEntity($clientId, $context->getContext());
            $this->customerResolver->disconnect($clientId, $context->getCustomer()->getId(), $context);
        } catch (\Exception $e) {
            $this->tinectOauth2StorefrontLoginLogger->error('OAuth disconnect failed', ['clientId' => $clientId, 'exception' => $e]);
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.disconnectFailed'));

            return $this->redirectToRoute('frontend.account.profile.page');
        }

        $this->addFlash(self::SUCCESS, $this->trans('tinect-oauth.disconnect.success', ['%provider%' => $entity->name]));

        return $this->redirectToRoute('frontend.account.profile.page');
    }

    private function handleLogin(string $clientId, string $state, string $code, SalesChannelContext $context, string $successUrl, string $errorUrl): Response
    {
        try {
            $entity = $this->clientLoader->getEntity($clientId, $context->getContext());
            $user = $this->clientLoader->load($clientId, $context->getContext(), $entity)
                ->getUser($state, $code, $this->buildRedirectBehaviour($clientId));

            $this->customerResolver->resolve($user, $clientId, $entity->name, $context, allowRegistration: !$entity->connectOnly, trustEmail: $entity->trustEmail, updateEmailOnLogin: $entity->updateEmailOnLogin, disablePasswordLogin: $entity->disablePasswordLogin);
        } catch (OAuthException $e) {
            $this->addOAuthFlash($e);

            return new RedirectResponse($errorUrl);
        } catch (\Exception $e) {
            $this->tinectOauth2StorefrontLoginLogger->error('OAuth login failed', ['clientId' => $clientId, 'exception' => $e]);
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.loginFailed'));

            return new RedirectResponse($errorUrl);
        }

        return new RedirectResponse($successUrl);
    }

    private function handleConnect(string $clientId, string $state, string $code, SalesChannelContext $context, string $successUrl, string $errorUrl): Response
    {
        if ($context->getCustomer() === null) {
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.loginRequired'));

            return new RedirectResponse($errorUrl);
        }

        try {
            $entity = $this->clientLoader->getEntity($clientId, $context->getContext());
            $user = $this->clientLoader->load($clientId, $context->getContext(), $entity)
                ->getUser($state, $code, $this->buildRedirectBehaviour($clientId));

            $this->customerResolver->connect($user, $clientId, $context->getCustomer()->getId(), $context, trustEmail: $entity->trustEmail, disablePasswordLogin: $entity->disablePasswordLogin);
        } catch (OAuthException $e) {
            $this->addOAuthFlash($e);

            return new RedirectResponse($errorUrl);
        } catch (\Exception $e) {
            $this->tinectOauth2StorefrontLoginLogger->error('OAuth connect failed', ['clientId' => $clientId, 'exception' => $e]);
            $this->addFlash(self::DANGER, $this->trans('tinect-oauth.error.connectFailed'));

            return new RedirectResponse($errorUrl);
        }

        $this->addFlash(self::SUCCESS, $this->trans('tinect-oauth.connect.success', ['%provider%' => $entity->name]));

        return new RedirectResponse($successUrl);
    }

    private function storeOAuthSession(SessionInterface $session, string $clientId, OAuthIntent $intent, string $locale): void
    {
        $session->set($this->getStateKey($clientId), Random::getString(32));
        $session->set($this->getIntentKey($clientId), $intent->value);
        $session->set($this->getLocaleKey($clientId), $locale);
    }

    private function buildRedirectBehaviour(string $clientId): RedirectBehaviour
    {
        return new RedirectBehaviour(
            expectState: true,
            redirectUri: $this->generateUrl(
                'tinect.oauth.callback',
                ['clientId' => $clientId],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        );
    }

    private function addOAuthFlash(OAuthException $e): void
    {
        $this->addFlash(self::DANGER, $this->trans($e->getSnippetKey(), $e->getSnippetParams()));
    }

    private function getStateKey(string $clientId): string
    {
        return 'tinect_oauth_state_' . $clientId;
    }

    private function getIntentKey(string $clientId): string
    {
        return 'tinect_oauth_intent_' . $clientId;
    }

    private function getLocaleKey(string $clientId): string
    {
        return 'tinect_oauth_locale_' . $clientId;
    }

    private function getSuccessUrlKey(string $clientId): string
    {
        return 'tinect_oauth_success_url_' . $clientId;
    }

    private function getErrorUrlKey(string $clientId): string
    {
        return 'tinect_oauth_error_url_' . $clientId;
    }

    private function getSuccessLoginUrl(Request $request, OAuthIntent $routes): string
    {
        if (!$request->query->has('redirectTo')) {
            $request->query->set('redirectTo', $routes->successRoute());
        }

        $actionResponse = $this->createActionResponse($request);

        if ($actionResponse instanceof RedirectResponse) {
            return $actionResponse->getTargetUrl();
        }

        return $this->generateUrl($routes->successRoute(), $request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
