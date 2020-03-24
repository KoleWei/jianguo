<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Order;
use app\common\model\OrderTake;
use app\common\model\StarUp;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\common\server\OrderServer;
use Exception;
use think\Config;
use think\Db;

use function PHPSTORM_META\map;

/**
 * 摄影师
 */
class Photoer extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $cust = (new Cust)
            ->where('id', $this->getCustId())
            ->find();

        if ($cust['is_photoer'] == 'n') {
            $this->error('当前用户非摄影师');
        }
    }

    /**
     * 上传作品
     */
    public function createzp($covorimage, $data, $style, $type)
    {
        $model = new Zp();

        $count = (new Zp())
            ->where('cust', $this->getCustId())
            ->where('style', $style)
            ->count();

        if ($count > 30){
            return $this->error('上传作品超过数量');
        }

        Db::startTrans();
        try {
            $r = $model->save([
                "cust" => $this->getCustId(),
                "style" => $style,
                "data" => $data,
                "covorimage" => $covorimage,
                "type" => $type,
                "check" => 't',
            ]);
            if (empty($r)) {
                throw new Exception('作品上传失败');
            }

            $sobj = Styles::get($style);
            if (empty($sobj)) {
                throw new Exception('作品类目不存在');
            }

            $scount = (new StylesCust())->where('cust', $this->getCustId())
                ->where('style', $style)
                ->count();

            if ($scount <= 0) {
                $r = (new StylesCust())->save([
                    "cust" => $this->getCustId(),
                    "style" => $style,
                ]);

                if (empty($r)) {
                    throw new Exception('作品上传失败');
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

        $this->success('上传成功, 等待审核');
    }

    /**
     * 显示分类
     *
     */
    public function styles()
    {

        $hasstyle = (new Zp)->where('cust', $this->getCustId())->distinct(true)->column('style');

        $styles = (new StylesCust())
            ->with(['styles'])
            ->where('styles.deletetime is null')
            ->where('styles_cust.cust', $this->getCustId())
            ->where('styles_cust.style', 'in', $hasstyle)
            ->select();

        $this->success('获取成功', $styles);
    }

    /**
     * 设置样式
     *
     * @return void
     */
    public function setstyle($style) {
        $param = $this->request->param();
        $scust = (new StylesCust())
            ->where('cust', $this->getCustId())
            ->where('style', $style)
            ->find();

        $data = [];
        if (!empty($param['defimage'])) {
            $data['defimage'] = $param['defimage'];
        }

        $scust->save($data);

        $this->success('更换成功');
    }

    /**
     * 获得类型
     * @param [type] $id
     * @return void
     */
    public function style($id) {
        $style = (new StylesCust())
            ->with(['styles'])
            ->where('styles.deletetime is null')
            ->where('styles_cust.cust', $this->getCustId())
            ->where('styles_cust.style', $id)
            ->find();

            $this->success('获取成功', $style);
    }

    /**
     * 读取作品
     */
    public function product() {
        $param = $this->request->param();

        $zpmodle = (new Zp)->where('cust', $this->getCustId());

        if (!empty($param['style'])) {
            $zpmodle->where('style', $param['style']);
        }

        $zpmodle->order("usort ASC, createtime DESC");

        $list = $zpmodle->select();

        $this->success('获取成功', $list);
    }

    /**
     * 删除作品
     */
    public function deletezp($id) {
        $zp = (new Zp())
            ->where('cust', $this->getCustId())
            ->where('id', $id)
            ->find();

        if (empty($zp)) {
            return $this->error('作品不存在');
        }

        $zp->delete();
        $this->success('删除成功');
    }

    /**
     * 提升星级
     */
    public function starup($styleid) {
        $stylecust = (new StylesCust())
            ->where('cust', $this->getCustId())
            ->where('style', $styleid)
            ->find();

        if (empty($stylecust)) {
            return $this->error('当前类目没有作品，无法提升星级!');
        }

        if ($stylecust['star'] >= 5) {
            return $this->error('已经到达最高星级!');  
        }

        Db::startTrans();
        try {
            $starlogcount = (new StarUp())
                ->where('cust', $this->getCustId())
                ->where('style', $styleid)
                ->where('step', 1)
                ->count();
            if ($starlogcount > 0) {
                return $this->error('该类目星级在审核中!');
            }

            (new StarUp())->save([
                "cust" => $this->getCustId(),
                "style" => $styleid,
                "stylecust" => $stylecust['id'],
                "needstar" => $stylecust['star'] + 1,
                "step" => 1,
                "createtime" => time(),
            ]);
        } catch(Exception $e) {
            Db::rollback();
            throw $e;
        }

        Db::commit();
        
        return $this->success('提升星级申请成功，等待审核');
    }

     /**
     * 订单列表
     */
    public function orderlist() {
        $param = $this->request->param();

        $zpmodel = (new Order());

       
        // 暂无状态
        if (empty($param['status'])) {
            return $this->error('请选择订单状态');
        }

        $zpmodel->where('status',$param['status']);
        // 待接订单
        if ($param['status'] != 1){
            $zpmodel
            ->where('photoerid', $this->getCustId());

            // 分页
            if (!empty($param['page'])) {
                $zpmodel->limit($this->limitfmt($param['page']));
            }
        }

        

        $list = $zpmodel->order('createtime desc')->select();

        foreach ($list as $row) {
            $row->visible(['id','orderno','uname', 'uphone', 'allow', 'udemand', 'shoottime', 'endtime', 'type', 'ordermoney', 'cbmoney', 'sysmoney', 'status', 'cbimage', 'dgimage', 'wkimage', 'sxmsg']);
        }

        $list = collection($list)->toArray();

        if ($param['status'] == 1){
            $resultlist = [];
            $custStyles = [];
            $cslist = (new StylesCust())
                ->where('cust', $this->getCustId())
                ->select();
            foreach ($cslist as $cs) {
                $custStyles[$cs['style']] = $cs['star'];
            }
            $hasStyle = (new StylesCust())->where('cust', $this->getCustId())->where('star', '>', 0)->count() > 0;
            foreach ($list as $row) {
                if (OrderServer::hasAllow($row['allow'], $custStyles, $hasStyle)){
                    $task = (new OrderTake())
                        ->where('order', $row['id'])
                        ->where('photoer', $this->getCustId())
                        ->find();
                    if (!empty($task)) {
                        if ($task['status'] == 'y'){
                            $row['status_text'] = '同意';
                        } else {
                            continue;
                        }
                    }
                    $resultlist[] = $row;
                }
            }

            $list = $resultlist;
        }

        $this->success('读取订单', $list);
    }

    public function orderdetail($id) {
        $order = (new Order())
            ->where('id', $id)
            ->field('id,orderno,uname, astar, uphone,shoottime,udemand,endtime, status, sysmoney,type,agent,photoerid,allow,createtime')
            ->find();
        if (empty($order)) {
            return $this->error('订单不存在');
        }

        // 获得经纪人电话
        $agent = (new Cust())->where('id', $order['agent'])->find();
        if (!empty($agent)) {
            $order['agentphone'] = $agent['phone'];
        }

        if($order['status'] == 1) {
            $custStyles = [];
            $cslist = (new StylesCust())
                ->where('cust', $this->getCustId())
                ->select();
            foreach ($cslist as $cs) {
                $custStyles[$cs['style']] = $cs['star'];
            }

            $hasStyle = (new StylesCust())->where('cust', $this->getCustId())->where('star', '>', 0)->count() > 0;
            if (!OrderServer::hasAllow($order['allow'], $custStyles, $hasStyle)){
                return $this->error('当前订单无权限接单');
            }
        } else{
            if ($order['photoerid'] != $this->getCustId()) {
                return $this->error('当前订单非您拥有');
            }
        }

        $orderTake = (new OrderTake())
            ->where('order', $id)
            ->where('photoer', $this->getCustId())
            ->find();

        $order['task'] = $orderTake;

        return $this->success('订单显示', $order);
    }

    /**
     * 接单
     */
    public function taskorder($id, $status) {
        $ordermodel = (new Order())
            ->where('id', $id)
            ->where('status', '1');


        $order = $ordermodel->find();

        if (empty($order)) {
            return $this->error('无此订单');
        }

        Db::startTrans();
        try {

            OrderServer::taskorder($id, $this->getCustId(),$status);

        }catch(Exception $e) {
            Db::rollback();
            throw $e;
        }
        Db::commit();
        $this->success($status == 'y' ?  '订单接单成功' : '订单拒绝成功');
    }

    /**
     * 作品排序
     */
    public function prosort($id, $sort) {
        $thiszp = (new Zp)
            ->where('cust', $this->getCustId())
            ->where('id', $id)
            ->find();

        if (empty($thiszp)){
            return $this->error('无此作品');
        }

        $otherzp = (new Zp)
            ->where('cust', $this->getCustId())
            ->where('id','<>',$id)
            ->where('style', $thiszp['style'])
            ->select();

        if ($sort <= 1) $sort = 1;
        if ($sort >= count($otherzp) + 1) $sort = count($otherzp) + 1;
        
        $c = 0;
        for($i = 1; $i <= count($otherzp) + 1; $i++) {
            if ($i == $sort) {
                $thiszp->save([
                    'usort' => $i
                ]);
            }else {
                $otherzp[$c]->save([
                    'usort' => $i
                ]);
                $c++;
            }
        }

        $this->success('排序成功');
    }

    public function pfstar($id, $astar = 5) {

        $order = (new Order())
            ->where('id', $id)
            ->where('photoerid', $this->getCustId())
            ->find();

        if (empty($order)){
            return $this->error('订单不存在');
        }

        if (!empty($order['astar'])) {
            return $this->error('已经评星');
        }

        
        $order->save([
            'astar' => $astar
        ]);

        OrderServer::changeStar($order['agent'], 'agent');

        $this->success('评分成功');
    }
}
