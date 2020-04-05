<?php

namespace app\common\server;

use app\common\model\Cust;
use app\common\model\StarUp;
use app\common\model\StylesCust;
use app\common\model\Order;
use app\common\model\OrderLog;
use app\common\model\OrderTake;
use Exception;

class OrderServer
{
   
    // 创建订单
    public static function create($param) {

        $data = [];

        $data['orderno'] = date('YmdHis') . rand(0000, 9999);
        $data['uname'] = $param['uname'];
        $data['uphone'] = $param['uphone'];
        $data['udemand'] = $param['udemand'];
        $data['agent'] = $param['agent'];
        $data['type'] = $param['type'];

        
        if (empty($data['type'])) {
            throw new Exception('请选择拍摄类型');
        }

        if ($param['type'] == 'ps'){
            if (empty($param['ordermoney']) || empty($param['sysmoney'])){
                throw new Exception('请填写订单总价、请填写摄影师价格');
            } 
            $data['ordermoney'] = floatval($param['ordermoney']);
            $data['sysmoney'] = floatval($param['sysmoney']);
        }


        if (empty($param['photpertype'])) {
            throw new Exception('请选择摄影师类型');
        }

        if ($param['photpertype'] == '1') {
            $data['status'] = '2'; // 洽谈
            $data['photoerid'] = $param['photper'];
            $data['allow'] = '*';
        } else {
            $data['allow'] = $param['photper'];
        }

        

        $data['shoottime'] = strtotime($param['shoottime']);
        // $data['endtime'] = strtotime($param['endtime']);

        // if ($data['shoottime'] > $data['endtime']) {
        //     throw new Exception('拍摄结束时间需要大于拍摄时间');
        // }


        (new Order())->save($data);
    }

