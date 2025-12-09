/**
 * Wettervorhersage - Modernisiertes JavaScript
 * Konsolidierte und optimierte Version
 *
 * Module:
 * - SearchModule: Ortssuche und Autocomplete
 * - WeatherModule: Wettervorhersage-Logik
 * - ChartsModule: Highcharts-Integration
 * - AlertsModule: DWD Warnungen
 * - UIModule: UI-Interaktionen
 */

'use strict';

// ============================================
// KONFIGURATION
// ============================================
const APP_CONFIG = {
    mapbox: {
        token: 'pk.eyJ1IjoiZGlnaXRhdCIsImEiOiJjbGVoNmQ0ajEwZzJmM3BtY2JmMXF4aGl3In0.82BMBf-3pv4NyM1Z6hnG5g',
        countries: 'DE,AT,FR,IT,ES,CH,CZ,NL,DK,PL,BE,SK,HU,SI,HR,LU'
    },
    api: {
        brightsky: 'https://api.brightsky.dev/alerts',
        openMeteo: 'https://mein-wetter-proxy.vercel.app/v1/forecast',
        ensemble: 'https://ensemble-api.open-meteo.com/v1/ensemble'
    },
    storage: {
        recentSearches: 'recentSearches',
        maxRecentSearches: 5
    },
    debounce: {
        search: 300,
        resize: 150
    },
    icons: {
        baseUrl: 'https://www.wetterstation-neustadt.de/icons/new4'
    }
};

