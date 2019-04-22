<?php
session_start();
$index  = new IndexClass();

$act = isset($_GET['act']) ? $_GET['act'] : 'list';
switch ($act) {
    case 'list':
        return $index->getList();
        break;

    case 'send':
        $openId = isset($_POST['openId']) ? $_POST['openId'] : '';
        return $index->sendMessage($openId);
        break;
    
    default:
        # code...
        break;
}

class IndexClass
{
    public $appid  = 'wx352e1bbc2a748137';
    public $sercet = 'dd22744f927fe4cfa8f2ba2726aefaf4';
    public $token  = 'zhaixing';

    public function getList()
    {
        $openId = isset($_SESSION['openid']) ? $_SESSION['openid'] : [];

        exit(json_encode($openId));
    }

    public function sendMessage($openId)
    {
        foreach ($openId as $key => $value) {
            $this->sendCustomerMsg($value);
        }
    }

    public function sendCustomerMsg($obj)
    {
        $accessToken = $this->getAccessToken();

        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$accessToken";

        // 不指定某个客服回复
        $postData = array(
            'touser'  => "$obj",
            'msgtype' => 'text',
            'text'    => array (
                'content' => 'Hello a',
            )
        );

        $res = $this->postHttp($url, json_encode($postData));

        $resArr = json_decode($res, true);

        if (isset($resArr['errcode']) && $resArr['errcode'] == 0) {
            $this->logger("\r\n" . '发送成功');

            return true;
        }

        $this->logger("\r\n" . $res);
    }

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
