<?php
/**
 * -------------------------------------------------
 * 买家常用收货地址
 * -------------------------------------------------
 * Create by Lazycat <673090083@qq.com>
 * -------------------------------------------------
 * 2017-01-16
 * -------------------------------------------------
 */
namespace Mobile\Controller;
use Think\Controller;
class ShoppingAddressController extends CommonController {
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        $this->check_logined();
    }

    /**
     * 地址列表
     */
    public function index(){
        //C('DEBUG_API',true);
        $res = $this->doApi2('/ShoppingAddress/address',['openid' => session('user.openid')]);
        $this->assign('pagelist',$res['data']);

        //var_dump($res);

        $this->display();
    }

    public function address_page(){
        $res = $this->doApi2('/ShoppingAddress/address',['openid' => session('user.openid'),'p' => I('get.p')]);
        $this->ajaxReturn($res);
    }

    /**
     * 删除地址
     */
    public function delete(){
        $res = $this->doApi2('/ShoppingAddress/delete',I('post.'));
        $this->ajaxReturn($res);
    }

    /**
     * 新增地址
     */
    public function add(){
        $city = $this->doApi2('/City/city_level');
        $this->assign('city',$city['data']);
        //var_dump($city);

        $this->display();
    }

    public function add_save(){
        $res = $this->doApi2('/ShoppingAddress/add',I('post.'));
        $this->ajaxReturn($res);
    }


    /**
     * 修改地址
     */
    public function edit(){
        $city = $this->doApi2('/City/city_level');
        $this->assign('city',$city['data']);

        $data = ['id' => I('get.id'),'openid' => session('user.openid')];
        $res = $this->doApi2('/ShoppingAddress/view',$data);

        $this->assign('rs',$res['data']);
        $this->display();
    }

    public function edit_save(){
        $res = $this->doApi2('/ShoppingAddress/edit',I('post.'));
        $this->ajaxReturn($res);
    }

    /**
     * 设为默认收货地址
     */
    public function set_default(){
        $res = $this->doApi2('/ShoppingAddress/set_default',I('post.'));
        $this->ajaxReturn($res);
    }
}