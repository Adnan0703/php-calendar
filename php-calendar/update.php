<?php
/*
    Copyright 2002 Sean Proctor, Nathan Poiro

    This file is part of PHP-Calendar.

    PHP-Calendar is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    PHP-Calendar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PHP-Calendar; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('IN_PHPC', true);

$phpc_root_path = './';

require_once($phpc_root_path . "includes/calendar.php");
require_once($phpc_root_path . 'adodb/adodb.inc.php');
require_once($phpc_root_path . 'config.php');

// grab the DB info from the config file
if(defined('SQL_HOST')) {
        $sql_host = SQL_HOST;
        echo "<p>Your host is: $sql_host</p>";
} else {
        soft_error('No hostname found in your config file');
}

if(defined('SQL_USER')) {
        $sql_user = SQL_USER;
        echo "<p>Your SQL username is: $sql_user</p>";
} else {
        soft_error('No username found in your config file');
}

if(defined('SQL_PASSWD')) {
        $sql_passwd = SQL_PASSWD;
        echo "<p>Your SQL password is: $sql_passwd</p>";
} else {
        soft_error('No password found in your config file');
}

if(defined('SQL_DATABASE')) {
        $sql_database = SQL_DATABASE;
        echo "<p>Your SQL database name is: $sql_database</p>";
} else {
        soft_error('No database found in your config file');
}

if(defined('SQL_PREFIX')) {
        $sql_prefix = SQL_PREFIX;
        echo "<p>Your SQL table prefix is: $sql_prefix</p>";
} else {
        soft_error('No table prefix found in your config file');
}

if(defined('SQL_TYPE')) {
        $sql_type = SQL_TYPE;
} elseif(isset($dbms)) {
        $sql_type = $dbms;
} else {
        soft_error('No database type found in your config file');
}
echo "<p>Your database type is: $sql_type</p>";

// connect to the database
$db = NewADOConnection($sql_type);
$ok = $db->Connect($sql_host, $sql_user, $sql_passwd, $sql_database);
if(!$ok) {
        soft_error('Could not connect to the database');
}

if(false) {
        $query = "ALTER TABLE $mysql_tablename 
                ADD duration datetime AFTER stamp,
                    ADD eventtype int(4) AFTER duration;";
        $result = mysql_query($query)
                or die(mysql_error());

        $query = "UPDATE $mysql_tablename
                SET duration=stamp, eventtype=1;";
        $result = mysql_query($query)
                or die(mysql_error());
}

// update calendars
$query = "SELECT * FROM ".SQL_PREFIX."calendars LIMIT 0 , 1";
$result = $db->Execute($query) or db_error('Error in query', $query);
if($result->FieldCount() == 0) {
        soft_error("You cannot upgrade a DB with no events.");
}
$event = $result->FetchRow();
if(array_key_exists('calendar', $event)) {
        $calendarName = $event['calendar'];
} else {
        if(array_key_exists('calno', $event)) {
                $query = "ALTER TABLE ".SQL_PREFIX."calendars\n"
                        ."CHANGE calno calendar varchar(32)";
        } else {
                $query = "ALTER TABLE ".SQL_PREFIX."calendars\n"
                        ."ADD calendar varchar(32)";
        }
        $db->Execute($query) or db_error("Error in alter", $query);
        echo "<p>Updated calendar in calendar table</p>";
}

// update events
$query = "SELECT * FROM ".SQL_PREFIX."events LIMIT 0 , 1";
$result = $db->Execute($query) or db_error('Error in query', $query);
if($result->FieldCount() == 0) {
        soft_error("You cannot upgrade a DB with no events.");
}
$event = $result->FetchRow();
if(array_key_exists('calendar', $event)) {
        $calendarName = $event['calendar'];
} else {
        if(array_key_exists('calno', $event)) {
                $query = "ALTER TABLE ".SQL_PREFIX."events\n"
                        ."CHANGE calno calendar varchar(32)";
        } else {
                $query = "ALTER TABLE ".SQL_PREFIX."events\n"
                        ."ADD calendar varchar(32)";
        }
        $db->Execute($query) or db_error("Error in alter", $query);
        echo "<p>Updated calendar in events table</p>";
}
?>
