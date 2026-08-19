# pcbmaker

**Platinen im 2,54er Raster zeichnen — heraus kommt die Fräsgrafik fürs Isolierfräsen.**

Lochraster ist das Maß, in dem die meisten Bauteile denken: DIP-ICs, Stiftleisten, Widerstände,
Klemmen. Also zeichnet dieses Werkzeug auch in genau diesem Raster — und rechnet daraus die
Bahnen, die eine Fräse abfahren muss, um das Kupfer zwischen den Leitern wegzunehmen.

Die App läuft komplett im Browser. Kein Konto, kein Build, keine Cloud — eine HTML-Datei
und ein paar lokale Dateien.

## Was es kann

- **Raster 2,54 mm**, wahlweise halbes Raster (1,27) für Bahnen, die zwischen den Pins durchmüssen.
- **Platinengröße jederzeit änderbar, an jeder Kante einzeln.** Die Regler wachsen nur nach
  rechts und unten; über die Kanten-Knöpfe kommt auch links und oben eine Rasterreihe dazu
  oder weg, und die Zeichnung wandert mit. *Auf Inhalt zuschneiden* macht die Platine so
  klein wie das, was drauf ist, plus ein Raster Luft ringsum.
- **Pads** rund oder quadratisch, Durchmesser und Bohrung je Pad.
- **Leiterbahnen** von Punkt zu Punkt, Breite einstellbar, wahlweise mit 45°-Zwang.
- **Kupferflächen** als Rechteck. Als **Massefläche** markiert, wird alles andere Kupfer
  automatisch um die eingestellte Freistellung ausgespart — Pads bekommen ihren Ring von selbst.
- **Bauteil-Bibliothek** nach Kategorien: DIP-8 bis DIP-40, Stiftleisten 1×N und 2×N,
  Widerstände, Dioden, LED, Elkos, TO-92, TO-220, Schraubklemmen, Taster, M3-Bohrung.
  Pin 1 ist quadratisch und sitzt auf dem Klickpunkt, `r` dreht in 90°-Schritten.
  **Eigene Bauteile** lassen sich aus der Zeichnung heraus anlegen — siehe unten.
- **Beschriftung im Kupfer**: `GND`, `V+`, `5V`, `TX` und was sonst gebraucht wird. Eingebaute
  Einlinien-Schrift (A–Z, 0–9, `+ - = / _ . , : ( ) < > * # ! ? ~ ° Ω`), Höhe und Strichbreite
  einstellbar, drehbar. Ein Klick auf einen der Schnellknöpfe setzt den Text. Die Schrift ist
  Kupfer wie alles andere — **die Isolierbahnen umfahren sie von selbst**.
  Beschriftung sitzt **frei, nicht am Raster** — sie gehört zwischen die Bauteile, nicht auf
  deren Pins. Das gilt auch beim Verschieben. Wer sie doch ausgerichtet haben will, setzt
  *ans Raster binden*.
- **Zwei Lagen** (Unterseite und Oberseite), die jeweils andere liegt blass darunter.
- **Export als SVG in echten Millimetern**, 1:1, jede Ebene als eigene `<g id>`-Gruppe.

## Bedienung

Drei Zonen: **oben** über der Zeichenfläche die Werkzeuge, die Seitenwahl und die drei
Schalter, die beim Zeichnen gelten. **Links** die Einstellungen dessen, was man gerade setzt —
Platine, Pad und Bahn, Bauteil, Beschriftung. **Rechts** alles, was aus dem Gezeichneten
etwas macht: Export, Fräsbahnen, eigene Bauteile.

Erklärungen stehen nicht mehr als Absätze zwischen den Reglern, sondern hängen als Tooltip an
dem Element, das sie erklären — erkennbar am gepunkteten Unterstrich.

**Ausrichtung** gilt für Bauteile und Beschriftungen gemeinsam — vier Knöpfe, ein Wert. Vorher
hatte jedes seine eigene Drehung, und wer ein gedrehtes Bauteil beschriften wollte, musste sie
an zwei Stellen gleich einstellen.

**Jede Zahl neben einem Regler ist ein Eingabefeld.** Hineinschreiben, Enter — mit dem Regler
trifft man 0,15 sonst nur mit Glück. Komma und Punkt gehen beide; Werte außerhalb des Reglers
werden auf dessen Grenzen gezogen, Unsinn verworfen.

