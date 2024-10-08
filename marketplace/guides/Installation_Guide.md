---
author: axytos GmbH
title: "Installationsanleitung"
subtitle: "axytos Kauf auf Rechnung für OXID5"
header-right: axytos Kauf auf Rechnung für OXID5
lang: "de"
titlepage: true
titlepage-rule-height: 2
toc-own-page: true
linkcolor: blue
---

## Installationsanleitung

Das Modul stellt die Bezahlmethode __Kauf Auf Rechnung__ für den Einkauf in Ihrem OXID Shop bereit.

Einkäufe mit dieser Bezahlmethode werden von axytos ggf. bis zum Forderungsmanagement übernommen.

Alle relevanten Änderungen an Bestellungen mit dieser Bezahlmethode werden automatisch an axytos übermittelt.

Anpassungen über die Installation hinaus, z.B. von Rechnungs- und E-Mail-Templates, sind nicht notwendig.

Weitere Informationen erhalten Sie unter [https://www.axytos.com/](https://www.axytos.com/).


## Voraussetzungen

1. Vertragsbeziehung mit [https://www.axytos.com/](https://www.axytos.com/).

2. Verbindungsdaten, um das Modul mit [https://portal.axytos.com/](https://portal.axytos.com/) zu verbinden.

Um dieses Modul nutzen zu können benötigen Sie zunächst eine Vertragsbeziehung mit [https://www.axytos.com/](https://www.axytos.com/).

Während des Onboarding erhalten Sie die notwendigen Verbindungsdaten, um das Modul mit [https://portal.axytos.com/](https://portal.axytos.com/) zu verbinden.


## Modul-Installation

Zuerst muss der Code des Moduls heruntergeladen und auf dem Server installiert werden. Befolgen Sie dazu folgende Schritte:

1. Zur Installation Ihrer OXID Distribution wechseln. Dort im Ordner `modules` einen neuen Ordner namens `axytos` anlegen

2. Den Quellcode des __Kauf auf Rechnung__ OXID-5 Moduls herunterladen und im `axytos`-Ordner in einen Ordner namens
`kaufaufrechnung` entpacken

3. Mit dem Terminal in den `modules/axytos/kaufaufrechnung` Ordner wechseln und dort das `install.php` script ausführen:

```bash
cd <oxid-installations-ordner>/modules/axytos/kaufaufrechnung
php install.php
```

Nachdem das Modul erfolgreich installiert wurde, kann es nun aktiviert werden. Dies erfolgt über die
Administrations-Oberfläche von OXID.

1. Zur Administration Ihrer OXID Distribution wechseln. Nach Installation ist das Modul unter _Erweiterungen > Module_ aufgeführt.

2. Unter _Stamm_ __Aktivieren__ ausführen.

Das Modul ist jetzt installiert und aktiviert und kann konfiguriert werden.

Um das Modul nutzen zu können, benötigen Sie valide Verbindungsdaten zu [https://portal.axytos.com/](https://portal.axytos.com/) (siehe Voraussetzungen).


## Modul- und Shop-Konfiguration in OXID

1. Zur Administration Ihrer OXID Distribution wechseln. Das Modul ist unter _Erweiterungen > Module_ aufgeführt.

2. Zu _Einstell._ wechseln und _API Einstellungen_ aufklappen, um die Konfiguration zu öffnen.

3. __API Host__ auswählen, entweder 'Live' oder 'Sandbox'.

4. __API Key__ zwei mal eintragen. Der korrekte Wert wird Ihnen während des Onboarding von axytos mitgeteilt (siehe Voraussetzungen).

5. __Client Secret__ zwei mal eintragen. Der korrekte Wert wird Ihnen ebenfalls im Onboarding mitgeteilt (siehe Voraussetzungen).

6. __Speichern__ ausführen.

7. Die Bezahlmethode einer Versandart unter _Shopeinstellungen > Versandarten > (Ausgewählte Versandart) > Zahlungsarten > Zahlungsarten zuordnen_ zuordnen.

Zur Konfiguration müssen Sie valide Verbindungsdaten zu [https://portal.axytos.com/](https://portal.axytos.com/) (siehe Voraussetzungen), d.h. __API Host__, __API Key__ und __Client Secret__ für das Modul speichern.

## Cron-Job aktivieren

Das __Kauf Auf Rechnung__ Modul benötigt einen regelmäßig ausgeführten Cron-Job, um Änderungen am Bestellstatus
synchronisieren zu können. Dieser wird automatisch in OXID registriert, sobald das Modul aktiviert wurde.

Damit dieser jedoch auch ausgeführt wird, muss der OXID-Cron-Job selbst aktiviert werden. Dazu müssen folgende
Voraussetzungen gegeben sein:

1. Cron muss in dem Betriebssystem installiert und aktiviert sein. Falls dies nicht der Fall ist, kontaktieren Sie
ihren Server-Administrator, um Cron zu installieren und aktivieren.

2. Es muss ein Cron-Job existieren, welcher `<oxid-installations-ordner>/bin/cron.php` in regelmäßigen Abständen
ausführt. Falls dies nicht der Fall ist, kann mit folgendem Befehl einer angelegt werden, welcher die Synchronisation
einmal stündlich anstößt. Dabei muss `<oxid-installations-ordner>` durch den vollständigen Dateipfad zu dem Ordner
Ihrer OXID-Installation ersetzt werden.

```bash
echo '0 */1 * * * php <oxid-installations-ordner>/bin/cron.php' > /tmp/crontab.txt
crontab /tmp/crontab.txt
rm /tmp/crontab.txt
```

__Hinweis:__ Es ist auch möglich, andere Scheduler-Lösung zu verwenden. Wichtig ist nur, dass `bin/cron.php` regelmäßig
ausgeführt wird. Für alternative Scheduler stellen wir keine Einrichtungshilfen bereit.

## Kauf auf Rechnung kann nicht für Einkäufe ausgewählt werden?

Überprüfen Sie folgende Punkte:

1. Das Modul __axytos Kauf auf Rechnung__ ist installiert.

2. Das Modul __axytos Kauf auf Rechnung__ ist aktiviert.

3. Das Modul __axytos Kauf auf Rechnung__ ist mit korrekten Verbindungsdaten (__API Host__ & __API Key__) konfiguriert.

4. Sie haben Versandarten, Benutzergruppen und Länder zugeordnet.

5. Der OXID-Cron-Job ist aktiviert und wird regelmäßig ausgeführt.

Fehlerhafte Verbindungsdaten führen dazu, dass das Modul nicht für Einkäufe ausgewählt werden kann. Wird der Cron-Job
zu selten ausgeführt (z.B. nur 1x täglich) führt dies dazu, das Änderungen an einer Bestellung nur verzögert übertragen
werden.
