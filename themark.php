<?php
	if(!defined('USETHEMARK')){
		die(header('Location: /'));
	}
	
	function simplemark($str){
		// 하위문서 링크
		$str = str_replace("[[/", "[[".$_GET['w']."/", $str);
		$str = str_replace("</div>}}}_(HTMLE)_", "</div>}}}{{{#!html </div>}}}", $str);
		$str = str_replace('[[나무파일:', '[[파일:', $str);
		
		// [[XXX|[[XXX]]]] 문법 우회 적용
		$str = str_replace("| [[:", "|[[:", $str);
		$filestart = explode('|[[파일:', $str);
		for($x=0;$x<count($filestart)-1;$x++){
			$include2 = end(explode("[[", $filestart[$x]));
			$filelink = "파일:".reset(explode("]]", $filestart[$x+1]));
			
			if(substr($include2, 0, 7)=="http://"||substr($include2, 0, 8)=="https://"||substr($include2, 0, 2)=="//"){
				$change = '{{{#!html <a href="'.$include2.'" target="_blank">}}}[['.$filelink.']]{{{#!html </a>}}}';
			} else {
				$change = '{{{#!html <a href="/w/'.$include2.'" target="_self">}}}[['.$filelink.']]{{{#!html </a>}}}';
			}
			$str = str_replace("[[".$include2."|[[".$filelink."]]]]", $change, $str);
		}
		
		// 작업마무리
		$str = str_replace("_(HTMLS)_", "{{{", $str);
		$str = str_replace("_(HTMLE)_", "}}}", $str);
		$str = str_replace("_(HTMLSTART)_", "{{{#!html", $str);
		$str = str_replace("_(NAMUMIRRORHTMLSTART)_", "{{{#!html <div style=", $str);
		$str = str_replace("_(NAMUMIRRORHTMLEND)_", "}}}", $str);
		$str = str_replace("_(NAMUMIRRORDAASH)_", "'", $str);
		$str = str_replace("\n ||", "\n||", $str);
		$str = str_replace("#!end}}}", "}}}", $str);
		
		return $str;
	}
	
	function themark($str){
		// 하위문서 링크
		$str = str_replace("[[/", "[[".$_GET['w']."/", $str);
		$str = str_replace("</div>}}}_(HTMLE)_", "</div>}}}{{{#!html </div>}}}", $str);
		$str = str_replace('[[나무파일:', '[[파일:', $str);
		
		// [[XXX|[[XXX]]]] 문법 우회 적용
		$str = str_replace("| [[:", "|[[:", $str);
		$filestart = explode('|[[파일:', $str);
		for($x=0;$x<count($filestart)-1;$x++){
			$include = end(explode("[[", $filestart[$x]));
			$filelink = "파일:".reset(explode("]]", $filestart[$x+1]));
			
			if(substr($include, 0, 7)=="http://"||substr($include, 0, 8)=="https://"||substr($include, 0, 2)=="//"){
				$change = '{{{#!html <a href="'.$include.'" target="_blank">}}}[['.$filelink.']]{{{#!html </a>}}}';
			} else {
				$change = '{{{#!html <a href="/w/'.$include.'" target="_self">}}}[['.$filelink.']]{{{#!html </a>}}}';
			}
			$str = str_replace("[[".$include."|[[".$filelink."]]]]", $change, $str);
		}
		
		// 작업마무리
		$str = str_replace("_(HTMLS)_", "{{{", $str);
		$str = str_replace("_(HTMLE)_", "}}}", $str);
		$str = str_replace("_(HTMLSTART)_", "{{{#!html", $str);
		$str = str_replace("_(NAMUMIRRORHTMLSTART)_", "{{{#!html <div style=", $str);
		$str = str_replace("_(NAMUMIRRORHTMLEND)_", "}}}", $str);
		$str = str_replace("_(NAMUMIRRORDAASH)_", "'", $str);
		$str = str_replace("\n ||", "\n||", $str);
		$str = str_replace("#!end}}}", "}}}", $str);
		
		// #!folding 문법 #!end}}} 치환
		$foldingstart = explode('{{{#!folding ', $str);
		for($z=1;$z<count($foldingstart);$z++){
			$foldingcheck = true;
			$find = '';
			$match = '';
			$temp_explode = '';
			
			if(count(explode("}}}", $foldingstart[$z]))>1){
				$temp_explode = explode("}}}", $foldingstart[$z]);
				
				$end_loop = 0;
				while(count($temp_explode)>$end_loop){
					if(count(explode('{{{', $temp_explode[$end_loop]))>1){
						$end_loop++;
					} else {
						for($x=0;$end_loop>$x;$x++){
							$match .= $temp_explode[$x].'}}}';
						}
						$find = $match.$temp_explode[$end_loop].'}}}';
						$match .= $temp_explode[$end_loop].'#!end}}}';
						$end_loop = count($temp_explode)+1;
					}
				}
				
				$str = str_replace('{{{#!folding '.$find, '{{{#!folding '.$match, $str);
			}
		}
		// #!folding 문법 우선 적용
		$foldingstart = explode('{{{#!folding ', $str);
		for($z=1;$z<count($foldingstart);$z++){
			$foldingcheck = true;
			$foldopentemp = reset(explode("
", $foldingstart[$z]));
			if(count(explode("#!end}}}", $foldingstart[$z]))>1){
				$foldingtemp = str_replace("#!end}}}", "_(FOLDINGEND)_", $foldingstart[$z]);
				$foldingdatatemp = next(explode($foldopentemp, reset(explode("_(FOLDINGEND)_", $foldingtemp))));
				$md5 = md5(rand(1,10).$foldingdatatemp);
				$foldopen[$md5] = $foldopentemp;
				$foldingdata[$md5] = $foldingdatatemp;
				$str = str_replace("{{{#!folding ".$foldopentemp.$foldingdatatemp."#!end}}}", "_(FOLDINGSTART)_".$md5."_(FOLDINGSTART2)_ _(FOLDINGDATA)_".$md5."_(FOLDINGDATA2)_ _(FOLDINGEND)_", $str);
			}
		}
		
		// MySQLWikiPage와는 달리 PlainWikiPage의 첫 번째 인수로 위키텍스트를 받습니다.
		$wPage = new PlainWikiPage($str);
		
		// NamuMark 생성자는 WikiPage를 인수로 받습니다.
		$wEngine = new NamuMark($wPage);
		
		// 위키링크의 앞에 붙을 경로를 prefix에 넣습니다.
		$wEngine->prefix = "/w";
		if($namespace!='3'&&$namespace!='11'&&defined('THEMARK_IMGLOAD')==0){ $wEngine->imageAsLink = true; }
		$wPrint = $wEngine->toHtml();
		
		// #!folding
		if($foldingcheck){
			$wPrint = str_replace('_(FOLDINGEND)_', '</div></dd></dl>', $wPrint);
			
			$getmd5 = explode("_(FOLDINGDATA)_", $wPrint);
			for($xz=1;$xz<count($getmd5);$xz++){
				$mymd5 = reset(explode("_(FOLDINGDATA2)_", $getmd5[$xz]));
				$wPrint = str_replace('_(FOLDINGSTART)_'.$mymd5.'_(FOLDINGSTART2)_', '<dl class="wiki-folding"><dt><center>'.$foldopen[$mymd5].'</center></dt><dd style="display: none;"><div class="wiki-table-wrap">', $wPrint);
				
				$fPage = new PlainWikiPage($foldingdata[$mymd5]);
				$fEngine = new NamuMark($fPage);
				$fEngine->prefix = "/w";
				$fPrint = $fEngine->toHtml();
				
				$wPrint = str_replace('<div class="wiki-table-wrap"> _(FOLDINGDATA)_'.$mymd5.'_(FOLDINGDATA2)_ </div>', '<div class="wiki-table-wrap"> '.$fPrint.' </div>', $wPrint);
			}
		}
		
		// 이미지 queue 지원
		$wPrint = str_replace("[IMGQUEUE]", $IMGQUEUE, $wPrint);
		$wPrint = str_replace("[TEMPIMGQUEUE]", $TEMPIMGQUEUE, $wPrint);
		
		foreach($wPrint2 as $key=>$value){
			$find = "_(SCRIPTBYPASS_".$key.")_";
			$wPrint = str_replace($find, $value, $wPrint);
		}
		
		foreach($override as $key => $val){
			$wPrint = str_replace('@'.$key.'@', $val, $wPrint);
		}
		
		$wPrint = str_replace('<a href="/w/'.str_replace(array('%3A', '%2F', '%23', '%28', '%29'), array(':', '/', '#', '(', ')'), rawurlencode($_GET['w'])).'"', '<a style="font-weight:bold;" href="/w/'.str_replace(array('%3A', '%2F', '%23', '%28', '%29'), array(':', '/', '#', '(', ')'), rawurlencode($_GET['w'])).'"', $wPrint);
		return str_replace('<br> <br>', '', str_replace('&lt;math&gt;', '$$', str_replace('&lt;/math&gt;', '$$', $wPrint)));
	}
?>