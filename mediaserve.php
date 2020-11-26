<?php

// CONFIG

define("PREFIX_PATH", "");		// NORMALLLY NOT UNDER PUBLIC DOC ROOT BUT ENSURE USER HAS PERMISSION
define("META_PATH"	, "meta/");
define("ALLO_PATH"	, "allo/");
define("FILE_PATH"	, "file/");
define("HASH_EXT"	, ".hash");

define("BLOCK_SIZE" , 65536);

define("MIN_BLOCKS"	, 20);
define("MAX_BLOCKS"	, 200);

if(!is_dir(PREFIX_PATH.META_PATH)) { mkdir(PREFIX_PATH.META_PATH); }
if(!is_dir(PREFIX_PATH.ALLO_PATH)) { mkdir(PREFIX_PATH.ALLO_PATH); }
if(!is_dir(PREFIX_PATH.FILE_PATH)) { mkdir(PREFIX_PATH.FILE_PATH); }


// FUNCTIONS

function zboxcmd($cmd, $params) {
	$zboxbinarypath = "/home/zcdn/.zcn/";
	$zboxbinarycmd = "zbox";
	$zcmd = $zboxbinarypath.$zboxbinarycmd." ".$cmd." ".implode(" ", $params);
	$arr = array();
	$dummy = exec($zcmd, $arr);
				
	$res = implode("<br>",$arr);
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
	}
	else
	{
		$metaRes = zboxcmd("meta", array( "--wallet", "config_webserver.json", "--authticket", $a, "--json" ) );
		file_put_contents($metaFile, $metaRes);
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
	}
	else
	{
		$alloRes = zboxcmd("get", array( "--wallet", "config_webserver.json", "--allocation", $allocationid, "--json" ) );
		file_put_contents($alloFile, $alloRes);
	}
	return($alloRes);
}


// MAIN

$authHash = FALSE;

$a = getAuth();

if(!$a)
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$minBlocks = MIN_BLOCKS;
if(isset($_GET['b'])) {
	$minBlocks = $_GET['b'];
}
if($minBlocks < 1) {
	$minBlocks = MIN_BLOCKS;
}
if($minBlocks > MAX_BLOCKS) {
	$minBlocks = MAX_BLOCKS;
}

$authHash = getHash($a);
	
$jsonauth = base64_decode($a);
$data = json_decode($jsonauth, TRUE);
$fileName = $data['file_name'];
$allocationId = $data['allocation_id'];

$metaRes = getMetaRes($a);

$fileData = json_decode($metaRes, TRUE);
$fileMimetype = $fileData['MimeType'];
$fileHash = $fileData['Hash'];
$fileSize = $fileData['Size'];

$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
$hashFileName = PREFIX_PATH.FILE_PATH.$fileHash.".".$fileExt; 
$alloRes = getAlloRes( $allocationId );
$alloData = json_decode($alloRes, TRUE);
$dataShards = $alloData['data_shards'];

$chunkMult = 1;
if($dataShards > 0) {
	while(($dataShards*$chunkMult) < $minBlocks) {
		$chunkMult++; 
	}
}
$chunkSize = $chunkMult * $dataShards * BLOCK_SIZE; // The size of each chunk to output
$numChunks = floor(($fileSize + $chunkSize - 1)/$chunkSize);


// Check if it's a HTTP range request
$rangerequested = isset($_SERVER['HTTP_RANGE']);
if($rangerequested) {
	// Parse the range header to get the byte offset
	$ranges = array_map(
		'intval', // Parse the parts into integer
		explode(
			'-', // The range separator
			substr($_SERVER['HTTP_RANGE'], 6) // Skip the `bytes=` part of the header
		)
	);
	// If the last range param is empty, it means the EOF (End of File)
	if(!$ranges[1]){
		$ranges[1] = $fileSize - 1;
	}
	$sendrange = TRUE;
}
else
{
	if($fileSize > $chunkSize) {
		$ranges = array();
		$ranges[0] = 0;
		$ranges[1] = $fileSize-1;
		$sendrange = TRUE;	
	}
	else
	{
		$sendrange = FALSE;
	}
}

