<?php
/**
 * Wettervorhersage - Hilfsfunktionen
 * Alle Utility-Funktionen für die Wetteranwendung
 */

/**
 * Sichere cURL-Anfrage mit Fehlerbehandlung
 */
function fetchUrl(string $url, array $options = []): ?string {
    $defaults = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Wettervorhersage/2.0 (+' . APP_URL . ')'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, $defaults + $options);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error || $httpCode >= 400) {
        error_log("cURL Error for $url: $error (HTTP $httpCode)");
        return null;
    }

    return $response;
}

/**
 * JSON sicher dekodieren
 */
function jsonDecode(string $json): ?array {
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return null;
    }

    return $data;
}

/**
 * Holt Geodaten für einen URL-Slug
 */
function getGeocodingForSlug(string $slug): ?array {
    $searchTerm = str_replace('-', ' ', $slug);

    $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/" .
           urlencode($searchTerm) .
           ".json?access_token=" . MAPBOX_TOKEN .
           "&country=" . MAPBOX_COUNTRIES .
           "&language=de&types=postcode,place&limit=1";

    $response = fetchUrl($url);
    if (!$response) return null;

    $data = jsonDecode($response);
    if (!$data || empty($data['features'])) return null;

    $feature = $data['features'][0];
    $city = '';
    $province = '';
    $country = '';

    // Hauptmerkmal auswerten
    if (strpos($feature['id'], 'place') === 0) {
        $city = $feature['text_de'] ?? $feature['text'] ?? '';
    }

    // Kontext durchsuchen
    if (isset($feature['context'])) {
        foreach ($feature['context'] as $context) {
            if (strpos($context['id'], 'place') === 0 && empty($city)) {
                $city = $context['text_de'] ?? $context['text'] ?? '';
            }
            if (strpos($context['id'], 'region') === 0) {
                $province = $context['text_de'] ?? $context['text'] ?? '';
            }
            if (strpos($context['id'], 'country') === 0) {
                $country = $context['text_de'] ?? $context['text'] ?? '';
            }
        }
    }

    // Fallback für Stadt
    if (empty($city)) {
        $city = $feature['text_de'] ?? $feature['text'] ?? 'Unbekannter Ort';
    }

    // Provinz/Land zusammensetzen
    $provinceCountry = implode(', ', array_filter([$province, $country]));

    return [
        'lat' => $feature['center'][1],
        'lng' => $feature['center'][0],
        'city' => $city,
        'province_country' => $provinceCountry
    ];
}

/**
 * Holt Höhendaten für Koordinaten
 */
function getElevation(float $lat, float $lng): ?int {
    $url = ELEVATION_API . "?locations={$lat},{$lng}";

    $response = @fetchUrl($url);
    if (!$response) return null;

    $data = jsonDecode($response);
    if (!$data || !isset($data['results'][0]['elevation'])) return null;

    return (int) round($data['results'][0]['elevation']);
}

/**
 * Windrichtung als Text (Himmelsrichtung)
 */
function getWindDirectionText(?float $degree): string {
    if ($degree === null) return 'N/A';

    $directions = ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW', 'N'];
    $index = (int) round(($degree % 360) / 45);

    return $directions[$index];
}

/**
 * Formatiert Datum mit IntlDateFormatter
 */
function formatDate(int $timestamp, string $pattern): string {
    static $formatter = null;

    if ($formatter === null) {
        $formatter = new IntlDateFormatter(
            'de_DE',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Berlin'
        );
    }

    $formatter->setPattern($pattern);
    return $formatter->format($timestamp);
}

/**
 * Formatiert Datum relativ (Heute, Morgen, etc.)
 */
function formatRelativeDate(int $timestamp): string {
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    $dayAfter = strtotime('+2 days midnight');

    if ($timestamp >= $today && $timestamp < $tomorrow) {
        return 'Heute';
    }
    if ($timestamp >= $tomorrow && $timestamp < $dayAfter) {
        return 'Morgen';
    }

    return formatDate($timestamp, 'EEEE');
}

/**
 * Prüft ob es Tag oder Nacht ist
 */
function isDaytime(int $timestamp, float $lat, float $lng): bool {
    $sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng);
    $sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng);

    return $timestamp >= $sunrise && $timestamp <= $sunset;
}

/**
 * Erkennt Mobile-Geräte
 */
function isMobileDevice(): bool {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $mobilePattern = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|' .
                    'elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|' .
                    'netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|' .
                    'series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|' .
                    'xda|xiino/i';

    return (bool) preg_match($mobilePattern, $userAgent);
}

/**
 * Sichere HTML-Ausgabe
 */
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generiert kanonische URL
 */
function getCanonicalUrl(string $slug): string {
    return APP_URL . '/wettervorhersage/' . e($slug);
}

/**
 * Generiert strukturierte Daten (JSON-LD) für SEO
 */
