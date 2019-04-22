<?php
require_once('redis.php');

session_start();

$wechat = new wechatCallBack();
if (isset($_GET['echostr'])) {
    $wechat->checkSignature();
} else {
    return $wechat->responseMsg();
}

class wechatCallBack
{
    public $appid  = 'wx352e1bbc2a748137';
    public $sercet = 'dd22744f927fe4cfa8f2ba2726aefaf4';
    public $token  = 'zhaixing';
    
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

    /**
     * 响应
     *
     * @return void
     *
     * @author zhengkexin
     *
     * @created 2019-04-19 10:44:54
     */
    public function responseMsg()
    {
        $postStr = file_get_contents("php://input");

        $this->logger("\r\n" . $postStr);

        if (!empty($postStr)) {
            $this->logger("R \r\n" . $postStr);

            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $reType = trim($postObj->MsgType);

            if ($reType) {
                switch ($reType) {
                    case 'event':
                        if ($postObj->EventKey == 'contact') {
                            $redis = new redisClass();
                            $redis->set($postObj->FromUserName);
                            $this->sendCustomerMsg($postObj);
                        }
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }
        }
    }

    /**
     * 添加客服
     */
    public function addCustomerService()
    {
        $accessToken = $this->getAccessToken();

        $url = "https://api.weixin.qq.com/customservice/kfaccount/add?access_token=$accessToken";
        
        $postData = array(
            'kf_account' => '-qXnbbuUM5Jw@g2522658f',
            'nickname'   => '小客服一号',
            'password'   => 'wechat1113776415'
        );

        $res = $this->postHttp($url, json_encode($postData));

        $resArr = json_decode($res, true);
        $this->logger("\r\n 添加结果：\r\n" . $res);
        if (isset($resArr['errcode']) && $resArr['errcode'] == 0) {
            $this->logger('添加成功');

            return true;
        }

        return false;
    }

    /**
     * 发送客服消息
     *
     * @param [type] $obj
     * @return void
     *
     * @author zhengkexin
     *
     * @created 2019-04-19 15:30:35
     */
    public function sendCustomerMsg($obj)
    {
        $accessToken = $this->getAccessToken();

        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$accessToken";

        // 不指定某个客服回复
        $postData = array(
            'touser'  => "$obj->FromUserName",
            'msgtype' => 'text',
            'text'    => array (
                'content' => 'Hello a',
            )
        );

        // 指定某个客服回复
        // $postData = array(
        //     'touser'  => "$obj->FromUserName",
        //     'msgtype' => 'text',
        //     'customservice' => array(
        //         "kf_account" => "test1@kftest"
        //     ),
        //     'text'    => array(
        //         'content' => 'Hello a',
        //     )
        // );

        $res = $this->postHttp($url, json_encode($postData));

        $resArr = json_decode($res, true);

        if (isset($resArr['errcode']) && $resArr['errcode'] == 0) {
            $this->logger("\r\n" . '发送成功');

            return true;
        }

        $this->logger("\r\n" . $res);
    }

    /**
     * 将消息转发到客服
     *
     * @return void
     *
     * @author zhengkexin
     *
     * @created 2019-04-19 17:02:49
     */
    public function forwardCustomerService($obj)
    {
        // 不指定转发
        $tmpXml = '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[transfer_customer_service]]></MsgType>
            </xml>';

        // 指定转发到某客服
        $tmpXml1 = '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[transfer_customer_service]]></MsgType>
                <TransInfo>
                    <KfAccount><![CDATA[test1@test]]></KfAccount>
                </TransInfo>
            </xml>';

        $resultXml = sprintf($tmpXml, $obj->FromUserName, $obj->ToUserName, time());

        echo $resultXml;
    }

    /**
     * 获取access_token
     */
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

    /**
     * GET 请求
     */
    public function getHttp($url = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }

    /**
     * POST 请求
     */
    public function postHttp($url, $data = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }

    /**
     * 日志
     */
    public function logger($logContent)
    {
        $max_size = 1000000;
        $log_filename = "log.xml";

        if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
            unlink($log_filename);
        }

        file_put_contents($log_filename, date('Y-m-d H:i:s') . "： " . $logContent . "\r\n", FILE_APPEND);
    }
}