<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Goodscategory178Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='goods_category';
	protected $_validate = array(
        array('is_zhaoshang','require','是否启用招商不能为空!',1,'regex',3), 
        array('category_name','require','类目名称不能为空!',1,'regex',3), 

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>