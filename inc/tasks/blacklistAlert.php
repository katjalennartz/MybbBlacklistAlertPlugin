<?php
/**
 * blacklistAlert.php
 *
 * Blacklist plugin for MyBB 1.8
 * Automatische Anzeige der BL 
 * Aktualisierung des Feldes, BL Warnung ausblenden 
 *
 */

// Fehleranzeige 
//error_reporting ( -1 );
//ini_set ( 'display_errors', true ); 

function task_blacklistAlert($task)
{
    global $db, $mybb, $lang;
    //settings 
    $opt_bl_days=intval($mybb->settings['blacklistAlert_duration']);
    $opt_bl_date=intval($mybb->settings['blacklistAlert_date']);

    $opt_bl_ingame=intval($mybb->settings['blacklistAlert_ingame']);
    $opt_bl_archiv=intval($mybb->settings['blacklistAlert_archiv']);
    $opt_bl_excluded = trim($mybb->settings['blacklistAlert_excluded']);


    //ausgeschlossene Gruppen
    $extended_groups = explode( ',',  $opt_bl_excluded);
    foreach ($extended_groups as &$value) {
        if ($value == 0) {
            $build_string ="";
        } else {
            $build_string .= " AND uid != ".$value;
        }
    }
    unset($value);
  
  //Archiv ja/Nein?
    if($opt_bl_archiv == 0) {
       $archiv ="";
    } else {
        $archiv =  " OR concat(',',parentlist,',') LIKE '%,".$opt_bl_archiv.",%'";
    }
   // echo " $opt_bl_ingame".$opt_bl_ingame;
   // echo " $ $opt_bl_archiv".$opt_bl_archiv;

    $get_blacklisted = $db->query("SELECT uid FROM mybb_posts posts 
    INNER JOIN 
    (SELECT fid FROM mybb_forums WHERE 
            concat(',',parentlist,',') LIKE '%,".$opt_bl_ingame.",%' 
            ".$archiv.") 
            as fids
    ON fids.fid = posts.fid
    AND uid != 0
    ".$build_string."

    GROUP BY uid
    Having  datediff(CURDATE(),FROM_UNIXTIME(max(dateline))) > 1
    ORDER BY uid");

    while($blacklisted = $db->fetch_array($get_blacklisted)){ 
         $getUser = $db->query("SELECT uid,username,email,bl_mail,bl_ice FROM ".TABLE_PREFIX."users WHERE uid = ".$blacklisted['uid']."");

            while($userdetail = $db->fetch_array($getUser)){ 
                if($userdetail['bl_mail'] == 1 && $userdetail['bl_ice'] == 0){
                $subject = "Blacklist vom ".htmlspecialchars_uni($mybb->settings['bbname']);
                $message = "Dein Charakter ".htmlspecialchars_uni($userdetail['username'])." wird auf der nächsten Blacklist am ".$opt_bl_date." stehen. Wenn du nicht willst, dass er darauf stehen wird, melde dich zurück ode. ".$mybb->settings['bburl'];
                 my_mail($userdetail['email'], $subject, $message);
                 add_task_log($task, "Blacklistmail an ".htmlspecialchars_uni($userdetail['username'])." geschickt.");
            }
        }
     
    }

    //clean bl info
    $db->query("UPDATE ".TABLE_PREFIX."users SET bl_info=1, bl_info_timestamp=0");
    add_task_log($task, "Blacklist - bl Info von allen Usern zurückgesetzt");
}
