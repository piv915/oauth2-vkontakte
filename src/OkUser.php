<?php
/**
 * Created by PhpStorm.
 * User: pix
 * Date: 27.08.15
 * Time: 19:01
 */

namespace J4k\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class OkUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param  array $response
     */
    public function __construct(array $response)
    {
        $this->data = $response;
        if (!empty($response['picture']['data']['url'])) {
            $this->data['picture_url'] = $response['picture']['data']['url'];
        }
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getField('id');
    }

    public function getEmail()
    {
        return $this->getField('email');
    }

    public function getName()
    {
        return $this->getField('first_name'). ' '. $this->getField('last_name');
    }

    public function getCity()
    {
        return $this->getField('city')['title'];
    }

    public function getGender()
    {
        $numeric = intval($this->getField('sex'));
        if (!$numeric)
            return null;
        return $numeric == 1 ? 'female' : 'male';
    }

    /**
     * Returns all the data obtained about the user.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Returns a field from the Graph node data.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    private function getField($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}