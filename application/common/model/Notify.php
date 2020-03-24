<?php

namespace app\common\model;

use think\Model;


class Notify extends Model
{

    

    

    // 表名
    protected $name = 'notify';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'action_text',
        'type_text',
        'createtime_text',
    ];
    

    
    public function getActionList()
    {
        return ['sys' => __('Action sys'), 'mg' => __('Action mg')];
    }

    public function getTypeList()
    {
        return ['1' => __('作品通知'), '2' => __('订单通知'), '3' => __('通知')];
    }


    public function getActionTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['action']) ? $data['action'] : '');
        $list = $this->getActionList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data) {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value; 
    }




    public function cust()
    {
        return $this->belongsTo('Cust', 'cust', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
