<?php
/**
 * Wettervorhersage - Moderne Version 2.0
 *
 * Verbesserungen:
 * - SEO: Strukturierte Daten, Open Graph, semantisches HTML
 * - Performance: Lazy Loading, kritisches CSS, optimierte Skripte
 * - Barrierefreiheit: ARIA-Labels, Skip-Links, Fokus-Management
 * - Sicherheit: CSP-Header, Input-Validierung, XSS-Schutz
 */

declare(strict_types=1);

// Konfiguration und Funktionen laden
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Sicherheitsheader setzen
setSecurityHeaders();

// ============================================
// ROUTING & DATENVERARBEITUNG
// ============================================

// Standardwerte initialisieren
$location = [
    'lat' => DEFAULT_LAT,
    'lng' => DEFAULT_LNG,
    'city' => DEFAULT_CITY,
    'province_country' => DEFAULT_PROVINCE,
    'slug' => DEFAULT_SLUG
];

// URL-Parameter verarbeiten
if (!empty($_GET['location'])) {
    $slug = trim(filter_var($_GET['location'], FILTER_SANITIZE_SPECIAL_CHARS));

    if ($slug) {
        $geoData = getGeocodingForSlug($slug);

        if ($geoData) {
            $location = [
                'lat' => $geoData['lat'],
                'lng' => $geoData['lng'],
                'city' => $geoData['city'],
                'province_country' => $geoData['province_country'],
                'slug' => $slug
            ];
        }
    }
}

// Höhendaten abrufen
$elevation = getElevation((float) $location['lat'], (float) $location['lng']);
$elevationText = $elevation !== null ? "{$elevation} Meter ü.NN." : '';

// Wetterdaten abrufen
$weatherData = fetchWeatherData((float) $location['lat'], (float) $location['lng']);
$dailyData = $weatherData ? processDailyData($weatherData) : null;

// Mobile-Erkennung
$isMobile = isMobileDevice();
$dayColSize = $isMobile ? 4 : 3;

// Wintersaison prüfen (für Schneeanzeige)
$isWinter = isWinterSeason();

// Prüfen ob Deutschland (für Radar)
$isGermany = str_contains($location['province_country'], 'Deutschland');

// Icons und Beschreibungen laden
$weatherIcons = getWeatherIcons();
$weatherDescriptions = getWeatherDescriptions();

// SEO-Daten vorbereiten
$pageTitle = "Wettervorhersage " . e($location['city']) . " - 7-Tage-Prognose";
$pageDescription = "Präzise 7-Tage-Wettervorhersage für " . e($location['city']) . ", " .
                   e(explode(',', $location['province_country'])[0] ?? '') .
                   ". Mit HD-Regenradar, Satellitenbildern und Live-Warnungen des DWD.";
$canonicalUrl = getCanonicalUrl($location['slug']);

?>
<!DOCTYPE html>
<html lang="de" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Kritisches CSS inline für schnelleres Rendering -->
    <?= getCriticalCss() ?>

    <!-- Preload kritische Ressourcen -->
    <?= getPreloadLinks() ?>

    <!-- Basis Meta-Tags -->
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="author" content="Alexander Aschenauer">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="theme-color" content="#0056b3">

    <!-- Geo Meta-Tags -->
    <meta name="geo.placename" content="<?= e($location['city']) ?>">
    <meta name="geo.position" content="<?= e($location['lat']) ?>;<?= e($location['lng']) ?>">
    <meta name="ICBM" content="<?= e($location['lat']) ?>, <?= e($location['lng']) ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">

    <!-- Open Graph & Twitter Cards -->
    <?= generateOpenGraphTags($location) ?>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ?>/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= APP_URL ?>/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= APP_URL ?>/images/apple-icon-180x180.png">

    <!-- Stylesheets - nicht-kritisches CSS mit preload -->
    <link rel="preload" href="<?= APP_URL ?>/vendor/bootstrap/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= APP_URL ?>/vendor/bootstrap/css/bootstrap.min.css"></noscript>

    <link rel="stylesheet" href="<?= APP_URL ?>/css/weather-icons.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/wettervorhersage-modern.css">

    <!-- Strukturierte Daten (JSON-LD) -->
    <?= generateStructuredData($location, $dailyData ?? []) ?>
