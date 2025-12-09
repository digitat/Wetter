<?php
/**
 * Wettervorhersage - Konfiguration
 * Zentrale Konfigurationsdatei für alle Einstellungen
 */

// Fehlerbehandlung für Produktion
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Zeitzone und Locale
setlocale(LC_TIME, 'de_DE.UTF-8', 'de_DE', 'German_Germany');
date_default_timezone_set('Europe/Berlin');

// Anwendungskonstanten
define('APP_NAME', 'Wettervorhersage');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://www.wetterstation-neustadt.de');

// API-Konfiguration
define('MAPBOX_TOKEN', 'pk.eyJ1IjoiZGlnaXRhdCIsImEiOiJjbGVoNmQ0ajEwZzJmM3BtY2JmMXF4aGl3In0.82BMBf-3pv4NyM1Z6hnG5g');
define('MAPBOX_COUNTRIES', 'DE,AT,FR,IT,ES,CH,CZ,NL,DK,PL,BE,SK,HU,SI,HR,LU');

// Open-Meteo Proxy
define('OPENMETEO_PROXY', 'https://mein-wetter-proxy.vercel.app/v1');
define('OPENMETEO_DWD', OPENMETEO_PROXY . '/dwd-icon');
define('OPENMETEO_FORECAST', OPENMETEO_PROXY . '/forecast');

// Ensemble API
define('ENSEMBLE_API', 'https://ensemble-api.open-meteo.com/v1/ensemble');

// Höhendaten API
define('ELEVATION_API', 'https://api.opentopodata.org/v1/eudem25m');

// Icon-Pfade
define('ICON_BASE_URL', APP_URL . '/icons/new4');
define('IMAGE_BASE_URL', APP_URL . '/images');

// Standardwerte für Neustadt an der Donau
define('DEFAULT_LAT', 48.81476);
define('DEFAULT_LNG', 11.7695);
define('DEFAULT_CITY', 'Neustadt an der Donau');
define('DEFAULT_PROVINCE', 'Bayern, Deutschland');
define('DEFAULT_SLUG', 'neustadt-donau-bayern');

// Vorhersage-Einstellungen
define('FORECAST_DAYS', 14);
define('FORECAST_DAYS_DISPLAY', 8);

// Wind-Schwellenwerte (km/h)
define('WIND_THRESHOLD_LIGHT', 55);
define('WIND_THRESHOLD_HIGH', 85);

// Cache-Einstellungen (Sekunden)
define('CACHE_WEATHER', 300);      // 5 Minuten
define('CACHE_ELEVATION', 86400);   // 24 Stunden

// Sicherheitseinstellungen
define('ENABLE_HTTPS', true);
define('SECURE_COOKIES', true);

// Content Security Policy Header
function setSecurityHeaders(): void {
    if (headers_sent()) return;

    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // CSP - angepasst für externe Ressourcen
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
               "https://www.googletagmanager.com " .
               "https://www.google-analytics.com " .
               "https://api.mapbox.com " .
               "https://ensemble-api.open-meteo.com " .
               "https://mein-wetter-proxy.vercel.app; " .
           "style-src 'self' 'unsafe-inline'; " .
           "img-src 'self' data: https: blob:; " .
           "font-src 'self' data:; " .
           "frame-src 'self' https://www.wetterstation-neustadt.de; " .
           "connect-src 'self' " .
               "https://api.mapbox.com " .
               "https://api.brightsky.dev " .
               "https://ensemble-api.open-meteo.com " .
               "https://api.opentopodata.org " .
               "https://mein-wetter-proxy.vercel.app " .
               "https://www.google-analytics.com;";

    header("Content-Security-Policy: $csp");
}

// Wetter-Icons Mapping
function getWeatherIcons(): array {
    $dayIcons = [];
    $nightIcons = [];

    $iconMap = [
        0 => '0', 1 => '1', 2 => '2', 3 => '3',
        45 => '45-48', 48 => '45-48',
        51 => '51-57', 53 => '51-57', 55 => '51-57', 56 => '51-57', 57 => '51-57',
        61 => '61', 63 => '63-65', 65 => '63-65',
        66 => '66-67', 67 => '66-67',
        71 => '71-75', 73 => '71-75', 75 => '71-75', 77 => '77',
        80 => '80-82', 81 => '80-82', 82 => '80-82',
        85 => '85-86', 86 => '85-86',
        95 => '95-96', 96 => '95-96', 99 => '99',
        100 => 'shower_slight_', 101 => 'snow_shower_light_'
    ];

    foreach ($iconMap as $code => $file) {
        $suffix = ($code >= 100) ? '' : '';
        $dayIcons[$code] = ICON_BASE_URL . "/{$file}d.svg";
        $nightIcons[$code] = ICON_BASE_URL . "/{$file}n.svg";
    }

    // Spezialfälle für 100 und 101
    $dayIcons[100] = ICON_BASE_URL . "/shower_slight_d.svg";
    $nightIcons[100] = ICON_BASE_URL . "/shower_slight_n.svg";
    $dayIcons[101] = ICON_BASE_URL . "/snow_shower_light_d.svg";
    $nightIcons[101] = ICON_BASE_URL . "/snow_shower_light_n.svg";

    return ['day' => $dayIcons, 'night' => $nightIcons];
}

// Wetter-Beschreibungen
function getWeatherDescriptions(): array {
    return [
        0 => 'wolkenlos',
        1 => 'gering bewölkt',
        2 => 'bewölkt',
        3 => 'stark bewölkt',
        45 => 'neblig',
        48 => 'neblig (Glätte!)',
        51 => 'Sprüh-/Nieselregen',
        53 => 'Sprüh-/Nieselregen',
        55 => 'Sprüh-/Nieselregen',
        56 => 'gefrierender Niesel',
        57 => 'gefrierender Niesel',
        61 => 'leichter Regen',
        63 => 'mäßiger Regen',
        65 => 'starker Regen',
        66 => 'gefrierender Regen',
        67 => 'gefrierender Regen',
        71 => 'leichter Schneefall',
        73 => 'mäßiger Schneefall',
        75 => 'starker Schneefall',
        77 => 'Schneegriesel',
        80 => 'leichte Regenschauer',
        81 => 'mäßige Regenschauer',
        82 => 'starke Regenschauer',
        85 => 'leichte Schneeschauer',
        86 => 'starke Schneeschauer',
        95 => 'Gewitter',
        96 => 'Gewitter mit Hagel',
        99 => 'schweres Gewitter',
        100 => 'leichte Regenschauer',
        101 => 'leichte Schneeschauer'
    ];
}
