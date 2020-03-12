<?php

namespace app\common\server;

use app\common\model\StarUp;
use app\common\model\StylesCust;
use Exception;

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

        if ($status) {
            $styleCust->save([
                "star" => $starUp['needstar']
            ]);
        }


        $starUp->save([
            "step" => "2",
            "endtime" => time()
        ]);
    }

}
