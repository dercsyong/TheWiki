<?php
	session_start();
	
	define('THEWIKI', true);
	$THEWIKI_FOOTER = 1;
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	
	if($THEWIKI_NOW_TITLE_FULL=="!MyPage"){
		die(header("Location: /settings"));
	}
	
	if(!empty($_GET['settings'])){
		$THEWIKI_NOW_TITLE_FULL = $_SERVER['REMOTE_ADDR']." 개인 설정";
		$THEWIKI_NOW_TITLE_REAL = "!MyPage";
		$settings['enableViewCount'] = false;
		$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[REMOTE_ADDR]'";
		if(!empty($_GET['autover'])){
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`) VALUES ";
				$sql .= "('".$_SERVER['REMOTE_ADDR']."', '$settingsref[docVersion]')";
				mysqli_query($config_db, $sql);
			}
			
			$enableAds = 1;
			switch($_GET['autover']){
				case '180925_alphawiki':
					$docVersion = 180925;
					break;
				case '180326': case '170327': case '161031': case '160829': case '160728': case '160627': case '160530': case '160425': case '160329': case '160229': case '160126': case '151130': case '151108': case '150928': case '150831': case '150728': case '150629':
					$docVersion = $_GET['autover'];
					break;
				default:
					$docVersion = $settingsref['docVersion'];
					break;
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', enableAds = '1' WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			mysqli_query($config_db, $sql);
			
			die(header("Location: /"));
		}
		if(!empty($_GET['create'])){
			$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`) VALUES ";
				$sql .= "('".$_SERVER['REMOTE_ADDR']."', '$settingsref[docVersion]')";
				mysqli_query($config_db, $sql);
			}
			
			die(header("Location: /settings"));
		}
		if(!empty($_GET['apply'])){
			switch($_POST['Ads']){
				case 'on':
					$enableAds = 1;
					break;
				default:
					$enableAds = 0;
			}
			switch($_POST['Notice']){
				case 'on':
					$enableNotice = 1;
					break;
				default:
					$enableNotice = 0;
			}
			switch($_POST['docSL']){
				case 'on':
					$docStrikeLine = 1;
					break;
				default:
					$docStrikeLine = 0;
			}
			switch($_POST['imgAL']){
				case 'on':
					$imgAutoLoad = 1;
					break;
				default:
					$imgAutoLoad = 0;
			}
			
			switch($_POST['docVersion']){
				case '180925': case '180326': case '170327': case '161031': case '160829': case '160728': case '160627': case '160530': case '160425': case '160329': case '160229': case '160126': case '151130': case '151108': case '150928': case '150831': case '150728': case '150629':
					$docVersion = $_POST['docVersion'];
					$enableAds = 1;
					break;
				default:
					$docVersion = $settingsref['docVersion'];
					break;
			}
			switch($_POST['ViewCount']){
				case 'on':
					$enableViewCount = 1;
					break;
				default:
					$enableViewCount = 0;
			}
			switch($_POST['docSI']){
				case 'on':
					$docShowInclude = 1;
					break;
				default:
					$docShowInclude = 0;
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', docStrikeLine = '$docStrikeLine', imgAutoLoad = '$imgAutoLoad', enableAds = '$enableAds', enableNotice = '$enableNotice', enableViewCount = '$enableViewCount', docShowInclude = '$docShowInclude' WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			mysqli_query($config_db, $sql);
			
			die(header("Location: /settings"));
		}
	}
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/TheWiki:%ED%99%88'));
	}
	
	if($settings['docVersion']!=$settingsref['docVersion']){
		$userAlert = "현재 ".$settings['docVersion']."버전 덤프를 사용중이며, 로딩이 불안정할 수 있습니다. &nbsp; &nbsp; =><a href='/w/?settings=1&autover=".$settingsref['docVersion']."'>안정 버전 사용</a>";
	} else {
		$userAlert = '궁금하신 점이 있으신가요? <a href="/request/">기술지원</a>을 요청해보세요.';
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
	$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
	if($settings['docVersion']=='180925'&&!empty($THEWIKI_NOW_NAMESPACE_FAKE)){
		if($THEWIKI_NOW_NAMESPACE_FAKE!=7){
			$_POST['namespace'] = $THEWIKI_NOW_NAMESPACE_FAKE;
			$_POST['divide'] = 1;
		}
	}
	define('MODEINCLUDE', true);
	if($THEWIKI_NOW_TITLE_REAL!='!MyPage'){
		include $_SERVER['DOCUMENT_ROOT'].'/API.php';
	} else {
		$api_result->status = 'success';
	}
	$_POST = $tPost;
	
	if($api_result->status!='success'){
		if($api_result->reason=='main db error'){
			$arr['text'] = '{{{+2 메인 DB 서버에 접속할 수 없습니다.[br]주요 기능이 동작하지 않습니다.}}}';
		} else if($api_result->reason=='please check document title'){
			$arr['text'] = '{{{+2 누락된 정보가 있습니다.}}}';
		} else if($api_result->reason=='forbidden'){
			$forceDocument = '{{{#!html <a>더위키</a>}}}에서 '.$api_result->expire.'까지 읽기 보호가 설정된 문서입니다.[br]이 문서는 View 권한이 '.$api_result->class.'등급 이상인 운영진만 볼 수 있습니다.';
		} else if($api_result->reason=='empty document'){
			$empty = true;
		} else if($api_result->reason=='mongoDB server error'){
			$arr['text'] = '{{{+2 mongoDB 서버에 접속할 수 없습니다.[br]설정이 초기화됩니다.}}}{{{#!html <meta http-equiv="Refresh" content="3;url=/settings">}}}';
		} else {
			$arr['text'] = '{{{+2 API에 문제가 발생했습니다.}}}';
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
	unset($api_result);
	
	// 애드센스 정책
	if(count(explode("틀:성적요소", $arr['text']))>1){
		$settings['enableAds'] = false;
		$settings['enableAdsAdult'] = true;
	}
	
	if($THEWIKI_NOW_NAMESPACE==5){
		$get_block_arr = getBlockCHK($get_admin[1]);
		$get_admin = getAdminCHK($get_admin[1]);
		
		if($get_block_arr['expire']>$date){
			$arr['text'] = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>'.$get_block_arr['expire'].'까지 차단된 계정입니다.<br>사유 : '.$get_block_arr['title'].'</p></div>}}}';
		}
	}
	
	if($THEWIKI_NAV_ADMIN){
		$THEWIKI_BTN[] = array('/admin/acl///HERE//', 'ACL');
	}
	if($THEWIKI_NOW_TITLE_REAL!="!MyPage"){
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
			$THEWIKI_BTN[] = array('/discuss///HERE///0', '토론');
		}
	} else {
		$THEWIKI_BTN[] = array('/w/TheWiki:%EC%88%98%EC%9D%B5%EA%B8%88%20%EB%B3%B4%EA%B3%A0%EC%84%9C', '광고 수익금 보고서');
	}
	
	if(defined('isdeleted')&&$arr['text']==' '){
		$arr['text'] = '{{{#!html <hr>이 문서는 삭제되었습니다.<hr><a href="/edit/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'" target="_top">새로운 문서 만들기</a>}}}';
	}
	
	if($THEWIKI_NOW_NAMESPACE==3){
		$empty = false;
		$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]".$arr['text'];
	}
	if($THEWIKI_NOW_NAMESPACE==11){
		$empty = false;
		if(file_exists($_SERVER['DOCUMENT_ROOT']."/customupload/".$THEWIKI_NOW_TITLE_REAL)){
			$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]\n".$arr['text'];
		} else {
			$arr['text'] = "저장된 이미지가 아닙니다.";
		}
	}
	
	// 분류 문서
	if($THEWIKI_NOW_NAMESPACE==2){
		try{
			$mongo2 = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
			$query = array("title"=>"분류:".$THEWIKI_NOW_TITLE_REAL);
			$query = new MongoDB\Driver\Query($query);
			if($settings['docVersion']==$settingsref['docVersion']){
				$print = $mongo2->executeQuery('nisdisk.category'.$settingsref['docVersion'], $query);
				//print_r($print);
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
				$print = $mongo2->executeQuery('nisdisk.category'.$settings['docVersion'], $query);
				foreach($print as $value){
					$arr2 .= "= 포함된 문서 =\n";
					foreach($value->includeDoc as $inDoc){
						$arr2 .= "[[".$inDoc."]]\n";
					}
				}
			} else {
				$print = $mongo2->executeQuery('nisdisk.category'.$settings['docVersion'], $query);
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
			$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
		}
		
		$arr['text'] = $arr2."\n= 분류 설명 =\n".$arr['text'];
	}
	
	if(!empty($forceDocument)){
		$arr['text'] = $forceDocument;
	}
	
	if(!empty($arr['text'])){
		require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
		$theMark = new theMark($arr['text']);
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
		
		$theMarkDescription = new theMark($arr['text']);
		if(!$settings['docStrikeLine']){
			$theMarkDescription->strikeLine = true;
		}
		if($settings['imgAutoLoad']=='0'){
			$theMarkDescription->imageAsLink = true;
		}
		if($THEWIKI_NOW_NAMESPACE==3||$THEWIKI_NOW_NAMESPACE==11){
			$theMarkDescription->imageAsLink = false;
		}
		if(!$settings['docShowInclude']){
			$theMarkDescription->included = true;
		}
		$theMarkDescription->alltext = true;
		$theMarkDescription = $theMarkDescription->toHtml();
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
		<meta property="og:url" content="http://thewiki.ga/w/<?=$THEWIKI_NOW_TITLE_FULL?>">
		<link rel="stylesheet" href="/namuwiki/css/jquery-ui.min.css"/>
		<link rel="stylesheet" href="/namuwiki/css/bootstrap.min.css"/>
		<link rel="stylesheet" href="/namuwiki/css/ionicons.min.css"/>
		<link rel="stylesheet" href="/namuwiki/css/katex.min.css"/>
		<link rel="stylesheet" href="/namuwiki/css/flag-icon.min.css"/>
		<link rel="stylesheet" href="/namuwiki/css/diffview.css"/>
		<link rel="stylesheet" href="/namuwiki/css/nprogress.css"/>
		<link rel="stylesheet" href="/namuwiki/css/bootstrap-fix.css"/>
		<link rel="stylesheet" href="/namuwiki/css/layout.css"/>
		<link rel="stylesheet" href="/namuwiki/css/wiki.css"/>
		<link rel="stylesheet" href="/namuwiki/css/discuss.css"/>
		<link rel="stylesheet" href="/namuwiki/css/dark.css"/>
		<!--[if (!IE)|(gt IE 8)]><!-->
		<script type="text/javascript" src="/namuwiki/js/jquery-2.1.4.min.js"></script>
		<!--<![endif]-->
		<!--[if lt IE 9]>
		<script type="text/javascript" src="/namuwiki/js/jquery-1.11.3.min.js?1444428364"></script>
		<script type="text/javascript" src="/namuwiki/js/html5.js?1444428364"></script>
		<script type="text/javascript" src="/namuwiki/js/respond.min.js?1444428364"></script>
		<![endif]-->
		<script type="text/javascript" src="/namuwiki/js/jquery.lazyload.min.js"></script>
		<script type="text/javascript" src="/namuwiki/js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="/namuwiki/js/tether.min.js"></script>
		<script type="text/javascript" src="/namuwiki/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="/namuwiki/js/jquery.pjax.js"></script>
		<script type="text/javascript" src="/namuwiki/js/nprogress.js"></script>
		<script type="text/javascript" src="/namuwiki/js/dateformatter.js"></script>
		<script type="text/javascript" src="/namuwiki/js/namu.js"></script>
		<script type="text/javascript" src="/namuwiki/js/wiki.js"></script>
		<script type="text/javascript" src="/namuwiki/js/edit.js"></script>
		<script type="text/javascript" src="/namuwiki/js/discuss.js"></script>
		<script type="text/javascript" src="/namuwiki/js/theseed.js"></script>
		<script src="/js/katex.min.js" integrity="sha384-483A6DwYfKeDa0Q52fJmxFXkcPCFfnXMoXblOkJ4JcA8zATN6Tm78UNL72AKk+0O" crossorigin="anonymous"></script>
		<script src="/js/auto-render.min.js" integrity="sha384-yACMu8JWxKzSp/C1YV86pzGiQ/l1YUfE8oPuahJQxzehAjEt2GiQuy/BIvl9KyeF" crossorigin="anonymous"></script>
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				renderMathInElement(document.body, {
					delimiters: [
						{left: "$$", right: "$$", display: false}
					]
				});
			});
		</script>
	</head>
	<body class="senkawa hide-sidebar fixed-size fixed-1300">
		<script type="text/javascript" src="/namuwiki/js/layout.js?e4665c6b"></script>
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
	<?php	}
			if(!$settings['enableAdsAdult']){ ?>
				<!-- 구글 자동광고 영역 -->
	<?php	}
			if($settings['enableAds']){ ?>
				<p>
					<!-- 구글 일반광고 영역 -->
				</p>
	<?php	} ?>	
				<div class="wiki-article-menu">
					<div class="btn-group" role="group">
		<?php	foreach($THEWIKI_BTN as $c=>$list){
					echo '<a class="btn btn-secondary" itemprop="url" href="'.str_replace('//HERE//', rawurlencode($THEWIKI_NOW_TITLE_FULL), $list[0]).'" role="button">'.$list[1].'</a>';
				} ?>
					</div>
				</div>
				<h1 class="title">
					<span itemprop="name"><?=$THEWIKI_NOW_TITLE_FULL?></span> <?php if(!empty($AllPage)){ echo '(r20'.$settings['docVersion'].'판)'; } if($get_admin['name']!=''){ echo '<span style="font-size:1rem;">('.$get_admin['name'].')</span>'; } ?>
				</h1>
				<p class="wiki-edit-date"><?=$wiki_count?></p>
				<div class="wiki-content clearfix">
					<div class="wiki-inner-content">
			<?php	if($THEWIKI_NOW_TITLE_REAL=="!MyPage"){
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
											<option value="190312" <?php if($settings['docVersion']=="190312"){ echo 'selected'; } ?>>20190312 (* 권장)</option>
											<option value="180925_alphawiki" <?php if($settings['docVersion']=="180925"){ echo 'selected'; } ?>>20180925_alphawiki</option>
											<option value="180326" <?php if($settings['docVersion']=="180326"){ echo 'selected'; } ?>>20180326</option>
											<option value="170327" <?php if($settings['docVersion']=="170327"){ echo 'selected'; } ?>>20170327</option>
											<option value="161031" <?php if($settings['docVersion']=="161031"){ echo 'selected'; } ?>>20161031</option>
											<option value="160829" <?php if($settings['docVersion']=="160829"){ echo 'selected'; } ?>>20160829</option>
											<option value="160728" <?php if($settings['docVersion']=="160728"){ echo 'selected'; } ?>>20160728</option>
											<option value="160627" <?php if($settings['docVersion']=="160627"){ echo 'selected'; } ?>>20160627</option>
											<option value="160530" <?php if($settings['docVersion']=="160530"){ echo 'selected'; } ?>>20160530</option>
											<option value="160425" <?php if($settings['docVersion']=="160425"){ echo 'selected'; } ?>>20160425</option>
											<option value="160329" <?php if($settings['docVersion']=="160329"){ echo 'selected'; } ?>>20160329</option>
											<option value="160229" <?php if($settings['docVersion']=="160229"){ echo 'selected'; } ?>>20160229</option>
										</select>
									</div>
									
									<div class="form-group" id="imagesAutoLoad">
										<label class="control-label">자동으로 이미지 읽기</label>
										<div class="checkbox">
											<label>
												<input type="checkbox" name="imgAL" id="needads" <?php if($settings['imgAutoLoad']){ echo "checked"; }?>> 사용
											</label>
										</div>
									</div>
									<div class="form-group" id="Ads">
										<label class="control-label">광고 보이기</label>
										<div class="checkbox">
											<label>
									<?php	if($settings['docVersion']!=$settingsref['docVersion']){ ?>
												<input type="hidden" name="Ads" value="on"><input type="checkbox" name="Ads" id="ads" <?php if($settings['enableAds']){ echo "checked"; }?> disabled> 사용 <small>(비권장 덤프를 사용할 경우 기능 비활성화 불가능)</small>
									<?php	} else { ?>
												<input type="checkbox" name="Ads" id="ads" onclick="if(!document.settings.ads.checked){ alert('더위키는 광고 수익금으로 운영됩니다.\n광고가 너무 거슬린다면 기술지원을 통해 피드백을 부탁드립니다.'); }" <?php if($settings['enableAds']){ echo "checked"; }?>> 사용
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
	ob_flush(); flush();
	
	if(!$empty){
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