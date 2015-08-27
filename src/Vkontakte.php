<?php

namespace J4k\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Http\Message\ResponseInterface;


class Vkontakte extends AbstractProvider
{
    /**
     * OAuth URL.
     *
     * @const string
     */
    const BASE_VK_URL = 'https://oauth.vk.com';

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'user_id';

    const API_VERSION = '5.37';

    public $scopes = ['email'];
    public $uidKey = 'user_id';
    public $responseType = 'json';
    
    public function getAccessToken($grant = 'authorization_code', $params = [])
    {
        return parent::getAccessToken($grant, $params);

        if (is_string($grant)) {
            // PascalCase the grant. E.g: 'authorization_code' becomes 'AuthorizationCode'
            $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $grant)));
            $grant = 'League\\OAuth2\\Client\\Grant\\'.$className;
            if (! class_exists($grant)) {
                throw new \InvalidArgumentException('Unknown grant "'.$grant.'"');
            }
            $grant = new $grant();
        } elseif (! $grant instanceof GrantInterface) {
            $message = get_class($grant).' is not an instance of League\OAuth2\Client\Grant\GrantInterface';
            throw new \InvalidArgumentException($message);
        }

        $defaultParams = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => $grant,
        ];

        $requestParams = $grant->prepRequestParams($defaultParams, $params);

        try {
            switch (strtoupper($this->method)) {
                case 'GET':
                    // @codeCoverageIgnoreStart
                    // No providers included with this library use get but 3rd parties may
                    $client = $this->getHttpClient();
                    $client->setBaseUrl($this->urlAccessToken() . '?' . $this->httpBuildQuery($requestParams, '', '&'));
                    $request = $client->get(null, null, $requestParams)->send();
                    $response = $request->getBody();
                    break;
                    // @codeCoverageIgnoreEnd
                case 'POST':
                    $client = $this->getHttpClient();
                    $client->setBaseUrl($this->urlAccessToken());
                    $request = $client->post(null, null, $requestParams)->send();
                    $response = $request->getBody();
                    break;
                // @codeCoverageIgnoreStart
                default:
                    throw new \InvalidArgumentException('Neither GET nor POST is specified for request');
                // @codeCoverageIgnoreEnd
            }
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $response = $e->getResponse()->getBody();
            // @codeCoverageIgnoreEnd
        }

        switch ($this->responseType) {
            case 'json':
                $result = json_decode($response, true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    $result = [];
                }

                break;
            case 'string':
                parse_str($response, $result);
                break;
        }

        if (isset($result['error']) && ! empty($result['error'])) {
            // @codeCoverageIgnoreStart
            throw new IDPException($result);
            // @codeCoverageIgnoreEnd
        }

        $result = $this->prepareAccessTokenResult($result);

        $accessToken = $grant->handleResponse($result);

        // Add email from response
        if (!empty($result['email'])) {
            $accessToken->email = $result['email'];
        }
        return $accessToken;
    }

//    public function userDetails($response, AccessToken $token)
//    {
//        $response = $response->response[0];
//
//        $user = new User();
//
//        $email = (isset($token->email)) ? $token->email : null;
//        $location = (isset($response->country)) ? $response->country : null;
//        $description = (isset($response->status)) ? $response->status : null;
//
//        $user->exchangeArray([
//            'uid' => $response->uid,
//            'nickname' => $response->nickname,
//            'name' => $response->screen_name,
//            'firstname' => $response->first_name,
//            'lastname' => $response->last_name,
//            'email' => $email,
//            'location' => $location,
//            'description' => $description,
//            'imageUrl' => $response->photo_200_orig,
//        ]);
//
//        return $user;
//    }

//    public function userUid($response, AccessToken $token)
//    {
//        $response = $response->response[0];
//
//        return $response->uid;
//    }
//
//    public function userEmail($response, AccessToken $token)
//    {
//        return (isset($token->email)) ? $token->email : null;
//    }
//
//    public function userScreenName($response, AccessToken $token)
//    {
//        $response = $response->response[0];
//
//        return [$response->first_name, $response->last_name];
//    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseVkUrl().'/authorize';
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
        return $this->getBaseVkUrl().'/access_token';
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

//        return "https://api.vk.com/method/users.get?user_id={$token->getResourceOwnerId()}".
//        "&v=".static::API_VERSION."&access_token={$token}";

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
        return [ 'email', 'wall' ];
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
            $message = $data['error']['type'].': '.$data['error']['message'];
            throw new IdentityProviderException($message, $data['error']['code'], $data);
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
        return new VkUser($response);
    }

    /**
     * Get the base Vk URL.
     *
     * @return string
     */
    private function getBaseVkUrl()
    {
        return static::BASE_VK_URL;
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
        return $baseResponse['response'][0];
    }
}
