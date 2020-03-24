<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Order;
use app\common\model\OrderTake;
use app\common\server\OrderServer;

/**
 * 首页接口
 */
class Notify extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function index(){
        
        $this->o1();

        $this->o2();
        
        echo 'ok';
    }

    private function o1() {
        $orders = (new Order())
            ->where('status', 4)
            ->where('endedtime', '<=', strtotime('-5day'))
            ->where('astar is null')
            ->select();

        if (empty($orders) || count($orders) <= 0) {
            return '订单：' . count($orders);
        }


        for($i = 0; $i < count($orders); $i++) {
            $orders[$i]->save([
                'astar' => 5
            ]);
        }

        OrderServer::changeStar($orders[$i]['agent'], 'agent');

        echo '评星订单：' . count($orders);
    }

    private function o2() {
        $orders = (new Order())
            ->where('status', '1')
            ->where('createtime', '<=', strtotime('-1day'))
            ->select();

        if (empty($orders) || count($orders) <= 0) {
            return '订单：' . count($orders);
        }


        for($i = 0; $i < count($orders); $i++) {
            $otc = (new OrderTake())
                ->where('order', $orders[$i]['id'])
                ->where('status', 'y')
                ->count();

            if (empty($otc) || $otc == 0) {
                OrderServer::cancel($orders[$i]['id'], '无人抢单');
            }
        }



        echo '取消订单：' . count($orders);
    }
}