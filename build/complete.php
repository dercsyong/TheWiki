<?php
	define('THEWIKI', true);
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	
	try{
		if($THEWIKI_NOW_NAMESPACE==5||$THEWIKI_NOW_NAMESPACE==10||$THEWIKI_NOW_NAMESPACE==11){
			$search = "SELECT * FROM wiki_contents_history WHERE namespace = '$THEWIKI_NOW_NAMESPACE' AND title LIKE '".$THEWIKI_NOW_TITLE_REAL."%'";
			$searchres = mysqli_query($wiki_db, $search);
			$data = array();
			while($searcharr = mysqli_fetch_array($searchres)){
				if(!in_array($THEWIKI_NOW_NAMESPACE_NAME.":".$searcharr[2], $data)){
					$data[] = $THEWIKI_NOW_NAMESPACE_NAME.":".$searcharr[2];
					$loop++;
				}
				if($loop>15){
					break;
				}
			}
			$dbMode = true;
		} else {
			if($THEWIKI_NOW_NAMESPACE==2||$THEWIKI_NOW_NAMESPACE==3||$THEWIKI_NOW_NAMESPACE==4){
				if($settings['docVersion']!='180925'&&$settings['docVersion']>=180326){
					$settings['docVersion'] = '170327';
				}
			} else {
				if($settings['docVersion']=='180925'&&!empty($THEWIKI_NOW_NAMESPACE_FAKE)){
					$THEWIKI_NOW_NAMESPACE = $THEWIKI_NOW_NAMESPACE_FAKE;
				}
			}
			$mongo = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
		}
		if(!$dbMode){
			if(empty($THEWIKI_NOW_NAMESPACE)){
				$THEWIKI_NOW_NAMESPACE = "0";
			}
			if(!empty($THEWIKI_NOW_TITLE_REAL)){
				$query = new MongoDB\Driver\Query(array('namespace' => $THEWIKI_NOW_NAMESPACE, 'title' => array('$regex'=>"^".$THEWIKI_NOW_TITLE_REAL)), array('limit' => 10 ));
			} else {
				if(!empty($THEWIKI_NOW_TITLE_FULL)){
					$query = new MongoDB\Driver\Query(array('namespace' => $THEWIKI_NOW_NAMESPACE), array('limit' => 10 ));
				}
			}
			$arr = $mongo->executeQuery('thewiki.docData'.$settings['docVersion'], $query);
			if(!empty($THEWIKI_NOW_NAMESPACE_NAME)){
				$THEWIKI_NOW_NAMESPACE_NAME = $THEWIKI_NOW_NAMESPACE_NAME.":";
			}
			$data = array();
			$trigger = true;
			foreach($arr as $doc){
				$trigger = false;
				$data[] = $THEWIKI_NOW_NAMESPACE_NAME.$doc->title;
			}
			if($trigger){
				$query = new MongoDB\Driver\Query(array('$text'=>array('$search'=>$THEWIKI_NOW_TITLE_FULL)), array('limit' => 10 ));
				$arr = $mongo->executeQuery('thewiki.docData'.$settingsref['docVersion'], $query);
				foreach($arr as $doc){
					$trigger = false;
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
					$data[] = $doc->title;
				}
			}
		}
		echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	} catch (MongoDB\Driver\Exception\Exception $e){
		die();
	}
?>
