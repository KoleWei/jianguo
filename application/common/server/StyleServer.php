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

    // 自动更新状态
    public static function updateTotalStyleState() {
        $sql = "update jg_styles_cust sc set read_num = ( select coalesce(sum(read_num),0) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust ), ac_zp = ( select count(1) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y' ), has_top = ( select count(1) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1 ),c_time= (select max(createtime) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y')";
        return Db::execute($sql);
    }

     // 自动更新状态
     public static function updateTotalStyleStateByUid($uid) {
        $sql = "update jg_styles_cust sc set read_num = ( select coalesce(sum(read_num),0) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.cust = ? ), ac_zp = ( select count(1) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.cust = ? and zp.check = 'y' ), has_top = ( select count(1) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.cust = ? and zp.is_top = 1 ),c_time= (select max(createtime) from jg_zp zp where zp.deletetime is null and zp.style = sc.style and zp.cust = sc.cust and zp.cust = ? and zp.check = 'y') where sc.cust = ?";
        return Db::execute($sql, [$uid,$uid,$uid,$uid,$uid]);
    }

    // 更新热图
    public static function updateHotImage() {
        $sql = "update jg_styles_cust sc set hotimage = (select covorimage from jg_zp zp where zp.deletetime is null and  sc.cust = zp.cust and sc.style = zp.style ORDER BY read_num desc limit 1)";
        return Db::execute($sql);
    }

}
