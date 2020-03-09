<?php

namespace app\common\model;

use think\Model;


class Yuyue extends Model
{

    

    

    // 表名
    protected $name = 'yuyue';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function cust()
    {
        return $this->belongsTo('Cust', 'cust', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function styles()
    {
        return $this->belongsTo('Styles', 'style', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
