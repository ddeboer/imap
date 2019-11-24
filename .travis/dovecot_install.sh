#!/bin/sh

set -ex

sudo apt-get -q update
sudo apt-get -q -y install dovecot-imapd

SSL_CERT="/etc/ssl/certs/dovecot.crt"
SSL_KEY="/etc/ssl/private/dovecot.key"

sudo openssl req -new -x509 -days 3 -nodes \
    -out "$SSL_CERT" \
    -keyout "$SSL_KEY" \
    -subj "/C=EU/ST=Europe/L=Home/O=Travis/OU=Travis DEV/CN=""$IMAP_SERVER_NAME"

sudo chown root:dovecot "$SSL_CERT" "$SSL_KEY"
sudo chmod 0440 "$SSL_CERT"
sudo chmod 0400 "$SSL_KEY"

DOVECOT_CONF="/etc/dovecot/local.conf"
MAIL_CONF="/etc/dovecot/conf.d/10-mail.conf"
IMAP_CONF="/etc/dovecot/conf.d/20-imap.conf"
QUOTA_CONF="/etc/dovecot/conf.d/90-quota.conf"
sudo touch "$DOVECOT_CONF" "$MAIL_CONF" "$IMAP_CONF" "$QUOTA_CONF"
sudo chown root:dovecot "$DOVECOT_CONF" "$MAIL_CONF" "$IMAP_CONF" "$QUOTA_CONF"
sudo chmod 0640 "$DOVECOT_CONF" "$MAIL_CONF" "$IMAP_CONF" "$QUOTA_CONF"

{
    echo "ssl = required"
    echo "disable_plaintext_auth = yes"
    echo "ssl_cert = <""$SSL_CERT"
    echo "ssl_key = <""$SSL_KEY"
    echo "ssl_protocols = !SSLv2 !SSLv3"
    echo "ssl_cipher_list = AES128+EECDH:AES128+EDH"
} | sudo tee -a "$DOVECOT_CONF"

{
    echo "mail_plugins = \$mail_plugins quota"
} | sudo tee -a "$MAIL_CONF"

{
    echo "protocol imap {"
    echo "  mail_plugins = \$mail_plugins imap_quota"
    echo "}"
} | sudo tee -a "$IMAP_CONF"

{
    echo "plugin {"
    echo "  quota = maildir:User quota"
    echo "  quota_rule = *:storage=1G"
    echo "  quota_rule2 = INBOX/Trash:storage=+100M"
    echo "  quota_grace = 10%%"
    echo "  quota_warning = storage=95%% quota-warning 95 %u"
    echo "  quota_warning2 = storage=80%% quota-warning 80 %u"
    echo "}"
} | sudo tee -a "$QUOTA_CONF"

sudo useradd --create-home --shell /bin/false "$IMAP_USERNAME"
echo "$IMAP_USERNAME"":""$IMAP_PASSWORD" | sudo chpasswd

sudo service dovecot restart

sudo doveadm auth test -x service=imap "$IMAP_USERNAME" "$IMAP_PASSWORD"
