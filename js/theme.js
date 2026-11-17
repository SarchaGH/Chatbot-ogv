/**
 * KATTA THEME – deles av ALLE sider (chat, innlogging, dokumentasjon).
 *
 * Lastes i <head> FØR css, slik at siden aldri "blinker" i feil farge.
 *
 * Lagret i nettleseren (localStorage, nøkkel katta_theme_v1):
 *   accent: 'red' | 'blue' | 'green' | 'purple' | 'orange'
 *   mode:   'system' (følger enheten) | 'light' | 'dark'
 *
 * API:
 *   KattaTheme.get()            -> { accent, mode, resolved }
 *   KattaTheme.set({ accent, mode })
 *   KattaTheme.cycleMode()      -> system -> light -> dark -> system
 *   KattaTheme.onChange(fn)     -> kalles hver gang temaet endres (også fra andre faner)
 */
(function () {
    'use strict';

    var KEY = 'katta_theme_v1';
    var ACCENTS = ['red', 'blue', 'green', 'purple', 'orange'];
    var MODES = ['system', 'light', 'dark'];
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var listeners = [];

    function load() {
        var t = {};
        try { t = JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { /* ingen lagret tema */ }
        return {
            accent: ACCENTS.indexOf(t.accent) > -1 ? t.accent : 'red',
            mode: MODES.indexOf(t.mode) > -1 ? t.mode : 'system'
        };
    }

    var state = load();

    function resolved() {
        if (state.mode !== 'system') return state.mode;
        return media && !media.matches ? 'light' : 'dark';
    }

    // Fargen på mobilens adresselinje følger temaet
    function syncMetaColor() {
        var meta = document.querySelector('meta[name="theme-color"]');
        if (!meta && document.head) {
            meta = document.createElement('meta');
            meta.name = 'theme-color';
            document.head.appendChild(meta);
        }
        var c = getComputedStyle(root).getPropertyValue('--bg-card').trim();
        if (meta && c) meta.content = c;
    }

    function apply() {
        root.dataset.accent = state.accent;
        root.dataset.mode = resolved();
        root.dataset.modePref = state.mode;
        syncMetaColor();
        var snapshot = KattaTheme.get();
        listeners.forEach(function (fn) { fn(snapshot); });
    }

    var KattaTheme = {
        ACCENTS: ACCENTS,
        MODES: MODES,
        get: function () {
            return { accent: state.accent, mode: state.mode, resolved: resolved() };
        },
        set: function (patch) {
            if (patch.accent && ACCENTS.indexOf(patch.accent) > -1) state.accent = patch.accent;
            if (patch.mode && MODES.indexOf(patch.mode) > -1) state.mode = patch.mode;
            try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) { /* privat modus */ }
            apply();
        },
        cycleMode: function () {
            this.set({ mode: MODES[(MODES.indexOf(state.mode) + 1) % MODES.length] });
        },
        onChange: function (fn) { listeners.push(fn); }
    };
    window.KattaTheme = KattaTheme;

    // Sett attributtene med en gang (før første tegning)
    root.dataset.accent = state.accent;
    root.dataset.mode = resolved();
    root.dataset.modePref = state.mode;

    // Enheten bytter mellom lys/mørk mens modus er "system"
    if (media) {
        var onMedia = function () { if (state.mode === 'system') apply(); };
        if (media.addEventListener) media.addEventListener('change', onMedia);
        else if (media.addListener) media.addListener(onMedia);
    }

    // Tema endret i en annen fane
    window.addEventListener('storage', function (e) {
        if (e.key === KEY) { state = load(); apply(); }
    });

    // Når CSS er lastet kan vi lese fargen til adresselinjen
    window.addEventListener('load', syncMetaColor);
})();
