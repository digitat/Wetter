<?php
/**
 * Wettervorhersage - Modal-Dialoge
 * Alle Modals für Info-Dialoge, Warnungen etc.
 */
?>

<!-- ==================== WETTERWARNUNGEN MODAL ==================== -->
<div class="modal fade" id="alertsModal" tabindex="-1" role="dialog" aria-labelledby="alertsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertsModalLabel">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    Wetterwarnungen
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Filter Bar -->
                <div class="filter-bar" id="filterBar" role="toolbar" aria-label="Filter für Wetterwarnungen">
                    <button class="filter-btn active" data-filter="all" aria-pressed="true">
                        <i class="fa-solid fa-list" aria-hidden="true"></i>
                        Alle (<span id="count-all">0</span>)
                    </button>
                    <button class="filter-btn" data-filter="active" aria-pressed="false">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        Aktive (<span id="count-active">0</span>)
                    </button>
                    <button class="filter-btn" data-filter="upcoming" aria-pressed="false">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        Bevorstehend (<span id="count-upcoming">0</span>)
                    </button>
                    <button class="filter-btn" data-filter="severe" aria-pressed="false">
                        <i class="fa-solid fa-house-chimney-crack" aria-hidden="true"></i>
                        Unwetter (<span id="count-severe">0</span>)
                    </button>
                </div>

                <!-- Alerts Container -->
                <div id="alerts" role="region" aria-live="polite" aria-label="Aktuelle Wetterwarnungen"></div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state text-center py-5" style="display: none;">
                    <i class="fa-solid fa-cloud-sun fa-3x text-muted mb-3" aria-hidden="true"></i>
                    <p class="text-muted">Keine Wetterwarnungen für diese Ansicht</p>
                </div>

                <!-- Legend -->
                <hr>
                <details>
                    <summary class="text-muted small mb-2" style="cursor: pointer;">
                        <strong>Warnstufen anzeigen</strong>
                    </summary>
                    <div class="warning-legend mt-2">
                        <div class="legend-item"><span class="marker-warning level-1"></span> Wetterwarnung</div>
                        <div class="legend-item"><span class="marker-warning level-2"></span> Markantes Wetter</div>
                        <div class="legend-item"><span class="marker-warning level-3"></span> Unwetter</div>
                        <div class="legend-item"><span class="marker-warning level-4"></span> Extremes Unwetter</div>
                    </div>
                </details>
            </div>

            <div class="modal-footer">
                <small class="text-muted mr-auto">
                    <i class="fa-solid fa-copyright" aria-hidden="true"></i>
                    Deutscher Wetterdienst (DWD)
                </small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== WETTERMODELLE ERKLÄRUNG MODAL ==================== -->
<div class="modal fade" id="weatherModelsModal" tabindex="-1" role="dialog" aria-labelledby="weatherModelsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="weatherModelsModalLabel">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Warum gibt es 3 verschiedene Wetterdiagramme?
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="lead">
                    Meine Wettervorhersage nutzt drei verschiedene Modelle, um Ihnen die beste Genauigkeit für jeden Zeitraum zu bieten.
                </p>

                <article class="mb-4">
                    <h6><strong>48 Stunden: Höchste Detailgenauigkeit</strong></h6>
                    <p>
                        Das <strong>ICON-D2 Modell</strong> ist mit 2 km Auflösung das präziseste verfügbare Wettermodell.
                        Es erfasst selbst kleinräumige Wetterphänomene wie lokale Schauer, Gewitter oder Windböen besonders genau.
                    </p>
                </article>

                <article class="mb-4">
                    <h6><strong>5 Tage: Optimale Wochenplanung</strong></h6>
                    <p>
                        Das <strong>ICON-EU Modell</strong> mit 7 km Auflösung deckt ganz Europa ab und bietet den
                        besten Kompromiss zwischen Genauigkeit und Vorhersagelänge.
                    </p>
                </article>

                <article class="mb-4">
                    <h6><strong>7,5 Tage: Längerfristige Trends</strong></h6>
                    <p>
                        Das <strong>globale ICON-Modell</strong> mit 13 km Auflösung zeigt großräumige Wettermuster
                        und Trends für über eine Woche im Voraus.
                    </p>
                </article>

                <div class="alert alert-info" role="note">
                    <strong>Tipp:</strong> Für heute und morgen schauen Sie ins 48-Stunden-Diagramm.
                    Für die kommende Woche nutzen Sie das 5-Tage-Diagramm.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Verstanden</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== RADAR INFO MODAL ==================== -->
