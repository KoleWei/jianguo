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
use fast\Random;
use fast\Tree;
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
