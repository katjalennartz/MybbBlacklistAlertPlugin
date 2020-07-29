<?php

/**
 * automatische Blacklistwarnung  - by risuena
 * lslv.de
 * Risuena im sg
 * https://storming-gates.de/member.php?action=profile&uid=39
 */

// Fehleranzeige 
error_reporting ( -1 );
ini_set ( 'display_errors', true ); 


 
define("IN_MYBB", 1);

require("global.php");
	global $db, $mybb, $templates, $user, $date, $lastpost, $blacklistAlert_ShowUserPosts_bit; 
  	$this_user = intval($mybb->user['uid']);
	  //TO DO: USER // BEWERBER
    $opt_blShow_user=intval($mybb->settings['blacklistAlert_show_user']);
  	$opt_blShow_guest=intval($mybb->settings['blacklistAlert_show_guest']);
//echo $opt_blShow_user;
if (
	($mybb->usergroup['canmodcp'] == 1) ||
	($this_user == 0 & $opt_blShow_guest ==1) ||
	($this_user!=0 & $opt_blShow_user ==1)) {
//Einstellungen holen
 $this_user = intval($mybb->user['uid']); //wer ist online

 $as_uid = intval($mybb->user['as_uid']); //Accountswitcher, Hauptcharakter? 
$extended_groups = array();
  //Einstellungen aus dem ACP ziehen
  $opt_bl_days=intval($mybb->settings['blacklistAlert_duration']);
  $opt_bl_ingame=intval($mybb->settings['blacklistAlert_ingame']);
  $opt_bl_archiv=intval($mybb->settings['blacklistAlert_archiv']);
  $opt_bl_threadsolved=intval($mybb->settings['blacklistAlert_threadsolved']);
  $opt_bl_excluded = trim($mybb->settings['blacklistAlert_excluded']);
  
	$opt_bl_away=intval($mybb->settings['blacklistAlert_away']);
	$opt_bl_bewerber=$mybb->settings['blacklistAlert_bewerbergruppe'];
	$opt_bl_bewerberfid=$mybb->settings['blacklistAlert_bewerberfid'];
	$opt_bl_bewerberdauer=$mybb->settings['blacklistAlert_bewerberdauer'];

	//ausgeschlossene Gruppen
    $extended_groups = explode( ',',  $opt_bl_excluded);
	foreach ($extended_groups as $value) {
			
		if ($value == 0) {
			$build_string ="";
		} else {
		$build_string .= " AND uid != ".$value;
		}
	}
	unset($value);
	//echo $build_string;

    //Archiv ja/Nein?
    if($opt_bl_archiv == 0) {
	   $archiv ="";
    } else {
    	$archiv =  " OR concat(',',parentlist,',') LIKE '%,".$opt_bl_archiv.",%'";
    }

    //threadsolved? 
    if($opt_bl_threadsolved == 0) {
	   $threadsolved ="";
	   $threadsolvedField = "";
    } else {
    	$threadsolved = " AND (threadsolved = 0)";
    	$threadsolvedField = ", t.threadsolved";
    }



$today = new DateTime(date("Y-m-d H:i:s"));
//exkluded groups ?
// away? 
//on ice 

//User die auf der Blackliste stehen - länger als Zeitraum X nicht gepostet
$postdate ="";
$get_meldung = $db->query("SELECT *,posts.fid,max(tid) as tid,max(pid) as pid,uid,max(dateline) as dateline FROM ".TABLE_PREFIX."posts posts 
	INNER JOIN 
	(SELECT fid FROM ".TABLE_PREFIX."forums WHERE 
			concat(',',parentlist,',') LIKE '%,".$opt_bl_ingame.",%' 
			".$archiv.") 
			as fids
	ON fids.fid = posts.fid
	AND uid != 0
	".$build_string."
	GROUP BY uid
    ORder BY dateline");

	//die Posts im Ingame des Charakters
    while($output= $db->fetch_array($get_meldung)){
  
		$get_user = get_user($output['uid']); 
		$user =	build_profile_link($output['username'], $output['uid'], '_blank');
		$userid = $output['uid'];
		$postdate = $output['dateline'];
		$date = my_date('relative', $output['dateline']);
		$pid = $output['pid'];
		$tid = $output['tid'];
		$blIce = $output['bl_ice'];
		$blAway = $output['away'];
		$lastpost = '<a href="showthread.php?tid='.$tid.'&pid='.$pid.'#pid'.$pid.'" target="_blank">Letzter Post</a>';

		//Ist der user auf Eis gelegt? 
		$awayicequery = $db->simple_select("users", "away, bl_ice", "uid=".$userid."");
		//Ice und Away des users
		$blIce = $db->fetch_field($awayicequery, "bl_ice");
		$blAway = $db->fetch_field($awayicequery, "away");
		
		//Soll away überhaupt berücksichtigt werden? Sonst 0
		if ($opt_bl_away == 0) {
			$blAway == 0; 
		}

		$blacklistAlert_ShowUserPosts_bit="";
		
		//MIt deisem Query kriegen wir alle posts aus dem aktuellen Ingame um sich anzuzeigen
		$get_ingameposts = $db->query("
			SELECT name,allposts.maxpid, t.subject, t.fid, t.tid,t.uid,t.dateline, t.lastpost,t.lastposter, t.lastposteruid,t.closed, username  
			FROM ".TABLE_PREFIX."threads t 
			INNER JOIN 
				(Select name, max(pid) as maxpid, tid, fid, uid from ".TABLE_PREFIX."posts p INNER JOIN 
					(SELECT fid as ifid, name FROM ".TABLE_PREFIX."forums 
					WHERE 
					concat(',',parentlist,',') LIKE '%,".$opt_bl_ingame.",%' 
			) as f 
			ON p.fid = ifid WHERE uid = '".$userid."' 
			GROUP BY tid ) as allposts 
			WHERE t.tid = allposts.tid".$threadsolved."
			 ORDER BY dateline");
			while($output_posts= $db->fetch_array($get_ingameposts)){
				//echo " - ".$output_posts['username']." - ";
				$pid_all = $output_posts['maxpid'];
				$tid_all = $output_posts['tid'];
				eval("\$blacklistAlert_ShowUserPosts_bit.= \"".$templates->get("blacklistAlert_ShowUserPosts_bit")."\";");
			}


$postdate_f = new DateTime(date('Y-m-d H:i:s',  $postdate));
$interval = $postdate_f->diff($today);
$difference = $interval->format('%a');

if($difference >= $opt_bl_days AND $blIce == 0 AND $blAway == 0){
	eval("\$blacklistAlert_lastpost_bit.= \"".$templates->get("blacklistAlert_lastpost_bit")."\";");
	}
 }

//Die Bewerber die überfällig sind.
 $get_meldung_bewerber = $db->query("SELECT grp.username, grp.uid, grp.regdate, habenposts.dateline, tid, subject FROM (SELECT * FROM ".TABLE_PREFIX."users WHERE usergroup = ".$opt_bl_bewerber.") as grp left JOIN 
(SELECT username,dateline,max(tid) tid,fid,uid, subject FROM ".TABLE_PREFIX."threads thread
	INNER JOIN 
	           (SELECT fid as fff FROM ".TABLE_PREFIX."forums as f WHERE concat(',',parentlist,',') LIKE '%,".$opt_bl_bewerberfid.",%') as fids
	ON fff = thread.fid
    GROUP BY uid) as habenposts
    
 ON grp.uid = habenposts.uid"); 

	while($output_b= $db->fetch_array($get_meldung_bewerber)){
		$get_user_b = get_user($output_b['uid']); 
		$user_b =	build_profile_link($output_b['username'], $output_b['uid'], '_blank');
		$userid_b = $output_b['uid'];
		$regdate = my_date('relative', $output_b['regdate']);
		if ($output_b['dateline'] == NULL) {
			$difference_b = $opt_bl_bewerberdauer; 
			$date_b = "Kein Stekbrief gepostet";
		} else {
			$date_b = my_date('relative', $output_b['dateline']);
			$postdate_f_b = new DateTime(date('Y-m-d H:i:s', $output_b['dateline']));
			$interval_b = $postdate_f_b->diff($today);
			$difference_b = $interval_b->format('%a');
		}
		
		$tid_b = $output['tid'];
		if ($output_b['tid'] == NULL) {
			$lastpost_b = "//";
		}else {
		$lastpost_b = '<a href="showthread.php?tid='.$tid.'" target="_blank">Steckbrief</a>';
		}


	if($difference_b >= $opt_bl_bewerberdauer){
	eval("\$blacklistAlert_lastpost_bit_bewerber.= \"".$templates->get("blacklistAlert_lastpost_bit_bewerber")."\";");
	}
	}


	eval("\$page= \"".$templates->get("blacklistAlert_lastpost")."\";");
	output_page($page);

} else {
	error_no_permission();
}

?>