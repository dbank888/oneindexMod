<?php
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('PRC');
define('TIME', time());
!defined('ROOT') && define('ROOT', str_replace("\\", "/", dirname(__FILE__)) . '/');

//__autoload方法
function i_autoload($className) {
	if (is_int(strripos($className, '..'))) {
		return;
	}
	$file = ROOT . 'lib/' . $className . '.php';
	if (file_exists($file)) {
		include $file;
	}
}
spl_autoload_register('i_autoload');

!defined('FILE_FLAGS') && define('FILE_FLAGS', LOCK_EX);
/**
 * config('name');
 * config('name@file');
 * config('@file');
 */
if (!function_exists('config')) {
	!defined('CONFIG_PATH') && define('CONFIG_PATH', ROOT . 'config/');
	function config($key) {
		static $configs = array();
		list($key, $file) = explode('@', $key, 2);
		$file = empty($file) ? 'base' : $file;

		$file_name = CONFIG_PATH . $file . '.php';
		//读取配置
		if (empty($configs[$file]) AND file_exists($file_name)) {
			$configs[$file] = @include $file_name;
		}

		if (func_num_args() === 2) {
			$value = func_get_arg(1);
			//写入配置
			if (!empty($key)) {
				$configs[$file] = (array) $configs[$file];
				if (is_null($value)) {
					unset($configs[$file][$key]);
				} else {
					$configs[$file][$key] = $value;
				}

			} else {
				if (is_null($value)) {
					return unlink($file_name);
				} else {
					$configs[$file] = $value;
				}

			}
			file_put_contents($file_name, "<?php return " . var_export($configs[$file], true) . ";", FILE_FLAGS);
		} else {
			//返回结果
			if (!empty($key)) {
				return $configs[$file][$key];
			}

			return $configs[$file];
		}
	}
}
/**
 * config('name');
 * config('name@file');
 * config('@file');
 */
if (!function_exists('cache')) {
	!defined('CACHE_PATH') && define('CACHE_PATH', ROOT . 'cache/');
	function cache($key, $value = null) {
		//$file = CACHE_PATH . md5($key) . '.php';
		$redis=new RedisDrive();
        if(!$redis){
            echo 'Redis 服务未连接';
            die();
        }
		$redis->key=md5($key);
		if (is_null($value)) {
            if($redis->exists()){
                return (array)unserialize($redis->get());
            }
            else{
                $redis->value=serialize([time(), $value]);
                $redis->setex();
                return array(TIME, $value);
            }
		} else {
		    $redis->value=serialize([time(), $value]);
		    $redis->setex();
			//file_put_contents($file, "<?php return " . var_export(array(TIME, $value), true) . ";", FILE_FLAGS);
			return array(TIME, $value);
		}
	}
}

//获取token专用函数
if(!function_exists('getToken')){
    function getToken(){
        $redis=new RedisDrive();
        if(!$redis){
            echo 'Redis 服务未连接';
            die();
        }
        $redis->key=md5('token');
        if($redis->exists()){
            $token=(array)unserialize($redis->get());
            if($token['expires_on'] > time()+600){
                return $token;
            }else{
                $refresh_token = config('refresh_token');
                $token = onedrive::get_token($refresh_token);
                if(!empty($token['refresh_token'])) {
                    $token['expires_on'] = time() + $token['expires_in'];
                    $redis->value=serialize($token);
                    $redis->setex();
                    return $token;
                }
            }
        }else{
            $refresh_token = config('refresh_token');
            $token = onedrive::get_token($refresh_token);
            $redis->value=serialize($token);
            $redis->setex();
            return (array)$token;
        }
    }
}




if (!function_exists('db')) {
	function db($table) {
		return db::table($table);
	}
}

if(!function_exists('dd')){
    function dd($dump=null){
        if(!$dump){
            var_dump($dump);
        }
        die();
    }
}

if (!function_exists('view')) {
	function view($file, $set = null) {
		return view::load($file, $set = null);
	}
}

if (!function_exists('_')) {
	function _($str) {
		return htmlspecialchars($str);
	}
}

if (!function_exists('e')) {
	function e($str) {
		echo $str;
	}
}

function get_absolute_path($path) {
    $path = str_replace(array('/', '\\', '//'), '/', $path);
    $parts = array_filter(explode('/', $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    return str_replace('//','/','/'.implode('/', $absolutes).'/');
}

!defined('CONTROLLER_PATH') && define('CONTROLLER_PATH', ROOT.'controller/');
onedrive::$client_id = config('client_id');
onedrive::$client_secret = config('client_secret');
onedrive::$redirect_uri = config('redirect_uri');