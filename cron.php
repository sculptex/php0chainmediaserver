<?php
if(!defined("CONFIG")) {
	include("config.php");
}
$crondemand=CRONDEMAND;
if(isset($_GET["crondemand"])) {
	$crondemand = $_GET["crondemand"];
}
if(!$crondemand) { echo "<br><b>CRON</b><br>"; }
foreach($nets as $net) {
	$fileList = glob($net."/file/*");
	foreach($fileList as $fileName){
		if(is_file($fileName)) {
			$filemtime = filemtime($fileName);		
			if(substr($fileName,-2)==".x") {
				if(!$crondemand) { echo "X ".$fileName."<br>"; }
				// DELETE
				if( (time() - $filemtime) > DEL_AGE) {
					unlink($fileName);
					if(!$crondemand) { echo "DELETED ".$fileName."<br>"; }
				}
			}
			else
			{
				// FLAG FOR DELETION
				if(!$crondemand) { echo $fileName."<br>"; }
				if((time() - $filemtime) > FLAG_AGE) {
					rename($fileName, $fileName.".x");
					if(!$crondemand) { echo "FLAGGED ".$fileName." for deletion<br>"; }
				}
			}
		}   
	}
}
