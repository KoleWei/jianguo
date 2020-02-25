<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Styles;
use think\Config;

/**
 * 首页接口
 */
class Order extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function list($page = 1)
    {
        $data = [];

        if ($page < 3)

        for($i = 0; $i < 10; $i++) {
            $data[] = [
                'id' => ($page * 10 + $i),
                'udemand' => '需求需求需求需求需求需求需求需求需求需求需求需求需求需求需求需求',
                'ordermoney' => 100.00,
                'status_text' => '订单状态',
                'orderno' => 'J' . $i . '-----' . $page,
            ];
        }

        $this->success('成功', $data);
    }

   

}
