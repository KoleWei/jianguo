<?php

namespace app\common\model;

use think\Model;


class Cust extends Model
{

    

    

    // 表名
    protected $name = 'cust';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_photoer_text',
        'is_teacher_text',
        'is_agent_text',
        'is_tg_text',
        'failuretime_text'
    ];
    

    
    public function getIsPhotoerList()
    {
        return ['y' => __('Is_photoer y'), 'n' => __('Is_photoer n')];
    }

    public function getIsTeacherList()
    {
        return ['y' => __('Is_teacher y'), 'n' => __('Is_teacher n')];
    }

    public function getIsAgentList()
    {
        return ['y' => __('Is_agent y'), 'n' => __('Is_agent n')];
    }
    public function getIsVipAgentList()
    {
        return ['y' => __('核心经纪人'), 'n' => __('普通经纪人')];
    }

    public function getIsTgList()
    {
        return ['y' => __('Is_tg y'), 'n' => __('Is_tg n')];
    }


    public function getIsPhotoerTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_photoer']) ? $data['is_photoer'] : '');
        $list = $this->getIsPhotoerList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsTeacherTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_teacher']) ? $data['is_teacher'] : '');
        $list = $this->getIsTeacherList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsAgentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_agent']) ? $data['is_agent'] : '');
        $list = $this->getIsAgentList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsTgTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_tg']) ? $data['is_tg'] : '');
        $list = $this->getIsTgList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getFailuretimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['failuretime']) ? $data['failuretime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFailuretimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
