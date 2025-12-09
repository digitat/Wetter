<?php
/**
 * Wettervorhersage - Standalone Version
 * Mit CDN-Links für alle Abhängigkeiten
 */

declare(strict_types=1);

// Konfiguration und Funktionen laden
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

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

// Wintersaison prüfen
$isWinter = isWinterSeason();

// Prüfen ob Deutschland
$isGermany = str_contains($location['province_country'], 'Deutschland');

// SEO-Daten
$pageTitle = "Wettervorhersage " . e($location['city']) . " - 7-Tage-Prognose";
$pageDescription = "Präzise 7-Tage-Wettervorhersage für " . e($location['city']) . ". Mit HD-Regenradar und Live-Warnungen.";

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">

    <!-- Bootstrap 4 CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <!-- Font Awesome (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">

    <!-- Weather Icons (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/weather-icons/2.0.12/css/weather-icons.min.css" integrity="sha512-yX7wJVyTxJMKPLOmUUGhqygjW2oOA8cMt42ZAM7BGqbVeaHGNLLgdDd0wTH2lOIbQQXzT8C2/3H0xnAH7Rx+xQ==" crossorigin="anonymous">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/wettervorhersage-modern.css">

    <style>
        /* Zusätzliche Styles für Standalone */
        .navbar { margin-bottom: 0; }
        .hero-section {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            color: white;
            padding: 2rem 0;
        }
        .search-container { max-width: 500px; }
        .search-container .form-control {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
        }
        .location-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1rem 0;
        }
        .location-info-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .forecast-overview {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .forecast-day {
            padding: 1rem;
            text-align: center;
            min-width: 120px;
        }
        .forecast-day:not(:last-child) {
            border-right: 1px solid #dee2e6;
        }
        .temp-max { color: #dc3545; font-weight: 700; font-size: 1.25rem; }
        .temp-min { color: #007bff; font-weight: 600; }
        .weather-tabs { margin-top: 2rem; }
        .radar-embed { border-radius: 0.5rem; min-height: 400px; }
        #suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.2);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        }
        .suggestion-header {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #6c757d;
            background: #f8f9fa;
        }
        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .suggestion-item:hover { background: #e3f2fd; }
        .suggestion-item i { color: #0056b3; margin-right: 0.5rem; }
        #clear-icon {
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
        .hours-scroll { overflow-x: auto; padding: 1rem 0; }
        .hours { display: flex; gap: 0.5rem; }
        .hours-hour {
            flex: 0 0 auto;
            min-width: 110px;
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        #b2t {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #0056b3;
            color: white;
            border: none;
            display: none;
            z-index: 1000;
        }
        #b2t.visible { display: block; }
    </style>
</head>

<body>
    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Zum Hauptinhalt</a>

    <!-- Back to Top -->
    <button id="b2t" title="Nach oben"><i class="fas fa-arrow-up"></i></button>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cloud-sun"></i>
                Wetter <?= e($location['city']) ?>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="#">
                            <span class="badge badge-light">HD</span> Vorhersage
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container">
            <h1><strong><?= e($location['city']) ?></strong></h1>
            <p><?= e($location['province_country']) ?></p>

            <div class="location-info">
                <?php if ($elevationText): ?>
                <span class="location-info-item">
                    <i class="fas fa-mountain"></i> <?= e($elevationText) ?>
                </span>
                <?php endif; ?>

                <?php if ($dailyData): ?>
                <span class="location-info-item">
                    <i class="fas fa-sun"></i> <?= e($dailyData['sunrise']) ?> Uhr
                </span>
                <span class="location-info-item">
                    <i class="fas fa-moon"></i> <?= e($dailyData['sunset']) ?> Uhr
                </span>
                <?php endif; ?>
            </div>

            <!-- Suchformular -->
            <div class="search-container position-relative mt-4">
                <form id="search-form" onsubmit="return false;">
                    <div class="input-group">
                        <input type="text"
                               class="form-control"
                               id="search-input"
                               autocomplete="off"
                               placeholder="Das Wetter in ...">
                        <i class="fa fa-times" id="clear-icon" style="display:none;"></i>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" style="border-radius: 0 50px 50px 0;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <div id="suggestions"></div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="container py-4">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb bg-transparent px-0">
                <li class="breadcrumb-item"><a href="#">Startseite</a></li>
                <li class="breadcrumb-item active">Wetter <?= e($location['city']) ?></li>
            </ol>
        </nav>

        <!-- Alert Button (wird per JS gefüllt) -->
        <div id="alertButtonContainer" class="mb-3" style="display:none;">
            <button id="alertButton" class="btn btn-warning btn-block" data-toggle="modal" data-target="#alertsModal">
                <i class="fas fa-exclamation-triangle"></i> Wetterwarnungen anzeigen
            </button>
        </div>

        <!-- 7-Tage Übersicht -->
        <section class="mb-5">
            <h2 class="h4 mb-3">
                <i class="fas fa-calendar-week"></i> 7-Tage Übersicht
            </h2>

            <div class="forecast-overview">
                <div class="row flex-nowrap m-0">
                    <?php
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
                    <div class="col forecast-day">
                        <h6>
                            <?php if ($i === 0): ?>
                            <span class="badge badge-primary">Heute</span><br>
                            <?php endif; ?>
                            <?= e($dayLabel) ?><br>
                            <small class="text-muted"><?= formatDate($timestamp, 'dd. MMM') ?></small>
                        </h6>

                        <hr>

                        <div id="Tag<?= $i + 1 ?>ContainerDIV">
                            <i class="fas fa-cloud fa-2x text-muted"></i>
                        </div>

                        <hr>

                        <?php if ($dayData): ?>
                        <div class="mb-2">
                            <span class="temp-max"><?= e($dayData['temp_max']) ?></span><br>
                            <span class="temp-min"><?= e($dayData['temp_min']) ?></span>
                        </div>

                        <small class="text-muted d-block">
                            <i class="wi wi-umbrella"></i> <?= e($dayData['precipitation']) ?>
                        </small>

                        <?php if ($isWinter): ?>
                        <small class="text-muted d-block">
                            <i class="fas fa-snowflake"></i> <?= e($dayData['snowfall']) ?>
                        </small>
                        <?php endif; ?>

                        <small class="text-muted d-block">
                            <i class="wi wi-strong-wind"></i> <?= e($dayData['wind_max']) ?>
                            (<?= e($dayData['wind_direction']) ?>)
                        </small>
                        <?php else: ?>
                        <span class="text-muted">Keine Daten</span>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <!-- Radar/Satellit Tabs -->
        <section class="weather-tabs">
            <h2 class="h4 mb-3">
                <i class="fas fa-map"></i> Karten & Radar
            </h2>

            <ul class="nav nav-pills mb-3" id="weatherTabs" role="tablist">
                <?php if ($isGermany): ?>
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#radarPane">
                        <i class="fas fa-cloud-rain"></i> Regenradar
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= !$isGermany ? 'active' : '' ?>" data-toggle="pill" href="#satellitePane">
                        <i class="fas fa-satellite"></i> Satellitenbild
                    </a>
                </li>
            </ul>

            <div class="tab-content bg-white p-3 rounded shadow-sm">
                <?php if ($isGermany): ?>
                <div class="tab-pane fade show active" id="radarPane">
                    <h3 class="h5">
                        Regenradar <?= e($location['city']) ?>
                        <span class="badge badge-primary">DWD 250m HD</span>
                    </h3>
                    <p class="text-muted small">
                        Niederschlagsradar in Echtzeit – sehen Sie straßengenau, wo es regnet.
                    </p>
                    <iframe class="radar-embed w-100"
                            loading="lazy"
                            style="border:0; height:500px;"
                            title="Regenradar"
                            src="https://www.wetterstation-neustadt.de/radar/radar.php?&startcenter=<?= e($location['lat']) ?>,<?= e($location['lng']) ?>">
                    </iframe>
                </div>
                <?php endif; ?>

                <div class="tab-pane fade <?= !$isGermany ? 'show active' : '' ?>" id="satellitePane">
                    <h3 class="h5">
                        Satellitenbild <?= e($location['city']) ?>
                        <span class="badge badge-info">LIVE</span>
                    </h3>
                    <p class="text-muted small">
                        EUMETSAT-Satellitenbilder, alle 15 Minuten aktualisiert.
                    </p>
                    <iframe class="radar-embed w-100"
                            loading="lazy"
                            style="border:0; height:500px;"
                            title="Satellitenbild"
                            src="https://www.wetterstation-neustadt.de/wettervorhersage/rainradarv2/sat.php?lat=<?= e($location['lat']) ?>&long=<?= e($location['lng']) ?>">
                    </iframe>
                </div>
            </div>
        </section>

        <!-- Info -->
        <section class="mt-5 p-4 bg-light rounded">
            <h2 class="h4">Wetter für <?= e($location['city']) ?></h2>
            <p>
                Präzise Wettervorhersage basierend auf dem <strong>ICON-Modell des Deutschen Wetterdienstes</strong>.
                Die Daten werden mehrmals täglich aktualisiert.
            </p>
            <ul class="list-unstyled">
                <li><i class="fas fa-check text-success"></i> 7-Tage-Vorhersage mit Temperatur, Wind und Niederschlag</li>
                <li><i class="fas fa-check text-success"></i> HD-Regenradar in 250m Auflösung (nur Deutschland)</li>
                <li><i class="fas fa-check text-success"></i> Echtzeit-Satellitenbilder von EUMETSAT</li>
            </ul>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1">&copy; <?= date('Y') ?> Wettervorhersage</p>
            <small class="text-muted">Daten: DWD ICON via open-meteo.com</small>
        </div>
    </footer>

    <!-- Alerts Modal -->
    <div class="modal fade" id="alertsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Wetterwarnungen</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="alerts">
                    <p class="text-muted text-center">Lade Warnungen...</p>
                </div>
                <div class="modal-footer">
                    <small class="text-muted mr-auto">© Deutscher Wetterdienst</small>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap JS (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>

    <!-- Koordinaten für JavaScript -->
    <script>
        window.WEATHER_CONFIG = {
            lat: <?= json_encode($location['lat']) ?>,
            lng: <?= json_encode($location['lng']) ?>,
            city: <?= json_encode($location['city']) ?>,
            slug: <?= json_encode($location['slug']) ?>,
            isGermany: <?= json_encode($isGermany) ?>
        };

        // ===== SUCHFUNKTION =====
        const MAPBOX_TOKEN = 'pk.eyJ1IjoiZGlnaXRhdCIsImEiOiJjbGVoNmQ0ajEwZzJmM3BtY2JmMXF4aGl3In0.82BMBf-3pv4NyM1Z6hnG5g';
        const MAPBOX_COUNTRIES = 'DE,AT,FR,IT,ES,CH,CZ,NL,DK,PL,BE,SK,HU,SI,HR,LU';

        let debounceTimer;

        document.getElementById('search-input').addEventListener('input', function() {
            const value = this.value.trim();
            const clearIcon = document.getElementById('clear-icon');

            clearIcon.style.display = value ? 'block' : 'none';

            clearTimeout(debounceTimer);

            if (value.length < 3) {
                document.getElementById('suggestions').innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => fetchSuggestions(value), 300);
        });

        document.getElementById('clear-icon').addEventListener('click', function() {
            document.getElementById('search-input').value = '';
            this.style.display = 'none';
            document.getElementById('suggestions').innerHTML = '';
        });

        async function fetchSuggestions(query) {
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${MAPBOX_TOKEN}&autocomplete=true&country=${MAPBOX_COUNTRIES}&language=de&types=postcode,place`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (!data.features) return;

                const suggestions = document.getElementById('suggestions');
                suggestions.innerHTML = '<div class="suggestion-header">Vorschläge</div>';

                data.features.slice(0, 5).forEach(feature => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${feature.place_name}`;
                    div.addEventListener('click', () => {
                        const slug = slugify(feature.place_name);
                        window.location.href = `?location=${slug}`;
                    });
                    suggestions.appendChild(div);
                });
            } catch (error) {
                console.error('Geocoding error:', error);
            }
        }

        function slugify(text) {
            if (!text) return '';
            const parts = text.split(',');
            let slugText = parts[0];
            if (parts[1]) slugText += ' ' + parts[1];

            return slugText.toLowerCase().trim()
                .replace(/\s+/g, '-')
                .replace(/[äöüß]/g, m => ({'ä':'ae','ö':'oe','ü':'ue','ß':'ss'}[m]))
                .replace(/&/g, '-und-')
                .replace(/[^\w-]+/g, '')
                .replace(/--+/g, '-');
        }

        // Click outside schließt Suggestions
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                document.getElementById('suggestions').innerHTML = '';
            }
        });

        // ===== BACK TO TOP =====
        const b2tBtn = document.getElementById('b2t');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 200) {
                b2tBtn.classList.add('visible');
            } else {
                b2tBtn.classList.remove('visible');
            }
        });

        b2tBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ===== WETTERWARNUNGEN =====
        async function loadAlerts() {
            if (!window.WEATHER_CONFIG.isGermany) return;

            try {
                const response = await fetch(`https://api.brightsky.dev/alerts?lat=${WEATHER_CONFIG.lat}&lon=${WEATHER_CONFIG.lng}`);
                const data = await response.json();

                if (data.alerts && data.alerts.length > 0) {
                    document.getElementById('alertButtonContainer').style.display = 'block';

                    const alertsDiv = document.getElementById('alerts');
                    alertsDiv.innerHTML = data.alerts.map(alert => `
                        <div class="alert alert-warning">
                            <strong>${alert.headline_de}</strong>
                            <p class="mb-0 small">${alert.description_de}</p>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Fehler beim Laden der Warnungen:', error);
            }
        }

        // Warnungen laden
        loadAlerts();
    </script>

</body>
</html>
