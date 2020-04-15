<?php
	session_start();
	
	define('THEWIKI', true);
	$THEWIKI_FOOTER = 1;
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	
	if($THEWIKI_NOW_TITLE_FULL=="!MyPage"){
		die(header("Location: /settings"));
	} else if($THEWIKI_NOW_TITLE_FULL=="!DenyUsers"){
		die(header("Location: /DenyUsers"));
	}
	
	if(!empty($_GET['block'])){
		$THEWIKI_NOW_TITLE_FULL = "차단내역 조회";
		$THEWIKI_NOW_TITLE_REAL = "!DenyUsers";
		$settings['enableViewCount'] = 0;
		$settings['docCache'] = 0;
		$denyLists = getdenyLists();
	}
	if(!empty($_GET['settings'])){
		$THEWIKI_NOW_TITLE_FULL = $_SERVER['HTTP_CF_CONNECTING_IP']." 개인 설정";
		$THEWIKI_NOW_TITLE_REAL = "!MyPage";
		$settings['enableViewCount'] = false;
		$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
		if(!empty($_GET['autover'])){
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`) VALUES ";
				$sql .= "('".$_SERVER['HTTP_CF_CONNECTING_IP']."', '$settingsref[docVersion]')";
				mysqli_query($config_db_write, $sql);
			}
			
			if($_GET['autover']=="180925_alphawiki"){
				$docVersion = 180925;
			} else if(in_array($_GET['autover'], $dumpArray)){
				$docVersion = $_GET['autover'];
			} else {
				$docVersion = $settingsref['docVersion'];
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', enableAds = '1' WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
			mysqli_query($config_db_write, $sql);
			
			if(!empty($_SERVER['HTTP_REFERER'])){
				$_SESSION['AUTOVER_APPLY'] = true;
				$_SESSION['AUTOVER_APPLY_VER'] = $docVersion;
				die('<script> location.href = "'.$_SERVER['HTTP_REFERER'].'"; </script>');
			} else {
				die(header("Location: /"));
			}
		}
		if(!empty($_GET['create'])){
			$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`) VALUES ";
				$sql .= "('".$_SERVER['HTTP_CF_CONNECTING_IP']."', '$settingsref[docVersion]')";
				mysqli_query($config_db_write, $sql);
			}
			
			die(header("Location: /settings"));
		}
		if(!empty($_GET['apply'])){
			if($_POST['Notice']=="on"){
				$enableNotice = 1;
			} else {
				$enableNotice = 0;
			}
			if($_POST['docSL']=="on"){
				$docStrikeLine = 1;
			} else {
				$docStrikeLine = 0;
			}
			if($_POST['imgAL']=="on"){
				$imgAutoLoad = 1;
			} else {
				$imgAutoLoad = 0;
			}
			if(in_array($_POST['docVersion'], $dumpArray)){
				$docVersion = $_POST['docVersion'];
			} else {
				$docVersion = $settingsref['docVersion'];
			}
			if($_POST['ViewCount']=="on"){
				$enableViewCount = 1;
			} else {
				$enableViewCount = 0;
			}
			if($_POST['docSI']=="on"){
				$docShowInclude = 1;
			} else {
				$docShowInclude = 0;
			}
			if($docVersion!=$settingsref['docVersion']){
				$enableAds = 1;
			} else {
				if($_POST['Ads']=="on"){
					$enableAds = 1;
				} else {
					$enableAds = 0;
				}
			}
			if(!$imgAutoLoad||!$docStrikeLine||!$docShowInclude){
				$docCache = 0;
			} else {
				if($_POST['docCA']=="on"){
					$docCache = 1;
				} else {
					$docCache = 0;
				}
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', docStrikeLine = '$docStrikeLine', imgAutoLoad = '$imgAutoLoad', enableAds = '$enableAds', enableNotice = '$enableNotice', enableViewCount = '$enableViewCount', docShowInclude = '$docShowInclude', docCache = '$docCache' WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
			mysqli_query($config_db_write, $sql);
			
			die(header("Location: /settings"));
		}
	}
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/TheWiki:%ED%99%88'));
	}
	
	if($THEWIKI_MOVED_DOCUMENT){
		$userAlert = $THEWIKI_BEFORE_TITLE_FULL.'에서 이동된 문서입니다.';
	}
	
	if($settings['enableViewCount']){
		$wiki_count = "<script type=\"text/javascript\"> $(document).ready(function(){ $.get(\"/count/".sha1($THEWIKI_NOW_TITLE_FULL)."\", function(Data){ $(\".viewcount\").html('문서 조회수 : '+Data+'회'); }); }); </script><span class='viewcount'>문서 조회수 확인중...</span>";
	} else {
		$wiki_count = "<span>&nbsp;</span>";
	}
	
	$tPost = $_POST;
	$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'option'=>'original');
	if($settings['docVersion']=='180925'&&!empty($THEWIKI_NOW_NAMESPACE_FAKE)){
		if($THEWIKI_NOW_NAMESPACE_FAKE!=7){
			$_POST['namespace'] = $THEWIKI_NOW_NAMESPACE_FAKE;
			$_POST['divide'] = 1;
		}
	}
	define('MODEINCLUDE', true);
	if($THEWIKI_NOW_TITLE_REAL!='!MyPage'&&$THEWIKI_NOW_TITLE_REAL!='!DenyUsers'){
		include $_SERVER['DOCUMENT_ROOT'].'/API.php';
	} else {
		$api_result->status = 'success';
	}
	$_POST = $tPost;
	$empty = false;
	if($api_result->status!='success'){
		if($api_result->reason=='main db error'){
			$forceDocument = '{{{+2 메인 DB 서버에 접속할 수 없습니다.[br]주요 기능이 동작하지 않습니다.}}}';
		} else if($api_result->reason=='please check document title'){
			$forceDocument = '{{{+2 누락된 정보가 있습니다.}}}';
		} else if($api_result->reason=='forbidden'){
			$settings['enableAds'] = false;
			$settings['enableAdsAdult'] = true;
			$forceDocument = '{{{#!html <a>더위키</a>}}}에서 '.$api_result->expire.'까지 읽기 보호가 설정된 문서입니다.[br]이 문서는 View 권한이 '.$api_result->class.'등급 이상인 운영진만 볼 수 있습니다.';
		} else if($api_result->reason=='empty document'){
			$empty = true;
		} else if($api_result->reason=='mongoDB server error'){
			$forceDocument = '{{{+2 mongoDB 서버에 접속할 수 없습니다.[br]설정이 초기화됩니다.}}}{{{#!html <meta http-equiv="Refresh" content="3;url=/settings">}}}';
		} else {
			$forceDocument = '{{{+2 API에 문제가 발생했습니다.}}}';
		}
	}
	
	if($api_result->type=='refresh'){
		die(header("Location: $api_result->link"));
	} else if(empty($arr['text'])){
		$arr['text'] = $api_result->data;
		$contribution = $api_result->contribution;
		if($contribution==''){
			$contribution = '기여자 정보가 없습니다';
		}
		$AllPage = $api_result->count;
	}
	$THEWIKI_NOW_REV = $api_result->rev;
	unset($api_result);
	
	// 애드센스 정책
	if(count(explode("틀:성적요소", $arr['text']))>1||count(explode("틀:심플/성적요소", $arr['text']))>1){
		$settings['enableAds'] = false;
		$settings['enableAdsAdult'] = true;
	}
	
	if($THEWIKI_NOW_NAMESPACE==5){
		$get_block_arr = getBlockCHK($THEWIKI_NOW_TITLE_REAL);
		$get_admin = getAdminCHK($THEWIKI_NOW_TITLE_REAL);
		
		if($get_block_arr['status']=="success"&&$get_block_arr['result']=="deny"&&$get_block_arr['datas']['endDate']>$date&&$get_block_arr['datas']['startDate']<$date){
			$forceDocument = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>'.$get_block_arr['datas']['endDate'].'까지 차단된 계정입니다.<br>사유 : '.$get_block_arr['datas']['reason'].'</p></div>}}}';
		}
	}
	
	if($THEWIKI_NAV_ADMIN){
		$THEWIKI_BTN[] = array('/admin/acl///HERE//', 'ACL');
	}
	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
		$THEWIKI_BTN = array();
	} else if($THEWIKI_NOW_TITLE_REAL!="!MyPage"){
		if($THEWIKI_NOW_NAMESPACE==5){
			$THEWIKI_BTN[] = array('/userinfo///HERE///contributions', '문서 기여내역');
			$THEWIKI_BTN[] = array('/userinfo///HERE///discuss', '토론 기여내역');
		} else {
			if($contribution!='기여자 정보가 없습니다'){
				$THEWIKI_BTN[] = array('/contribution///HERE//', '기여자 내역');
			}
			$THEWIKI_BTN[] = array('/backlink///HERE//', '역링크');
		}
		$THEWIKI_BTN[] = array('/history///HERE//', '수정 내역');
		if(!empty($_SESSION['name'])){
			$THEWIKI_BTN[] = array('/edit///HERE//', '편집');
		}
		$discussBoldCHK = getDiscussCHK($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL);
		
		if(!empty($discussBoldCHK['topic_title'])){
			$discussBold = true;
		}
		$THEWIKI_BTN[] = array('/discuss///HERE///0', '토론');
	} else {
		$THEWIKI_BTN[] = array('/w/TheWiki:%EC%88%98%EC%9D%B5%EA%B8%88%20%EB%B3%B4%EA%B3%A0%EC%84%9C', '광고 수익금 보고서');
	}
	
	$CacheCheck = theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], null);
	if(!$CacheCheck['status']){
		$needCache = true;
		if(defined('isdeleted')&&$arr['text']==' '){
			$needCache = false;
			$arr['text'] = '{{{#!html <hr>이 문서는 삭제되었습니다.<hr><a href="/edit/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'" target="_top">새로운 문서 만들기</a>}}}';
		} else if($THEWIKI_NOW_NAMESPACE==3){
			$empty = false;
			$needCache = false;
			$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]".$arr['text'];
		} else if($THEWIKI_NOW_NAMESPACE==11){
			$empty = false;
			$needCache = false;
			$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]\n".$arr['text'];
		}
		
		// 분류 문서
		if($THEWIKI_NOW_NAMESPACE==2){
			$empty = false;
			try{
				$mongo2 = new MongoDB\Driver\Manager('mongodb://'.$mongoUser.':'.$mongoPW.'@'.$mongoHost.':27017/thewiki');
				$query = array("title"=>"분류:".$THEWIKI_NOW_TITLE_REAL);
				$query = new MongoDB\Driver\Query($query);
				$arr2 = null;
				if($settings['docVersion']==$settingsref['docVersion']){
					$print = $mongo2->executeQuery('thewiki.category'.$settingsref['docVersion'], $query);
					foreach($print as $value){
						$arr2 = "= 상위 분류 =\n";
						foreach($value->up as $topCa){
							$arr2 .= "[[:".$topCa."]]\n";
						}
						$arr2 .= "= 하위 분류 =\n";
						foreach($value->btm as $btmCa){
							$arr2 .= "[[:".$btmCa."]]\n";
						}
					}
					$print = $mongo2->executeQuery('thewiki.category'.$settings['docVersion'], $query);
					foreach($print as $value){
						$arr2 .= "= 포함된 문서 =\n";
						foreach($value->includeDoc as $inDoc){
							$arr2 .= "[[".$inDoc."]]\n";
						}
					}
				} else {
					$print = $mongo2->executeQuery('thewiki.category'.$settings['docVersion'], $query);
					foreach($print as $value){
						$arr2 = "= 상위 분류 =\n";
						foreach($value->up as $topCa){
							$arr2 .= "[[:".$topCa."]]\n";
						}
						$arr2 .= "= 하위 분류 =\n";
						foreach($value->btm as $btmCa){
							$arr2 .= "[[:".$btmCa."]]\n";
						}
						$arr2 .= "= 포함된 문서 =\n";
						foreach($value->includeDoc as $inDoc){
							$arr2 .= "[[".$inDoc."]]\n";
						}
					}
				}
			} catch (MongoDB\Driver\Exception\Exception $e){
				$needCache = false;
				$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
			}
			if(!$arr2){
				$arr['text'] = '{{{+2 존재하지 않는 분류}}}{{{#!html <hr>}}}이 이름으로 분류된 문서가 없습니다.';
			} else {
				$arr['text'] = $arr2."\n= 분류 설명 =\n".$arr['text'];
			}
		}
		
		if(!empty($forceDocument)){
			$needCache = false;
			$arr['text'] = $forceDocument;
		}
		
		if(!empty($arr['text'])){
			require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
			$theMark = new theMark($arr['text']);
			$theMark->pageTitle = $THEWIKI_NOW_TITLE_FULL;
			if($noredirect){
				$theMark->redirect = false;
			}
			if(!$settings['docStrikeLine']){
				$theMark->strikeLine = true;
			}
			if($settings['imgAutoLoad']=='0'){
				$theMark->imageAsLink = true;
			}
			if($THEWIKI_NOW_NAMESPACE==3||$THEWIKI_NOW_NAMESPACE==11){
				$theMark->imageAsLink = false;
			}
			if(!$settings['docShowInclude']){
				$theMark->included = true;
			}
			$theMark = $theMark->toHtml();
			$theMarkDescription = preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $theMark);
		}
	} else {
		$arr['text'] = $CacheCheck['raw'];
		
		if(!empty($forceDocument)){
			require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
			$theMark = new theMark($forceDocument);
			$arr['text'] = $theMark->toHtml();
		}
		
		$theMark = $arr['text'];
		$theMarkDescription = preg_replace('~<\s*\bscript\b[^>]*>(.*?)<\s*\/\s*script\s*>~is', '', $theMark);
	}
	
	if($_SESSION['AUTOVER_APPLY']){
		$userAlert .= "<hr>덤프 버전을 r20".$_SESSION['AUTOVER_APPLY_VER']."으로 변경했습니다.";
		
		$_SESSION['AUTOVER_APPLY'] = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title><?=$THEWIKI_NOW_NAMESPACE==10?'더위키:'.$THEWIKI_NOW_TITLE_REAL:$THEWIKI_NOW_TITLE_FULL?></title>
		<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"/>
		<meta http-equiv="x-ua-compatible" content="ie=edge"/>
		<meta name="naver-site-verification" content="65bf0fc9bfe222387454ce083d03b9be7eb54808"/>
		<meta name="robots" content="index,follow">
		<meta name="description" content="<?=trim(mb_substr(strip_tags($theMarkDescription), mb_strlen(strip_tags($theMarkDescription), 'utf8')/2, 300, 'utf8'))?>">
		<meta property="og:type" content="website">
		<meta property="og:title" content="더위키 :: <?=$THEWIKI_NOW_TITLE_FULL?>">
		<meta property="og:description" content="<?=trim(mb_substr(strip_tags($theMarkDescription), mb_strlen(strip_tags($theMarkDescription), 'utf8')/2, 300, 'utf8'))?>">
		<meta property="og:url" content="http://thewiki.kr/w/<?=$THEWIKI_NOW_TITLE_FULL?>">
		<link defer rel="stylesheet" href="/namuwiki/css/jquery-ui.min.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/bootstrap.min.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/ionicons.min.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/katex.min.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/flag-icon.min.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/diffview.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/nprogress.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/bootstrap-fix.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/layout.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/wiki.css"/>
		<link defer rel="stylesheet" href="/namuwiki/css/discuss.css"/>
		<!--[if (!IE)|(gt IE 8)]><!-->
		<script type="text/javascript" src="/namuwiki/js/jquery-2.1.4.min.js"></script>
		<!--<![endif]-->
		<!--[if lt IE 9]>
		<script type="text/javascript" src="/namuwiki/js/jquery-1.11.3.min.js?1444428364"></script>
		<script type="text/javascript" src="/namuwiki/js/html5.js?1444428364"></script>
		<script type="text/javascript" src="/namuwiki/js/respond.min.js?1444428364"></script>
		<![endif]-->
		<script type="text/javascript" src="/namuwiki/js/jquery.lazyload.min.js"></script>
		<script type="text/javascript">
			$(function(){
				$('img.lazyimage').lazyload({
					placeholder : 'data:image/gif;base64,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',
					threshold: 0,
					load : function(){
						$(this).attr('src',$(this).attr("data-original"));
					}
				});
			});
		</script>
		<script type="text/javascript" src="/namuwiki/js/jquery-ui.min.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/tether.min.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/bootstrap.min.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/jquery.pjax.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/nprogress.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/dateformatter.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/namu.js"></script>
		<script defer type="text/javascript" src="/namuwiki/js/theseed.js"></script>
		<script defer src="/js/katex.min.js" integrity="sha384-483A6DwYfKeDa0Q52fJmxFXkcPCFfnXMoXblOkJ4JcA8zATN6Tm78UNL72AKk+0O" crossorigin="anonymous"></script>
		<script defer src="/js/auto-render.min.js" integrity="sha384-yACMu8JWxKzSp/C1YV86pzGiQ/l1YUfE8oPuahJQxzehAjEt2GiQuy/BIvl9KyeF" crossorigin="anonymous"></script>
		<!-- Google Analytics -->
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				renderMathInElement(document.body, {
					delimiters: [
						{left: "$$", right: "$$", display: false}
					]
				});
			});
		</script>
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.18.1/styles/default.min.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.18.1/highlight.min.js"></script>
		<script>hljs.initHighlightingOnLoad();</script>
