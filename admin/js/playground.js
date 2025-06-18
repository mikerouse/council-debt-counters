document.addEventListener('DOMContentLoaded', function() {
    var council = document.getElementById('cdc-play-council');
    var type = document.getElementById('cdc-play-type');
    var code = document.getElementById('cdc-play-shortcode');
    var preview = document.getElementById('cdc-play-preview');

    function buildShortcode() {
        var c = council.value;
        var t = type.value;
        switch (t) {
            case 'debt':
                return '[council_counter id="' + c + '"]';
            case 'spending':
                return '[spending_counter id="' + c + '"]';
            case 'income':
                return '[revenue_counter id="' + c + '"]';
            case 'deficit':
                return '[deficit_counter id="' + c + '"]';
            case 'interest':
                return '[interest_counter id="' + c + '"]';
            default:
                return '[custom_counter id="' + c + '" type="' + t + '"]';
        }
    }

    function update() {
        var sc = buildShortcode();
        code.value = sc;
        fetch(cdcPlay.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=cdc_preview_shortcode&shortcode=' + encodeURIComponent(sc)
        }).then(function(r){ return r.text(); }).then(function(html){
            preview.innerHTML = html;
        });
    }

    if (council && type) {
        council.addEventListener('change', update);
        type.addEventListener('change', update);
        update();
    }
});
