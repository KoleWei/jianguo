<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use app\common\model\Cust;
use app\common\model\Styles;
use app\common\model\Zp;
use app\common\server\StyleServer;
use app\common\server\ZpServer;
use Exception;
use PDOException;
use think\Db;
use think\exception\ValidateException;

/**
 * 作品审核
 * @icon fa fa-circle-o
 */
class ThZp extends Backend
{
    
    /**
     * Zp模型对象
     * @var \app\common\model\Zp
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Zp;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("isTopList", $this->model->getIsTopList());
        $this->view->assign("checkList", $this->model->getCheckList());

        $styles = (new Styles())->select();
        $this->view->assign("stylesList", $styles);


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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['cust','styles'])
                    ->where('zp.check', 't')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['cust','styles'])
                    ->where('zp.check', 't')
                    ->where($where)
                    ->order('zp.createtime desc ')
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','type','data', 'createtime', 'covorimage']);
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
     * 作品审核
     *
     * @return void
     */
    public function check($ids, $status, $msg = "") {

        $zp = (new Zp())
            ->where('id', $ids)
            ->where('check', 't')
            ->find();

        if (empty($zp)){
            return $this->error('审核作品不存在');
        }

        if ($status == 'n'){
            if ($this->request->isAjax()){
                if (empty($msg)) {
                    return $this->error('拒绝理由请填写');
                }
            } else {
                $this->assign("ids", $ids);
                return $this->view->fetch();
            }
        }

        Db::startTrans();
        try {
            ZpServer::check($ids, $status, $this->cust['id'], $msg);
        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        // 作品审核
        StyleServer::updateTotalStyleState();
        Db::commit();
        return $this->success('审核操作成功');
        

    }


    /**
     * 查看详情
     *
     * @param [type] $ids
     * @return void
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);


                    StyleServer::updateTotalStyleState();

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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
