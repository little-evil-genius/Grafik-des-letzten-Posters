<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


// HOOKS
$plugins->add_hook("build_forumbits_forum", "lastavatar_forumbit");

// Die Informationen, die im Pluginmanager angezeigt werden
function lastavatar_info(){
	return array(
		"name"		=> "Avatare des letzten Posters auf dem Index",
		"description"	=> "Erweitert die Informationen vom letzten Poster auf dem Index um eine Avataranzeige.",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function lastavatar_install(){
    global $db, $cache, $mybb;

	// EINSTELLUNGEN HINZUFÜGEN
	$setting_group = array(
		'name'          => 'lastavatar',
		'title'         => 'Avatare des letzten Posters auf dem Index',
		'description'   => 'Einstellungen für die Avatare des letzten Posters auf dem Index',
		'disporder'     => 1,
		'isdefault'     => 0
	);
			
	$gid = $db->insert_query("settinggroups", $setting_group); 
			
	$setting_array = array(

		// Default-Avatar
		'lastavatar_defaultavatar' => array(
			'title' => 'Standard-Avatar',
            'description' => 'Wie heißt die Bilddatei für die Standard-Avatare? Diese Grafik wird falls ein Mitglied noch kein Avatar hochgeladen hat stattdessen angezeigt. Damit der Avatar für jedes Design angepasst wird, sollte der Dateiname in allen Ordner für die Designs gleich heißen.',
            'optionscode' => 'text',
            'value' => 'default_avatar.png', // Default
            'disporder' => 1
		),

        // Gäste
        'lastavatar_guest' => array(
            'title' => 'Gäste Ansicht',
            'description' => 'Sollen die Avatar vor Gästen versteckt werden? Statt dem Avatar wird der festgelegte Standard-Avatar angezeigt.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 2
        ),

        // Spezielle Accounts
        'lastavatar_specialavas' => array(
            'title' => 'Spezielle Accounts',
            'description' => 'Gib es Accounts, welche Avatare besitzen die sich je nach Design verändern? Wie der Admin-Account oder ein NPC-Account. Liste hier die UIDs mit einem , auf. Falls nicht benötigt einfach frei lassen.<br>
			<b>Wichtig</b> Der Dateiname für den Avatar wird aus dem Accountnamen gezogen. z.B. "Admin" -> admin.png oder "The Devil" -> thedevil.png. PNG ist das feste Dateiformat. Umlaute und ß werden umgeformt.',
            'optionscode' => 'text',
            'value' => '1,2', // Default
            'disporder' => 3
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
        'title'		=> 'forumbit_depth2_forum_lastpost_avatar',
        'template'	=> $db->escape_string('<img src="{$forum[\'lastposteravatarurl\']}" style="height: 50px;">'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function lastavatar_is_installed(){
	global $mybb;

    if(isset($mybb->settings['lastavatar_specialavas']))
    {
        return true;
    }
    return false;
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function lastavatar_uninstall(){
	
    global $db;
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'lastavatar%'");
    $db->delete_query('settinggroups', "name = 'lastavatar'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title = 'forumbit_depth2_forum_lastpost_avatar'");
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function lastavatar_activate(){
    
    // VARIABLE EINFÜGEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("forumbit_depth2_forum_lastpost", "#".preg_quote('<span class="smalltext">')."#i", '{$forum[\'lastavatar\']}<span class="smalltext">');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function lastavatar_deactivate(){

    // VARIABLE ENTFERNEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumbit_depth2_forum_lastpost", "#".preg_quote('{$forum[\'lastavatar\']}')."#i", '', 0);
}

function lastavatar_forumbit(&$forum){
    
    global $db, $mybb, $templates, $theme, $lastpost_data, $depth;

    // EINSTELLUNGEN ZIEHEN
    // Standard-Avatar
    $defaultavatar =  $mybb->settings['lastavatar_defaultavatar'];
    // Gäste
    $guest_setting =  $mybb->settings['lastavatar_guest'];
    // Spezielle Accounts
    $specialavas_setting =  $mybb->settings['lastavatar_specialavas'];

    // UMLAUTE UMFORMEN
    $tempstr = Array("Ä" => "AE", "Ö" => "OE", "Ü" => "UE", "ä" => "ae", "ö" => "oe", "ü" => "ue", "ß" => "ss");

	// Eigene UID
	$activeuser_uid = intval($mybb->user['uid']);

	// UID letzter Post
    if (!empty($forum['lastposteruid'])) {
        $lastposteruid = $forum['lastposteruid'];
    } else {
        $forum_info = build_forumbits($forum['fid'], $depth+1);
        if (!empty($forum_info['lastpost'])) {
            $lastpost_data = $forum_info['lastpost'];   
        }
        $lastposteruid = $lastpost_data['lastposteruid'];
    }

    // Last Post Gast - Standardavatar
    $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;

    $lastposteravatar_query = $db->query("SELECT * FROM ".TABLE_PREFIX."users
	WHERE uid = '$lastposteruid'
	");

    while ($lastpost = $db->fetch_array($lastposteravatar_query)) {

        // Gäste dürfen keine Avatare sehen
        if ($guest_setting == 1) {

            // Es gibt spezielle Accounts
            if ($specialavas_setting != '') {

                // $pos = strpos($meinString, $findMich);
                $pos = strpos(",".$specialavas_setting.",", ",".$lastposteruid.",");

                // letzter Poster ist ein spezieller Account - eigene Avatare   
                if ($pos !== false) {

                    // Leerlaufen lassen
                    $username = "";

                    // Username
                    $username = $lastpost['username'];

                    // Umlaute entfernen
                    $username = strtr($username, $tempstr);
                    // alles klein schreiben
                    $username = strtolower($username);
                    // leerzeichen entfernen
                    $username = str_replace(" ", "", $username);

                    // Gäste bekommen trotzdem nur Default Avatar
                    if ($activeuser_uid == 0) {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;
                    } else {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$username.".png";
                    }

                }
                // normaler User - normaler Avatar
                else {

                    // Leerlaufen lassen
                    $avatar = "";

                    // Username
                    $avatar = $lastpost['avatar'];

                    // Gäste bekommen trotzdem nur Default Avatar UND wenn man kein Avatar hat
                    if ($avatar == "" || $activeuser_uid == 0) {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;
                    } else {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = $avatar;
                    }
   
                }

            } else {

                // Leerlaufen lassen
                $avatar = "";

                // Username
                $avatar = $lastpost['avatar'];

                // Gäste bekommen trotzdem nur Default Avatar UND wenn man kein Avatar hat
                if ($avatar == "" || $activeuser_uid == 0) {
                    // Dateinamen bauen
                    $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;
                } else {
                    // Dateinamen bauen
                    $forum['lastposteravatarurl'] = $avatar;
                }

            }

        } else {

            // Es gibt spezielle Accounts
            if ($specialavas_setting != '') {

                // $pos = strpos($meinString, $findMich);
                $pos = strpos(",".$specialavas_setting.",", ",".$lastposteruid.",");

                // letzter Poster ist ein spezieller Account - eigene Avatare   
                if ($pos !== false) {

                    // Leerlaufen lassen
                    $username = "";

                    // Username
                    $username = $lastpost['username'];

                    // Umlaute entfernen
                    $username = strtr($username, $tempstr);
                    // alles klein schreiben
                    $username = strtolower($username);
                    // leerzeichen entfernen
                    $username = str_replace(" ", "", $username);

                    // Dateinamen bauen
                    $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$username.".png";

                }
                // normaler User - normaler Avatar
                else {

                    // Leerlaufen lassen
                    $avatar = "";

                    // Username
                    $avatar = $lastpost['avatar'];

                    // wenn man kein Avatar hat - standard Avatar
                    if ($avatar == "") {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;
                    } else {
                        // Dateinamen bauen
                        $forum['lastposteravatarurl'] = $avatar;
                    }
   
                }

            } else {

                // Leerlaufen lassen
                $avatar = "";

                // Username
                $avatar = $lastpost['avatar'];

                // wenn man kein Avatar hat - standard Avatar
                if ($avatar == "") {
                    // Dateinamen bauen
                    $forum['lastposteravatarurl'] = "{$theme['imgdir']}/".$defaultavatar;
                } else {
                    // Dateinamen bauen
                    $forum['lastposteravatarurl'] = $avatar;
                }

            }

        }
    }

    eval('$forum[\'lastavatar\'] = "'.$templates->get('forumbit_depth2_forum_lastpost_avatar').'";');

}
