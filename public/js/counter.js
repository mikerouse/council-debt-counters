(function() {
    function animate(el) {
        var target = parseFloat(el.dataset.target);
        if (isNaN(target)) return;
        var duration = 2000;
        var step = Math.max(target / (duration / 50), 1);
        var current = 0;
        var int = setInterval(function() {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(int);
            }
            el.textContent = current.toLocaleString('en-GB', {
                style: 'currency',
                currency: 'GBP',
                maximumFractionDigits: 0
            });
        }, 50);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.cdc-counter').forEach(function(el) {
            animate(el);
        });
    });
})();
