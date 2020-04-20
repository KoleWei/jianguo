<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Yuyue;
use app\common\model\Zp;
use app\common\server\NotifyServer;
use app\common\server\OrderServer;
use app\wxapi\library\Wx;
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
        $styles = (new Styles())->order('weigh DESC')->select();

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

        // 通知
        NotifyServer::notify($param['cust'], 'sys', [
            "type" => 5,
            "role" => "photoer",
            "desc" => "预约",
            "content" => "客户: " . $param['uname'] . "<br/>电话: " . $param['phone'] . "<br/>预约内容: " . $param['remark']
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
    public function randomcall($uid = 0, $isu = 0) {
        $phone = null;
        if ($uid > 0) {
            $cust = Cust::get($uid);
            if (!empty($cust) && $cust['is_tg'] == 'y') {
                $phone = $cust['phone'];
            }

            if ($isu > 0) {
                $phone = $cust['phone'];
                return $this->success('获得电话', $phone);
            }
        }

        if (empty($phone)){
            $f = (new Cust());
            $phonelist = $f->where("phone != ''")->where('is_agent_vip', 'y')->where('is_agent', 'y')->field('phone')->select();
            if (!empty($phonelist)){
                $phone = $phonelist[array_rand($phonelist, 1)]['phone'];
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
        $result = array();

        $param = $this->request->param();
        $zpmodel = new Zp();
        $zpmodel->with(['styles', 'cust', 'stylescust'])
            ->where('stylescust.cust = zp.cust')
            ->where('zp.check', 'y');

        // 类目
        if (!empty($param['style'])) {
            $zpmodel->where('zp.style', $param['style']);
        }

        // 摄影师
        if (!empty($param['cust'])) {
            $zpmodel->where('zp.cust', $param['cust']);
            $result["cust"] = (new Cust())->field(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage', 'is_tg'])->find();
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
        $result["rows"] = $list;
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
     * 获得类目
     * @return void
     */
    public function prostyles() {
        $param = $this->request->param();
        $scmodel = new StylesCust();
        $scmodel->with(['styles', 'cust'])
            ->where('styles_cust.ac_zp', '>', 0);

        // 类目
        if (!empty($param['style'])) {
            $scmodel->where('styles_cust.style', $param['style']);
        }

        // 摄影师
        if (!empty($param['cust'])) {
            $scmodel->where('styles_cust.cust', $param['cust']);
        }

        // 搜索
        if (!empty($param['searchphotoername'])) {
            // 搜索摄影师
            $scmodel->where("(cust.uname != '' and cust.uname like '%". $param['searchphotoername'] ."%') or (cust.uname = '' and  cust.nickname like '%". $param['searchphotoername'] ."%')");
        }

        // 排序
        if (!empty($param['sort'])) {
            switch ($param['sort']) {
                case 'xj':
                    // 星级
                    $scmodel->order('styles_cust.star DESC');
                    break;
                case 'xjs':
                    // 星级升序
                    $scmodel->order('styles_cust.star ASC');
                    break;
                case 'rd':
                    // 浏览量
                    $scmodel->order('styles_cust.read_num DESC');
                    break;
                case 'zx':
                    // 最新
                    $scmodel->order('styles_cust.c_time DESC');
                    break;
                case 'zh':
                default:
                    // 前5个作品管理员置顶，后面按星级排序
                    $scmodel->order('styles_cust.has_top DESC, styles_cust.star DESC');
                    break;
            }
        }

        // 分页
        if (!empty($param['curpage'])) {
            $scmodel->limit($this->limitfmt($param['curpage']));
        }

        $list = $scmodel->select();

        foreach ($list as $row) {
            $row->visible(['defimage','star', 'has_top']);
            $row->visible(['styles']);
            $row->getRelation('styles')->visible(['id','defimage', 'name', 'showimage', 'type']);
            $row->visible(['cust']);
            $row->getRelation('cust')->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }
        $list = collection($list)->toArray();
        $result = array("rows" => $list);
        $this->success('读取作品', $result);
    }

    /**
     * 读取产品
     * @return void
     */
    public function product($id)
    {
        $params = $this->request->param();
        $access = false;
        $accessmsg = '';

        $zpmodel = (new Zp())
            ->with(['styles', 'cust'])
            ->where('zp.id', $id);

        $detail = $zpmodel->find();

        if (!empty($detail)) {
            if ($detail['check'] == 'n') {
                $accessmsg = '作品审核被拒绝';
            } else

                if ($detail['check'] == 't') {
                    $accessmsg = '作品正在审核中';
                } else

                    if ($detail['check'] == 'y') {
                        $access = true;
                    }

            if ($detail['cust']['id'] == $this->getCustId()) {
                $access = true;
            }

            $detail->visible(['id','covorimage','is_top', 'type', 'type_text', 'style', 'read_num', 'data']);
            $detail->visible(['styles']);
            $detail->getRelation('styles')->visible(['defimage', 'name', 'showimage', 'type']);
            $detail->visible(['cust']);
            $detail->getRelation('cust')->visible(['nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage', 'is_tg', 'id']);
        } else {
            $access = false;
            $accessmsg = '作品不存在';
        }

        if ($access) {
            (new Zp())->where('id', $id)->setInc('read_num');
            (new StylesCust())->where('cust', $detail['cust'])->where('style', $detail['style'])->setInc('read_num');
        }

        $this->success('读取作品', [
            "product"=> $detail,
            "accessmsg" => $accessmsg,
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

    /**
     * 分享图片
     */
    public function shareimg($id) {

        $config = Config::get('site');

        $zp = (new Zp())
            ->where('id', $id)
            ->find();

        if (empty($zp)) {
            return $this->error('作品不存在', null, 404);
        }

        if (!empty($zp['fximage'])){
            //header("Location: " . $this->request->baseUrl . $zp['fximage']);
        }

        $qrimg = $zp['qrimage'];
        // $qrimg = "";
        if (empty($qrimg) || is_file(ROOT_PATH . 'public' . $qrimg)) {
            $qrimg = Wx::qrcode($id,'pages/product/detail?id=' . $id . '&isu=1', $config['miniqrwidth']);
            $zp->save([
                'qrimage' => $qrimg
            ]);
        }
        $image = \think\Image::open(ROOT_PATH . 'public' . $config['wx_share_bg']);

        $savePath = '/uploads/qr/share';
        $downloadpath = ROOT_PATH . 'public' . $savePath;
        if (!is_dir($downloadpath)){  
            mkdir(iconv("UTF-8", "GBK", $downloadpath),0777,true); 
        }

        $dbpath = $savePath . '/'. $id .'.jpg';

        $pathimg =  $downloadpath . '/'. $id .'.jpg';


        $image = $image->water(ROOT_PATH . 'public' . $qrimg,[
            $config['miniqrleft'], $config['miniqrtop']
        ] ,100);

        if (!empty($zp['covorimage'])) {

            $covorimagePath = ROOT_PATH . 'public' . $zp['covorimage'];
            $covorimagePath = str_replace('_sm', '',$covorimagePath);

            $covorimage = \think\Image::open($covorimagePath);
            // 剪切图片

            if (!empty($covorimage)) {

                $covorimage->thumb(900, 600,\think\Image::THUMB_CENTER)->save($downloadpath . '/t_'. $id .'.jpg');

                $image = $image->water($downloadpath . '/t_'. $id .'.jpg',[
                    ($image->width()-900)/2, 300
                ] ,100);
            }
           
        }

        $image->save($pathimg); 



        $zp->save([
            'fximage' => $dbpath,
        ]);

        header("Location: " . $this->request->baseUrl . $dbpath);
    }

}
