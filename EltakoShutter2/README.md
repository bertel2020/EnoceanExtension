<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
  </head>

  <body>
	<h1>Enocean Module mit erweitertem Funktionsumfang</h1>
	<h2>Grundsätzliches</h2>
	Die Module haben denselben Funktionsumfang, wie die Grundmodule von Symcon.
	Der Befehlsaufruf ändert sich allerdings. Der Prefix <b><i>ENO</i></b> wird durch den Prefix <b><i>ENOEXT</i></b> ersetzt. Der Rest des Aufrufs bleibt inklusiver der zu übergebenden Parameter identisch.
	<h2>EltakoShutter</h2>
	Das Modul wurde für den <b>FSB14</b> entwickelt, sollte aber auch für den <b>FSB61</b> und den <b>FSB71</b> einsetzbar sein.<br>
	Das Modul kann zum exakten Anfahren einer bestimmten Rollladenposition konfiguriert werden.
	<h3>Kalibrierung</h3>
	Das Kalibriermenü wird in der Modulansicht mit <b><i>KALIBRIEREN</i></b> aufgerufen.<br><br>
	<b><i>Fahrzeit Schließen (0-100%)</i></b>
	<ol>
		<li>Rolladen <b>vollständig</b> öffnen</li>
		<li>Kalibrierung starten</li>
		<li>Sobald der Rollladen vollständig geschlossen ist, <b><i>KALIBRIEREN</i></b> drücken. Je exakter Sie den Zeitpunkt treffen, um so exakter ist die Kalibrierung</li>
	</ol>
	Durch den Vorgang wird der Wert von <b><i>Fahrzeit Schließen (0-100%)</i></b> überschrieben. Zu Feinkalibrierung können Sie den Wert in der Modulansicht händisch anpassen.<br><br>
	<b><i>Fahrzeit Öffnen (100-0%)</i></b>
	<ol>
		<li>Rolladen <b>vollständig</b> schließen</li>
		<li>Kalibrierung starten</li>
		<li>Sobald der Rollladen vollständig geöffnet ist, <b><i>KALIBRIEREN</i></b> drücken. Je exakter Sie den Zeitpunkt treffen, um so exakter ist die Kalibrierung</li>
	</ol>
	Durch den Vorgang wird der Wert von <b><i>Fahrzeit Öffnen (100-0%)</i></b> überschrieben. Zu Feinkalibrierung können Sie den Wert in der Modulansicht händisch anpassen.<br><br>
	<b><i>Wickelfaktor</i></b><br><br>
	Der Wickelfaktor berücksichtigt die Tatsache, dass die Rolle des Rollladens beim Aufrollen größer wird. Dadurch steigt die Geschwindigkeit des Öffnungsvorgangs.
	Je größer der Wickelfaktor desto größer der Effekt.<br>
	Bei Raffstoren gibt es diesen Effekt nicht. Hier ist beim  <i>Wickelfaktor</i> der Wert 1 einzutragen.<br>
	<ol>
		<li>Rolladen komplett öffnen</li>
		<li><b><i>50% SCHLIESSEN</i></b> drücken. Der Rollladen sollte sich jetzt zu 50% schließen</li>
		<li>Ist der Rollladen zu weit geschlossen, den 2.Schritt mit einem größeren Wickelfaktor wiederholen</li>
		<li>Ist der Rollladen zu weit geöffnet, den 2.Schritt mit einem kleineren Wickelfaktor wiederholen</li>
		<li>Passt die Position, sollten zur Kontrolle die Schritte 1 und 2 wiederholt werden</li>
	</ol><br> 	
	<h3>Zusätzliche PHP-Befehle</h3>
	<table>
	  <tr>
		<td>1.&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td><b><i>ENOEXT_ShutterMoveTo($ID, $Position)</i></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Anfahren der gewählten Position</td>
	  </tr>
	  <tr>
		<td>2.</td>
		<td><b><i>ENOEXT_ShutterStepUp($ID)</i></b></td>
		<td>Einen Schritt öffnen (siehe Einstellung <i>Schrittdauer in sec:</i>)</td>
	  </tr>
	  <tr>
		<td>3.</td>
		<td><b><i>ENOEXT_ShutterStepDown($ID)</i></b></td>
		<td>Einen Schritt schließen (siehe Einstellung <i>Schrittdauer in sec:</i>)</td>
	  </tr>
	  <tr>
		<td>4.&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td><b><i>ENOEXT_ShutterMoveUpEx/ENOEXT_ShutterMoveDownEx($ID, $seconds)</i></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>x Sekunden öffnen/schließen</td>
	  </tr>
	  <tr>
		<td>5.&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td><b><i>ENOEXT_SetSlatAngle($ID, $angle)</i></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Anfahren des gewählten Lamellenwinkels</td>
	  </tr>
	</table>
	<h2>Changelog</h2>
	<table>
	  <tr>
		<td>V1.00 &nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>Initialversion</td>
	  </tr>
	</table>
  </body>
</html>
