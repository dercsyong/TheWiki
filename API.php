<?php
	$API_SETTINGS['docReVersion'] = $_POST['docReVersion'];
	$API_SETTINGS['docVersion'] = $_POST['docVersion'];
	$API_SETTINGS['divide'] = $_POST['divide'];
	$API_SETTINGS['title'] = $_POST['title'];
	$API_SETTINGS['namespace'] = $_POST['namespace'];
	$API_SETTINGS['noredirect'] = $_POST['noredirect'];
	
	$number_only = '/([0-9])+/';
	preg_match_all($number_only, $API_SETTINGS['docReVersion'], $match);
	$API_SETTINGS['docReVersion'] = implode('', $match[0]);
	
	preg_match_all($number_only, $API_SETTINGS['docVersion'], $match);
	$API_SETTINGS['docVersion'] = implode('', $match[0]);
	
	preg_match_all($number_only, $API_SETTINGS['namespace'], $match);
	$API_SETTINGS['namespace'] = implode('', $match[0]);
	
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
				
				if(!$API_SETTINGS['noredirect']){
					$sql = "SELECT * FROM wiki_contents_moved WHERE p_namespace = '$API_SETTINGS[namespace]' AND p_title = binary('$API_SETTINGS[title]') ORDER BY no DESC LIMIT 1";
					$res = mysqli_query($wiki_db, $sql);
					$moved_arr = mysqli_fetch_array($res);
				} else {
					$moved_arr = null;
				}
				
				if(!empty($moved_arr['title'])){
					$API_SETTINGS['namespace'] = $moved_arr[4];
					$API_SETTINGS['title'] = $moved_arr[5];
					$find = "SELECT * FROM wiki_contents_namespace WHERE code = '$moved_arr[4]'";
					$findres = mysqli_query($wiki_db, $find);
					$findarr = mysqli_fetch_array($findres);
					if($findarr){
						$API_SETTINGS['namespace'] = $findarr[3].':';
					} else {
						$API_SETTINGS['namespace'] = '';
					}
					$API_RETURN = array('status'=>'success', 'type'=>'refresh', 'link'=>'/w/'.$API_SETTINGS['namespace'].$API_SETTINGS['title'], 'namespace'=>$moved_arr['namespace'], 'title'=>$moved_arr['title']);
				} else {
					$sql = "SELECT * FROM wiki_contents_moved WHERE namespace = '$API_SETTINGS[namespace]' AND title = binary('$API_SETTINGS[title]') ORDER BY no DESC LIMIT 1";
					$res = mysqli_query($wiki_db, $sql);
					$moved_arr = mysqli_fetch_array($res);
					
					$API_SETTINGS['SQLnamespace'] = $API_SETTINGS['namespace'];
					$API_SETTINGS['SQLtitle'] = $API_SETTINGS['title'];
					$find = "SELECT * FROM wiki_contents_namespace WHERE code = '$API_SETTINGS[namespace]'";
					$findres = mysqli_query($wiki_db, $find);
					$sqlfindarr = mysqli_fetch_array($findres);
					if($sqlfindarr){
						$API_SETTINGS['SQLnamespace_text'] = $sqlfindarr[3].':';
					} else {
						$API_SETTINGS['SQLnamespace_text'] = '';
					}
					$API_SETTINGS['SQLfull_title'] = $API_SETTINGS['SQLnamespace_text'].$API_SETTINGS['SQLtitle'];
					if(!empty($moved_arr['title'])){
						$API_SETTINGS['namespace'] = $moved_arr[1];
						$API_SETTINGS['title'] = $moved_arr[2];
					}
					$find = "SELECT * FROM wiki_contents_namespace WHERE code = '$moved_arr[1]'";
					$findres = mysqli_query($wiki_db, $find);
					$findarr = mysqli_fetch_array($findres);
					if($findarr){
						$API_SETTINGS['namespace_text'] = $findarr[3].':';
					} else {
						$API_SETTINGS['namespace_text'] = '';
					}
					$API_SETTINGS['full_title'] = $API_SETTINGS['namespace_text'].$API_SETTINGS['title'];
					$arr4 = getACL($API_SETTINGS['SQLfull_title'], 'ALL', 'document');
					
					if(!$arr4){
						$arr4 = getACL($sqlfindarr[3], 'ALL', 'document');
					}
					
					if(getACL($arr4['view'], 'view', 'userDiff')&&$arr4['view']>0){
						$API_RETURN = array('status'=>'error', 'reason'=>'forbidden', 'expire'=>$arr4['expire'], 'class'=>$arr4['view']);
					} else {
						if(empty($API_SETTINGS['docReVersion'])){
							$select = "SELECT * FROM wiki_contents_data WHERE namespace = '$API_SETTINGS[namespace]' AND title = binary('$API_SETTINGS[title]') ORDER BY no DESC LIMIT 1";
							$select_query = mysqli_query($wiki_db, $select);
							$select_array = mysqli_fetch_array($select_query);
							if(!empty($select_array['contents'])){
								$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$select_array['contents'], 'rev'=>$select_array['rev']);
								$return = getDumpVersion($API_SETTINGS['namespace'], $API_SETTINGS['title'], $settings['docVersion']);
								if($return['contribution']){
									$API_RETURN['contribution'] = $return['contribution'];
								}
								unset($return);
							} else {
								$mongoDB = true;
							}
						} else {
							$select = "SELECT * FROM wiki_contents_data WHERE namespace = '$API_SETTINGS[namespace]' AND title = binary('$API_SETTINGS[title]') AND rev ='$API_SETTINGS[docReVersion]' LIMIT 1";
							$select_query = mysqli_query($wiki_db, $select);
							$select_array = mysqli_fetch_array($select_query);
							if(!empty($select_array['contents'])){
								$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$select_array['contents'], 'rev'=>$select_array['rev']);
							} else {
								$API_RETURN = array('status'=>'error', 'reason'=>'reversion error');
							}
						}
						if($API_SETTINGS['namespace']==5){
							$find_array = getUser($API_SETTINGS['title'], 'id');
							if(!empty($find_array['name'])&&$find_array['name']!=$API_SETTINGS['title']){
								$API_RETURN = array('status'=>'success', 'type'=>'refresh', 'link'=>'/w/'.$API_SETTINGS['SQLnamespace_text'].$find_array['name'], 'namespace'=>$API_SETTINGS['namespace'], 'title'=>$find_array['name']);
								$mongoDB = false;
							}
						}
						if(getDocumentDetail($API_SETTINGS, 'delete')){
							define('isdeleted', true)
							$select_array['contents'] = " ";
							$mongoDB = true;
							$API_RETURN = array();
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
			$arr4 = getACL($API_SETTINGS['full_title'], 'ALL', 'document');
			
			if(!$arr4){
				$arr4 = getACL($API_SETTINGS['namespace_text'], 'ALL', 'document');
			}
			if(getACL($arr4['view'], 'view', 'userDiff')&&$arr4['view']>0){
				$API_RETURN = array('status'=>'error', 'reason'=>'forbidden', 'expire'=>$arr4['expire'], 'class'=>$arr4['view']);
			} else {
				if(!$mongo){
					$mongo = mongoDBconnect();
				}
				try{
					
					if(!$API_SETTINGS['namespace']){
						$query = array("namespace"=>"0", "title"=>$API_SETTINGS['title']);
					} else {
						$query = array("namespace"=>$API_SETTINGS['namespace'], "title"=>$API_SETTINGS['title']);
					}
					$query = new MongoDB\Driver\Query($query);
					$arr2 = $mongo->executeQuery('thewiki.docData'.$settings_api['docVersion'], $query);
					
					foreach($arr2 as $value){
						$raw = $value->text;
						$contribution = implode('\\n',$value->contributors);
						break;
					}
					
					if(empty($raw)){
						if(empty($dumpArray)){
							$dumpArray = array(190312);
						}
						foreach($dumpArray as $currentDump){
							$arr2 = $mongo->executeQuery('thewiki.docData'.$currentDump, $query);
							
							foreach($arr2 as $value){
								$raw = $value->text;
								$contribution = implode('\\n',$value->contributors);
								break;
							}
							
							if(!empty($raw)){
								$userAlert = "이 문서는 r20".$settings['docVersion']."판에서 저장되지 않은 문서입니다.<br>자동으로 <a href='/w/?settings=1&autover=".$currentDump."'>r20".$currentDump."판</a> 문서를 읽어왔습니다.";
								$settings['docVersion'] = $currentDump;
								break;
							}
						}
					}
					if(getDocumentDetail($API_SETTINGS, 'namufilter')){
						if(empty($dumpArray)){
							$dumpArray = array(190312);
						}
						foreach($dumpArray as $currentDump){
							$arr2 = $mongo->executeQuery('thewiki.docData'.$currentDump, $query);
							
							foreach($arr2 as $value){
								$raw = $value->text;
								$contribution = implode('\\n',$value->contributors);
								break;
							}
							
							if(getDocumentDetail($raw, 'namufilter2')){
								$userAlert = "이 문서는 나무위키에서 <a href='https://namu.wiki/w/나무위키:투명성 보고서/요청/".$THEWIKI_NOW_TITLE_FULL."' target='_blank'>권리자의 요청</a>으로 임시조치 되었습니다.<br>자동으로 <a href='/w/?settings=1&autover=".$currentDump."'>r20".$currentDump."판</a> 문서를 읽어왔습니다.";
								$settings['docVersion'] = $currentDump;
								break;
							}
						}
					}
					if(empty($raw)){
						$API_RETURN = array('status'=>'error', 'reason'=>'empty document');
					} else {
						$arr = $mongo->executeCommand('thewiki', new MongoDB\Driver\Command(["count"=>"docData".$settings_api['docVersion']]))->toArray();
						$AllPage = $arr[0]->n;
						$API_RETURN = array('status'=>'success', 'type'=>'raw', 'data'=>$raw, 'contribution'=>$contribution, 'count'=>$AllPage);
					}
				} catch (MongoDB\Driver\Exception\Exception $e){
					$API_RETURN = array('status'=>'error', 'reason'=>'mongoDB server error');
				}
			}
		}
	}
	
	if(empty($API_RETURN)){
		$API_RETURN = array('status'=>'error', 'reason'=>'API error');
	}
	if(getDocumentDetail($API_SETTINGS, 'delete')){
		$API_RETURN['data'] = ' ';
		define('isdeleted', true);
	}
	if(!defined('MODEINCLUDE')){
		echo json_encode($API_RETURN);
	} else {
		$api_result = json_decode(json_encode($API_RETURN));
	}
?>
