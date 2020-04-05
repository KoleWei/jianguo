<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use app\common\model\Cust;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\common\server\StyleServer;
use Exception;
use PDOException;
use think\Db;
use think\exception\ValidateException;

/**
 * 作品
 * @icon fa fa-circle-o
 */
class PhZp extends Backend
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
        if ($cust['is_photoer'] != 'y') {
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
                    ->where('cust.id', $this->cust['id'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['cust','styles'])
                    ->where('cust.id', $this->cust['id'])
                    ->where($where)
                    ->order('is_top asc, createtime desc ')
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id', 'data','covorimage','type','style','is_top','check','read_num','createtime']);
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $model = new Zp();
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;


                // 作品数量
                $count = (new Zp())
                    ->where('cust', $this->cust['id'])
                    ->where('style', $params['style'])
                    ->count();

                if ($count > 30){
                    return $this->error('上传作品超过数量');
                }


                $styleObj = (new Styles())->where('id', $params['style'])->find();

                if ($styleObj['type'] == 'img') {
                    $params['data'] = $params['data-zp'];
                } else {
                    $params['data'] = $params['data-tx'];
                }

                if (empty($params['data'])) {
                    return $this->error('请上传作品');
                }

                Db::startTrans();
                try {
                    $r = $model->save([
                        "cust" => $this->cust['id'],
                        "style" => $params['style'],
                        "data" => $params['data'],
                        "covorimage" => $params['covorimage'],
                        "type" => ($styleObj['type'] == 'img'? 'zp' : 'tx'),
                        "check" => 't',
                    ]);
                    if (empty($r)) {
                        throw new Exception('作品上传失败');
                    }
        
                    $sobj = Styles::get($styleObj['id']);
                    if (empty($sobj)) {
                        throw new Exception('作品类目不存在');
                    }
        
                    $scount = (new StylesCust())->where('cust', $this->cust['id'])
                        ->where('style', $params['style'])
                        ->count();
        
                    if ($scount <= 0) {
                        $r = (new StylesCust())->save([
                            "cust" => $this->cust['id'],
                            "style" => $params['style'],
                        ]);
        
                        if (empty($r)) {
                            throw new Exception('作品上传失败');
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
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where("cust", $this->cust['id'])->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }

                 // 删除重新更新关系
                StyleServer::updateTotalStyleState();
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
