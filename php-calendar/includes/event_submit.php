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

function event_submit()
{
	global $calendar_name, $day, $month, $year, $db, $vars, $config;

	if(isset($vars['id'])) {
		$id = $vars['id'];
		$modify = 1;
	} else {
		$modify = 0;
	}

	if(isset($vars['description'])) {
		$description = ereg_replace('<[bB][rR][^>]*>', "\n", 
				$vars['description']);
	} else {
		$description = '';
	}

	if(isset($vars['subject'])) {
		$subject = addslashes(ereg_replace('<[^>]*>', '', 
					$vars['subject']));
	} else {
		$subject = '';
	}

	if(isset($vars['description'])) {
		$description = addslashes(ereg_replace('</?([^aA/]|[a-zA-Z_]{2,})[^>]*>',
					'', $vars['description']));
	} else {
		$description = '';
	}

	if(empty($vars['day'])) soft_error(_('No day was given.'));

	if(empty($vars['month'])) soft_error(_('No month was given.'));

	if(empty($vars['year'])) soft_error(_('No year was given'));

	if(isset($vars['hour'])) $hour = $vars['hour'];
	else soft_error(_('No hour was given.'));

	if(isset($vars['pm']) && $vars['pm'] == 1) $hour += 12;

	if(isset($vars['minute'])) $minute = $vars['minute'];
	else soft_error(_('No minute was given.'));

	if(isset($vars['durationmin']))
		$duration_min = $vars['durationmin'];
	else soft_error(_('No duration minute was given.'));

	if(isset($vars['durationhour']))
		$duration_hour = $vars['durationhour'];
	else soft_error(_('No duration hour was given.'));

	if(isset($vars['typeofevent']))
		$typeofevent = $vars['typeofevent'];
	else soft_error(_('No type of event was given.'));

	if(isset($vars['endday']))
		$end_day = $vars['endday'];
	else soft_error(_('No end day was given'));

	if(isset($vars['endmonth']))
		$end_month = $vars['endmonth'];
	else soft_error(_('No end month was given'));

	if(isset($vars['endyear']))
		$end_year = $vars['endyear'];
	else soft_error(_('No end year was given'));

	if(strlen($subject) > $config['subject_max']) {
		soft_error(_('Your subject was too long')
		.". $config[subject_max] ".('characters max').".");
	}

	$uid = check_user();

	$startstamp = mktime($hour, $minute, 0, $month, $day, $year);
	$startdate = $db->DBDate($startstamp);
	$starttime = date('H:i:s', $startstamp);

	$endstamp = mktime(0, 0, 0, $end_month, $end_day, $end_year);
	$enddate = $db->DBDate($endstamp);
	$duration = $duration_hour * 60 + $duration_min;

	$table = SQL_PREFIX . 'events';

	if($modify) {
		if(!check_user() && $config['anon_permission'] < 2) {
			soft_error('You do not have permission to modify events.');
		}
		$query = "UPDATE $table\n"
			."SET startdate=$startdate,\n"
			."enddate=$enddate,\n"
			."starttime='$starttime',\n"
			."duration='$duration',\n"
			."subject='$subject',\n"
			."description='$description',\n"
			."eventtype='$typeofevent'\n"
			."WHERE id='$id'";
	} else {
		if(!check_user() && $config['anon_permission'] < 1) {
			soft_error('You do not have permission to post.');
		}
		$id = $db->GenID(SQL_PREFIX . 'sequence');
		$query = "INSERT INTO $table\n"
			."(id, uid, startdate, enddate, starttime, duration,"
			." subject, description, eventtype, calendar)\n"
			."VALUES ($id, '$uid', $startdate, $enddate,"
			."'$starttime', '$duration', '$subject',"
			."'$description', '$typeofevent', '$calendar_name')";
	}

	$result = $db->Execute($query);

	if(!$result) {
		db_error(_('Error processing event'), $query);
	}

	$affected = $db->Affected_Rows($result);
	if($affected < 1) soft_error(_('No changes made')."\nsql:\n$query");

	header("Location: $_SERVER[SCRIPT_NAME]?action=display&id=$id");
	return tag('div', attributes('class="box"'), _('Date updated').": $affected");
}

?>
