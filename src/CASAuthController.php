<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Auth\CAS;

use Flarum\Forum\AuthenticationResponseFactory;
use Flarum\Forum\Controller\AbstractOAuth2Controller;
use Flarum\Settings\SettingsRepositoryInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class CASAuthController extends AbstractOAuth2Controller
{
    /**
     * @var CAS Server
     */
    protected $mailSrv = 'dingstudio.cn';
    protected $authUrl = 'https://passport.dingstudio.cn/sso/login';
    protected $signUrl = 'https://passport.dingstudio.cn/sso/api?format=json&action=verify&';

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
    public function handle(Request $request)
    {
        $redirectUri = (string) $request->getAttribute('originalUri', $request->getUri())->withQuery('');
        $ticket = !empty(htmlspecialchars(@$_REQUEST['token'])) ? htmlspecialchars($_REQUEST['token']) : null;
        if (is_null($ticket)) {
            header('Location: '.$this->authUrl.'?returnUrl='.urlencode($redirectUri));
            exit();
        }
        $result = file_get_contents($this->signUrl.'token='.$ticket.'&reqtime='.time());
        $userinfo = json_decode($result, true)['data'];
        $identification = ['email'  =>  $userinfo['username'].'@'.$this->mailSrv];
        $suggestions = $this->getSuggestions($userinfo);
        return $this->authResponse->make($request, $identification, $suggestions);
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider($redirectUri)
    {
        $appid = $this->settings->get('flarum-ext-auth-cas.app_id');
        $appkey = $this->settings->get('flarum-ext-auth-cas.app_secret');
        return new OAuth($appid, $appkey);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationUrlOptions()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentification(ResourceOwnerInterface $resourceOwner)
    {
        return [
            'email' => null ?: $this->getEmailFromApi()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuggestions($userinfo)
    {
        $username = preg_replace('/[^a-z0-9-_]/i', '', $userinfo['username']);
        if ($username == '')
        {
            $username = $userinfo['user_id'];
        }
        return [
            'username' =>  $userinfo['username'],
            'avatarUrl' => 'https://cas.dingstudio.cn/static/images/head.png'
        ];
    }
}
