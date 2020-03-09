<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Yuyue;
use app\common\model\Zp;
use think\Config;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function index()
    {
        // 获取首页
        $styles = (new Styles())->select();

        $this->success('成功', [
            "styles" =>  $styles
        ]);
    }

    /**
     * 显示样式
     * @return void
     */
    public function styles()
    {
        // 获取首页
        $styles = (new Styles())->select();

        $this->success('成功', $styles);
    }

    /**
     * 存储预留信息
     * @return void
     */
    public function savebook() {
        $param = $this->request->param();
        (new Yuyue())->save([
            "style" => $param['style'],
            "name" => $param['uname'],
            "phone" => $param['phone'], 
            "msg" => $param['remark'],
            "cust" => $param['cust'],
        ]);

        $this->success('提交成功');
    }

    /**
     * 
     * @return void
     */
    public function searchuserorstyle($search) {

        // 查询人
        $cust = (new Cust)->where('is_photoer', 'y')->where("(uname != '' and uname like '%". $search ."%') or (uname = '' and  nickname like '%". $search ."%')")->find();
        if (!empty($cust)){
            return $this->success('获得摄影师', [
                "type" => 'photoer',
                "data" => [
                    "id" => $cust['id'],
                    "name" => empty($cust['uname']) ? $cust['nickname'] : $cust['uname']
                ]
            ]);
        }

        // 查询类型
        $style = (new Styles)->where('name', 'like', '%' . $search . '%')->find();
        if (!empty($style)){
            return $this->success('获得类目', [
                "type" => 'styles',
                "data" => [
                    "id" => $style['id'],
                    "name" => $style['name']
                ]
            ]);
        }

        $this->error('未找到结果');
    }

    /**
     * 随机获得电话号码
     * @return void
     */
    public function randomcall($uid = 0) {
        $phone = null;
        if ($uid > 0) {
            $cust = Cust::get($uid);
            if (!empty($cust) && $cust['is_tg'] == 'y') {
                $phone = $cust['phone'];
            }
        }

        if (empty($phone)){
            $phonelist = (new Cust())->where("phone != ''")->where('is_agent', 'y')->column('phone');
            if (!empty($phonelist)){
                $phone = array_rand($phonelist, 1);
            }
        }

        $this->success('获得电话', $phone);
    }

    /**
     * 获得微信
     * @param integer $uid
     * @return void
     */
    public function randomwx($uid = 0) {
        $wx = null;
        if ($uid > 0) {
            $cust = Cust::get($uid);
            if (!empty($cust) && $cust['is_tg'] == 'y') {
                $wx = $cust['wximg'];
            }
        }

        $this->success('获得电话', $wx);
    }

    /**
     * 作品列表
     * @return void
     */
    public function products()
    {
        $param = $this->request->param();
        $zpmodel = new Zp();
        $zpmodel->with(['styles', 'cust', 'stylescust'])
            ->where('stylescust.cust = zp.cust')
            ->where('zp.check', 'y');

        // 类目
        if (!empty($param['style'])) {
            $zpmodel->where('zp.style', $param['style']);
        }

        // 搜索
        if (!empty($param['searchphotoername'])) {
            // 搜索摄影师
            $zpmodel->where("(cust.uname != '' and cust.uname like '%". $param['searchphotoername'] ."%') or (cust.uname = '' and  cust.nickname like '%". $param['searchphotoername'] ."%')");
        }

        // 排序
        if (!empty($param['sort'])) {
            switch ($param['sort']) {
                case 'xj':
                    // 星级
                    $zpmodel->order('stylescust.star DESC');
                    break;
                case 'rd':
                    // 浏览量
                    $zpmodel->order('zp.read_num DESC');
                    break;
                case 'zx':
                    // 按照更新时间，最新发布的在最前面
                    $zpmodel->order('zp.createtime DESC');
                    break;
                case 'zh':
                default:
                    // 前5个作品管理员置顶，后面按星级排序
                    $zpmodel->order('is_top ASC, stylescust.star DESC');
                    break;
            }
        }

        // 分页
        if (!empty($param['curpage'])) {
            $zpmodel->limit($this->limitfmt($param['curpage']));
        }

        $list = $zpmodel->select();

        foreach ($list as $row) {
            $row->visible(['id','covorimage','is_top', 'type', 'type_text', 'style', 'read_num', 'data']);
            $row->visible(['styles']);
            $row->getRelation('styles')->visible(['defimage', 'name', 'showimage', 'type']);
            $row->visible(['cust']);
            $row->getRelation('cust')->visible(['nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
            $row->visible(['stylescust']);
            $row->getRelation('stylescust')->visible(['defimage','star']);
        }
        $list = collection($list)->toArray();
        $result = array("rows" => $list);
        $this->success('读取作品', $result);
    }

    /**
     * 获得用户类型
     * @return void
     */
    public function ustyles($uid) {
        $param = $this->request->param();
        $hasstyle = (new Zp)->where('check', 'y')->where('cust', $uid)->distinct(true)->column('style');

        
        $stylesModel = (new StylesCust())
            ->with(['styles'])
            ->where('styles_cust.cust', $uid)
            ->where('styles_cust.style', 'in', $hasstyle);

        if (!empty($param['search'])) {
            $stylesModel->where('styles.name', 'like', '%' . trim($param['search'] . '%')); 
        }

        $styles = $stylesModel->select();

        $this->success('获取成功', $styles);
    }


    /**
     * 读取产品
     * @return void
     */
    public function product($id)
    {
        $params = $this->request->param();
        $access = false;

        $zpmodel = (new Zp())
            ->with(['styles', 'cust'])
            ->where('zp.id', $id);

        $detail = $zpmodel->find();

        if (!empty($detail)) {
            if ($detail['cust']['id'] == $this->getCustId()) {
                $access = true;
            }

            $detail->visible(['id','covorimage','is_top', 'type', 'type_text', 'style', 'read_num', 'data']);
            $detail->visible(['styles']);
            $detail->getRelation('styles')->visible(['defimage', 'name', 'showimage', 'type']);
            $detail->visible(['cust']);
            $detail->getRelation('cust')->visible(['nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage', 'is_tg', 'id']);
        }

        $this->success('读取作品', [
            "product"=> $detail,
            "access" => $access
        ]);
    }

    /**
     * 搜索
     *
     * @return void
     */
    public function search($search) {

        // 搜索用户

        // 搜索分类


    }
}
