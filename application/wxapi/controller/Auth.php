<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use app\common\model\Cust;
use app\common\model\Styles;
use app\common\server\CustServer;
use app\wxapi\library\Utils;
use app\wxapi\library\Wx;
use Overtrue\Pinyin\Pinyin;

use think\Config;

/**
 * 首页接口
 */
class Auth extends Api
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     */
    public function login($code, $iv, $encryptedData)
    {
        $user = Wx::login($code, $iv, $encryptedData);

        $cust = CustServer::login($user);
        $this->success('登录成功', $cust);
    }

    // 获取用户
    public function getuser() {
        $user = CustServer::getUser($this->getAccess());
        if (empty($user)) {
            $this->error('获取用户信息为空');
        }
        $this->success('成功获取用户信息', $user);
    }

    // 设置用户
    public function setuser(){
        $param = $this->request->param();
        $cust = (new Cust())
            ->where('id', $this->getCustId())
            ->find();

        $data = [];
        if (!empty($param['is_tg'])) {
            $data['is_tg'] = ($param['is_tg'] == 'y' ? 'y' : 'n');
        }

        if (!empty($param['uname'])) {
            $data['uname'] = $param['uname'];
        }

        if (!empty($param['phone'])) {
            $data['phone'] = $param['phone'];
        }

        if (!empty($param['logoimage'])) {
            $data['logoimage'] = $param['logoimage'];
        }

        if (!empty($param['wximg'])) {
            $data['wximg'] = $param['wximg'];
        }

        $cust->save($data);

        $this->success('设置用户成功');
    }

    /**
     * 获得用户
     */
    public function custlist() {
        $param = $this->request->param();

        $custModle = (new Cust());

        if (!empty($param['role'])) {
            switch($param['role']) {
                case 'photoer':
                    $custModle->where('is_photoer', 'y');
                break;
                case 'teacher':
                    $custModle->where('is_teacher', 'y');
                break;
                case 'agent':
                    $custModle->where('is_agent', 'y');
                break;
            }
        }

        if (!empty($param['search'])) {
            $custModle->where("(uname != '' and uname like '%". $param['search'] ."%') or (uname = '' and  nickname like '%". $param['search'] ."%')");
        }
        
        $list = $custModle->select();
        foreach ($list as $row) {
            $row->visible(['id','nickname','uname', 'phone', 'logoimage', 'wximg', 'avatarimage']);
        }
        $list = collection($list)->toArray();

        $maplist = [];
        $pinyin = (new Pinyin());
        foreach ($list as $row) {
            $zm = strtoupper($pinyin->abbr(empty($row['uname']) ? $row['nickname'] : $row['uname'])[0]);
            if (preg_match('/[A-Z]/', $zm)) {
                if(empty($maplist[$zm])) $maplist[$zm] = [];
                $maplist[$zm][] = $row;
            } else {
                if(empty($maplist['#'])) $maplist['#'] = [];
                $maplist['#'][] = $row;
            }
        }

        $this->success("用户列表", [
            "list" => $maplist
        ]);
    }
}
