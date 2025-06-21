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
        var actionInput = document.querySelector('input[name="action"][value="cdc_save_council"]');
        if (!actionInput) return; // only on edit/add page
        var form = actionInput.closest('form');
        if (!form) return;

        function validateField(field){
            if(!field.required) return;
            if(field.value.trim()===''){ field.classList.add('is-invalid'); }
            else{ field.classList.remove('is-invalid'); }
        }

        form.querySelectorAll('[required]').forEach(function(f){
            validateField(f);
            f.addEventListener('input', function(){
                validateField(f); updateTabState();
            });
        });

        function updateTabState(){
            document.querySelectorAll('.tab-pane').forEach(function(tab){
                var link = document.querySelector('[data-bs-target="#'+tab.id+'"]');
                if(!link) return;
                if(tab.querySelector('.is-invalid')) link.classList.add('text-danger');
                else link.classList.remove('text-danger');
            });
        }
        updateTabState();

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
        var totalField = document.querySelector('[data-cdc-field="total_debt"]');
        var ratesOutput = document.createElement('div');
        ratesOutput.id = 'cdc-debt-rates';
        ratesOutput.className = 'mt-2 alert alert-info';
        if (shortField) {
            shortField.parentElement.appendChild(ratesOutput);
        }

        var growthPerSecond = 0;

        function updateAll() {
            var shortVal = parseFloat(shortField ? shortField.value : 0) || 0;
            var longVal = parseFloat(longField ? longField.value : 0) || 0;
            var leaseVal = parseFloat(leaseField ? leaseField.value : 0) || 0;
            var manual = parseFloat(manualField ? manualField.value : 0) || 0;
            var adjustments = parseFloat(adjustmentsField ? adjustmentsField.value : 0) || 0;
            var interest = parseFloat(interestField ? interestField.value : 0) || 0;
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

            growthPerSecond = interest / (365 * 24 * 60 * 60);
        }

        if (shortField) shortField.addEventListener('input', updateAll);
        if (longField) longField.addEventListener('input', updateAll);
        if (leaseField) leaseField.addEventListener('input', updateAll);
        if (interestField) interestField.addEventListener('input', updateAll);
        updateAll();

        form.addEventListener('submit', function(){
            var file = document.getElementById('cdc-soa');
            var url = form.querySelector('input[name="statement_of_accounts_url"]');
            if((file && file.files.length>0) || (url && url.value.trim()!=='')){
                var overlay=document.createElement('div');
                overlay.id='cdc-upload-overlay';
                overlay.innerHTML='<span class="spinner is-active"></span><p>Uploading…</p>';
                document.body.appendChild(overlay);
            }
        });
        document.querySelectorAll(".cdc-extract-ai").forEach(function(btn){
            btn.addEventListener("click", function(e){
                e.preventDefault();
                var docId = btn.value;
                var overlay = document.createElement("div");
                overlay.id = "cdc-ai-overlay";
                overlay.innerHTML = "<span class=\"spinner is-active\"></span><div class=\"progress w-75 mt-2\" style=\"height:8px\"><div class=\"progress-bar\" style=\"width:0%\"></div></div><p></p>";
                var p = overlay.querySelector("p");
                var bar = overlay.querySelector('.progress-bar');
                var maxTime = cdcAiMessages.timeout || 60;
                var elapsed = 0;
                var barTimer = setInterval(function(){
                    elapsed++;
                    var pct = Math.min(100, (elapsed / maxTime) * 100);
                    bar.style.width = pct + '%';
                    if (elapsed >= maxTime) clearInterval(barTimer);
                },1000);
                document.body.appendChild(overlay);

                var steps = cdcAiMessages.steps || [];
                var i = 0;
                p.textContent = steps[i] || '';
                var interval = setInterval(function(){
                    i++;
                    if (i < steps.length) {
                        p.textContent = steps[i];
                    }
                }, 1500);

                var data = new FormData();
                data.append("action","cdc_extract_figures");
                data.append("doc_id", docId);
                fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:data})
                    .then(function(r){return r.json();})
                    .then(function(res){
                        clearInterval(interval);
                        clearInterval(barTimer);
                        var msg = (res.data && res.data.message) || res.message;
                        var tokens = res.data && res.data.tokens ? res.data.tokens : 0;
                        if (tokens) {
                            msg += ' (' + tokens + ' tokens)';
                        }
                        p.textContent = msg || cdcAiMessages.error;
                        setTimeout(function(){location.reload();},1200);
                    })
                    .catch(function(){
                        clearInterval(interval);
                        clearInterval(barTimer);
                        p.textContent = cdcAiMessages.error;
                    });
            });
        });
    });
})();
