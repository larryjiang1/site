<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Swoolequeue183Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='swoole_queue';
	protected $_validate = array(
        array('worker_id','require','所属worker不能为空!',1,'regex',3), 

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>