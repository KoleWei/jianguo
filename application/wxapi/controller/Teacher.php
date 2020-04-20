<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\StarUp;
use app\common\model\StarUpLog;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\common\server\StarUpServer;
use app\common\server\StyleServer;
use app\common\server\ZpServer;
use Exception;
use think\Config;
use think\Db;


/**
 * 老师
 */
class Teacher extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $cust = (new Cust)
            ->where('id', $this->getCustId())
            ->find();

        if ($cust['is_teacher'] == 'n') {
            $this->error('当前用户非老师');
        }
    }

    /**
     * 星级审核列表
     */
    public function starlog() {
        $list = (new StarUp())
            ->with(['styles', 'cust'])
            ->where('star_up.step', "1")
            ->where('star_up.needstar', '<', 5)
            ->where('star_up.id', 'not in', function($query){
                $query->table((new StarUpLog())
                    ->where('teacher', $this->getCustId())->buildSql() . ' temp_table')
                    ->field('oid');
            })
            ->select();

        foreach ($list as $row) {
            $row->visible(['id','needstar','step', 'createtime', 'endtime']);
            $row->visible(['styles']);
            $row->getRelation('styles')->visible(['id','defimage', 'name', 'showimage', 'type']);
            $row->visible(['cust']);
            $row->getRelation('cust')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }

        $this->success("星级审核列表", $list);
    }

    /**
     * 提升星级
     */
    public function checkstar($oid, $status) {


        $starUp = (new StarUp())
            ->where('id', $oid)
            ->where('step', "1")
            ->find();

        if (empty($starUp)) {
            return $this->error('审核无法审核');
        }

        $starUpLogCount = (new StarUpLog())
            ->where('oid', $oid)
            ->where('teacher', $this->getCustId())
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
                "teacher" => $this->getCustId(),
                "status" => $status,
            ]);

            $accessOk = (new StarUpLog())
                    ->where('oid', $oid)
                    ->where('status', 'y')
                    ->count();
            $teachercount = (new Cust())->where('is_teacher', 'y')->count();
            $starUpLogCount = (new StarUpLog())->where('oid', $oid)->count();

            // 1-3 只要2个
            if ($starUp['needstar'] <= 3) {
                if ($accessOk == 2) {
                    StarUpServer::end($oid, true);
                    Db::commit();
                    return $this->success('审核操作成功');
                }
            } else if ($starUp['needstar'] == 4) {
                // 4 全部老师
                if ($accessOk >= $teachercount) {
                    StarUpServer::end($oid, true);
                    Db::commit();
                    return $this->success('审核操作成功');
                }
            }

            // 如果老师不够2个
            if ($accessOk >= $teachercount) {
                StarUpServer::end($oid, true);
            } else if ($starUpLogCount >= $teachercount) {
                StarUpServer::end($oid, false);
            }
            
        }catch(Exception $e) {
            Db::rollback();
            throw $e;
            // return $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('审核操作成功');
    }

    /**
     * 作品审核列表
     */
    public function productlog() {
        $list = (new Zp())
            ->with(['styles', 'cust'])
            ->where('zp.check', 't')
            ->select();

        foreach ($list as $row) {
            $row->visible(['id','type','data', 'createtime', 'covorimage']);
            $row->visible(['styles']);
            $row->getRelation('styles')->visible(['id','defimage', 'name', 'showimage', 'type']);
            $row->visible(['cust']);
            $row->getRelation('cust')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }

        $this->success("作品审核列表", $list);
    }
    // 删除作品
    public function deletezp($id) {
        $zp = (new Zp())
            ->where('id', $id)
            ->find();

        if (empty($zp)){
            return $this->error('作品不存在');
        }

        Db::startTrans();
        try {
            $zp->delete();
        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        // 作品审核
        StyleServer::updateTotalStyleState();
        Db::commit();
        return $this->success('删除作品成功');
    }
    // 审核作品
    public function checkproduct($id, $status = "n",$msg = '') {

        $zp = (new Zp())
            ->where('id', $id)
            ->where('check', 't')
            ->find();

        if (empty($zp)){
            return $this->error('审核作品不存在');
        }

        if ($status == 'n' && empty($msg)) {
            return $this->error('拒绝理由请填写');
        }

        Db::startTrans();
        try {
            ZpServer::check($id, $status, $this->getCustId(),$msg);
        }catch(Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
        // 作品审核
        StyleServer::updateTotalStyleState();
        Db::commit();
        return $this->success('审核操作成功');
    }
}
