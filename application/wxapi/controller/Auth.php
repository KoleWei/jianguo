<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Styles;
use app\wxapi\library\Utils;
use app\wxapi\library\Wx;
use think\Config;

/**
 * 首页接口
 */
class Auth extends Api
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function login($code, $iv, $encryptedData)
    {
        $user = Wx::login($code, $iv, $encryptedData);

        $custmodel = (new Cust());
        $cust = $custmodel->where('openid', $user['openId'])->find();

        $custobj = [
            'openid' => $user['openId'],
            'unionid' => $user['unionId'],
            'nickname' => Utils::removeEmoji($user['nickName']),
            'avatar_url' => $user['avatarUrl'],
        ];

        if (empty($cust)) {
            $custmodel->save($custobj);
            $cust = $custmodel->where('openid', $user['openId'])->find();
        } else {
            $cust->save($custobj);
        }
        session('user', $cust);
        $cust['session_token'] = session_id();
        $this->success('登录成功', $cust);
    }
}
