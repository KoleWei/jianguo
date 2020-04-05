<?php

namespace app\common\server;

use app\common\model\Cust;
use app\common\model\StylesCust;
use app\common\model\Zp;
use app\wxapi\library\Utils;
use think\Db;
use think\Model;


class StyleServer
{

    public static function updateTotalStyleState() {
        $sql = "update jg_styles_cust sc set read_num = ( select sum(read_num) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust ), ac_zp = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y' ), has_top = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1 )";
        Db::execute($sql);
    }

}
