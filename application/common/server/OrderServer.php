<?php

namespace app\common\server;

use app\common\model\StarUp;
use app\common\model\StylesCust;
use app\common\model\Order;
use Exception;

class OrderServer
{
   
    public static function create() {
        (new Order())->save([
            
        ]);
    }

}