    // 取消订单
    public static function cancel($id, $msg) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        $order->save([
            'status' => 5,
            'sxmsg' => $msg,
            'canceltime' => time()
        ]);
    }

    // 接单
    public static function taskorder($id, $photoer, $status) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        // 是否已经接单
        if ((new OrderTake())
            ->where('order', $id)
            ->where('photoer', $photoer)
            ->count() > 0) {
            throw new Exception('已经对订单进行操作');
        }

        $custStyles = [];
        $cslist = (new StylesCust())
            ->where('cust', $photoer)
            ->select();
        foreach ($cslist as $cs) {
            $custStyles[$cs['style']] = $cs['star'];
        }

        $hasStyle = (new StylesCust())->where('cust', $photoer)->where('star', '>', 0)->count() > 0;
        if (!OrderServer::hasAllow($order['allow'], $custStyles, $hasStyle)){
            throw new Exception('当前订单无权限接单');
        }

        (new OrderTake())->save([
            'order' => $id,
            'photoer' => $photoer,
            'status' => $status
        ]);

    }

    // 修改
    public static function changeInfo($id, $param) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        $changeParam = [];
        $changeParamLog = [];
        $ischange = false;

        if (!empty($param['type'])) {
            $changeParam['type'] = $param['type'];
            // if ($order['type'] != $param['type']) {
            //     $ischange = true;
            //     $changeParamLog['type'] = $order['type'];
            // }
        }
        if (!empty($param['uname'])) {
            $changeParam['uname'] = $param['uname'];
            if ($order['uname'] != $param['uname']) {
                
                $ischange = true;
                $changeParamLog['uname'] = $order['uname'];
            }
        }
        if (!empty($param['uphone'])) {
            $changeParam['uphone'] = $param['uphone'];
            if ($order['uphone'] != $param['uphone']) {
                $ischange = true;
                $changeParamLog['uphone'] = $order['uphone'];
            }
        }
        if (!empty($param['udemand'])) {
            $changeParam['udemand'] = $param['udemand'];
            if ($order['udemand'] != $param['udemand']) {
                $ischange = true;
                $changeParamLog['udemand'] = $order['udemand'];
            }
        }

        if (!empty($param['ordermoney'])) {
            $changeParam['ordermoney'] = $param['ordermoney'];
            if ($order['ordermoney'] != $param['ordermoney']) {
                $ischange = true;
                $changeParamLog['ordermoney'] = $order['ordermoney'];
            }
        }
        if (!empty($param['cbmoney'])) {
            $changeParam['cbmoney'] = $param['cbmoney'];
        }
        if (!empty($param['sysmoney'])) {
            $changeParam['sysmoney'] = $param['sysmoney'];
            if ($order['sysmoney'] != $param['sysmoney']) {
                $ischange = true;
                $changeParamLog['sysmoney'] = $order['sysmoney'];
            }
        }
        if (!empty($param['cbimage'])) {
            $changeParam['cbimage'] = $param['cbimage'];
        }
        if (!empty($param['dgimage'])) {
            $changeParam['dgimage'] = $param['dgimage'];
        }
        if (!empty($param['wkimage'])) {
            $changeParam['wkimage'] = $param['wkimage'];
        }

        if (!empty($param['shoottime'])) {
            $changeParam['shoottime'] = strtotime($param['shoottime']);
            if ($order['shoottime'] != $changeParam['shoottime']) {
                $ischange = true;
                $changeParamLog['shoottime'] = $order['shoottime'];
            }
        }
        // if (!empty($param['endtime'])) {
        //     $changeParam['endtime'] = strtotime($param['endtime']);
        //     if ($order['endtime'] != $changeParam['endtime']) {
        //         $ischange = true;
        //         $changeParamLog['endtime'] = $order['endtime'];
        //     }
        // }

        $order->save($changeParam);

        // 修改
        if ($ischange && $order['status'] == 3) {
            $changeParamLog['orderid'] = $order['id'];
            (new OrderLog())->save($changeParamLog);
            // (new OrderLog())->save([
            //     "orderid" => $order['id'],
            //     "uname" => $changeParamLog['uname'],
            //     "uphone" => $changeParamLog['uphone'],
            //     "udemand" => $changeParamLog['udemand'],
            //     "shoottime" => $changeParamLog['shoottime'],
            //     "endtime" => $changeParamLog['endtime'],
            //     "ordermoney" => $changeParamLog['ordermoney'],
            //     "sysmoney" => $changeParamLog['sysmoney'],
            // ]);
        }
    }

    /**
     * 订单洽谈
     */
    public static function shooting($id, $dgimage) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        // 试拍
        if ($order['type'] == 'sp') {
            if (empty($order['ordermoney']) || empty($order['sysmoney'])){
                throw new Exception('无法提交没设置订单总价和摄影师价格的订单');
            }
        }

        NotifyServer::notify($order['photoerid'], 'sys', [
            "type" => 2,
            "order" => $order['id'],
            "role" => "photoer",
            "desc" => "订单进入拍摄中",
            "content" =>  '订单' . $order['orderno'] . "进入拍摄中"
        ]);
    
        $order->save([
            'status' => 3,
            'type' => 'ps',
            'dgimage' => $dgimage,
        ]);

    }

    /**
     * 订单完成
     */
    public static function endorder($id, $wkimage, $pstar, $cbimage, $cbmoney) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        // 试拍
        // if (!is_numeric($order['cbmoney'])){
        //     throw new Exception('请设置拍摄成本');
        // }

        NotifyServer::notify($order['photoerid'], 'sys', [
            "type" => 2,
            "order" => $order['id'],
            "role" => "photoer",
            "desc" => "订单完成",
            "content" =>  '订单' . $order['orderno'] . "完成"
        ]);

        OrderServer::changeStar($order['photoerid'], 'photoer');
    
        $order->save([
            'status' => 4,
            'pstar' => $pstar,
            'endedtime' => time(),
            'cbimage' => $cbimage,
            'cbmoney' => $cbmoney,
            'wkimage' => $wkimage,
        ]);

    }

    public static function changeStar($id, $role) {

        $cust = (new Cust())->where('id', $id)->find();

        if (empty($cust)) {
            return ;
        }

        if ($role == 'agent') {
            $astar = (new Order())
                ->where('status', 4)
                ->where('agent', $id)
                ->where('astar is not null')
                ->avg('astar');

            $cust->save([
                'astar' => floor($astar)
            ]);
        } else {
            $pstar = (new Order())
            ->where('status', 4)
            ->where('photoerid', $id)
            ->where('pstar is not null')
            ->avg('pstar');

            $cust->save([
                'pstar' => floor($pstar)
            ]);
        }
    }

    /**
     * 订单洽谈
     */
    public static function negotiation($id, $photoer) {
        $ordermodel = (new Order())
            ->where('id', $id);

        $order = $ordermodel->find();

        if (empty($order)) {
            throw new Exception('无此订单');
        }

        $cust = (new Cust())
            ->where('id', $photoer)
            ->where('is_photoer', 'y')
            ->find();
        
        if (empty($cust)) {
            throw new Exception('摄影师不存在');
        }

        NotifyServer::notify($photoer, 'sys', [
            "type" => 2,
            "order" => $order['id'],
            "role" => "photoer",
            "desc" => "订单进入洽谈中",
            "content" =>  '订单' . $order['orderno'] . "进入洽谈中"
        ]);

        $order->save([
            'status' => 2,
            'photoerid' => $photoer
        ]);
    }

    /**
     * 是否拥有权限
     * *
     * 1:*;2:1,2
     */
    public static function hasAllow($allow, $starMap, $hasStyle) {
        // 全选存在星级
        if ($allow == '*') {
            return $hasStyle;
        }

        $styleallows = explode(';', $allow);
        foreach($styleallows as $styleallow) {
            $styleEnt = explode(':', $styleallow);
            // 切割失败
            if (count($styleEnt) != 2) {
                continue;
            }

            // 不存在该类型或者没星级
            if(empty($starMap[$styleEnt[0]]) || $starMap[$styleEnt[0]] <= 0){
                continue;
            }

            // 是否全选类型
            if ($styleEnt[1] == '*'){
                return true;
            }

            $styleids = explode(',', $styleEnt[1]);

            if(in_array($starMap[$styleEnt[0]], $styleids)) {
                return true;
            }

        }

        return false;
    }

}
