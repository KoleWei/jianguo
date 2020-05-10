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
use app\common\server\StyleServer;
use Exception;
use think\Config;
use think\Db;

use function fast\e;
use function PHPSTORM_META\map;

/**
 * 摄影师
 */
class Photoer extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    protected $custuser = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->custuser = (new Cust)
            ->where('id', $this->getCustId())
            ->find();

        if ($this->custuser['is_photoer'] == 'n') {
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
                ->lock(true)
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
            return $this->error($e->getMessage());
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

        $data['defimage'] = $param['defimage'];

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
        // 删除重新更新关系
        StyleServer::updateTotalStyleStateByUid($this->getCustId());
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
                throw new Exception('该类目星级在审核中!');
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
            return $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('提升星级申请成功，等待审核');
    }


     /**
     * 订单列表
     */
    public function orderlist() {
        $param = $this->request->param();

        $zpmodel = (new Order())->with(['styles']);

       
        // 暂无状态
        if (empty($param['status'])) {
            return $this->error('请选择订单状态');
        }

        $zpmodel->where('status',$param['status']);
        // 非待接订单
        if ($param['status'] != 1){
            $zpmodel
            ->where('order.photoerid', $this->getCustId());

            // 分页
            if (!empty($param['page'])) {
                $zpmodel->limit($this->limitfmt($param['page']));
            }
        }

        // 待接
        if ($param['status'] == 1){
            $zpmodel
                ->where('order.createtime', '>=', strtotime('-1day'));
        }
        

        $list = $zpmodel->order('createtime desc')->select();

        foreach ($list as $row) {
            $row->visible(['id','orderno','uname', 'agent','uphone', 'allow', 'udemand', 'shoottime', 'endtime', 'type', 'ordermoney', 'cbmoney', 'sysmoney', 'status', 'cbimage', 'dgimage', 'wkimage', 'sxmsg']);
            $row->visible(['styles']);
            $row->getRelation('styles')->visible(['id','defimage', 'name', 'showimage', 'type']);
        }

        $list = collection($list)->toArray();

        if ($this->custuser['is_tg'] == 'n' && $param['status'] == 1){
            $list = [];
        }

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
                
                $task = (new OrderTake())
                    ->where('order', $row['id'])
                    ->where('photoer', $this->getCustId())
                    ->find();
                if (!empty($task)) {
                    if ($task['status'] == 'y'){
                        $row['status_text'] = '同意';
                        $row['istake'] = 'y';
                    } else {
                        continue;
                    }
                }

                // 是否可以接单
                $row['is_taskable'] = OrderServer::hasAllow($row['allow'], $custStyles, $hasStyle);

                // 经纪人成单数
                $row['cdcount'] = (new Order())->where('type', 'ps')->where('agent', $row['agent'])->where('status', '4')->count();

                // 经纪人成单率
                $jjrcds = (new Order())->where('type', 'ps')->where('agent', $row['agent'])->count();
                if ($jjrcds != 0){
                    $row['cdlount'] = $row['cdcount'] / $jjrcds;
                } else {
                    $row['cdlount'] = 0;
                }

                $agent = (new Cust())->where('id', $row['agent'])->find();
                if (empty($agent) || !$agent['astar']){
                    // 按好评率
                    $row['hpl'] = 0;
                } else {
                    $row['hpl'] = $agent['astar'];
                }

                $resultlist[] = $row;
                
            }

            $list = $resultlist;


            $sorttype = $param['sorttype'];
            $sortval = $param['sortval'];

            // 排序  订单排序：按好评率（升、降）、按成单率（升、降）、按成单数（升、降）、按拍摄费用（升、降）
            usort($list, function($x, $y) use ($sorttype, $sortval) {
                $xval = floatval($x[$sorttype]);
                $yval = floatval($y[$sorttype]);

                $result = 0;

                if ($sortval == 'desc') {
                    $result = $yval - $xval;
                } else if ($sortval == 'asc') {
                    $result = $xval - $yval;
                } else {
                    $result = $xval - $yval;
                }

                // echo $result;
                return $result;
            });
           
        }

        $this->success('读取订单', $list);
    }

    public function orderdetail($id) {
        $order = (new Order())
            ->where('id', $id)
            ->field('id,orderno,uname, astar, uphone,shoottime,style,udemand,endtime, status, sysmoney,type,agent,photoerid,allow,createtime')
            ->find();
        if (empty($order)) {
            return $this->error('订单不存在');
        }

        // 获得经纪人电话
        $agent = (new Cust())->where('id', $order['agent'])->find();
        if (!empty($agent)) {
            $order['agentphone'] = $agent['phone'];
            $order['agentnickname'] = $agent['nickname'];
            $order['agentuname'] = $agent['uname'];
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
            // if (!OrderServer::hasAllow($order['allow'], $custStyles, $hasStyle)){
            //     return $this->error('当前订单无权限接单');
            // }
            $order['is_taskable'] = OrderServer::hasAllow($order['allow'], $custStyles, $hasStyle);
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

        $order['styles'] = (new Styles())->where('id', $order['style'])->find();

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
            return $this->error($e->getMessage());
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
