<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\model\Notify;
use app\common\model\StylesCust;
use app\common\server\NotifyServer;
use Exception;
use think\Db;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use app\common\model\Cust as ModelCust;
use app\common\model\Order;
use app\common\model\Styles;
use app\common\model\Zp;
use fast\Random;
use fast\Tree;
use PDOException;
use think\exception\ValidateException;
use think\Validate;

/**
 * 用户
 *
 * @icon fa fa-circle-o
 */
class Cust extends Backend
{
    
    /**
     * Cust模型对象
     * @var \app\common\model\Cust
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Cust;
        $this->view->assign("isPhotoerList", $this->model->getIsPhotoerList());
        $this->view->assign("isTeacherList", $this->model->getIsTeacherList());
        $this->view->assign("isAgentList", $this->model->getIsAgentList());
        $this->view->assign("isVipAgentList", $this->model->getIsVipAgentList());
        $this->view->assign("isTgList", $this->model->getIsTgList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','logoimage','openid','nickname','uname','phone','is_photoer','is_teacher','is_agent','is_agent_vip','is_tg','createtime']);
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function setstar($ids) {

        $sc = (new StylesCust())->where('id', $ids)->find();

        if ($this->request->isAjax()){

            Db::startTrans();
            try {
                $sc->save([
                    'star' => $this->request->param('star')
                ]);
            }catch(Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
    
            Db::commit();
            return $this->success('设置成功');
        }

        $this->assign('sc', $sc);
        return $this->view->fetch();
        
    }

    /**
     * 查看星级
     *
     * @return void
     */
    public function star($ids) {
        $cust = (new \app\common\model\Cust())->where('id',$ids)->find();
        if (empty($cust)) {
            $this->error('用户不存在');
        }

        $styles = (new StylesCust())
            ->with(['styles'])
            ->where('styles.deletetime is null')
            ->where('styles_cust.cust', $ids)
            ->select();

        $this->assign('styles', $styles);
        return $this->view->fetch();
    }


