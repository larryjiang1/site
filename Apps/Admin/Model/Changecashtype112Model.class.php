<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Changecashtype112Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='change_cash_type';
	protected $_validate = array(
        array('type_name','require','企业类型不能为空!',1,'regex',3), 

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>