Unter *Beschriftung* liegen die üblichen Kürzel (GND, VCC, TX …) und darunter die Pin-Nummern
**00 bis 44**, immer zweistellig — die Strichschrift hat feste Zeichenbreiten, „1" und „10"
stünden an der Stiftleiste sonst verschieden weit.

| Eingabe | Wirkung |
|---|---|
| `1` … `6` | Zeiger · Pad · Bahn · Fläche · Bauteil · Text |
| `r` | dreht — ein ausgewähltes Stück, sonst die *Ausrichtung* für das nächste |
| `l` | Seite wechseln — die Ansicht kippt dabei mit |
| Doppelklick, Rechtsklick, `Enter` | laufende Bahn beenden |
| `Esc` | Bahn verwerfen, Auswahl aufheben |
| Rahmen ziehen (Zeiger) | alles auswählen, was ganz darin liegt — über beide Seiten hinweg |
| Strg/⌘ + `A` | alles auswählen |
| `Entf` | Auswahl löschen |
| Strg/⌘ + `Z` | zurück |
| Strg/⌘ + Mausrad | zoomen |
| `+` `−` `0` | Zoom größer, kleiner, einpassen |

Der Stand liegt im Browser-Speicher und ist beim nächsten Öffnen wieder da. Über
**Speichern** und **Laden** geht die Platine als `.json` auf die Platte.

## Wie die Isolierbahnen entstehen

Nicht über Polygon-Offset — das wird bei verschmolzenen Pads, Bahnkreuzungen und
Masseflächen mit Aussparungen schnell zu einem Fall von Sonderfällen. Stattdessen:

1. **Das Kupfer einer Lage wird gerastert.** Dieselbe Zeichenroutine, die auch die Anzeige
   malt, füllt ein Schwarzweiß-Bild mit einstellbarer Auflösung (Regler *Genauigkeit*,
   Punkte pro mm). Damit stimmen Bild und Rechnung garantiert überein.
   Das Bild bekommt dabei einen Rand rings um die Platine, breiter als der äußerste
   Umlauf. Ohne den würden Höhenlinien von Kupfer nahe der Kante am Bildrand
   abgeschnitten — offene Konturen also, die beim Füllen mit einer Sehne quer über die
   Platine zugezogen werden.
2. **Distanztransformation.** Für jeden Pixel wird der exakte Abstand zum nächsten
   Kupferpixel bestimmt (Felzenszwalb, zwei 1D-Durchgänge, linear in der Pixelzahl).
3. **Höhenlinien.** Die Fräsbahn ist die Linie konstanten Abstands. Der erste Umlauf liegt
   beim halben Fräserdurchmesser — da streift die Fräserkante das Kupfer gerade eben.
   Jeder weitere Umlauf liegt um den Bahnabstand weiter draußen. Marching Squares zieht
   diese Linien aus dem Distanzfeld, Sattelpunkte werden über den Zellmittelwert aufgelöst.
4. **Zusammenhängen und glätten.** Die losen Segmente werden über ihre Endpunkte zu
   durchgehenden Bahnen verkettet und mit Douglas-Peucker (0,015 mm) ausgedünnt.

**Die Bahnen im SVG sind Mittellinien des Fräsers.** Im CAM also mit Werkzeugdurchmesser 0
bzw. „auf der Linie" fahren, nicht noch einmal versetzen.

Der Vorteil des Rasterwegs: was zwischen zwei Kupferstücken nicht mehr durchpasst, fällt
von selbst weg, statt eine kaputte Kontur zu erzeugen. Der Preis ist die Auflösung — bei
0,2 mm Fräser sind 16 Punkte/mm sinnvoll, feiner lohnt selten. Für große Platinen greift
eine Notbremse, die die Auflösung selbst zurücknimmt und das in der Statuszeile sagt.

## Was im SVG steht

Exportiert wird **eine Seite pro Knopf**: *SVG unten* schreibt `platine-unten.svg`,
*SVG oben* schreibt `platine-oben.svg`, jede mit nur dem Kupfer ihrer Seite, 1:1 in mm.

