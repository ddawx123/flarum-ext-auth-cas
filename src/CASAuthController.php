<?php

/*
 * This file is part of Flarum.
 *
 * (c) David Ding <ding@dingstudio.cn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Auth\Github;

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class CASAuthController extends AbstractOAuth2Controller implements RequestHandlerInterface
{
    /**
     * @var CAS Server
     */
    protected $mailSrv = 'dingstudio.cn';
    //protected $authUrl = 'https://cas.dingstudio.cn/cas/login';

    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param ResponseFactory $response
     */
    public function __construct(ResponseFactory $response, SettingsRepositoryInterface $settings)
    {
        $this->response = $response;
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(Request $request): ResponseInterface
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

    private function getEmailFromApi(String $uid)
    {
        $email = array('email'=>$uid.'@'.$this->mailSrv);
        return $email['email'];
    }
}
