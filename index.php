<?php
	session_start();
	
	if($THEWIKI_NOW_REV_SET!=null&&(int)$THEWIKI_NOW_REV_SET==$THEWIKI_NOW_REV_SET){
		die(header("Location: /rev/".$THEWIKI_NOW_TITLE_FULL."/!".$THEWIKI_NOW_REV_SET));
	}
	
	$userAlert = isUserAlert();
	if($THEWIKI_NOW_TITLE_FULL=="!DenyUsers"){
		$THEWIKI_NOW_TITLE_FULL = "차단내역 조회";
		$THEWIKI_NOW_TITLE_REAL = "!DenyUsers";
		$settings['docCache'] = 0;
		$denyLists = array();
		$denyLists[] = getdenyLists('user');
		$denyLists[] = getdenyLists('ip');
	}
	
	if($THEWIKI_NOW_TITLE_FULL=="!MyPage"){
		$T_SERVER['HTTP_CF_CONNECTING_IP'] = false;
		if(isLogin($_SESSION)['return']){
			$T_SERVER['HTTP_CF_CONNECTING_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
			$_SERVER['HTTP_CF_CONNECTING_IP'] = isLogin($_SESSION)['id'];
		}
		$THEWIKI_NOW_TITLE_FULL = $_SERVER['HTTP_CF_CONNECTING_IP']." 개인 설정";
		$THEWIKI_NOW_TITLE_REAL = "!MyPage";
		$settings = getWikiSettings('all', $_SERVER['HTTP_CF_CONNECTING_IP']);
		if(!empty($_GET['autover'])){
			if(in_array($_GET['autover'], $dumpArray)){
				$docVersion = $_GET['autover'];
			} else {
				$docVersion = $settingsref['docVersion'];
			}
			
			@updateWikiSettings('parser.docVersion', $_SERVER['HTTP_CF_CONNECTING_IP'], $docVersion);
			
			if(!empty($_SERVER['HTTP_REFERER'])){
				$_SESSION['AUTOVER_APPLY'] = true;
				$_SESSION['AUTOVER_APPLY_VER'] = $docVersion;
				die('<script> location.href = "'.$_SERVER['HTTP_REFERER'].'"; </script>');
			} else {
				die(header("Location: /"));
			}
		}
		
		if(!empty($_GET['create'])){
			@updateWikiSettings('skin.delete', $_SERVER['HTTP_CF_CONNECTING_IP']);
			die("ok");
		}
		
		if(!empty($_GET['apply'])){
			if($_POST['checkSave']=="1"){
				@updateWikiSettings('skin.all', $_SERVER['HTTP_CF_CONNECTING_IP'], $_POST);
			}
			@updateWikiSettings('wiki.all', $_SERVER['HTTP_CF_CONNECTING_IP'], $_POST);
			
			die(header("Location: /settings"));
		}
		
		if($T_SERVER['HTTP_CF_CONNECTING_IP']){
			$_SERVER['HTTP_CF_CONNECTING_IP'] = $T_SERVER['HTTP_CF_CONNECTING_IP'];
		}
	}
	
	if(empty($THEWIKI_NOW_TITLE_FULL)||empty($THEWIKI_NOW_TITLE_REAL)){
		die(header('Location: /w/%EB%8D%94%EC%9C%84%ED%82%A4%3A%ED%99%88'));
	}
	
	if(!empty($_SESSION['THEWIKI_MOVED_DOCUMENT'])){
		$userAlert = '<a href="'.rawurlencode($_SESSION['THEWIKI_MOVED_DOCUMENT']).'?noredirect=1">'.$_SESSION['THEWIKI_MOVED_DOCUMENT'].'</a>에서 넘어왔습니다.';
		$_SESSION['THEWIKI_PJAX_MOVED'] = "https://".$_SERVER['HTTP_HOST']."/w/".rawurlencode($THEWIKI_NOW_TITLE_FULL);
		$_SESSION['THEWIKI_MOVED_DOCUMENT'] = null;
	}
	if($THEWIKI_MOVED_DOCUMENT){
		$userAlert = '<b>'.$THEWIKI_BEFORE_TITLE_FULL.'</b>에서 이동된 문서입니다.';
	}
	
	if($THEWIKI_NOW_NAMESPACE==5){
		$return = getUserData(strtolower($THEWIKI_NOW_TITLE_REAL), null, null, 'id');
		if($return['status']=="success"&&$return['name']!=$THEWIKI_NOW_TITLE_REAL){
			die(header("Location: /w/내문서:".$return['name']));
		}
	}
	
	$movedDocu = mongoDBmovedCheck($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL);
	if($movedDocu){
		$empty = true;
	} else {
		$tPost = $_POST;
		$_POST = array('namespace'=>$THEWIKI_NOW_NAMESPACE, 'title'=>$THEWIKI_NOW_TITLE_REAL, 'ip'=>$_SERVER['HTTP_CF_CONNECTING_IP'], 'option'=>'original');
		define('MODEINCLUDE', true);
		if($THEWIKI_NOW_TITLE_REAL!='!MyPage'&&$THEWIKI_NOW_TITLE_REAL!='!DenyUsers'){
			include $_SERVER['DOCUMENT_ROOT'].'/API.php';
		} else {
			$api_result->status = 'success';
		}
		
		$_POST = $tPost;
		$empty = false;
		if($api_result->status!='success'){
			$settings['enableAds'] = false;
			$settings['enableAdsAdult'] = true;
			if($api_result->reason=='main db error'){
				$forceDocument = '{{{+2 메인 DB 서버에 접속할 수 없습니다.[br]주요 기능이 동작하지 않습니다.}}}';
			} else if($api_result->reason=='please check document title'){
				$forceDocument = '{{{+2 누락된 정보가 있습니다.}}}';
			} else if($api_result->reason=='forbidden'){
				/* 권한 부족 문구 */
			} else if($api_result->reason=='empty document'){
				$empty = true;
			} else if($api_result->reason=='mongoDB server error'){
				$forceDocument = '{{{+2 mongoDB 서버에 접속할 수 없습니다.}}}';
			} else {
				$forceDocument = '{{{+2 API에 문제가 발생했습니다.}}}';
			}
		} else {
			$THEWIKI_NOW_EDIT_DATE = $api_result->edit_date;
		}
	}
	
	if(empty($arr['text'])){
		$arr['text'] = $api_result->data;
		$AllPage = $api_result->count;
	}
	$THEWIKI_NOW_REV = $api_result->rev;
	unset($api_result);
	
	if((substr($arr['text'], 0, 9)=="#redirect"||substr($arr['text'], 0, 13)=="#넘겨주기")&&empty($forceDocument)&&!$noredirect){
		$_SESSION['THEWIKI_MOVED_DOCUMENT'] = $THEWIKI_NOW_TITLE_FULL;
		if(substr($arr['text'], 0, 9)=="#redirect"){
			$MOVE_TO = trim(substr($arr['text'], 10));
		} else {
			$MOVE_TO = trim(substr($arr['text'], 14));
		}
		if(count(explode("\n", $MOVE_TO))>1){
			$temp = explode("\n", $MOVE_TO);
			$MOVE_TO = trim($temp[0]);
		}
		if($THEWIKI_NOW_TITLE_FULL!=$MOVE_TO&&$_SESSION['THEWIKI_MOVED_DOCUMENT_CNT']<5){
			$_SESSION['THEWIKI_MOVED_DOCUMENT_CNT']++;
			die(header('Location: /w/'.str_replace("%23s-", "#s-", rawurlencode($MOVE_TO))));
		}
	}
	
	// 애드센스 정책
	if(count(explode("틀:성적요소", $arr['text']))>1||count(explode("틀:심플/성적요소", $arr['text']))>1||count(explode("틀:성인 사이트", $arr['text']))>1||count(explode("틀:체위", $arr['text']))>1){
		$settings['enableAds'] = false;
		$settings['enableAdsAdult'] = true;
		$pageRank = false;
	}
	
	if($THEWIKI_NOW_NAMESPACE==5){
		if($THEWIKI_NOW_TITLE_REAL=="[System]"){
			$forceDocument = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>이 계정은 특수 계정입니다.</p></div>}}}';
			$empty = false;
		}
		
		$get_block_arr = getBlockCHK($THEWIKI_NOW_TITLE_REAL);
		$get_admin = getAdminCHK($THEWIKI_NOW_TITLE_REAL);
		$is_deleted = userCheck($THEWIKI_NOW_TITLE_REAL)['isDeleted'];
		
		if($get_block_arr['status']=="success"&&$get_block_arr['result']=="deny"&&$get_block_arr['datas']['endDate']>$date&&$get_block_arr['datas']['startDate']<$date){
			$forceDocument = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>'.$get_block_arr['datas']['endDate'].'까지 차단된 계정입니다.<br>사유 : '.$get_block_arr['datas']['reason'].'</p></div>}}}';
		} else if($is_deleted){
			$forceDocument = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>이 계정은 탈퇴된 계정입니다.</p></div>}}}';
		}
	}
	
	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
		$THEWIKI_BTN = array();
	} else if($THEWIKI_NOW_TITLE_REAL!="!MyPage"){
		if($THEWIKI_NOW_NAMESPACE==5){
			$THEWIKI_BTN[] = array('/userinfo///HERE///contributions', '문서 기여내역');
			$THEWIKI_BTN[] = array('/userinfo///HERE///discuss', '토론 기여내역');
		} else {
			if($settings['docCache']||(!$settings['docCache']&&$noredirect)){
				$THEWIKI_BTN[] = array('/renew///HERE//', '새로고침');
			}
			$THEWIKI_BTN[] = array('/backlink///HERE//', '역링크');
		}
		$THEWIKI_BTN[] = array('/history///HERE//', '수정 내역');
		$ipCheck = getViewerCHK($_SERVER['HTTP_CF_CONNECTING_IP']);
		if($ipCheck['edit']||!isLogin($_SESSION)){
			$THEWIKI_BTN[] = array('/edit///HERE//', '편집');
		}
		$discussBold = discussBoldChk($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, 'default');
		if(!$discussBold){
			$discussBold = discussBoldChk($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, 'delete');
		}
		$THEWIKI_BTN[] = array('/discuss///HERE///0', '토론');
	}
	
	if($settings['docCache']&&$THEWIKI_NOW_NAMESPACE!=2){
		$CacheCheck = theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], null, null);
	} else {
		$CacheCheck['status'] = false;
	}
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
			$CacheCheck['isExpire'] = 0;
			if($settings['docVersion']!=$settingsref['docVersion']&&$currentDumpText==null){
				$arr['text'] = "{{{+2 권장 덤프버전으로 설정해야 분류 문서를 확인할 수 있습니다.}}}";
			} else {
				if(!$mongoStatus){
					$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
				} else {
					try{
						$query = array("title"=>"분류:".$THEWIKI_NOW_TITLE_REAL);
						$query = new MongoDB\Driver\Query($query, array('maxTimeMS'=>1500));
						$arr2 = null;
						$print = $mongo->executeQuery('db.category', $query);
						foreach($print as $value){
							if(!empty($value->up)){
								$arr2 = "== 상위 분류 ==\n";
								foreach($value->up as $topCa){
									$arr2 .= "[[:".$topCa."]]\n";
								}
							}
							if(!empty($value->btm)){
								$arr2 .= "== 하위 분류 ==\n";
								foreach($value->btm as $btmCa){
									$arr2 .= "[[:".$btmCa."]]\n";
								}
							}
						}
						$print = $mongo->executeQuery('db.category', $query);
						foreach($print as $value){
							$arr2 .= "== 포함된 문서 ==\n";
							foreach($value->includeDoc as $inDoc){
								$arr2 .= "[[".$inDoc."]]\n";
							}
						}
					} catch (MongoDB\Driver\Exception\Exception $e){
						$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
					}
				}
				if(!$arr2){
					$arr['text'] = '{{{+2 존재하지 않는 분류}}}{{{#!html <hr>}}}이 이름으로 분류된 문서가 없습니다.';
				} else {
					$arr['text'] = $arr2."\n== 분류 설명 ==\n".$arr['text'];
				}
			}
		}
		
		if(!empty($forceDocument)){
			$CacheCheck['isExpire'] = 0;
			$arr['text'] = $forceDocument;
		}
		
		if(!empty($arr['text'])){
			if(defined("loginUserAdmin")||defined("joinBetaParser")){
				require_once("/".theMarkBetaPath."/".theMarkBetaName.".php");
			} else {
				require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
			}
			$theMark = new theMark($arr['text']);
			if($THEWIKI_NOW_NAMESPACE==10){
				$theMark->pageTitle = "더위키:".$THEWIKI_NOW_TITLE_REAL;
			} else {
				$theMark->pageTitle = $THEWIKI_NOW_TITLE_FULL;
			}
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
			$theMark1 = $theMark->toHtml();
			$theMarkRefresh = $theMark->getRefresh();
			$theMark = $theMark1;
			
			/* description */
			$theMarkDescription = true;
		}
	} else {
		$arr['text'] = $CacheCheck['raw'];
		if($_SESSION['THEWIKI_MOVED_DOCUMENT_CNT']>0){
			$_SESSION['THEWIKI_MOVED_DOCUMENT_CNT'] = 0;
		}
		if(!empty($forceDocument)){
			require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
			$theMark = new theMark($forceDocument);
			if($THEWIKI_NOW_NAMESPACE==10){
				$theMark->pageTitle = "더위키:".$THEWIKI_NOW_TITLE_REAL;
			} else {
				$theMark->pageTitle = $THEWIKI_NOW_TITLE_FULL;
			}
			$arr['text'] = $theMark->toHtml();
		}
		
		$theMark = $arr['text'];
		$theMark1 = $CacheCheck['description'];
		$theMarkDescription = true;
	}
	
	if($_SESSION['AUTOVER_APPLY']){
		$userAlert .= "덤프 버전을 r20".$_SESSION['AUTOVER_APPLY_VER']."으로 변경했습니다.";
		$_SESSION['AUTOVER_APPLY'] = false;
	}
	
	$title = $THEWIKI_NOW_NAMESPACE==10?'더위키:'.$THEWIKI_NOW_TITLE_REAL:$THEWIKI_NOW_TITLE_FULL;
	include $_SERVER['DOCUMENT_ROOT'].'/layout.php';
	
	if($THEWIKI_NAV_ADMIN&&$THEWIKI_NOW_TITLE_REAL!="!MyPage"&&$THEWIKI_NOW_TITLE_REAL!="!DenyUsers"){
		$THEWIKI_BTN[] = array('/admin/acl///HERE//', 'ACL');
	}
	
	if($THEWIKI_NOW_TITLE_REAL!="!DenyUsers"&&$THEWIKI_NOW_TITLE_REAL!="!MyPage"&&$empty){
		header("HTTP/1.1 404 Not Found");
	}
	
	if(!$_SERVER['HTTP_X_PJAX']){
		echo $headLayout.$parserLayout.$bodyLayout.$siteNotice;
	} else {
		echo $parserLayout;
		$footerLayout = '';
	}
	
	echo getTheWikiNotice($userAlert);
