<?php

// CONFIG

include("config.php");

// FUNCTIONS

include("functions.php");

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
			$chunkRes = zboxcmd("download", array( "--authticket", $a, "--localpath", $hashChunkFileName, "--startblock", (($chunkno-1)*$chunkMult)+1, "--endblock", (($chunkno-1)*$chunkMult)+$chunkMult, "--blockspermarker", $chunkMult ) );
		}
		if(file_exists($hashChunkFileName)) {
			touch($hashChunkFileName);	// refresh file time stamp to delay deletion from cache
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
		$chunkRes = zboxcmd("download", array("--authticket", $a, "--localpath", $hashFileName ) );
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

if(defined("CRONDEMAND") && CRONDEMAND) {
	include("cron.php");
}
