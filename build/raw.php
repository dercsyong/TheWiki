<?php
	session_start();
	
	if($_GET['w']=='!MyPage'){
		die(header("Location: /settings"));
	}
	
	$w = $_GET['w'];
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
			$w = str_replace($tp[0].":", "", implode(":", $tp));
		}
	}
	
	$_POST = array('namespace'=>$namespace, 'title'=>$w, 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
	if($alpha){
		if($namespace=='12'){
			$namespace = '11';
		} else if($namespace=='13'){
			$namespace = '6';
		}
		$_POST = array('namespace'=>$namespace, 'title'=>$w, 'divide'=>'1', 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
		$namespace = 0;
	}
	define('MODEINCLUDE', true);
	include $_SERVER['DOCUMENT_ROOT'].'/API.php';
	$_POST = null;
	
	if($api_result->status!='success'){
		header("Content-Type: text/html; charset=UTF-8");
		if($api_result->reason=='main db error'){
			die('<script> alert("메인 DB 서버에 접속할 수 없습니다.\\n주요 기능이 동작하지 않습니다."); </script>');
		} else if($api_result->reason=='please check document title'){
			die('<script> alert("누락된 정보가 있습니다."); </script>');
		} else if($api_result->reason=='forbidden'){
			die('<script> alert("권한이 부족합니다."); </script>');
		} else if($api_result->reason=='empty document'){
			$api_result->data = '';
		} else {
			die('<script> alert("API에 문제가 발생했습니다."); </script>');
		}
	}
	
	if($api_result->type=='refresh'){
		header("Content-Type: text/html; charset=UTF-8");
		die('<script> location.href="'.str_replace('/w/', '/raw/', $api_result->link).'"; </script>');
	}
	
	if(defined("isdeleted")){
		header("Content-Type: text/html; charset=UTF-8");
		$api_result->data = '';
	}
	
	if(!empty($api_result->data)){
		header("Content-Type: text/plain; charset=UTF-8");
	}
	
	die('<xmp>'.$api_result->data.'</xmp>');
?>