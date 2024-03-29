<?php
	session_start();
	
	define('THEWIKI', true);
	$THEWIKI_FOOTER = 0;
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	$version = $page;
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/%EB%8D%94%EC%9C%84%ED%82%A4%3A%ED%99%88'));
	}
	
	$movedDocu = mongoDBmovedCheck($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL);
	if($movedDocu){
		$api_result->data = '';
	} else {
		if($version>0){
			$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'noredirect'=>true, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'docReVersion'=>$version, 'option'=>'original');
		} else if($version==null){
			$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'noredirect'=>true, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'option'=>'original');
		} else {
			$now = getDocumentNow($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL);
			if(gettype($now['data']->originDocuN)!="NULL"&&$now['data']->originDocuT!=null){
				$dumpNamespace = $now['data']->originDocuN;
				$dumpTitle = $now['data']->originDocuT;
			} else {
				$dumpNamespace = $THEWIKI_NOW_NAMESPACE;
				$dumpTitle = $THEWIKI_NOW_TITLE_REAL;
			}
			
			$_POST = array('namespace'=>$dumpNamespace, 'title'=>$dumpTitle, 'noredirect'=>true, 'divide'=>'1', 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'docVersion'=>$settings['docVersion'], 'option'=>'original');
		}
		
		define('MODEINCLUDE', true);
		include $_SERVER['DOCUMENT_ROOT'].'/API.php';
		$_POST = null;
		
		if($api_result->status!='success'){
			header("Content-Type: text/html; charset=UTF-8");
			if($api_result->reason=='main db error'){
				die('<script> alert("메인 DB 서버에 접속할 수 없습니다.\\n주요 기능이 동작하지 않습니다."); </script>');
			} else if($api_result->reason=='please check document title'){
				die('<script> alert("누락된 정보가 있습니다."); history.go(-1); </script>');
			} else if($api_result->reason=='forbidden'){
				die('<script> alert("권한이 부족합니다."); history.go(-1); </script>');
			} else if($api_result->reason=='empty document'){
				$api_result->data = '';
			} else if($api_result->reason=='reversion error'){
				if($api_result->isDump){
					die('<script> alert("잘못된 버전입니다. 덤프데이터는 r0 판으로 조회해야 합니다."); history.go(-1); </script>');
				} else {
					$api_result->data = '';
				}
			} else {
				die('<script> alert("API에 문제가 발생했습니다."); </script>');
			}
		}
		
		if($THEWIKI_NOW_NAMESPACE==5){
			if(isDenyUser($THEWIKI_NOW_TITLE_REAL)){
				$api_result->data = '';
			}
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