    public function notify($ids) {
       
        if ($this->request->isAjax()){

            Db::startTrans();
            try {
                $is = explode(',', $ids);

                if (count($is) == 0) {
                    $this->error('未选择用户');
                }
                foreach($is as $i) {
                    NotifyServer::notify($i, 'mg', [
                        "type" => 3,
                        "role" => 'all',
                        "desc" => $this->request->param('desc'),
                        "content" => $this->request->param('notify'),
                    ]);
                }

            }catch(Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
    
            Db::commit();
            return $this->success('设置成功');
        }

        return $this->view->fetch();
    }
    /**
     * 创建作品
     *
     * @return void
     */
    public function createzp($ids){
        $styles = (new Styles())->select();
        $this->view->assign("stylesList", $styles);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;


                // 作品数量
                $count = (new Zp())
                    ->where('cust', $ids)
                    ->where('style', $params['style'])
                    ->count();

                


                $styleObj = (new Styles())->where('id', $params['style'])->find();

                $covorimage = [];
                if ($styleObj['type'] == 'img') {
                    $params['data'] = $params['data-zp'];
                    $covorimage = array_map(function ($n) {
                        return substr($n,0,strripos($n, '.')) . '_sm' . substr($n,strripos($n, '.'));
                    }, explode(',',$params['data']));
                } else {
                    $params['data'] = $params['data-tx'];
                }

                $data = explode(',',$params['data']);

                if (empty($params['data']) || count($data) == 0) {
                    return $this->error('请上传作品');
                }

                if ($count > 30 - count($data)){
                    return $this->error('上传作品超过数量');
                }

                Db::startTrans();
                try {
                    foreach($data as $k => $d){
                        $model = new Zp();
                        $r = $model->save([
                            "cust" => $ids,
                            "style" => $params['style'],
                            "data" => $d,
                            "covorimage" => ($styleObj['type'] == 'img'? $covorimage[$k] : $params['covorimage']),
                            "type" => ($styleObj['type'] == 'img'? 'zp' : 'tx'),
                            "check" => 'y',
                        ]);
                        if (empty($r)) {
                            throw new Exception('作品上传失败');
                        }
            
                        $sobj = Styles::get($styleObj['id']);
                        if (empty($sobj)) {
                            throw new Exception('作品类目不存在');
                        }
            
                        $scount = (new StylesCust())->where('cust', $ids)
                            ->where('style', $params['style'])
                            ->count();
            
                        if ($scount <= 0) {
                            $r = (new StylesCust())->save([
                                "cust" => $ids,
                                "style" => $params['style'],
                            ]);
            
                            if (empty($r)) {
                                throw new Exception('作品上传失败');
                            }
                        }
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
                
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 订单统计
     *
     * @return void
     */
    public function ordertotal($ids) {
        $cust = (new ModelCust())->where('id', $ids)->find();
        if (empty($cust)){
            return $this->error('用户不存在');
        }
        $this->assign("cust", $cust);

        if ($cust['is_agent'] == 'y') {

            // 发布订单数
            $tfbdd = (new Order())->where('agent', $ids)->count();
            // 成单数
            $tcdc = (new Order())->where('agent', $ids)->where('status', '4')->count();
            // 成单率
            if ($tfbdd == 0)
                $tcdl = 0;
            else
                $tcdl = round(($tcdc/$tfbdd)*100, 2);

            // 成单总金额
            $tcdje = (new Order())->where('agent', $ids)->where('status', '4')->sum('ordermoney');
            // 好评率 
            $thp = $cust['astar'];
            $this->assign("tfbdd", $tfbdd);
            $this->assign("tcdc", $tcdc);
            $this->assign("tcdl", $tcdl);
            $this->assign("tcdje", $tcdje);
            $this->assign("thp", $thp);
        }

        if ($cust['is_agent'] == 'y') {

            //成单数
            $pcdc = (new Order())->where('photoerid', $ids)->where('status', '4')->count();
            //成单总金额
            $pcdje = (new Order())->where('photoerid', $ids)->where('status', '4')->sum('ordermoney');
            // 好评率
            $php = $cust['pstar'];
            $this->assign("pcdc", $pcdc);
            $this->assign("pcdje", $pcdje);
            $this->assign("php", $php);

        }

        return $this->view->fetch();
    }

    /**
     * 创建账号
     *
     * @return void
     */
    public function account($ids) {
        $idadmin = (new Admin())->where("custid", $ids)->find();
        if (!empty($idadmin)) {
            return $this->redirect("admin/cust/eaccount",'ids=' .  $ids);
        }

        if ($this->request->isPost()) {

            $model = model('Admin');

            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                if (!Validate::is($params['password'], '\S{6,16}')) {
                    $this->error(__("Please input correct password"));
                }
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
                $params['custid'] = $ids; // 为客户开通账号
                $result = $model->validate('Admin.add')->save($params);
                if ($result === false) {
                    $this->error($model->getError());
                }

                //过滤不允许的组别,避免越权
                $dataset = [];
                $dataset[] = ['uid' => $model->id, 'group_id' => 6];
                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }

    /**
     * 编辑账户
     */
    public function eaccount($ids)
    {
        $idadmin = (new Admin())->where("custid", $ids)->find();
        $model = model('Admin');
        $row = $model->get(['id' => $idadmin['id']]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['password']) {
                    if (!Validate::is($params['password'], '\S{6,16}')) {
                        $this->error(__("Please input correct password"));
                    }
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                } else {
                    unset($params['password'], $params['salt']);
                }
                //这里需要针对username和email做唯一验证
                $adminValidate = \think\Loader::validate('Admin');
                $adminValidate->rule([
                    'username' => 'require|regex:\w{3,12}|unique:admin,username,' . $row->id,
                    'email'    => 'email',
                    'password' => 'regex:\S{32}',
                ]);
                $result = $row->validate('Admin.edit')->save($params);
                if ($result === false) {
                    $this->error($row->getError());
                }

                $this->success();
            }
            $this->error();
        }
       
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
