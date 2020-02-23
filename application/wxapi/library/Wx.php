<?php

namespace app\wxapi\library;

use EasyWeChat\Foundation\Application;

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
}
