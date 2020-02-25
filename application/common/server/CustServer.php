<?php

namespace app\common\server;

use app\common\model\Cust;
use app\wxapi\library\Utils;
use think\Model;


class CustServer
{
   
    public static function login($user) {
        $custmodel = (new Cust());
        $cust = $custmodel->where('openid', $user['openId'])->find();

        $custobj = [
            'openid' => $user['openId'],
            'nickname' => Utils::removeEmoji($user['nickName']),
            'avatarimage' => $user['avatarUrl'],
            "accessauth" => md5($user['openId'] . time()),
            "failuretime" => strtotime('+10day'),
        ];

        if (empty($cust)) {
            $custmodel->save($custobj);
            $cust = $custmodel->where('openid', $user['openId'])->find();
        } else {
            $cust->save($custobj);
        }

        return self::getUser($cust['accessauth']);
    }

    public static function getUser($access) {
        $cust = self::getCust($access);
        if (empty($cust)) {
            return null;
        }
        return [
            "accessauth" => $cust['accessauth'],
            "failuretime" => $cust['failuretime'],
            "is_agent" => $cust['is_agent'],
            "is_photoer" => $cust['is_photoer'],
            "is_teacher" => $cust['is_teacher'],
            "is_tg" => $cust['is_tg'],
            "phone" => $cust['phone'],
            "uname" => $cust['uname'],
            "wximg" => $cust['wximg'],
            "logoimage" => $cust['logoimage'],
        ];
    }

    public static function getCust($access) {
        return (new Cust())->where('accessauth', $access)->find();
    }

}
