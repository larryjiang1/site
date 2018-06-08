<?php
/**
* 此文件为自动生成
*/
namespace Admin\Model;
use Think\Model;
class Goodsattrvalue96Model extends Model {
	//protected $patchValidate = true; //批量验证
	protected $tableName='goods_attr_value';
	protected $_validate = array(
        array('attr_id','require','库存属性ID不能为空!',1,'regex',3), 
        array('goods_id','require','商品ID不能为空!',1,'regex',3), 

	);
	
	protected $_auto = array (
		array('ip','get_client_ip',1,'function'),	
	);
	

}
?>