?>
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
	<span itemprop="name"><?=$THEWIKI_NOW_TITLE_FULL?></span> <?php if($get_admin['name']!=''){ echo '<small>('.$get_admin['name'].')</small>'; } ?>
</h1>
<?php
	if(!empty($AllPage)){
		if($currentDumpText!=null){
			$wiki_count = "덤프버전 : r".$currentDumpText;
		} else {
			$wiki_count = "덤프버전 : ".$settings['docVersion_text'];
		}
	} else {
		if($THEWIKI_NOW_EDIT_DATE>0){
			$THEWIKI_NOW_EDIT_DATE = date("Y-m-d H:i:s", $THEWIKI_NOW_EDIT_DATE);
			$wiki_count = "최근 편집일시 : ".$THEWIKI_NOW_EDIT_DATE;
		} else {
			$wiki_count = "&nbsp;";
		}
	}
?>
<p class="wiki-edit-date"><span><?=$wiki_count?></span></p>
<div class="wiki-content clearfix">
	<div class="wiki-inner-content">
<?php
	if($THEWIKI_NOW_TITLE_REAL=="!DenyUsers"){
		echo $blockScript;
		$blocksearch_text = empty($blocksearch_text)?'검색할 내용 입력':$blocksearch_text.' 검색결과';
		echo '<form action="" method="post"><table class="btntable" style="width:100%;"><tr><td style="width:85%;"><input type="text" name="blocksearch_text" class="form-control ipcheck" placeholder="'.$blocksearch_text.'"></td><td style="width:25px;"></td><td><button class="btn btn-block btn-danger" style="width:60px;">검색</button></td></tr></table></form><br>';
		$arr1 = "{{{+1 최근 200건까지만 표시되며, 그 외 내역은 [[https://thewiki.kr/request/|기술지원]]을 통해 요청해주세요.}}}[br]\n";
		$arr['text'] = implode("\n", $denyLists);
		
		require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
		$theMark = new theMark($arr1.$arr['text']);
		$theMark = $theMark->toHtml();
		echo $theMark;
		$theMark = null;
		$CacheCheck['isExpire'] = 0;
		$THEWIKI_NOW_NAMESPACE = 10;
	} else if($THEWIKI_NOW_TITLE_REAL=="!MyPage"){
		// config
		echo $blockScript; ?>
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
				
				function wiki_settings(settings){
					if(settings.darkMode=='forced'||(settings.darkMode=='default'&&window.matchMedia('(prefers-color-scheme: dark)').matches)){
						$(".senkawa").addClass("dark");
					}
					if(settings.darkMode=='default'){
						if(!window.matchMedia('(prefers-color-scheme: dark)').matches){
							$(".senkawa").removeClass("dark");
						}
						window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
						  if(event.matches) {
							$(".senkawa").addClass("dark");
						  } else {
							$(".senkawa").removeClass("dark");
						  }
						})
					}
					if(settings.darkMode=='disabled'){
						$(".senkawa").removeClass("dark");
					}
					
					$(".content-wrapper").css("max-width",settings.articleSize+"px");
					$(".navbar").css("max-width",settings.articleSize+"px");
					
					if(settings.fixNavbar){
						$(".senkawa").addClass("fixNavbar");
					} else {
						$(".senkawa").removeClass("fixNavbar");
					}
					
					if(settings.leftsidebar){
						$(".senkawa").addClass("leftSidebar");
					} else {
						$(".senkawa").removeClass("leftSidebar");
					}
					
					if(settings.hideSidebar){
						$(".senkawa").addClass("hide-sidebar");
					} else {
						$(".senkawa").removeClass("hide-sidebar");
					}
					
					localStorage.setItem("wiki", JSON.stringify(settings));
					return;
				}
				
				let thewiki_settings = localStorage.getItem("wiki");
				let default_thewiki_settings = {'darkMode':'default', 'articleSize':1300, 'fixNavbar':false, 'leftsidebar':false, 'enableCount':true, 'hideStrikeline': false, 'hideSidebar': false};
				
				if(thewiki_settings==null){
					thewiki_settings = default_thewiki_settings;
				} else {
					thewiki_settings = JSON.parse(thewiki_settings);
					var dark2 = "default";
					if(thewiki_settings.darkMode=="forced"){
						dark2 = "forceon";
					} else if(thewiki_settings.darkMode=="disabled"){
						dark2 = "forceoff";
					}
					$("#browserDarkMode > select").val(dark2);
					$("#articleSize > select").val(thewiki_settings.articleSize);
					$("#ViewCount :checkbox").prop("checked",thewiki_settings.enableCount);
					$("#documentStrikeLine :checkbox").prop("checked",!thewiki_settings.hideStrikeline);
					$("#fixNavbar :checkbox").prop("checked",thewiki_settings.fixNavbar);
					$("#leftSidebar :checkbox").prop("checked",thewiki_settings.leftsidebar);
					$("#hideSidebar :checkbox").prop("checked",thewiki_settings.hideSidebar);
				}
				
				let saveStroage;
				if($("#checkSave > select").val()==0){
					$(".hdn").css("display","none");
					saveStroage = true;
					$.get("/settingscreate");
				}
				
				$("#checkSave > select").change(function(){
					if($("#checkSave > select").val()==0){
						$(".hdn").css("display","none");
						saveStroage = true;
						wiki_settings(thewiki_settings);
						$.get("/settingscreate");
					} else {
						$(".hdn").css("display","block");
						saveStroage = false;
						localStorage.removeItem("wiki");
					}
				});
				
				$("#browserDarkMode > select").change(function(){
					if(saveStroage){
						var val = $("#browserDarkMode > select").val();
						if($("#browserDarkMode > select").val()=="forceon"){
							val = "forced";
						} else if($("#browserDarkMode > select").val()=="forceoff"){
							val = "disabled";
						}
						thewiki_settings.darkMode = val;
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#articleSize > select").change(function(){
					if(saveStroage){
						thewiki_settings.articleSize = Number($("#articleSize > select").val());
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#ViewCount :checkbox").change(function(){
					if(saveStroage){
						if(this.checked){
							thewiki_settings.enableCount = true;
						} else {
							thewiki_settings.enableCount = false;
						}
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#documentStrikeLine :checkbox").change(function(){
					if(saveStroage){
						if(this.checked){
							thewiki_settings.hideStrikeline = false;
						} else {
							thewiki_settings.hideStrikeline = true;
						}
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#fixNavbar :checkbox").change(function(){
					if(saveStroage){
						if(this.checked){
							thewiki_settings.fixNavbar = true;
						} else {
							thewiki_settings.fixNavbar = false;
						}
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#leftSidebar :checkbox").change(function(){
					if(saveStroage){
						if(this.checked){
							thewiki_settings.leftsidebar = true;
						} else {
							thewiki_settings.leftsidebar = false;
						}
						wiki_settings(thewiki_settings);
					}
				});
				
				$("#hideSidebar :checkbox").change(function(){
					if(saveStroage){
						if(this.checked){
							thewiki_settings.hideSidebar = true;
						} else {
							thewiki_settings.hideSidebar = false;
						}
						wiki_settings(thewiki_settings);
					}
				});
			});
		</script>
		<form action="settingsapply" method="post" name="settings">
			<section class="tab-content settings-section">
				<div role="tabpanel" class="tab-pane fade in active" id="siteLayout">
					<br><h4>스킨 설정</h4><br>
					<div class="form-group" id="checkSave">
						<label class="control-label">스킨설정 저장위치</label>
						<select class="form-control setting-item" name="checkSave">
							<option value="0" <?php if($settings['checkSave']==null||$settings['checkSave']!=1){ echo 'selected'; } ?>>브라우저</option>
							<option value="1" <?php if($settings['checkSave']==1){ echo 'selected'; } ?>>서버</option>
						</select>
					</div>
					
					<div class="form-group" id="browserDarkMode">
						<label class="control-label">다크모드</label>
						<select class="form-control setting-item" name="enableDM">
							<option value="forceoff" <?php if($settings['enableDarkMode']==-1){ echo 'selected'; } ?>>사용안함</option>
							<option value="default" <?php if($settings['enableDarkMode']==0){ echo 'selected'; } ?>>브라우저 설정에 따름</option>
							<option value="forceon" <?php if($settings['enableDarkMode']==1){ echo 'selected'; } ?>>사용함</option>
						</select>
					</div>
					
					<div class="form-group" id="articleSize">
						<label class="control-label">고정폭</label>
						<select class="form-control setting-item" name="articleSize">
				<?php	$articleSize = array(1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000);
						foreach($articleSize as $value){ ?>
							<option value="<?=$value?>" <?php if($settings['articleSize']==$value){ echo 'selected'; } ?>><?=$value?>px<?php if($settingsref['articleSize']==$value){ echo ' (* 권장)'; }?></option>
				<?php	} ?>
						</select>
					</div>
					
					<div class="form-group" id="documentStrikeLine">
						<label class="control-label">취소선 보이기</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="docSL" <?php if($settings['docStrikeLine']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					
					<div class="form-group" id="fixNavbar">
						<label class="control-label">상단바 고정</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="fixNavbar" <?php if($settings['fixNavbar']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					
					<div class="form-group" id="leftSidebar">
						<label class="control-label">왼쪽 사이드바</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="leftSidebar" <?php if($settings['leftSidebar']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					
					<div class="form-group" id="hideSidebar">
						<label class="control-label">사이드바 숨기기</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="hideSidebar" <?php if($settings['hideSidebar']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					
					<div class="form-group hdn">
						&nbsp;	<button type="submit" class="btn btn-primary">적용</button>
					</div>
					<hr><br><h4>엔진/파서 설정 <small>(엔진/파서설정은 서버에 저장됩니다)</small></h4><br>
					
					<div class="form-group" id="documentVersion">
						<label class="control-label">덤프 버전</label>
						<select class="form-control setting-item" name="docVersion">
				<?php	foreach($dumpArray as $value){ ?>
							<option value="<?=$value?>" <?php if($settings['docVersion']==$value){ echo 'selected'; } ?>>20<?=$value?><?php if($settingsref['docVersion']==$value){ echo ' (* 권장)'; }?></option>
				<?php	} ?>
						</select>
					</div>
					
					<div class="form-group" id="imagesAutoLoad">
						<label class="control-label">이미지 표시하기</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="imgAL" <?php if($settings['imgAutoLoad']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					<div class="form-group" id="Ads">
						<label class="control-label">광고 보이기</label>
						<div class="checkbox">
							<label style="width:100%;">
					<?php	if($settings['docVersion']!=$settingsref['docVersion']){ ?>
								<input type="hidden" name="Ads" value="on"><input type="checkbox" name="Ads" <?php if($settings['enableAds']){ echo "checked"; }?> disabled> 사용 <small>(비권장 덤프를 사용할 경우 기능 비활성화 불가능)</small>
					<?php	} else { ?>
								<input type="checkbox" name="Ads" <?php if($settings['enableAds']){ echo "checked"; }?>> 사용
					<?php	} ?>
							</label>
						</div>
					</div>
					
					<div class="form-group" id="documentCache">
						<label class="control-label">문서 캐싱</label>
						<div class="checkbox">
							<label style="width:100%;">
					<?php	if(!$settings['imgAutoLoad']||!$settings['docStrikeLine']||!$settings['docShowInclude']||$settings['docfold']){ ?>
								<input type="hidden" name="docCA" value=""><input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?> disabled> 사용 <small>(일부 기능 변경시 기능 활성화 불가능)</small>
					<?php	} else { ?>
								<input type="checkbox" name="docCA" <?php if($settings['docCache']){ echo "checked"; }?>> 사용
					<?php	} ?>
							</label>
						</div>
					</div>
					
					<div class="form-group" id="documentFold">
						<label class="control-label">문단 접기 활성화</label>
						<div class="checkbox">
							<label style="width:100%;">
								<input type="checkbox" name="docF" <?php if($settings['docfold']){ echo "checked"; }?>> 사용
							</label>
						</div>
					</div>
					
					<div class="form-group" id="enablePjax">
						<label class="control-label">pjax 활성화</label>
						<div class="checkbox">
							<label style="width:100%;">
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
	</div>
</div>
<?php
		die($footerLayout);
	} // 더위키 설정 페이지
	
	if(!$empty){
		if(!$settings['docCache']){
			$CacheCheck['isExpire'] = false;
		}
		if($CacheCheck['isExpire']){
			@theWikiCache($THEWIKI_NOW_NAMESPACE, $THEWIKI_NOW_TITLE_REAL, $THEWIKI_NOW_REV, $settings['docVersion'], $theMark, $theMarkRefresh, $theMark1);
		}
		echo $theMark;
		$trigger = true;
		if($THEWIKI_NOW_NAMESPACE<10&&$THEWIKI_NOW_NAMESPACE!=5){
			$print = randomdocu(5);
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
		
		echo '<br><h4>존재하지 않는 문서</h4><hr>1) 다른 문서로 문서가 이동되었을 수 있습니다. <a href="/renew/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'">문서 새로고침</a>으로 문서가 이동되었는지 확인해보세요.<br>2) Google 맞춤검색에서 비슷한 문서가 있는지 검색해보세요.<br>3) <a href="/edit/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'" target="_top">새로운 문서</a>를 만들어보세요.';
		if($THEWIKI_NOW_NAMESPACE==0||$THEWIKI_NOW_NAMESPACE==1){
			echo '<br>4) 나무위키에서 해당 문서를 가져오도록 <a href="/fork/'.rawurlencode($THEWIKI_NOW_TITLE_FULL).'">포크 요청</a> 해보세요.';
		}
		if(count($result)){
			echo '<hr><br>이런 문서들이 있을 수 있습니다. 확인해보세요!<br>'.$title_list;
		}
		die('</div></div>'.$footerLayout);
	} ?>
	</div>
</div>
<script type="text/javascript">
	$(".wiki-inner-content").ready(function(){
		if($("body").hasClass('dark')){
			$("[data-dark]").each(function(){
				$(this).css("color", $(this).attr("data-dark"));
			});
			$("[data-dark_bg]").each(function(){
				$(this).css("background-color", $(this).attr("data-dark_bg"));
			});
			$("[data-dark_br]").each(function(){
				$(this).css("border-color", $(this).attr("data-dark_br"));
			});
		}
	});
</script>
<?=$footerLayout?>