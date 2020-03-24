<?php

namespace app\common\server;

use app\common\model\Notify;

class NotifyServer
{
    // 通知类型:1=作品,2=订单,3=通知,4=星级
    public static function notify($cust, $action='sys', $param = []) {

        if (empty($param['role']) || empty($param['type'])){
            return ;
        }

        $param['cust'] = $cust;
        $param['action'] = $action;

        (new Notify())->save($param);
    }

}
