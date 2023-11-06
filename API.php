<?php
	$API_SETTINGS['docReVersion'] = $_POST['docReVersion'];
	$API_SETTINGS['docVersion'] = $_POST['docVersion'];
	$API_SETTINGS['divide'] = $_POST['divide'];
	$API_SETTINGS['title'] = $_POST['title'];
	$API_SETTINGS['namespace'] = $_POST['namespace'];
	$API_SETTINGS['ip'] = $_POST['ip'];
	$API_SETTINGS['option'] = $_POST['option'];
	$API_SETTINGS['noredirect'] = $_POST['noredirect'];
	if(empty($API_SETTINGS['ip'])){
		$API_SETTINGS['ip'] = '0.0.0.0';
	}
	
	$number_only = '/([0-9])+/';
	preg_match_all($number_only, $API_SETTINGS['docReVersion'], $match);
	$API_SETTINGS['docReVersion'] = implode('', $match[0]);
	
	preg_match_all($number_only, $API_SETTINGS['docVersion'], $match);
	$API_SETTINGS['docVersion'] = implode('', $match[0]);
	
	preg_match_all($number_only, $API_SETTINGS['namespace'], $match);
	$API_SETTINGS['namespace'] = implode('', $match[0]);
	
	$eng_only = '/([a-z])+/';
	preg_match_all($eng_only, $API_SETTINGS['option'], $match);
	$API_SETTINGS['option'] = implode('', $match[0]);
	
	if(empty($API_SETTINGS['docVersion'])&&empty($API_SETTINGS['divide'])){
		if(empty($API_SETTINGS['title'])){
			$API_RETURN = array('status'=>'error', 'reason'=>'please check document title');
		} else {
			if(!$wiki_db){
				$API_RETURN = array('status'=>'error', 'reason'=>'main db error');
			} else {
				if(empty($API_SETTINGS['namespace'])){
					$API_SETTINGS['namespace'] = 0;
				}
				
				$find = "SELECT * FROM wiki_contents_namespace WHERE code = '".$API_SETTINGS['namespace']."'";
				$findres = mysqli_query($wiki_db, $find);
				$findarr = mysqli_fetch_array($findres);
				if($findarr){
					$API_SETTINGS['namespace_text'] = $findarr[3].':';
				} else {
					$API_SETTINGS['namespace_text'] = '';
				}
				$API_SETTINGS['full_title'] = $API_SETTINGS['namespace_text'].$API_SETTINGS['title'];
				
				$now = getDocumentNow($API_SETTINGS['namespace'], $API_SETTINGS['title']);
				if($now['status']!="success"&&$now['status']!="not found"){
					// mongo error
				} else {
					$arr4 = getACL($API_SETTINGS['full_title'], 'ALL', 'document');
					
					if(!$arr4){
						$arr4 = getACL($API_SETTINGS['namespace_text'], 'ALL', 'document');
					}
					
					if(getACL($arr4['view'], 'view', 'userDiff')&&$arr4['view']>0){
						$API_RETURN = array('status'=>'error', 'reason'=>'forbidden', 'expire'=>$arr4['expire'], 'class'=>$arr4['view']);
					} else {
						if(empty($API_SETTINGS['docReVersion'])){
							if($now['status']=="not found"||$now['data']->isDump||$now['data']->rev==null){
								$mongoDB = true;
								$editRev = $now['data']->rev;
								if(gettype($now['data']->originDocuN)!="NULL"&&$now['data']->originDocuT!=null){
									$API_SETTINGS['namespace'] = $now['data']->originDocuN;
									$API_SETTINGS['title'] = $now['data']->originDocuT;
								}
							} else {
								$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$now['data']->contents, 'rev'=>$now['data']->rev, 'deleted'=>$now['data']->isDelete, 'edit_date'=>$now['data']->date);
								$return = getDumpVersion($API_SETTINGS['namespace'], $API_SETTINGS['title'], $settingsref['docVersion']);
								if($return['contribution']){
									$API_RETURN['contribution'] = $return['contribution'];
								}
							}
						} else {
							$now = getDocumentData($API_SETTINGS['namespace'], $API_SETTINGS['title'], $API_SETTINGS['docReVersion']);
							if($now['status']=="not found"||$now['data']->isDump){
								$API_RETURN = array('status'=>'error', 'reason'=>'reversion error', 'isDump'=>$now['data']->isDump);
							} else {
								$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$now['data']->contents, 'rev'=>$now['data']->rev, 'deleted'=>$now['data']->isDelete, 'edit_date'=>$now['data']->date);
							}
						}
					}
				}
			}
		}
	}
	
	$sqlref = "SELECT * FROM settings WHERE ip = '0.0.0.0'";
	$resref = mysqli_query($config_db, $sqlref);
	$settings_apiref = mysqli_fetch_array($resref);
	
	if(!empty($API_SETTINGS['divide'])){
		$mongoDB = true;
	}
	
	if($mongoDB){
		if(!$config_db){
			$API_RETURN = array('status'=>'error', 'reason'=>'sub db error');
		} else {
			if(isLogin($_SESSION)['return']){
				$API_SETTINGS['ip'] = isLogin($_SESSION)['id'];
			}
			$sql = "SELECT * FROM settings WHERE ip = '$API_SETTINGS[ip]'";
			$res = mysqli_query($config_db, $sql);
			$settings_api = mysqli_fetch_array($res);
			
			if($settings_api['docVersion']!=$settings_apiref['docVersion']){
				$API_SETTINGS['docVersion'] = $settings_api['docVersion'];
			} else {
				$API_SETTINGS['docVersion'] = $settings_apiref['docVersion'];
			}
			if(empty($API_SETTINGS['docVersion'])){
				$API_SETTINGS['docVersion'] = $settings_apiref['docVersion'];
			}
		}
	}
	
	if(!empty($API_SETTINGS['docVersion'])){
		$API_RETURN = array();
		if($API_SETTINGS['docVersion']!=$settings_api['docVersion']){
			$settings_api['docVersion'] = $API_SETTINGS['docVersion'];
		}
		if(empty($API_SETTINGS['title'])){
			$API_RETURN = array('status'=>'error', 'reason'=>'please check document title');
		} else {
			if($API_SETTINGS['namespace']==4){
				$settings_api['docVersion'] = '170327';
				$settings['docVersion'] = '170327';
				$currentDumpText = "20".$settings['docVersion'];
				$userAlert = "사용자 문서는 r20".$settings['docVersion']."판으로만 확인할 수 있습니다.<br>자동으로 r20".$settings['docVersion']."판 문서를 읽어왔습니다.";
			}
			
			$find = "SELECT * FROM wiki_contents_namespace WHERE code = '".$API_SETTINGS['namespace']."'";
			$findres = mysqli_query($wiki_db, $find);
			$findarr = mysqli_fetch_array($findres);
			if($findarr){
				$API_SETTINGS['namespace_text'] = $findarr[3].':';
			} else {
				$API_SETTINGS['namespace_text'] = '';
			}
			$API_SETTINGS['full_title'] = $API_SETTINGS['namespace_text'].$API_SETTINGS['title'];
			
			$now = getDocumentNow($API_SETTINGS['namespace'], $API_SETTINGS['title']);
			if($now['status']!="success"&&$now['status']!="not found"){
				// mongo error
			} else {
				$arr4 = getACL($API_SETTINGS['full_title'], 'ALL', 'document');
			
				if(!$arr4){
					$arr4 = getACL($API_SETTINGS['namespace_text'], 'ALL', 'document');
				}
				if(getACL($arr4['view'], 'view', 'userDiff')&&$arr4['view']>0){
					$API_RETURN = array('status'=>'error', 'reason'=>'forbidden', 'expire'=>$arr4['expire'], 'class'=>$arr4['view']);
				} else {
					try{
						if(!$API_SETTINGS['namespace']){
							$API_SETTINGS['namespace'] = 0;
						}
						$arr2 = dumpCheck($API_SETTINGS['namespace'], $API_SETTINGS['title'], null);
						
						foreach($arr2 as $value){
							$raw = $value->text;
							$contribution = implode('\\n',$value->contributors);
							break;
						}
						
						if(empty($raw)){
							if(empty($dumpArray)){
								$dumpArray = array(230302, 220720, 210301, 200302, 190312);
							}
							foreach($dumpArray as $currentDump){
								$arr2 = dumpCheck($API_SETTINGS['namespace'], $API_SETTINGS['title'], $currentDump);
								
								foreach($arr2 as $value){
									$raw = $value->text;
									$contribution = implode('\\n',$value->contributors);
									break;
								}
								
								if(!empty($raw)){
									$currentDumpText = "20".$currentDump;
									if(!$donotOtherDump){
										$userAlert = "이 문서는 ".$settings['docVersion_text']."판에서 저장되지 않은 문서입니다.<br>자동으로 <a href='/w/?settings=1&autover=".$currentDump."'>r".$currentDumpText."판</a> 문서를 읽어왔습니다.";
									}
									$settings['docVersion'] = $currentDump;
									$settings['docVersion_text'] = "r20".$settings['docVersion'];
									break;
								}
							}
						}
						if(getDocumentDetail($API_SETTINGS, 'namufilter')){
							if(empty($dumpArray)){
								$dumpArray = array(230302, 220720, 210301, 200302, 190312);
							}
							foreach($dumpArray as $currentDump){
								$arr2 = dumpCheck($API_SETTINGS['namespace'], $API_SETTINGS['title'], $currentDump);
								
								foreach($arr2 as $value){
									$raw = $value->text;
									$contribution = implode('\\n',$value->contributors);
									break;
								}
								
								if(getDocumentDetail($raw, 'namufilter2')){
									$currentDumpText = "20".$currentDump;
									$userAlert = "이 문서는 나무위키에서 <a href='https://namu.wiki/w/나무위키:투명성 보고서/요청/".$THEWIKI_NOW_TITLE_FULL."' target='_blank'>권리자의 요청</a>으로 임시조치 되었습니다.<br>자동으로 <a href='/w/?settings=1&autover=".$currentDump."'>r".$currentDumpText."판</a> 문서를 읽어왔습니다.";
									$settings['docVersion'] = $currentDump;
									break;
								}
							}
						}
						
						if(empty($raw)){
							$API_RETURN = array('status'=>'error', 'reason'=>'empty document');
						} else {
							$AllPage = getpagecount();
							$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$raw, 'contribution'=>$contribution, 'count'=>$AllPage, 'ifEdit'=>$editRev);
						}
					} catch (MongoDB\Driver\Exception\Exception $e){
						$API_RETURN = array('status'=>'error', 'reason'=>'mongoDB server error');
					}
				}
			}
		}
	}
	
	if(empty($API_RETURN)){
		$API_RETURN = array('status'=>'error', 'reason'=>'API error');
	}
	if($API_RETURN['deleted']){
		define('isdeleted', true);
	}
	if(!defined('MODEINCLUDE')){
		echo json_encode($API_RETURN);
	} else {
		$api_result = json_decode(json_encode($API_RETURN));
	}
?>