// ============================================
// UTILITY FUNCTIONS
// ============================================
const Utils = {
    /**
     * Debounce-Funktion für Performance-Optimierung
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle-Funktion für Scroll-Events
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Sichere localStorage-Operationen
     */
    storage: {
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.warn('localStorage read error:', e);
                return defaultValue;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                console.warn('localStorage write error:', e);
                return false;
            }
        },
        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                return false;
            }
        }
    },

    /**
     * Slug-Generierung für URLs
     */
    slugify(text) {
        if (!text) return '';
        const parts = text.split(',');
        let slugText = parts[0];
        if (parts[1]) slugText += ' ' + parts[1];

        return slugText
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[äöüß]/g, (match) => ({ 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' }[match]))
            .replace(/&/g, '-und-')
            .replace(/[^\w-]+/g, '')
            .replace(/--+/g, '-');
    },

    /**
     * Escape HTML für sichere Ausgabe
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Formatierung von Datum/Zeit
     */
    formatDate(date, options = {}) {
        const defaults = {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(date).toLocaleString('de-DE', { ...defaults, ...options });
    },

    /**
     * Berechnung der verbleibenden Zeit
     */
    calculateTimeRemaining(targetDate) {
        const diff = new Date(targetDate) - new Date();
        return {
            total: diff,
            days: Math.floor(diff / 86400000),
            hours: Math.floor((diff % 86400000) / 3600000),
            minutes: Math.floor((diff % 3600000) / 60000)
        };
    },

    /**
     * Formatierung der verbleibenden Zeit
     */
    formatTimeRemaining({ days, hours, minutes }) {
        const parts = [];
        if (days > 0) parts.push(`${days}d`);
        if (hours > 0) parts.push(`${hours}h`);
        if (minutes > 0) parts.push(`${minutes}min`);
        return parts.join(' ') || '< 1min';
    }
};

// ============================================
// SEARCH MODULE
// ============================================
const SearchModule = {
    elements: {},
    debounceTimer: null,

    init() {
        this.elements = {
            form: document.getElementById('search-form'),
            input: document.getElementById('search-input'),
            suggestions: document.getElementById('suggestions'),
            clearIcon: document.getElementById('clear-icon'),
            recentMenu: document.getElementById('recent-searches-menu'),
            recentDropdown: document.getElementById('recent-searches-dropdown')
        };

        if (!this.elements.form || !this.elements.input) return;

        this.bindEvents();
        this.loadRecentSearches();
    },

    bindEvents() {
        // Input-Event mit Debounce
        this.elements.input.addEventListener('input',
            Utils.debounce(() => this.handleInput(), APP_CONFIG.debounce.search)
        );

        // Clear-Icon
        if (this.elements.clearIcon) {
            this.elements.clearIcon.addEventListener('click', () => this.clearInput());
        }

        // Suggestions klicken
        if (this.elements.suggestions) {
            this.elements.suggestions.addEventListener('click', (e) => this.handleSuggestionClick(e));
        }

        // Recent Searches klicken
        if (this.elements.recentMenu) {
            this.elements.recentMenu.addEventListener('click', (e) => this.handleRecentClick(e));
        }

        // Form Submit
        this.elements.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Click outside schließt Suggestions
        document.addEventListener('click', (e) => {
            if (!this.elements.suggestions?.contains(e.target) &&
                !this.elements.input?.contains(e.target)) {
                this.closeSuggestions();
            }
        });

        // Tastatur-Navigation für Barrierefreiheit
        this.elements.input.addEventListener('keydown', (e) => this.handleKeyboard(e));
    },

    async handleInput() {
        const value = this.elements.input.value.trim();

        // Clear Icon anzeigen/verstecken
        if (this.elements.clearIcon) {
            this.elements.clearIcon.style.display = value ? 'block' : 'none';
        }

        if (value.length < 3) {
            this.closeSuggestions();
            return;
        }

        await this.fetchSuggestions(value);
    },

    async fetchSuggestions(query) {
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?` +
            `access_token=${APP_CONFIG.mapbox.token}&` +
            `autocomplete=true&country=${APP_CONFIG.mapbox.countries}&` +
            `language=de&types=postcode,place`;

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            this.renderSuggestions(data.features?.slice(0, 5) || []);
        } catch (error) {
            console.error('Geocoding error:', error);
            this.closeSuggestions();
        }
    },

    renderSuggestions(features) {
        if (!features.length) {
            this.closeSuggestions();
            return;
        }

        const header = document.createElement('div');
        header.className = 'suggestion-header';
        header.textContent = 'Vorschläge';
        header.setAttribute('role', 'presentation');

        this.elements.suggestions.innerHTML = '';
        this.elements.suggestions.appendChild(header);

        features.forEach((feature, index) => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.setAttribute('role', 'option');
            div.setAttribute('tabindex', '0');
            div.setAttribute('aria-selected', 'false');
            div.dataset.index = index;
            div.dataset.name = feature.place_name;
            div.dataset.latitude = feature.center[1];
            div.dataset.longitude = feature.center[0];

            div.innerHTML = `
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                <span>${Utils.escapeHtml(feature.place_name)}</span>
            `;

            this.elements.suggestions.appendChild(div);
        });

        this.elements.suggestions.setAttribute('role', 'listbox');
        this.elements.suggestions.setAttribute('aria-label', 'Ortsvorschläge');
    },

    handleSuggestionClick(e) {
        const suggestion = e.target.closest('.suggestion-item');
        if (!suggestion) return;

        this.selectSuggestion(suggestion.dataset);
    },

    selectSuggestion({ name, latitude, longitude }) {
        // In localStorage speichern
        Utils.storage.set(name, { latitude, longitude });

        // Zu recent searches hinzufügen
        let recent = Utils.storage.get(APP_CONFIG.storage.recentSearches, []);
        if (!recent.includes(name)) {
            recent.push(name);
            if (recent.length > APP_CONFIG.storage.maxRecentSearches) {
                const oldest = recent.shift();
                Utils.storage.remove(oldest);
            }
            Utils.storage.set(APP_CONFIG.storage.recentSearches, recent);
        }

        // Navigation
        const slug = Utils.slugify(name);
        window.location.href = `./${slug}`;
    },

    handleRecentClick(e) {
        const link = e.target.closest('.dropdown-item');
        if (!link) return;

        e.preventDefault();
        const searchTerm = link.dataset.name;
        if (searchTerm) {
            const slug = Utils.slugify(searchTerm);
            window.location.href = `./${slug}`;
        }
    },

    handleSubmit(e) {
        e.preventDefault();
        const searchTerm = this.elements.input.value.trim();
        if (searchTerm) {
            const slug = Utils.slugify(searchTerm);
            window.location.href = `./${slug}`;
        }
    },

    handleKeyboard(e) {
        const suggestions = this.elements.suggestions?.querySelectorAll('.suggestion-item');
        if (!suggestions?.length) return;

        const current = this.elements.suggestions.querySelector('[aria-selected="true"]');
        let currentIndex = current ? parseInt(current.dataset.index) : -1;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = Math.min(currentIndex + 1, suggestions.length - 1);
                this.highlightSuggestion(suggestions, currentIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = Math.max(currentIndex - 1, 0);
                this.highlightSuggestion(suggestions, currentIndex);
                break;
            case 'Enter':
                if (current) {
                    e.preventDefault();
                    this.selectSuggestion(current.dataset);
                }
                break;
            case 'Escape':
                this.closeSuggestions();
                break;
        }
    },

    highlightSuggestion(suggestions, index) {
        suggestions.forEach((s, i) => {
            s.setAttribute('aria-selected', i === index ? 'true' : 'false');
            if (i === index) s.focus();
        });
    },

    loadRecentSearches() {
        const recent = Utils.storage.get(APP_CONFIG.storage.recentSearches, []);

        if (!recent.length || !this.elements.recentDropdown) {
            if (this.elements.recentDropdown) {
                this.elements.recentDropdown.style.display = 'none';
            }
            return;
        }

        this.elements.recentDropdown.style.display = 'block';
        this.elements.recentMenu.innerHTML = '';

        recent.slice().reverse().forEach(search => {
            const item = document.createElement('a');
            item.className = 'dropdown-item recent-search-item';
            item.href = '#';
            item.dataset.name = search;
            item.setAttribute('role', 'menuitem');
            item.innerHTML = `
                <i class="fa-solid fa-clock-rotate-left recent-search-icon" aria-hidden="true"></i>
                <span class="recent-search-text">${Utils.escapeHtml(search)}</span>
            `;
            this.elements.recentMenu.appendChild(item);
        });
    },

    clearInput() {
        this.elements.input.value = '';
        this.elements.clearIcon.style.display = 'none';
        this.closeSuggestions();
        this.elements.input.focus();
    },

    closeSuggestions() {
        if (this.elements.suggestions) {
            this.elements.suggestions.innerHTML = '';
        }
    }
};

// ============================================
// UI MODULE
// ============================================
const UIModule = {
    init() {
        this.initBackToTop();
        this.initTooltips();
        this.initLazyLoading();
        this.initScrollBehavior();
    },

    initBackToTop() {
        const button = document.getElementById('b2t');
        if (!button) return;

        const toggleVisibility = () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            button.classList.toggle('visible', scrollTop > 200);
        };

        window.addEventListener('scroll', Utils.throttle(toggleVisibility, 100));

        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Tastatur-Zugänglichkeit
        button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    },

    initTooltips() {
        // Bootstrap Tooltips initialisieren
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip({
                trigger: 'hover focus',
                container: 'body'
            });
        }
    },

    initLazyLoading() {
        // Native Lazy Loading für Bilder
        const images = document.querySelectorAll('img[data-src]');

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '50px' });

            images.forEach(img => observer.observe(img));
        } else {
            // Fallback für ältere Browser
            images.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }

        // Lazy Loading für iframes
        const iframes = document.querySelectorAll('iframe[data-src]');
        if ('IntersectionObserver' in window) {
            const iframeObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const iframe = entry.target;
                        iframe.src = iframe.dataset.src;
                        iframe.removeAttribute('data-src');
                        iframeObserver.unobserve(iframe);
                    }
                });
            }, { rootMargin: '100px' });

            iframes.forEach(iframe => iframeObserver.observe(iframe));
        }
    },

    initScrollBehavior() {
        // Smooth scroll für Anker-Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    // Fokus für Barrierefreiheit
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                }
            });
        });
    },

    /**
     * Zeigt einen Toast/Benachrichtigung an
     */
    showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} notification-toast`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML = `
            <span>${Utils.escapeHtml(message)}</span>
            <button type="button" class="close" aria-label="Schließen">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

        toast.querySelector('.close').addEventListener('click', () => toast.remove());

        document.body.appendChild(toast);

        // Auto-remove nach 5 Sekunden
        setTimeout(() => toast.remove(), 5000);
    }
};

// ============================================
// ALERTS MODULE
// ============================================
const AlertsModule = {
    config: {
        severityLevels: {
            'vorabinformation': 0, 'minor': 1, 'moderate': 2, 'severe': 3, 'extreme': 4
        },
        severityClasses: {
            'vorabinformation': 'alert-vorabinformation btn-vorabinformation',
            'minor': 'alert-minor btn-minor',
            'moderate': 'alert-moderate btn-moderate',
            'severe': 'alert-danger btn-danger',
            'extreme': 'alert-extreme btn-extreme'
        },
        eventIcons: {
            'wind': 'fa-solid fa-wind',
            'thunderstorm': 'fa-solid fa-cloud-bolt',
            'snow': 'fa-solid fa-snowflake',
            'ice': 'fa-solid fa-road',
            'frost': 'fa-solid fa-temperature-low',
            'rain': 'fa-solid fa-cloud-showers-heavy',
            'fog': 'fa-solid fa-smog',
            'default': 'fa-solid fa-triangle-exclamation'
        }
    },
    allAlerts: [],
    localAlerts: [],
    localLocationName: '',
    currentFilter: 'all',
    availableHeights: new Set(),

    init(lat, lon, locationName) {
        if (!lat || !lon) return;

        this.localLocationName = locationName || 'Ihr Standort';
        this.fetchAlerts(lat, lon);
        this.bindEvents();
    },

    async fetchAlerts(lat, lon) {
        try {
            const response = await fetch(`${APP_CONFIG.api.brightsky}?lat=${lat}&lon=${lon}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (!data.alerts?.length) {
                this.hideAlertButton();
                return;
            }

            if (data.location?.name) {
                this.localLocationName = data.location.name;
            }

            this.localAlerts = this.sortAlerts(data.alerts);
            this.allAlerts = [...this.localAlerts];

            this.updateTriggerButton();
            this.renderAlerts();
            this.updateCounts();
        } catch (error) {
            console.error('Fehler beim Laden der Warnungen:', error);
        }
    },

    sortAlerts(alerts) {
        return alerts.sort((a, b) => {
            const sevDiff = this.config.severityLevels[this.getSeverityClass(b)] -
                           this.config.severityLevels[this.getSeverityClass(a)];
            if (sevDiff !== 0) return sevDiff;
            return new Date(b.effective) - new Date(a.effective);
        });
    },

    getSeverityClass(alert) {
        if (alert.headline_de?.includes('VORABINFORMATION')) return 'vorabinformation';
        return alert.severity || 'minor';
    },

    getEventIcon(alert) {
        const eventEn = (alert.event_en || '').toLowerCase();
        const eventDe = (alert.event_de || '').toLowerCase();
        const combined = eventEn + ' ' + eventDe;

        for (const [key, icon] of Object.entries(this.config.eventIcons)) {
            if (key !== 'default' && combined.includes(key)) {
                return icon;
            }
        }
        return this.config.eventIcons.default;
    },

    extractHeight(alert) {
        const desc = alert.description_de || alert.description_en || '';
        const patterns = [/oberhalb\s+(\d+)\s*m/i, /über\s+(\d+)\s*m/i, />\s*(\d+)\s*m/i];

        for (const pattern of patterns) {
            const match = desc.match(pattern);
            if (match) return parseInt(match[1]);
        }
        return null;
    },

    isActiveAlert(alert) {
        const now = new Date();
        return new Date(alert.onset) <= now && new Date(alert.expires) > now;
    },

    isSevereAlert(alert) {
        return this.config.severityLevels[this.getSeverityClass(alert)] >= 3;
    },

    updateTriggerButton() {
        const button = document.getElementById('alertButton');
        const container = document.getElementById('alertButtonContainer');

        if (!button || !this.localAlerts.length) return;

        const mainAlert = this.localAlerts[0];
        const severity = this.getSeverityClass(mainAlert);
        const buttonClass = this.config.severityClasses[severity].split(' ')[1];
        const iconClass = this.getEventIcon(mainAlert);

        button.className = `btn btn-block mb-3 ${buttonClass}`;
        button.style.display = 'block';
        if (container) container.style.display = 'flex';

        button.innerHTML = `
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center">
                    <i class="${iconClass} fa-2x mr-3" aria-hidden="true"></i>
                    <div class="text-left">
                        <strong>${Utils.escapeHtml(mainAlert.headline_de)}</strong><br>
                        <small>${this.localAlerts.length > 1 ?
                            `+ ${this.localAlerts.length - 1} weitere Warnungen` :
                            'Klicken für Details'}</small>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </div>
        `;
    },

    hideAlertButton() {
        const button = document.getElementById('alertButton');
        const container = document.getElementById('alertButtonContainer');
        if (button) button.style.display = 'none';
        if (container) container.style.display = 'none';
    },

    renderAlerts() {
        const container = document.getElementById('alerts');
        const emptyState = document.getElementById('emptyState');

        if (!container) return;

        if (!this.allAlerts.length) {
            container.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        // Nach Typ gruppieren
        const grouped = this.groupByType(this.allAlerts);

        container.innerHTML = Object.entries(grouped)
            .sort((a, b) => {
                const maxA = Math.max(...a[1].map(item => this.config.severityLevels[item.severity]));
                const maxB = Math.max(...b[1].map(item => this.config.severityLevels[item.severity]));
                return maxB - maxA;
            })
            .map(([type, items]) => this.renderCategory(type, items))
            .join('');
    },

    groupByType(alerts) {
        const grouped = {};

        alerts.forEach(alert => {
            const type = this.getEventType(alert);
            if (!grouped[type]) grouped[type] = [];

            grouped[type].push({
                alert,
                severity: this.getSeverityClass(alert),
                height: this.extractHeight(alert)
            });
        });

        return grouped;
    },

    getEventType(alert) {
        const names = {
            'wind gusts': 'Windböen', 'gale-force gusts': 'Sturmböen',
            'frost': 'Frost', 'icy surfaces': 'Glätte',
            'thunderstorm': 'Gewitter', 'heavy rain': 'Starkregen',
            'snowfall': 'Schneefall', 'fog': 'Nebel'
        };
        return names[alert.event_en] || alert.event_de || 'Wetterwarnung';
    },

    renderCategory(type, items) {
        const severity = items[0].severity;
        const alertClass = this.config.severityClasses[severity].split(' ')[0];
        const iconClass = this.getEventIcon(items[0].alert);

        return `
            <div class="alert-category ${alertClass}">
                <div class="category-header"
                     onclick="AlertsModule.toggleCategory(this)"
                     role="button"
                     aria-expanded="false"
                     tabindex="0"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();AlertsModule.toggleCategory(this)}">
                    <span>
                        <i class="${iconClass}" aria-hidden="true"></i>
                        ${Utils.escapeHtml(type)}
                        <span class="category-badge">${items.length}</span>
                    </span>
                    <i class="fa-solid fa-chevron-down expand-icon" aria-hidden="true"></i>
                </div>
                <div class="category-content" style="display: none;" aria-hidden="true">
                    ${items.map(item => this.renderAlertItem(item)).join('')}
                </div>
            </div>
        `;
    },

    renderAlertItem(item) {
        const { alert, severity, height } = item;
        const alertClass = this.config.severityClasses[severity].split(' ')[0];
        const progressBar = this.generateProgressBar(alert);

        if (height) this.availableHeights.add(height);

        return `
            <article class="alert-item ${alertClass}"
                     data-severity="${severity}"
                     role="article"
                     aria-label="${Utils.escapeHtml(alert.headline_de)}">
                <div>
                    <strong>${Utils.escapeHtml(alert.headline_de)}</strong>
                    ${height ? `<span class="height-badge"><i class="fa-solid fa-mountain" aria-hidden="true"></i> > ${height}m</span>` : ''}
                </div>
                ${progressBar}
                <div class="mt-3">
                    <p class="mb-0">${Utils.escapeHtml(alert.description_de)}</p>
                </div>
            </article>
        `;
    },

    generateProgressBar(alert) {
        const now = new Date();
        const onset = new Date(alert.onset);
        const expires = new Date(alert.expires);

        if (onset > now) {
            const remaining = Utils.calculateTimeRemaining(onset);
            const timeStr = Utils.formatTimeRemaining(remaining);
            return `
                <div class="progress-container">
                    <span class="status-badge status-upcoming">Bevorstehend</span>
                    <div class="alert-time">
                        <i class="fa-solid fa-hourglass-start" aria-hidden="true"></i>
                        Beginnt in ${timeStr}<br>
                        <small>ab ${Utils.formatDate(onset)}</small>
                    </div>
                </div>
            `;
        }

        const totalDuration = expires - onset;
        const elapsed = now - onset;
        const progress = Math.min(100, Math.round((elapsed / totalDuration) * 100));
        const remaining = Utils.calculateTimeRemaining(expires);
        const timeStr = Utils.formatTimeRemaining(remaining);

        return `
            <div class="progress-container">
                <span class="status-badge status-active">Aktiv</span>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small>${Utils.formatDate(onset, { hour: '2-digit', minute: '2-digit' })}</small>
                    <small><i class="fa-solid fa-hourglass-end" aria-hidden="true"></i> noch ${timeStr}</small>
                    <small>${Utils.formatDate(expires, { hour: '2-digit', minute: '2-digit' })}</small>
                </div>
                <div class="progress mt-1" role="progressbar" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-danger" style="width: ${progress}%"></div>
                </div>
            </div>
        `;
    },

    toggleCategory(element) {
        const content = element.closest('.alert-category').querySelector('.category-content');
        const isExpanded = element.getAttribute('aria-expanded') === 'true';

        if (typeof $ !== 'undefined') {
            $(content).slideToggle(300);
        } else {
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        }

        element.classList.toggle('expanded');
        element.setAttribute('aria-expanded', !isExpanded);
        content.setAttribute('aria-hidden', isExpanded);
    },

    applyFilter(filter, heightValue = null) {
        this.currentFilter = filter;
        let filtered = this.allAlerts;

        switch (filter) {
            case 'active':
                filtered = this.allAlerts.filter(a => this.isActiveAlert(a));
                break;
            case 'upcoming':
                filtered = this.allAlerts.filter(a => !this.isActiveAlert(a));
                break;
            case 'severe':
                filtered = this.allAlerts.filter(a => this.isSevereAlert(a));
                break;
            case 'height':
                if (heightValue) {
                    filtered = this.allAlerts.filter(a => this.extractHeight(a) === heightValue);
                }
                break;
        }

        this.renderFilteredAlerts(filtered);
        this.updateFilterButtons(filter, heightValue);
    },

    renderFilteredAlerts(alerts) {
        const tempAlerts = this.allAlerts;
        this.allAlerts = alerts;
        this.renderAlerts();
        this.allAlerts = tempAlerts;
    },

    updateFilterButtons(filter, heightValue) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (filter === 'height' && heightValue &&
                btn.dataset.filter === 'height' &&
                parseInt(btn.dataset.height) === heightValue) {
                btn.classList.add('active');
            } else if (btn.dataset.filter === filter && filter !== 'height') {
                btn.classList.add('active');
            }
        });
    },

    updateCounts() {
        const counts = {
            all: this.allAlerts.length,
            active: this.allAlerts.filter(a => this.isActiveAlert(a)).length,
            upcoming: this.allAlerts.filter(a => !this.isActiveAlert(a)).length,
            severe: this.allAlerts.filter(a => this.isSevereAlert(a)).length
        };

        Object.entries(counts).forEach(([key, count]) => {
            const el = document.getElementById(`count-${key}`);
            if (el) el.textContent = count;
        });
    },

    bindEvents() {
        // Filter-Buttons
        document.addEventListener('click', (e) => {
            const filterBtn = e.target.closest('.filter-btn');
            if (filterBtn) {
                const filter = filterBtn.dataset.filter;
                const height = filterBtn.dataset.height ? parseInt(filterBtn.dataset.height) : null;
                this.applyFilter(filter, height);
            }
        });

        // Alert-Button
        const alertButton = document.getElementById('alertButton');
        if (alertButton) {
            alertButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.restoreLocalAlerts();
            });
        }
    },

    restoreLocalAlerts() {
        this.allAlerts = [...this.localAlerts];
        this.availableHeights = new Set();
        this.currentFilter = 'all';

        const modalTitle = document.querySelector('#alertsModal .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="fa-solid fa-location-dot" aria-hidden="true"></i> Warnungen: ${Utils.escapeHtml(this.localLocationName)}`;
        }

        this.renderAlerts();
        this.updateCounts();

        if (typeof $ !== 'undefined') {
            $('#alertsModal').modal('show');
        }
    }
};

