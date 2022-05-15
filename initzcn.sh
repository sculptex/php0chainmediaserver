source config.sh

cd /home/$user
mkdir .zcn
cd .zcn
wget https://github.com/0chain/zboxcli/releases/download/v1.3.4/zbox-linux.tar.gz
tar -xvf zbox-linux.tar.gz
wget https://github.com/0chain/zwalletcli/releases/download/v1.1.1/zwallet-linux.tar.gz
tar -xvf zwallet-linux.tar.gz
rm *.tar.gz
wget https://raw.githubusercontent.com/0chain/zwalletcli/staging/network/one.yaml
mv one.yaml config.yaml
sed -i "s|^block_worker: .*$|block_worker: https://beta.0chain.net/dns|" config.yaml
cp config.yaml beta.yaml
cp config.yaml dev.yaml
cp config.yaml test.yaml
sed -i "s|^block_worker: .*$|block_worker: https://dev.0chain.net/dns|" dev.yaml
sed -i "s|^block_worker: .*$|block_worker: https://test.0chain.net/dns|" test.yaml
cd ..
chown $user:$user .zcn -R
