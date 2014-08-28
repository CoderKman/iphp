<?php
function base_path(){
	static $path = false;
	if($path === false){
		$uri = $_SERVER['REQUEST_URI'];
		if(($pos = strpos($uri, '?')) !== false){
			$uri = substr($uri, 0, $pos);
		}
		$uri = secure_path($uri);
		if(preg_match('/^(.*)\/(\d+)$/', $uri, $ms)){
			$uri = $ms[1] . '/view';
			$_GET['id'] = $ms[2];
		}
		$basepath = dirname($_SERVER['SCRIPT_NAME']);
		$path = substr($uri, strlen($basepath));
		$path = trim(trim($path), '/');
	}
	return $path;
}

function secure_path($path){
	$path = preg_replace('/[\.]+/', '.', $path);
	$path = preg_replace('/[\/]+/', '/', $path);
	$path = str_replace('./', '', $path);
	return $path;
}

function ip(){
	static $cip = null;
	if($cip == null){
		if($_SERVER["HTTP_CLIENT_IP"] && $_SERVER["HTTP_CLIENT_IP"]!='unknown'){
			$cip = $_SERVER["HTTP_CLIENT_IP"];
		}else if($_SERVER["HTTP_X_FORWARDED_FOR"] && $_SERVER["HTTP_X_FORWARDED_FOR"]!='unknown'){
			$cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else if($_SERVER["REMOTE_ADDR"] && $_SERVER["REMOTE_ADDR"]!='unknown'){
			$cip = $_SERVER["REMOTE_ADDR"];
		}else{
			$cip = "0.0.0.0";
		}
	}
	return $cip;
}

function _view(){
	foreach(include_paths() as $path){
		// 由 Controller 指定模板的名字
		if(App::$controller->action && App::$controller->action != 'index'){
			$action = App::$controller->action;
		}else{
			$action = $path['action'];
		}
		$file = find_view_file($path['base'], $action);
		if($file){
			break;
		}
	}

	if(!$file){
		$path = base_path();
		Logger::trace("No view for $path!");
		return false;
	}
	Logger::trace("View $file");
	$arr = array();
	foreach(App::$context as $k=>$v){
		$arr[$k] = $v;
	}
	extract($arr);
	include($file);
}

function _widget($name, $params=array()){
	$ps = explode('/', App::$controller->module);
	foreach(App::$controller->view_path as $view_path){
		for($i=count($ps); $i>=0; $i--){
			$dir = join('/', array_slice($ps, 0, $i));
			$dir = APP_PATH . "/$view_path/$dir/";
			$file = $dir . "$name.tpl.php";
			if(file_exists($file)){
				extract($params);
				include($file);
				return;
			}
		}
	}
}

function _redirect($url, $params=array()){
	App::$controller->layout = false;
	App::$finish = true;
	$url = Html::link($url, $params);
	header("Location: $url");
	App::_break();
}

function _url($url='', $params=array()){
	$url = Html::link($url, $params);
	return $url;
}

function _image($url){
	$url = Html::link($url);
	return "<img src=\"$url\" />";
}

// 从数组列表中, 使用 k_attr 和 v_attr 指定的字段, 组成一个关联数组.
function _kvs($arr_arr, $k_attr, $v_attr){
	$kvs = array();
	foreach($arr_arr as $arr){
		if(is_array($arr)){
			$k = $arr[$k_attr];
			$v = $arr[$v_attr];
		}else{
			$k = $arr->$k_attr;
			$v = $arr->$v_attr;
		}
		$kvs[$k] = $v;
	}
	return $kvs;
}

function _render($name){
	App::$controller->action = $name;
}

/**
 * 用于生成指向 module#action 的 URL
 * @param string action 动作的名字
 * @param mixed m Model 对象的实例, 或者是参数数组
 * @param string module 如果不指定, 则为当前的 controller
 */
function _action($action, $m=null, $module=null){
	if(is_array($m)){
		$params = $m;
	}else{
		$params = array();
	}
	if($action == 'view'){
		$action = $m->id;
	}else if($action == 'list'){
		$action = '';
	}else{
		if(is_object($m)){
			$params['id'] = $m->id;
		}
	}
	$mod = $module? $module : App::$controller->module;
	if($action){
		return _url($mod . '/' . $action, $params);
	}else{
		return _url($mod, $params);
	}
}

function _new_url(){
	return _action('new');
}

function _save_url(){
	return _action('save');
}

function _list_url(){
	return _action('list');
}

function _view_url($m){
	return _action('view', $m);
}

function _edit_url($m){
	return _action('edit', $m);
}

function _update_url(){
	return _action('update');
}

function _days_from_now($date){
	return ceil((strtotime($date) - time())/86400);
}

function _days_until_now($date){
	return -_days_from_now($date);
}

function _throw($msg, $code=0){
	throw new Exception($msg, $code);
}