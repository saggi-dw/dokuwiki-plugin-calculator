# Calculator für DokuWiki

[English](README.md) | **Deutsch**

Ein Rechnerbaukasten für dein Wiki: Definiere Eingabefelder und Formeln auf einer Wiki-Seite und binde die fertigen Rechner dort ein, wo sie gebraucht werden.

Das Plugin unterstützt einzelne Zahlen, Zahlenlisten und Zahlen in freien Texten. Ein integrierter Chemie-Rechner berechnet zusätzlich molare Massen und die Massenanteile der Elemente aus einer chemischen Formel.

**Dokumentierter Stand:** 0.6.5 · **Autoren:** saggi und ChatGPT · **Lizenz:** [GNU GPL v2](LICENSE)

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
| Eigene Rechner | Eingabefelder und Formeln als Wiki-Definition anlegen; z. B. für Einheitenumrechnungen, Mengenplanung, Listenstatistik oder Preisberechnungen. |
| Zahlenlisten | Jede Zeile einzeln berechnen und die Reihenfolge erhalten. |
| RegEx-Textlisten | Benannte Zahlengruppen je Zeile erkennen und berechnen; eine Gruppe value lässt sich im Ursprungstext ersetzen. |
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

Dieses Beispiel berechnet eine Endkonzentration aus der Ausgangskonzentration und dem Anteil der Ausgangslösung an der fertigen Mischung.

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
  label = Anteil der Ausgangslösung an der Endmischung
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
| `regex-list` | Benannte Zahlen aus Textzeilen erfassen | `label`, `pattern`, optional `flags`, `unit`, `default`, `min`, `max`, `break` |
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
| `template` | Optionale Textvorlage für die Ausgabe; siehe unten. |
| `break` | Mit `true` eine neue Zeile beginnen und die volle Breite belegen; Standard `false`. |

Ohne `type` wird eine Einzelausgabe angenommen. Mit `source` wird der Typ passend zum Eingang gewählt: `decimal-list` bei einer Zahlenliste oder RegEx-Liste ohne Gruppe `value`; `text-list` bei einer RegEx-Liste mit Gruppe `value`. Eine RegEx-Quelle kann auch ausdrücklich als `decimal-list` ausgegeben werden.

Bei Zahlenlisten ist `value` der aktuelle Zahlenwert des Eingangs. RegEx-Quellen stellen benannte Gruppen als `row.name` bereit; bei einer Gruppe `value` zusätzlich als `value`. Eine Einzelausgabe hat keine `source` und kein `value`. `source` verweist immer auf einen **Eingang**, nicht auf eine andere Ausgabe.

### Ausgabevorlagen

Mit `template` lässt sich der angezeigte Text je Ergebnis gestalten. Die Eigenschaft steht im Ausgabeblock, ohne umschließende Anführungszeichen:

```text
  template = {line} → {label}: {value} {unit}
```

Beispiel bei deutschem Zahlenformat:

```text
1.25 kg x 2 units @ 8.00 EUR/kg → Price per row: 20,00 EUR
```

| Platzhalter | Ersetzung |
|---|---|
| `{line}` | Die ursprüngliche Eingabezeile einschließlich ihrer Leerzeichen; nur bei Listenausgaben. |
| `{label}` | Die Beschriftung der Ausgabe. |
| `{value}` | Die berechnete Zahl im Zahlenformat der Wiki-Sprache, mit `decimals` und `fixed`. |
| `{unit}` | Die Einheit der Ausgabe oder ein leerer Text, falls keine definiert ist. |

Die Reihenfolge ist frei, etwa `{value} {unit} ← {line}`. Vorlagen funktionieren auch bei Einzelausgaben; dort ist `{line}` nicht verfügbar. Leere und nicht erkannte Eingabezeilen bleiben unverändert. Die Vorlage betrifft nur die Anzeige: Aggregate und weitere Ausgaben verwenden weiterhin die ungerundeten Zahlen.

Ohne `template` bleibt das bisherige Verhalten erhalten, einschließlich des Ersetzens der Gruppe `value` in Textlisten. Mit Vorlage wird stattdessen die gesamte Ausgabezeile gestaltet. Vorlagen sind einfacher Text, kein HTML und keine Rechenausdrücke. Erlaubt ist eine nicht leere Zeile mit höchstens 2000 UTF-8-Bytes und den vier Platzhaltern; zusätzliche geschweifte Klammern oder Escape-Regeln sind nicht vorgesehen. Eine vollständige Definition steht in [templated_output.txt](examples/templated_output.txt).

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

### Bedingungen und optionale Gruppen

