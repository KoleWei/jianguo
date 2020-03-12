<?php

namespace app\common\server;

use app\common\model\StarUp;
use app\common\model\StylesCust;
use app\common\model\Zp;
use Exception;

class ZpServer
{
   
    public static function check($id, $status, $teacher, $msg = "") {
        $zp = (new Zp())
            ->where('id', $id)
            ->where('check', 't')
            ->find();

        if (empty($zp)){
            throw new Exception('作品不存在');
        }

        $zp->save([
            "teacher" => $teacher,
            "check" => $status,
            "checktime" => time(),
        ]);
    }

}
