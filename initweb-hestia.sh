source config.sh

v-add-letsencrypt-host

chown $user:$user *.php
rm /home/$user/web/$url/public_html/*
cp *.php /home/$user/web/$url/public_html
cp /home/$user/web/$url/public_html/mediaserve.php /home/$user/web/$url/public_html/index.php
