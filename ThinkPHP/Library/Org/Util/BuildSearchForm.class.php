<?php
namespace Org\Util;
/**
 * 表单生成器，by enhong
 */
class BuildSearchForm {
	protected $option;	//字段选项
	protected $methods=array('text','textarea','hidden','radio','checkbox','editor','date','select','password','file','html','images','verify','images_more','radio_list','checkbox_list','vcode','select_images');
	protected $outhtml=array();	//表单生成后输出
	private $value=null;	//表单值
    /**
     * 架构函数
     * @access public
     * @param string $this->str  数据
     */
    public function __construct($option) {
    	$this->option=$this->parseOption($option);
    }
	
	/**
	* 设置属性
	*/
	public function __set($name,$v){
		return $this->$name=$v;
	}

	/**
	* 获取属性
	*/
	public function __get($name){
		return isset($this->$name)?$this->$name:null;
	}
	
	/**
	* 销毁属性
	*/
    public function __unset($name) {
        unset($this->$name);
    }

    /**
    * 连贯操作的实现
    * @param string $method  方法
    * @param array 参数
    */
    public function __call($method,$args){
    	$method=strtolower($method);
    	if(in_array($method,$this->methods,true)) {
    		$action='_'.$method;
            $this->outhtml[] =   $this->$action($args[0]);
            return $this;
        }else{
        	echo '调用类'.get_class($this).'中的方法'.$method.'()不存在';
        }
    }

    /**
    * 连贯操作后的结果组合
	* @param array $option 字段选项    
    */
    public function create(){
    	$html=@implode('',$this->outhtml);
    	$this->outhtml=array(); 	//销毁之前的内容以便create后重新生成
    	return $html;
    }
	
	/**
	* input
	* @param array $option 字段选项	
	*/
	public function _text($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="text" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control '.$option['class'].'" style="'.$option['style'].'" '.$option['attr'].'>';

		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* radio
	* @param array $option 字段选项	
	*/
	public function _radio($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);

		foreach($option['data'] as $val){
			$html.='<label class="'.$option['class'].' mr20" style="'.$option['style'].'"><input type="radio" class="i-red-square" name="'.$option['name'].'" value="'.$val[$option['field'][0]].'" '.($option['value']==$val[$option['field'][0]]?'checked':'').' '.$option['attr'].'> '.$val[$option['field'][1]].'</label>';
		}

		$html.=$this->_end_html($option);
		return $html;		
	}

	/**
	* checkbox
	* @param array $option 字段选项	
	*/
	public function _checkbox($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);

		if(!empty($option['value']) && !is_array($option['value'])) $option['value']=explode(',',$option['value']);
		if(!empty($option['disable_value']) && !is_array($option['disable_value'])) $option['disable_value']=explode(',',$option['disable_value']);

		if($option['level'] > 1){
			$html.=$this->_checkbox_item($option);
		}else{
			foreach($option['data'] as $val){
				$html.='<label class="'.$option['class'].' mr20" style="'.$option['style'].'"><input type="checkbox" class="i-red-square" name="'.$option['name'].'[]" value="'.$val[$option['field'][0]].'" '.(in_array($val[$option['field'][0]],$option['value'])?'checked':'').' '.$option['attr'].'> '.$val[$option['field'][1]].'</label>';
			}			
		}

