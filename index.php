<?php
	session_start();
	
	if(substr($_GET['w'], -10)=="?skin=dark"){
		header('HTTP/1.0 403 Forbidden');
		die('403 forbidden');
	}
	
	if(substr($_GET['w'], -13)=="?noredirect=1"){
		$_GET['w'] = str_replace('?noredirect=1', '', $_GET['w']);
		$noredirect = true;
	}
	
	if($_GET['w']=="!MyPage"){
		die(header("Location: /settings"));
	}
	
	if($_GET['settings']!=''){
		$_GET['w'] = "!MyPage";
		$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[REMOTE_ADDR]'";
		if($_GET['autover']!=""){
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`, `docStrikeLine`, `imgAutoLoad`) VALUES ";
				$sql .= "('".$_SERVER['REMOTE_ADDR']."', '$settingsref[docVersion]', '1', '1')";
				mysqli_query($config_db, $sql);
			}
			
			$imgAutoLoad = 0;
			$enableAds = 1;
			switch($_GET['autover']){
				case '180925_alphawiki':
					$docVersion = 180925;
					break;
				case '180326': case '170327': case '161031': case '160829': case '160728': case '160627': case '160530': case '160425': case '160329': case '160229':
					$docVersion = $_GET['autover'];
					break;
				default:
					$imgAutoLoad = 1;
					$docVersion = $settingsref['docVersion'];
					break;
			}
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', docStrikeLine = '1', imgAutoLoad = '1', enableAds = '1', enableViewCount = '1', enableNotice = '1' WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			mysqli_query($config_db, $sql);
			
			die(header("Location: /w/TheWiki:홈"));
		}
		if($_GET['create']!=""){
			$sql = "SELECT * FROM settings WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if(!$cnt){
				$sql = "INSERT INTO settings(`ip`, `docVersion`, `docStrikeLine`, `imgAutoLoad`) VALUES ";
				$sql .= "('".$_SERVER['REMOTE_ADDR']."', '$settingsref[docVersion]', '1', '1')";
				mysqli_query($config_db, $sql);
			}
			
			die(header("Location: /settings"));
		}
		if($_GET['apply']!=""){
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
				case '180925_alphawiki':
					$docVersion = 180925;
					$imgAutoLoad = 0;
					$enableAds = 1;
					break;
				case '180326': case '170327': case '161031': case '160829': case '160728': case '160627': case '160530': case '160425': case '160329': case '160229':
					$docVersion = $_POST['docVersion'];
					$imgAutoLoad = 0;
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
			
			$sql = "UPDATE settings SET docVersion = '$docVersion', docStrikeLine = '$docStrikeLine', imgAutoLoad = '$imgAutoLoad', enableAds = '$enableAds', enableNotice = '$enableNotice', enableViewCount = '$enableViewCount' WHERE ip = '$_SERVER[REMOTE_ADDR]'";
			mysqli_query($config_db, $sql);
			
			die(header("Location: /settings"));
		}
	}
	
	if($_GET['w']=="!ADReport"){
		$settings['docVersion'] = $settingsref['docVersion'];
	}
	if($_GET['w']==''){
		die(header('Location: /w/TheWiki:홈'));
	}
	$w = $_GET['w'];
	$queueV2 = true;
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
			$queueV2 = false;
			$w = str_replace($tp[0].":", "", implode(":", $tp));
		}
	}
	
	if($namespace==1){
		$tw = explode(',', str_replace('_(SSS)_', '#', $w));
		$w = $tw[0];
		foreach($tw as $key => $val){
			if($key>0){
				$twval = explode('=', $val);
				$override[trim($twval[0])] = trim(str_replace($twval[0].'=', '', $val));
			}
		}
	}
	
	if($_GET['raw']==''){
		$wiki_count = sha1($_GET['w']);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title><?=$_GET['w']?></title>
		<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"/>
		<meta http-equiv="x-ua-compatible" content="ie=edge"/>
		<meta name="naver-site-verification" content="65bf0fc9bfe222387454ce083d03b9be7eb54808"/>
		<meta name="robots" content="index,follow">
		<meta name="description" content="<?=$_GET['w']?> 문서">
		<meta property="og:type" content="website">
		<meta property="og:title" content="<?=$_GET['w']?> - The Wiki">
		<meta property="og:description" content="<?=$_GET['w']?> 문서">
		<meta property="og:url" content="http://thewiki.ga/w/<?=$_GET['w']?>">
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
		<script type="text/javascript">
			$(document).ready(function(){
<?php		if($settings['docVersion']!=$settingsref['docVersion']){ ?>
				$("#userDiscussAlert").html('현재 <?=$settings['docVersion']?>버전 덤프를 사용중이며, 로딩이 불안정할 수 있습니다. &nbsp; &nbsp; =><a href="/w/?settings=1&autover=<?=$settingsref['docVersion']?>">안정 버전 사용</a>');
<?php		} else { ?>
				$("#userDiscussAlert").html('궁금하신 점이 있으신가요? <a href="/request/">기술지원</a>을 요청해보세요.');
<?php		} ?>
				var addque = true;
				function urlencode(str) {
					str = (str + '').toString();
					return encodeURIComponent(str)
						.replace('%2F', '/')
						.replace(/!/g, '%21')
						.replace(/'/g, '%27')
						.replace(/\(/g, '%28')
						.replace(/\)/g, '%29')
						.replace(/\*/g, '%2A')
						.replace(/%20/g, '+');
				}
				
				$("#addque").click(function(){
					if(addque){
						addque = false;
						$.get("/queue/"+urlencode('<?=$_GET['w']?>'), function(Data){
							Data = JSON.parse(Data);
							if(Data.result.status=="success"){
								$("#userDiscussAlert").html('대기열에 추가했습니다. 곧 문서가 갱신됩니다.');
								alert('대기열에 추가했습니다. 곧 문서가 갱신됩니다.');
							} else {
								$("#userDiscussAlert").html('대기열에 추가하지 못했습니다. 같은 문제가 지속되면 <a href="/request/">기술지원</a>을 요청해보세요.');
								alert('대기열에 추가하지 못했습니다.');
								addque = true;
							}
						});
					}
				});
				
				$("#addquev2").click(function(){
					if(addque){
						addque = false;
						$.get("/queuev2/"+urlencode('<?=$_GET['w']?>'), function(Data){
							Data = JSON.parse(Data);
							if(Data.result.status=="success"){
								$("#addquev2").css('display','none');
								$("#userDiscussAlert").html('대기열에 추가했습니다. 곧 문서가 갱신됩니다.');
								alert('대기열에 추가했습니다. 곧 문서가 갱신됩니다.');
							} else {
								$("#userDiscussAlert").html('대기열에 추가하지 못했습니다. 같은 문제가 지속되면 <a href="/request/">기술지원</a>을 요청해보세요.');
								alert('대기열에 추가하지 못했습니다.');
								addque = true;
							}
						});
					}
				});
			});
		</script>
<?php if($settings['enableViewCount']){ ?>
		<script type="text/javascript">
			$(document).ready(function(){
				$.get("/count/<?=$wiki_count?>", function(Data){
					$(".viewcount").html('문서 조회수 : '+Data+'회');
				});
			});
		</script>
<?php	} ?>
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
<?php if($settings['enableNotice']){ ?>
				<div class="alert alert-info fade in last" id="userDiscussAlert" role="alert">
					Loading... <?=$_GET['w']?>
				</div>
<?php	}
	}
	
	if($namespace==""){
		$namespace = 0;
	}
	$tPost = $_POST;
	$_POST = array('namespace'=>$namespace, 'title'=>$w, 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
	if($settings['docVersion']=='180925'&&$alpha){
		if($namespace=='12'){
			$namespace = '11';
		} else if($namespace=='13'){
			$namespace = '6';
		}
		$_POST = array('namespace'=>$namespace, 'title'=>$w, 'divide'=>'1', 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
		$namespace = 0;
	}
	
	define('MODEINCLUDE', true);
	if($_GET['w']!='!MyPage'){
		include $_SERVER['DOCUMENT_ROOT'].'/API.php';
	} else {
		$api_result->status = 'success';
	}
	$_POST = $tPost;
	
	if($api_result->status!='success'){
		if($api_result->reason=='main db error'){
			die('<h2>메인 DB 서버에 접속할 수 없습니다.<br>주요 기능이 동작하지 않습니다.</h2>');
		} else if($api_result->reason=='please check document title'){
			die('<h2>누락된 정보가 있습니다."</h2>');
		} else if($api_result->reason=='forbidden'){ ?>
				<h1 class="title">
					<a href="#" data-npjax="true"><span itemprop="name"><?=$_GET['w']?></span></a>
				</h1>
				<p class="wiki-edit-date"><?=$wiki_count?></p>
				<div class="wiki-content clearfix">
					<div class="wiki-inner-content">
					<a href="#">The Wiki</a>에서 읽기 보호가 설정된 문서입니다.
					</div>
				</div>
				<?=$THEWIKI_FOOTER?>
			</article>
		</div>
	</body>
</html>
<?php		die();
		} else if($api_result->reason=='empty document'){
			// ok
		} else if($api_result->reason=='mongoDB server error'){
			die('<h2>mongoDB 서버에 접속할 수 없습니다.<br>설정이 초기화됩니다.</h2><meta http-equiv="Refresh" content="3;url=/settings">');
		} else {
			die('<h2>API에 문제가 발생했습니다.</h2>');
		}
	}
	
	if($api_result->type=='refresh'){
		die('<script> location.href="'.$api_result->link.'"; </script>');
	} else {
		$arr['text'] = $api_result->data;
		$contribution = $api_result->contribution;
		if($contribution==''){
			$contribution = '기여자 정보가 없습니다';
		}
		$AllPage = $api_result->count;
		unset($api_result);
	}
	
	if($settings['enableViewCount']){
		$wiki_count = "<span class='viewcount'>문서 조회수 확인중...</span>";
	} else {
		$wiki_count = "<span>&nbsp;</span>";
	}
	
	// 애드센스 정책
	if(count(explode("틀:성적요소", $arr['text']))>1){
		$settings['enableAds'] = false;
		$settings['enableAdsAdult'] = true;
	}
	
		if(!$settings['enableAdsAdult']){ ?>
				<!-- 구글 자동광고 영역 -->
<?php	}
		if($settings['enableAds']){ ?>
				<p>
					<!-- 구글 일반광고 영역 -->
				</p>
<?php	}
	
	if(count(explode("내문서:", $w))>1){
		$get_admin = explode("내문서:", addslashes($w));
		$date = date('Y-m-d H:i:s');
		
		$get_block_arr = getBlockCHK($get_admin[1]);
		$get_admin = getAdminCHK($get_admin[1]);
		
		if($get_block_arr['expire']>$date){
			$arr['text'] = '{{{#!html <div class="alert alert-info fade in last" id="userDiscussAlert" role="alert"><p>'.$get_block_arr['expire'].'까지 차단된 계정입니다.<br>사유 : '.$get_block_arr['title'].'</p></div>}}}';
		}
	}
		if($w!="!MyPage"){
			if(count(explode("내문서:", $w))>1){ ?>
				<div class="wiki-article-menu">
					<div class="btn-group" role="group">
						<a class="btn btn-secondary" itemprop="url" href="/userinfo/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>/contributions" role="button">문서 기여내역</a>
						<a class="btn btn-secondary" itemprop="url" href="/userinfo/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>/discuss" role="button">토론 기여내역</a>
						<a class="btn btn-secondary" itemprop="url" href="/history/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>" role="button">수정 내역</a>
						<a class="btn btn-secondary" itemprop="url" href="/edit/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>" role="button">편집</a>
						<a class="btn btn-secondary" itemprop="url" href="/discuss/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>/0" role="button">토론</a>
					</div>
				</div>
			<?php } else { ?>
				<div class="wiki-article-menu">
					<div class="btn-group" role="group">
<?php				if($contribution!='기여자 정보가 없습니다'){ ?>
						<a class="btn btn-secondary" href="#bottom" onclick="alert('<?=$contribution?>'); return false;" role="button">기여자 내역</a>
<?php				}
					if($queueV2&&empty($_SESSION['name'])){ ?>
						<a class="btn btn-secondary" id="addquev2" role="button">문서 갱신</a>
<?php				} ?>
						<a class="btn btn-secondary" itemprop="url" href="/backlink/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>" role="button">역링크</a>
						<a class="btn btn-secondary" itemprop="url" href="/history/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>" role="button">수정 내역</a>
<?php				if($_SESSION['name']!=''){ ?>
						<a class="btn btn-secondary" itemprop="url" href="/edit/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>" role="button">편집</a>
						<a class="btn btn-secondary" itemprop="url" href="/discuss/<?=str_replace("%2F", "/", rawurlencode($_GET['w']))?>/0" role="button">토론</a>
<?php				} ?>
					</div>
				</div>
			<?php } ?>
<?php	} else { ?>
				<div class="wiki-article-menu">
					<div class="btn-group" role="group">
						<a class="btn btn-secondary" href="/w/!ADReport" role="button">광고 수익금 보고서</a>
					</div>
				</div>
<?php	} ?>
				<h1 class="title">
					<span itemprop="name"><?=$_GET['w']?></span> <?php if(!empty($AllPage)){ echo '(r20'.$settings['docVersion'].'판)'; } if($get_admin['name']!=''){ echo '<span style="font-size:1rem;">('.$get_admin['name'].')</span>'; } ?>
				</h1>
				<p class="wiki-edit-date"><?=$wiki_count?></p>
				<div class="wiki-content clearfix">
					<div class="wiki-inner-content">
<?php
			if($w=="!MyPage"){ ?>
				<h2 class="title">
					<?=$_SERVER['REMOTE_ADDR']?> 개인 설정
				</h2>
<?php			if($settings['ip']=="0.0.0.0"){ ?>
					<h4>
						<a href="settingscreate">설정파일 생성</a>이 필요합니다.
					</h4>
<?php			} else { ?>
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
<?php									if($settings['docVersion']!=$settingsref['docVersion']){ ?>
											<input type="checkbox" name="imgAL" id="needads" disabled> <s>사용</s> <small>(비권장 덤프를 사용할 경우 기능 활성화 불가능)</small>
<?php									} else { ?>
											<input type="checkbox" name="imgAL" id="needads" <?php if($settings['imgAutoLoad']){ echo "checked"; }?>> 사용
<?php									} ?>
										</label>
									</div>
								</div>
								<div class="form-group" id="Ads">
									<label class="control-label">광고 보이기</label>
									<div class="checkbox">
										<label>
<?php									if($settings['docVersion']!=$settingsref['docVersion']){ ?>
											<input type="hidden" name="Ads" value="on"><input type="checkbox" name="Ads" id="ads" <?php if($settings['enableAds']){ echo "checked"; }?> disabled> 사용 <small>(비권장 덤프를 사용할 경우 기능 비활성화 불가능)</small>
<?php									} else { ?>
											<input type="checkbox" name="Ads" id="ads" onclick="if(!document.settings.ads.checked){ alert('The Wiki는 광고 수익금으로 운영됩니다.\n광고가 너무 거슬린다면 기술지원을 통해 피드백을 부탁드립니다.'); }" <?php if($settings['enableAds']){ echo "checked"; }?>> 사용
<?php									} ?>
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
								
								<div class="form-group">
									&nbsp;	<button type="submit" class="btn btn-primary">적용</button>
								</div>
							</div>
						</section>
					</form>
<?php			} ?>
					</div>
				</div>
				
				<?=$THEWIKI_FOOTER?>
			</article>
		</div>
	</body>
</html>
<?php	die(); }
	if(defined("isdeleted")){
		die('<hr>이 문서는 삭제되었습니다.<hr><a href="/edit/'.str_replace("%2F", "/", rawurlencode($_GET['w'])).'" target="_top">새로운 문서 만들기</a> &nbsp; | &nbsp; <a id="addque">나무위키에서 가져오기</a></div></div>'.$THEWIKI_FOOTER.'</article></div></body></html>');
	}
	
	ob_flush(); flush();
	
	if($namespace==3){
		$arr['text'] = "[[".$_GET['w']."]]".$arr['text'];
	}
	if($namespace==11){
		$arr['text'] = "[[".$_GET['w']."]]\n".$arr['text'];
	}
	
	// 분류 문서
	if($namespace==2){
		try{
			$mongo2 = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
			$query = array("title"=>"분류:".$w);
			$query = new MongoDB\Driver\Query($query);
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
		} catch (MongoDB\Driver\Exception\Exception $e){
			$arr2 = "{{{+2 mongoDB 서버에 접속할 수 없습니다}}}";
		}
		
		$arr['text'] = $arr2."\n= 분류 설명 =\n".$arr['text'];
	}
	
	if($arr['text']!=""){
		require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
		$theMark = new theMark($arr['text']);
		if($noredirect){
			$theMark->redirect = false;
		}
		if(!$settings['docStrikeLine']){
			$theMark->strikeLine = true;
		}
		if($namespace=='3'||$namespace=='11'||$settings['imgAutoLoad']=='0'){
			$theMark->imageAsLink = false;
		}
		
		echo $theMark->toHtml();
	} else {
		if($namespace=="11"){
			$sql = "SELECT * FROM file WHERE name = '$_GET[w]'";
			$res = mysqli_query($config_db, $sql);
			$cnt = mysqli_num_rows($res);
			if($cnt>0){
				require_once($_SERVER['DOCUMENT_ROOT']."/theMark.php");
				$arr['text'] = "[[".$_GET['w']."]]";
				$theMark = new theMark($arr['text']);
				
				echo $theMark->toHtml();
			} else { ?>
				업로드된 이미지가 아닙니다.<br><a href='/Upload.php' target='_top'>이미지 업로드</a></div></div><?=$THEWIKI_FOOTER?></article></div></body></html>
<?php			die();
			}
		}
?>
<!-- 구글 검색광고 영역 -->
<?php
		$cURLs = "http://ac.search.naver.com/nx/ac?_callback=result&q=".rawurlencode($_GET['w'])."&q_enc=UTF-8&st=100&frm=nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=1";
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
		
		$result = reset(explode('"]]', next(explode('[["', $result))));
		$result = explode('"],["', $result);
		
		foreach($result as $key=>$value){
			$title_list .= "<a href='/w/".$value."'>".$value."</a> | ";
		}
		$title_list = "| ".$title_list;
		
		echo '<br><hr>저장된 문서가 아닙니다.<br>Google 맞춤검색에서 비슷한 문서가 있는지 검색해보세요.<hr><a href="/edit/'.str_replace("%2F", "/", rawurlencode($_GET['w'])).'" target="_top">새로운 문서 만들기</a> &nbsp; | &nbsp; <a id="addque">나무위키에서 가져오기</a>';
		if(count($result)>1){
			echo '<hr><br>이런 문서들이 있을 수 있습니다. 확인해보세요!<br>'.$title_list;
		}
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