<?php
	session_start();
	define('THEWIKI', true);
	$THEWIKI_SUBSTRLEN = explode('/', $_SERVER['REQUEST_URI'])[1];
	include $_SERVER['DOCUMENT_ROOT'].'/config.php';
	
	if(!$mongoStatus){
		die();
	}
	$THEWIKI_NOW_TITLE_FULL = filter_d($THEWIKI_NOW_TITLE_FULL);
	$THEWIKI_NOW_TITLE_REAL = filter_d($THEWIKI_NOW_TITLE_REAL);
	try{
		if($THEWIKI_NOW_NAMESPACE==2){
			while(substr($THEWIKI_NOW_TITLE_REAL, 0, 1)==" "){
				$THEWIKI_NOW_TITLE_REAL = substr($THEWIKI_NOW_TITLE_REAL, 1);
			}
			$query = new MongoDB\Driver\Query(array('title' => array('$regex'=>"^분류:".str_replace(array("(", ")"), array("\\(", "\\)"), $THEWIKI_NOW_TITLE_REAL))), array('limit'=>10, 'maxTimeMS'=>1000));
			$arr = $mongo->executeQuery('search.category', $query);
			foreach($arr as $doc){
				$data[] = $doc->title;
			}
		} else if($THEWIKI_NOW_NAMESPACE==3){
			$ext = strtolower(end(explode(".", $THEWIKI_NOW_TITLE_REAL)));
			if($ext=="jpg"||$ext=="png"||$ext=="gif"||$ext=="svg"||$ext=="webp"){
				$fileName = bin2hex($THEWIKI_NOW_TITLE_REAL).".".end(explode(".", $THEWIKI_NOW_TITLE_REAL));
			} else {
				$fileName = bin2hex($THEWIKI_NOW_TITLE_REAL);
			}
			while(substr($fileName, 0, 2)=="20"){
				$fileName = substr($fileName, 2);
			}
			if(!$mongodumpStatus){
				die();
			}
			$query = new MongoDB\Driver\Query(array('title' => array('$regex'=>"^".$fileName)), array('limit'=>10, 'maxTimeMS'=>1000));
			$arr = $mongodump->executeQuery('search.images', $query);
			foreach($arr as $doc){
				$data[] = $doc->text;
			}
		} else {
			$query = new MongoDB\Driver\Query(array('namespace' => (int)$THEWIKI_NOW_NAMESPACE, 'title' => array('$regex'=>"^".str_replace(array("(", ")"), array("\\(", "\\)"), $THEWIKI_NOW_TITLE_REAL))), array('limit'=>10, 'maxTimeMS'=>1000));
			$arr = $mongo->executeQuery('search.db', $query);
			if(!empty($THEWIKI_NOW_NAMESPACE_NAME)){
				$THEWIKI_NOW_NAMESPACE_NAME = $THEWIKI_NOW_NAMESPACE_NAME.":";
			}
			foreach($arr as $doc){
				$data[] = $THEWIKI_NOW_NAMESPACE_NAME.$doc->title;
			}
		}
		
		if(empty($data)){
			try{
				$query = new MongoDB\Driver\Query(array('$text'=>array('$search'=>$THEWIKI_NOW_TITLE_FULL)), array('limit'=>10, 'maxTimeMS'=>1000));
				$arr = $mongodump->executeQuery('db.collection', $query);
				$print = array();
				foreach($arr as $doc){
					if($doc->namespace>0){
						$doc->title = $THEWIKI_NAMESPACE_NAME[$doc->namespace].":".$doc->title;
					}
					$data[] = $doc->title;
				}
			} catch (MongoDB\Driver\Exception\Exception $e){
				// pass
			}
		}
		
		$data = array_unique($data);
		echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	} catch (MongoDB\Driver\Exception\Exception $e){
		die();
	}
?>
