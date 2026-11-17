<?php
/**
 * ============================================
 * KONFIGURASJON FOR KATTA AI CHAT
 * ============================================
 *
 * Her ligger de felles innstillingene.
 * Selve agentene (navn, beskrivelse, temperatur og systemprompt)
 * ligger i filen agents.php.
 *
 * API-nøkkelen ligger i secrets.php (IKKE i denne filen),
 * slik at du kan dele config.php uten å lekke nøkkelen.
 */

require_once __DIR__ . '/secrets.php';   // OPENAI_API_KEY
require_once __DIR__ . '/agents.php';    // $AGENTS (listen med alle agenter)

// Modell - gpt-4o-mini er rask og rimelig
define('OPENAI_MODEL', 'gpt-4o-mini');

// Maks tokens i svaret (jo høyere, jo lengre svar kan AI-en gi)
define('OPENAI_MAX_TOKENS', 1000);

// Hvilken agent som er valgt når siden åpnes (nøkkel fra agents.php:
// 'game', 'history', 'it' eller 'travel')
define('DEFAULT_AGENT', 'game');

// Navn og beskrivelse til standardagenten (brukes bl.a. på innloggingssiden)
define('AGENT_NAME', $AGENTS[DEFAULT_AGENT]['name']);
define('AGENT_DESCRIPTION', $AGENTS[DEFAULT_AGENT]['description']);

// Grenser for temperaturen brukeren kan velge i nettsiden
define('TEMPERATURE_MIN', 0.0);
define('TEMPERATURE_MAX', 1.0);

// Legger ?v=<endringstid> bak css/js-filer, slik at nettleseren alltid henter nyeste versjon
// (løser "siden ser rar ut"-problemer som kommer av gammel cache).
function asset($path) {
    return $path . '?v=' . (@filemtime(__DIR__ . '/' . $path) ?: 1);
}
