<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Notify;
use app\common\model\StylesCust;
use app\common\server\NotifyServer;
use Exception;
use think\Db;

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
                $row->visible(['id','logoimage','openid','nickname','uname','phone','is_photoer','is_teacher','is_agent','is_tg','createtime']);
                
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
}