Vor jedem Export geht ein Fenster auf und fragt, was hinein soll — Ebenen, Farben und die
Frage Linie oder Fläche. Das steht dort und nicht dauerhaft in der Seitenleiste, weil beide
Seiten verschiedene Sätze brauchen: unten wird isoliert und gebohrt, oben steht meist nur die
Beschriftung. Die App merkt sich für jede Seite ihren eigenen Satz.

Der Standard, den *Standard* wiederherstellt:

| | Unterseite | Oberseite |
|---|---|---|
| Ebenen | 1 Isolierbahn Leiter + Bohrungen (1) · 2 Bohrungslöcher · 7 Restfläche · 8 Außenkontur (1) · 9 Außenkontur (2) | 6 Schrift selbst · 8 Außenkontur (1) |
| Isolierbahnen | Fläche — Gravieren | Fläche — Gravieren |
| Bohrungen | Kreis-Umriss | Kreis-Umriss |

Die Nummern stehen im Fenster vor jeder Zeile — sie sind die Reihenfolge im SVG.

Eine Seite ohne eigenes Kupfer wird nicht mehr stillschweigend übersprungen; das Fenster sagt
es und man entscheidet selbst.

Die Zeichnung ist der **Blick auf die Unterseite**. Schaltet man auf die Oberseite um, kippt
die Ansicht — man sieht die Platine dann von oben, so wie sie liegt, wenn man sie zum
Bearbeiten umdreht. Dieselbe Kippung steckt in `platine-oben.svg`, sonst passen die beiden
Seiten nach dem Umdrehen nicht mehr übereinander. Wer das nicht will, schaltet
*Oberseite spiegeln* ab; dann werden beide Seiten von unten gezeigt und ungespiegelt
ausgegeben.

Gespeichert wird immer im ungespiegelten System — ein Loch ist auf beiden Seiten derselbe
Punkt. Nur Beschriftung, die in der gekippten Ansicht gesetzt wurde, liegt im Speicher
seitenverkehrt; auf dem Bildschirm und im ebenfalls gekippten SVG liest sie sich damit
richtig herum.

Jede Datei enthält dieselben Ebenen in derselben Reihenfolge:

| # | Gruppe (`id`) | Farbe | Inhalt |
|---|---|---|---|
| 1 | `isolation-leiter-1` | `#00897B` | Isolierbahn um Leiterbahnen, Pads und Flächen |
| 2 | `bohrloecher` | `#E1C000` | je Durchmesser eine Untergruppe, `data-durchmesser` in mm |
| 3 | `isolation-text-1` | `#2366FF` | Isolierbahn um Symbole und Beschriftung |
| 4 | `isolation-leiter-2` | `#FE0002` | wie 1, zweiter Durchgang |
| 5 | `isolation-text-2` | `#582FA8` | wie 3, zweiter Durchgang |
| 6 | `schrift` | `#EB3DBA` | die Buchstaben selbst, als Striche in ihrer echten Strichbreite |
| 7 | `restflaeche` | `#96D71D` | das übrige Kupfer neben den Bahnen, als Fläche — komplett abtragen |
| 8 | `aussenkontur-1` | `#848B96` | Platinenumriss, um den halben Konturfräser nach außen versetzt |
| 9 | `aussenkontur-2` | `#00BEFE` | wie 8, zweiter Durchgang |

Mit **Haltestege = 0** ist die Außenkontur ein einziger geschlossener Ring (`… Z`) — so will
ein Schneidprogramm sie sehen. Jeder Haltesteg bricht sie in ein weiteres offenes Stück auf;
dafür hängt die Platine hinterher noch im Rest.

Die Paare (1)/(4), (3)/(5) und (8)/(9) sind **dieselbe Geometrie zweimal** in verschiedenen
Farben — damit in xTool Studio jeder Durchgang eigene Leistung und Geschwindigkeit bekommt.

`schrift` und `isolation-text-*` sind zwei verschiedene Dinge: `schrift` sind die Buchstaben,
`isolation-text-*` ist die Bahn drumherum. Jeder Pfad in `schrift` trägt die Strichbreite
seiner eigenen Beschriftung, mehrere Texte mit verschiedenen Breiten bleiben also
unterscheidbar.

### Restfläche — warum die nötig ist

