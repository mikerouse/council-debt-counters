(function() {
    function formatCurrency(val) {
        if (isNaN(val)) return '';
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 1 }).format(val);
    }

    function addHelper(field) {
        var helper = document.createElement('div');
        helper.className = 'text-muted mt-1 cdc-helper';
        field.parentElement.appendChild(helper);
        function update() {
            var val = parseFloat(field.value);
            helper.textContent = val ? formatCurrency(val) : '';
        }
        field.addEventListener('input', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="number"]').forEach(function(field) {
            // Only add helper if not a council tax band field
            var name = field.getAttribute('name') || '';
            if (!/acf\[field_cdc_band_[a-h]_props?\]/i.test(name)) {
                addHelper(field);
            }
        });

        var extField = document.querySelector('input[name="acf[field_cdc_total_external_borrowing]"]');
        var interestField = document.querySelector('input[name="acf[field_cdc_interest_paid]"]');
        var totalField = document.querySelector('input[name="acf[field_cdc_total_debt]"]');
        var ratesOutput = document.createElement('div');
        ratesOutput.id = 'cdc-debt-rates';
        ratesOutput.className = 'mt-2 alert alert-info';
        if (extField) {
            extField.parentElement.appendChild(ratesOutput);
        }

        function updateAll() {
            var external = parseFloat(extField ? extField.value : 0) || 0;
            var interest = parseFloat(interestField ? interestField.value : 0) || 0;
            var total = external + interest;
            if (totalField) {
                totalField.value = total.toFixed(2);
                totalField.dispatchEvent(new Event('input'));
            }
            var perDay = external / 365;
            var perHour = perDay / 24;
            var perSecond = perHour / 3600;
            ratesOutput.textContent = 'Debt per day: £' + perDay.toFixed(2) + ', per hour: £' + perHour.toFixed(2) + ', per second: £' + perSecond.toFixed(2);
        }

        if (extField) extField.addEventListener('input', updateAll);
        if (interestField) interestField.addEventListener('input', updateAll);
        updateAll();
    });
})();
