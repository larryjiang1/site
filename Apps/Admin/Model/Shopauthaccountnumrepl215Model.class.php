<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Shopauthaccountnumrepl215Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='shop_auth_account_num_repl';
	protected $_validate = array(

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>