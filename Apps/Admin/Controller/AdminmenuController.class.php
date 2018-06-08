<?php
/**
* 此文件为根据表单模板创建
*/
namespace Admin\Controller;
use Think\Controller;
class AdminmenuController extends CommonModulesController {
	protected $name 			='菜单管理';	//控制器名称
    protected $formtpl_id		=59;			//表单模板ID
    protected $fcfg				=array();		//表单配置
    protected $do 				='';			//实例化模型
    protected $map				=array();		//where查询条件

    /**
    * 初始化
    */
    public function _initialize() {
    	parent::_initialize();

    	//初始化表单模板
    	$this->_initform();

    	//dump($this->fcfg);

    }

    /**
    * 列表
    */
    public function index(){
    	$this->_category();
		$this->display();
    }

    /**
    * 添加记录
    */
    public function add(){
    	$this->display();
    }
	
	/**
	* 保存新增记录
	*/
	public function add_save(){
		$result=$this->_add_save();

		$this->ajaxReturn($result);
	}

	/**
	* 修改记录
	*/
	public function edit(){
		$this->_edit();
		$this->display();
	}

	/**
	* 保存修改记录
	*/
	public function edit_save(){
		$result=$this->_edit_save();

		$this->ajaxReturn($result);
	}

	/**
	* 删除选中记录
	*/
	public function delete_select(){
		$result=$this->_delete_select();
		$this->ajaxReturn($result);
	}

	/**
	* 批量更改状态
	*/
	public function active_change_select(){
		$result=$this->_active_change_select();

		$this->ajaxReturn($result);		
	}

	/**
	* 排序
	*/
	public function setsort(){
		$result=$this->_setsort();
		$this->ajaxReturn($result);
	}

	/**
	* 类目转移
	*/
	public function sid_change_select(){
		$result=$this->_sid_change_select();
		$this->ajaxReturn($result);
	}
}