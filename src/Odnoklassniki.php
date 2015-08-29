<?php

namespace J4k\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Http\Message\ResponseInterface;


class Odnoklassniki extends AbstractProvider
{
    /**
     * OAuth URL.
     *
     * @const string
     */
    const BASE_OK_URL = 'https://connect.ok.ru/oauth';

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'user_id';

    const API_VERSION = '5.37';

    public $scopes = ['email'];
    public $uidKey = 'user_id';
    public $responseType = 'json';
    
    public function getAccessToken($grant = 'authorization_code', $params = [])
    {
        return parent::getAccessToken($grant, $params);
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseOkUrl().'/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
//        return $this->getBaseOkUrl().'/access_token';
        return 'http://api.odnoklassniki.ru/oauth/token.do';
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $fields = ['email',
            'nickname',
            'screen_name',
            'sex',
            'bdate',
            'city',
            'country',
            'timezone',
            'photo_50',
            'photo_100',
            'photo_200_orig',
            'has_mobile',
            'contacts',
            'education',
            'online',
            'counters',
            'relation',
            'last_seen',
            'status',
            'can_write_private_message',
            'can_see_all_posts',
            'can_see_audio',
            'can_post',
            'universities',
            'schools',
            'verified', ];

        return "https://api.vk.com/method/users.get?user_id={$token->getResourceOwnerId()}&fields="
        .implode(",", $fields)."&access_token={$token}&v=".static::API_VERSION;
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [ 'VALUABLE_ACCESS' ];
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $message = $data['error_description'];
            throw new IdentityProviderException($message, 0/*$data['error']*/, $data);
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new OkUser($response);
    }

    /**
     * Get the base Vk URL.
     *
     * @return string
     */
    private function getBaseOkUrl()
    {
        return static::BASE_OK_URL;
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);

        $baseResponse = $this->getResponse($request);
        var_dump($baseResponse); exit;
        return $baseResponse['response'][0];
    }
}