Die Isolierbahn trennt sauber, aber auf der Rohplatine ist **alles** Kupfer: neben den
Leiterbahnen bleibt ein großes freischwebendes Kupferfeld stehen. Beim Löten brückt das Zinn
über den schmalen Graben genau dorthin, und das Bauteil hängt an einem Feld, das anderswo an
Masse liegt. Die Ebene `restflaeche` ist dieses Feld als Fläche — komplett abtragen lassen,
dann bleibt nur noch stehen, was leiten soll.

Gebaut aus einem Rechteck plus allen äußersten Isolierkonturen in einem Pfad mit
`fill-rule="evenodd"`. Das Rechteck ist **nicht** der Platinenrand, sondern reicht **1 mm über
die Außenkontur hinaus** — also `Konturfräser / 2 + 1 mm` nach jeder Seite. Endete es am
Platinenrand, bliebe zwischen Rand und Schnittbahn ein schmaler Kupferstreifen stehen. Der
Überstand wird beim Ausschneiden ohnehin weggefräst und kostet damit nichts.

Die Paritätsprobe geht auf:

| Ort | Kreuzungen | Ergebnis |
|---|---|---|
| Restfeld, auch der Streifen bis zur Außenkontur | Rechteck (1) | gefüllt — weg damit |
| Leiterbahn samt Graben | Rechteck (1) + Kontur (2) | frei — bleibt stehen |
| Innenraum eines Buchstabens | Rechteck (1) + außen (2) + innen (3) | gefüllt — auch das ist Restkupfer |

Dazu drei Zugaben, normalerweise aus: `kupfer` (das Kupferbild zum Vergleichen),
`platinenrand` und `bestueckung`.

### Warum Leiterbahnen und Text getrennt sind — aber zusammen gerechnet werden

Beide Sorten brauchen andere Einstellungen an der Maschine, deshalb liegen sie auf eigenen
Ebenen. Gerechnet werden sie trotzdem **aus einem gemeinsamen Distanzfeld**: würde man die
Leiterbahnen für sich rechnen, liefe ihre Isolierbahn mitten durch einen daneben stehenden
Buchstaben und würde ihn wegfräsen. Also wird das Feld aus dem Minimum beider Abstände
gebildet, und jede fertige Kontur bekommt hinterher das Etikett des Kupfers, an dem sie
tatsächlich entlangläuft.

### Linien oder Fläche

| Modus | Ausgabe | wofür |
|---|---|---|
| **Linien — Fräsen** | ein Pfad je Umlauf und Kontur, `fill:none`, `data-fraeser` nennt den Durchmesser | Fräse, im CAM auf der Linie fahren |
| **Fläche — Gravieren** | **ein** Pfad je Ebene mit `fill-rule="evenodd"`, darin innerster und äußerster Umlauf als geschlossene Teilpfade, `data-graben` nennt die Breite | Laser, xTool Studio & Co. |

Der Flächen-Modus gibt es, weil Gravier-Programme einzelne Konturen als je eigene Form
lesen: die äußere Kontur wird dann zur vollen Fläche und die innere gleich mit übermalt.
Von Hand hilft „äußeren und inneren Vektor auswählen, Compound-Pfad daraus machen" —
genau das nimmt dieser Modus einem ab. Weil beide Konturen in einem `d`-Attribut mit
`evenodd` stehen, ist der Ring von Haus aus ein Compound-Pfad und es wird nur der Graben
graviert.

### Ebenen über Farben

Importer wie xTool Studio sortieren die Objekte eines SVG nach ihrer Farbe auf getrennte
Ebenen: **eine Farbe = eine Ebene**. Deshalb steht die Farbe an **jedem einzelnen Element** —
auf Vererbung von der `<g>` verlassen sich diese Programme nicht.

Alle Farben sind im Export-Fenster unter *Ebenen und Farben* frei änderbar. Zwei gleiche Farben landen
im Importer auf derselben Ebene, und die App meldet das, sobald es passiert. Farben und
Linienstärke bleiben im Browser gespeichert: einmal auf die eigene Vorlage eingestellt, passt
jedes weitere SVG ohne Nacharbeit.

**Der Graben braucht mindestens zwei Umläufe** — seine Breite ist (Umläufe − 1) ×
Bahnabstand. Mit einem Umlauf hat er keine Breite und die Fläche wird zum Klotz; die App
sagt das im Export-Fenster und in der Statuszeile.

