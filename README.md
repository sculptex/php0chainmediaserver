# php0chainmediaserver
Media Server for 0chain, serves media files via partial content where allowed

## Use
  mediaserve.php?a=xx&b=nn&network=beta

a = authticket
b = minblocksize
network = network(.yaml) config file to be used ( ~/.zcn folder )

## Notes
  Adjust file paths accordingly
  file paths must be accessible/writeable by web user
  
## Pre-Requisites
* zbox CLI binary and network config(s) already present and functional in ~.zcn folder, again with user permissions
* Default to networks:- beta.yaml, dev.yaml, test.yaml

# Update

* Split out files:-
  * config.php - Editable config file
  * functions.php - Re-usable functions
  * cron.php - Removes old file chunks
* cron.php (to delete older file chunks) can be called from main script by setting CRONDEMAND in config.php
* Wallet auto-created if doesn't exist
