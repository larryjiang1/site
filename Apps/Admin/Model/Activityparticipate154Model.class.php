<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Activityparticipate154Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='activity_participate';
	protected $_validate = array(

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>