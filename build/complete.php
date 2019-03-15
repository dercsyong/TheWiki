<?php
	$namespace = '0';
	if(count(explode(":", $_GET['w']))>1){
		$tp = explode(":", $_GET['w']);
		switch($tp[0]){
			case '틀':
				$namespace = '1';
				break;
			case '분류':
				$namespace = '2';
				break;
			case '파일':
				$namespace = '3';
				break;
			case '사용자':
				$namespace = '4';
				break;
			case '나무위키':
				$namespace = '6';
				break;
			case '휴지통':
				$namespace = '8';
				break;
			case 'TheWiki':
				$namespace = '10';
				break;
			case '이미지':
				$namespace = '11';
				break;
			case '집단창작':
				$alpha = true;
				$namespace = '12';
				break;
			case '알파위키':
				$alpha = true;
				$namespace = '13';
				break;
			default:
				$namespace = '0';
		
		}
		if($namespace>0){
			$_GET['w'] = str_replace($tp[0].":", "", implode(":", $tp));
		}
	}
	
	if($namespace>0){
		$tp = $tp[0].":";
	} else {
		$tp = "";
	}
	
	try{
		$mongo = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
		define('THEWIKI', true);
		include $_SERVER['DOCUMENT_ROOT'].'/config.php';
		if(!$config_db){
			$arr = $mongo->executeQuery('thewiki.docData180326', $query);
		} else {
			$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			
			if($cnt){
				$settings = mysqli_fetch_array($res);
			} else {
				$sql = "SELECT * FROM settings WHERE ip = '0.0.0.0'";
				$res = mysqli_query($config_db, $sql);
				$settings = mysqli_fetch_array($res);
			}
			
			if($settings['docVersion']=='180925'&&$alpha){
				if($namespace=='12'){
					$namespace = '11';
				} else if($namespace=='13'){
					$namespace = '6';
				}
			} else {
				if($namespace=='6'||$namespace=='11'){
					$namespace = 12;
				}
			}
			$query = new MongoDB\Driver\Query(array('namespace' => $namespace, 'title' => array('$regex'=>'^'.$_GET['w'])), array('limit' => 10 ));
			switch($settings['docVersion']){
				case '160229': $arr = $mongo->executeQuery('thewiki.docData160229', $query); break;
				case '160329': $arr = $mongo->executeQuery('thewiki.docData160329', $query); break;
				case '160425': $arr = $mongo->executeQuery('thewiki.docData160425', $query); break;
				case '160530': $arr = $mongo->executeQuery('thewiki.docData160530', $query); break;
				case '160627': $arr = $mongo->executeQuery('thewiki.docData160627', $query); break;
				case '160728': $arr = $mongo->executeQuery('thewiki.docData160728', $query); break;
				case '160829': $arr = $mongo->executeQuery('thewiki.docData160829', $query); break;
				case '161031': $arr = $mongo->executeQuery('thewiki.docData161031', $query); break;
				case '170327': $arr = $mongo->executeQuery('thewiki.docData170327', $query); break;
				case '180925': $arr = $mongo->executeQuery('thewiki.docData180925', $query); break;
				default: $arr = $mongo->executeQuery('thewiki.docData180326', $query); break;
			}
		}
		
		$data = array();
		foreach($arr as $doc){
			$data[] = $tp.$doc->title;
		}
		echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	} catch (MongoDB\Driver\Exception\Exception $e){
		die();
	}
?>