`exists(row.name)` prüft, ob eine benannte RegEx-Gruppe in der aktuellen Zeile Text erfasst hat. Das Ergebnis ist `1` bei einer belegten Gruppe und `0`, wenn die optionale Gruppe oder Alternative nicht beteiligt war. Eine erfasste Null **ist vorhanden**. Auch eine leer erfasste Zeichenfolge ist vorhanden, lässt sich aber nicht als Zahl verrechnen. Erlaubt ist genau eine bekannte `row.name`-Gruppe aus der RegEx-Quelle der Ausgabe; keine beliebigen Ausdrücke, Einzeleingaben oder Listen.

`if(bedingung, dann, sonst)` wertet die Bedingung und anschließend **nur den gewählten Zweig** aus. Bei Null wird `sonst` gewählt, bei jeder anderen endlichen Zahl `dann`. Ein leeres Ergebnis (`null`), etwa `avg()` einer leeren Liste, bleibt leer, ohne einen Zweig auszuwerten. Aufrufe dürfen innerhalb der bisherigen Größen- und Verschachtelungsgrenzen verschachtelt werden.

```text
  formula = if(exists(row.component), row.component * factor, 0)
```

Für mehrere alternative Zeilentypen:

```text
  formula = if(exists(row.item), row.item, if(exists(row.discount), -row.discount, row.fee))
```

Direkter Zugriff auf eine fehlende Gruppe bleibt ein Fehler. Benannte Gruppen werden erst beim Zugriff in Zahlen umgewandelt und gegen die Eingabegrenzen geprüft; `exists()` prüft nur ihr Vorhandensein, nicht ihre Gültigkeit als Zahl. Normale Zahleneingaben und die bisherige Gruppe `value` werden weiterhin wie zuvor validiert. Leere und nicht erkannte Zeilen werden weiterhin übersprungen.

Alle Zweige werden beim Laden auf Syntax, gültige Namen und Ausgabeabhängigkeiten geprüft. Ausgabeabhängigkeiten bleiben statisch: `if()` umgeht weder ungültige Definitionen noch Kreisbezüge oder Fehler in einer anderen Ausgabe. Übersprungen wird nur die Berechnung innerhalb des eigenen ungewählten Zweigs. [conditional_rows.txt](examples/conditional_rows.txt) enthält einen vollständigen Rechner mit verschachtelten Bedingungen, Ausgabevorlagen und Gesamtsumme.

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

Ein `regex-list`-Eingang erkennt benannte Zahlengruppen je Zeile. Mit einer Gruppe `value` kann eine `text-list`-Ausgabe diesen Zahlenbereich ersetzen; der übrige Text bleibt erhalten. Ohne `value` wird je Treffer ein Ergebnis in einer separaten `decimal-list` berechnet.

```text
<calcdefinition scale_text>
title = Mengen im Text umrechnen
showwork = true

input factor:
  type = decimal
  label = Skalierungsfaktor
  default = 2
  min = 0

input quantities:
  type = regex-list
  label = Eine Menge am Anfang jeder Zeile
  default = "100 g Mehl
250 ml Milch
2 Eier
Salz nach Geschmack"
  pattern = ^\s*(?:[•*]\s*|-\s+)?(?<value>[+-]?(?:\d+(?:[.,]\d+)?|[.,]\d+))
  flags = u
  min = 0
  break = true

output scaled:
  type = text-list
  source = quantities
  label = Umgerechneter Text
  formula = value * factor
  decimals = 3
  break = true
</calcdefinition>
```

Einbindung: `<calcuser scale_text>`. Mit Faktor `2` wird aus `100 g Mehl` die Zeile `200 g Mehl`; `Salz nach Geschmack` bleibt erhalten.

- Das Muster benötigt mindestens eine benannte Gruppe. **`value`** ist das Ersetzungsziel einer `text-list`-Ausgabe.
- `pattern` enthält die rohe RegEx ohne umgebende `/`-Trennzeichen. Backslashes direkt schreiben, etwa `\d`.
- `flags` erlaubt `i` und `u`, jeweils höchstens einmal; Standard ist `u`.
- Je Zeile wird nur der erste Treffer verarbeitet. Leerzeilen und Zeilen ohne Treffer bleiben unverändert.
- Zusätzliche Zahlen, Einheiten und andere Texte außerhalb der Gruppe `value` bleiben erhalten.
- Ein Treffer ohne gültige Zahl oder außerhalb von `min`/`max` führt zu einem Fehler mit Zeilennummer.