</head>

<body>
    <!-- Skip-Link für Barrierefreiheit -->
    <a href="#main-content" class="skip-link">Zum Hauptinhalt springen</a>

    <!-- Back to Top Button -->
    <button type="button"
            id="b2t"
            class="btn"
            title="Zurück nach oben"
            aria-label="Zurück nach oben scrollen">
        <i class="fa-solid fa-circle-up" aria-hidden="true"></i>
    </button>

    <!-- Navigation -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark" role="navigation" aria-label="Hauptnavigation">
        <div class="container">
            <a class="navbar-brand" href="/" title="Zur Startseite">
                <span class="d-none d-sm-inline">Wetter</span> <?= e($location['city']) ?>
            </a>

            <button class="navbar-toggler"
                    type="button"
                    data-toggle="collapse"
                    data-target="#navbarMain"
                    aria-controls="navbarMain"
                    aria-expanded="false"
                    aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/index.php#begin">
                            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                            <span class="d-lg-inline">Wetterdaten</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="<?= APP_URL ?>/wettervorhersage/" aria-current="page">
                            <span class="badge badge-light" aria-label="High Definition">HD</span>
                            Vorhersage
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/grafiken.php">
                            <i class="fa-solid fa-chart-area" aria-hidden="true"></i>
                            <span class="d-lg-inline">Grafiken</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/wasserstand_donau_pollenflug.php">
                            <i class="fa-solid fa-water" aria-hidden="true"></i>
                            <span class="d-lg-inline">Donau</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/die_wetterstation.php">
                            <i class="fa-solid fa-tower-observation" aria-hidden="true"></i>
                            <span class="d-lg-inline">Station</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section mit Suche -->
    <header class="hero-section" role="banner">
        <div class="container">
            <h1>
                <strong><?= e($location['city']) ?></strong>
            </h1>

            <p class="mb-2"><?= e($location['province_country']) ?></p>

            <!-- Standort-Informationen -->
            <div class="location-info" role="contentinfo" aria-label="Standortinformationen">
                <?php if ($elevationText): ?>
                <span class="location-info-item">
                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                    <span><?= e($elevationText) ?></span>
                </span>
                <?php endif; ?>

                <?php if ($dailyData): ?>
                <span class="location-info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <title>Sonnenaufgang</title>
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l1.5 1.5a.5.5 0 0 1-.708.708L8.5 2.707V4.5a.5.5 0 0 1-1 0V2.707l-.646.647a.5.5 0 1 1-.708-.708l1.5-1.5zM2.343 4.343a.5.5 0 0 1 .707 0l1.414 1.414a.5.5 0 0 1-.707.707L2.343 5.05a.5.5 0 0 1 0-.707zm11.314 0a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM11.709 11.5a4 4 0 1 0-7.418 0H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1h-3.79zM0 10a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 0 10zm13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <span><?= e($dailyData['sunrise']) ?> Uhr</span>
                </span>

                <span class="location-info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <title>Sonnenuntergang</title>
                        <path d="M7.646 4.854a.5.5 0 0 0 .708 0l1.5-1.5a.5.5 0 0 0-.708-.708l-.646.647V1.5a.5.5 0 0 0-1 0v1.793l-.646-.647a.5.5 0 1 0-.708.708l1.5 1.5zm-5.303-.51a.5.5 0 0 1 .707 0l1.414 1.413a.5.5 0 0 1-.707.707L2.343 5.05a.5.5 0 0 1 0-.707zm11.314 0a.5.5 0 0 1 0 .706l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM11.709 11.5a4 4 0 1 0-7.418 0H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1h-3.79zM0 10a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 0 10zm13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <span><?= e($dailyData['sunset']) ?> Uhr</span>
                </span>
                <?php endif; ?>
            </div>

            <!-- Suchformular -->
            <div class="search-container mt-4">
                <form id="search-form"
                      role="search"
                      aria-label="Ortssuche"
                      onsubmit="return false;">
                    <div class="input-group">
                        <label for="search-input" class="visually-hidden">Ort suchen</label>
                        <input type="text"
                               class="form-control"
                               id="search-input"
                               name="location"
                               autocomplete="off"
                               placeholder="Das Wetter in ..."
                               aria-describedby="search-help"
                               required>

                        <input type="hidden" id="latitude" aria-hidden="true">
                        <input type="hidden" id="longitude" aria-hidden="true">

                        <i class="fa fa-times clear-icon"
                           id="clear-icon"
                           role="button"
                           tabindex="0"
                           aria-label="Eingabe löschen"
                           style="display: none;"></i>

                        <div class="input-group-append">
                            <button class="btn btn-primary btn-search"
                                    type="submit"
                                    id="submit-button"
                                    aria-label="Suchen">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <small id="search-help" class="form-text text-white-50">
                        Geben Sie einen Ortsnamen oder eine Postleitzahl ein
                    </small>
                </form>

                <div id="suggestions"
                     class="suggestions-dropdown"
                     role="listbox"
                     aria-label="Ortsvorschläge"></div>
            </div>

            <!-- Letzte Suchanfragen -->
            <div class="dropdown recent-searches"
                 id="recent-searches-dropdown"
                 style="display: none;">
                <button class="btn recent-btn-modern dropdown-toggle"
                        type="button"
                        id="recent-searches"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    Letzte Suchanfragen
                </button>
                <div class="dropdown-menu modern-dropdown-menu"
                     aria-labelledby="recent-searches"
                     id="recent-searches-menu"
                     role="menu"></div>
            </div>
        </div>
    </header>

    <!-- Hauptinhalt -->
    <main id="main-content" class="container py-4" role="main">

        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="<?= APP_URL ?>/" itemprop="item">
                        <span itemprop="name">Startseite</span>
                    </a>
                    <meta itemprop="position" content="1">
                </li>
                <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                    <span itemprop="name">Wetter für <?= e($location['city']) ?></span>
                    <meta itemprop="position" content="2">
                </li>
            </ol>
        </nav>

        <!-- Wetterwarnungen Button -->
        <div class="alert-button-container d-flex justify-content-center w-100"
             id="alertButtonContainer"
             style="display: none !important;">
            <button type="button"
                    id="alertButton"
                    class="btn btn-primary btn-block alert-trigger-btn"
                    data-toggle="modal"
                    data-target="#alertsModal"
                    aria-label="Wetterwarnungen anzeigen">
                Zeige Wetterwarnungen
            </button>
        </div>

        <!-- Stundenvorhersage -->
        <section aria-labelledby="hourly-heading" class="mb-4">
            <h2 id="hourly-heading" class="h5">
                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                Vorhersage für die kommenden Stunden
            </h2>

            <div class="hours-scroll" role="region" aria-label="Stündliche Wettervorhersage">
                <div class="hours" id="hourly-forecast">
                    <!-- Wird per JavaScript gefüllt oder hier serverseitig -->
                    <div class="loading" aria-live="polite">
                        <div class="spinner" role="status">
                            <span class="visually-hidden">Wetterdaten werden geladen...</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7-Tage Übersicht -->
        <section aria-labelledby="weekly-heading" class="mb-4">
            <h2 id="weekly-heading" class="h4">
                <i class="fa-solid fa-calendar-week" aria-hidden="true"></i>
                7-Tage Übersicht
            </h2>
            <p class="text-muted">
                Wetterüberblick für die nächsten Tage. Klicken Sie auf einen Tag für mehr Details.
            </p>

            <div class="forecast-overview">
                <div class="row scroll background" role="list">
                    <?php
                    // Tagesvorhersage generieren
                    $startDay = (date('H:i') <= '19:30') ? 0 : 1;
                    $endDay = (date('H:i') > '18:15') ? 8 : 7;

                    for ($i = $startDay; $i < $endDay; $i++):
                        $timestamp = strtotime("+{$i} days");
                        $dayData = $dailyData['days'][$i] ?? null;

                        $dayLabel = match($i) {
                            0 => 'Heute',
                            1 => 'Morgen',
                            default => formatDate($timestamp, 'EEEE')
                        };
                    ?>
                    <div class="col-<?= $dayColSize ?> forecast-day <?= $i < $endDay - 1 ? 'border-right' : '' ?>"
                         role="listitem"
                         tabindex="0"
                         aria-label="Wettervorhersage für <?= e($dayLabel) ?>">

                        <h3 class="h6">
                            <?php if ($i === 0): ?>
                            <span class="badge badge-primary">Heute</span><br>
                            <?php endif; ?>
                            <?= e($dayLabel) ?><br>
                            <small class="text-muted"><?= formatDate($timestamp, 'dd. MMM') ?></small>
                        </h3>

                        <hr>

                        <!-- Wetter-Icons werden per JS eingefügt -->
                        <div id="Tag<?= $i + 1 ?>ContainerDIV" class="weather-icons-container">
                            <div class="loading-skeleton" style="height: 50px;"></div>
                        </div>

                        <hr>

                        <?php if ($dayData): ?>
                        <div class="forecast-temps">
                            <span class="temp-max" aria-label="Höchsttemperatur">
                                <?= e($dayData['temp_max']) ?>
                            </span>
                            <br>
                            <span class="temp-min" aria-label="Tiefsttemperatur">
                                <?= e($dayData['temp_min']) ?>
                            </span>
                        </div>

                        <hr>

                        <div class="forecast-details">
                            <small>
                                <span class="wi wi-umbrella" aria-hidden="true"></span>
                                <span class="visually-hidden">Niederschlag:</span>
                                <?= e($dayData['precipitation']) ?>
                            </small>
                            <span id="precipitationDiv<?= $i + 1 ?>"></span>

                            <?php if ($isWinter): ?>
                            <br>
                            <small>
                                <i class="fa-solid fa-snowflake" aria-hidden="true"></i>
                                <span class="visually-hidden">Schneefall:</span>
                                <?= e($dayData['snowfall']) ?>
                            </small>
                            <span id="snowfallDiv<?= $i + 1 ?>"></span>
                            <?php endif; ?>

                            <br>
                            <small>
                                <span class="wi wi-strong-wind" aria-hidden="true"></span>
                                <span class="visually-hidden">Wind:</span>
                                <?= e($dayData['wind_max']) ?>
                                (<?= e($dayData['wind_direction']) ?>)
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="forecast-temps">
                            <span class="text-muted">Keine Daten</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <!-- Diagramme -->
        <section aria-labelledby="charts-heading" class="charts-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 id="charts-heading" class="h4 mb-0">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                    Wetter-Diagramme
                </h2>
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        data-toggle="modal"
                        data-target="#weatherModelsModal"
                        aria-label="Erklärung zu den Wettermodellen">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Erklärung</span>
                </button>
            </div>

            <!-- Modell-Auswahl -->
            <div class="model-selector" id="model-selector" role="tablist" aria-label="Wettermodell auswählen">
                <strong class="mr-3 align-self-center">Modell:</strong>
                <button class="model-btn"
                        data-model="icon_d2"
                        role="tab"
                        aria-selected="false"
                        aria-controls="chart-panels">
                    48 Stunden
                    <span class="badge badge-secondary">HD</span>
                </button>
                <button class="model-btn"
                        data-model="icon_eu"
                        role="tab"
                        aria-selected="false"
                        aria-controls="chart-panels">
                    5 Tage
                    <span class="badge badge-secondary">HD</span>
                </button>
                <button class="model-btn"
                        data-model="icon_global"
                        role="tab"
                        aria-selected="false"
                        aria-controls="chart-panels">
                    7,5 Tage
                </button>
            </div>

            <!-- Chart Container -->
            <div id="chart-panels" role="tabpanel" class="chart-scroll-wrapper">
                <div class="chart-min-width">
                    <div id="temperature-dewpoint" class="chart-container" aria-label="Temperatur und Taupunkt Diagramm"></div>
                    <div id="clouds" class="chart-container" aria-label="Bewölkung Diagramm"></div>
                    <div id="rain" class="chart-container" aria-label="Niederschlag Diagramm"></div>
                    <div id="precip-prob" class="chart-container" aria-label="Niederschlagswahrscheinlichkeit Diagramm"></div>
                    <div id="wind" class="chart-container" aria-label="Wind Diagramm"></div>
                    <div id="humidity" class="chart-container" aria-label="Luftfeuchtigkeit Diagramm"></div>
                    <div id="pressure" class="chart-container" aria-label="Luftdruck Diagramm"></div>
                </div>
            </div>

            <!-- Modell-Info -->
            <div class="text-right small text-muted mt-2">
                <span id="run-info">Lade Modellinformationen...</span>
            </div>
        </section>

        <!-- Radar/Satellit/Warnungen Tabs -->
        <section aria-labelledby="maps-heading" class="weather-tabs mt-5">
            <h2 id="maps-heading" class="h4 mb-3">
                <i class="fa-solid fa-map" aria-hidden="true"></i>
                Karten & Radar
            </h2>

            <ul class="nav nav-pills mb-3" id="weatherTabs" role="tablist">
                <?php if ($isGermany): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link active"
                       id="radarTab"
                       data-toggle="pill"
                       href="#radarPane"
                       role="tab"
                       aria-controls="radarPane"
                       aria-selected="true">
                        <i class="fas fa-cloud-rain" aria-hidden="true"></i>
                        <span>Regenradar</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link"
                       id="warningTab"
                       data-toggle="pill"
                       href="#warningPane"
                       role="tab"
                       aria-controls="warningPane"
                       aria-selected="false">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <span>Warnungen</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= !$isGermany ? 'active' : '' ?>"
                       id="satelliteTab"
                       data-toggle="pill"
                       href="#satellitePane"
                       role="tab"
                       aria-controls="satellitePane"
                       aria-selected="<?= !$isGermany ? 'true' : 'false' ?>">
                        <i class="fas fa-satellite" aria-hidden="true"></i>
                        <span>Satellitenbild</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="weatherTabContent">
                <?php if ($isGermany): ?>
                <!-- Regenradar -->
                <div class="tab-pane fade show active"
                     id="radarPane"
                     role="tabpanel"
                     aria-labelledby="radarTab">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">
                            Regenradar <?= e($location['city']) ?>
                            <span class="badge badge-primary">DWD 250m HD</span>
                        </h3>
                        <button type="button"
                                class="btn btn-sm btn-outline-info"
                                data-toggle="modal"
                                data-target="#radarInfoModal"
                                aria-label="Informationen zum Wetterradar">
                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Info</span>
                        </button>
                    </div>

                    <p class="text-muted small mb-3">
                        Niederschlagsradar in Echtzeit – sehen Sie straßengenau, wo es gerade regnet, schneit oder hagelt.
                    </p>

                    <iframe class="radar-embed w-100"
                            loading="lazy"
                            style="border:0; height:65vh; min-height: 400px;"
                            title="Live HD-Regenradar für <?= e($location['city']) ?>"
                            data-src="<?= APP_URL ?>/radar/radar.php?&startcenter=<?= e($location['lat']) ?>,<?= e($location['lng']) ?>"
                            src="about:blank">
                    </iframe>
                </div>

                <!-- Warnungen -->
                <div class="tab-pane fade"
                     id="warningPane"
                     role="tabpanel"
                     aria-labelledby="warningTab">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">
                            Unwetterwarnungen <?= e($location['city']) ?>
                        </h3>
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-info"
                                    data-toggle="modal"
                                    data-target="#warningInfoModal"
                                    aria-label="Informationen zu Wetterwarnungen">
                                <i class="fas fa-question-circle" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-info"
                                    data-toggle="modal"
                                    data-target="#warningReportModal"
                                    aria-label="Warnlagebericht öffnen">
                                <i class="fas fa-file-alt" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">Bericht</span>
                            </button>
                        </div>
                    </div>

                    <iframe class="radar-embed w-100"
                            loading="lazy"
                            style="border:0; height:65vh; min-height: 400px;"
                            title="Wetterwarnungen für <?= e($location['city']) ?>"
                            data-src="<?= APP_URL ?>/wettervorhersage/rainradarv2/warn.php?speed=1000&mapstyle=osm_dark&zoom=7&startcenter=<?= e($location['lat']) ?>,<?= e($location['lng']) ?>&radarstyle=5&smoothing=1&snow=1&static=false&controlbarposition=top&controlscolor=light&refreshrate=600000&iframeheight=600&markerlon=<?= e($location['lat']) ?>&markerlat=<?= e($location['lng']) ?>&markerstyle=blueIcon&fullscreen=false&auth=true"
                            src="about:blank">
                    </iframe>

                    <!-- Warnlevel Legende -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted d-block mb-2 font-weight-bold">Warnstufen:</small>
                        <div class="warning-legend">
                            <div class="legend-item"><span class="marker-warning level-0"></span> Keine</div>
                            <div class="legend-item"><span class="marker-warning level-1"></span> Gelb</div>
                            <div class="legend-item"><span class="marker-warning level-2"></span> Orange</div>
                            <div class="legend-item"><span class="marker-warning level-3"></span> Rot</div>
                            <div class="legend-item"><span class="marker-warning level-4"></span> Violett</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Satellitenbild -->
                <div class="tab-pane fade <?= !$isGermany ? 'show active' : '' ?>"
                     id="satellitePane"
                     role="tabpanel"
                     aria-labelledby="satelliteTab">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">
                            Satellitenbild <?= e($location['city']) ?>
                            <span class="badge badge-primary">LIVE</span>
                        </h3>
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-info"
                                    data-toggle="modal"
                                    data-target="#satelliteInfoModal"
                                    aria-label="Informationen zu Satellitenbildern">
                                <i class="fas fa-question-circle" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    data-toggle="modal"
                                    data-target="#layerInfoModal"
                                    aria-label="Layer-Auswahl">
                                <i class="fa fa-layer-group" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">Layer</span>
                            </button>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">
                        EUMETSAT-Satellitenbilder, alle 15 Minuten aktualisiert.
                    </p>

                    <iframe class="radar-embed w-100"
                            loading="lazy"
                            style="border:0; height:65vh; min-height: 400px;"
                            title="Satellitenbild für <?= e($location['city']) ?>"
                            data-src="<?= APP_URL ?>/wettervorhersage/rainradarv2/sat.php?lat=<?= e($location['lat']) ?>&long=<?= e($location['lng']) ?>"
                            src="about:blank">
                    </iframe>
                </div>
            </div>
        </section>

        <!-- Info-Daten Quellenangabe -->
        <aside class="text-right small text-muted mt-4" aria-label="Datenquellen">
            <p class="mb-1">Alle Zeitangaben in Lokalzeit (MEZ/MESZ)</p>
            <p class="mb-1"><strong>Datenbasis:</strong> DWD ICON (via open-meteo.com)</p>
        </aside>

    </main>

    <!-- Info Section -->
    <section class="info-section" aria-labelledby="info-heading">
        <div class="container">
            <article>
                <h2 id="info-heading">Wetter für <?= e($location['city']) ?>: Direkt, präzise & ohne Werbung</h2>

                <p>
                    Schluss mit ungenauen Standard-Apps. Ich betreibe diese Seite als Hobby-Meteorologe, weil ich
                    genaue Daten will – und die teile ich hier mit Ihnen. Für die <strong>Wettervorhersage in <?= e($location['city']) ?></strong>
                    nutze ich direkt die Rohdaten des <strong>ICON-Modells (Deutscher Wetterdienst)</strong>.
                </p>

                <ul class="list-unstyled mt-3 mb-4">
                    <li class="mb-2">
                        <i class="fas fa-check text-success" aria-hidden="true"></i>
                        <strong>7-Tage-Trend:</strong> Klarer Blick auf die Woche, ohne Spielereien.
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success" aria-hidden="true"></i>
                        <strong>ICON-D2 Modell:</strong> Höchste Präzision für die nächsten 48 Stunden.
                    </li>
                </ul>

                <?php if ($isGermany): ?>
                <h3>Echtes 250m HD-Regenradar</h3>
                <p>
                    Das unterscheidet meine Seite von fast allen großen Wetterportalen: Ich zeige Ihnen das
                    <strong>Original-Radar des DWD in voller Auflösung (250 Meter)</strong>.
                    Hier sehen Sie Regenfronten, Gewitterkerne und Hagel nahezu <strong>straßengenau</strong>.
                </p>

                <h3>Amtliche Warnungen für <?= e($location['city']) ?></h3>
                <p>
                    Wenn es brenzlig wird, erfahren Sie es hier sofort. Ich spiele die <strong>amtlichen Unwetterwarnungen des DWD</strong>
                    ungefiltert für Ihre Region aus.
                </p>
                <?php else: ?>
                <h3>Satellitenbild Europa</h3>
                <p>
                    Neben der lokalen Vorhersage biete ich Ihnen den Blick aufs große Ganze. Mein
                    <strong>europäisches Satellitenbild</strong> zeigt alle 15 Minuten frisch, was auf den Kontinent zusteuert.
                </p>
                <?php endif; ?>

                <p class="text-muted mt-4">
                    <small>Wetterdaten für <?= e($location['city']) ?>. Betrieben mit Leidenschaft & DWD-Daten.</small>
                </p>
            </article>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 bg-dark" role="contentinfo">
        <div class="container text-center">
            <p class="m-0 text-white">
                &copy; 2011-<?= date('Y') ?> Wetterstation-Neustadt.de
            </p>
            <p class="m-0">
                <a href="<?= APP_URL ?>/impressum_datenschutz.php" class="text-white-50 small">
                    Impressum & Datenschutz
                </a>
            </p>
        </div>
    </footer>

    <!-- Modals (ausgelagert für bessere Übersichtlichkeit) -->
    <?php include __DIR__ . '/partials/modals.php'; ?>

    <!-- JavaScript -->
    <!-- jQuery und Bootstrap (kritisch) -->
    <script src="<?= APP_URL ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= APP_URL ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome (Icons) - defer für nicht-kritische Skripte -->
    <script defer src="<?= APP_URL ?>/js/font-awesome.js"></script>

    <!-- Highcharts (nur bei Bedarf laden) -->
    <script defer src="<?= APP_URL ?>/js/highcharts.js"></script>
    <script defer src="<?= APP_URL ?>/js/highcharts-more.js"></script>
    <script defer src="<?= APP_URL ?>/js/heatmap.js"></script>
    <script defer src="<?= APP_URL ?>/js/windbarb.js"></script>

    <!-- Eigenes JavaScript -->
    <script defer src="<?= APP_URL ?>/js/wettervorhersage-modern.js"></script>

    <!-- Koordinaten für JavaScript bereitstellen -->
    <script>
        window.WEATHER_CONFIG = {
            lat: <?= json_encode($location['lat']) ?>,
            lng: <?= json_encode($location['lng']) ?>,
            city: <?= json_encode($location['city']) ?>,
            slug: <?= json_encode($location['slug']) ?>,
            isGermany: <?= json_encode($isGermany) ?>
        };
    </script>

    <!-- Lazy Loading für iframes aktivieren -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // iframes mit data-src laden wenn Tab aktiv wird
            const tabLinks = document.querySelectorAll('[data-toggle="pill"]');
            tabLinks.forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(e) {
                    const targetPane = document.querySelector(e.target.getAttribute('href'));
                    const iframe = targetPane?.querySelector('iframe[data-src]');
                    if (iframe && !iframe.src.includes('http')) {
                        iframe.src = iframe.dataset.src;
                    }
                });
            });

            // Erstes aktives Tab sofort laden
            const activePane = document.querySelector('.tab-pane.active');
            const activeIframe = activePane?.querySelector('iframe[data-src]');
            if (activeIframe) {
                activeIframe.src = activeIframe.dataset.src;
            }

            // Alerts initialisieren
            if (window.WEATHER_CONFIG && typeof AlertsModule !== 'undefined') {
                AlertsModule.init(
                    window.WEATHER_CONFIG.lat,
                    window.WEATHER_CONFIG.lng,
                    window.WEATHER_CONFIG.city
                );
            }
        });
    </script>

    <!-- Google Analytics (optional, nur mit Consent) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-27353361-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-27353361-1', { 'anonymize_ip': true });
    </script>

</body>
</html>