// Clears the cache and prevent unwanted output
ob_clean();
@ini_set('error_reporting', E_ALL & ~ E_NOTICE);
@ini_set('zlib.output_compression', 'Off');

// Send the content type header
header('Content-type: ' . $fileMimetype);

$gotfile = FALSE;

if($sendrange){

	$chunkstart = $ranges[0];
	$chunkend = $ranges[1];

	$chunkend = ( floor(($chunkstart + $chunkSize ) / $chunkSize) * $chunkSize)-1;
	if($chunkend >= $fileSize) {
		$chunkend = $fileSize-1;
	}	
	
	// APPLE FIX?
	if ( ($ranges[0]==0) && ($ranges[1]==1) ) {
		$chunkend = 1;
	}
	$sendsize = $chunkend - $chunkstart + 1;	
	
	if(file_exists($hashFileName)) {
		$wholefile = TRUE;
	}
	else
	{
		$wholefile = FALSE;
	}
	
    if($wholefile) {
		// If already got whole file, makes no sense to download chunk so serve from whole file
		$f = fopen($hashFileName, 'rb');
	    fseek($f, $chunkstart);
	    $gotfile = TRUE;
  	}
	else
	{
	    // Haven't got whole file, so just download chunk that overrides start request falls in
	    $chunkno = floor($chunkstart / $chunkSize)+1;
		$chunkseekstart = $chunkstart % $chunkSize;
		$hashChunkFileName = $hashFileName.".".($dataShards*$chunkMult).".".$chunkno;

		if(!file_exists($hashChunkFileName)) {
			$chunkRes = zboxcmd("download", array( "--wallet", "config_webserver.json", "--authticket", $a, "--localpath", $hashChunkFileName, "--startblock", (($chunkno-1)*$chunkMult)+1, "--endblock", (($chunkno-1)*$chunkMult)+$chunkMult, "--blockspermarker", $chunkMult ) );
		}
		if(file_exists($hashChunkFileName)) {
			$gotfile = TRUE;
			$f = fopen($hashChunkFileName, 'rb');
		    fseek($f, $chunkseekstart);			
		}
	}
    if($gotfile)
    {
		// Send the appropriate headers
	    header('HTTP/1.1 206 Partial Content');
	    header('Accept-Ranges: bytes');
	    header('Content-Length: ' . ($sendsize)); // The size of the range
	
	    // Send the ranges we offered
	    header(
	        sprintf(
	            'Content-Range: bytes %d-%d/%d', // The header format
	            $chunkstart, // The start range
	            $chunkend, // The end range
	            $fileSize // Total size of the file
	        )
	    );
	    echo fread($f, $sendsize);
	}
	else
	{
		// Haven't successfully got file/chunk so return error. (Subsequent requests are permissible)
		header("HTTP/1.1 404 Not Found");
	}
}
else
{	
	// WHOLE FILE REQUESTED (may not be partial compatible)
	if(!file_exists($hashFileName)) {
		// Doesn't exist so get whole file
		$chunkRes = zboxcmd("download", array( "--wallet", "config_webserver.json", "--authticket", $a, "--localpath", $hashFileName ) );
	}
	if(file_exists($hashFileName)) {
		$gotfile = TRUE;
		//$f = fopen($hashFileName, 'rb');
	    //fseek($f, 0);
	}

    if($gotfile)
    {
	    header('HTTP/1.1 200 OK');
	    header('Content-Length: ' . $fileSize);
	    // SERVE WHOLE FILE
	    @readfile($hashFileName);
	}
	else
	{
		// Haven't successfully got file so return error. (Subsequent requests are permissible)
		header("HTTP/1.1 404 Not Found");
	}
}
// and flush the buffer
@ob_flush();
flush();
