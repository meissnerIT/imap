# imap

## Interactive map for Zabbix

This version is patched for Zabbix versions 4.0.

## Installation

Tested on Debian 8 (Jessie):

* Clone the repository to `/usr/local/share/zabbix-interactive-map-19730/imap/` on the zabbix server. 

* Link `imap` and `imap.php`:

```
cd /usr/share/zabbix
sudo ln -s ../../local/share/zabbix-interactive-map-19730/imap/zabbix/imap .
sudo ln -s ../../local/share/zabbix-interactive-map-19730/imap/zabbix/imap.php .
```

* Add the menu entry "Monitoring -> Interactive Map" to your zabbix installation by adding this line just before "$denied_page_requested = false;" (~line 304) in `/usr/share/zabbix/include/menu.inc.php`:

```
    require_once dirname(__FILE__).'/../imap/menu3.inc.php';
```

For additional settings, locate file settings.js.template in the folder imap, rename it in settings.js and change settings to your liking.

To get an API key for Bing you need to get a Microsoft account and create a new key. Look it for details: http://msdn.microsoft.com/ru-ru/library/ff428642.aspx

For work hardware icons, put png-images in folder imap/hardware. Look at file imap/hardware/readme.md for details.

## BD-additions

For working host's links, we need to add two tables in the database Zabbix.

Look at file imap/tables-xxx.sql

### For MySQL:

You can open phpmyadmin, select the database Zabbix, and select this file in the Import section

The second way for fans of the command line:

`mysql -u user -p zabbixbd < /usr/share/zabbix/imap/tables-mysql.sql`

Replace zabbixbd the name of the table with the data zabbix, username for a user with the addition of tables in the database and enter the password.

### For PostgreSQL 

run under root:

`sudo -u zabbix psql -U zabbix -W -d zabbix < table-postgresql.sql`

where

sudo -u zabbix - act as system user 'zabbix' (otherwise PosgreSQL will not authenticate user),

-U zabbix - database owner,

-d zabbix - database name.