Die Definitionsprüfung verwendet PHP-PCRE, die Berechnung JavaScript-RegExp. Muster sollten deshalb mit beiden Varianten kompatibel sein. Die Ausführung erfolgt in einem Web Worker mit **zwei Sekunden Zeitlimit**; neue Eingaben brechen eine laufende Auswertung ab.

### Mehrere Zahlengruppen pro Zeile

Benannte Gruppen wie `(?<quantity>...)`, `(?<count>...)` und `(?<price>...)` sind als `row.quantity`, `row.count` und `row.price` verfügbar. Beispiel innerhalb einer Definition:

```text
input items:
  type = regex-list
  label = Quantity, count and price per row
  pattern = ^(?<quantity>\d+(?:[.,]\d+)?)\s*kg\s*x\s*(?<count>\d+)\s*units\s*@\s*(?<price>\d+(?:[.,]\d+)?)\s*EUR/kg$
  flags = u

output line_totals:
  type = decimal-list
  source = items
  label = Price per row
  formula = row.quantity * row.count * row.price
  decimals = 2
  fixed = true
```

Die Zeile `2,5 kg x 3 units @ 4,20 EUR/kg` ergibt im deutschen Wiki `31,50`. Eine Einzelausgabe kann anschließend `sum(line_totals)` verwenden. [multi_value_text.txt](examples/multi_value_text.txt) enthält den vollständigen Rechner mit Standardwerten und Gesamtsumme.

Gruppennamen beginnen mit einem Kleinbuchstaben und enthalten Kleinbuchstaben, Ziffern und Unterstriche. Die Gruppen stehen nur in Listenformeln mit dem zugehörigen RegEx-Eingang als `source` zur Verfügung. `row.price` und ein Eingabefeld `price` bleiben eindeutig.

- Verwendete Gruppen müssen gültige Dezimalzahlen enthalten. Fehlt eine verwendete optionale Gruppe bei einem Treffer, erscheint ein Fehler.
- `min` und `max` gelten für die von Formeln verwendeten Zahlengruppen. Eine vorhandene Gruppe `value` wird immer geprüft.
- Leerzeilen und Zeilen ohne Treffer ergeben leere Ergebniszeilen. Eine eingegebene Null zählt als Zahl.
- Unbenutzte Textgruppen bleiben erlaubt; bisherige Muster bleiben damit kompatibel.
- Ohne Gruppe `value` die berechnete Ergebnisliste aggregieren, etwa `sum(line_totals)` oder `count(line_totals)`. Der Eingang selbst hat keine eindeutige Zahlenspalte.
- Eine `text-list` benötigt `value` und ersetzt nur diese Gruppe. Andere Ersetzungsziele sind noch nicht vorgesehen.
- Bisherige Formeln mit `value` bleiben gültig; alternativ ist `row.value` verfügbar.

## Chemie-Rechner

Der Chemie-Rechner benötigt keine Definition auf der zentralen Wiki-Seite.

| Wiki-Syntax | Verhalten |
|---|---|
| `<calcchem>` | Formel- und Anteilseingabe mit sofortiger Berechnung. Ein leerer Anteil bedeutet 100 %; erlaubt sind 0 bis 100 %. |
| `<calcchem H2O>` | Fest vorgegebene Formel, gesetzte Notation und Ergebnistabelle ohne Eingabefelder; Anteil 100 %. |

Die Ausgabe zeigt molare Masse, Elementsymbole, Atomanzahl, Atommassen sowie Massenanteile in Prozent und ppm. Indizes werden tiefgestellt, etwa **H₂O** und **CuSO₄·5H₂O**.

Prozentwerte werden mit drei Nachkommastellen angezeigt, ppm als ganze Zahlen; hier werden sprachabhängige Tausendertrennzeichen verwendet. Ein Doppelklick auf eine Zahlenzelle oder Enter bei fokussierter Zelle kopiert den ungerundeten Wert. Die Tabellen-Kopierfunktion übernimmt die angezeigten Werte als tabulatorgetrennten Text.

### Unterstützte Formeln

- Elementsymbole mit korrekter Groß-/Kleinschreibung: `H2O`, nicht `h2o`.
- Ganze positive Indizes und Vorfaktoren sowie wiederholte Elementsymbole.
- Runde, eckige und geschweifte, auch verschachtelte Klammern.
- Kristallwasser und Komponenten mit `·`, `*` oder `.` als Trennzeichen.
- Tiefgestellte Ziffern als Eingabe, beispielsweise `H₂O`.

Nicht unterstützt werden Stoffnamen, CAS-Suche, Ladungen, Isotopenangaben, gebrochene/dezimale Indizes, variable Gruppen oder alternative Belegungen wie `(Mg,Fe)`. Leerzeichen innerhalb einer Formel werden nicht automatisch entfernt. Ein normaler Punkt ist immer ein Komponententrenner, kein Dezimalzeichen.

