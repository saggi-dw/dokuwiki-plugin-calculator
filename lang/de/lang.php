<?php
/** Calculator UI strings. GPL-2.0. */
$lang['locale'] = 'de-DE';
$lang['loading'] = 'Rechner wird geladen …';
$lang['load_error'] = 'Rechner konnte nicht geladen werden.';
$lang['calculate'] = 'Berechnen';
$lang['copy'] = 'Ergebnis kopieren';
$lang['copied'] = 'Ergebnis kopiert.';
$lang['copy_error'] = 'Kopieren fehlgeschlagen.';
$lang['copy_select'] = 'Ergebnis ist markiert. Bitte mit Strg+C kopieren.';
$lang['number_hint'] = 'Komma oder Punkt als Dezimalzeichen; keine Tausendertrennzeichen.';
$lang['list_hint'] = 'Eine Zahl pro Zeile. Leere Zeilen bleiben erhalten.';
$lang['invalid_number'] = 'Bitte eine gültige Zahl eingeben.';
$lang['range_error'] = 'Wert liegt außerhalb des erlaubten Bereichs.';
$lang['line'] = 'Zeile';
$lang['division_zero'] = 'Division durch null ist nicht möglich.';
$lang['invalid_expression'] = 'Ungültiger Rechenausdruck in der Definition.';
$lang['unknown_variable'] = 'Unbekannte Variable in der Definition.';
$lang['nonfinite'] = 'Ergebnis ist nicht definiert oder zu groß.';
$lang['empty_list'] = 'Bitte mindestens eine Zahl in die Liste eingeben.';
$lang['required'] = 'Bitte einen Wert eingeben.';
$lang['result'] = 'Ergebnis';
$lang['too_many_lines'] = 'Maximal 10.000 Zeilen und 200.000 Zeichen sind erlaubt.';
$lang['javascript_required'] = 'Für diesen Rechner muss JavaScript aktiviert sein.';
$lang['invalid_name'] = 'Ungültiger Definitionsname.';
$lang['access_denied'] = 'Keine Leseberechtigung für den Rechner oder die Definitionsseite.';
$lang['missing_page'] = 'Die konfigurierte Definitionsseite fehlt.';
$lang['missing_definition'] = 'Rechnerdefinition nicht gefunden:';

// Definition parser diagnostics (translated with DokuWiki getLang()).
$lang['definition_page_too_large'] = 'Definitionsseite ist zu groß (maximal 1 MiB).';
$lang['definition_duplicate_name'] = 'Doppelter Definitionsname: %s';
$lang['definition_too_many'] = 'Maximal %d Definitionen pro Seite.';
$lang['definition_duplicate_title'] = 'Titel mehrfach angegeben.';
$lang['definition_reserved_field'] = 'Feldname doppelt oder reserviert: %s';
$lang['definition_duplicate_property'] = 'Eigenschaft mehrfach angegeben: %s';
$lang['definition_invalid_line'] = 'Unbekannte oder falsch eingerückte Definitionszeile.';
$lang['definition_invalid_title'] = 'Ein Titel mit maximal %d Bytes ist erforderlich.';
$lang['definition_too_many_fields'] = 'Maximal %d Felder sind erlaubt.';
$lang['definition_unknown_property'] = 'Unbekannte Eigenschaft in %s.';
$lang['definition_invalid_label'] = 'Beschriftung fehlt oder ist zu lang: %s (maximal %d Bytes).';
$lang['definition_unit_too_long'] = 'Einheit ist zu lang (maximal %d Bytes).';
$lang['definition_invalid_type'] = 'Eingabetyp muss decimal, decimal-list, regex-list oder const sein.';
$lang['definition_invalid_range'] = 'Minimum ist größer als Maximum.';
$lang['definition_default_out_of_range'] = 'Standardwert liegt außerhalb der Grenzen.';
$lang['definition_invalid_formula'] = 'Gültige Formel fehlt oder ist zu lang (maximal %d Bytes).';
$lang['definition_formula_character'] = 'Unzulässiges Zeichen im Rechenausdruck.';
$lang['definition_invalid_decimals'] = 'decimals muss zwischen %d und %d liegen.';
$lang['definition_invalid_structure'] = 'Erforderlich: %d bis %d Ausgaben und maximal %d Eingaben; höchstens eine Werteliste.';
$lang['definition_invalid_source'] = 'Ausgabequelle muss eine Liste mit passendem Typ sein.';
$lang['definition_invalid_number'] = 'Ungültiger Zahlenwert: %s';
$lang['definition_number_too_large'] = 'Zahlenwert zu groß.';
$lang['definition_line'] = 'Definitionszeile %d: %s';

$lang['definition_invalid_output_type'] = 'Ausgabetyp muss decimal, decimal-list oder text-list sein.';
$lang['definition_scalar_source'] = 'Eine decimal-Ausgabe darf keine source-Eigenschaft haben.';

$lang['definition_expression_syntax'] = 'Ungültiger Rechenausdruck: Operatoren, Zahlen und Klammern prüfen.';
$lang['definition_unknown_variable'] = 'Unbekannte oder hier nicht verfügbare Variable: %s';
$lang['definition_unknown_function'] = 'Unbekannte Funktion: %s';
$lang['definition_function_arguments'] = 'Funktion %s erwartet %d bis %d Argumente.';