// Globale Funktion für externe Aufrufe (z.B. aus iframe)
window.openExternalAlerts = function(alerts, locationName, excludedTypes = []) {
    let processed = alerts;
    if (excludedTypes.length) {
        processed = alerts.filter(a => !excludedTypes.includes(a.event_en));
    }

    AlertsModule.allAlerts = AlertsModule.sortAlerts(processed);
    AlertsModule.availableHeights = new Set();
    AlertsModule.currentFilter = 'all';

    const modalTitle = document.querySelector('#alertsModal .modal-title');
    if (modalTitle) {
        modalTitle.innerHTML = `<i class="fa-solid fa-map-marker-alt" aria-hidden="true"></i> Warnungen: ${Utils.escapeHtml(locationName)}`;
    }

    AlertsModule.renderAlerts();
    AlertsModule.updateCounts();

    if (typeof $ !== 'undefined') {
        $('#alertsModal').modal('show');
    }
};

window.toggleCategory = function(element) {
    AlertsModule.toggleCategory(element);
};

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Module initialisieren
    SearchModule.init();
    UIModule.init();

    // Alerts werden in der PHP-Datei mit Koordinaten initialisiert
    // AlertsModule.init(lat, lon, locationName);

    // Console-Info für Entwickler
    console.log('%c🌤️ Wettervorhersage geladen', 'color: #0056b3; font-size: 14px; font-weight: bold;');
});

// Export für Module
window.WeatherApp = {
    Utils,
    SearchModule,
    UIModule,
    AlertsModule,
    config: APP_CONFIG
};
