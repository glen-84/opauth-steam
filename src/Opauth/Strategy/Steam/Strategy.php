<?php

/**
 * Steam strategy for Opauth
 *
 * @link https://github.com/glen-84/opauth-steam
 * @license MIT License
 */

namespace Opauth\Strategy\Steam;

use Opauth\AbstractStrategy;

/**
 * Steam strategy for Opauth
 */
class Strategy extends AbstractStrategy
{
    protected $openId;

    public function request()
    {
        $this->loadOpenId();

        if ($this->openId->mode) {
            $error = array(
                'code' => 'bad_mode',
                'message' => 'Callback url is not set'
            );

            return $this->response($this->openId->data, $error);
        }

        $this->openId->identity = 'http://steamcommunity.com/openid';

        try{
            $url = $this->openId->authUrl();
        } catch (\ErrorException $e) {
            $error = array(
                'code' => 'bad_identifier',
                'message' => $e->getMessage()
            );

            return $this->response($this->openId->data, $error);
        }

        $this->http->redirect($url);
    }

    public function callback()
    {
        $this->loadOpenId();

        if ($this->openId->mode === 'cancel') {
            $error = array(
                'code' => 'cancel_authentication',
                'message' => 'User has canceled authentication'
            );

            return $this->response($this->openId->data, $error);
        }

        if (!$this->openId->validate()) {
            $error = array(
                'provider' => 'Steam',
                'code' => 'not_logged_in',
                'message' => 'User has not logged in'
            );

            return $this->response($this->openId->data, $error);
        }

        $response = $this->response($this->openId->data);

        $response->credentials = array('identity' => $this->openId->identity);
        $response->name = sprintf('steam-%d', substr($this->openId->identity, strrpos($this->openId->identity, '/') + 1));
        $response->uid = $this->openId->identity;

        return $response;
    }

    protected function loadOpenId()
    {
        $url = $this->callbackUrl();
        $this->openId = new \LightOpenID($url);
        $this->openId->returnUrl = $url;
    }
}