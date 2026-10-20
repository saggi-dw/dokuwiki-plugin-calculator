# Calculator für DokuWiki

[English](README.md) | **Deutsch**

Ein Rechnerbaukasten für dein Wiki: Definiere Eingabefelder und Formeln auf einer Wiki-Seite und binde die fertigen Rechner dort ein, wo sie gebraucht werden.

Das Plugin unterstützt einzelne Zahlen, Zahlenlisten und Zahlen in freien Texten. Ein integrierter Chemie-Rechner berechnet zusätzlich molare Massen und die Massenanteile der Elemente aus einer chemischen Formel.

**Dokumentierter Stand:** 0.5.5 · **Autor:** saggi · **Lizenz:** [GNU GPL v2](LICENSE)

## Inhalt

- [Funktionen](#funktionen)
- [Installation](#installation)
- [Schnellstart](#schnellstart)
- [Definitionen und Felder](#definitionen-und-felder)
- [Formeln und Aggregate](#formeln-und-aggregate)
- [Konstanten](#konstanten)
- [Textlisten mit RegEx](#textlisten-mit-regex)
- [Chemie-Rechner](#chemie-rechner)
- [Konfiguration und Zugriffsrechte](#konfiguration-und-zugriffsrechte)
- [Beispiele](#beispiele)
- [Grenzen und Fehlerbehandlung](#grenzen-und-fehlerbehandlung)
- [Entwicklung und Tests](#entwicklung-und-tests)

## Funktionen

| Funktion | Beschreibung |
|---|---|
| Eigene Rechner | Eingabefelder und Formeln als Wiki-Definition anlegen; z. B. für Verdünnungen, ppm/%-Umrechnungen oder Rezepturmengen. |
| Zahlenlisten | Jede Zeile einzeln berechnen und die Reihenfolge erhalten. |
| RegEx-Textlisten | Eine Zahl je Zeile erkennen, berechnen und im ursprünglichen Text ersetzen. |
| Aggregate | Summe, Anzahl, Mittelwert, Minimum und Maximum einer Liste verwenden. |
| Verknüpfte Ausgaben | Mit berechneten Einzelwerten und Ergebnislisten weiterrechnen. |
| Konstanten | Feste Werte lokal je Rechner oder global auf der Definitionsseite hinterlegen. |
| Darstellung | Einheiten, Nachkommastellen, Standardwerte und Feldumbrüche konfigurieren. |
| Transparenz | Verwendete Formeln, Konstanten und RegEx-Regeln auf Wunsch anzeigen. |
| Chemie | Chemische Formeln setzen, molare Masse und Elementanteile berechnen. |

Die Berechnung erfolgt im Browser. Mehrere Rechner auf derselben Seite arbeiten unabhängig voneinander. Externe JavaScript-Bibliotheken oder Onlinedienste werden nicht benötigt.

## Installation

1. Den Pluginordner nach `lib/plugins/calculator/` kopieren.
2. Unter **Admin → Konfiguration → calculator** die Definitionsseite festlegen. Standard ist `calculator:definitions`.
3. Die Definitionsseite anlegen und einen Rechner wie im Schnellstart eintragen.

Beim Aktualisieren auch die Unterordner übernehmen. Falls noch das ursprüngliche Plugin-Gerüst installiert ist, dessen leere Datei `syntax.php` entfernen; die Syntax-Komponenten liegen unter `syntax/`.

Das Plugin legt keine Wiki-Seiten an und verändert keine bestehenden Seiten.

### Voraussetzungen

- DokuWiki mit namespaced Extension-Klassen und PHP **ab 7.4**.
- Ein Browser mit aktuellem JavaScript; für RegEx-Listen zusätzlich Web Worker und RegExp-Gruppenindizes.
- Bei einer Content Security Policy muss für RegEx-Listen `worker-src 'self'` zulässig sein.

Eine Integrationsprüfung erfolgte mit DokuWiki **2026-07-14c „Mort“** und PHP **8.3**. Weitere Angaben zum Teststand stehen in [TESTING.md](TESTING.md).

## Schnellstart

Dieses Beispiel berechnet eine Endkonzentration aus Ausgangskonzentration und Einsatzanteil.

**1. Auf der Seite `calculator:definitions` eintragen:**

```text
<calcdefinition dilution>
title = Verdünnung

input concentration:
  type = decimal
  label = Ausgangskonzentration
  unit = %
  default = 40
  min = 0
  max = 100

input addition:
  type = decimal
  label = Einsatzanteil
  unit = %
  default = 2
  min = 0
  max = 100

output result:
  label = Endkonzentration
  unit = %
  formula = concentration * addition / 100
  decimals = 3
  fixed = true
</calcdefinition>
```

Den Block als normalen Wiki-Quelltext einfügen, **ohne zusätzlichen `<code>`-Block**. Bei gültiger Definition zeigt die Definitionsseite nur den Titel; Fehler erscheinen mit einer lokalisierten Meldung und, soweit zuordenbar, der Zeile innerhalb des Blocks.

**2. Auf einer beliebigen Wiki-Seite einbinden:**

```text
<calcuser dilution>
```

Mit den Standardwerten `40 %` und `2 %` erscheint **0,800 %**. Nach Änderungen an den Eingaben wird automatisch neu berechnet. **Berechnen** startet die Berechnung sofort; die Kopierfunktion übernimmt die angezeigten Ergebnisse.

## Definitionen und Felder

Eine zentrale Definitionsseite kann mehrere benannte `<calcdefinition name>`-Blöcke enthalten.

### Syntaxregeln

- Definitionsnamen beginnen mit einem Kleinbuchstaben. Danach sind Kleinbuchstaben, Ziffern, `_` und `-` erlaubt; maximal 64 Zeichen.
- Feldnamen folgen denselben Regeln, erlauben aber keinen Bindestrich. Sie müssen innerhalb des Rechners eindeutig sein; `value` ist reserviert.
- `title`, `showwork`, `input` und `output` beginnen am Zeilenanfang. Feldeigenschaften werden eingerückt.
- Leere Zeilen und eigene Kommentarzeilen mit `#` werden außerhalb zitierter Standardtexte ignoriert.
- Titel und Beschriftungen sind Klartext.
- Ein Rechner benötigt mindestens eine Ausgabe und unterstützt höchstens **einen Listeneingang**. Einzelwerte und Listenausgaben lassen sich kombinieren.

### Eingabetypen

| `type` | Verwendung | Eigenschaften zusätzlich zu `type` |
|---|---|---|
| `decimal` | Ein einzelner Zahlenwert | `label`, optional `unit`, `default`, `min`, `max`, `break` |
| `decimal-list` | Eine Zahl pro Zeile | `label`, optional `unit`, `default`, `min`, `max`, `break` |
| `regex-list` | Zahlen aus Textzeilen erfassen | `label`, `pattern`, optional `flags`, `unit`, `default`, `min`, `max`, `break` |
| `const` | Eine feste lokale Konstante | `value`, optional `label`, `unit`; kein Eingabefeld |

`min` und `max` sind einschließlich ihrer Grenzwerte gültig. Bei Listen werden sie auf jeden erkannten Zahlenwert angewendet. Einzelne Zahlenfelder dürfen nicht leer bleiben; leere Listen sind erlaubt.

### Ausgaben

| Eigenschaft | Bedeutung |
|---|---|
| `label` | Beschriftung; erforderlich. |
| `formula` | Rechenausdruck; erforderlich. |
| `type` | `decimal`, `decimal-list` oder `text-list`; siehe unten. |
| `source` | Name des Listeneingangs für eine Listenausgabe. |
| `unit` | Optionale Anzeigeeinheit; bewirkt keine automatische Umrechnung. |
| `decimals` | Maximal angezeigte Nachkommastellen: `0` bis `12`, Standard `3`. |
| `fixed` | Mit `true` genau `decimals` Stellen anzeigen; Standard `false`. |
| `break` | Mit `true` eine neue Zeile beginnen und die volle Breite belegen; Standard `false`. |

Ohne `type` wird eine Einzelausgabe angenommen. Mit `source` wird der Typ passend zum Eingang gewählt: `decimal-list` bei einer Zahlenliste, `text-list` bei einer RegEx-Liste.

In Listenausgaben ist `value` der aktuelle Zahlenwert des Eingangs. Eine Einzelausgabe hat keine `source` und kein `value`. `source` verweist immer auf einen **Eingang**, nicht auf eine andere Ausgabe.

### Standardwerte und Layout

Listen können mit mehrzeiligen Texten vorbelegt werden:

```text
input quantities:
  type = decimal-list
  label = Mengen
  default = "0,500
0,060
0,040"
  min = 0
  break = true
```

Fortsetzungszeilen müssen nicht eingerückt sein. Leerzeichen, Tabulatoren, Leerzeilen und `#`-Zeilen innerhalb des Texts bleiben erhalten; Zeilenenden werden auf LF normalisiert. Nach dem schließenden Anführungszeichen ist nur Leerraum erlaubt.

Innerhalb eines zitierten Standardtexts stehen `\"` für ein doppeltes Anführungszeichen und `\\` für einen Backslash. Andere Backslash-Folgen bleiben wörtlich erhalten: Für einen Zeilenumbruch eine echte neue Zeile eingeben, nicht `\n`.

Zahlenlisten-Standardwerte werden bereits bei der Definitionsprüfung auf Zahlen und Grenzen geprüft. RegEx-Standardtexte werden beim Berechnen wie normale Texteingaben verarbeitet.

Mit `showwork = true` auf Definitionsebene wird die **Berechnungsgrundlage** eingeblendet: Formeln, verwendete Konstanten und RegEx-Regeln. Standard ist `false`. Das betrifft die Darstellung; die Definition wird für die Berechnung weiterhin an den Browser übertragen.

## Formeln und Aggregate

### Zahlen und Operatoren

Zahleneingaben akzeptieren Dezimalkomma oder Dezimalpunkt, aber keine Tausendertrennzeichen, Einheiten, Brüche oder Exponentialschreibweise. **`1.000` bedeutet `1`, nicht eintausend.**

In Formeln ausschließlich den **Dezimalpunkt** verwenden. Kommas trennen Funktionsargumente.

| Ausdruck | Bedeutung |
|---|---|
| `+`, `-`, `*`, `/` | Grundrechenarten |
| `%` | Rest einer Division, kein Prozentzeichen |
| `^` | Potenz |
| `( … )`, `+x`, `-x` | Klammern und Vorzeichen |
| `abs(x)`, `sqrt(x)` | Betrag und Quadratwurzel |
| `round(x)`, `floor(x)`, `ceil(x)` | Runden, Abrunden und Aufrunden auf ganze Zahlen |
| `pow(x, y)` | Potenzfunktion |
| `min(x, y, …)`, `max(x, y, …)` | Minimum bzw. Maximum von 2 bis 16 Einzelwerten |

Es gibt keine implizite Multiplikation: `2 * amount` statt `2amount`. Potenzen sind rechtsassoziativ: `2^3^2 = 512`; `-2^2 = -4`. `round()` folgt JavaScript `Math.round()`, beispielsweise `round(-1.5) = -1`.

### Listenfunktionen

| Funktion | Ergebnis | Bei leerer Liste |
|---|---|---|
| `sum(quantities)` | Summe | `0` |
| `count(quantities)` | Anzahl der Zahlen | `0` |
| `avg(quantities)` | Arithmetischer Mittelwert | Leer |
| `min(quantities)` | Kleinster Wert | Leer |
| `max(quantities)` | Größter Wert | Leer |

`quantities` ist hier der Name eines Listeneingangs oder einer berechneten Ergebnisliste. Leerzeilen und RegEx-Zeilen ohne Zahl werden ignoriert; eine eingegebene Null zählt mit. Fehlende Ergebnisse bleiben auch in weiteren Ausdrücken leer, etwa bei `avg(quantities) + 1`.

Aggregate akzeptieren jeweils einen Listennamen. `sum(quantities * 2)`, `sum(1, 2)` oder `avg(1, 2)` sind nicht erlaubt.

### Mit Ausgaben weiterrechnen

Einzelausgaben lassen sich direkt über ihren Namen verwenden; Ergebnislisten über eine Aggregatfunktion:

```text
output net_amounts:
  type = decimal-list
  source = quantities
  label = Nettowerte
  formula = value * price
  decimals = 2
  fixed = true

output tax:
  label = Steuerbetrag
  formula = sum(net_amounts) * global.tax_rate
  decimals = 2
  fixed = true
```

Hierzu müssen die Eingänge `quantities` und `price` sowie die globale Konstante `tax_rate` definiert sein. Die Reihenfolge der Ausgaben ist beliebig: Abhängigkeiten werden automatisch aufgelöst, Kreisbezüge als Fehler gemeldet. Angezeigt wird weiterhin in Definitionsreihenfolge.

### Anzeige und Rechenwert

`decimals` und `fixed` ändern ausschließlich die Anzeige. Weitergerechnet wird mit den **ungerundeten Ergebnissen**, bei Textlisten mit den Zahlen hinter dem Text. Der allgemeine Rechner verwendet keine Tausendertrennzeichen.

Soll bereits das Rechenergebnis auf drei Stellen gerundet werden, dies ausdrücklich in die Formel aufnehmen:

```text
formula = round(value * factor * 1000) / 1000
```

Eine nachfolgende Summe verwendet dann diese gerundeten Werte. JavaScript rechnet mit Gleitkommazahlen, nicht mit exakter Dezimalarithmetik.

## Konstanten

**Lokale Konstanten** stehen in der jeweiligen Rechnerdefinition und erzeugen kein Eingabefeld:

```text
input factor:
  type = const
  label = Umrechnungsfaktor
  value = 10000
```

Eine Formel kann anschließend `amount * factor` verwenden. `value` ist erforderlich; ohne `label` dient der Name als Beschriftung. `default`, `min`, `max` und `break` sind bei Konstanten nicht vorgesehen.

**Globale Konstanten** stehen in einem gemeinsamen Block auf der zentralen Definitionsseite:

```text
<calcconstants>
ppm_per_percent = 10000
grams_per_kg = 1000
tax_rate = 0.19
</calcconstants>
```

Der Zugriff erfolgt beispielsweise über `global.ppm_per_percent`. Das Präfix ist Teil des zugelassenen Namens, kein allgemeiner Objektzugriff. Pro Seite ist ein Konstantenblock mit höchstens 128 Einträgen erlaubt. Werte sind Zahlen, keine Formeln oder Verweise auf andere Konstanten.

Namen beginnen mit einem Kleinbuchstaben; danach sind Kleinbuchstaben, Ziffern und `_` erlaubt, maximal 64 Zeichen. Lokale und globale Konstanten dürfen gleich heißen. Mit `showwork = true` werden nur die tatsächlich verwendeten Konstanten angezeigt.

## Textlisten mit RegEx

Ein `regex-list`-Eingang erkennt eine Zahl je Zeile. Eine `text-list`-Ausgabe ersetzt genau diesen Zahlenbereich; der übrige Text bleibt erhalten.

```text
<calcdefinition scale_text>
title = Mengen im Text umrechnen

input factor:
  type = decimal
  label = Faktor
  default = 2
  min = 0

input quantities:
  type = regex-list
  label = Eine kg-Menge pro Zeile
  default = "ITEM-A 0,500 kg
ITEM-B 0,060 kg
ITEM-C 0,040 kg"
  pattern = (?<value>[+-]?\d+(?:[.,]\d+)?)(?=\s+kg(?:\s|$))
  flags = iu
  min = 0
  break = true

output scaled:
  type = text-list
  source = quantities
  label = Umgerechnete Mengen
  formula = value * factor
  decimals = 3
  fixed = true
  break = true
</calcdefinition>
```

Einbindung: `<calcuser scale_text>`. Mit Faktor `2` wird aus `ITEM-A 0,500 kg` die Zeile `ITEM-A 1,000 kg`.

- Die benannte Gruppe muss **`value`** heißen und eine Zahl erfassen.
- `pattern` enthält die rohe RegEx ohne umgebende `/`-Trennzeichen. Backslashes direkt schreiben, etwa `\d`.
- `flags` erlaubt `i` und `u`, jeweils höchstens einmal; Standard ist `u`.
- Je Zeile wird nur der erste Treffer verarbeitet. Leerzeilen und Zeilen ohne Treffer bleiben unverändert.
- Zusätzliche Zahlen, Einheiten und andere Texte außerhalb der Gruppe `value` bleiben erhalten.
- Ein Treffer ohne gültige Zahl oder außerhalb von `min`/`max` führt zu einem Fehler mit Zeilennummer.

Die Definitionsprüfung verwendet PHP-PCRE, die Berechnung JavaScript-RegExp. Muster sollten deshalb mit beiden Varianten kompatibel sein. Die Ausführung erfolgt in einem Web Worker mit **zwei Sekunden Zeitlimit**; neue Eingaben brechen eine laufende Auswertung ab.

## Chemie-Rechner

Der Chemie-Rechner benötigt keine Definition auf der zentralen Wiki-Seite.

| Wiki-Syntax | Verhalten |
|---|---|
| `<calcchem>` | Formel- und Anteilseingabe mit sofortiger Berechnung. Ein leerer Anteil bedeutet 100 %; erlaubt sind 0 bis 100 %. |
| `<calcchem TiO2>` | Fest vorgegebene Formel, gesetzte Notation und Ergebnistabelle ohne Eingabefelder; Anteil 100 %. |

Die Ausgabe zeigt molare Masse, Elementsymbole, Atomanzahl, Atommassen sowie Massenanteile in Prozent und ppm. Indizes werden tiefgestellt, etwa **TiO₂** und **CuSO₄·5H₂O**.

Prozentwerte werden mit drei Nachkommastellen angezeigt, ppm als ganze Zahlen; hier werden sprachabhängige Tausendertrennzeichen verwendet. Ein Doppelklick auf eine Zahlenzelle oder Enter bei fokussierter Zelle kopiert den ungerundeten Wert. Die Tabellen-Kopierfunktion übernimmt die angezeigten Werte als tabulatorgetrennten Text.

### Unterstützte Formeln

- Elementsymbole mit korrekter Groß-/Kleinschreibung: `TiO2`, nicht `tio2`.
- Ganze positive Indizes und Vorfaktoren sowie wiederholte Elementsymbole.
- Runde, eckige und geschweifte, auch verschachtelte Klammern.
- Kristallwasser und Komponenten mit `·`, `*` oder `.` als Trennzeichen.
- Tiefgestellte Ziffern als Eingabe, beispielsweise `H₂O`.

Nicht unterstützt werden Stoffnamen, CAS-Suche, Ladungen, Isotopenangaben, gebrochene/dezimale Indizes, variable Gruppen oder alternative Belegungen wie `(Mg,Fe)`. Leerzeichen innerhalb einer Formel werden nicht automatisch entfernt. Ein normaler Punkt ist immer ein Komponententrenner, kein Dezimalzeichen.

Die Elementanteile beziehen sich auf die reine Verbindung bzw. den eingegebenen Mischungsanteil. Ihre Summe entspricht dem Anteil; molare Masse und Atomanzahl bleiben davon unabhängig. Führende Faktoren multiplizieren die vollständige Formeleinheit: Bei `2H2O` bezieht sich die ausgegebene Masse auf beide Einheiten.

Hinterlegt sind 84 Zentralwerte aus den [CIAAW Abridged Standard Atomic Weights 2024](https://www.ciaaw.org/abridged-atomic-weights.htm). Elemente ohne hinterlegten Wert werden abgelehnt; Unsicherheiten und Isotopenvariationen werden nicht fortgepflanzt. Auch feste Formeln werden im Browser berechnet und benötigen JavaScript.

## Konfiguration und Zugriffsrechte

Unter **Admin → Konfiguration → calculator** stehen zwei Einstellungen bereit:

| Einstellung | Standard | Bedeutung |
|---|---|---|
| `definitions_page` | `calculator:definitions` | Vollständige Seiten-ID der zentralen Definitionsseite. |
| `require_definition_read` | Eingeschaltet | Zusätzlich zum Leserecht auf der Rechnerseite ist Leserecht auf der Definitionsseite erforderlich. |

Bei ausgeschaltetem `require_definition_read` dürfen Leser der Rechnerseite die benötigte Definition auch ohne Leserecht auf deren Ursprungsseite verwenden. Die Definition wird dabei an ihren Browser übertragen und darf daher keine vertraulichen Angaben enthalten.

Definitionen werden bei jedem Aufruf der Rechnerseite über DokuWikis AJAX-Endpunkt geladen. Die Antwort wird nicht gecacht; die Rechte werden bei jedem Abruf geprüft. Der Wiki-Seiten-Cache enthält nur den Platzhalter. Nach Änderungen an Definitionen oder Konfiguration bereits geöffnete Rechnerseiten neu laden.

Die Titelansicht der Definitionsseite und `showwork = false` verbergen keinen Quelltext: Bearbeitungsansicht, Seitenquelltext und Versionsgeschichte bleiben nach den normalen Wiki-Rechten verfügbar. Der Chemie-Rechner benötigt keinen Definitionsabruf.

## Beispiele

Vollständige Wiki-Beispiele liegen im Repository unter [`examples/`](examples/):

| Datei | Beispiel |
|---|---|
| [definitions.txt](examples/definitions.txt) | Rezeptmengen auf eine andere Personenzahl skalieren. |
| [verduennung.txt](examples/verduennung.txt) | Verdünnung mit zwei Einzelwerten. |
| [konstanten.txt](examples/konstanten.txt) | ppm-Umrechnung mit lokalen und globalen Konstanten. |
| [rezept_regex.txt](examples/rezept_regex.txt) | Zutatenmengen im Text ersetzen. |
| [dosierungswechsel.txt](examples/dosierungswechsel.txt) | Komponenten umrechnen und Trägerrest berechnen. |
| [mehrwertsteuer.txt](examples/mehrwertsteuer.txt) | Ergebnislisten, Nettosumme, Steuerbetrag und Bruttosumme verknüpfen. |
| [chemie.txt](examples/chemie.txt) | Interaktive und fest vorgegebene chemische Formeln. |

Beim Dosierungswechsel nur Komponenten **ohne Träger** in die Liste aufnehmen und die Gesamtmenge einschließlich Träger separat eingeben. Der Faktor ist bisherige Dosierung geteilt durch neue Dosierung. Das Beispiel erkennt oder entfernt Trägerzeilen nicht automatisch. Ein negativer Trägerrest wird angezeigt, aber nicht automatisch als Fehler abgewiesen.

## Grenzen und Fehlerbehandlung

| Bereich | Grenze |
|---|---|
| Rechnerdefinition | 32 Eingaben, 8 Ausgaben, höchstens ein Listeneingang |
| Definitionsseite | 64 Definitionen, 1 MiB Quelltext |
| Globale Konstanten | 128 Einträge |
| Formel | 512 Zeichen, 32 Verschachtelungsebenen |
| Listeneingabe | 10.000 Zeilen, 200.000 Zeichen |
| Listen-Standardtext | 10.000 Zeilen, 200.000 UTF-8-Bytes |
| RegEx-Muster | 512 Bytes |
| Chemische Formel | 2.000 Zeichen, 32 Klammer-Ebenen, sichere ganze Atomanzahlen |

Die Definitionsseite prüft Syntax, Eigenschaften, Typen, Quellen, Standardwerte und Formelabhängigkeiten auch in der Vorschau. Eine gültige Definition garantiert noch kein gültiges Ergebnis für jede Eingabe: Division durch null oder nicht endliche Ergebnisse werden erst beim Berechnen erkannt.

Bei Berechnungsfehlern werden alte Ergebnisse gelöscht und Kopierfunktionen deaktiviert. Für Listen wird die betroffene Zeile angegeben. Gerundete Anzeigen können sich beim Summieren geringfügig vom angezeigten Gesamtwert unterscheiden.

Für das Kopieren wird, soweit verfügbar, die Clipboard-API verwendet. Andernfalls versucht das Plugin einen Kopierfallback; wenn auch dieser nicht möglich ist, kann der markierte Text manuell kopiert werden.

## Entwicklung und Tests

PHP liest und prüft die Definitionen; JavaScript übernimmt die Berechnung. Der Ausdrucksparser verwendet weder `eval()` noch `Function()`.

| Verzeichnis / Datei | Aufgabe |
|---|---|
| `syntax/` | Wiki-Syntax für Rechner, Definitionen und Konstanten |
| `inc/` | Definitionsparser und serverseitige Formelprüfung |
| `modules/` | Berechnung, Benutzeroberflächen und RegEx-Worker |
| `action.php`, `helper.php` | AJAX, Seitenzugriff, Rechte- und Cache-Anbindung |
| `script.js`, `style.css` | Initialisierung und Darstellung |
| `lang/en/`, `lang/de/` | Englische und deutsche Übersetzungen |
| `_tests/` | PHP-/JavaScript-Tests und englische Testdaten |

### Tests ausführen

Im Pluginordner zuerst die PHP-Tests starten; sie erzeugen auch die von JavaScript verwendeten JSON-Testdaten. Danach die JavaScript-Tests ausführen. Beispiel für eine POSIX-Shell:

```sh
for test in _tests/*.test.php; do
  php "$test" || exit 1
done

node --test _tests/*.test.js
```

Die Tests prüfen unter anderem Formeln, Aggregate, Ausgabereferenzen, Standardwerte, Rundung, Internationalisierung und Chemie. Einzelne Tests lassen sich direkt mit `php` bzw. `node --test` starten. Details und frühere Integrationsprüfungen stehen in [TESTING.md](TESTING.md).

### Übersetzungen und Erweiterungen

Englisch ist die Basissprache, Deutsch ist enthalten. Weitere Übersetzungen werden unter `lang/<Sprache>/` ergänzt. Titel und Feldbeschriftungen bleiben in der vom Definitionsautor gewählten Sprache.

Der PHP-Parser liefert Sprachschlüssel, Parameter und gegebenenfalls eine Zeilennummer. DokuWiki übersetzt diese über `getLang()`. Weitere programmierte Rechner können als Module unter `DokuCalculator.modules` registriert und über eine Syntax-Komponente eingebunden werden; die Skripteinbindung erfolgt über `JS_SCRIPT_LIST` in `action.php`. Ein Installationsmechanismus für zusätzliche Module ist nicht enthalten.

Die [`.gitattributes`](.gitattributes) schließt Dotdateien, `_tests/`, `examples/` und `TESTING.md` bei `git archive` aus. Diese Dateien gehören weiterhin ins Quellcode-Repository; ein solcher Installations-Export enthält sie nicht.

## Lizenz

GNU General Public License, Version 2. Siehe [LICENSE](LICENSE).

Autor: **saggi** · Kontakt: <saggi@gmx.de>
