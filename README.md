# CollaboraOnline user counter

Used to measure the amount of unique users using CollaboraOnline.
These two scripts can be used as a standalone or integrated into zabbix.

## Installation
- Clone the repo
- Run `composer install`
- Copy config.sample.php `cp config.sample.php config.php`
- Change config if needed

## Data gathering

Run regularly this script (for example every 5 minutes via crontab)

```
php cool_current_user_count.php https://my-collabora-instance.localnet username password
```
Output example:
```
15
```

It will store its data into the sqlite database created at the script location.

## Summary

Run once this script to display the amount of unique users that has used CollaboraOnline in the last year.

```
php cool_unique_users.php https://my-collabora-instance.localnet
```
Output example:
```
268
```
If the Collabora Online instance is used by multiple WopiHosts, you can filter in the wanted one.
```
php cool_unique_users.php https://my-collabora-instance.localnet my-wopi-host.localnet
```

## Zabbix integration
- Make sure your zabbix server has:
  - php
  - php_sqlite

Create a php symlink and clone this repository in the `externalscripts` directory of the zabbix server.

```
cd /usr/lib/zabbix/externalscripts/
sudo ln -s /usr/bin/php
```
```
git clone https://github.com/arawa/Collabora-seats-counter.git
cd Collabora-seats-counter
composer install
```
Change permissions if needed
```
sudo chown -R zabbix: /usr/lib/zabbix/externalscripts/Collabora-seats-counter/
sudo chown zabbix: /usr/lib/zabbix/externalscripts/php
```

### Zabbix web ui

- In a template, create two items

- Name: `CollaboraOnline current users`
  - Type: `External check`
  - Key: `php["/usr/lib/zabbix/externalscripts/Collabora-seats-counter/cool_current_user_count.php","{$WEB_URL}","{$WEB_URL_USER}" ,"{$WEB_URL_PASSWORD}"]`
  - Type of information: `Numeric (unsigned)`
  - Update interval: `1m`
  - Tags: `Application`: `CollaboraOnline`

- Name: `CollaboraOnline seats used`
  - Type: `External check`
  - Key: `php["/usr/lib/zabbix/externalscripts/Collabora-seats-counter/cool_unique_users.php","{$WEB_URL}"]`
  - Type of information: `Numeric (unsigned)`
  - Update interval: `5m`
  - Tags: `Application`: `CollaboraOnline`

On each hosts, if not already, define the following macros:
- `{$WEB_URL}`: full path to the CollaboraOnline instance, eg: `https://my-collabora-instance.localnet`
- `{$WEB_URL_USER}`: admin username, eg: `cool`
- `{$WEB_URL_PASSWORD}`: admin password, eg: `my5up3rP4ssW0rD`