$lang['calculation_basis'] = 'Berechnungsgrundlage';
$lang['constant_name'] = 'Konstante';
$lang['constant_value'] = 'Wert';
$lang['constant_scope'] = 'Herkunft';
$lang['scope_local'] = 'Lokal';
$lang['scope_global'] = 'Global';
$lang['global_constants'] = 'Globale Konstanten';
$lang['constants_multiple_blocks'] = 'Nur ein calcconstants-Block pro Definitionsseite ist erlaubt.';
$lang['constants_invalid_line'] = 'Ungültige globale Konstante; erwartet wird name = Zahl.';
$lang['constants_duplicate_name'] = 'Globale Konstante mehrfach definiert: %s';
$lang['constants_too_many'] = 'Maximal %d globale Konstanten sind erlaubt.';
$lang['constants_value_required'] = 'Für die Konstante %s ist value erforderlich.';

$lang['chem_title'] = 'Elementanteile';
$lang['chem_formula'] = 'Chemische Formel';
$lang['chem_part'] = 'Anteil (%) – leer = 100';
$lang['chem_hint'] = 'Beispiele: TiO2, Ca(OH)2, CuSO4·5H2O. Groß-/Kleinschreibung beachten.';
$lang['chem_molar_mass'] = 'Molare Masse';
$lang['chem_total'] = 'Summe der Massenanteile';
$lang['chem_rounding'] = 'Tabellenwerte sind gerundet.';
$lang['chem_element'] = 'Element';
$lang['chem_count'] = 'Atomanzahl';
$lang['chem_weight'] = 'Atommasse (u)';
$lang['chem_percent'] = 'Massenanteil (%)';
$lang['chem_ppm'] = 'Massenanteil (ppm)';
$lang['chem_copy_table'] = 'Tabelle kopieren';
$lang['chem_copy_cell'] = 'Doppelklick oder Enter kopiert den ungerundeten Zahlenwert.';
$lang['chem_enter_formula'] = 'Bitte eine chemische Formel eingeben.';
$lang['chem_part_error'] = 'Bitte einen Anteil zwischen 0 und 100 % eingeben.';
$lang['chem_too_long'] = 'Die Formel ist zu lang (maximal 2000 Zeichen).';
$lang['chem_invalid_count'] = 'Indizes und Vorfaktoren müssen positive ganze Zahlen sein.';
$lang['chem_too_large'] = 'Die Zahlen in der Formel sind zu groß.';
$lang['chem_too_deep'] = 'Zu viele verschachtelte Klammern.';
$lang['chem_missing_bracket'] = 'Eine schließende Klammer fehlt.';
$lang['chem_unknown_element'] = 'Unbekanntes Element oder keine hinterlegte Standardatommasse.';
$lang['chem_invalid_character'] = 'Ungültiges Zeichen in der Formel.';
$lang['chem_empty_group'] = 'Leere Formelgruppe oder fehlender Formelbestandteil.';
$lang['chem_position'] = 'Position';
$lang['chem_basis'] = 'M = Σ(n × Atommasse). Elementanteil (%) = n × Atommasse / M × eingegebener Anteil. ppm = % × 10000. Die molare Masse bleibt vom Anteil unabhängig. Ohne Anteilseingabe gilt die reine Verbindung (100 %).';
$lang['chem_weights_source'] = 'Standardatomgewichte: Zentralwerte ohne Fortpflanzung von Unsicherheiten oder Isotopenvariationen. Quelle:';
$lang['chem_fixed'] = 'Fest vorgegebene Formel';

$lang['regex_hint'] = 'Pro Zeile wird die erste Zahl aus der Gruppe value ersetzt. Text und Zeilen ohne Treffer bleiben erhalten.';
$lang['regex_pattern'] = 'RegEx';
$lang['regex_processing'] = 'Text wird ausgewertet …';
$lang['regex_invalid_pattern'] = 'Ungültige RegEx (maximal 512 Bytes, ohne /-Trennzeichen).';
$lang['regex_invalid_flags'] = 'Erlaubte RegEx-Flags: i und u, jeweils höchstens einmal.';
$lang['regex_named_group_required'] = 'Die RegEx benötigt eine benannte Zahlengruppe (?<value>...).';
$lang['regex_invalid_capture'] = 'Die RegEx hat getroffen, aber keine Zahl in der Gruppe value geliefert.';
$lang['regex_unsupported'] = 'Die RegEx-Auswertung konnte nicht gestartet werden. Web Worker und RegEx-Gruppenindizes müssen verfügbar sein.';
$lang['regex_timeout'] = 'Die RegEx-Auswertung wurde nach 2 Sekunden abgebrochen. Bitte Muster oder Eingabe vereinfachen.';
$lang['regex_not_ready'] = 'Der Text wurde noch nicht ausgewertet.';

$lang['definition_invalid_boolean'] = 'Eigenschaft %s muss true oder false sein.';

$lang['definition_circular_reference'] = 'Kreisbezug zwischen Ausgaben: %s.';
$lang['circular_reference'] = 'Kreisbezug zwischen Ausgaben.';

$lang['definition_invalid_default_text'] = 'Der Standardtext muss mit einem doppelten Anführungszeichen geschlossen werden; danach ist nur Leerraum erlaubt.';

$lang['definition_default_too_large'] = 'Der Standardtext darf höchstens %s Bytes und %s Zeilen enthalten.';
