<?php
/**
 *  redisdrive.class.php
 * php redis 操作类
 **/
class RedisDrive{
    //键名
    public $key;
    //值
    public $value;
    //默认生存时间
    public $expire = 60*60; /*60*60*24*/
    //连接是否成功
    public $redis;
    //连接redis服务器ip
    public $ip = '127.0.0.1';
    //端口
    public $port = 6379;
    //密码
    private $password = null;
    //数据库
    public $dbindex = 1;

    /**
     * 自动连接到redis缓存
     */
    public function __construct(){
        //判断php是否支持redis扩展
        if(extension_loaded('redis')){
            //实例化redis
            if($this->redis = new redis()){
                //ping连接
                if(!$this->ping()){
                    $this->redis = false;
                }else{
                    //连接通后的数据库选择和密码验证操作
                    $this->redis -> select($this->dbindex);
                    $this->redis->auth($this->password);
                }
            }else{
                $this->redis = false;
            }
        }else{
            $this->redis = false;
        }
    }

    /**
     * ping redis 的连通性
     */
    public function ping(){
        if($this->redis->connect($this->ip,$this->port)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检测redis键是否存在
     */
    public function exists(){
        if($this->redis->exists($this->key)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取redis键的值
     */
    public function get(){
        if($this->exists()){
            return json_decode($this->redis->get($this->key),true);
        }else{
            return false;
        }
    }

    /**
     * 带生存时间写入key
     */
    public function setex(){
        return $this->redis->setex($this->key,$this->expire,json_encode($this->value));
    }

    /**
     * 设置redis键值
     */
    public function set(){
        if($this->redis->set($this->key,json_encode($this->value))){
            return $this->redis->expire($this->key,$this->expire);
        }else{
            return false;
        }
    }

    /**
     * 获取key生存时间
     */
    public function ttl(){
        return $this->redis->ttl($this->key);
    }

    /**
     *删除key
     */
    public function del(){
        return $this->redis->del($this->key);
    }

    /**
     * 清空所有数据
     */
    public function flushall(){
        return $this->redis->flushall();
    }

    /**
     * 获取所有key
     */
    public function keys(){
        return $this->redis->keys('*');
    }

}