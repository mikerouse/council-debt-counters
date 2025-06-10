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
            // Only add helper if field represents a monetary value
            var name = field.getAttribute('name') || '';
            var isBand = /acf\[field_cdc_band_[a-h]_props?\]/i.test(name);
            var isPopulation = name === 'acf[field_cdc_population]';
            var isMembers = name === 'acf[field_cdc_elected_members]';
            if (!isBand && !isPopulation && !isMembers) {
                addHelper(field);
            }
        });

        var extField = document.querySelector('input[name="acf[field_cdc_total_external_borrowing]"]'); // legacy
        var shortField = document.querySelector('input[name="acf[field_cdc_short_term_borrowing]"]');
        var longField = document.querySelector('input[name="acf[field_cdc_long_term_borrowing]"]');
        var leaseField = document.querySelector('input[name="acf[field_cdc_finance_lease_pfi_liabilities]"]');
        var manualField = document.querySelector('input[name="acf[field_cdc_manual_debt_entry]"]');
        var adjustmentsField = document.querySelector('input[name="acf[field_cdc_debt_adjustments]"]');
        var interestField = document.querySelector('input[name="acf[field_cdc_interest_paid]"]');
        var mrpField = document.querySelector('input[name="acf[field_cdc_mrp]"]');
        var pwlbField = document.querySelector('input[name="acf[field_cdc_pwlb_borrowing]"]');
        var cfrField = document.querySelector('input[name="acf[field_cdc_cfr]"]');
        var totalField = document.querySelector('input[name="acf[field_cdc_total_debt]"]');
        var ratesOutput = document.createElement('div');
        ratesOutput.id = 'cdc-debt-rates';
        ratesOutput.className = 'mt-2 alert alert-info';
        if (shortField) {
            shortField.parentElement.appendChild(ratesOutput);
        } else if (extField) {
            extField.parentElement.appendChild(ratesOutput);
        }

        var sidebar = document.createElement('div');
        sidebar.id = 'cdc-counter-sidebar';
        sidebar.className = 'card position-fixed end-0 top-0 m-3';
        sidebar.style.width = '18rem';
        sidebar.innerHTML = '<div class="card-body">' +
            '<h5 class="card-title">Live Counter</h5>' +
            '<div id="cdc-counter-display" class="h3" role="status" aria-live="polite">£0</div>' +
            '<p id="cdc-counter-rate" class="mb-0"></p>' +
            '</div>';
        document.body.appendChild(sidebar);
        var counterDisplay = sidebar.querySelector('#cdc-counter-display');
        var rateDisplay = sidebar.querySelector('#cdc-counter-rate');
        var baseTotal = 0;
        var startTime = Date.now();
        var growthPerSecond = 0;

        function tick() {
            var elapsed = (Date.now() - startTime) / 1000;
            var current = baseTotal + elapsed * growthPerSecond;
            counterDisplay.textContent = current.toLocaleString('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 2 });
        }
        setInterval(tick, 1000);

        function updateAll() {
            var shortVal = parseFloat(shortField ? shortField.value : 0) || 0;
            var longVal = parseFloat(longField ? longField.value : 0) || 0;
            var leaseVal = parseFloat(leaseField ? leaseField.value : 0) || 0;
            var manual = parseFloat(manualField ? manualField.value : 0) || 0;
            var adjustments = parseFloat(adjustmentsField ? adjustmentsField.value : 0) || 0;
            var interest = parseFloat(interestField ? interestField.value : 0) || 0;
            var mrp = parseFloat(mrpField ? mrpField.value : 0) || 0;
            // Total debt is short term + long term + lease/PFI + manual + adjustments
            var total = shortVal + longVal + leaseVal + manual + adjustments;
            if (totalField) {
                totalField.value = total.toFixed(2);
                totalField.dispatchEvent(new Event('input'));
            }
            var perDay = total / 365;
            var perHour = perDay / 24;
            var perSecond = perHour / 3600;
            ratesOutput.textContent = 'Debt per day: £' + perDay.toFixed(2) + ', per hour: £' + perHour.toFixed(2) + ', per second: £' + perSecond.toFixed(2);

            growthPerSecond = (interest - mrp) / (365 * 24 * 60 * 60);
            baseTotal = total;
            startTime = Date.now();
            rateDisplay.textContent = 'Growth per second: £' + growthPerSecond.toFixed(6);
        }

        if (shortField) shortField.addEventListener('input', updateAll);
        if (longField) longField.addEventListener('input', updateAll);
        if (leaseField) leaseField.addEventListener('input', updateAll);
        if (extField) extField.addEventListener('input', updateAll); // legacy
        if (interestField) interestField.addEventListener('input', updateAll);
        if (mrpField) mrpField.addEventListener('input', updateAll);
        if (pwlbField) pwlbField.addEventListener('input', updateAll);
        if (cfrField) cfrField.addEventListener('input', updateAll);
        updateAll();
        // Add explainer for calculation
        var explainer = document.createElement('div');
        explainer.className = 'alert alert-warning mt-2';
        explainer.innerHTML = 'Total debt = <strong>Current Liabilities + Long Term Liabilities + Finance Lease/PFI Liabilities + Adjustments + Manual Entry</strong>. The growth or shrinkage estimate uses interest from the last statement of accounts, but interest itself is not added to the debt figure.';
        sidebar.querySelector('.card-body').appendChild(explainer);
    });
})();
