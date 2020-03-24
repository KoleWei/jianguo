<?php

namespace app\common\model;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'shoottime_text',
        'endtime_text',
        'shoottime_day',
        'endtime_day',
        'type_text',
        'status_text'
    ];
    

    
    public function getTypeList()
    {
        return ['sp' => __('试拍'), 'ps' => __('拍摄')];
    }

    public function getStatusList()
    {
        return ['1' => __('待接'), '2' => __('洽谈'), '3' => __('进行'), '4' => __('结束'), '5' => __('失败')];
    }


    public function getShoottimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shoottime']) ? $data['shoottime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getShoottimeDayAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shoottime']) ? $data['shoottime'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


    public function getEndtimeDayAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setShoottimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function cust()
    {
        return $this->belongsTo('Cust', 'agent', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function photouser()
    {
        return $this->belongsTo('Cust', 'photoerid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function photoer()
    {
        return $this->belongsTo('Cust', 'photoerid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
