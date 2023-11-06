<?php
	$cssVersion = 202304050142;
	$jsVersion = 202212280200;
	$initLayout = '<!DOCTYPE html><html lang="ko"><head>';
	if($settings['enableAds']){
		$initLayout .= $THEWIKI_LAYOUT_ADS;
	}
	if($theMarkDescription){
		$initLayout .= filter_layout($theMarkDescription);
	}
	/* css, js */
	
	$_SESSION = getAdminCHKsession($_SESSION);
	$THEWIKI_NAV = getdropdown($_SESSION);
	
	if($settings['enableAds']){
		$sidebarAds = '<!-- 광고 코드 -->';
	} else {
		$sidebarAds = "";
	}
	
	if($_COOKIE['darkMode']=="true"){
		$bodyClass = " dark";
	} else {
		$bodyClass = " ";
	}
	$articleSize = "";
	$customDarkMode = "";
	
	if(!$settings['checkSave']){
		if($_COOKIE['darkMode']=="true"){
			$bodyClass = " dark";
		} else {
			$bodyClass = " ";
		}
	} else {
		$bodyClass .= " custom";
		if($settings['articleSize']!=1300){
			$articleSize = ' style="max-width: '.$settings['articleSize'].'px;"';
		}
		if($settings['fixNavbar']){
			$bodyClass .= " fixNavbar";
		}
		if($settings['leftSidebar']){
			$bodyClass .= " leftSidebar";
		}
		if($settings['hideSidebar']){
			$bodyClass .= " hide-sidebar";
		}
		if($settings['enableDarkMode']==1){
			//$bodyClass .= " dark";
		} else if($settings['enableDarkMode']==0){
			$customDarkMode = $THEWIKI_LAYOUT_DARK_CUSTOM;
			$bodyClass = str_replace("dark", "", $bodyClass);
		} else {
			$bodyClass = str_replace("dark", "", $bodyClass);
		}
		$bodyClass = str_replace("  ", " ", $bodyClass);
	}
	
	$sidebarScript .= filter_layout($sidebarScript);
	$sidebarContent = '<aside class="sidebar"><div class="card recent-card"><h5 class="card-title">최근 변경</h5><div class="link-table" id="recentChangeTable"><a><span>00:00</span>갱신중...</a></div><a class="more-link" href="/Recent">[더 보기]</a></div><div class="card recent-card"><h5 class="card-title">최근 토론</h5><div class="link-table" id="recentDiscussTable"><a><span>00:00</span>갱신중...</a></div><a class="more-link" href="/RecentDiscuss">[더 보기]</a></div><div style="position: sticky;top: 1rem;">'.$sidebarAds.'</div></aside>';
	$headLayout = $initLayout.'</head>';
	
	if(!empty($needCss)){
		$sidebarScript = $needCss.$sidebarScript;
	}
	
	$bodyLayout = '<body class="senkawa fixed-size'.$bodyClass.'">'.$sidebarScript.'<div class="content-wrapper"'.$articleSize.'>'.$customDarkMode.'<article class="container-fluid wiki-article">';
	$footerLayout = $THEWIKI_FOOTER;
	if(!empty($title)&&$_SERVER['HTTP_X_PJAX']){
		echo '<script> document.title = "'.$title.'"; ';
		$currentPage = explode('/', $THEWIKI_NOW_PAGE)[1];
		if(($currentPage=="w"||$currentPage=="search"||$currentPage=="googlesearch")&&empty($_SESSION['THEWIKI_PJAX_MOVED'])){
			echo "var cur = document.location.pathname.split('/')[1]; if(cur=='renew'||cur=='go'){ history.pushState(null, null, document.location.pathname.replace('/'+cur+'/', '/".$currentPage."/')); } ";
		} else if(!empty($_SESSION['THEWIKI_PJAX_MOVED'])){
			echo "history.pushState(null, null, '".$_SESSION['THEWIKI_PJAX_MOVED']."');";
			$_SESSION['THEWIKI_PJAX_MOVED'] = null;
		}
		echo '</script>';
	}
	if($_SERVER['HTTP_X_PJAX']){
		echo $siteNotice;
	}
?>