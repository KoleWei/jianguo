<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Styles;
use app\common\server\CustServer;
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

        $cust = CustServer::login($user);
        $this->success('登录成功', $cust);
    }

    public function getuser() {
        $user = CustServer::getUser($this->getAccess());
        if (empty($user)) {
            $this->error('获取用户信息为空');
        }
        $this->success('成功获取用户信息', $user);
    }
}
