<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\StarUp;
use app\common\model\Styles;
use app\common\model\StylesCust;
use app\common\model\Zp;
use Exception;
use think\Config;
use think\Db;

use function PHPSTORM_META\map;

/**
 * 摄影师
 */
class Photoer extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 上传作品
     */
    public function createzp($covorimage, $data, $style, $type)
    {
        $model = new Zp();

        Db::startTrans();
        try {
            $r = $model->save([
                "cust" => $this->getCustId(),
                "style" => $style,
                "data" => $data,
                "covorimage" => $covorimage,
                "type" => $type,
                "check" => 't',
            ]);
            if (empty($r)) {
                throw new Exception('作品上传失败');
            }

            $sobj = Styles::get($style);
            if (empty($sobj)) {
                throw new Exception('作品类目不存在');
            }

            $scount = (new StylesCust())->where('cust', $this->getCustId())
                ->where('style', $style)
                ->count();

            if ($scount <= 0) {
                $r = (new StylesCust())->save([
                    "cust" => $this->getCustId(),
                    "style" => $style,
                ]);

                if (empty($r)) {
                    throw new Exception('作品上传失败');
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('上传成功, 等待审核');
    }

    /**
     * 显示分类
     *
     */
    public function styles()
    {

        $hasstyle = (new Zp)->where('cust', $this->getCustId())->distinct(true)->column('style');

        $styles = (new StylesCust())
            ->with(['styles'])
            ->where('styles.deletetime is null')
            ->where('styles_cust.cust', $this->getCustId())
            ->where('styles_cust.style', 'in', $hasstyle)
            ->select();

        $this->success('获取成功', $styles);
    }

    /**
     * 设置样式
     *
     * @return void
     */
    public function setstyle($style) {
        $param = $this->request->param();
        $scust = (new StylesCust())
            ->where('cust', $this->getCustId())
            ->where('style', $style)
            ->find();

        $data = [];
        if (!empty($param['defimage'])) {
            $data['defimage'] = $param['defimage'];
        }

        $scust->save($data);

        $this->success('更换成功');
    }

    /**
     * 获得类型
     * @param [type] $id
     * @return void
     */
    public function style($id) {
        $style = (new StylesCust())
            ->with(['styles'])
            ->where('styles.deletetime is null')
            ->where('styles_cust.cust', $this->getCustId())
            ->where('styles_cust.style', $id)
            ->find();

            $this->success('获取成功', $style);
    }

    /**
     * 读取作品
     */
    public function product() {
        $param = $this->request->param();

        $zpmodle = (new Zp)->where('cust', $this->getCustId());

        if (!empty($param['style'])) {
            $zpmodle->where('style', $param['style']);
        }

        $list = $zpmodle->select();

        $this->success('获取成功', $list);
    }

    /**
     * 提升星级
     */
    public function starup($styleid) {
        $stylecust = (new StylesCust())
            ->where('cust', $this->getCustId())
            ->where('style', $styleid)
            ->find();

        if (empty($stylecust)) {
            return $this->error('当前类目没有作品，无法提升星级!');
        }

        if ($stylecust['star'] >= 5) {
            return $this->error('已经到达最高星级!');  
        }

        Db::startTrans();
        try {
            $starlogcount = (new StarUp())
                ->where('cust', $this->getCustId())
                ->where('style', $styleid)
                ->where('step', 1)
                ->count();
            if ($starlogcount > 0) {
                return $this->error('该类目星级在审核中!');
            }

            (new StarUp())->save([
                "cust" => $this->getCustId(),
                "style" => $styleid,
                "stylecust" => $stylecust['id'],
                "needstar" => $stylecust['star'] + 1,
                "step" => 1,
                "createtime" => time(),
            ]);
        } catch(Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        Db::commit();
        
        return $this->success('提升星级申请成功，等待审核');
    }
}
