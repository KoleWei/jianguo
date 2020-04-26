<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Order;
use app\common\model\OrderTake;
use app\common\server\OrderServer;
use app\common\server\StyleServer;
use think\Db;

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

        $this->o3();

        $this->o4();
        
        echo 'ok';
    }

    // 自动评星
    private function o1() {
        $orders = (new Order())
            ->where('status', 4)
            ->where('endedtime', '<=', strtotime('-5day'))
            ->where('astar is null')
            ->select();

        if (empty($orders) || count($orders) <= 0) {
            return '评星订单-' . count($orders);
        }


        for($i = 0; $i < count($orders); $i++) {
            $orders[$i]->save([
                'astar' => 5
            ]);
            OrderServer::changeStar($orders[$i]['agent'], 'agent');
        }

       

        echo '评星订单=：' . count($orders);
    }

    // 自动取消订单
    private function o2() {
        $orders = (new Order())
            ->where('status', '1')
            ->where('createtime', '<=', strtotime('-1day'))
            ->select();

        if (empty($orders) || count($orders) <= 0) {
            return '取消订单-：' . count($orders);
        }


        for($i = 0; $i < count($orders); $i++) {
            $otc = (new OrderTake())
                ->where('order', $orders[$i]['id'])
                ->where('status', 'y')
                ->count();

            echo '[' . $orders[$i]['id']. '抢单数:' . $otc . ']';

            if (empty($otc) || $otc == 0) {
                OrderServer::cancel($orders[$i]['id'], '无人抢单');
            }
        }



        echo '取消订单=：' . count($orders);
    }

    // 更新个人状态
    public function o3() {
        $i = StyleServer::updateTotalStyleState();
        echo '状态修改=：' . $i;
    }

    public function o4() {
        $i = StyleServer::updateHotImage();
        echo '热度更改=：' .    $i;
    }
}