source config.sh

wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh

sudo bash hst-install.sh --interactive no --email $email --password $password --hostname $url --mysql no --phpfpm yes --multiphp no --apache no --named no --exim no --dovecot no --sieve no --clamav no --spamassassin no --iptables no --fail2ban no --quota no --api no -f
sed -i "s|/home/%user%/.composer:/home/%user%/web/|/home/%user%/.composer:/home/%user%/.zcn:/home/%user%/web/|" /usr/local/hestia/data/templates/web/php-fpm/default.tpl
