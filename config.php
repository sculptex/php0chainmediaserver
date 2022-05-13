<?php

$nets = array("beta","test","dev");

$choosenetwork = FALSE;
if(!isset($network)) {
	if(isset($_GET['network'])){
		$network = $_GET['network'];
	}
	else
	{
		$network = $nets[0];	// First network in list default choice
		$choosenetwork = TRUE;
	}
}

define("CONFIG"		, true);	// Prevent crondemand loading config twice

define("CRONDEMAND"	, false);	// Set to true to auto-delete

define("PREFIX_PATH", $network."/");
define("META_PATH"	, "meta/");
define("ALLO_PATH"	, "allo/");
define("LOG_PATH"	, "log/");
define("CMDLOG_PATH", "cmd/");
define("CMDLOG_NAME", "cmdlog");
define("DEBUG_PATH"	, "debug/");
define("FILE_PATH"	, "file/");
define("HASH_EXT"	, ".hash");
define("LOG_EXT"	, ".log");
define("DEBUG_EXT"	, ".log");

define("NL"			, "\n");

define("BLOCK_SIZE" , 65536);

define("ZCN_PATH"	, "/home/admin/.zcn");
define("MIN_BLOCKS"	, 10);
define("MAX_BLOCKS"	, 200);
define("FLAG_AGE"	, 60);		// Seconds. Set to 0 to not cache
define("DEL_AGE"	, 120);		// Seconds. Set to 0 to not cache

if(!is_dir($network)) { mkdir($network); }
if(!is_dir(PREFIX_PATH.META_PATH)) { mkdir(PREFIX_PATH.META_PATH); }
if(!is_dir(PREFIX_PATH.ALLO_PATH)) { mkdir(PREFIX_PATH.ALLO_PATH); }
if(!is_dir(PREFIX_PATH.LOG_PATH)) { mkdir(PREFIX_PATH.LOG_PATH); }
if(!is_dir(PREFIX_PATH.CMDLOG_PATH)) { mkdir(PREFIX_PATH.CMDLOG_PATH); }
if(!is_dir(PREFIX_PATH.DEBUG_PATH)) { mkdir(PREFIX_PATH.DEBUG_PATH); }
if(!is_dir(PREFIX_PATH.FILE_PATH)) { mkdir(PREFIX_PATH.FILE_PATH); }