<div class="modal fade" id="radarInfoModal" tabindex="-1" role="dialog" aria-labelledby="radarInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="radarInfoModalLabel">
                    <i class="fas fa-broadcast-tower" aria-hidden="true"></i>
                    So funktioniert das Wetterradar
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        Was zeigt das Regenradar?
                    </h6>
                    <p>
                        Das HD-Wetterradar erfasst mit einer Auflösung von bis zu 250 Metern präzise, wo gerade Niederschlag fällt.
                        Sie sehen flächendeckend für ganz Deutschland, in welchen Gebieten es aktuell regnet, schneit, graupelt oder hagelt.
                    </p>
                </section>

                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">
                        <i class="fas fa-palette" aria-hidden="true"></i>
                        Wie lese ich die Farbskala?
                    </h6>
                    <p>
                        Die Farben zeigen die Niederschlagsintensität: <span class="text-info">Blau</span> und
                        <span class="text-success">Grün</span> bedeuten leichten bis mäßigen Regen,
                        <span class="text-warning">Gelb</span> und <span class="text-orange">Orange</span> stehen für starken Niederschlag.
                        <span class="text-danger">Rote</span> und <span class="text-purple">violette</span> Bereiche weisen auf Starkregen oder Hagel hin.
                    </p>
                </section>

                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">
                        <i class="fas fa-eye-slash" aria-hidden="true"></i>
                        Was kann das Radar nicht anzeigen?
                    </h6>
                    <ul>
                        <li><strong>Keine Wolken:</strong> Das Radar erfasst nur fallenden Niederschlag, keine Bewölkung.</li>
                        <li><strong>Keine Niederschlagsart:</strong> Ob Regen, Schnee oder Hagel kann nicht unterschieden werden.</li>
                        <li><strong>Kein Sprühregen:</strong> Sehr feiner Nieselregen wird oft nicht erfasst.</li>
                    </ul>
                </section>

                <section>
                    <h6 class="font-weight-bold text-primary">
                        <i class="fas fa-database" aria-hidden="true"></i>
                        Datenquelle
                    </h6>
                    <p class="mb-0">
                        Die Daten liefert der <strong>Deutsche Wetterdienst (DWD)</strong> mit seinem Netzwerk aus 17 Radarstationen.
                    </p>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Verstanden</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== WARNUNGEN INFO MODAL ==================== -->
<div class="modal fade" id="warningInfoModal" tabindex="-1" role="dialog" aria-labelledby="warningInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="warningInfoModalLabel">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    Wetterwarnungen verstehen
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <section class="mb-4">
                    <h6 class="font-weight-bold">Was sind amtliche Wetterwarnungen?</h6>
                    <p>
                        Der Deutsche Wetterdienst (DWD) gibt offizielle Warnungen heraus, wenn gefährliche Wetterereignisse drohen.
                        Diese Warnungen sind rechtlich bindend für Behörden und dienen Ihrer Sicherheit.
                    </p>
                </section>

                <section class="mb-4">
                    <h6 class="font-weight-bold">Die vier Warnstufen</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Stufe</th>
                                    <th scope="col">Bedeutung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge" style="background:#ffeb3b;color:#000">Gelb</span></td>
                                    <td>Wetterentwicklung beobachten. Keine unmittelbare Gefahr.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge" style="background:#ff9800;color:#000">Orange</span></td>
                                    <td>Vorsichtsmaßnahmen treffen. Mögliche Gefahren im Freien.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge" style="background:#f44336;color:#fff">Rot</span></td>
                                    <td>Aufenthalt im Freien vermeiden. Erhebliche Schäden möglich.</td>
                                </tr>
                                <tr>
                                    <td><span class="badge" style="background:#9c27b0;color:#fff">Violett</span></td>
                                    <td>Lebensgefahr möglich! Anweisungen der Behörden befolgen.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-dismiss="modal">Verstanden</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== WARNLAGEBERICHT MODAL ==================== -->
<div class="modal fade" id="warningReportModal" tabindex="-1" role="dialog" aria-labelledby="warningReportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="warningReportLabel">
                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                    Warnlagebericht Deutschland
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="warningReportContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Lädt Warnlagebericht...</span>
                    </div>
                    <p class="mt-2 text-muted">Lade Warnlagebericht...</p>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">© Deutscher Wetterdienst</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SATELLITEN INFO MODAL ==================== -->
