<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Auth\GitHub;

use Flarum\Forum\AuthenticationResponseFactory;
use Flarum\Forum\Controller\AbstractOAuth2Controller;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class GitHubAuthController extends AbstractOAuth2Controller implements RequestHandlerInterface
{
    /**
     * @var CAS Server
     */
    protected $mailSrv = 'dingstudio.cn';
    //protected $authUrl = 'https://cas.dingstudio.cn/cas/login';

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param AuthenticationResponseFactory $authResponse
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(AuthenticationResponseFactory $authResponse, SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->authResponse = $authResponse;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider($redirectUri)
    {
        include(dirname(__FILE__).'/lib/CASLogic.php');
        $redirectUri = (string) $request->getAttribute('originalUri', $request->getUri())->withQuery('');
        $session = $request->getAttribute('session');
        $queryParams = $request->getQueryParams();
        $ticket = array_get($queryParams, 'ticket');

        if (!$ticket) {
            //mCAS::CASLogin();
            return new RedirectResponse($this->authUrl.'?service='.urlencode($redirectUri));
        }

        $username = mCAS::CASLogin();
        $token = md5(uniqid());
        //$token = $provider->getAccessToken('authorization_code', compact('ticket'));


        return $this->response->make(
            'cas', $username,
            function (Registration $registration) use ($user, $provider, $token) {
                $registration
                    ->provideTrustedEmail($this->getEmailFromApi($username))
                    ->provideAvatar('http://1.gravatar.com/avatar/767fc9c115a1b989744c755db47feb60?s=200&r=pg&d=mp')
                    ->suggestUsername($username)
                    ->setPayload(array());
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationUrlOptions()
    {
        return ['scope' => ['user:email']];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentification(ResourceOwnerInterface $resourceOwner)
    {
        return [
            'email' => $resourceOwner->getEmail() ?: $this->getEmailFromApi()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuggestions(ResourceOwnerInterface $resourceOwner)
    {
        return [
            'username' => $resourceOwner->getNickname(),
            'avatarUrl' => array_get($resourceOwner->toArray(), 'avatar_url')
        ];
    }

    protected function getEmailFromApi(String $uid)
    {
        $email = array('email'=>$uid.'@'.$this->mailSrv);
        return $email['email'];
    }
}
