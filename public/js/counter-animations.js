(function(){
    'use strict';

    const LOG_LEVELS = { quiet: 0, standard: 1, verbose: 2 };

    function getCurrentLogLevel() {
        if (window.CDC_LOGGER && window.CDC_LOGGER.logLevel) {
            return window.CDC_LOGGER.logLevel;
        }
        return 'standard';
    }

    function debugLog(message, data, level = 'standard'){
        const current = getCurrentLogLevel();
        if (LOG_LEVELS[current] >= LOG_LEVELS[level]) {
            if(data !== undefined){
                console.log('[CDC]', message, data);
            } else {
                console.log('[CDC]', message);
            }
        }
    }

    function getCountUpClass(){
        // Prefer the CountUp instance bundled with this plugin. Some themes
        // include an old global `CountUp` (v1) which does not support prefixes
        // or decimal places.
        if (window.countUp && window.countUp.CountUp) {
            debugLog('Using bundled CountUp', {version: window.countUp.CountUp.version});
            return window.countUp.CountUp;
        }
        if (window.CountUp && window.CountUp.version) {
            debugLog('Using global CountUp', {version: window.CountUp.version});
            return window.CountUp;
        }
        console.error('CountUp library not loaded or incompatible');
        logToServer('CountUp library missing or bad version');
        return null;
    }

    function logToServer(message){
        if (!window.CDC_LOGGER) return;
        try {
            const data = new FormData();
            data.append('action', 'cdc_log_js');
            data.append('nonce', window.CDC_LOGGER.nonce);
            data.append('message', message);
            navigator.sendBeacon(window.CDC_LOGGER.ajaxUrl, data);
        } catch (e) {
            console.warn('Logging failed', e);
        }
    }

    function init(el){
        const CountUpClass = getCountUpClass();
        if (!CountUpClass) return;

        const target  = parseFloat(el.dataset.target) || 0;
        let start     = parseFloat(el.dataset.start)  || 0;
        const growth  = parseFloat(el.dataset.growth) || 0;
        const prefix  = el.dataset.prefix || '';
        const decimals = 2;

        debugLog('Initialising counter', {target, start, growth, prefix});

        const counter = new CountUpClass(el, target, {
            startVal: start,
            decimalPlaces: decimals,
            prefix: prefix
        });

        if (counter.error) {
            logToServer(counter.error);
            return;
        }

        counter.start(() => {
            debugLog('Counter started', {target});
            if (growth !== 0) {
                setInterval(() => {
                    start += growth;
                    counter.update(start);
                    debugLog('Counter tick', {value: start}, 'verbose');
                }, 1000);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        debugLog('DOM loaded - launching counters');
        document.querySelectorAll('.cdc-counter').forEach(init);
    });
})();
