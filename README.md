# Native Gesetzesbibliothek für WoltLab Suite 6.2.7

Installierbare WoltLab-Erweiterung mit Gebietssystematik in der linken Seitenleiste, Gesetzesübersicht und einzelnen Gesetzesseiten. Die Darstellung verwendet WCF-Templates, die vorhandenen WoltLab-Komponenten und den jeweils aktivierten Stil. Formulare entstehen mit dem WCF FormBuilder; Gesetzestexte werden im nativen WoltLab-Editor bearbeitet. Das Paket enthält kein eigenes JavaScript und kein eigenes CSS.

## Installation

1. Das Paket `de.eulenthal.wcf.lawLibrary_1.3.1.tar` unter [Releases](https://github.com/tinsever/gesetzesbibliothek/releases/latest) herunterladen oder mit `python3 scripts/package.py` selbst bauen. Anschließend im WoltLab-ACP unter **Konfiguration → Pakete → Paket installieren** hochladen.
2. Unter **Benutzer → Benutzergruppen** die Rechte prüfen: Das Leserecht steht im allgemeinen Bereich **Gesetzesbibliothek**, das Verwaltungsrecht im administrativen Bereich **Inhalt → Gesetzesbibliothek**. Für die Verwaltung ist zusätzlich der ACP-Zugang erforderlich. Standardmäßig können alle lesen; Administratoren dürfen verwalten.
3. Unter **Inhalt → Gesetzesbibliothek → Gebietssystematik** die Rechtsgebiete anpassen.
4. Unter **Inhalt → Gesetzesbibliothek → Gesetze** Gesetze hinzufügen und bearbeiten. Die öffentliche Bibliothek wird über den Menüpunkt **Gesetzesbibliothek** aufgerufen. Verwaltungslinks dort führen ebenfalls ins ACP.

Voraussetzung: WoltLab Suite Core **6.2.7**, innerhalb der Reihe 6.2. Node.js wird auf dem Server nicht benötigt. Eine neue Installation enthält neun vorbereitete Rechtsgebiete und keine Beispielgesetze. Deutsche und englische Sprachvariablen sind enthalten.

Falls Version 1.0.0, 1.1.0, 1.2.0, 1.2.1 oder 1.3.0 bereits installiert wurde, das neue TAR als Paketaktualisierung hochladen. Die gespeicherten Gesetze bleiben erhalten; die eigenen JavaScript- und CSS-Dateien der vorherigen Oberfläche werden entfernt. Alte Klartexte bleiben lesbar und können im nativen Editor weiterbearbeitet werden. Browserlokale Merklisten der Version 1.0.0 werden nicht übernommen. Die serverseitigen Merklisten aus 1.1.0 bleiben erhalten. Das frühere allgemeine Verwaltungsrecht wird durch das administrative Recht **Kann Rechtsgebiete und Gesetze im ACP verwalten** ersetzt. Für zusätzliche Redakteure müssen dieses Recht und der ACP-Zugang ausdrücklich vergeben werden.

## Gebietssystematik im ACP

Unter **Inhalt → Gesetzesbibliothek → Gebietssystematik** lassen sich Rechtsgebiete anlegen und bearbeiten:

- **Name und Beschreibung** erscheinen in der Bibliothek. Die Beschreibung wird beim Öffnen des Rechtsgebiets angezeigt.
- **Nummernkreis** ist die Zahl vor „xx“: 1 ergibt 101–199, 12 ergibt 1201–1299. Möglich sind die Nummernkreise 1 bis 9999; jeder darf nur einmal vorkommen.
- **Einfügen vor** bestimmt die Reihenfolge der Rechtsgebiete in der Seitenleiste. Beim Bearbeiten ist die aktuelle Position vorausgewählt. Die Reihenfolge und die Nummernkreise sind unabhängig voneinander.
- Ein anderer Nummernkreis nummeriert alle Gesetze des Rechtsgebiets automatisch um. Die Zuordnung und die beständigen Gesetzeslinks bleiben erhalten.
- Ein leeres Rechtsgebiet kann im Bearbeitungsformular über **Dieses Rechtsgebiet beim Absenden löschen → Ja** entfernt werden. Gesetze müssen vorher in ein anderes Rechtsgebiet verschoben werden. Das letzte Rechtsgebiet lässt sich nicht löschen.

Es sind höchstens 99 Rechtsgebiete mit jeweils höchstens 99 Gesetzen möglich. Die neun mitgelieferten Rechtsgebiete sind Startwerte und frei anpassbar. Verschachtelte Untergebiete sind nicht Bestandteil dieser Version.

## Nummerierung und Bedienung

- Öffentliches Recht: **101–199**, Privatrecht: **201–299**, entsprechend den im ACP gewählten Nummernkreisen.
- Die Nummer ergibt sich automatisch aus Rechtsgebiet und Position. Beim Einfügen vor 102 erhält das neue Gesetz 102; das bisherige 102 wird 103.
- Im nativen ACP-Erstellungs- und Bearbeitungsformular bestimmen **Rechtsgebiet** und **Einfügen vor** die Position. **Am Ende** hängt das Gesetz an. Damit lassen sich vorhandene Gesetze auch umsortieren und zwischen Rechtsgebieten verschieben.
- Pro Rechtsgebiet sind höchstens 99 Gesetze möglich. Die Grenze wird vor dem Speichern geprüft.
- Links verwenden einen unveränderlichen Schlüssel (`lawKey`) und bleiben bei Umnummerierungen erhalten. Nummern, die als Klartext in Gesetzestexten stehen, werden nicht automatisch ersetzt.

Die linke WoltLab-Seitenleiste zeigt die Rechtsgebiete und die nummerierten Gesetze des geöffneten Rechtsgebiets. Auf Mobilgeräten übernimmt WoltLab die Darstellung der Seitenleiste. Suche nach Nummer, Titel, Abkürzung und Gesetzestext, Statusfilter, zuletzt geänderte Gesetze sowie eine persönliche Merkliste sind enthalten. Die Suche umfasst alle Rechtsgebiete; in der Merkliste durchsucht sie nur gemerkte Gesetze.

Gesetzestexte unterstützen den nativen Rich-Text-Editor. Überschriften und deren Inhaltsverzeichnis verarbeitet WoltLab. Die Status „In Kraft“, „Verkündet“ und „Außer Kraft“ sind redaktionelle Angaben; das Inkrafttretensdatum ändert den Status nicht automatisch.

Artikelimport und eine Löschfunktion für Gesetze sind nicht enthalten. Gesetze können bearbeitet und außer Kraft gesetzt werden.

## Buchstabenlisten (1.3.1)

Auf einer Gesetzesseite führt **Listenformatierung** direkt zu einer separaten Einstellung. Unter **Kennzeichnung der obersten Listenebene → Großbuchstaben: A., B., C.** werden die obersten geordneten Listen alphabetisch dargestellt, damit beispielsweise ein Verweis auf **F.** zur sichtbaren Kennzeichnung passt. Diese Auswahl steht auch im normalen Gesetzesformular zur Verfügung.

Die separate Listenformatierung ändert ausschließlich die Darstellungsoption des gewählten Gesetzes. Der gespeicherte Gesetzestext wird nicht erneut durch den Editor verarbeitet; Text, IDs, Reihenfolge, Änderungsdatum und Historie bleiben erhalten. Unterlisten, Aufzählungspunkte, zitierte Listen und das native Inhaltsverzeichnis behalten ihre bisherige Kennzeichnung. Jede Liste zählt separat. **Standard (unverändert)** stellt die bisherige Darstellung wieder her. Die Paketaktualisierung schaltet vorhandene Gesetze nicht automatisch um.

WoltLabs nativer Texteditor zeigt weiterhin Ziffern; die Buchstaben erscheinen auf der öffentlichen Gesetzesseite. Die Option gilt für alle obersten geordneten Listen des gewählten Gesetzes. Eine unterschiedliche Kennzeichnung einzelner oberster Listen ist damit nicht möglich.

## Anzeigename und Änderungshistorie (1.3.0)

Unter **Konfiguration → Optionen → Gesetzesbibliothek → Allgemein → Name der Bibliothek** lässt sich beispielsweise **EulLex** eintragen. Der Name gilt für die öffentliche Überschrift, den Seitentitel sowie den Hauptmenüpunkt und die ACP-Inhaltsrubrik. Leer lassen stellt den bisherigen, sprachabhängigen Namen wieder her. Der Paketbezeichner und die URLs bleiben gleich. Die WoltLab-Seitenverwaltung behält ihre technischen Seitennamen; ein separat individuell beschrifteter Menüpunkt bleibt individuell.

Im Gesetzesformular ist **Zuletzt geändert** jetzt ein frei wählbares Kalenderdatum. Bei bestehenden Gesetzen wird das bisher gespeicherte Datum übernommen und beim Bearbeiten nicht mehr automatisch durch das Tagesdatum ersetzt. Die Übersicht zeigt das Datum zusätzlich zum Inkrafttreten an; **Zuletzt geändert** sortiert weiterhin nach diesem redaktionellen Datum.

- Mit **Änderndes Gesetz** im Gesetzesformular wird optional ein anderer, bereits gespeicherter Bibliothekseintrag mit dem gewählten Datum zur Historie hinzugefügt. Bestehende Verknüpfungen bleiben erhalten; dieselbe Kombination aus Datum und Gesetz wird nicht doppelt angelegt.
- Unter **Änderungshistorie → Änderung hinzufügen** lassen sich weitere, auch ältere Änderungen nachtragen. Jeder Eintrag kann über das Stiftsymbol korrigiert oder ausdrücklich entfernt werden. Diese Formulare verändern den Gesetzestext nicht.
- Die öffentliche Gesetzesseite zeigt die Historie absteigend nach Datum und verlinkt die ändernden Gesetze. Verweise mit dem Datum **Zuletzt geändert** erscheinen zusätzlich direkt bei diesem Datum. Mehrere ändernde Gesetze am selben Tag sind möglich.
- Neuere Historieneinträge setzen **Zuletzt geändert** vor; ältere Einträge ändern es nicht. Nach Korrektur oder Entfernung eines Eintrags bleibt das manuelle Datum erhalten und kann im Gesetzesformular zurückgesetzt werden. Es darf nicht vor einem noch vorhandenen Historieneintrag liegen.
- Links verwenden stabile IDs und zeigen stets die aktuelle Nummer und den aktuellen Titel des ändernden Gesetzes an. Selbstverweise und Verweise auf nicht vorhandene Gesetze werden abgewiesen.

Die Historie dokumentiert, **welche Gesetze ein Gesetz wann geändert haben**. Frühere Textfassungen werden dadurch nicht rekonstruiert oder archiviert. Vor dem Update entstandene Änderungen können manuell nachgetragen werden.

### Verlustfreies Update

**Das Paket als Aktualisierung installieren, nicht deinstallieren und neu installieren.** Die Updatepfade von 1.0.0, 1.1.0, 1.2.0, 1.2.1 und 1.3.0 sind enthalten. Kein Updatepfad ersetzt oder initialisiert die bestehende Gesetzessammlung. Neue Historienfelder sind optional; vorhandene Gesetze, Texte, IDs, Rechtsgebiete, Reihenfolgen und Änderungsdaten müssen nicht migriert werden. Auch das Ändern des Anzeigenamens schreibt keine Gesetzesdaten. Die serverseitigen Merklisten bleiben erhalten. Nur beim Update von 1.0.0 wird die damals noch fehlende Merklistentabelle angelegt.

Beim gleichzeitigen Bearbeiten von Gesetzen und Historieneinträgen greift derselbe atomare Revisionsvergleich wie bisher. Ein bereits vor dem Update geöffnetes Gesetzesformular ohne das neue Datumsfeld wird beim Absenden abgewiesen; nach Sicherung der Eingabe das Formular neu laden.

## Speicherung und Schutz vor Konflikten

Die geordnete Sammlung liegt serverseitig in `wcfN_law_library`. Schreiboperationen verwenden einen atomaren Revisionsvergleich. Ändert jemand die Sammlung zwischen Öffnen und Absenden eines Formulars, erscheint eine native Fehlermeldung; die eigene Eingabe bleibt im Formular erhalten. Zum erneuten Speichern den Text sichern und das Formular neu laden.

Merklisten liegen pro Benutzer in `wcfN_law_library_bookmark` und sind damit browserübergreifend verfügbar. Lese- und Verwaltungsrechte werden serverseitig geprüft. Änderungen und Merklistenaktionen benötigen WoltLabs CSRF-Token. Rich Text wird durch WoltLabs Eingabe- und Ausgabeprozessoren verarbeitet.

Das Datenmodell ist für eine kleine Sammlung ausgelegt: höchstens 12 MB insgesamt und 200.000 Zeichen pro Gesetzestext. Die gesamte Sammlung wird als JSON-Dokument gelesen; für große Sammlungen wäre ein Datenmodell mit einzelnen Gesetzesdatensätzen und einer Suchindexanbindung sinnvoll.

## Entwicklung

Quellen: `woltlab/files/lib/` (PHP), `woltlab/templates/` (öffentliche Seiten), `woltlab/acptemplates/` (ACP) und `woltlab/language/`. Das aktive Projekt enthält keine eigenständige Frontend-Vorschau mehr.

```sh
python3 scripts/package.py
php tests/domain.php
python3 tests/package.py
```

Der Paketbau benötigt ausschließlich Python 3. Die PHP-Tests benötigen PHP mit `mbstring`.

Für Integrationstests wird eine echte, separat installierte WoltLab Suite 6.2.7 mit installiertem Paket benötigt. Die Tests erstellen Gesetze und Rechtsgebiete und sollten deshalb ausschließlich in einer Testinstallation laufen. Sie erwarten die ursprünglichen Rechtsgebiete Öffentliches Recht und Privatrecht sowie freie Nummernkreise 50 bis 52. Einen angemeldeten Administratorzustand als Playwright-Storage-State bereitstellen:

```sh
npm ci
LAW_NATIVE_TEST_URL=https://test.example.invalid \
LAW_NATIVE_ADMIN_STATE=/absoluter/pfad/admin-state.json \
npm run test:e2e
```

Google Chrome muss lokal verfügbar sein. Die lokalen Standardwerte zeigen auf die Entwicklungsinstallation unter `https://127.0.0.1:8447` und `.local/native-admin.json`. Diese lokalen Zugangsdaten gehören nicht ins Paket.

## Geprüfter Stand

Version **1.3.1** wurde mit den PHP-Domänentests, WoltLabs tatsächlichen HTML-Eingabe- und Ausgabeprozessoren, dem nativen FormBuilder sowie den Paket- und XML-Prüfungen geprüft. Eine Browser-Sichtprüfung der damit erzeugten HTML-Ausgabe bestätigt Buchstaben auf der obersten Ebene und Ziffern in Unterlisten. Es wurde keine Änderung an der produktiven Website vorgenommen. Die vollständigen Browser-Szenarien und der ACP-Upgrade-Test wurden für 1.3.1 nicht erneut ausgeführt.

Der zusätzliche Rendering- und Formulartest läuft mit `LAW_WCF_DIR=/absoluter/pfad/zur/woltlab-installation php tests/render-lists.php` gegen eine lokale WoltLab-Testinstallation. Er schreibt keine Gesetzesdatensätze.

Version **1.3.0** wurde mit verlustfreiem ACP-Update von 1.2.1, PHP-Domänentests und vier Browser-Szenarien geprüft. Der zugehörige Prüfbericht und die Bilder liegen ausschließlich in den lokalen Entwicklungsartefakten.

Das Paket wurde im ACP einer echten lokalen **WoltLab Suite 6.2.7** von 1.1.0 auf 1.2.0 aktualisiert. Die gespeicherte Sammlung blieb beim Update bytegenau erhalten. Die automatisierten Browsertests prüfen den nativen Editor, Einfügen und Umnummerierung, stabile Links, Wechsel des Rechtsgebiets, Suche, Merklistenpersistenz, konkurrierende Bearbeitungen und Gastberechtigungen. Die ACP-Tests prüfen außerdem Anlegen, Umbenennen, Sortieren, Nummernkreiswechsel, öffentliche Beschreibung, doppelte Nummernkreise, Löschschutz, gleichzeitige Änderungen und CSRF-Prüfung. Zusätzlich wurden die PHP-Nummerierungs- und Validierungsregeln sowie die Paket-XML gegen die offiziellen WoltLab-Schemata geprüft.

Die lokale Entwicklungsinstallation verwendet einen TLS-Proxy und eine ausschließlich lokale Anpassung der WCF-Hostprüfung für den hohen Testport. Diese Anpassung ist kein Bestandteil der Erweiterung. Das Paket verändert keine WoltLab-Kerndateien.

Lokale Entwicklungsartefakte unter `artifacts/` und die Testinstallation unter `.local/` sind nicht Teil dieses Repositorys oder des installierbaren Pakets.

Referenzen: [WoltLab-Entwicklerdokumentation 6.2](https://docs.woltlab.com/6.2/), [WoltLab-Quellcode 6.2](https://github.com/WoltLab/WCF/tree/6.2).


## Sicherheitsprüfung 1.2.1

Die Rechte gelten für die gesamte Bibliothek. Es gibt ein Leserecht und ein administratives Verwaltungsrecht, das zusätzlich den ACP-Zugang voraussetzt. Separate Rechte pro Rechtsgebiet, getrennte Rechte für Anlegen/Bearbeiten/Löschen, Freigabeabläufe, ein Änderungsprotokoll und eine Wiederherstellung früherer Fassungen sind nicht implementiert.

Version 1.2.1 lehnt ungültige Kalenderdaten vor dem Speichern ab, verlangt eine tatsächlich übermittelte Revision und weist falsch strukturierte Formularwerte ab. Fehlende oder ungültige CSRF-Tokens bei Merklistenaktionen liefern einen Fehlerstatus.

Zusätzlich zu den Funktionstests wurden eingeschränkte Benutzerkonten, direkte ACP-Anfragen, gesperrter Lesezugriff, benutzergebundene Merklisten, manipulierte Eingaben und HTML-/Script-Injektionen geprüft. Die Ergebnisse und Prüfgrenzen wurden lokal in `artifacts/SICHERHEITSPRÜFUNG.md` dokumentiert; dieser Bericht wird nicht mitveröffentlicht. Die Tests sind keine unabhängige Sicherheitszertifizierung und ersetzen keine Datensicherung der WoltLab-Installation.

`tests/security.spec.js` benötigt eigene Testkonten und Beispielgesetze in einer isolierten lokalen Installation. Die Tests verändern Beispielinhalte. Sie sind ohne `LAW_SECURITY_PASSWORD` deaktiviert; die lokale Prüfkonfiguration liegt in `playwright.security.config.js`.
