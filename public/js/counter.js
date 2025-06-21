(function() {
    function animate(el) {
        if (el.dataset.cdcCountupInitialised) return;
        el.dataset.cdcCountupInitialised = '1';

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

    function observeCounters(context){
        context.querySelectorAll('.cdc-counter').forEach(function(el){
            if (!el.dataset.cdcCountupInitialised){
                observer.observe(el);
            }
        });
    }

    var observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
            if (entry.isIntersecting){
                animate(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });

    var mutation = new MutationObserver(function(mutations){
        mutations.forEach(function(m){
            m.addedNodes.forEach(function(node){
                if (node.nodeType === 1){
                    if (node.classList && node.classList.contains('cdc-counter')){
                        observeCounters(node.parentNode || document);
                    }
                    var nested = node.querySelectorAll ? node.querySelectorAll('.cdc-counter') : [];
                    nested.forEach(function(el){ observeCounters(el.parentNode || document); });
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function(){
        observeCounters(document);
        mutation.observe(document.body, {childList: true, subtree: true});
    });
})();
