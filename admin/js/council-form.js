(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var totalField = document.querySelector('input[name="acf[field_cdc_total_external_borrowing]"]');
        if (!totalField) return;

        var output = document.createElement('div');
        output.id = 'cdc-debt-rates';
        output.className = 'mt-2 alert alert-info';
        totalField.parentElement.appendChild(output);

        function updateRates() {
            var val = parseFloat(totalField.value);
            if (isNaN(val)) val = 0;
            var perDay = val / 365;
            var perHour = perDay / 24;
            var perSecond = perHour / 3600;
            output.textContent = 'Debt per day: £' + perDay.toFixed(2) + ', per hour: £' + perHour.toFixed(2) + ', per second: £' + perSecond.toFixed(2);
        }

        totalField.addEventListener('input', updateRates);
        updateRates();
    });
})();
