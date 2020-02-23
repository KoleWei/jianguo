<?php

namespace app\wxapi\controller;

use app\common\controller\Api;
use PDO;
use think\Config;
use think\Loader;
use think\Session;

Loader::import('controller/Jump', TRAIT_PATH, EXT);

/**
 * 首页接口
 */
class Configs extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $site = Config::get("site");
        $upload = \app\common\model\Config::upload();

        $modulename = $this->request->module();
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());

        $lang = strip_tags($this->request->langset());

        $config = [
            'updatetime'     => (time() + 24 * 60 * 60) * 1000,
            'site'           => $site,
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang,
            'referer'        => Session::get("referer")
        ];
        $this->success('读取配置', $config);
    }

    /**
     * 读取配置资源
     *
     * @param [type] $key
     * @return void
     */
    public function getconfigassert($key) {
        $site = Config::get("site");
        $assert = $site[$key];
        header("Location: " . $this->request->baseUrl . $assert);
    }
}
