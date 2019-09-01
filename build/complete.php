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
				if($loop>10){
					break;
				}
			}
			$dbMode = true;
		} else {
			if($THEWIKI_NOW_NAMESPACE==2||$THEWIKI_NOW_NAMESPACE==3||$THEWIKI_NOW_NAMESPACE==4){
				if($settings['docVersion']==$settingsref['docVersion']){
					$settings['docVersion'] = '180326';
				}
				$mongo = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
			} else {
				if($settings['docVersion']==$settingsref['docVersion']){
					$mongo = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
				} else {
					if(!empty($THEWIKI_NOW_NAMESPACE_FAKE)){
						$THEWIKI_NOW_NAMESPACE = $THEWIKI_NOW_NAMESPACE_FAKE;
					}
					$mongo = new MongoDB\Driver\Manager('mongodb://username:password@localhost:27017/thewiki');
				}
			}
		}
		if(!$dbMode&&!empty($THEWIKI_NOW_TITLE_REAL)){
			$query = new MongoDB\Driver\Query(array('namespace' => $THEWIKI_NOW_NAMESPACE, 'title' => array('$regex'=>'^'.$THEWIKI_NOW_TITLE_REAL)), array('limit' => 10 ));
			switch($settings['docVersion']){
				case '160229': $arr = $mongo->executeQuery('nisdisk.docData160229', $query); break;
				case '160329': $arr = $mongo->executeQuery('nisdisk.docData160329', $query); break;
				case '160425': $arr = $mongo->executeQuery('nisdisk.docData160425', $query); break;
				case '160530': $arr = $mongo->executeQuery('nisdisk.docData160530', $query); break;
				case '160627': $arr = $mongo->executeQuery('nisdisk.docData160627', $query); break;
				case '160728': $arr = $mongo->executeQuery('nisdisk.docData160728', $query); break;
				case '160829': $arr = $mongo->executeQuery('nisdisk.docData160829', $query); break;
				case '161031': $arr = $mongo->executeQuery('nisdisk.docData161031', $query); break;
				case '170327': $arr = $mongo->executeQuery('nisdisk.docData170327', $query); break;
				case '180326': $arr = $mongo->executeQuery('nisdisk.docData180326', $query); break;
				case '180925': $arr = $mongo->executeQuery('nisdisk.docData180925', $query); break;
				default: $arr = $mongo->executeQuery('nisdisk.docData190312', $query); break;
			}
			
			$data = array();
			foreach($arr as $doc){
				$data[] = $THEWIKI_NOW_NAMESPACE_NAME.":".$doc->title;
			}
		}
		echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	} catch (MongoDB\Driver\Exception\Exception $e){
		die();
	}
?>