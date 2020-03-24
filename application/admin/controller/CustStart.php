<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\StarUp;
use app\common\server\StarUpServer;
use Exception;
use think\Db;

/**
 * 星级提升
 *
 * @icon fa fa-circle-o
 */
class CustStart extends Backend
{
    
    /**
     * Cust模型对象
     * @var \app\common\model\Cust
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new StarUp();
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
        $this->relationSearch = true;
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
                    ->where('step', '1')
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['styles', 'cust'])
                    ->where('star_up.step', '1')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','needstar','createtime']);
                $row->visible(['styles']);
                $row->getRelation('styles')->visible(['name']);
                $row->visible(['cust']);
				$row->getRelation('cust')->visible(['uname', 'nickname']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function yunxu($ids) {
        Db::startTrans();
        try {
            StarUpServer::end($ids, true);
        }catch(Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('审核操作成功');
    }

    

    public function jujue($ids) {
        Db::startTrans();
        try {
            StarUpServer::end($ids, false);
        }catch(Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('审核操作成功');
    }
}
