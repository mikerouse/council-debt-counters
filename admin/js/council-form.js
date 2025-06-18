(function() {
    function formatCurrency(val) {
        if (isNaN(val)) return '';
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 2 }).format(val);
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
        var form = document.querySelector('form[action*="cdc_save_council"]');
        if (!form) return; // only on edit/add page

        document.querySelectorAll('input[type="number"]').forEach(function(field) {
            // Only add helper if field represents a monetary value
            var meta = field.getAttribute('data-cdc-field') || '';
            var isBand = /^band_[a-h]_properties$/.test(meta);
            var isPopulation = meta === 'population';
            if (!isBand && !isPopulation) {
                addHelper(field);
            }
        });

        var shortField = document.querySelector('[data-cdc-field="current_liabilities"]');
        var longField = document.querySelector('[data-cdc-field="long_term_liabilities"]');
        var leaseField = document.querySelector('[data-cdc-field="finance_lease_pfi_liabilities"]');
        var manualField = document.querySelector('[data-cdc-field="manual_debt_entry"]');
        var adjustmentsField = document.querySelector('[data-cdc-field="debt_adjustments"]');
        var interestField = document.querySelector('[data-cdc-field="interest_paid_on_debt"]');
        var mrpField = document.querySelector('[data-cdc-field="minimum_revenue_provision"]');
        var totalField = document.querySelector('[data-cdc-field="total_debt"]');
        var ratesOutput = document.createElement('div');
        ratesOutput.id = 'cdc-debt-rates';
        ratesOutput.className = 'mt-2 alert alert-info';
        if (shortField) {
            shortField.parentElement.appendChild(ratesOutput);
        }

        var sidebar = document.createElement('div');
        sidebar.id = 'cdc-counter-sidebar';
        sidebar.className = 'card mb-3 ms-3';
        sidebar.style.width = '18rem';
        sidebar.innerHTML = '<div class="card-body">' +
            '<h5 class="card-title">Live Counter</h5>' +
            '<div id="cdc-counter-display" class="h3" role="status" aria-live="polite">£0</div>' +
            '<p id="cdc-counter-rate" class="mb-0"></p>' +
            '</div>';
        form.parentElement.appendChild(sidebar);
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
            // Total debt is current liabilities + long term liabilities + lease/PFI + manual + adjustments
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
        if (interestField) interestField.addEventListener('input', updateAll);
        if (mrpField) mrpField.addEventListener('input', updateAll);
        updateAll();
        // Add explainer for calculation
        var explainer = document.createElement('div');
        explainer.className = 'alert alert-warning mt-2';
        explainer.innerHTML = 'Total debt = <strong>Current Liabilities + Long Term Liabilities + Finance Lease/PFI Liabilities + Adjustments</strong>. The growth or shrinkage estimate uses interest from the last statement of accounts.';
        sidebar.querySelector('.card-body').appendChild(explainer);
    });
})();