<div class="modal fade" id="satelliteInfoModal" tabindex="-1" role="dialog" aria-labelledby="satelliteInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="satelliteInfoModalLabel">
                    <i class="fas fa-satellite" aria-hidden="true"></i>
                    Satellitenbilder verstehen
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">Was zeigen Satellitenbilder?</h6>
                    <p>
                        Wettersatelliten fotografieren die Erde aus dem Weltall und zeigen Wolkenformationen und Wetterfronten.
                        Anders als das Radar sehen Sie hier die gesamte Bewölkung – auch Wolken, aus denen (noch) kein Regen fällt.
                    </p>
                </section>

                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">Woher stammen die Bilder?</h6>
                    <p>
                        Die Aufnahmen liefert <strong>EUMETSAT</strong> vom geostationären Satelliten <strong>Meteosat</strong>
                        in 36.000 km Höhe. Die Bilder werden alle <strong>15 Minuten</strong> aktualisiert.
                    </p>
                </section>

                <section class="mb-4">
                    <h6 class="font-weight-bold text-primary">Was kann ich erkennen?</h6>
                    <ul>
                        <li><strong>Weiße, kompakte Türme:</strong> Gewitterwolken</li>
                        <li><strong>Flächige graue Schleier:</strong> Hochnebelfelder</li>
                        <li><strong>Spiralförmige Strukturen:</strong> Tiefdruckgebiete</li>
                        <li><strong>Lange Wolkenbänder:</strong> Wetterfronten</li>
                        <li><strong>Dunkle Bereiche:</strong> Wolkenfreie Zonen</li>
                    </ul>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-dismiss="modal">Verstanden</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== LAYER INFO MODAL ==================== -->
<div class="modal fade" id="layerInfoModal" tabindex="-1" role="dialog" aria-labelledby="layerInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="layerInfoModalLabel">
                    <i class="fa fa-layer-group" aria-hidden="true"></i>
                    Karten-Layer auswählen
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Dialog schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p class="text-muted small">Wählen Sie den optimalen Layer für Ihre Wetteranalyse:</p>

                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Satellit HD</strong>
                            <span class="badge badge-success">Tag & Nacht</span>
                        </div>
                        <small class="text-muted">Kombiniert: Tagsüber Echtfarben, nachts Infrarot.</small>
                    </div>

                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Europa HD</strong>
                            <span class="badge badge-info">Nur Tag</span>
                        </div>
                        <small class="text-muted">Höchste Auflösung. Ideal für Gewittertürme.</small>
                    </div>

                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Infrarot HD</strong>
                            <span class="badge badge-secondary">Temperatur</span>
                        </div>
                        <small class="text-muted">Zeigt Wolkentemperaturen. <strong>Beste Wahl nachts!</strong></small>
                    </div>

                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Niederschlagsradar</strong>
                            <span class="badge badge-primary">Overlay</span>
                        </div>
                        <small class="text-muted">DWD-Radardaten über das Satellitenbild.</small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Warnlagebericht Laden Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delegierter Event-Listener für Warnlagebericht
    $('#warningReportModal').on('show.bs.modal', async function() {
        const content = document.getElementById('warningReportContent');

        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Lädt...</span>
                </div>
            </div>
        `;

        try {
            const response = await fetch('proxy_warnlagebericht.php');

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const html = await response.text();

            let text = html
                .replace(/<pre[^>]*>|<\/pre>/g, '')
                .replace(/\b([A-ZÄÖÜ]{2,}(?:\s[A-ZÄÖÜ]{2,})*)\b/g, '<strong>$1</strong>');

            const sections = text.split(/\n\s*\n+/).map(section =>
                '<p>' + section.replace(/\n/g, '<br>') + '</p>'
            ).join('');

            content.innerHTML = sections + '<small class="text-muted d-block mt-3">© Deutscher Wetterdienst</small>';

        } catch (error) {
            console.error('Fehler beim Laden des Warnlageberichts:', error);
            content.innerHTML = `
                <div class="alert alert-warning" role="alert">
                    <strong>Fehler:</strong> Der Warnlagebericht konnte nicht geladen werden.
                </div>
            `;
        }
    });
});
</script>
