# Socialite
社会化登陆扩展包，当前包含微信、微博、QQ(`因官方审核十分缓慢，故暂未完成完整测试`)。后续将增加百度、Github等。

# 特点
* 可扩展性高
* 可记录每个请求数据（用于将来甩锅大战）

# 安装
* 前置要求
    > PHP >= 5.5
* 安装方式
    > composer require "namet/socialite"

# 使用方法
1. 配置信息
```php
<?php

$config = [
    // 必填项
    'appid' => YOUR_APP_ID,
    'secret' => YOUR_SECRET,
    'redirect_uri' => YOUR_CALLBACK_URL,
    // 选填项
    'scope' => 'SCOPE', // 详情请参照各个开放平台的文档
    'state' => STATE,   // 传过去的参数，服务端也会原样返回，用于防止CSRF
];
```
2. 获取实例，并跳转至认证服务器地址，以`微信`登陆为例：
```php
<?php
use Namet\Socialite\OAuth;

// 当前支持的驱动有：wechat(微信)、qq(QQ账号)、weibo(新浪微博)
/* Step1: 获取OAuth实例 */

// 获取实例 方法1:
$oauth = new OAuth('wechat', $config);
// 或者使用 方法2:
$oauth = new OAuth();
$oauth->driver('wechat')->config($config);

/* Step2: 跳转到认证服务器 */
$oauth->authorize();
```
3. 在回调地址中获取Code，然后换取Access_token，再获取用户信息
```php
<?php
use Namet\Socialite\OAuth;
use Namet\Socialite\SocialiteException;

try {
    // 获取oauth实例
    $oauth = new OAuth('wechat', $config);

    // 是否开启所有请求结果日志记录，⚠️默认是false
    $oauth->log(true);
    // 若要自定义日志记录方法，请实现 \Namet\Socialite\LogInterface接口
    // 调用handler::handle($data)的参数为一个数组，其结构为：
    // array (
    //    'driver' => 驱动名称,
    //    'request_time' => 请求发送时间,
    //    'response_time' => 接收到相应时间,
    //    'method' => 调用方式 get/post,
    //    'params' => 请求参数,
    //    'response' => 原始返回数据(字符串),
    // )
    $oauth->setLogHandler(new otherHandler());

    // 直接获取用户信息
    $user = $oauth->getUserInfo();

    // 获取用户access_token
    $access_token = $oauth->getToken();

    // 获取认真服务器返回的code
    $code = $oauth->getCode();

    // 刷新access_token
    $oauth->refreshToken();
    $new_token = $oauth->getToken();

    // 验证access_token是否有效。若已无效，当传入参数为false时，返回结果为false；反之则会抛出异常
    $bool = $oauth->checkToken(false); //

    // 获取请求服务端返回的原始数据信息（数组形式）
    // $key可以：getToken(换取access_token时返回数据)、
    //          getUserInfo(获取用户信息时返回数据)、
    //          refreshToken(刷新token时返回数据)、
    //          checkToken(验证oken时返回数据，`⚠️目前仅有微信提供接口，其他不可用`)、
    $response = $oauth->getResponse($key); //

} catch(SocialiteException $e) {
    // 所有报错信息都将抛出异常，捕获后可进行处理
    echo $e->getMessage();
}
```
# 扩展驱动
1. 首先需要自定义驱动实现`\Namet\Socialite\DriverInterface`接口
2. 在获取OAuth实例之后进行驱动注册。(获取新OAuth实例时不需要再次注册。😊)
```php
<?php
use Namet\Socialite\OAuth;

$oauth = new OAuth;
// 注册驱动
$oauth->registerDriver('new_driver', \NAMESPACE\TO\NEW_DRIVER::class);
// 使用注册后的驱动
$oauth->driver('new_driver')->config($new_config);
...
```

# LICENSE
MIT
