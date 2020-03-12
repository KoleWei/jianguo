<?php

namespace app\common\model;

use think\Model;


class StarUp extends Model
{

    

    

    // 表名
    protected $name = 'star_up';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'step_text',
        'endtime_text'
    ];
    

    
    public function getStepList()
    {
        return ['1' => __('Step 1'), '2' => __('Step 2')];
    }


    public function getStepTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['step']) ? $data['step'] : '');
        $list = $this->getStepList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function styles()
    {
        return $this->belongsTo('Styles', 'style', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function cust()
    {
        return $this->belongsTo('Cust', 'cust', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
