<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use app\common\model\Cust;
use app\common\model\StarUp;
use app\common\model\StarUpLog;
use app\common\model\Zp;
use app\common\server\StarUpServer;
use Exception;
use think\Db;

/**
 * 提升星级
 *
 * @icon fa fa-circle-o
 */
class ThStarUp extends Backend
{
    
    /**
     * StarUp模型对象
     * @var \app\common\model\StarUp
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\StarUp;
        $this->view->assign("stepList", $this->model->getStepList());

        $admin = (new Admin())->where('id', $this->auth->id)->find();
        if (empty($admin['custid'])) {
            return $this->error("无权限");
        }

        $cust = (new Cust())->where('id', $admin['custid'])->find();
        if ($cust['is_teacher'] != 'y') {
            return $this->error("无权限");
        }
        $this->cust = $cust;
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
            $model = (new StarUp());
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $model
                    ->with(['styles', 'cust'])
                    ->where('star_up.step', 1)
                    ->where('star_up.needstar', '<', 5)
                    ->where('star_up.id', 'not in', function($query){
                        $query->table((new StarUpLog())
                            ->where('teacher', $this->cust['id'])->buildSql() . ' temp_table')
                            ->field('oid');
                    })
                    ->where($where)
                    ->count();

            $list = $model
                    ->with(['styles', 'cust'])
                    ->where('star_up.step', 1)
                    ->where('star_up.needstar', '<', 5)
                    ->where('star_up.id', 'not in', function($query){
                        $query->table((new StarUpLog())
                            ->where('teacher', $this->cust['id'])->buildSql() . ' temp_table')
                            ->field('oid');
                    })
                    ->where($where)
                    ->order($sort, $order)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','needstar','step', 'createtime', 'endtime']);
                $row->visible(['styles']);
                $row->getRelation('styles')->visible(['id','defimage', 'name', 'showimage', 'type']);
                $row->visible(['cust']);
                $row->getRelation('cust')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);

            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 作品列表
     */
    public function plist($custid, $style)
    {
        $this->model = new \app\common\model\Zp();
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
            $total = (new Zp())
                    ->with(['cust','styles'])
                    ->where('cust.id', $custid)
                    ->where('styles.id', $style)
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = (new Zp())
                    ->with(['cust','styles'])
                    ->where('cust.id', $custid)
                    ->where('styles.id', $style)
                    ->where($where)
                    ->order('zp.createtime desc ')
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','type','data', 'createtime', 'covorimage', 'check']);
                $row->visible(['cust']);
				$row->getRelation('cust')->visible(['nickname','uname']);
				$row->visible(['styles']);
                $row->getRelation('styles')->visible(['name']);

            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

     /**
     * 星级审核
     *
     * @return void
     */
    public function check($ids, $status) {
        $starUp = (new StarUp())
            ->where('id', $ids)
            ->where('step', "1")
            ->find();

        if (empty($starUp)) {
            return $this->error('审核无法审核');
        }

        $starUpLogCount = (new StarUpLog())
            ->where('oid', $ids)
            ->where('teacher', $this->cust['id'])
            ->count();
        
        if ($starUpLogCount > 0) {
            return $this->error('该审核已经处理，无法重复处理');
        }

        Db::startTrans();
        try {

            (new StarUpLog())->save([
                "cust" => $starUp['cust'],
                "style" => $starUp['style'],
                "oid" => $starUp['id'],
                "teacher" => $this->cust['id'],
                "status" => $status,
            ]);

            $accessOk = (new StarUpLog())
                    ->where('oid', $ids)
                    ->count();
            $teachercount = (new Cust())->where('is_teacher', 'y')->count();
            $starUpLogCount = (new StarUpLog())->where('oid', $ids)->count();

            // 1-3 只要2个
            if ($starUp['needstar'] <= 3) {
                if ($accessOk == 2) {
                    StarUpServer::end($ids, true);
                    Db::commit();
                    return $this->success('审核操作成功');
                }
            } else if ($starUp['needstar'] == 4) {
                // 4 全部老师
                if ($accessOk >= $teachercount) {
                    StarUpServer::end($ids, true);
                    Db::commit();
                    return $this->success('审核操作成功');
                }
            }

            // 如果老师不够2个
            if ($accessOk >= $teachercount) {
                StarUpServer::end($ids, true);
            } else if ($starUpLogCount >= $teachercount) {
                StarUpServer::end($ids, false);
            }
            
        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('审核操作成功');
    }
}
