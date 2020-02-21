<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use think\Config;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $site = Config::get("site");
        $this->success($site);
    }
}
