<?php

namespace app\common\server;

use app\common\model\StarUp;
use app\common\model\Styles;
use app\common\model\StylesCust;
use Exception;
use think\console\output\formatter\Style;

class StarUpServer
{
   
    public static function end($id, $status) {
        $starUp = (new StarUp())
            ->where('id', $id)
            ->where('step', "1")
            ->find();

        if (empty($starUp)) {
            throw new Exception("星级记录不存在");
        }

        $styleCust = (new StylesCust())
            ->where('id', $starUp['stylecust'])
            ->where('style', $starUp['style'])
            ->find();

        if (empty($styleCust)) {
            throw new Exception("摄影师类目不存在");
        }


        // 类型
        $style = (new Styles())->where('id', $starUp['style'])->find();


        if ($status) {
            $styleCust->save([
                "star" => $starUp['needstar']
            ]);

            NotifyServer::notify($starUp['cust'], 'sys', [
                "type" => 4,
                "style" => $starUp['style'],
                "role" => "photoer",
                "desc" => "星级审核通过",
                "content" => $style['name'] . '类目' . $starUp['needstar'] . "星级审核通过"
            ]);

        } else {
            NotifyServer::notify($starUp['cust'], 'sys', [
                "type" => 4,
                "style" => $starUp['style'],
                "role" => "photoer",
                "desc" => "星级审核不通过",
                "content" => $style['name'] . '类目' . $starUp['needstar'] . "星级审核不通过"
            ]);
        }


        $starUp->save([
            "step" => "2",
            "endtime" => time()
        ]);
    }

}
