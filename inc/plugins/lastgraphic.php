<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
$plugins->add_hook('admin_config_settings_change', 'lastgraphic_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'lastgraphic_settings_peek');
$plugins->add_hook("build_forumbits_forum", "lastgraphic_forumbit"); 
$plugins->add_hook('forumdisplay_thread_end', 'lastgraphic_thread');

// Die Informationen, die im Pluginmanager angezeigt werden
function lastgraphic_info(){
	return array(
		"name"		=> "Grafik des letzten Posters",
		"description"	=> "Erweitert die Informationen vom letzten Poster um eine Grafikanzeige.",
		'website'	=> 'https://github.com/little-evil-genius/Grafik-des-letzten-Posters',
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function lastgraphic_install(){
    global $db, $cache, $mybb;

	// EINSTELLUNGEN HINZUFÜGEN
    $maxdisporder = $db->fetch_field($db->query("SELECT MAX(disporder) FROM ".TABLE_PREFIX."settinggroups"), "MAX(disporder)");
	$setting_group = array(
		'name'          => 'lastgraphic',
		'title'         => 'Grafik des letzten Posters',
		'description'   => 'Einstellungen für die Grafik des letzten Posters',
		'disporder'     => $maxdisporder,
		'isdefault'     => 0
	);
			
	$gid = $db->insert_query("settinggroups", $setting_group); 
			
	$setting_array = array(
		'lastgraphic_graphic' => array(
			'title' => 'Grafiktyp',
            'description' => 'Welche Grafik soll vom letzten Poster angezeigt werden? Zur Auswahl steht klassisch der Avatar, ein Element aus dem Uploadsystem von little.evil.genius, ein klassisches Profilfeld oder ein Feld aus dem Steckbriefplugin von risuena.',
            'optionscode' => 'select\n0=Avatar\n1=Upload-Element\n2=Profilfeld\n3=Steckbrieffeld',
            'value' => 'Avatar', // Default
            'disporder' => 1
		),
		'lastgraphic_uploadsystem' => array(
			'title' => 'Identifikator von dem Upload-Element',
            'description' => 'Wie lautet der Identifikator von dem Upload-Element, welches genutzt werden soll als Grafik des letzten Posters?',
            'optionscode' => 'text',
            'value' => 'index', // Default
            'disporder' => 2
		),
		'lastgraphic_profilefield' => array(
			'title' => 'FID von dem Profilfeld',
            'description' => 'Wie lautet die FID von dem Profilfeld, welches genutzt werden soll als Grafik des letzten Posters?',
            'optionscode' => 'numeric',
            'value' => '6', // Default
            'disporder' => 3
		),
		'lastgraphic_characterfield' => array(
			'title' => 'Identifikator von dem Steckbrieffeld',
            'description' => 'Wie lautet der Identifikator von dem Steckbrieffeld, welches genutzt werden soll als Grafik des letzten Posters?',
            'optionscode' => 'text',
            'value' => 'index_pic', // Default
            'disporder' => 4
		),
		'lastgraphic_defaultgraphic' => array(
			'title' => 'Standard-Grafik',
            'description' => 'Wie heißt die Bilddatei für die Standard-Grafik? Diese Grafik wird, falls ein Mitglied noch keine entsprechende Grafik besitzt, stattdessen angezeigt. Damit die Grafik für jedes Design angepasst wird, sollte der Dateiname in allen Ordner für die Designs gleich heißen.',
            'optionscode' => 'text',
            'value' => 'default_avatar.png', // Default
            'disporder' => 5
		),
        'lastgraphic_guest' => array(
            'title' => 'Gäste-Ansicht',
            'description' => 'Sollen die Grafik vor Gästen versteckt werden? Statt der Grafik wird die festgelegte Standard-Grafik angezeigt.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 6
        ),
        'lastgraphic_specialgraphic' => array(
            'title' => 'Spezielle Accounts',
            'description' => 'Gibt es Accounts, wo sich die Grafik je nach Design verändern soll? Wie zum Beispiel der Admin-Account oder ein NPC-Account. Liste hier die UIDs mit einem , auf. Falls nicht benötigt einfach frei lassen.<br>
<b>Wichtig</b> Der Dateiname für die Grafik wird aus dem Accountnamen gezogen. z.B. "Admin" -> admin oder "The Devil" -> thedevil. PNG, JPG, JPEG und GIF sind als Dateiformat möglich. Umlaute und ß werden umgeformt. ` oder \\\' werden entfernt.',
            'optionscode' => 'text',
            'value' => '1,2', // Default
            'disporder' => 7
        ),
        'lastgraphic_specialetters' => array(
            'title' => 'spezielle Buchstaben',
            'description' => 'Die gängigsten Umlaute ä,ö und ü sowie das ß werden schon standardmäßig umgeformt. Doch gibt es noch weitere Buchstaben, die nicht im deutschen Alphabet vorkommen. Falls einer der angegebenen speziellen Accounts solche Buchstaben besitzt, müssen diese auch umgeformt werden. Hier werden die einzelnen Buchstaben definiert. Wenn nicht vorhanden, dann freilassen.<br>
Folgendes Schema: å = a; ó = o; ð = d',
            'optionscode' => 'textarea',
            'value' => 'å = a; ó = o; ð = d', // Default
            'disporder' => 8
        ),
	);
			
	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid']  = $gid;
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

    // TEMPLATE ERSTELLEN
    $insert_array = array(
        'title'		=> 'forumbit_depth2_forum_lastpost_graphic',
        'template'	=> $db->escape_string('<img src="{$forum[\'lastpostergraphicurl\']}" style="height: 50px;">'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'		=> 'forumdisplay_thread_lastpost_graphic',
        'template'	=> $db->escape_string('<img src="{$thread[\'lastpostergraphicurl\']}" style="height: 50px;"><br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function lastgraphic_is_installed(){
	global $mybb;

    if(isset($mybb->settings['lastgraphic_specialgraphic']))
    {
        return true;
    }
    return false;
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function lastgraphic_uninstall(){
	
    global $db;
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'lastgraphic%'");
    $db->delete_query('settinggroups', "name = 'lastgraphic'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title IN('forumbit_depth2_forum_lastpost_graphic', 'forumdisplay_thread_lastpost_graphic')");
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function lastgraphic_activate(){
    
    // VARIABLE EINFÜGEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("forumbit_depth2_forum_lastpost", "#".preg_quote('<span class="smalltext">')."#i", '{$forum[\'lastgraphic\']}<span class="smalltext">');
	find_replace_templatesets("forumdisplay_thread", "#".preg_quote('<span class="lastpost smalltext">{$lastpostdate}<br />')."#i", '{$thread[\'lastgraphic\']}<span class="lastpost smalltext">{$lastpostdate}<br />');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function lastgraphic_deactivate(){

    // VARIABLE ENTFERNEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumbit_depth2_forum_lastpost", "#".preg_quote('{$forum[\'lastgraphic\']}')."#i", '', 0);
    find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$thread[\'lastgraphic\']}')."#i", '', 0);
}

// ADMIN-CP PEEKER
function lastgraphic_settings_change(){
    
    global $db, $mybb, $lastgraphic_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='lastgraphic'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $lastgraphic_settings_peeker = ($mybb->get_input('gid') == $group['gid']) && ($mybb->request_method != 'post');
}
function lastgraphic_settings_peek(&$peekers){

    global $mybb, $lastgraphic_settings_peeker;

    // Geburtstag
	if ($lastgraphic_settings_peeker) {
        $peekers[] = 'new Peeker($("#setting_lastgraphic_graphic"), $("#row_setting_lastgraphic_uploadsystem"),/^1/,false)';
    }
	if ($lastgraphic_settings_peeker) {
        $peekers[] = 'new Peeker($("#setting_lastgraphic_graphic"), $("#row_setting_lastgraphic_profilefield"),/^2/,false)';
    }
    if ($lastgraphic_settings_peeker) {
        $peekers[] = 'new Peeker($("#setting_lastgraphic_graphic"), $("#row_setting_lastgraphic_characterfield"),/^3/,false)';
    }
}

// FORUM BIT
function lastgraphic_forumbit(&$forum){
    
    global $db, $mybb, $templates, $theme;

    // EINSTELLUNGEN ZIEHEN
    // Grafiktyp
    $graphictyp = $mybb->settings['lastgraphic_graphic'];
    // Uploadsystem
    $uploadsystem_graphic = $mybb->settings['lastgraphic_uploadsystem'];
    // Profilfeld
    $profilefield_graphic = $mybb->settings['lastgraphic_profilefield'];
    // Steckifeld
    $characterfield_graphic = $mybb->settings['lastgraphic_characterfield'];
    // Standard-Avatar
    $defaultgraphic = $mybb->settings['lastgraphic_defaultgraphic'];
    // Gäste
    $guest_setting = $mybb->settings['lastgraphic_guest'];
    // Spezielle Accounts
    $specialgraphic_setting = str_replace(", ", ",", $mybb->settings['lastgraphic_specialgraphic']);
    // Spezielle Buchstaben
    $lastgraphic_specialletters = $mybb->settings['lastgraphic_specialetters'];

    // UMLAUTE UMFORMEN
    $classicletters = Array("Ä" => "AE", "Ö" => "OE", "Ü" => "UE", "ä" => "ae", "ö" => "oe", "ü" => "ue", "ß" => "ss", "'" => "", "`" => "", "´" => "");
    if (!empty($lastgraphic_specialletters)) {
        $specialletters_setting = str_replace("; ", ";", $lastgraphic_specialletters);
        $specialletters_array = specialetters_array($specialletters_setting);
    
        $all_specialletters = array_merge($specialletters_array, $classicletters);
    } else {
        $all_specialletters = $classicletters;
    }

	// Eigene UID
	$activeuser_uid = intval($mybb->user['uid']);

    $lastposteruid = get_last_poster_uid($forum['fid']);

    if ($lastposteruid == 0) {
        $forum['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
    } else {

        $lastposteravatar_query = $db->query("SELECT * FROM ".TABLE_PREFIX."users
        WHERE uid = '".$lastposteruid."'
        ");

        while ($lastpost = $db->fetch_array($lastposteravatar_query)) {

            // Gäste bekommen nur Default Avatar -> wenn aktiviert
            if ($activeuser_uid == 0 && $guest_setting == 1) {
                $forum['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
            } else {
    
                // spezielle Accounts 
                $pos = strpos(",".$specialgraphic_setting.",", ",".$lastposteruid.",");

                // letzter Poster ist ein spezieller Account - eigene Grafiken
                if ($pos !== false) {
    
                    // Leerlaufen lassen
                    $username = ""; 
    
                    // Username
                    $username = $lastpost['username'];
    
                    // Umlaute entfernen
                    $username = strtr($username, $all_specialletters);
                    // alles klein schreiben
                    $username = strtolower($username);
                    // leerzeichen entfernen
                    $username = str_replace(" ", "", $username);

                    $forum['lastpostergraphicurl'] = find_graphic_format($username);
    
                }
                // normaler User - normale Grafik
                else {
    
                    // Leerlaufen lassen
                    $lastgraphic = "";

                    // Avatar
                    if ($graphictyp == 0) {
                        $lastgraphic = $lastpost['avatar'];
                    } 
                    // Uploadsystem
                    else if ($graphictyp == 1) {
                        $path = $db->fetch_field($db->simple_select("uploadsystem", "path", "identification = '".$uploadsystem_graphic."'"), "path");                  
                        $value = $db->fetch_field($db->simple_select("uploadfiles", $uploadsystem_graphic, "ufid = '".$lastposteruid."'"), $uploadsystem_graphic);

                        if ($value != "") {
                            $lastgraphic = $path."/".$value;
                        } else {
                            $lastgraphic = "";
                        }
                    }
                    // Profilfelder
                    else if ($graphictyp == 2) {
                        $fid = "fid".$profilefield_graphic;
                        $lastgraphic = $db->fetch_field($db->simple_select("userfields", $fid, "ufid = '".$lastposteruid."'"), $fid);
                    }
                    // Steckifelder
                    else if ($graphictyp == 3) {	
                        $fieldid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$characterfield_graphic."'"), "id");                  
                        $value = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$lastposteruid."' AND fieldid = '".$fieldid."'"), "value");

                        if ($value != "") {
                            $lastgraphic = $value;
                        } else {
                            $lastgraphic = "";
                        }
                    }
    
                    // wenn man kein Avatar hat => Default
                    if ($lastgraphic == "") {
                        // Dateinamen bauen
                        $forum['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
                    } else {
                        // Dateinamen bauen
                        $forum['lastpostergraphicurl'] = $lastgraphic;
                    }
    
                }

            }
        }
    }

    eval('$forum[\'lastgraphic\'] = "'.$templates->get('forumbit_depth2_forum_lastpost_graphic').'";');
}

// FORUM DISPLAY
function lastgraphic_thread() {

    global $templates, $mybb, $db, $thread, $theme;

    // EINSTELLUNGEN ZIEHEN
    // Grafiktyp
    $graphictyp = $mybb->settings['lastgraphic_graphic'];
    // Uploadsystem
    $uploadsystem_graphic = $mybb->settings['lastgraphic_uploadsystem'];
    // Profilfeld
    $profilefield_graphic = $mybb->settings['lastgraphic_profilefield'];
    // Steckifeld
    $characterfield_graphic = $mybb->settings['lastgraphic_characterfield'];
    // Standard-Avatar
    $defaultgraphic = $mybb->settings['lastgraphic_defaultgraphic'];
    // Gäste
    $guest_setting = $mybb->settings['lastgraphic_guest'];
    // Spezielle Accounts
    $specialgraphic_setting = str_replace(", ", ",", $mybb->settings['lastgraphic_specialgraphic']);
    // Spezielle Buchstaben
    $lastgraphic_specialletters = $mybb->settings['lastgraphic_specialetters'];

    // UMLAUTE UMFORMEN
    $classicletters = Array("Ä" => "AE", "Ö" => "OE", "Ü" => "UE", "ä" => "ae", "ö" => "oe", "ü" => "ue", "ß" => "ss", "'" => "", "`" => "", "´" => "");
    if (!empty($lastgraphic_specialletters)) {
        $specialletters_setting = str_replace("; ", ";", $lastgraphic_specialletters);
        $specialletters_array = specialetters_array($specialletters_setting);
    
        $all_specialletters = array_merge($specialletters_array, $classicletters);
    } else {
        $all_specialletters = $classicletters;
    }

	// Eigene UID
	$activeuser_uid = intval($mybb->user['uid']);

	// UID letzter Post
    $lastposteruid = $thread['lastposteruid'];

    if ($lastposteruid == 0) {
        $thread['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
    } else {

        $lastposteravatar_query = $db->query("SELECT * FROM ".TABLE_PREFIX."users
        WHERE uid = '".$lastposteruid."'
        ");

        while ($lastpost = $db->fetch_array($lastposteravatar_query)) {

            // Gäste bekommen nur Default Avatar -> wenn aktiviert
            if ($activeuser_uid == 0 && $guest_setting == 1) {
                $thread['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
            } else {
    
                // spezielle Accounts 
                $pos = strpos(",".$specialgraphic_setting.",", ",".$lastposteruid.",");

                // letzter Poster ist ein spezieller Account - eigene Grafiken
                if ($pos !== false) {
    
                    // Leerlaufen lassen
                    $username = ""; 
    
                    // Username
                    $username = $lastpost['username'];
    
                    // Umlaute entfernen
                    $username = strtr($username, $all_specialletters);
                    // alles klein schreiben
                    $username = strtolower($username);
                    // leerzeichen entfernen
                    $username = str_replace(" ", "", $username);

                    $thread['lastpostergraphicurl'] = find_graphic_format($username);
    
                }
                // normaler User - normale Grafik
                else {
    
                    // Leerlaufen lassen
                    $lastgraphic = "";

                    // Avatar
                    if ($graphictyp == 0) {
                        $lastgraphic = $lastpost['avatar'];
                    } 
                    // Uploadsystem
                    else if ($graphictyp == 1) {	
                        $path = $db->fetch_field($db->simple_select("uploadsystem", "path", "identification = '".$uploadsystem_graphic."'"), "path");                  
                        $value = $db->fetch_field($db->simple_select("uploadfiles", $uploadsystem_graphic, "ufid = '".$lastposteruid."'"), $uploadsystem_graphic);

                        if ($value != "") {
                            $lastgraphic = $path."/".$value;
                        } else {
                            $lastgraphic = "";
                        }
                    }
                    // Profilfelder
                    else if ($graphictyp == 2) {
                        $fid = "fid".$profilefield_graphic;
                        $lastgraphic = $db->fetch_field($db->simple_select("userfields", $fid, "ufid = '".$lastposteruid."'"), $fid);
                    }
                    // Steckifelder
                    else if ($graphictyp == 3) {	
                        $fieldid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$characterfield_graphic."'"), "id");                  
                        $value = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$lastposteruid."' AND fieldid = '".$fieldid."'"), "value");

                        if ($value != "") {
                            $lastgraphic = $value;
                        } else {
                            $lastgraphic = "";
                        }
                    }
    
                    // wenn man kein Avatar hat => Default
                    if ($lastgraphic == "") {
                        // Dateinamen bauen
                        $thread['lastpostergraphicurl'] = $theme['imgdir']."/".$defaultgraphic;
                    } else {
                        // Dateinamen bauen
                        $thread['lastpostergraphicurl'] = $lastgraphic;
                    }
    
                }

            }
        }
    }

    eval('$thread[\'lastgraphic\'] = "'.$templates->get('forumdisplay_thread_lastpost_graphic').'";');
}

function specialetters_array($specialetters_string) {

    $pair_strings = explode(';', $specialetters_string);

    $specialetters_array = array();
    foreach ($pair_strings as $pair_string) {
        $pair_string = str_replace(" = ", "=", $pair_string);
        $pair = explode('=', $pair_string);
        $specialetters_array[$pair[0]] = $pair[1];
    }

    return $specialetters_array;
}

function collect_forum_ids($parent_fid, &$forum_ids) {
    global $db;

    $subforums_query = $db->simple_select("forums", "fid", "pid = '".$parent_fid."'");

    while($subforum = $db->fetch_array($subforums_query)) {
        $forum_ids[] = $subforum['fid']; 
        collect_forum_ids($subforum['fid'], $forum_ids);
    }
}

function get_last_poster_uid($fid) {
    global $db;

    $forum_ids = array($fid);

    collect_forum_ids($fid, $forum_ids);

    $last_post_query = $db->query("SELECT p.uid FROM ".TABLE_PREFIX."posts p
    WHERE p.fid IN (".implode(',', $forum_ids).")
    ORDER BY p.dateline DESC 
    LIMIT 1"
    );

    if($last_post = $db->fetch_array($last_post_query)) {
        return $last_post['uid'];
    } else {
        return 0;
    }
}

function find_graphic_format($username) {

    global $mybb, $theme;

    $defaultgraphic = $mybb->settings['lastgraphic_defaultgraphic'];

    $allowed_formats = array('jpg', 'jpeg', 'gif', 'png');
    $themesdir = str_replace($mybb->settings['bburl']."/", "", $theme['imgdir']);

    $found_file = '';

    foreach ($allowed_formats as $format) {
        $file_path = $themesdir."/".$username.".".$format;
        if (file_exists($file_path)) {
            $found_file = $file_path;
            break;
        }
    }

    if (!empty($found_file)) {
        return $found_file; 
    } else {
        return $theme['imgdir']."/".$defaultgraphic;
    }
}
