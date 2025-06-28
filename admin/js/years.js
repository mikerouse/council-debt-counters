(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }
  ready(function(){
    var overlay=document.getElementById('cdc-years-overlay');
    function show(){ if(overlay) overlay.style.display='flex'; }
    function hide(){ if(overlay) overlay.style.display='none'; }
    var tbody=document.querySelector('#cdc-years-table tbody');
    var addBtn=document.getElementById('cdc-add-year-btn');
    var newInput=document.getElementById('cdc-new-year');
    function addRow(year){
      var tr=document.createElement('tr');
      tr.innerHTML='<td><input type="text" class="cdc-year-input form-control" data-original="'+year+'" value="'+year+'"></td>'+
                   '<td><input type="radio" name="cdc_default_year" value="'+year+'"></td>'+
                   '<td><button type="button" class="button cdc-delete-year">Delete</button></td>';
      tbody.appendChild(tr);
    }
    if(addBtn){
      addBtn.addEventListener('click',function(){
        var y=newInput.value.trim();
        if(!/^\d{4}\/\d{2}$/.test(y)) return;
        show();
        var d=new FormData();
        d.append('action','cdc_add_year');
        d.append('nonce',cdcYears.nonce);
        d.append('year',y);
        fetch(cdcYears.ajaxUrl,{method:'POST',credentials:'same-origin',body:d})
          .then(r=>r.json())
          .then(res=>{ if(res&&res.success){ addRow(y); newInput.value=''; } })
          .finally(hide);
      });
    }
    if(tbody){
      tbody.addEventListener('change',function(e){
        if(e.target.classList.contains('cdc-year-input')){
          var old=e.target.getAttribute('data-original');
          var val=e.target.value.trim();
          if(!/^\d{4}\/\d{2}$/.test(val)) { e.target.value=old; return; }
          show();
          var d=new FormData();
          d.append('action','cdc_update_year');
          d.append('nonce',cdcYears.nonce);
          d.append('old',old);
          d.append('new',val);
          fetch(cdcYears.ajaxUrl,{method:'POST',credentials:'same-origin',body:d})
            .then(r=>r.json())
            .then(res=>{ if(res&&res.success){ e.target.setAttribute('data-original',val); } else { e.target.value=old; } })
            .finally(hide);
        }
        if(e.target.name==='cdc_default_year'){
          var val=e.target.value;
          show();
          var d=new FormData();
          d.append('action','cdc_set_default_year');
          d.append('nonce',cdcYears.nonce);
          d.append('year',val);
          fetch(cdcYears.ajaxUrl,{method:'POST',credentials:'same-origin',body:d})
            .finally(hide);
        }
      });
      tbody.addEventListener('click',function(e){
        if(e.target.classList.contains('cdc-delete-year')){
          if(!confirm(cdcYears.deleteConfirm)) return;
          var row=e.target.closest('tr');
          var val=row.querySelector('.cdc-year-input').value;
          show();
          var d=new FormData();
          d.append('action','cdc_delete_year');
          d.append('nonce',cdcYears.nonce);
          d.append('year',val);
          fetch(cdcYears.ajaxUrl,{method:'POST',credentials:'same-origin',body:d})
            .then(r=>r.json())
            .then(res=>{ if(res&&res.success){ row.remove(); } })
            .finally(hide);
        }
      });
    }
  });
})();