## Ablauf bis zur Platine

1. Zeichnen, dabei die Unterseite als Hauptlage nehmen.
2. Fräser-Ø auf das eintragen, was wirklich in der Spindel steckt (bei V-Stichel: die
   effektive Breite in der Frästiefe, nicht die Schaftgröße).
3. **Fräsbahnen** rechnen, Bild kontrollieren — enge Stellen erkennt man daran, dass dort
   keine Bahn mehr liegt.
4. SVG exportieren, im CAM die Gruppen als getrennte Aufträge anlegen:
   Isolieren → Bohren → Kontur.

## Bauteil-Bibliothek

Ein Bauteil ist eine Liste von Teilen in mm, gemessen vom Ankerpunkt aus — Pin 1. **Jedes
Teil trägt seine eigene Lage.** Deshalb kann ein Bauteil unten die Pins haben und oben die
Beschriftung, und beim Setzen landet beides automatisch auf der richtigen Seite.

```json
{ "id": "eigen-sensor-4-polig-glc0", "name": "Sensor 4-polig", "kategorie": "Module",
  "teile": [
    { "t": "pad",  "x": 0, "y": 0, "d": 1.8, "bohr": 1, "form": "eckig", "lage": "unten" },
    { "t": "text", "x": 0, "y": 5.08, "s": "VCC", "h": 2, "b": 0.4, "rot": 0, "lage": "oben" }
  ] }
```

### Eigene Bauteile anlegen

Kein zweiter Editor — gebaut wird mit den Werkzeugen, die es ohnehin gibt:

1. Pins auf der Unterseite setzen, auf die Oberseite wechseln, beschriften.
2. Mit dem **Zeiger** einen Rahmen über alles ziehen. Was **ganz** darin liegt, kommt mit —
   über beide Seiten hinweg. Halb erwischte Leiterbahnen bleiben absichtlich draußen.
3. Rechts unter *Eigenes Bauteil* Namen und Kategorie eintragen, **Speichern**.

Der Ankerpunkt wird das oberste linke Pad, also dasselbe Pin 1 wie bei den mitgelieferten
Bauteilen. `Strg`/`⌘` + `A` wählt alles aus, `Esc` hebt die Auswahl auf.

### Wo die Bibliothek liegt

| Datei | Inhalt | Deploy |
|---|---|---|
| `bauteile.json` | die mitgelieferte Bibliothek | wird überschrieben |
| `daten/eigene.json` | selbst angelegte Bauteile | **ausgeschlossen**, bleibt stehen |
| `daten/token.txt` | Schreibschlüssel | **ausgeschlossen** |

Getrennt, weil es sonst genau einmal gutginge: der nächste Deploy würde jedes selbst gebaute
Bauteil stillschweigend löschen.

`bibliothek.php` liefert beide zusammen aus. **Lesen darf jeder, schreiben nur mit Token** —
ein offenes Schreibrecht auf eine Datei im Netz ist eine Einladung, die niemand ausschlägt.
Der Schlüssel steht in `daten/token.txt` auf dem Server und wird im Feld *Schreibschlüssel*
einmal eingetragen; er bleibt im Browser.

Token auf dem Server anlegen:

```bash
mkdir -p daten && head -c 24 /dev/urandom | xxd -p | tr -d '\n' > daten/token.txt
```

Läuft kein PHP — etwa beim lokalen Ausprobieren mit `python3 -m http.server` —, holt die App
`bauteile.json` direkt und hält eigene Bauteile im Browser. Die Statuszeile unter der
Bauteilauswahl sagt, welcher Fall gerade gilt. **Eigene sichern** und **Einlesen** schreiben
und lesen eine `eigene-bauteile.json`, damit die Sammlung zwischen Rechnern wandern kann.

## Starten

Keine Installation. Entweder online unter
[tools.malandereideen.de/pcbmaker](https://tools.malandereideen.de/pcbmaker/)
oder lokal:

```bash
python3 -m http.server 8788
```

Dann `http://localhost:8788` im Browser öffnen.

## Lizenz

MIT — siehe [LICENSE](LICENSE).

Teil von [makr.tools](https://tools.malandereideen.de/).
