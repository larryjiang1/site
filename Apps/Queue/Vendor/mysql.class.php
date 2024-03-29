<?php
/**
 * mysql数据库基类
 * Lazycat 整理
 */
class mysql {
    public $link;
    public $dbhost;//数据库主机
    public $dbuser;//数据库用户名
    public $dbpw;//数据库密码
    public $dbcharset;//数据库编码
    public $pconnect;//true表示使用永久连接，false表示不适用永久连接，一般不使用永久连接
    public $tablepre;//数据库表前缀
    public $goneaway;//数据库连接失败，重试次数
    public $query_result;
    //连接数据库
    public function connect($dbhost,$dbuser,$dbpw,$dbname='',$dbcharset='',$pconnect=false,$tablepre='') {
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpw = $dbpw;
        $this->dbname = $dbname;
        $this->dbcharset = $dbcharset;
        $this->pconnect = 1;
        $this->tablepre = $tablepre;
        $this->goneaway = 3;
        if($pconnect){
            if(!$this->link = @mysql_pconnect($dbhost,$dbuser,$dbpw)){
                $this->halt('无法连接到数据库服务器');
            }
        }else{
            if(!$this->link = @mysql_connect($dbhost,$dbuser,$dbpw)){
                $this->halt('无法连接到数据库服务器');
            }
        }
        if($this->version() > '4.1'){
            if($dbcharset){
                mysql_query("SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary",$this->link);
            }
            if($this->version() > '5.0.1'){
                mysql_query("SET sql_mode=''",$this->link);
             }
        }
        if($dbname){
            $this->select_db($dbname);
        }
    }
    //选择数据库
    public function select_db($dbname){
        return mysql_select_db($dbname,$this->link);
    }
    //SQL指令安全过滤
    public function escape_string($str){
        return mysql_escape_string($str);
    }
    //启用事务
    public function startTrans(){
       return $this->query('START TRANSACTION');
    }
    //事务提交
    public function commit(){
        return $this->query('COMMIT');
    }
    //事务回滚
    public function rollback(){
        return $this->query('ROLLBACK');
    }
    //查询sql语句
    public function query($sql){
        if(!($query = mysql_query($sql,$this->link))){
            $this->halt('MySQL Query Error', $sql);
        }
        $this->query_result = $query;
        return $query;
    }
    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有
    public function fetch_array($query,$result_type=MYSQL_ASSOC){
        return mysql_fetch_array($query,$result_type);
    }
    //获取上一次插入的id
    public function insert_id(){
        return ($id = mysql_insert_id($this->link)) >= 0 ? $id : mysql_result($this->query("SELECT last_insert_id()"), 0);
    }
    //取得前一次 MySQL 操作所影响的记录行数
    public function affected_rows(){
        return mysql_affected_rows($this->link);
    }
    //取得结果集中行的数目
    public function num_rows($query){
        $query = mysql_num_rows($query);
        $this->query_result = $query;
        return $query;
    }
    //取得结果集中字段的数目
    public function num_fields($query){
        $this->query_result = $query;
        return mysql_num_fields($query);
    }
    //从结果集中取得列信息并作为对象返回
    public function fetch_fields($query){
        $this->query_result = $query;
        return mysql_fetch_field($query);
    }
    //释放结果内存
    public function free_result(){
        return mysql_free_result($this->query_result);
    }
    //获取错误信息详情
    public function error(){
        return (($this->link) ? mysql_error($this->link) : mysql_error());
    }
    //获取错误代码
    public function errno(){
        return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
    }
    //获取版本号
    public function version(){
        return mysql_get_server_info($this->link);
    }
    //关闭数据库连接
    public function close(){
        return mysql_close($this->link);
    }
    //输出错误信息
    public function halt($message = '',$sql = ''){
        $error = $this->error();
        $errorno = $this->errno();
        if($errorno == 2006 && $this->goneaway-- > 0){
            $this->connect($this->dbhost,$this->dbuser,$this->dbpw,$this->dbname,$this->dbcharset,$this->pconnect,$this->tablepre);
            $this->query($sql);
        }else{
           /*header("Content-Type:text/html; charset=utf-8");
            $str= " <b>出错</b>: $message<br>
                    <b>SQL</b>: $sql<br>
                    <b>错误详情</b>: $error<br>
                    <b>错误代码</b>:$errorno<br>";
            exit($str);*/
            return false;
        }
    }
}
?>