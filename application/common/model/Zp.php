<?php

namespace app\common\model;

use think\Model;


class Zp extends Model
{

    

    

    // 表名
    protected $name = 'zp';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'is_top_text',
        'check_text'
    ];
    

    
    public function getTypeList()
    {
        return ['zp' => __('Type zp'), 'sp' => __('Type sp'), 'tx' => __('Type tx')];
    }

    public function getIsTopList()
    {
        return ['1' => __('Is_top 1'), '2' => __('Is_top 2')];
    }

    public function getCheckList()
    {
        return ['y' => __('Check y'), 'n' => __('Check n'), 't' => __('Check t')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsTopTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_top']) ? $data['is_top'] : '');
        $list = $this->getIsTopList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCheckTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['check']) ? $data['check'] : '');
        $list = $this->getCheckList();
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
}
