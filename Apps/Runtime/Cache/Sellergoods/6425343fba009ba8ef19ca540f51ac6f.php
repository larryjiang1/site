<?php if (!defined('THINK_PATH')) exit();?><link href="/Public/ueditor/themes/default/css/ueditor.min.css" type="text/css" rel="stylesheet">
<script type="text/javascript" charset="utf-8" src="/Public/Jquery/qiniu_ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="/Public/Jquery/qiniu_ueditor/ueditor.all.js"> </script>

<!--建议手动加在语言，避免在ie下有时因为加载语言失败导致编辑器加载失败-->
<!--这里加载的语言文件会覆盖你在配置项目里添加的语言类型，比如你在配置项目里配置的是英文，这里加载的中文，那最后就是中文-->
<script type="text/javascript" charset="utf-8" src="/Public/Jquery/qiniu_ueditor/lang/zh-cn/zh-cn.js"></script>
<script id="<?php echo ($param["name"]); ?>" name="<?php echo ($param["name"]); ?>" type="text/plain" style="width:100%;height:300px;"><?php echo ($param["value"]); ?></script>
<script type="text/javascript">
var toolbars = [
           [
               //'anchor',//锚点
               'source', //源代码
               'undo', //撤销
               'redo', //重做
               'bold', //加粗
               'indent', //首行缩进
               'italic', //斜体
               'underline', //下划线
               'strikethrough', //删除线
               'subscript', //下标
               'fontborder', //字符边框
               'superscript', //上标
               'formatmatch', //格式刷
               'blockquote', //引用
               'selectall', //全选
               'preview', //预览
               'horizontal', //分隔线
               'removeformat', //清除格式
               'time', //时间
               'date', //日期
               'paragraph', //段落格式
               'simpleupload', //单图上传
               'emotion', //表情
               'searchreplace', //查询替换
               'justifyleft', //居左对齐
               'justifyright', //居右对齐
               'justifycenter', //居中对齐
               'justifyjustify', //两端对齐
               'forecolor', //字体颜色
               'backcolor', //背景色
               'fontfamily', //字体
               'fontsize', //字号
               'underline', //下划线
               'strikethrough', //删除线
               'fullscreen', //全屏
           ]
       ]
	var ue = UE.getEditor('<?php echo ($param["name"]); ?>',{toolbars: toolbars,elementPathEnabled:false,wordCount:false});
	//var ue = UE.getEditor('<?php echo ($param["name"]); ?>');
</script>