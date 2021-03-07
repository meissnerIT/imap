# imap

## Interactive map for Zabbix, google map zabbix integration, geomap zabbix

This version is patched for Zabbix versions 5.0.

## Installation

Tested on Centos 7 :

* Clone the repository to `/usr/local/share/zabbix-interactive-map/imap/` on the zabbix server. 

* Link `imap` and `imap.php`:

```
cd /usr/share/zabbix
ln -s /usr/local/share/zabbix-interactive-map/imap/imap .
ln -s /usr/local/share/zabbix-interactive-map/imap/imap.php .
```

* Add the menu entry "Monitoring -> Interactive Map" to your zabbix installation by adding this line just before "->setAliases(['dashboard.list'])," (~line 33) in `/usr/share/zabbix/include/menu.inc.php`:

```
(new CMenuItem(_('Interactive map')))
	->setUrl(new CUrl('imap.php'), 'imap.php')
	->setAliases(['imap.php']),
```

* Add the zabbix MWP router entry to your zabbix installation by adding this line just before 	"'triggers.php'	=> ['CLegacyAction', null, null]," (~line 300) in `/usr/share/zabbix/include/classes/mvc/Crouter.php`:

```
'imap.php'	=> ['CLegacyAction', null, null],
```

For additional settings, locate file imap.php in the folder imap and change settings or code to your liking.

For working need change default hosts gropus id in file imap.php at line 18 like $defaultgroupid='42';

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

`sudo -u zabbix psql -U zabbix -W -d zabbix < tables-postgresql.sql`

where

sudo -u zabbix - act as system user 'zabbix' (otherwise PosgreSQL will not authenticate user),

-U zabbix - database owner,

-d zabbix - database name.

