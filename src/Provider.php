<?php
namespace SocialiteProviders\StackExchange;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

/**
 * https://api.stackexchange.com/docs/authentication
 * Class Provider.
 */
class Provider extends AbstractProvider implements ProviderInterface
{
    protected $version = '2.2';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://stackexchange.com/oauth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        // https://api.stackexchange.com/docs/authentication

        $session = $this->request->getSession();

        return $url.'?'.http_build_query(
            [
                'client_id'    => $this->clientId,
                'redirect_uri' => $this->redirectUrl,
                'scope'        => $this->formatScopes($this->scopes, $this->scopeSeparator),
                'state'        => $state,
            ],
            '',
            '&',
            $this->encodingType
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://stackexchange.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // https://api.stackexchange.com/docs/me
        $response = $this->getHttpClient()->get(
            'https://api.stackexchange.com/'.$this->version.
            '/me?'.http_build_query(
                [
                    'site'         => $this->getFromConfig('site'),
                    'access_token' => $token,
                    'key'          => $this->getFromConfig('key'),
                ]
            ),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * @param string $arrayKey
     */
    protected function getFromConfig($arrayKey)
    {
        return app()['config']['services.stackexchange'][$arrayKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id'       => $user['items'][0]['account_id'],
                'nickname' => $user['items'][0]['display_name'],
                'name'     => $user['items'][0]['display_name'],
                'avatar'   => $user['items'][0]['profile_image'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        parse_str($body, $data);

        return $data['access_token'];
    }
}
