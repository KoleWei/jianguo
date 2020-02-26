<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Styles;
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
     */
    public function index()
    {
        // 获取首页
        $styles = (new Styles())->select();

        $this->success('成功', [
            "styles" =>  $styles
        ]);
    }


    /**
     * 读取产品
     * @return void
     */
    public function product() {
        $params = $this->request->param();
        
        $this->success('读取作品', [
            "type" => 'tx',
        ]);
    }

}
