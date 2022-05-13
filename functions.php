<?php

function op($html) {
	global $authHash;
	if($authHash) {
		$debugFile = PREFIX_PATH.DEBUG_PATH.$authHash.DEBUG_EXT;
		if(file_exists($debugFile)) { $debug = file_get_contents($debugFile); } else { $debug = ""; }
		$debug = $debug.NL.$html;
		file_put_contents($debugFile, $debug);
	}
}

function showfilebytes($bytes) {
	return(number_format($bytes,0));		
}

function showfilesize($bytes) {
	$str = $bytes." B";
	if($bytes >= 1024) {
		$str = round($bytes / 1024 , 0)." KB";
	}
	if($bytes >= 1024*1024) {
		$str = round($bytes / (1024*1024) , 1)." MB";
	}
	if($bytes >= 1024*1024*1024) {
		$str = round($bytes / (1024*1024*1024) , 2)." GB";
	}
	if($bytes >= 1024*1024*1024*1024) {
		$str = round($bytes / (1024*1024*1024*1024) , 3)." TB";
	}
	if($bytes >= 1024*1024*1024*1024*1024) {
		$str = round($bytes / (1024*1024*1024*1024*1024) , 3)." PB";
	}
	return($str);	
}

function zboxcmd($cmd, $params) {
	
	global $network;
	
	$zboxbinarypath = ZCN_PATH."/";
	$zboxbinarycmd = "zbox";

	$params = array_merge( array("--config", $network.".yaml", "--wallet", $network."_webserver.json"), $params);

	$zcmd = $zboxbinarypath.$zboxbinarycmd." ".$cmd." ".implode(" ", $params);
	$arr = array();

	$dummy = exec($zcmd, $arr);
		
	$cmdLogFile = PREFIX_PATH.CMDLOG_PATH.CMDLOG_NAME.LOG_EXT;
	if(file_exists($cmdLogFile)) { $cmdLog = file_get_contents($cmdLogFile); } else { $cmdLog = ""; }
	$cmdLog = "CMD ".$zcmd.NL.$cmdLog;
	file_put_contents($cmdLogFile, $cmdLog);
		
	$res = implode(NL,$arr);
	if($res == "") {
		$res = $dummy;
	}
	return($res);
}

function getAuth() {
	if(isset($_GET['a'])) {
		$a = $_GET['a'];
	}
	else
	{
		$a = FALSE;
	}
	return($a);
}

function getHash($h) {
	return(md5($h));
}

function getMetaRes($a) {
	// CACHE AUTHTICKET BY ITS HASH
		
	$authHash = getHash($a);
	$metaFile = PREFIX_PATH.META_PATH.$authHash.HASH_EXT;
	if(file_exists($metaFile)) {
		$metaRes = file_get_contents($metaFile);
		op("GETMETARES ".$a);
	}
	else
	{
		$metaRes = zboxcmd("meta", array("--authticket", $a, "--json" ) );
		file_put_contents($metaFile, $metaRes);
		op("NEWMETARES ".$a);
	}
	return($metaRes);	
}

function getAlloRes($allocationid) {
	// CACHE ALLOCATION BY ITS HASH
		
	$alloHash = $allocationid;
	if(!is_dir(PREFIX_PATH.ALLO_PATH)) {
		mkdir(PREFIX_PATH.ALLO_PATH);
	}
	$alloFile = PREFIX_PATH.ALLO_PATH.$alloHash.HASH_EXT;
	if(file_exists($alloFile)) {
		$alloRes = file_get_contents($alloFile);
		op("GETALLORES ".$allocationid);
	}
	else
	{
		$alloRes = zboxcmd("get", array("--allocation", $allocationid, "--json --silent" ) );
		file_put_contents($alloFile, $alloRes);
		op("NEWALLORES ".$allocationid);
	}
	return($alloRes);
}
