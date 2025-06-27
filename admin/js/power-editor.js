(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

  var pending=0;
  var spinner;

  function showSpinner(){
    pending++;
    if(spinner) spinner.classList.remove('d-none');
  }

  function hideSpinner(){
    pending=Math.max(0,pending-1);
    if(pending===0 && spinner){ spinner.classList.add('d-none'); }
  }

  function save(input){
    var row = input.closest('tr');
    var cid = row.getAttribute('data-cid');
    var field = input.getAttribute('data-field');
    var year = document.getElementById('cdc-pe-year').value;
    var d = new FormData();
    d.append('action','cdc_power_save');
    d.append('cid', cid);
    d.append('field', field);
    d.append('value', input.value);
    d.append('year', year);
    d.append('nonce', cdcPower.nonce);
    showSpinner();
    fetch(cdcPower.ajaxUrl,{method:'POST',credentials:'same-origin',body:d})
      .then(function(r){return r.json();})
      .then(function(res){
        if(res && res.success){
          input.classList.add('bg-success','text-white');
          setTimeout(function(){input.classList.remove('bg-success','text-white');},1000);
        }else{
          input.classList.add('bg-danger','text-white');
        }
      })
      .finally(hideSpinner);
  }

  function filter(){
    var term = document.getElementById('cdc-pe-search').value.toLowerCase();
    document.querySelectorAll('#cdc-power-table tbody tr').forEach(function(tr){
      var name = tr.children[1].textContent.toLowerCase();
      tr.style.display = name.indexOf(term) !== -1 ? '' : 'none';
    });
  }

  ready(function(){
    spinner=document.getElementById('cdc-pe-spinner');
    document.querySelectorAll('.cdc-pe-input').forEach(function(el){
      el.addEventListener('change', function(){
        save(el);
      });
      el.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
          e.preventDefault();
          save(el);
          var next = el.closest('td').nextElementSibling;
          if (next) {
            next = next.querySelector('.cdc-pe-input');
          }
          if (! next) {
            var nr = el.closest('tr').nextElementSibling;
            if (nr) {
              next = nr.querySelector('.cdc-pe-input');
            }
          }
          if (next) {
            next.focus();
          }
        }
      });
    });
    var search=document.getElementById('cdc-pe-search');
    if(search){ search.addEventListener('input', filter); }
  });
})();
