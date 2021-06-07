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
		$settings['enableNotice'] = false;
		$denyLists = array();
		$denyLists[] = getdenyLists('user');
		$denyLists[] = getdenyLists('ip');
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
				mysqli_query($config_db, $sql);
			}
			
			if($_GET['autover']=="180925_alphawiki"){
				$docVersion = 180925;
			} else if(in_array($_GET['autover'], $dumpArray)){
				$docVersion = $_GET['autover'];
			} else {
				$docVersion = $settingsref['docVersion'];
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', enableAds = '1' WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
			mysqli_query($config_db, $sql);
			
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
				mysqli_query($config_db, $sql);
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
			if($_POST['docF']=="on"){
				$docfold = 1;
			} else {
				$docfold = 0;
			}
			if(!$imgAutoLoad||!$docStrikeLine||!$docShowInclude||$docfold){
				$docCache = 0;
			} else {
				if($_POST['docCA']=="on"){
					$docCache = 1;
				} else {
					$docCache = 0;
				}
			}
			if($_POST['showSB']=="on"){
				$showSidebar = 1;
			} else {
				$showSidebar = 0;
			}
			if($docfold){
				$enablePjax = 0;
			} else {
				if($_POST['enablepjax']=="on"){
					$enablePjax = 1;
				} else {
					$enablePjax = 0;
				}
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', docStrikeLine = '$docStrikeLine', imgAutoLoad = '$imgAutoLoad', enableAds = '$enableAds', enableNotice = '$enableNotice', enableViewCount = '$enableViewCount', docShowInclude = '$docShowInclude', docCache = '$docCache', docfold = '$docfold', showSidebar = '$showSidebar', enablePjax = '$enablePjax' WHERE ip = '$_SERVER[HTTP_CF_CONNECTING_IP]'";
			mysqli_query($config_db, $sql);
			
			die(header("Location: /settings"));
		}
	}
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/TheWiki:%ED%99%88'));
	}
	
	if(!empty($_SESSION['THEWIKI_MOVED_DOCUMENT'])){
		$userAlert = '<a href="'.rawurlencode($_SESSION['THEWIKI_MOVED_DOCUMENT']).'?noredirect=1">'.$_SESSION['THEWIKI_MOVED_DOCUMENT'].'</a>에서 넘어왔습니다.';
		$_SESSION['THEWIKI_MOVED_DOCUMENT'] = null;
	}
	if($THEWIKI_MOVED_DOCUMENT){
		$userAlert = '<b>'.$THEWIKI_BEFORE_TITLE_FULL.'</b>에서 이동된 문서입니다.';
	}
	
	if($settings['enableViewCount']){
		$wiki_count = "<script type=\"text/javascript\"> $(document).ready(function(){ $.post(\"/count/".$_SESSION['uuid']."\", {d: \"".$THEWIKI_NOW_TITLE_FULL."\"}, function(Data){ $(\".viewcount\").html('문서 조회수 : '+Data+'회'); }); }); </script><span class='viewcount'>문서 조회수 확인중...</span>";
	} else {
		$wiki_count = "<span>&nbsp;</span>";
	}
	
	$tPost = $_POST;
	$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL);
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
			$forceDocument = '[[TheWiki:관리내역/'.$THEWIKI_NOW_TITLE_FULL.'|더위키]]에서 '.$api_result->expire.'까지 읽기 보호가 설정된 문서입니다.[br]이 문서는 View 권한이 '.$api_result->class.'등급 이상인 운영진만 볼 수 있습니다.';
		} else if($api_result->reason=='empty document'){
			$empty = true;
			$wiki_count = "<span>&nbsp;</span>";
		} else if($api_result->reason=='mongoDB server error'){
			$forceDocument = '{{{+2 mongoDB 서버에 접속할 수 없습니다.[br]설정이 초기화됩니다.}}}{{{#!html <meta http-equiv="Refresh" content="3;url=/settings">}}}';
		} else {
			$forceDocument = '{{{+2 API에 문제가 발생했습니다.}}}';
		}
	}
	
	if(empty($arr['text'])){
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
	
	if($THEWIKI_NAV_ADMIN&&$THEWIKI_NOW_TITLE_REAL!="!MyPage"){
		$THEWIKI_BTN[] = array('/admin/acl///HERE//', 'ACL');
	}
	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
		$THEWIKI_BTN = array();
	} else if($THEWIKI_NOW_TITLE_REAL!="!MyPage"){
		if($THEWIKI_NOW_NAMESPACE==5){
			$THEWIKI_BTN[] = array('/userinfo///HERE///contributions', '문서 기여내역');
			$THEWIKI_BTN[] = array('/userinfo///HERE///discuss', '토론 기여내역');
		} else {
			if($settings['docCache']){
				$THEWIKI_BTN[] = array('/renew///HERE//', '새로고침');
			}
			if($contribution!='기여자 정보가 없습니다'){
				$THEWIKI_BTN[] = array('/contribution///HERE//', '기여자 내역');
			}
			$THEWIKI_BTN[] = array('/backlink///HERE//', '역링크');
		}
		$THEWIKI_BTN[] = array('/history///HERE//', '수정 내역');
		$ipCheck = getViewerCHK($_SERVER['HTTP_CF_CONNECTING_IP']);
		if($ipCheck['edit']){
			$THEWIKI_BTN[] = array('/edit///HERE//', '편집');
		}
		$discussBoldSQL = "SELECT * FROM wiki_discuss_target WHERE namespace = '$THEWIKI_NOW_NAMESPACE' AND title = binary('$THEWIKI_NOW_TITLE_REAL') AND status = '0' LIMIT 1";
		$discussBoldRES = mysqli_query($wiki_db, $discussBoldSQL);
		$discussBoldCHK = mysqlI_fetch_array($discussBoldRES);
		
		if(!empty($discussBoldCHK['topic_title'])){
			$discussBold = true;
		}
		if(!$discussBold){
			$discussBoldSQL = "SELECT * FROM wiki_discuss_delete_target WHERE namespace = '$THEWIKI_NOW_NAMESPACE' AND title = binary('$THEWIKI_NOW_TITLE_REAL') AND status = '0' LIMIT 1";
			$discussBoldRES = mysqli_query($wiki_db, $discussBoldSQL);
			$discussBoldCHK = mysqlI_num_rows($discussBoldRES);
			
			if($discussBoldCHK){
				$discussBold = true;
			}
		}
		$THEWIKI_BTN[] = array('/discuss///HERE///0', '토론');
	}
	
	$CacheCheck = theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], null, null);
	if(!$CacheCheck['status']){
		$CacheCheck['isExpire'] = 1;
		if(defined('isdeleted')){
			$CacheCheck['isExpire'] = 0;
			$arr['text'] = '{{{#!html <hr>이 문서는 삭제되었습니다.<hr><a href="/edit/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'" target="_top">새로운 문서 만들기</a>}}}';
		} else if($THEWIKI_NOW_NAMESPACE==3){
			$empty = false;
			$CacheCheck['isExpire'] = 0;
			$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]".$arr['text'];
		} else if($THEWIKI_NOW_NAMESPACE==11){
			$empty = false;
			$CacheCheck['isExpire'] = 0;
			$arr['text'] = "[[".$THEWIKI_NOW_TITLE_FULL."]]\n".$arr['text'];
		}
		
		// 분류 문서
		if($THEWIKI_NOW_NAMESPACE==2){
			$empty = false;
			if(!$mongo){
				$mongo = mongoDBconnect();
			}
			try{
				$query = array("title"=>"분류:".$THEWIKI_NOW_TITLE_REAL);
				$query = new MongoDB\Driver\Query($query);
				$arr2 = null;
				if($settings['docVersion']==$settingsref['docVersion']){
					$print = $mongo->executeQuery('thewiki.categoryALL', $query);
					foreach($print as $value){
						if(!empty($value->up)){
							$arr2 = "= 상위 분류 =\n";
							foreach($value->up as $topCa){
								$arr2 .= "[[:".$topCa."]]\n";
							}
						}
						if(!empty($value->btm)){
							$arr2 .= "= 하위 분류 =\n";
							foreach($value->btm as $btmCa){
								$arr2 .= "[[:".$btmCa."]]\n";
							}
						}
					}
					$print = $mongo->executeQuery('thewiki.categoryALL', $query);
					foreach($print as $value){
						$arr2 .= "= 포함된 문서 =\n";
						foreach($value->includeDoc as $inDoc){
							$arr2 .= "[[".$inDoc."]]\n";
						}
					}
				} else {
					$print = $mongo->executeQuery('thewiki.category'.$settings['docVersion'], $query);
					foreach($print as $value){
						if(!empty($value->up)){
							$arr2 = "= 상위 분류 =\n";
							foreach($value->up as $topCa){
								$arr2 .= "[[:".$topCa."]]\n";
							}
						}
						if(!empty($value->btm)){
							$arr2 .= "= 하위 분류 =\n";
							foreach($value->btm as $btmCa){
								$arr2 .= "[[:".$btmCa."]]\n";
							}
						}
						$arr2 .= "= 포함된 문서 =\n";
						foreach($value->includeDoc as $inDoc){
							$arr2 .= "[[".$inDoc."]]\n";
						}
					}
				}
			} catch (MongoDB\Driver\Exception\Exception $e){
				$CacheCheck['isExpire'] = 0;
				$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
			}
			if(!$arr2){
				$arr['text'] = '{{{+2 존재하지 않는 분류}}}{{{#!html <hr>}}}이 이름으로 분류된 문서가 없습니다.';
			} else {
				$arr['text'] = $arr2."\n= 분류 설명 =\n".$arr['text'];
			}
		}
		
		if(!empty($forceDocument)){
			$CacheCheck['isExpire'] = 0;
			$arr['text'] = $forceDocument;
		}
		
		if(!empty($arr['text'])){
			if(defined("loginUserAdmin")){
				require_once("/".theMarkBetaPath."/".theMarkBetaName.".php");
			} else {
				require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
			}
			$theMark = new theMark($arr['text']);
			$theMark->pageTitle = $THEWIKI_NOW_TITLE_FULL;
			if($noredirect){
				$theMark->redirect = false;
			}
			if($settings['docfold']){
				$theMark->docfold = true;
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
			$theMark1 = $theMark->toHtml();
			$theMarkRefresh = $theMark->getRefresh();
			$theMark = $theMark1;
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
	
	$title = $THEWIKI_NOW_NAMESPACE==10?'더위키:'.$THEWIKI_NOW_TITLE_REAL:$THEWIKI_NOW_TITLE_FULL;
	include $_SERVER['DOCUMENT_ROOT'].'/layout.php';
	
	if(!$_SERVER['HTTP_X_PJAX']){
		echo $headLayout.$adsenseScript.$parserLayout.$bodyLayout.$siteNotice;
	} else {
		echo $parserLayout;
		$footerLayout = '';
	}
	if($settings['enableNotice']){
		if($settings['enableAds']){
			$userAlert = getTheWikiAdvertise($userAlert);
		}
		echo '<div class="alert alert-info fade in last" id="userDiscussAlert" role="alert">'.$userAlert.'</div>';
	} ?>
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
<?php
	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
		echo $blockScript;
		$arr1 = "{{{+1 차단 해제내역은 기록되지 않으며, 차단내역은 현재 차단된 상태인 경우에만 조회됩니다.[br]그 외 차단내역/해제내역은 [[http://thewiki.kr/request/|기술지원]]을 통해 요청해주세요.}}}[br]\n";
		$price = array();
		foreach ($denyLists as $key => $row){
			$price[$key] = $row['startDate'];
		}
		array_multisort($price, SORT_ASC, $denyLists);
		
		foreach(array_reverse($denyLists) as $key=>$value){
			$get_admin = getAdminCHK($value['from']);
			$arr['text'] .= " * '''".$get_admin['name']." [[내문서:".$value['from']."|".$value['from']."]]''' => ";
			if($value['cidr']){
				$arr['text'] .= " IP ".$value['target']."/".$value['cidr']." ";
				if(!$value['topic']&&!$value['edit']){
					$arr['text'] .= "토론/편집";
				} else if(!$value['edit']){
					$arr['text'] .= "편집";
				} else if(!$value['topic']){
					$arr['text'] .= "토론";
				}
				$arr['text'] .= " 차단 '''(".$value['startDate']." ~ ".$value['endDate'].")'''\n";
			} else {
				$arr['text'] .= " 이용자 [[내문서:".$value['target']."|".$value['target']."]] 차단 '''(".$value['startDate']." ~ ".$value['endDate'].")'''\n";
			}
			preg_match("/#[0-9]+/i", htmlspecialchars($value['reason']), $match);
			$match[0] = str_replace("#", "", $match[0]);
			$value['reason'] = str_replace("#".$match[0], "<a href='/RecentDiscuss?threadNo=".$match[0]."'>#".$match[0]."</a>", $value['reason']);
			
			preg_match("/[0-9]+/i", htmlspecialchars($value['reason']), $match);
			if(substr($match[0], 0, 2)==substr($value['reason'], 0, 2)){
				$value['reason'] = "<a href='/Recent?docuNo=".$match[0]."'>".$match[0]."</a>".substr($value['reason'], strlen($match[0]));
			}
			
			$arr['text'] .= "  * 사유 : ".$value['reason']."\n";
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
		$CacheCheck['isExpire'] = 0;
		$THEWIKI_NOW_NAMESPACE = 10;
	} else if($THEWIKI_NOW_TITLE_REAL=="!MyPage"){
		echo $blockScript;
		if($settings['ip']=="0.0.0.0"){ ?>
			<h4>
				<a href="settingscreate">설정파일 생성</a>이 필요합니다.
			</h4>
<?php	} else { ?>
			<link href="/css/uploadfile.css" rel="stylesheet">
			<script type="text/javascript">
				$(document).ready(function(){
					$("#fileuploader").uploadFile({
						url:"Upload/user.php",
						fileName: "<?=$settings['ip']?>",
						returnType:"json",
						singleFileUploads : true,
						maxFileSize : 10240*1024,
						uploadStr : "업로드",
						doneStr : "완료",
						abortStr : "취소",
						allowedTypes: "jpg, png, gif",
						extErrorStr : ", 다응 확장자만 업로드할 수 있습니다 : "
					});
				});
			</script>
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
							<label class="control-label">공지사항 보이기</label> <label style="font-size:0.8em;">(텍스트 광고 포함)</label>
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
						<?php	if(!$settings['imgAutoLoad']||!$settings['docStrikeLine']||!$settings['docShowInclude']||$settings['docfold']){ ?>
									<input type="hidden" name="docCA" value=""><input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?> disabled> 사용 <small>(일부 기능 변경시 기능 활성화 불가능)</small>
						<?php	} else { ?>
									<input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?>> 사용
						<?php	} ?>
								</label>
							</div>
						</div>
						
						<div class="form-group" id="documentShowInclude">
							<label class="control-label">문단 접기 활성화</label>
							<div class="checkbox">
								<label>
									<input type="checkbox" name="docF" <?php if($settings['docfold']){ echo "checked"; }?>> 사용
								</label>
							</div>
						</div>
						
						<div class="form-group" id="documentShowInclude">
							<label class="control-label">사이드바 활성화</label>
							<div class="checkbox">
								<label>
									<input type="checkbox" name="showSB" <?php if($settings['showSidebar']){ echo "checked"; }?>> 사용
								</label>
							</div>
						</div>
						
						<div class="form-group" id="enablePjax">
							<label class="control-label">pjax 활성화</label>
							<div class="checkbox">
								<label>
						<?php	if($settings['docfold']){ ?>
									<input type="hidden" name="enablepjax" value=""><input type="checkbox" name="enablepjax" <?php if($settings['enablePjax']){ echo "checked"; }?> disabled> 사용 <small>(일부 기능 변경시 기능 활성화 불가능)</small>
						<?php	} else { ?>
									<input type="checkbox" name="enablepjax" <?php if($settings['enablePjax']){ echo "checked"; }?>> 사용
						<?php	} ?>
								</label>
							</div>
						</div>
						
						<div class="form-group" id="documentShowInclude">
							<label class="control-label">프로필 이미지 변경</label>
							<div id="fileuploader">Loading...</div>
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
<?php
		die($footerLayout);
	} // 더위키 설정 페이지
	
	if(!$empty){
		if($CacheCheck['isExpire']){
			theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], $theMark, $theMarkRefresh);
		}
		echo $theMark;
		$trigger = true;
		if($THEWIKI_NOW_NAMESPACE<10&&$THEWIKI_NOW_NAMESPACE!=5){
			if(!$mongo){
				$mongo = mongoDBconnect();
			}
			try{
				$query = new MongoDB\Driver\Query(array('$text'=>array('$search'=>$THEWIKI_NOW_TITLE_FULL)), array('limit'=>5));
				$arr = $mongo->executeQuery('thewiki.docData'.$settingsref['docVersion'], $query);
				$print = array();
				foreach($arr as $doc){
					$trigger = false;
					if($doc->namespace==1||$doc->namespace==6){
						continue;
					}
					if($doc->namespace>0){
						$find = "SELECT * FROM wiki_contents_namespace WHERE code = '$doc->namespace' OR fake = '$doc->namespace'";
						$findres = mysqli_query($wiki_db, $find);
						$findarr = mysqli_fetch_array($findres);
						
						if($findarr){
							$docTitle = $doc->title;
							$doc->title = $findarr[3].":".$doc->title;
							if($findarr[2]==$doc->namespace){
								if($findarr[4]!=$settings['docVersion']){
									$doc->title = $docTitle;
								}
							}
						}
					}
					if($doc->title==$THEWIKI_NOW_TITLE_FULL){
						continue;
					}
					$print[] = $doc->title;
				}
			} catch (MongoDB\Driver\Exception\Exception $e){
				//
			}
			if(!$trigger&&!empty($print)){
				shuffle($print);
				echo '<div class="clearfix"></div><div class="wiki-category"><h2>관련 문서</h2><ul>';
				for($x=0;$x<5;$x++){
					if(empty($print[$x])){
						break;
					}
					echo '<li><a href="/w/'.rawurlencode($print[$x]).'">'.$print[$x].'</a></li> ';
				}
				echo '</ul></div>';
			}
		}
	} else { ?>
		<!-- 구글 검색광고 영역 -->
<?php	$cURLs = "http://ac.search.naver.com/nx/ac?_callback=result&q=".rawurlencode($THEWIKI_NOW_TITLE_FULL)."&q_enc=UTF-8&st=100&frm=nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=1";
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
		die('</div></div>'.$footerLayout);
	} ?>
	</div>
</div>
<?=$footerLayout?>