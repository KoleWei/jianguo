<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Order;
use app\common\model\OrderLog;
use app\common\model\OrderTake;
use app\common\model\StarUp;
use app\common\model\StarUpLog;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\common\server\OrderServer;
use app\common\server\StarUpServer;
use app\common\server\ZpServer;
use Exception;
use think\Config;
use think\Db;


/**
 * 老师
 */
class Agent extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
        $cust = (new Cust)
            ->where('id', $this->getCustId())
            ->find();

        if ($cust['is_agent'] == 'n') {
            $this->error('当前用户非业务员');
        }
    }

    /**
     * 创建订单
     */
    public function createOrder() {

        $param = $this->request->param();
        $param['agent'] = $this->getCustId();

        Db::startTrans();
        try {

            OrderServer::create($param);

        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('订单创建成功');
    }

    /**
     * 订单开始拍摄
     */
    public function shooting($id, $dgimage) {
        $ordermodel = (new Order())
            ->where('id', $id)
            ->where('status', 2)
            ->where('agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        Db::startTrans();
        try {

            OrderServer::changeInfo($id, $this->request->param());
            OrderServer::shooting($id, $dgimage);

        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('订单开始拍摄');
    }

     /**
     * 订单完成拍摄
     */
    public function endorder($id, $wkimage, $cbimage, $cbmoney, $pstar = 1) {
        $ordermodel = (new Order())
            ->where('id', $id)
            ->where('status', 3)
            ->where('agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        Db::startTrans();
        try {

            OrderServer::changeInfo($id, $this->request->param());
            OrderServer::endorder($id, $wkimage, $pstar, $cbimage, $cbmoney);

        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('订单拍摄结束');
    }

    /**
     * 取消订单
     */
    public function cancelorder($id, $msg) {
        $ordermodel = (new Order())
            ->where('id', $id)
            ->where('agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        Db::startTrans();
        try {

            OrderServer::cancel($id, $msg);

        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('订单取消成功');
    }

    /**
     * 订单详情
     */
    public function orderdetail($id) {
        $ordermodel = (new Order())
            ->with(['photoer'])
            ->where('order.id', $id)
            ->where('order.agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        $this->success('读取订单', $order);
    }

    /**
     * 订单列表
     */
    public function orderlist() {
        $param = $this->request->param();

        $zpmodel = (new Order())
            ->with(['photoer'])
            ->where('order.agent', $this->getCustId());

        // 分页
        if (!empty($param['page'])) {
            $zpmodel->limit($this->limitfmt($param['page']));
        }
        // 暂无状态
        if (!empty($param['status'])) {
            $zpmodel->where('status',$param['status']);
        }

        $list = $zpmodel->order('createtime desc')->select();

        foreach ($list as $row) {
            $row->visible(['id','orderno','uname', 'uphone', 'udemand', 'shoottime', 'type', 'ordermoney', 'cbmoney', 'sysmoney', 'status', 'cbimage', 'dgimage', 'wkimage', 'sxmsg']);
            $row->visible(['photoer']);
            $row->getRelation('photoer')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }

        $list = collection($list)->toArray();
        $this->success('读取订单', $list);
    }

    /**
     * 订单同意列表
     */
    public function ordertasks($id) {
        $ordermodel = (new Order())
        ->where('id', $id)
        ->where('agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        $list = (new OrderTake())
            ->with('photoer')
            ->where('status', 'y')
            ->where('order_take.order', $id)
            ->select();

        foreach ($list as $row) {
            $row->visible(['id', 'status', 'order', 'photoer']);
            $row->visible(['photoer']);
            $row->getRelation('photoer')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }

        $list = collection($list)->toArray();
        $this->success('读取接单', $list);
    }

    public function orderlog($id) {
        $order = (new OrderLog())->where('orderid', $id)->find();
        $this->success('历史订单', $order);
    }

    // 进入洽谈
    public function negotiation($id, $photoer) {
        $ordermodel = (new Order())
            ->where('id', $id)
            ->where('status', 1)
            ->where('agent', $this->getCustId());


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        Db::startTrans();
        try {

            OrderServer::negotiation($id, $photoer);

        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('订单洽谈中');
    }
}
