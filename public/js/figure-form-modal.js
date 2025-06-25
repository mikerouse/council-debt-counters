(function(doc){
  doc.addEventListener('DOMContentLoaded',function(){
    doc.querySelectorAll('.cdc-open-fig-modal').forEach(function(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();
        var modalEl = doc.getElementById('cdc-fig-modal');
        if(modalEl){
          var m = new bootstrap.Modal(modalEl);
          m.show();
        }
      });
    });
  });
})(document);
