<?php

namespace app\common\model;

use think\Model;


class StarUpLog extends Model
{

    

    

    // 表名
    protected $name = 'star_up_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['y' => __('Status y'), 'n' => __('Status n')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function cust()
    {
        return $this->belongsTo('Cust', 'cust', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function styles()
    {
        return $this->belongsTo('Styles', 'style', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function starup()
    {
        return $this->belongsTo('StarUp', 'id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
