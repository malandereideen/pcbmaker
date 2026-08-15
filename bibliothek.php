<?php
/**
 * pcbmaker — Bauteil-Bibliothek
 *
 * Lesen darf jeder, schreiben nur mit Token. Die Bibliothek ist nichts
 * Geheimes, aber ein offenes Schreibrecht auf eine Datei im Netz ist eine
 * Einladung, die niemand ausschlagen wuerde.
 *
 * Zwei Quellen, bewusst getrennt:
 *   bauteile.json          — die mitgelieferte Bibliothek. Liegt im Repo,
 *                            wird beim Deploy ueberschrieben.
 *   daten/eigene.json      — selbst angelegte Bauteile. Liegt NUR auf dem
 *                            Server, ist vom Deploy ausgeschlossen und
 *                            ueberlebt damit jedes Hochladen.
 * Waeren beide dieselbe Datei, wuerde der naechste Deploy jedes selbst
 * gebaute Bauteil stillschweigend loeschen.
 *
 * Aufruf:
 *   GET  bibliothek.php                → { standard: [...], eigene: [...] }
 *   POST bibliothek.php                → speichert EIN Bauteil
 *   POST bibliothek.php?loeschen=<id>  → entfernt EIN eigenes Bauteil
 * Beim Schreiben Kopf `X-Pcb-Token` mitschicken.
 */

declare( strict_types = 1 );

const STANDARD_DATEI = __DIR__ . '/bauteile.json';
const EIGENE_DATEI   = __DIR__ . '/daten/eigene.json';
const TOKEN_DATEI    = __DIR__ . '/daten/token.txt';
const MAX_RUMPF      = 400000;   // ein Bauteil mit vielen Pins bleibt weit darunter
const MAX_BAUTEILE   = 2000;

header( 'Content-Type: application/json; charset=utf-8' );
header( 'Cache-Control: no-store' );

function ende( int $code, array $rumpf ): never {
	http_response_code( $code );
	echo json_encode( $rumpf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

function lies( string $datei ): array {
	if ( ! is_readable( $datei ) ) {
		return [];
	}
	$d = json_decode( (string) file_get_contents( $datei ), true );
	return is_array( $d ) ? $d : [];
}

// --- Lesen ---------------------------------------------------------------

if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	$standard = lies( STANDARD_DATEI );
	$eigene   = lies( EIGENE_DATEI );
	ende(
		200,
		[
			'version'    => 1,
			'kategorien' => $standard['kategorien'] ?? [],
			'standard'   => $standard['bauteile'] ?? [],
			'eigene'     => $eigene['bauteile'] ?? [],
			'schreiben'  => is_readable( TOKEN_DATEI ),
		]
	);
}

if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	ende( 405, [ 'fehler' => 'nur GET oder POST' ] );
}

// --- Schreiben braucht das Token ----------------------------------------

if ( ! is_readable( TOKEN_DATEI ) ) {
	ende( 503, [ 'fehler' => 'Auf diesem Server ist kein Token hinterlegt, es kann nur gelesen werden.' ] );
}
$erwartet  = trim( (string) file_get_contents( TOKEN_DATEI ) );
$geliefert = trim( (string) ( $_SERVER['HTTP_X_PCB_TOKEN'] ?? '' ) );
if ( '' === $erwartet || ! hash_equals( $erwartet, $geliefert ) ) {
	ende( 401, [ 'fehler' => 'Token fehlt oder stimmt nicht' ] );
}

if ( ! is_dir( dirname( EIGENE_DATEI ) ) ) {
	@mkdir( dirname( EIGENE_DATEI ), 0775, true );
}

/** Bezeichner: nur was gefahrlos in einen Dateinamen und ein Attribut passt. */
function kennung( $wert ): string {
	$s = preg_replace( '/[^a-z0-9\-_]/i', '', (string) $wert ) ?? '';
	return substr( $s, 0, 60 );
}

$eigene   = lies( EIGENE_DATEI );
$liste    = is_array( $eigene['bauteile'] ?? null ) ? $eigene['bauteile'] : [];
$loeschen = kennung( $_GET['loeschen'] ?? '' );

if ( '' !== $loeschen ) {
	$vorher = count( $liste );
	$liste  = array_values(
		array_filter(
			$liste,
			static fn( $b ) => ( $b['id'] ?? '' ) !== $loeschen
		)
	);
	if ( count( $liste ) === $vorher ) {
		ende( 404, [ 'fehler' => 'Kein Bauteil mit dieser Kennung' ] );
	}
} else {
	$rumpf = (string) file_get_contents( 'php://input' );
	if ( '' === $rumpf || strlen( $rumpf ) > MAX_RUMPF ) {
		ende( 400, [ 'fehler' => 'Rumpf fehlt oder ist zu gross' ] );
	}
	$neu = json_decode( $rumpf, true );
	if ( ! is_array( $neu ) ) {
		ende( 400, [ 'fehler' => 'kein gueltiges JSON' ] );
	}

	$id = kennung( $neu['id'] ?? '' );
	if ( '' === $id ) {
		ende( 400, [ 'fehler' => 'Kennung fehlt' ] );
	}
	if ( ! is_array( $neu['teile'] ?? null ) || ! count( $neu['teile'] ) ) {
		ende( 400, [ 'fehler' => 'Bauteil ohne Inhalt' ] );
	}
	$neu['id']        = $id;
	$neu['name']      = mb_substr( trim( (string) ( $neu['name'] ?? $id ) ), 0, 60 );
	$neu['kategorie'] = mb_substr( trim( (string) ( $neu['kategorie'] ?? 'Sonstiges' ) ), 0, 40 );
	$neu['geaendert'] = gmdate( 'c' );

	// Gleiche Kennung ersetzt, sonst haengt man Fassungen aneinander.
	$ersetzt = false;
	foreach ( $liste as $i => $b ) {
		if ( ( $b['id'] ?? '' ) === $id ) {
			$liste[ $i ] = $neu;
			$ersetzt     = true;
			break;
		}
	}
	if ( ! $ersetzt ) {
		if ( count( $liste ) >= MAX_BAUTEILE ) {
			ende( 507, [ 'fehler' => 'Bibliothek ist voll' ] );
		}
		$liste[] = $neu;
	}
}

// Erst daneben schreiben, dann umbenennen — ein abgebrochener Schreibvorgang
// darf keine halbe Bibliothek hinterlassen.
$inhalt = json_encode(
	[
		'version'  => 1,
		'bauteile' => $liste,
	],
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$temp = EIGENE_DATEI . '.tmp';
if ( false === @file_put_contents( $temp, $inhalt, LOCK_EX ) || ! @rename( $temp, EIGENE_DATEI ) ) {
	@unlink( $temp );
	ende( 500, [ 'fehler' => 'Konnte nicht schreiben' ] );
}

ende( 200, [ 'ok' => true, 'anzahl' => count( $liste ) ] );
