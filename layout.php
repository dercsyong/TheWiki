<?php
	$cssVersion = 202206030424;
	$jsVersion = 202206040351;
	$initLayout = '<!DOCTYPE html><html><head><title>'.$title.'</title><meta charset="utf-8"/><meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"/><meta http-equiv="x-ua-compatible" content="ie=edge"/>';
	$cssLayout = '<link rel="stylesheet" href="/namuwiki/css/jquery-ui.min.css"/><link rel="stylesheet" href="/namuwiki/css/bootstrap.min.css"/><link rel="stylesheet" href="/namuwiki/css/bootstrap-fix.css"/><link rel="stylesheet" href="/namuwiki/css/ionicons.min.css"/><link rel="stylesheet" href="/namuwiki/css/katex.min.css"/><link rel="stylesheet" href="/namuwiki/css/flag-icon.min.css"/><link rel="stylesheet" href="/namuwiki/css/wiki.css?version='.$cssVersion.'"/><link rel="stylesheet" href="/namuwiki/css/discuss.css"/><link rel="stylesheet" href="/js/diffview.css"/>';
	$jsLayout = '<script type="text/javascript" src="/namuwiki/js/jquery-2.1.4.min.js"></script><script type="text/javascript" src="/namuwiki/js/jquery-ui.min.js"></script><script type="text/javascript" src="/namuwiki/js/bootstrap.min.js"></script><script type="text/javascript" src="/js/wiki.js?version='.$jsVersion.'"></script><script src="/js/jquery.uploadfile.js"></script><script src="/js/bindWithDelay.js"></script><script type="text/javascript" src="/js/difflib.js"></script><script type="text/javascript" src="/js/diffview.js"></script>';
	$parserScript = '<script src="/js/katex.min.js" integrity="sha384-483A6DwYfKeDa0Q52fJmxFXkcPCFfnXMoXblOkJ4JcA8zATN6Tm78UNL72AKk+0O" crossorigin="anonymous"></script><script src="/js/auto-render.min.js" integrity="sha384-yACMu8JWxKzSp/C1YV86pzGiQ/l1YUfE8oPuahJQxzehAjEt2GiQuy/BIvl9KyeF" crossorigin="anonymous"></script><script type="text/javascript" src="/namuwiki/js/jquery.lazyload.min.js"></script><link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.18.1/styles/default.min.css"><script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.18.1/highlight.min.js"></script>';
	$parserLayout = '<script type="text/javascript"> document.addEventListener("DOMContentLoaded", function() { hljs.initHighlightingOnLoad(); $("img.lazyimage").lazyload({ placeholder : "https://via.placeholder.com/1x1", threshold: 50, effect : "fadeIn", load : function(){ $(this).attr("src",$(this).attr("data-original")); } }); }); </script>';
	if(!empty($title)&&$_SERVER['HTTP_X_PJAX']){
		$parserLayout .= '<script> hljs.initHighlightingOnLoad(); $("img.lazyimage").lazyload({ placeholder : "https://via.placeholder.com/1x1", threshold: 50, effect : "fadeIn", load : function(){ $(this).attr("src",$(this).attr("data-original")); } }); renderMathInElement(document.body, { delimiters: [ {left: "[math(", right: "#!mathend)]", display: false} ] }); </script>';
	} else {
		$parserLayout .= '<script> document.addEventListener("DOMContentLoaded", function() { renderMathInElement(document.body, { delimiters: [ {left: "[math(", right: "#!mathend)]", display: false} ] }); }); </script>';
	}
	
	$_SESSION = getAdminCHKsession($_SESSION);
	$THEWIKI_NAV = getdropdown($_SESSION);
	
	if($settings['enablePjax']){
		$sidebarScript = '<script defer type="text/javascript" src="/namuwiki/js/jquery.pjax.js?version='.$jsVersion.'"></script><script defer type="text/javascript" src="/namuwiki/js/nprogress.js?version='.$jsVersion.'"></script><link rel="stylesheet" href="/namuwiki/css/nprogress.css?version='.$cssVersion.'"/>';
	} else {
		$sidebarScript = '';
	}
	
	if($settings['enableAds']){
		$sidebarAds = '<!-- 광고 코드 -->';
	} else {
		$sidebarAds = "";
	}
	
	$bodyClass = "";
	$articleSize = "";
	$customDarkMode = "";
	if(!$settings['checkSave']){
		$bodyClass = "";
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
			$bodyClass .= " dark";
		} else if($settings['enableDarkMode']==0){
			$customDarkMode = '<link rel="stylesheet" href="/namuwiki/css/dark.css?version='.$cssVersion.'">';
		}
	}
	
	$sidebarScript .= '<link rel="stylesheet" href="/namuwiki/css/layout.sidebar.css?version='.$cssVersion.'"/><script type="text/javascript" src="/namuwiki/js/layout.sidebar.js?version='.$jsVersion.'"></script><div class="navbar-wrapper"><nav class="navbar bg-inverse"'.$articleSize.'>'.$THEWIKI_NAV.'</nav></div>';
	$sidebarContent = '<aside class="sidebar"><div class="card recent-card"><h5 class="card-title">최근 변경 내역</h5><div class="link-table" id="recentChangeTable"><a><span>00:00</span>갱신중...</a></div><a class="more-link" href="/Recent">[더 보기]</a></div><div class="card recent-card"><h5 class="card-title">최근 토론 목록</h5><div class="link-table" id="recentDiscussTable"><a><span>00:00</span>갱신중...</a></div><a class="more-link" href="/RecentDiscuss">[더 보기]</a></div><div style="position: sticky;top: 1rem;">'.$sidebarAds.'</div></aside>';
	
	$headLayout = $initLayout.$cssLayout.$jsLayout.$parserScript.'</head>';
	
	if(!empty($needCss)){
		$sidebarScript = $needCss.$sidebarScript;
	}
	
	$bodyLayout = '<body class="senkawa fixed-size'.$bodyClass.'">'.$sidebarScript.'<div class="content-wrapper"'.$articleSize.'>'.$customDarkMode.'<article class="container-fluid wiki-article">';
	$footerLayout = $THEWIKI_FOOTER;
	if(!empty($title)&&$_SERVER['HTTP_X_PJAX']){
		echo '<script> document.title = "'.$title.'"; </script>';
	}
	if($_SERVER['HTTP_X_PJAX']){
		echo $siteNotice;
	}
?>