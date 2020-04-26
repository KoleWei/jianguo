<?php

namespace app\wxapi\library;

use EasyWeChat\Foundation\Application;
use EasyWeChat\Kernel\Http\StreamResponse;

/**
 * 自定义API模块的错误显示
 */
class Wx
{
    public static $miniapp = [
        "app_id" => 'wx41ddc98205c9ba74',
        "secret" => 'a4b2c599df5537df3cd2d5fd093dabf0',
    ];

    public static function login($code, $iv, $encryptData)
    {

        $options = [
            // ...
            'mini_program' => [
                'app_id'   => self::$miniapp['app_id'],
                'secret'   => self::$miniapp['secret'],
                'token'    => 'component-token',
                'aes_key'  => 'component-aes-key'
            ],
            // ...
        ];

        $app = new Application($options);
        $miniProgram = $app->mini_program;
        $sessionkey = $miniProgram->sns->getSessionKey($code);
        $openid = $sessionkey['openid'];
        return $miniProgram->encryptor->decryptData($sessionkey['session_key'], $iv, $encryptData);
    }

    public static function qrcode($key,$path, $width, $getNew=false) {

        $options = [
            // ...
            'mini_program' => [
                'app_id'   => self::$miniapp['app_id'],
                'secret'   => self::$miniapp['secret'],
                'token'    => 'component-token',
                'aes_key'  => 'component-aes-key'
            ],
            // ...
        ];

        $app = new Application($options);
        $miniProgram = $app->mini_program;
        $response = $miniProgram->qrcode->getAppCode($path, $width);

        $savePath = '/uploads/qr/wxuser';
        $downloadpath = ROOT_PATH . 'public' . $savePath;
        if (!is_dir($downloadpath)){  
            mkdir(iconv("UTF-8", "GBK", $downloadpath),0777,true); 
        }

        if ($getNew){
            $dbpath = $savePath . '/'. $key . '_' . time() . '.jpg';
            $pathimg =  $downloadpath . '/'. $key . '_' . time() .'.jpg';
        } else {
            $dbpath = $savePath . '/'. $key .'.jpg';
            $pathimg =  $downloadpath . '/'. $key .'.jpg';
        }
        
        $file = fopen($pathimg, "w");//打开文件准备写入
        fwrite($file,$response);//写入
        fclose($file);//关闭

        return $dbpath;
    }
}