function generateStructuredData(array $location, array $forecast): string {
    $breadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Startseite',
                'item' => APP_URL . '/'
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Wettervorhersage ' . $location['city'],
                'item' => getCanonicalUrl($location['slug'])
            ]
        ]
    ];

    $place = [
        '@context' => 'https://schema.org',
        '@type' => 'Place',
        'name' => 'Wettervorhersage ' . $location['city'],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $location['lat'],
            'longitude' => $location['lng']
        ]
    ];

    // WeatherForecast Schema (experimentell aber gut für SEO)
    $weatherData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'Wettervorhersage für ' . $location['city'],
        'description' => 'Aktuelle 7-Tage-Wettervorhersage für ' . $location['city'] . ' mit Regenradar und Warnungen.',
        'url' => getCanonicalUrl($location['slug']),
        'dateModified' => date('c'),
        'about' => [
            '@type' => 'City',
            'name' => $location['city'],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $location['lat'],
                'longitude' => $location['lng']
            ]
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES) . '</script>' . "\n" .
           '<script type="application/ld+json">' . json_encode($place, JSON_UNESCAPED_SLASHES) . '</script>' . "\n" .
           '<script type="application/ld+json">' . json_encode($weatherData, JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generiert Open Graph Meta-Tags
 */
function generateOpenGraphTags(array $location): string {
    $title = "Wettervorhersage {$location['city']} - 7-Tage-Prognose";
    $description = "Präzise 7-Tage-Wettervorhersage für {$location['city']}. Mit HD-Regenradar, Satellitenbildern und DWD-Warnungen.";
    $url = getCanonicalUrl($location['slug']);
    $image = APP_URL . '/images/og-weather.jpg';

    return <<<HTML
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:url" content="{$url}">
    <meta property="og:image" content="{$image}">
    <meta property="og:locale" content="de_DE">
    <meta property="og:site_name" content="Wetterstation Neustadt">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$title}">
    <meta name="twitter:description" content="{$description}">
    <meta name="twitter:image" content="{$image}">
HTML;
}

/**
 * Holt Wetterdaten von Open-Meteo
 */
function fetchWeatherData(float $lat, float $lng): ?array {
    $url = OPENMETEO_DWD .
           "?latitude={$lat}" .
           "&longitude={$lng}" .
           "&daily=sunrise,sunset,temperature_2m_max,temperature_2m_min,precipitation_sum,rain_sum,showers_sum,snowfall_sum,windgusts_10m_max,winddirection_10m_dominant" .
           "&hourly=temperature_2m,pressure_msl,precipitation,weathercode,cloudcover,cloudcover_low,cloudcover_mid,cloudcover_high,winddirection_10m,windgusts_10m" .
           "&forecast_days=" . FORECAST_DAYS .
           "&timezone=Europe%2FBerlin";

    $response = fetchUrl($url);
    if (!$response) return null;

    return jsonDecode($response);
}

/**
 * Verarbeitet tägliche Wetterdaten
 */
function processDailyData(array $data): array {
    $daily = $data['daily'] ?? [];
    $daysCount = count($daily['time'] ?? []);

    $processed = [
        'sunrise' => isset($daily['sunrise'][0]) ? date('H:i', strtotime($daily['sunrise'][0])) : '--:--',
        'sunset' => isset($daily['sunset'][0]) ? date('H:i', strtotime($daily['sunset'][0])) : '--:--',
        'days' => []
    ];

    for ($i = 0; $i < $daysCount; $i++) {
        $processed['days'][$i] = [
            'date' => $daily['time'][$i] ?? '',
            'temp_max' => isset($daily['temperature_2m_max'][$i])
                ? round($daily['temperature_2m_max'][$i]) . '°C'
                : '--',
            'temp_min' => isset($daily['temperature_2m_min'][$i])
                ? round($daily['temperature_2m_min'][$i]) . '°C'
                : '--',
            'wind_max' => isset($daily['windgusts_10m_max'][$i])
                ? round($daily['windgusts_10m_max'][$i]) . ' km/h'
                : '--',
            'precipitation' => isset($daily['precipitation_sum'][$i])
                ? $daily['precipitation_sum'][$i] . ' l/m²'
                : '0 l/m²',
            'snowfall' => isset($daily['snowfall_sum'][$i])
                ? round($daily['snowfall_sum'][$i], 1) . ' cm'
                : '0 cm',
            'wind_direction' => isset($daily['winddirection_10m_dominant'][$i])
                ? getWindDirectionText($daily['winddirection_10m_dominant'][$i])
                : '--'
        ];
    }

    return $processed;
}

/**
 * Generiert kritisches CSS (Above-the-fold)
 */
function getCriticalCss(): string {
    return <<<CSS
<style>
:root{--color-primary:#0056b3;--color-bg:#f8f9fa}
*{box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;margin:0;background:var(--color-bg)}
.skip-link{position:absolute;top:-40px;left:0;background:var(--color-primary);color:#fff;padding:.5rem 1rem;z-index:10000}
.skip-link:focus{top:0}
.navbar{background:#343a40;padding:.5rem 1rem}
.navbar-brand{color:#fff;font-weight:700}
.container{max-width:1140px;margin:0 auto;padding:0 15px}
.hero-section{background:linear-gradient(135deg,var(--color-primary),#003d82);color:#fff;padding:2rem 0}
.loading{display:flex;justify-content:center;align-items:center;min-height:200px}
.spinner{width:40px;height:40px;border:4px solid #f3f3f3;border-top:4px solid var(--color-primary);border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
</style>
CSS;
}

/**
 * Prüft ob Schneeanzeige aktiviert sein sollte (Winter)
 */
function isWinterSeason(): bool {
    $monthDay = (int) date('md');
    return $monthDay >= 1115 || $monthDay <= 315;
}

/**
 * Generiert Preload-Links für kritische Ressourcen
 */
function getPreloadLinks(): string {
    return <<<HTML
    <link rel="preconnect" href="https://api.mapbox.com" crossorigin>
    <link rel="preconnect" href="https://mein-wetter-proxy.vercel.app" crossorigin>
    <link rel="preconnect" href="https://www.wetterstation-neustadt.de" crossorigin>
    <link rel="dns-prefetch" href="https://api.brightsky.dev">
    <link rel="dns-prefetch" href="https://ensemble-api.open-meteo.com">
HTML;
}