		//$html.='<input type="hidden" name="_checkbox_'.$option['name'].'" value="1">';
		$html.=$this->_end_html($option);
		return $html;		
	}

	/**
	* select
	* @param array $option 字段选项	
	*/
	public function _select($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);

		$html.='<select id="'.$option['name'].'" name="'.$option['name'].'" class="form-control '.$option['class'].'" style="'.$option['style'].'" '.$option['attr'].'>';

		if(!empty($option['first'])) $html.='<option value="'.$option['first'][0].'">'.$option['first'][1].'</option>';
		elseif($option['is_first']!==false) $html.='<option value="">请选择'.$option['label'].'</option>';

		//foreach($option['data'] as $val){
			//$html.='<option value="'.$val[$option['field'][0]].'" '.($option['value']==$val[$option['field'][0]]?'selected':'').'> '.$val[$option['field'][1]].'</option>';
		//}

		$html.=$this->_select_item($option['data'],$option['field'],$option['value']);
		$html.='</select>';

		$html.=$this->_end_html($option);
		return $html;		
	}

	/**
	* select_images
	* @param array $option 字段选项	
	*/
	public function _select_images($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);

		//dump($option);
		
		//二维数组
		if($option['level']==2){
			foreach($option['data'] as $key=>$val){
				$option_html.='<li class="us">'.$val['category_name'].'</li>';
				foreach($val[$option['field']['dlist']] as $v){
					$option_item='<div style="float:left"><img src="'.myurl($v[$option['field']['images']],$option['field']['width'],$option['field']['height']).'" alt="'.$v[$option['field']['name']].'"></div><div style="margin-left: '.($option['field']['width']+20).'px;line-height:'.$option['field']['height'].'px;">['.$v[$option['field']['value']].'] '.$v[$option['field']['name']].'</div>';
					if($v[$option['field']['value']]==$option['value']) $selected=$option_item;

					$option_html.='<li onclick="form_select_images($(this))" data-field="#'.$option['name'].'" data-tag=".select_'.$option['name'].'" data-value="'.$v[$option['field']['value']].'">'.$option_item.'</li>';
				}
			}
		}else{	//一维数组

		}

		$html.='<div class="select-images"><div class="select-images-ok select_'.$option['name'].'">'.($selected?$selected:'请选择……').'</div><ul>'.$option_html;
		$html.='</ul></div>';

		$html.='<input type="hidden" id="'.$option['name'].'" name="'.$option['name'].'" value="'.$option['value'].'">';

		$html.=$this->_end_html($option);
		return $html;		
	}
	/**
	* textarea
	* @param array $option 字段选项	
	*/
	public function _textarea($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);

		$html.='<textarea id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" class="form-control '.$option['class'].'" style="'.$option['style'].'" '.$option['attr'].'>'.$option['value'].'</textarea>';

		$html.=$this->_end_html($option);
		return $html;		
	}
	
	/**
	* password
	* @param array $option 字段选项	
	*/
	public function _password($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="password" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control '.$option['class'].'"  style="'.$option['style'].'" '.$option['attr'].'>';

		$html.='<input type="hidden" name="_password_'.$option['name'].'" value="'.$option['value'].'">';
		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* date 时期选择，结合jq日期插件datepicker使用
	* @param array $option 字段选项	
	*/
	public function _date($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="text" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control datepicker '.$option['class'].'"  style="'.$option['style'].'" data-date-format="yyyy-mm-dd" '.$option['attr'].'>';

		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* file
	* @param array $option 字段选项	
	*/
	public function _file($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="file" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control '.$option['class'].'"  style="'.$option['style'].'" '.$option['attr'].'>';

		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* hidden
	* @param array $option 字段选项	
	*/
	public function _hidden($option=null){
		$option=$this->_value($option);
		$html.='<input type="hidden" id="'.$option['name'].'" name="'.$option['name'].'" value="'.$option['value'].'">';

		return $html;	
	}

	/**
	* editor 百度编辑器 $('[data-type="ueditor"]') 去启用编辑器
	* @param array $option 字段选项	
	*/
	public function _editor($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$option['style']=$option['style']?$option['style']:'min-height:400px;';

		//$html.='<textarea id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" class="form-control '.$option['class'].'" style="'.$option['style'].'" data-type="ueditor" '.$option['attr'].'>'.$option['value'].'</textarea>';
		$html.='<script data-type="ueditor" id="'.$option['name'].'" name="'.$option['name'].'" type="text/plain" style="'.$option['style'].'">'.html_entity_decode($option['value']).'</script>';
		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* TAG input 结合JQ TAG插件使用
	* @param array $option 字段选项	
	*/
	public function _tag($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="text" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control tag-input '.$option['class'].'"  style="'.$option['style'].'" '.$option['attr'].'>';

		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* 上传图片
	* @param array $option 字段选项	
	*/
	public function _images($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='
			<div class="btn btn-rad btn-trans btn-primary upload" data-type="upload-images" data-name="'.$option['name'].'" data-label="'.$option['label'].'"><i class="fa fa-cloud-upload"></i> 上传图片</div>
				<ul id="'.$option['name'].'-list" class="images-select-box">';

		if($option['value']){
			$html.='<li id="" data-path="'.$option['value'].'" class="text-center">
				<div class="li-img-box">
					<a class="image-zoom" href="'.$option['value'].'" title="'.$option['value'].'"><img src="'.myurl($option['value'],200).'"></a>
				</div>													
				<div class="delete-images" onclick="$(this).parent(\'li\').remove();$(\'#'.$option['name'].'\').val(\'\');"><div class="selected-icon"><i class="fa fa-times"></i></div></div>												
			</li>';
		}
		$html.='</ul>
				<input type="hidden" id="'.$option['name'].'" name="'.$option['name'].'" value="'.$option['value'].'">';

		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* 验证码
	* @param array $option 字段选项	
	*/
	public function _vcode($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);
		
		$html.='<input type="text" id="'.$option['name'].'" name="'.$option['name'].'" placeholder="'.($option['placeholder']?$option['placeholder']:'请输入'.$option['label']).'" value="'.$option['value'].'" class="form-control '.$option['class'].'"  style="'.$option['style'].'" '.$option['attr'].'>';

		$html.=$this->_end_html($option);
		return $html;	
	}


	/**
	* html
	* @param array $option 字段选项	
	*/
	public function _html($option=null){
		$option=$this->_value($option);
		$html=$this->_prev_html($option);		
		$html.=html_entity_decode($option['html']);
		$html.=$this->_end_html($option);
		return $html;	
	}

	/**
	* 表单前部html
	* @param array $option 字段选项	
	*/
	protected function _prev_html($option=null){
		$option=$this->_value($option);
		$html='<td>';
		return $html;			
	}

	/**
	* 表单后面html
	* @param array $option 字段选项	
	*/
	protected function _end_html($option=null){
		$option=$this->_value($option);

		$html='</td>';
		return $html;			
	}

	/**
	* 处理选项值
	* @param array $option 字段选项	
	*/
	public function _value($option){
		if($this->option[$option['name']]!='') return $this->option[$option['name']];

		$option['value']=$this->value[$option['name']]!=''?$this->value[$option['name']]:$option['value'];
		$this->option[$option['name']]=$option;

		return $option;
	}


	/**
	* 生成无限级 select中的option选项
	* @param array 	$data 	选项数据
	* @param array  $field 选项数据的键值
	* @param integer $level 深度
	*/
	public function _select_item($data,$field,$value='',$level=0){
		$str='';
		if($level>0){
			for($i=0;$i<$level;$i++){
				$str.='　';
			}
			$str.='|— ';
		}
		$level++;
		foreach($data as $val){
			$html.='<option value="'.$val[$field[0]].'" '.(($value==$val[$field[0]] && $value!='')?'selected':'').'>'.$str.$val[$field[1]].'</option>';
			if($val['dlist']) $html.=$this->_select_item($val['dlist'],$field,$value,$level);
		}

		return $html;
	}

	/**
	* 生成二级 checkbox中的选项
	* @param array $option 字段选项
	*/
	public function _checkbox_item($option){

		switch($option['tpl']){
			case 'city':
				$html='<div style="max-height:500px;overflow:auto;padding-right:20px;"><table><thead><tr><th width="180" class="text-center ft16">省份</th><th class="ft16">城市</th></tr></thead><tbody>';
				foreach($option['data'] as $key=>$val){
					$html.='<tr>';
					$html.='<td><label class="'.$option['class'].' mr20" style="'.$option['style'].'"><input type="checkbox" class="i-red-square" name="'.$option['name'].'[]" value="'.$val[$option['field'][0]].'" '.(in_array($val[$option['field'][0]],$option['value'])?'checked':'').' '.$option['attr'].' data-type="select-all" data-tag="#'.$val[$option['field'][0]].'" data-name="'.$val[$option['field'][1]].'" '.(in_array($val[$option['field'][0]],$option['disable_value'])?'disabled':'').'> '.$val[$option['field'][1]].'</label></td>';
					$html.='<td><div class="row" id="'.$val[$option['field'][0]].'">';
					foreach($val['dlist'] as $v){
						$html.='<div class="col-xs-4"><label class="'.$option['class'].' mr20" style="'.$option['style'].'"><input type="checkbox" class="i-red-square" name="'.$option['name'].'[]" value="'.$v[$option['field'][0]].'" '.(in_array($v[$option['field'][0]],$option['value'])?'checked':'').' '.$option['attr'].' '.(in_array($v[$option['field'][0]],$option['disable_value'])?'disabled':'').'> '.$v[$option['field'][1]].'</label></div>';
					}
					$html.='</div></td>';
					$html.='</tr>';
				}
				$html.='</tbody></table></div>';
			break;
			default:
				$html='<ul class="nav nav-tabs border-d">';

				foreach($option['data'] as $key=>$val){
					$html.='<li class="'.($key==0?'active':'').'"><a href="#'.$option['name'].$key.'" data-toggle="tab">'.$val[$option['field'][1]].'</a></li>';
				}
				$html.='</ul><div class="tab-content tab-content-noborder">';
				foreach($option['data'] as $key=>$val){
					$html.='<div class="tab-pane '.($key==0?'active':'').'" id="'.$option['name'].$key.'">';
					foreach($val['dlist'] as $v){
						$html.='<label class="'.$option['class'].' mr20" style="'.$option['style'].'"><input type="checkbox" class="i-red-square" name="'.$option['name'].'[]" value="'.$v[$option['field'][0]].'" '.(in_array($v[$option['field'][0]],$option['value'])?'checked':'').' '.$option['attr'].'> '.$v[$option['field'][1]].'</label>';
					}
					$html.='</div>';
				}
				$html.='</div>';			
			break;
		}


		return $html;
	}

	/**
	* 表单选项参数分解
	* @param array $option 字段选项
	*/
	protected function parseOption($option){
		$option=$this->option;

		return $option;
	}
}
