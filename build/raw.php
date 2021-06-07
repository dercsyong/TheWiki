<?php
	session_start();
	
	define('THEWIKI', true);
	$THEWIKI_FOOTER = 0;
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	$version = $page;
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/TheWiki:%ED%99%88'));
	}
	
	if(!defined('loginUser')){
		header("Content-Type: text/plain; charset=UTF-8");
		die('login required');
	}
	
	if($version>0){
		$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'noredirect'=>true, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'docReVersion'=>$version, 'option'=>'original');
	} else if($version==null){
		$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'noredirect'=>true, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'option'=>'original');
	} else {
		$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'noredirect'=>true, 'divide'=>'1', 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'docVersion'=>$settings['docVersion'], 'option'=>'original');
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
		} else if($api_result->reason=='reversion error'){
			$api_result->data = '';
		} else {
			die('<script> alert("API에 문제가 발생했습니다."); </script>');
		}
	}
	
	if(defined('isdeleted')){
		header("Content-Type: text/html; charset=UTF-8");
		$api_result->data = '';
	}
	
	if(!empty($api_result->data)){
		header("Content-Type: text/plain; charset=UTF-8");
	}
	
	if($_SERVER['HTTP_X_PJAX']){ ?>
		<div class="wiki-article-menu">
			<div class="btn-group" role="group">
				<a class="btn btn-secondary" itemprop="url" href="/w/<?=rawurlencode($THEWIKI_NOW_TITLE_FULL)?>" role="button">문서 보기</a>
			</div>
		</div>
		<h1 class="title">
			<span itemprop="name"><?=$THEWIKI_NOW_TITLE_FULL?> RAW</span> <?php if(!$version){ echo '(r20'.$settings['docVersion'].'판)'; } else { echo '(r'.$version.'판)'; } ?>
		</h1>
		<p class="wiki-edit-date"></p>
		<div class="wiki-content clearfix">
			<div class="wiki-inner-content">
				<hr>
				<xmp><?=$api_result->data?></xmp>
<?php
	} else {
		echo $api_result->data;
	}
?>