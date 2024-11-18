<?php

/**
 * automatische blacklistanzeige - by risuena
 * Risuena im sg
 * https://storming-gates.de/member.php?action=profile&uid=39
 */

// Fehleranzeige 
// error_reporting ( -1 );
// ini_set ( 'display_errors', true ); 

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function blacklistAlert_info()
{
  return array(
    "name"      => "Blacklist Anzeige 1.1",
    "description"  => "Der User wird auf dem Index oder/und per Mail automatisch gewarnt, wenn er auf der nächsten BL stehen würde + Übersichtsseite(https://forenadresse/blacklist_show.php)",
    "website"    => "https://lslv.de",
    "author"    => "risuena",
    "authorsite"  => "https://lslv.de",
    "version"    => "1.1",
    "compatibility" => "*"
  );
}


function blacklistAlert_install()
{
  global $db, $cache;
  //useranzeige, ob anzeige ja oder nein zur usertabelle hinzufügen
  // Meldung für einen bestimmten Charakter ausgeblendet (0 ausgeblendet - 1 eingeblendet) 
  if (!$db->field_exists("bl_info", "users")) {
    $db->add_column("users", "bl_info", "INT(1) NOT NULL default '1'");
  }
  //Wann wurde die Meldung für einen bestimmten Charakter ausgeblendet? (0 Meldung wird angezeigt, 1 Meldung nicht anzeigen.)
  if (!$db->field_exists("bl_info_timestamp", "users")) {
    $db->add_column("users", "bl_info_timestamp", "INT(10) DEFAULT NULL");
  }
  //Blacklist meldung überhaupt generell für Charaktere anzeigen ja oder nein? (gilt automatisch auch für die angehangenen)
  if (!$db->field_exists("bl_view", "users")) {
    $db->add_column("users", "bl_view", "INT(1) NOT NULL default '1'");
  }
  //Mail benachrichtigung pro Charakter. Default: ausgeschaltet
  if (!$db->field_exists("bl_mail", "users")) {
    $db->add_column("users", "bl_mail", "INT(1) NOT NULL default '0'");
  }
  if (!$db->field_exists("bl_ice", "users")) {
    $db->add_column("users", "bl_ice", "INT(1) NOT NULL default '0'");
  }

  //Einstellungen 
  $setting_group = array(
    'name' => 'blacklistAlert',
    'title' => 'Blacklist Warnung',
    'description' => 'Einstellungen für die Blacklist Warnungen',
    'disporder' => 6, // The order your setting group will display
    'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);

  //Wieviele Tage
  $setting_array = array(
    'blacklistAlert_duration' => array(
      'title' => 'Zeitraum?',
      'description' => 'Nach wievielen Tagen, abhängig vom letzten Ingame Post(+ Archiv wenn gewünscht), soll ein User auf der BL stehen?',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 1
    ),

    //An welchem Tag im Monat?
    'blacklistAlert_date' => array(
      'title' => 'Datum der Blacklist',
      'description' => 'An welchem Tag des Monats erscheint eure Blacklist(1-31). Achtung bei Februar und Monate ohne 31.?',
      'optionscode' => 'text',
      'value' => '1', // Default
      'disporder' => 2
    ),

    //Gruppe für Bewerber
    'blacklistAlert_alert_days' => array(
      'title' => 'Anzeige Warnung',
      'description' => 'An welchem Tag im Monat (1-31) soll die Warnung für die Blacklist immer zurückgesetzt und wieder angezeigt werden? Z.b 7 Tage vor der Blacklist(am 1.) -> 23
          	(Achtung bei 29/31 - Februar und Monate die nur 30 Tage haben!) ',
      'optionscode' => 'text',
      'value' => '23', // Default
      'disporder' => 3
    ),

    //Gruppe für Bewerber
    'blacklistAlert_bewerbergruppe' => array(
      'title' => 'Gruppe für Bewerber',
      'description' => 'Wie ist die ID für eure Bewerbergruppe? 0 angeben wenn ihr die Funktion nicht nutzen wollt.',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 4
    ),
    //Dauer für Bewerber
    'blacklistAlert_bewerberdauer' => array(
      'title' => 'Zeitraum für Bewerber',
      'description' => 'Wieviel Zeit(Tage) haben Bewerber einen Steckbrief zu posten?',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 5
    ),
    //Die fid (forenid) der Bewerbungsarea
    'blacklistAlert_bewerberfid' => array(
      'title' => 'Bewerbungsarea',
      'description' => 'In welches Forum posten eure Bewerber die Steckbriefe(fid)?',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 6
    ),
    //ID fürs Ingame?
    'blacklistAlert_ingame' => array(
      'title' => 'Ingamebereich',
      'description' => 'Wie ist die ID für euer Ingame?',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 7
    ),

    //ID fürs Archiv?
    'blacklistAlert_archiv' => array(
      'title' => 'Archiv',
      'description' => 'Wenn die Posts im Archiv auch zählen sollen, die ID des Archivs eintragen, ansonsten bitte 0 eintragen.',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 8
    ),

    //Accountswitcher ja oder nein?
    'blacklistAlert_as' => array(
      'title' => 'Accountswitcher - Meldung übergreifend?',
      'description' => 'Wenn ihr den Accountswitcher benutzt, kann die Meldung Charakterübergreifend angezeigt werden. Wenn gewünscht eine 1 eintragen.',
      'optionscode' => 'yesno',
      'value' => '1', // Default
      'disporder' => 9
    ),

    //Abwesenheit ja oder nein?
    'blacklistAlert_away' => array(
      'title' => 'Abwesenheit beachten?',
      'description' => 'Soll beachtet werden, dass ein User abwesend gemeldet ist?.',
      "optionscode" => "yesno",
      'value' => '1', // Default
      'disporder' => 10
    ),
    //Eisliste ja oder nein?
    'blacklistAlert_ice' => array(
      'title' => 'Eisliste',
      'description' => 'Können einzelne Charaktere auf Eis gelegt werden?
          Anzeigbar im Profil mit der Variable {$iceMeldung}',
      "optionscode" => "yesno",
      'value' => '1', // Default
      'disporder' => 11
    ),
    //Erledigt / Unerledigt Plugin
    'blacklistAlert_threadsolved' => array(
      'title' => 'Erledigt/Unerledigt Plugin',
      'description' => 'Wird das erledigt/unerledigt Plugin benutzt und soll es beachtet werden -> erledigte Threads in der Blacklist_Show nicht berücksichtigen? In der Berechnung werden sie trotzdem beachtet.',
      "optionscode" => "yesno",
      'value' => '1', // Default
      'disporder' => 12
    ),

    //ID fürs Archiv?
    'blacklistAlert_excluded' => array(
      'title' => 'ausgeschlossene Gruppen',
      'description' => 'Gibt es einzelne Accounts die ausgeschlossen werden sollen? Bitte uid mit , getrennt eintragen.',
      'optionscode' => 'text',
      'value' => '0', // Default
      'disporder' => 13
    ),

    'blacklistAlert_text' => array(
      'title' => 'Ein Text der auf dem Index angezeigt werden soll. Html möglich',
      'description' => 'Ihr könnt zum Beispiel einen Link zu einem Thread angeben, wo man sich zurückmelden kann.',
      'optionscode' => 'textarea',
      'value' => 'Folgende Charaktere würden auf der <b>Blacklist</b> stehen:<br />', // Default
      'disporder' => 14
    ),

    'blacklistAlert_show_user' => array(
      'title' => 'Dürfen User die Blacklist Show sehen?',
      'description' => 'Ihr könnt zum Beispiel einen Link zu einem Thread angeben, wo man sich zurückmelden kann.',
      "optionscode" => "yesno",
      'value' => '0', // Default
      'disporder' => 15
    ),

    'blacklistAlert_show_guest' => array(
      'title' => 'Dürfen Gäste die Blacklist Show sehen?',
      'description' => 'Ihr könnt zum Beispiel einen Link zu einem Thread angeben, wo man sich zurückmelden kann.',
      "optionscode" => "yesno",
      'value' => '0', // Default
      'disporder' => 16
    )

  );


  foreach ($setting_array as $name => $setting) {
    $setting['name'] = $name;
    $setting['gid'] = $gid;
    $db->insert_query('settings', $setting);
  }
  rebuild_settings();

  //Task, der einmal im Monat -> angegeben in Settins bl_info wieder zurücksetzt. 
  $db->insert_query('tasks', array(
    'title' => 'Blacklist Meldung',
    'description' => 'An einem bestimmten Tag(siehe Einstellungen) die Warnung für alle betroffenen Charaktere wieder anzeigen (Es sei den der User will insgesamt keine Meldung) Default: am 1. jedes Monats. Zusätzlich bei Wunsch des Users wird eine Mail an den betroffenen Charakter geschickt.',
    'file' => 'blacklistAlert',
    'minute' => '0',
    'hour' => '0',
    'day' => '1',
    'month' => '*',
    'weekday' => '*',
    'nextrun' => TIME_NOW,
    'lastrun' => 0,
    'enabled' => 1,
    'logging' => 1,
    'locked' => 0,
  ));
  $cache->update_tasks();
}

//überprüft ob das Plugin in installiert ist
function blacklistAlert_is_installed()
{
  global $db;
  if ($db->field_exists("bl_info", "users")) {
    return true;
  }
  return false;
}

//Deinstallation des Plugins
function blacklistAlert_uninstall()
{
  global $db, $cache;
  //ist das Plugin überhaupt installiert? 
  // if ($db->field_exists("bl_info", "users")) {
  //Wenn ja, lösche angelegte Felder aus der User Tabelle
  if (!$db->field_exists("bl_info", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP bl_info");
  }
  if (!$db->field_exists("bl_info_timestamp", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP bl_info_timestamp");
  }
  if (!$db->field_exists("bl_view", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP bl_view");
  }
  if (!$db->field_exists("bl_mail", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP bl_mail");
  }
  if (!$db->field_exists("bl_ice", "users")) {
    $db->query("ALTER TABLE " . TABLE_PREFIX . "users DROP bl_ice");
  }
  //task löschen
  $db->delete_query('tasks', 'file=\'blacklistAlert\'');


  // Einstellungen entfernen
  $db->delete_query('settings', "name IN (
      'blacklistAlert_duration',
      'blacklistAlert_date',
      'blacklistAlert_alert_days',
      'blacklistAlert_bewerbergruppe',
      'blacklistAlert_bewerberdauer',
      'blacklistAlert_bewerberfid',
      'blacklistAlert_ingame',
      'blacklistAlert_archiv',
      'blacklistAlert_as',
      'blacklistAlert_away',
      'blacklistAlert_threadsolved',
      'blacklistAlert_excluded',
      'blacklistAlert_text',
      'blacklistAlert_show_user', 
      'blacklistAlert_show_guest')");
  $db->delete_query('settinggroups', "name = 'blacklistAlert'");
  //templates noch entfernen
  rebuild_settings();
  // Task löschen
  $db->delete_query('tasks', "file='blacklistAlert'");
  $cache->update_tasks();
}


//Plugin Aktivieren
function blacklistAlert_activate()
{
  global $db;

  //templates anlegen
  $insert_array = array(
    'title'    => 'blacklistAlert_index_warning',
    'template'  => '<div style="width:90%;border:1px solid black; margin:auto auto; text-align:center; padding: 5px;">
      {$opt_bl_text}
      {$blacklistAlert_blackbit_bewerber}
      {$blacklistAlert_blackbit}
        <a href="index.php?action=hideall&amp;id=all">[Meldung für alle verbergen]</a>
      </div>',
    'sid'    => '-1',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'    => 'blacklistAlert_ucp',
    'template'  => '<fieldset class="trow2">
<legend><strong>Blacklist Warnung</strong></legend>
<table cellspacing="0" cellpadding="0">
<tr>
<td colspan="2"><span class="smalltext">Soll eine Warnung angezeigt werden, wenn dein Charakter auf der Blacklist stehen würde?<br>
<b>Achtung: Schaltet die Meldung immer aus, nicht nur für einen Monat und für alle Charas.</span></td>
</tr>
<tr>
<td><span class="smalltext"><input type="radio" class="bl" name="bl" value="1" {$bl_check_yes} /> ja</span></td>
<td><span class="smalltext"><input type="radio" class="bl" name="bl" value="0" {$bl_check_no} /> nein</span></td>
</tr>
<tr>
<td colspan="2" align="center" valign="middle"><hr /></td>
<tr>
<td colspan="2"><span class="smalltext">Soll die Warnung für diesen Monat ausgeschaltet werden? 
<br>Wahlweise für diesen, oder für alle Charaktere.</span></td>
</tr>
<tr>
<td><span class="smalltext"><input type="radio" class="bl" name="blInfo" value="0" {$blInfo_check_yes} /> ja</span></td>
<td><span class="smalltext"><input type="radio" class="bl" name="blInfo" value="1" {$blInfo_check_no} /> nein</span></td>
</tr>
<tr>
<td colspan="2"><span class="smalltext">Möchtest du per E Mail informiert werden? 
<br>Wahlweise für diesen, oder für alle Charaktere.</span></td>
</tr>
<tr>
<td><span class="smalltext"><input type="radio" class="bl" name="blMail" value="1" {$blMail_check_yes} /> ja</span></td>
<td><span class="smalltext"><input type="radio" class="bl" name="blMail" value="0" {$blMail_check_no} /> nein</span></td>
</tr>
<tr>
<td colspan="2"><span class="smalltext">Sollen die Einstellungen (Mail und Warnung) für alle angehangenen Charaktere übernommen werden?</span></td>
</tr>
<tr>
<td><span class="smalltext"><input type="radio" class="bl" name="blas" value="1" /> ja</span></td>
<td><span class="smalltext"><input type="radio" class="bl" name="blas" value="0" /> nein</span></td>
</tr>
{$blacklistAlert_iceUCP}

</table>
</fieldset>',
    'sid'    => '-1',
    'version'  => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_iceUCP',
    'template'  => '<tr>
<td colspan="2"><span class="smalltext">Soll dieser Charakter auf Eis gelegt werden? </td>
</tr>
<tr>
<td><span class="smalltext"><input type="radio" class="bl" name="blIce" value="1" {$blIce_check_no} /> ja</span></td>
<td><span class="smalltext"><input type="radio" class="bl" name="blIce" value="0" {$blIce_check_no} /> nein</span></td>
</tr>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);


  $insert_array = array(
    'title'   => 'blacklistAlert_blackbit',
    'template'  => '{$output[\\\'username\\\']} würde auf der nächsten BL stehen. Der <a href="showthread.php?tid={$output[\\\'tid\\\']}&pid={$output[\\\'pid\\\']}#pid{$output[\\\'pid\\\']}">letzter Post</a> ist {$output[\\\'diff\\\']} Tage her! <a href="index.php?action=hide&amp;id={$output[\\\'uid\\\']}">[verbergen]</a></br>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_blackbit_bewerber',
    'template'  => '{$output_b[\\\'username\\\']} würde auf der nächsten BL stehen. Du hast noch keinen Steckbrief gepostet. <a href="index.php?action=hide&amp;id={$output_b[\\\'uid\\\']}">[verbergen]</a></br>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_lastpost',
    'template'  => '<html>
  <head>
    <title> Blacklistanzeige - {$mybb->settings[\\\'bbname\\\']}</title>
    {$headerinclude}
  </head>
  <body>
    {$header}   
<center>
  <table width="80%">
    <tr>
  <td colspan="3" text-align="center"> <strong>Mitglieder</strong></td>
  </tr>
      <tr>
    <td width="20%" align="center">User</td>
    <td width="15%" align="center">Letzter Post</td>
    <td width="15%" align="center">Datum</td>
  <td width="50%" align="center">aktuelle Ingameposts</td>
  </tr>
  {$blacklistAlert_lastpost_bit}
  </tr>
  </table>
  <table width="80%">
  <tr>
  <td colspan="3" text-align="center"> <strong>Bewerber</strong></td>
  </tr>
  <tr>
    <td width="33%" align="center">User</td>
    <td width="33%" align="center">Steckbrief</td>
    <td width="33%" align="center">Datum</td>
  </tr>
  {$blacklistAlert_lastpost_bit_bewerber}
  </table>  
  </center>

    {$footer}
  </body>
</html>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_lastpost_bit',
    'template'  => '<tr>
  <td valign="top">{$user}</td>
  <td valign="top">{$lastpost}</td>
  <td valign="top">{$date}</td>
  <td valign="top">{$blacklistAlert_ShowUserPosts_bit}</td>
</tr>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_lastpost_bit_bewerber',
    'template'  => '<tr>
  <td>{$user_b}<br/> Registriert seit: {$regdate}</td>
  <td>{$lastpost_b}</td>
  <td>{$date_b}</td>
</tr>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);

  $insert_array = array(
    'title'   => 'blacklistAlert_ShowUserPosts_bit',
    'template'  => '<span class="small-text"><a href="showthread.php?tid={$output_posts[\\\'tid\\\']}&pid={$output_posts[\\\'maxpid\\\']}#pid{$output_posts[\\\'maxpid\\\']}" target="_blank">{$output_posts[\\\'subject\\\']}</a>, letzter Post von {$output_posts[\\\'lastposter\\\']}<br/><br/></a>',
    'sid'   => '-1',
    'version' => '',
    'dateline'  => TIME_NOW
  );
  $db->insert_query("templates", $insert_array);


  include  MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", "#" . preg_quote('{$header}') . "#i", '{$header}{$blacklistAlert_index_warning}');
  //einfügen im profil
  find_replace_templatesets("usercp_profile", "#" . preg_quote('{$contactfields}') . "#i", '{$contactfields}{$blacklistAlert_ucp}');

  //enable task
  $db->update_query('tasks', array('enabled' => 1), "file = 'blacklistAlert'");
}

function blacklistAlert_deactivate()
{
  global $db;
  include  MYBB_ROOT . "/inc/adminfunctions_templates.php";
  $db->delete_query("templates", "title LIKE 'blacklistAlert_%'");

  find_replace_templatesets("index", "#" . preg_quote('{$blacklistAlert_index_warning}') . "#i", '');
  //im profil noch entfernen
  find_replace_templatesets("usercp_profile", "#" . preg_quote('{$blacklistAlert_ucp}') . "#i", '');

  // Disable the task
  $db->update_query('tasks', array('enabled' => 0), "file = 'blacklistAlert'");
}



/*#######################################
#//Hilfsfunktion für Mehrfachcharaktere (accountswitcher)
#//Alle angehangenen Charas holen
#//an die Funktion übergeben: Wer ist Online, die dazugehörige accountswitcher ID (ID des Hauptcharas) 
#// außerdem die Info, ob der Admin erlaubt, dass Charas auf Eis gelegt werden dürfen -> entsprechend ändert sich die Abfrage!
######################################*/
function get_allchars($this_user, $as_uid, $ice)
{
  global $db, $mybb, $hauptchar;
  //mit Hauptaccount online/oder keine angehangenen
  // suche alle angehangenen accounts
  if ($as_uid == 0) {
    $hauptchar = $this_user;
    $get_all_uids = $db->query("SELECT uid,username,usergroup FROM " . TABLE_PREFIX . "users WHERE 
      bl_info= 1 
        " . $ice . " 
         AND ((as_uid=$this_user) OR (uid=$this_user)) ORDER BY username");
  } else if ($as_uid != 0) { //nicht mit Hauptaccoung online
    //id des users holen wo alle angehangen sind + alle charas
    $hauptchar = $as_uid;
    $get_all_uids = $db->query("SELECT uid,username,usergroup FROM " . TABLE_PREFIX . "users WHERE
      bl_info = 1 
      " . $ice . " 
      AND 
      ((as_uid=$as_uid) OR (uid=$this_user) OR (uid=$as_uid)) 
      ORDER BY username");
  }
  //ergebnis querie zurückgeben
  return $get_all_uids;
}


//Das Datum der Tasks anpassen, wenn im Adminbereich etwas geändert wird
//-> Wann wird die Warnung für alle User wieder angezeigt, auch wenn sie die zwischenzeitlich ausgeblendet haben. Also zum Beispiel eine woche vor der Blacklist
//Ausnahme: Der User wünscht ausdrücklich keine Warnung bei keinem Charakter
$plugins->add_hook("admin_config_settings_change", "blacklistAlert_editTask");
function blacklistAlert_editTask()
{
  global $db, $mybb;
  $var = $mybb->input['upsetting']['blacklistAlert_alert_days'];
  $db->update_query('tasks', array('day' => $var), "file = 'blacklistAlert'");
}

//Hauptfunktion: Anzeige auf Index wenn Charaktere auf der BL sind
$plugins->add_hook('index_start', 'blacklistAlert_alert');
function blacklistAlert_alert()
{
  global $db, $mybb, $templates, $blacklistAlert_index_warning;
  //Variablen setzen

  //User spezifisch
  $this_user = intval($mybb->user['uid']); //wer ist online
  $saveUidForAway = intval($mybb->user['uid']);
  $as_uid = intval($mybb->user['as_uid']); //Accountswitcher, Hauptcharakter? 
  //meldung überhaupt anzeigen, Ja oder Nein? -> Für alle Charas
  $blview = intval($mybb->user['bl_view']);
  $blice = intval($mybb->user['bl_ice']);

  //Einstellungen aus dem ACP ziehen
  $opt_bl_days = intval($mybb->settings['blacklistAlert_duration']); //Zeitraum
  $opt_bl_date = intval($mybb->settings['blacklistAlert_date']); //an welchem Tag wird sie angezeigt
  $opt_bl_alert_days = intval($mybb->settings['blacklistAlert_alert_days']); //An welchem tag soll die Warnung (bl_info) zurückgesetzt werden
  $opt_bl_ingame = intval($mybb->settings['blacklistAlert_ingame']);
  $opt_bl_archiv = intval($mybb->settings['blacklistAlert_archiv']);
  $opt_bl_as = intval($mybb->settings['blacklistAlert_as']);
  $opt_bl_away = intval($mybb->settings['blacklistAlert_away']);
  $opt_bl_ice = intval($mybb->settings['blacklistAlert_ice']);


  $opt_bl_text = $mybb->settings['blacklistAlert_text'];

  //Bewerbereinstellungen holen
  $opt_bl_bewerbergruppe = $mybb->settings['blacklistAlert_bewerbergruppe'];
  $opt_bl_bewerberdauer = $mybb->settings['blacklistAlert_bewerberdauer'];
  $opt_bl_bewerberfid = $mybb->settings['blacklistAlert_bewerberfid'];


  //array für angehangenene Charas initialisieren (Accountswitcher)
  $array_uids = array();

  //warnung nur anzeigen, wenn ein Charakter auf der BL steht und nicht abwesend
  $is_alert = false;
  $abwesend = 0;

  //Niemals bei gästen anzeigen
  if ($this_user != 0) {
    //nur wenn anzeige gewünsch
    if ($blview != 0) {

      //Archiv ja/Nein?
      if ($opt_bl_archiv == 0) {
        $archiv = "";
      } else {
        $archiv =  " OR concat(',',parentlist,',') LIKE '%," . $opt_bl_archiv . ",%'";
      }

      //Auf Eis legen möglich? Ja oder nein
      if ($opt_bl_ice == 0) {
        $ice = "";
      } else {
        $ice =  " AND bl_ice != 1";
      }
      //echo $as_uid."<-- as uid ? ";

      //Mehrfachcharaktere anzeigen ja/nein?
      if ($opt_bl_as == 0) { //Nein
        $allechars = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE uid = " . $this_user . " AND bl_info=1" . $ice . "");

        $query = $db->simple_select('users', 'away', "uid ='" . $this_user . "'", array('LIMIT' => 1));
        $abwesend = $db->fetch_field($query, 'away');
      } else {
        //für abwesenheit
        if ($opt_bl_away == 1) {
          if ($as_uid == 0) {
            //   echo "hier";
            $query = $db->simple_select('users', 'away', "uid = '" . $this_user . "'", array('LIMIT' => 1));
            $abwesend = $db->fetch_field($query, 'away');
            // echo $abwesend."ist,,,";
          } else {
            //echo "hier?";
            $query = $db->simple_select('users', 'away', "uid ='" . $as_uid . "'", array('LIMIT' => 1));
            $abwesend = $db->fetch_field($query, 'away');
          }
        }

        // Alle verbundenen Charaktere Anzeigen - querie über hilfsfunktion 
        $allechars = get_allchars($this_user, $as_uid, $ice);
        //Funktion gibt ein array mit allen Charakteren des Users zurück
      }

      //Ergebnis durchgehen
      while ($meldung = $db->fetch_array($allechars)) {
        $uid = $meldung['uid'];
        $usergroup = $meldung['usergroup'];

        //Charakter in der Bewerbung?    
        if ($usergroup == $opt_bl_bewerbergruppe) {
          $get_bewerbermeldung = $db->query("SELECT grp.username, grp.uid, grp.regdate, habenposts.dateline, tid, subject FROM (SELECT * FROM " . TABLE_PREFIX . "users WHERE usergroup = " . $opt_bl_bewerbergruppe . ") as grp left JOIN 
(SELECT username,dateline,max(tid) tid,fid,uid, subject FROM " . TABLE_PREFIX . "threads thread
  INNER JOIN 
             (SELECT fid as fff FROM " . TABLE_PREFIX . "forums as f WHERE concat(',',parentlist,',') LIKE '%," . $opt_bl_bewerberfid . ",%') as fids
  ON fff = thread.fid
    GROUP BY uid) as habenposts
     ON grp.uid = habenposts.uid");

          while ($output_b = $db->fetch_array($get_bewerbermeldung)) {
            if ($output_b['dateline'] == NULL) {
              $difference_b = $opt_bl_bewerberdauer;
            } else {
              $postdate_f_b = new DateTime(date('Y-m-d H:i:s', $output_b['dateline']));
              $today = new DateTime(date("Y-m-d H:i:s"));
              $interval_b = $postdate_f_b->diff($today);
              $difference_b = $interval_b->format('%a');
            }

            if (($output_b['uid'] == $uid) && ($difference_b >= $opt_bl_bewerberdauer)) {
              $is_alert = true;
              eval("\$blacklistAlert_blackbit_bewerber.=\"" . $templates->get("blacklistAlert_blackbit_bewerber") . "\";");
              $array_uids[$output_b['uid']] = $output_b['username'];
            }
          }
        } else {
          //Alle anderen Charaktere
          $get_meldung = $db->query("SELECT *, DATEDIFF(CURDATE(),FROM_UNIXTIME(dateline)) as diff FROM 
        (SElECT uid, username, fid, tid, pid, dateline as dateline FROM " . TABLE_PREFIX . "posts WHERE uid = " . $uid . " AND visible != '-2') as up 
            INNER JOIN
        (SELECT fid FROM " . TABLE_PREFIX . "forums WHERE concat(',',parentlist,',') LIKE '%," . $opt_bl_ingame . ",%'" . $archiv . ") as fids
      ON fids.fid = up.fid
      ORDER by dateline DESC
          LIMIT 1");


          while ($output = $db->fetch_array($get_meldung)) {
            //wenn gruppe = angegebene gruppe anderer vergleich
            //sonst und gruppe ungleich.          
            if ($output['diff'] >= $opt_bl_days) {
              $is_alert = true;
              eval("\$blacklistAlert_blackbit.=\"" . $templates->get("blacklistAlert_blackbit") . "\";");
              $array_uids[$output['uid']] = $output['username'];
            }
          }
          $meldung = "";
        }
      }
      //    blacklistAlert_away
      if ($is_alert && $abwesend == 0) {
        //$blacklistAlert_index_warning 
        eval("\$blacklistAlert_index_warning .=\"" . $templates->get("blacklistAlert_index_warning") . "\";");
        $is_alert = false;
      }

      $today = time();
      if (($mybb->input['action'] == "hide")) {
        if (isset($_GET['id'])) {
          $id = intval($_GET['id']);
        }
        $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_info = '0', bl_info_timestamp = " . $today . " WHERE uid='" . $id . "'");
        redirect('index.php');
      }

      if (($mybb->input['action'] == "hideall")) {
        ////get all chars  
        foreach ($array_uids as $iduser => $nameuser) {
          $hideid = $iduser;
          $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_info = '0', bl_info_timestamp = " . $today . " WHERE uid='" . $hideid . "'");
        }
        redirect('index.php');
      }
    }
  }
}

//Einstellungen des Users, soll die BL Anzeige überhaupt angezeigt werden // Auf Eis
$plugins->add_hook('usercp_profile_start', 'blacklistAlert_edit_profile');
function blacklistAlert_edit_profile()
{
  global $mybb, $db, $templates, $blacklist, $blacklistAlert_ucp, $blInfo_check_no, $blInfo_check_yes, $bl_check_yes, $bl_check_no, $blacklistAlert_iceUCP, $blMail_check_no, $blMail_check_yes, $blIce_check_no, $blIce_check_yes;

  $this_user = intval($mybb->user['uid']);
  //view
  $blacklist = intval($mybb->user['bl_view']);
  //info
  $blacklistAlert_info = intval($mybb->user['bl_info']);
  //mail? 
  $blacklistAlert_mail = intval($mybb->user['bl_mail']);
  //Ice? 
  $blacklistAlert_ice = intval($mybb->user['bl_ice']);

  $opt_bl_ice = intval($mybb->settings['blacklistAlert_ice']);

  //echo "Test ice".$blacklistAlert_ice;



  //allgemeine Ansicht von der Blacklist (für alle Charas des Nutzers)
  //Für immer - wird nicht automatisch zurückgesetzt
  if ($blacklist == 1) {
    $bl_check_yes = 'checked="checked"'; // yes -> 1
    $bl_check_no = "";
  } else {
    $bl_check_yes = "";
    $bl_check_no = 'checked="checked"';
  }

  if ($blacklistAlert_info == 1) {
    //Soll die warnung ausgeschaltet werden für einen Monat ->
    $blInfo_check_no = 'checked="checked"'; // yes -> überträgt 0
    $blInfo_check_yes = "";  //no 0
  } else {
    $blInfo_check_no = "";
    $blInfo_check_yes = 'checked="checked"';
  }

  if ($blacklistAlert_mail == 1) {
    //Soll die warnung ausgeschaltet werden für einen Monat ->
    $blMail_check_yes = 'checked="checked"'; // yes -> überträgt 1
    $blMail_check_no = "";  //no 0
  } else {
    $blMail_check_yes = "";
    $blMail_check_no = 'checked="checked"';
  }

  if ($blacklistAlert_ice == 1) {
    //soll der Charakter auf Eis gelegt werden? 
    $blIce_check_yes = 'checked="checked"'; // yes -> überträgt 0
    $blIce_check_no = "";  //no 0
  } else {
    // echo "hier";
    $blIce_check_yes = "";
    $blIce_check_no = 'checked="checked"';
  }
  // echo "vla blub".$blIce_check_no;
  //{$blacklistAlert_iceUCP}
  if ($opt_bl_ice == 1) {
    eval("\$blacklistAlert_iceUCP.=\"" . $templates->get("blacklistAlert_iceUCP") . "\";");
  } else {
    $blacklistAlert_iceUCP = "";
  }

  eval("\$blacklistAlert_ucp.=\"" . $templates->get("blacklistAlert_ucp") . "\";");
}

//User CP: änderungen im ucp speichern 
//bei Wunsch des Users, Einstellung für alle Charaktere übernehmen
$plugins->add_hook('usercp_do_profile_start', 'blacklistAlert_edit_profile_do');
function blacklistAlert_edit_profile_do()
{
  global $mybb, $db, $templates;
  //Was hat der User eingestellt?

  $blacklistAlert_view = $mybb->input['bl'];
  $blacklistAlert_mail = $mybb->input['blMail'];
  $blacklistAlert_info = $mybb->input['blInfo'];
  $blacklistAlert_ice = $mybb->input['blIce'];
  $blacklistas = $mybb->input['blas'];
  //blacklistAlert_ice

  if ($blacklistAlert_ice == "") {
    $blacklistAlert_ice = 0;
  }
  //Wer ist online, Wer ist Hauptaccount.
  $this_user = intval($mybb->user['uid']);
  $as_uid = intval($mybb->user['as_uid']);
  if ($as_uid == 0) {
    $id =  $this_user;
  } else {
    $id = $as_uid;
  }

  //Soll für alle Charaktere übernommen werden oder nicht?
  if ($blacklistas == 1) {
    //Ja, alle raussuchen
    //speichern
    //für alle
    $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_view='" . $blacklistAlert_view . "', bl_info=" . $blacklistAlert_info . " WHERE uid=" . $id . " OR as_uid=" . $id . "");
  } else {
    //bl View für alle!
    $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_view='" . $blacklistAlert_view . "' WHERE uid=" . $id . " OR as_uid=" . $id . "");
    //bl info nur für aktuellen Charakter speichern
    $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_info='" . $blacklistAlert_info . "', bl_mail=" . $blacklistAlert_mail . " WHERE uid=" . $this_user . "");
  }

  $db->query("UPDATE " . TABLE_PREFIX . "users SET bl_ice='" . $blacklistAlert_ice . "' WHERE uid=" . $this_user . "");
}

$plugins->add_hook("member_profile_start", "blacklistAlert_viewOnIce");
function blacklistAlert_viewOnIce()
{
  global $db, $mybb, $iceMeldung;
  $this_user = intval($mybb->user['uid']); //wer ist online
  $query = $db->simple_select('users', 'bl_ice', "uid ='" . $this_user . "'", array('LIMIT' => 1));
  $on_ice = $db->fetch_field($query, 'bl_ice');
  if ($on_ice == 1) {
    $iceMeldung = "Dieser Charakter ist auf Eis gelegt.";
  }
}