Die Elementanteile beziehen sich auf die reine Verbindung bzw. den eingegebenen Mischungsanteil. Ihre Summe entspricht dem Anteil; molare Masse und Atomanzahl bleiben davon unabhängig. Führende Faktoren multiplizieren die vollständige Formeleinheit: Bei `2H2O` bezieht sich die ausgegebene Masse auf beide Einheiten.

Hinterlegt sind 84 Zentralwerte aus den [CIAAW Abridged Standard Atomic Weights 2024](https://www.ciaaw.org/abridged-atomic-weights.htm). Elemente ohne hinterlegten Wert werden abgelehnt; Unsicherheiten und Isotopenvariationen werden nicht fortgepflanzt. Auch feste Formeln werden im Browser berechnet und benötigen JavaScript.

### Formeln aus Struct-Seitendaten

Mit installiertem [Struct-Plugin](https://www.dokuwiki.org/plugin:struct) kann ein fester Chemierechner ein einzelnes Textfeld der aktuellen Seite auslesen:

```text
<calcchem {{$material.formula}}>
```

`material` ist das Schema, `formula` die Feldbezeichnung. Das Feld muss eine einzelne chemische Formel als einfachen Text enthalten, beispielsweise `C32H16CuN8`. Schema- und Feldnamen müssen exakt stimmen. Das gesamte Formelargument muss eine Struct-Referenz sein. Calculator lädt Struct nur bei solchen Referenzen und übergibt deren innere Syntax an Structs eigenen `InlineConfigParser`. Leerzeichen und zitierte Feldnamen sind erlaubt, beispielsweise `<calcchem {{$ material.formula }}>` und `<calcchem {{$material."Chemische Formel"}}>`. Andere Seiten, zusätzliche Filter, Schema-Aliase, mehrere Spalten, globale/serielle Daten und gemischte Formeln aus Text und Referenzen werden nicht unterstützt. Nicht unterstützte Auswahlen ergeben eine übersetzte Meldung und werden niemals stillschweigend als Abfrage der aktuellen Seite behandelt.

Calculator liest den Rohwert beim Rendern über die öffentliche Struct-Schnittstelle und prüft vorher die Leseberechtigung für die aktuelle Seite. Es wird keine Struct-Wikisyntax innerhalb des Tags gerendert. Der Wert wird nicht in Parseranweisungen gespeichert. Bei einer Seite mit dieser Referenz deaktiviert Calculator den Cache der gerenderten Seite, damit gespeicherte Struct-Änderungen beim nächsten Laden erscheinen. Bei alten Seitenrevisionen wird deren Zeitstempel an Struct übergeben. Ungespeicherte Struct-Änderungen werden nicht gelesen.

Fehlendes Struct-Plugin, fehlende/leere Felder, andere Datentypen, Mehrfachwerte und Lesefehler ergeben übersetzte Meldungen. Ein leeres Feld öffnet keinen interaktiven Rechner. Feste Formeln wie `<calcchem TiO2>` und der interaktive Aufruf `<calcchem>` funktionieren weiterhin ohne Struct und behalten das normale Cache-Verhalten.

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

Der [Beispielordner](examples/README.md) enthält eigenständig verwendbare Rechner mit englischen Beschriftungen und neutralen Beispieldaten. Die Zahlenformatierung richtet sich nach der Sprache des Wikis.

| Datei | Beispiel |
|---|---|
| [unit_conversion.txt](examples/unit_conversion.txt) | Kilogramm mit einer lokalen Konstante in Gramm umrechnen. |
| [percentage_conversion.txt](examples/percentage_conversion.txt) | Prozent in ppm und zurück umrechnen. |
| [scale_quantities.txt](examples/scale_quantities.txt) | Eine Mengenliste auf eine andere Anzahl von Portionen oder Einheiten skalieren. |
| [scale_text.txt](examples/scale_text.txt) | Mengen in einem Rezepttext skalieren und übrige Zeilen erhalten. |
| [multi_value_text.txt](examples/multi_value_text.txt) | Zeilenpreis aus Menge, Anzahl und Preis berechnen. |
| [statistics.txt](examples/statistics.txt) | Summe, Anzahl, Mittelwert, Minimum und Maximum berechnen. |
| [price_calculation.txt](examples/price_calculation.txt) | Nettowerte, Steuerbetrag und Bruttosumme mit verknüpften Ausgaben berechnen. |
| [dilution.txt](examples/dilution.txt) | Eine Endkonzentration aus zwei Prozentangaben berechnen. |
| [chemistry.txt](examples/chemistry.txt) | Wasser, Kochsalz, Glucose sowie Formeln mit Gruppen und Kristallwasser untersuchen. |

Die Beispielübersicht nennt Einbindungssyntax und erwartete Ergebnisse. Zahlenmengen nur in einer gemeinsamen Einheit summieren. Das Textbeispiel erhält Einheiten, rechnet sie aber nicht um. Das Preisbeispiel verwendet einen gemeinsamen Einzelpreis und einen beispielhaften Steuersatz.

Nur die benötigten Definitionen übernehmen. Globale Konstanten gegebenenfalls in einen vorhandenen `<calcconstants>`-Block einfügen; doppelte Definitionsnamen vermeiden.

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
| `_test/` | PHP-/JavaScript-Tests und englische Testdaten |

### Tests ausführen

Die PHP-Tests unter `_test/` sind PHPUnit-Tests mit der Gruppe `plugin_calculator`. Sie laufen in DokuWikis Testumgebung. Folgende Befehle gelten im Hauptverzeichnis eines DokuWiki-**Git-Checkouts**, in dem das Plugin unter `lib/plugins/calculator/` liegt:

```sh
COMPOSER=_test/composer.json COMPOSER_VENDOR_DIR=_test/vendor composer install --no-interaction
_test/vendor/bin/phpunit -c _test/phpunit.xml --group plugin_calculator
node --test lib/plugins/calculator/_test/*.test.js
```

Normale DokuWiki-Installationsarchive enthalten die Testumgebung möglicherweise nicht. Dafür einen Entwicklungs-Checkout verwenden. Einzelne PHP-Testklassen lassen sich beispielsweise mit dem zusätzlichen PHPUnit-Argument `--filter CalculatorParserTest` auswählen.

Die PHP-Tests validieren Definitionen und vergleichen die gespeicherten JSON-Testdaten mit den tatsächlichen Parserergebnissen. Die Node-Tests prüfen mit denselben Testdaten die JavaScript-Rechenengine. Die Tests überschreiben ihre Testdaten nicht mehr. Bei einer beabsichtigten Vertragsänderung die betreffende JSON-Datei gezielt aktualisieren und beide Testsuiten ausführen. Struct-Prüfungen mit nachgebildeten Klassen laufen in eigenen PHP-Prozessen, damit sie nicht mit DokuWikis echten Klassen kollidieren.

Der [GitHub-Actions-Workflow](.github/workflows/tests.yml) läuft bei Pushes, Pull Requests und manueller Ausführung. Er verwendet DokuWikis PHPUnit-Workflow für `stable` und `master` mit PHP 8.2, 8.3 und 8.4. Ein separater Job prüft JavaScript-Syntax und Tests mit Node.js 24. Der Workflow erhält nur Leserechte auf das Repository. Er führt keine automatischen Codekorrekturen aus und erstellt keine Pull Requests.

Integrationstests prüfen die echte DokuWiki-Syntaxverarbeitung, Rendering, geänderte Definitionen, übersetzte Fehler, Berechtigungen, AJAX und Cache-Verhalten. Struct bleibt optional; die normale Integrationsumgebung prüft den Betrieb ohne Struct. Die Regressionstests des Adapters verwenden aufgezeichnete Konfigurationen des Struct-Parsers, keine laufende Struct-Datenbank. Einzelheiten stehen in [TESTING.md](TESTING.md).

### Übersetzungen und Erweiterungen

Englisch ist die Basissprache, Deutsch ist enthalten. Weitere Übersetzungen werden unter `lang/<Sprache>/` ergänzt. Titel und Feldbeschriftungen bleiben in der vom Definitionsautor gewählten Sprache.

Der PHP-Parser liefert Sprachschlüssel, Parameter und gegebenenfalls eine Zeilennummer. DokuWiki übersetzt diese über `getLang()`. Weitere programmierte Rechner können als Module unter `DokuCalculator.modules` registriert und über eine Syntax-Komponente eingebunden werden; die Skripteinbindung erfolgt über `JS_SCRIPT_LIST` in `action.php`. Ein Installationsmechanismus für zusätzliche Module ist nicht enthalten.

Die [`.gitattributes`](.gitattributes) schließt Dotdateien, `_test/`, `examples/` und `TESTING.md` bei `git archive` aus. Diese Dateien gehören weiterhin ins Quellcode-Repository; ein solcher Installations-Export enthält sie nicht.

## Lizenz

GNU General Public License, Version 2. Siehe [LICENSE](LICENSE).

Autoren: **saggi und ChatGPT** · Kontakt: <saggi@gmx.de>
