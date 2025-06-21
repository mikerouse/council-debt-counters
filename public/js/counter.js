(function() {
    function animate(el) {
        if (el.dataset.cdcInitialised) return;
        el.dataset.cdcInitialised = '1';

        var target = parseFloat(el.dataset.target);
        if (isNaN(target)) return;
        var growth = parseFloat(el.dataset.growth) || 0;
        var start = parseFloat(el.dataset.start) || 0;
        var duration = 2000;
        var step = Math.max((target - start) / (duration / 50), 1);
        var current = start;
        var int = setInterval(function() {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(int);
                // If growth rate is set (positive or negative), start real-time update
                if (growth !== 0) {
                    setInterval(function() {
                        current += growth;
                        el.textContent = current.toLocaleString('en-GB', {
                            style: 'currency',
                            currency: 'GBP',
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }, 1000);
                }
            }
            el.textContent = current.toLocaleString('en-GB', {
                style: 'currency',
                currency: 'GBP',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }, 50);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.cdc-counter').forEach(function(el) {
            animate(el);
        });
    });
})();
