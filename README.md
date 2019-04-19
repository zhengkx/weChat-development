## 微信公众号开发之客服消息

### 一、接入微信公众号平台

* **从后台得到 `appID` 、`appsecret`， 以及自己填写的 `token`  **

* **通过微信服务器的验证**

  > 开发者提交信息后，微信服务器将发送 `GET` 请求到填写的服务器地址 `URL` 上，会携带参数 `signature`、 `timestamp`、 `nonce`、 `echostr`

  根据开发文档给的校验方式， 校验是否由微信服务器发送的请求，确认之后发送 `echostr` 参数，则接入成功，代码如下：

  ```php
  <?php
  
  $wechat = new wechatCallBack();
  
  if (isset($_GET['echostr'])) {
      $wechat->checkSignature();
  }
  
  class wechatCallBack
  {
      public $appid  = 'wx35*****48137';
      public $sercet = 'dd227*******aefaf4';
      public $token  = 'token';
  
      /**
       * 检验签名
       */
      public function checkSignature()
      {
          // 获得参数 signature nonce token timestamp echostr
          $nonce     = $_GET['nonce'];
          $timestamp = $_GET['timestamp'];
          $echostr   = $_GET['echostr'];
          $signature = $_GET['signature'];
  
          $array = array();
  
          $array = array($nonce, $timestamp, $this->token);
  
          sort($array);
  
          $str = sha1(implode($array));
          
          if ($str == $signature) {
              echo  $echostr;
  
              exit;
          }
      }
  }
  ```



### 二、获取 `access_token`

> `access_token` 是公众号的全局唯一接口调用凭据，公众号调用各接口时都需使用 `access_token` 。开发者需要进行妥善保存。`access_token` 的存储至少要保留512个字符空间。`access_token` 的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的 `access_token` 失效。

代码如下：

```php
public function getAccessToken()
{
    if (isset($_SESSION['access_token']) && time() < $_SESSION['expires_time']) {
        return $_SESSION['access_token'];
    } else {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->sercet";

        $res = $this->getHttp($url);

        $resArr = json_decode($res, true);

        if (isset($resArr['access_token'])) {
            $_SESSION['access_token'] = $resArr['access_token'];
            $_SESSION['expires_time'] = time() + $resArr['expires_in'];

            return $resArr['access_token'];
        }
    }
}
```

### 三、对接客服消息

有了上面的 `access_token` ，接下来就可以对接 **客服消息**

> 当用户和公众号产生特定动作的交互时，微信将会把消息数据推送给开发者，开发者可以在一段时间内（目前修改为48小时）调用客服接口，通过 POST 一个 JSON 数据包来发送消息给普通用户。此接口主要用于客服等有人工消息处理环节的功能，方便开发者为用户提供更加优质的服务。

#### 1. 客服帐号管理

**添加客服帐号**

```php

```





> 网上很多教程都是用 `$GLOBALS["HTTP_RAW_POST_DATA"]`  来接收微信服务器发来的消息，但可能有一些人说没有接收到信息。
>
> 在 [www.php.net](https://www.php.net/manual/zh/reserved.variables.httprawpostdata.php) 里关于这个预定义变量，有一个提醒
>
> ```
> Warning This feature was DEPRECATED in PHP 5.6.0, and REMOVED as of PHP 7.0.0.
> ```
>
> 此功能在PHP 5.6.0中已弃用，从PHP 7.0.0开始已删除。
>
> 所以他给出的是使用 `php://input` 来代替