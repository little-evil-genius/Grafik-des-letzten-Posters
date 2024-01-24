# Grafik des letzten Posters
Dieses Plugin bietet die Möglichkeit eine Grafik des letzten Posters auf dem Index (Forumbit) oder in der Übersicht der einzelnen Threads (Forumdisplay) anzuzeigen. Das Team kann auswählen, ob der Avatar, ein Link aus einem klassisches Profilfeld/Steckbrieffeld oder ein Element aus dem Uploadsystem als Grafik dargestellt werden soll. Zudem besteht die Option eine Standardgrafik festzulegen und die Sichtbarkeit für Gäste zu konfigurieren.<br>
<br>
Eine besondere Funktion des Plugins ist die Anzeige spezieller Grafiken für bestimmte Accounts. Es handelt sich um jene Accounts, welche je nach Design einen anderen Avatar bzw. farblich angepasste Grafik besitzen soll. In den Einstellungen werden die entsprechenden UIDs hinterlegen. Um die individuelle Grafiken/Avatare korrekt anzeigen zu lassen, wird der Benutzername des Accounts verwendet, um einen Dateinamen zu erstellen. Das Dateiformat spielt dabei keine Rolle, da das Plugin automatisch nach der entsprechenden Datei sucht. Möglich sind aber die Formate: PNG, JEPG, JPG und GIF.<br>
Um sicherzustellen, dass die Dateinamen maschinell lesbar sind, werden die Benutzernamen entsprechend umgeformt. Umlaute wie ä werden zu ae, ü zu ue, ö zu oe und ß zu ss umgewandelt. Zudem werden Zeichen wie ' und ` entfernt sowie alle Buchstaben in Kleinbuchstaben umgewandelt. Für besondere Buchstaben außerhalb des deutschen Alphabets können individuelle Umformungen angegeben werden in den Einstellungen.
<br>
<b>HINWEIS:</b><br>
Das Plugin ist kompatibel mit den klassischen Profilfeldern von MyBB, dem <a href="https://github.com/katjalennartz/application_ucp">Steckbrief-Plugin</a> von <a href="https://github.com/katjalennartz">risuena</a> und dem <a href="https://github.com/little-evil-genius/Upload-System">Uploadsystem</a> von mir.

# Einstellungen
- Grafiktyp
- Identifikator von dem Upload-Element
- FID von dem Profilfeld
- Identifikator von dem Steckbrieffeld
- Standard-Grafik
- Gäste Ansicht
- spezielle Accounts

# Neues Templatess (nicht global!) 
-  forumbit_depth2_forum_lastpost_avatar (Forum Bit Templates)
-  forumdisplay_thread_lastpost_graphic (Forum Display Templates)

# Neue Variable
- forumbit_depth2_forum_lastpost: {$forum['lastavatar']}
- forumdisplay_thread: {$thread['lastavatar']}

# Demo
<img src="https://stormborn.at/plugins/lastgraphic_settings.png">
<img src="https://stormborn.at/plugins/lastgraphic_user.png">
<img src="https://stormborn.at/plugins/lastgraphic_gast.png">
<img src="https://stormborn.at/plugins/lastgraphic_threads.png">
