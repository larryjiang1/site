<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Expresscategory119Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='express_category';
	protected $_validate = array(
        array('category_name','require','名称不能为空!',1,'regex',3), 

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>