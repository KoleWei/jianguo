<?php

namespace app\common\server;

use app\common\model\StarUp;
use app\common\model\Styles;
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

        // 类型
        $style = (new Styles())->where('id', $zp['style'])->find();

        if ($status == 'y') {
            NotifyServer::notify($zp['cust'], 'sys', [
                "type" => 1,
                "zp" => $zp['id'],
                "role" => "photoer",
                "desc" => "作品审核通过",
                "content" => $style['name'] . "作品审核通过"
            ]);
        } else {
            NotifyServer::notify($zp['cust'], 'sys', [
                "type" => 1,
                "zp" => $zp['id'],
                "role" => "photoer",
                "desc" => "作品审核不通过",
                "content" => $style['name'] . "作品审核不通过。拒绝理由:" . $msg
            ]);
        }

        $zp->save([
            "teacher" => $teacher,
            "check" => $status,
            "checktime" => time(),
        ]);
    }

}