<?php	if(!$settings['enableAdsAdult']&&$settings['enableAds']&&!$empty){ ?>
			<!-- 구글 자동광고 영역 -->
<?php	} ?>
	</head>
	<body class="senkawa hide-sidebar fixed-size fixed-1300">
		<script defer type="text/javascript" src="/namuwiki/js/layout.js?e4665c6b"></script>
		<div class="navbar-wrapper">
			<nav class="navbar navbar-dark bg-inverse navbar-static-top">
				<?=$THEWIKI_NAV?>
			</nav>
		</div>
		<div class="content-wrapper">
			<article class="container-fluid wiki-article">
	<?php	if($settings['enableNotice']){ ?>
				<div class="alert alert-info fade in last" id="userDiscussAlert" role="alert">
					<?=$userAlert?>
				</div>
	<?php	} ?>
				<div class="wiki-article-menu">
					<div class="btn-group" role="group">
		<?php	foreach($THEWIKI_BTN as $c=>$list){
					if($list[1]=="토론"&&$discussBold){
						echo '<a class="btn btn-secondary discuss-bold" itemprop="url" href="'.str_replace('//HERE//', rawurlencode($THEWIKI_NOW_TITLE_FULL), $list[0]).'" role="button">'.$list[1].'</a>';
					} else {
						echo '<a class="btn btn-secondary" itemprop="url" href="'.str_replace('//HERE//', rawurlencode($THEWIKI_NOW_TITLE_FULL), $list[0]).'" role="button">'.$list[1].'</a>';
					}
				} ?>
					</div>
				</div>
				<h1 class="title">
					<span itemprop="name"><?=$THEWIKI_NOW_TITLE_FULL?></span> <?php if(!empty($AllPage)){ echo '(r20'.$settings['docVersion'].'판)'; } if($get_admin['name']!=''){ echo '<span style="font-size:1rem;">('.$get_admin['name'].')</span>'; } ?>
				</h1>
				<p class="wiki-edit-date"><?=$wiki_count?></p>
				<div class="wiki-content clearfix">
					<div class="wiki-inner-content">
			<?php	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
						$arr1 = "{{{+1 차단 해제내역은 기록되지 않으며 IP 차단내역은 [[http://thewiki.kr/request/|기술지원]]을 통해 문의해주시기 바랍니다.}}}[br]\n";
						foreach(array_reverse($denyLists) as $key=>$value){
							$get_admin = getAdminCHK($value['from']);
							$arr['text'] .= " * '''".$get_admin['name']."''' 관리그룹 소속 [[내문서:".$value['from']."|".$value['from']."]]이/가 이용자 [[내문서:".$value['target']."|".$value['target']."]]을/를 '''".$value['startDate']." ~ ".$value['endDate']."''' 기간동안 차단함\n  * ".$value['reason']."\n";
						}
						require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
						$theMark = new theMark($arr1.$arr['text']);
						$theMark->pageTitle = $THEWIKI_NOW_TITLE_FULL;
						if($noredirect){
							$theMark->redirect = false;
						}
						if(!$settings['docStrikeLine']){
							$theMark->strikeLine = true;
						}
						if($settings['imgAutoLoad']=='0'){
							$theMark->imageAsLink = true;
						}
						$theMark = $theMark->toHtml();
						echo $theMark;
						$theMark = null;
						$needCache = false;
					} else if($THEWIKI_NOW_TITLE_REAL=="!MyPage"){
						define('THEWIKI_FOOTER', true);
						$THEWIKI_FOOTER = 0;
						include $_SERVER['DOCUMENT_ROOT'].'/config.php';
						if($settings['ip']=="0.0.0.0"){ ?>
						<h4>
							<a href="settingscreate">설정파일 생성</a>이 필요합니다.
						</h4>
			<?php	} else { ?>
						<form action="settingsapply" method="post" name="settings">
							<section class="tab-content settings-section">
								<div role="tabpanel" class="tab-pane fade in active" id="siteLayout">
									<div class="form-group" id="documentVersion">
										<label class="control-label">덤프 버전</label>
										<select class="form-control setting-item" name="docVersion">
								<?php	foreach($dumpArray as $value){ ?>
											<option value="<?=$value?>" <?php if($settings['docVersion']==$value){ echo 'selected'; } ?>>20<?=$value?><?php if($settingsref['docVersion']==$value){ echo ' (* 권장)'; }?></option>
								<?php	} ?>
										</select>
									</div>
									
									<div class="form-group" id="imagesAutoLoad">
										<label class="control-label">자동으로 이미지 읽기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="imgAL" <?php if($settings['imgAutoLoad']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									<div class="form-group" id="Ads">
										<label class="control-label">광고 보이기</label>
										<div class="checkbox">
											<label>
									<?php	if($settings['docVersion']!=$settingsref['docVersion']){ ?>
												<input type="hidden" name="Ads" value="on"><input type="checkbox" name="Ads" <?php if($settings['enableAds']){ echo "checked"; }?> disabled> 사용 <small>(비권장 덤프를 사용할 경우 기능 비활성화 불가능)</small>
									<?php	} else { ?>
												<input type="checkbox" name="Ads" <?php if($settings['enableAds']){ echo "checked"; }?>> 사용
									<?php	} ?>
											</label>
										</div>
									</div>
									
									<div class="form-group" id="Notice">
										<label class="control-label">공지사항 보이기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="Notice" <?php if($settings['enableNotice']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									
									<div class="form-group" id="ViewCount">
										<label class="control-label">문서 조회수 보이기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="ViewCount" <?php if($settings['enableViewCount']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									
									<div class="form-group" id="documentStrikeLine">
										<label class="control-label">취소선 보이기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="docSL" <?php if($settings['docStrikeLine']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									
									<div class="form-group" id="documentShowInclude">
										<label class="control-label">include된 문서 보이기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="docSI" <?php if($settings['docShowInclude']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									
									<div class="form-group" id="documentShowInclude">
										<label class="control-label">문서 캐싱</label>
										<div class="checkbox">
											<label>
									<?php	if(!$settings['imgAutoLoad']||!$settings['docStrikeLine']||!$settings['docShowInclude']){ ?>
												<input type="hidden" name="docCA" value=""><input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?> disabled> 사용 <small>(일부 기능 변경시 기능 활성화 불가능)</small>
									<?php	} else { ?>
												<input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?>> 사용
									<?php	} ?>
											</label>
										</div>
									</div>
									
									<div class="form-group">
										&nbsp;	<button type="submit" class="btn btn-primary">적용</button>
									</div>
								</div>
							</section>
						</form>
			<?php	} ?>
					</div>
				</div>
				
				<?=$THEWIKI_FOOTER?>
			</article>
		</div>
	</body>
</html>
		<?php	die(); } // 더위키 설정 페이지
	
	if(!$empty){
		if($needCache){
			theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], $theMark);
		}
		echo $theMark;
	} else { ?>
		<!-- 구글 검색광고 영역 -->
<?php
		$cURLs = "http://ac.search.naver.com/nx/ac?_callback=result&q=".rawurlencode($THEWIKI_NOW_TITLE_FULL)."&q_enc=UTF-8&st=100&frm=nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=1";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $cURLs);
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		$result = substr($result, 7, -1);
		$result = json_decode($result)->items[0];
		
		foreach($result as $key=>$value){
			foreach($value as $key2=>$value2){
				$title_list .= "<a href='/w/".$value2."'>".$value2."</a> | ";
			}
		}
		$title_list = "| ".$title_list;
		
		echo '<br><h4>존재하지 않는 문서</h4><hr>1) 이전 덤프버전에 해당 문서가 존재할 수 있습니다. <a href="/settings">설정</a>에서 덤프 버전을 변경해보세요.<br>2) Google 맞춤검색에서 비슷한 문서가 있는지 검색해보세요.<br>3) <a href="/edit/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'" target="_top">새로운 문서</a>를 만들어보세요.';
		if(count($result)){
			echo '<hr><br>이런 문서들이 있을 수 있습니다. 확인해보세요!<br>'.$title_list;
		}
		define('THEWIKI_FOOTER', true);
		$THEWIKI_FOOTER = 0;
		include $_SERVER['DOCUMENT_ROOT'].'/config.php';
		die('</div></div>'.$THEWIKI_FOOTER.'</article></div></body></html>');
	}
?>
					</div>
				</div>
			
				<?=$THEWIKI_FOOTER?>
			</article>
		</div>
	</body>
</html>