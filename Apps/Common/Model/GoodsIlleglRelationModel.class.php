<?php
namespace Common\Model;
use Think\Model\RelationModel;
class GoodsIlleglRelationModel extends RelationModel {
	protected $tableName='goods_illegl';
	protected $_link = array(
			'goods'	=>array(
					'mapping_type'		=>self::HAS_ONE,
					'class_name'		=>'goods',
					'foreign_key'		=>'id',
					'mapping_key'		=>'goods_id',
					'mapping_name'		=>'goods',
					'mapping_fields'	=>'id,goods_name,images,status,price,sale_num',
				),
			'goods_attr_list'	=>array(
					'mapping_type'		=>self::HAS_ONE,
					'class_name'		=>'goods_attr_list',
					'foreign_key'		=>'goods_id',
					'mapping_key'		=>'goods_id',
					'mapping_name'		=>'attr_list',
					'mapping_fields'	=>'id,concat("/Goods/view/id/",id,".html") as detail_url',
				),
		);

}
?>