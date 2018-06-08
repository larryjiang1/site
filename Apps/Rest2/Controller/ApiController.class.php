<?php
/**
 * ----------------------------------------------------------
 * RestFull API V2.0
 * ----------------------------------------------------------
 * 请求统一入口
 * ----------------------------------------------------------
 * Author:Lazycat <673090083@qq.com>
 * ----------------------------------------------------------
 * 2017-01-03
 * ----------------------------------------------------------
 */
namespace Rest2\Controller;
use Think\Controller\RestController;
class ApiController extends CommonController {
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 分页方法
     * Create by Lazycat
     * 2017-04-03
     */
    public function pagelist($options){
        $ac         = $options['do'] ? $options['do'] : 'M';                //实例化
        $pagesize   = $options['pagesize'] ? $options['pagesize'] : 12;     //每页数量
        $order      = $options['order'] ? $options['order'] : 'id desc';    //排序
        $group      = $options['group'] ? $options['group'] : null;         //Group
        $where      = [];
        if($options['where'])   $where = $options['where'];

        $do     = $ac($options['table']);
        $count  = $do->where($where)->group($group)->count();
        if($count > 0) {
            $page = ceil($count / $pagesize);
            $p = $options['p'] ? $options['p'] : 1;
            $list = $do->where($where)->field($options['field'])->group($group)->page($p)->limit($pagesize)->order($order)->select();
        }

        $result['pageinfo'] = ['count' => $count,'p' => $p,'page' => $page,'pagesize' => $pagesize];
        $result['list']     = $list;

        return $result;
    }



}