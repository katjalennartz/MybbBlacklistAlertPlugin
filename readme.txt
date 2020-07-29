Readme Blacklist Plugin
Autor: Risuena
Kontakt/Support:
https://storming-gates.de/member.php?action=profile&uid=39
https://lslv.de 


_________________
UPGRADE VON VERSION 1.0:
Altes Deinstallieren 
auf jedenfall inc/plugin/blacklist.php löschen
(und inc/tasks/blacklist.php) 

Dann neues hochladen und installieren
Einstellungen erneut machen (Achtung ein paar neue ;) )

_______________


Dieses Plugin ermöglicht eine automatische Warnung an Charaktere, 
die auf der Blacklist stehen würden.

Der Admin kann(und muss) im ACP (Einstellungen) :
- Den Zeitraum festlegen (in Tagen für angenommene Mitglieder)
- Datum der Blacklist (An welchem Tag im Monat soll die BL erscheinen?)
- Anzeige Warnung. (An welchem Tag im Monat soll das bl_info wieder zurückgesetzt und die Warnung angezeigt werden)
- Gruppe für Bewerber festlegen (-> ID der Gruppe angeben)
- Zeitraum für Bewerber (Den Zeitraum festlegen für diese Gruppe -> Tage)
- Bewerbungsarea (Area für Bewerber in der die Steckbriefe gepostet werden (auch Area mit Unterforen möglich))
- Ingamebereich (Der Ingamebereich des Forums)
- (optional) Archivbereich festlegen
- Einstellen ob der Accountswitcher berücksichtigt werden soll
- Einstellen ob die Abwesenheit berücksichtigt werden soll
- Einstellung ob User Charaktere auf Eis legen können
- Einstellen ob das Erledigt/Unerledigt Plugin verwendet wird
- User angeben die ausgeschlossen werden (Teamaccount, NPC...? -> uids mit , getrennt angeben)
- Einen Text festlegen der zusätzlich angezeigt werden soll.
- Dürfen User die Übersichtsseite sehen
- Dürfen Gäste die Übersichtsseite sehen.


Der User kann:
Im UCP (Profil bearbeiten) einstellen
ob er die Anzeige überhaupt haben möchte.

Für den aktuellen Monat die Anzeige pro Charakter (oder wahlweise alle) ausstellen
Aktivieren ob er eine Mail bekommen möchte
Seinen Charakter auf Eis legen


Task:
Automatisch wird einmal im Monat an einem gewählten Tag die monatliche Anzeige der User zurückgesetzt. (z.B eine Woche vor der BL)


Dateien:
inc/plugins/blacklistAlert.php
inc/tasks/blacklistAlert.php
blacklist_show.php


Templates die angelegt werden:


globale Templates:
- blacklistAlert_index_warning
	Box auf der index seite
- blacklistAlert_ucp
	Einstellungen der User -> User CP -> Profil ändern
- blacklistAlert_iceUCP
	User CP, wenn der Admin erlaubt dass Charaktere auf Eis gelegt werden
- blacklistAlert_blackbit
	Anzeige der einzelnen Charaktere (angenommene Mitglieder) auf dem Index
- blacklistAlert_blackbit_bewerber
	Anzeige der Charaktere, Gruppe Bewerber
- blacklist_ucp'
	Möglichkeit der Einstellungen im UCP
- blacklistAlert_lastpost
	Übersichtsseite aller die auf der BL stehen
	(erreichbar über forenadresse/blacklist_show.php )
- blacklistAlert_lastpost_bit
	anzeige der Charas (angenommene Mitglieder)
- blacklist_lastpost_bit_bewerber
	anzeige der Charas (angenommene Bewerber)
- blacklistAlert_ShowUserPosts_bit
	anzeige der aktiven (nicht im Archiv, nicht geschlossen, nicht erledigt) 		Szenen des users in der blacklist_show

im header.tpl hinzugefügt: 
{$blacklistAlert_index_warning} -> ruft blacklistAlert_index_warning.tpl auf

im usercp_profile.tpl hinzugefügt 
{$blacklistAlert_ucp} -> ruft blacklistAlert_ucp.tpl auf


Optional: (selber einfügen!) 
im User profil:
{$iceMeldung} -> Meldung dass der Charakter auf Eis liegt

Tabellen Felder die erstellt werden: (Datenbank)
in der (mybb_)users Tabelle:
bl_info (monatliche anzeige 1 -> wird angezeig, 0 -> nicht angezeigt)
bl_info_timestamp (in der aktuellen version nicht mehr verwendet, aber ich habs mal drin gelassen -> speichert wann der User bl_info ausschaltet) 
bl_view -> blendet die anzeige IMMER aus
bl_mail -> monatliche Mail wenn der User auf der BL steht Default 0 (keine mail) User können das im Profil einstellen
bl_ice -> Ob der Charakter auf Eis liegt oder nicht (1 -> liegt auf eis, 0 -> nicht auf eis) 