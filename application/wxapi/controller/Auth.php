<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Notify;
use app\common\model\Order;
use app\common\model\OrderTake;
use app\common\model\StarUp;
use app\common\model\StarUpLog;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\common\server\CustServer;
use app\common\server\OrderServer;
use app\wxapi\library\Utils;
use app\wxapi\library\Wx;
use Overtrue\Pinyin\Pinyin;

use think\Config;

/**
 * 首页接口
 */
class Auth extends Api
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function login($code, $iv, $encryptedData)
    {
        $user = Wx::login($code, $iv, $encryptedData);

        $cust = CustServer::login($user);
        $this->success('登录成功', $cust);
    }

    public function notifyDetail($id) {
        $zpmodel = (new Notify())
            ->where('cust', $this->getCustId())
            ->where('id', $id)
            ->find();

        if (empty($zpmodel)) {
            $this->error('通知不存在');
        }


        $zpmodel->save([
            'is_read' => 'y'
        ]);
         

        $this->success('通知', $zpmodel);
    }

    /**
     * 通知
     */
    public function notifylist() {
        $param = $this->request->param();
        $zpmodel = (new Notify())
            ->where('cust', $this->getCustId());
         // 分页
         if (!empty($param['page'])) {
            $zpmodel->limit($this->limitfmt($param['page']));
        }
        $zpmodel->order('createtime desc');
        $list = $zpmodel->select();

        $this->success('通知', $list);
    }

    // 获取用户
    public function getuser() {
        $user = CustServer::getUser($this->getAccess());
        if (empty($user)) {
            $this->error('获取用户信息为空');
        }
        $this->success('成功获取用户信息', $user);
    }

    // 获取用户
    public function getouser($id) {
        $user = CustServer::getOtherUser($id);
        if (empty($user)) {
            $this->error('获取用户信息为空');
        }
        $this->success('成功获取用户信息', $user);
    }

    // 设置用户
    public function setuser(){
        $param = $this->request->param();
        $cust = (new Cust())
            ->where('id', $this->getCustId())
            ->find();

        $data = [];
        if (!empty($param['is_tg'])) {
            $data['is_tg'] = ($param['is_tg'] == 'y' ? 'y' : 'n');
        }

        if (!empty($param['uname'])) {
            $data['uname'] = $param['uname'];
        }

        if (!empty($param['phone'])) {
            $data['phone'] = $param['phone'];
        }

        if (!empty($param['logoimage'])) {
            $data['logoimage'] = $param['logoimage'];
        }

        if (!empty($param['wximg'])) {
            $data['wximg'] = $param['wximg'];
        }

        $cust->save($data);

        $this->success('设置用户成功');
    }

    // 删除通知
    public function delnotify($id) {
        $n = (new Notify())->where('cust', $this->getCustId())->where('id', $id)->find();
        if (empty($n)) {
            $this->error('通知删除失败');
        }
        $n->delete();
        $this->success('删除成功');
    }

    /**
     * 获得用户
     */
    public function custlist() {
        $param = $this->request->param();

        $custModle = (new Cust());

        if (!empty($param['role'])) {
            switch($param['role']) {
                case 'photoer':
                    $custModle->where('is_photoer', 'y');
                break;
                case 'teacher':
                    $custModle->where('is_teacher', 'y');
                break;
                case 'agent':
                    $custModle->where('is_agent', 'y');
                break;
            }
        }

        if (!empty($param['search'])) {
            $custModle->where("(uname != '' and uname like '%". $param['search'] ."%') or (uname = '' and  nickname like '%". $param['search'] ."%')");
        }
        
        $list = $custModle->select();
        foreach ($list as $row) {
            $row->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }
        $list = collection($list)->toArray();

        $maplist = [];
        $pinyin = (new Pinyin());
        foreach ($list as $row) {
            $pperm = $pinyin->permalink(empty($row['uname']) ? $row['nickname'] : $row['uname'], ".");
          	if (empty($pperm))
              $pperm = "#";
            

            $zm = strtoupper($pperm[0]);
            if (preg_match('/[A-Z]/', $zm)) {
                if(empty($maplist[$zm])) $maplist[$zm] = [];
                $maplist[$zm][] = $row;
            } else {
                if(empty($maplist['#'])) $maplist['#'] = [];
                $maplist['#'][] = $row;
            }
        }

        $this->success("用户列表", [
            "list" => $maplist
        ]);
    }

    /**
     * 订单数量
     * 订单状态:1=创建订单,2=订单洽谈,3=订单进行中,4=订单结束,5=订单失效
     * @return void
     */
    public function ordercount($role) {

        $custuser = (new Cust())
            ->where('id', $this->getCustId())
            ->find();

        $reuslt = [
            'dj' => 0,
            'qt' => 0,
            'zx' => 0,

            'tz' => 0,
            'xj' => 0,
        ];
        if ($role == 'agent') {

            $reuslt['dj'] = (new Order())
                ->where('agent', $this->getCustId())
                ->where('status', 1)
                ->count();

            $reuslt['qt'] = (new Order())
                ->where('agent', $this->getCustId())
                ->where('status', 2)
                ->count();

            $reuslt['zx'] = (new Order())
                ->where('agent', $this->getCustId())
                ->where('status', 3)
                ->count();

        } else if ($role == 'photoer') {

            // 待接
            if($custuser['is_tg'] == 'n'){
                $reuslt['dj'] = 0;
            } else {
                $list = (new Order())
                    ->where('status', 1)
                    ->where('createtime', '>=', strtotime("-1day"))
                    ->select();
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
                    if (true || OrderServer::hasAllow($row['allow'], $custStyles, $hasStyle)){
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

                $reuslt['dj'] = count($resultlist);
            }
            

            $reuslt['qt'] = (new Order())
                ->where('photoerid', $this->getCustId())
                ->where('status', 2)
                ->count();

            $reuslt['zx'] = (new Order())
                ->where('photoerid', $this->getCustId())
                ->where('status', 3)
                ->count();
        } else if ($role == 'teacher') {
            $reuslt['xj'] = (new StarUp())
            ->with(['styles', 'cust'])
            ->where('star_up.step', 1)
            ->where('star_up.needstar', '<', 5)
            ->where('star_up.id', 'not in', function($query){
                $query->table((new StarUpLog())
                    ->where('teacher', $this->getCustId())->buildSql() . ' temp_table')
                    ->field('oid');
            })
            ->count();

            $reuslt['zp'] = (new Zp())
            ->with(['styles', 'cust'])
            ->where('zp.check', 't')
            ->count();
        }

        // 通知
        $reuslt['tz'] = (new Notify())->where('is_read', 'n')->where('cust', $this->getCustId())->count();

        return $this->success('订单状态', $reuslt);
    }
}
