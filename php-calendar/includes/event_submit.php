<?php
/*
   Copyright 2002, 2005 Sean Proctor, Nathan Poiro

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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_submit()
{
	global $calendar_name, $day, $month, $year, $db, $vars, $config,
	       $phpc_script;

        /* Validate input */
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

	if(isset($vars['hour'])) {
                $hour = $vars['hour'];
	} else {
                soft_error(_('No hour was given.'));
        }

        if(!$config['hours_24'])
        {
                if (array_key_exists('pm', $vars) && $vars['pm']) {
                        if ($hour < 12) {
                                $hour += 12;
                        }
                } elseif($hour == 12) {
                        $hour = 0;
                }
        }

        if(array_key_exists('minute', $vars)) {
                $minute = $vars['minute'];
        } else {
                soft_error(_('No minute was given.'));
        }

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
				.". $config[subject_max] "._('characters max')
				.".");
	}

	$uid = get_uid($_SESSION['user']);

	$startstamp = mktime($hour, $minute, 0, $month, $day, $year);
	$endstamp = mktime(0, 0, 0, $end_month, $end_day, $end_year);

        if($endstamp < mktime(0, 0, 0, $month, $day, $year)) {
                soft_error(_('The start of the event cannot be after the end of the event.'));
        }
	$startdate = $db->DBDate($startstamp);
	$starttime = date('H:i:s', $startstamp);

	$enddate = $db->DBDate($endstamp);
	$duration = $duration_hour * 60 + $duration_min;

	if($modify) {
		if(!check_user() && $config['anon_permission'] < 2) {
			soft_error(_('You do not have permission to modify events.'));
		}
		$query = "UPDATE ".SQL_PREFIX."events\n"
			."SET startdate=$startdate,\n"
			."enddate=$enddate,\n"
			."starttime='$starttime',\n"
			."duration='$duration',\n"
			."subject='$subject',\n"
			."description='$description',\n"
			."eventtype='$typeofevent'\n"
			."WHERE id='$id'";

                $result = $db->Execute($query)
                        or db_error(_('Error creating event'), $query);

                $affected = $db->Affected_Rows($result);
                if($affected < 1) return tag('div', _('No changes were made.'));
	} else {
		if(!check_user() && $config['anon_permission'] < 1) {
			soft_error(_('You do not have permission to post.'));
		}
		$id = $db->GenID(SQL_PREFIX . 'sequence');
		$query = "INSERT INTO ".SQL_PREFIX."events\n"
			."(id, uid, type, time, "
                        ."duration, subject, description, "
                        ."calendar_id)\n"
			."VALUES ($id, $uid, $typeofevent, '$starttime', "
                        ."'$duration', '$subject', '$description', "
                        ."'$calendar_id')";
                $result = $db->Execute($query)
                        or db_error(_('Error creating event'), $query);

                $values = array('event_id' => $id);
                if(!empty($startdate)) {
                        $values['start_date'] = $startdate;
                }
                if(!empty($enddate)) {
                        $value['end_date'] = $enddate;
                }
                $query = "INSERT INTO ".SQL_PREFIX."occurrences
                        (".implode(', ', array_keys($values)).")
                        VALUES (".implode(', ', $values).")";

                $result = $db->Execute($query)
                        or db_error(_('Error creating occurrence'), $query);
	}


        session_write_close();

	header("Location: $phpc_script?action=display&id=$id");
	return tag('div', attributes('class="box"'), _('Date submitted'));
}

?>
