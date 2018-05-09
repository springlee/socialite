<?php

namespace Namet\Socialite\Drivers;

use Namet\Socialite\DriverInterface;
use Namet\Socialite\DriverBase;
use Namet\Socialite\SocialiteException;

class Baidu extends DriverBase implements DriverInterface
{
    // client_id
    protected $_appid = null;
    // client_secret
    protected $_secret = null;
    // 跳转链接
    protected $_redirect_uri = null;
    // 接口返回的原始数据存储
    protected $_response = [];
    // 用户授权后，得到的code参数
    protected $_code = null;
    // 用户的token
    protected $_access_token = null;
    // 用户信息
    protected $_user_info = [];
    // oauth_api地址
    protected $_base_url = 'https://openapi.baidu.com/';
    // 此Driver的名称
    protected $_name = 'baidu';

    /**
     * 跳转到用户授权界面
     */
    public function authorize()
    {
        $params = [
            'client_id' => $this->_appid,
            'redirect_uri' => $this->_redirect_uri,
            'response_type' => 'code',
            'scope' => empty($this->_scope) ? 'basic' : $this->_scope,
        ];
        !empty($this->_state) && $params['state'] = $this->_state;
        !empty($this->_scope) && $params['scope'] = $this->_scope;

        $this->redirect($this->_base_url . 'oauth/2.0/authorize', $params);
    }

    /**
     * @desc 获取连接中的code参数
     *
     * @return string
     */
    public function getCode()
    {
        $this->_code = $this->_code ?: $_GET['code'];

        return $this->_code;
    }

    /**
     * @desc 获取access token
     *
     * @return string Access Token
     * @throws \Namet\Socialite\SocialiteException
     */
    public function getToken()
    {
        if (!$this->_access_token) {
            $params = [
                'client_id' => $this->_appid,
                'client_secret' => $this->_secret,
                'code' => $this->getCode(),
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->_redirect_uri,
            ];
            !empty($this->_state) && $params['state'] = $this->_state;

            $res = $this->get($this->_base_url . 'oauth/2.0/token', ['query' => $params]);

            // 检查是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response['token'] = $res;
            // 将得到的数据赋值到属性
            $this->config($res);
        }

        return $this->_access_token;
    }

    /**
     * @desc 判断接口返回的数据是否有错误
     *
     * @param array $res 请求的结果
     *
     * @throws \Namet\Socialite\SocialiteException
     */
    private function _checkError($res)
    {
        if (!empty($res['error']) || !empty($res['error_code'])) {
            // 百度认证服务器返回数据和获取用户信息接口返回数据格式不一致。
            $msg = isset($res['error'])
                ? ($res['error'] . ' : ' . $res['error_description'])
                : ($res['error_code'] . ' : ' . $res['error_msg']);

            throw new SocialiteException($msg);
        }
    }

    /**
     * @desc 根据access_token获取用户基本信息
     *
     * @param string $lang
     *
     * @throws \Namet\Socialite\SocialiteException
     *
     * @return array
     */
    public function getUserInfo($lang = '')
    {
        if (!$this->_user_info) {
            $res = $this->get($this->_base_url . 'rest/2.0/passport/users/getLoggedInUser', [
                'query' => [
                    'access_token' => $this->getToken()
                ],
                'headers' => [
                    'Accept' => '*/*',
                    'Accept-Encoding' => 'gzip,deflate',
                    'Accept-Charset' => 'utf-8',
                ],
            ]);
            // 检查返回值是否有错误
            $this->_checkError($res);
            // 记录返回的数据
            $this->_response['user'] = $res;

            return $this->_formatUserInfo();
        }

        return $this->_user_info;
    }

    /**
     * 格式化用户数据
     *
     * @return array
     */
    private function _formatUserInfo()
    {
        $this->_user_info = [
            'uid' => $this->_response['user']['uid'],
            'uname' => $this->_response['user']['uname'],
            'avatar' => 'http://tb.himg.baidu.com/sys/portrait/item/' . $this->_response['user']['portrait'],
        ];

        return $this->_user_info;
    }

    /**
     * @desc 刷新access_token
     *
     * @return $this
     * @throws \Namet\Socialite\SocialiteException
     */
    public function refreshToken()
    {
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->_refresh_token,
            'client_id' => $this->_appid,
            'client_secret' => $this->_secret,
        ];
        // 获取返回值数组
        $res = $this->get($this->_base_url . 'oauth/2.0/token', ['query' => $params]);
        // 检查返回值中是否有错误
        $this->_checkError($res);
        // 记录返回的数据
        $this->_response['refresh'] = $res;
        // 更新配置
        $this->config($res);

        return $this;
    }
}
