(function (document) {
	document.addEventListener(
		'DOMContentLoaded',
		function () {
			var form = document.getElementById( 'cdc-import-form' );
			if ( ! form) {
				return;
			}
			var file              = form.querySelector( 'input[type="file"]' );
			var submit            = form.querySelector( 'button[type="submit"]' );
			var overlay           = document.createElement( 'div' );
			overlay.id            = 'cdc-import-overlay';
			overlay.style.display = 'none';
			overlay.innerHTML     = '<span class="spinner is-active"></span><div class="progress w-75 mt-2" style="height:8px"><div class="progress-bar"></div></div><p></p>';
			document.body.appendChild( overlay );
			form.addEventListener(
				'submit',
				function (e) {
					e.preventDefault();
					if ( ! file.files.length) {
						return;
					}
					submit.disabled       = true;
					overlay.style.display = 'flex';
					var bar               = overlay.querySelector( '.progress-bar' );
					var msg               = overlay.querySelector( 'p' );
					var reader            = new FileReader();
					reader.onload         = function (ev) {
						var lines = ev.target.result.trim().split( /\r?\n/ );
						if (lines.length <= 1) {
							msg.textContent = cdcImport.done;
							bar.style.width = '100%';
							submit.disabled = false;
							return;
						}
						var header = lines[0].split( ',' );
						var index  = 0,total = lines.length - 1;
						function next(){
							if (index >= total) {
								bar.style.width = '100%';
								msg.textContent = cdcImport.done;
								setTimeout(
									function () {
										location.reload();},
									800
								);
								return;
							}
							var parts = lines[index + 1].split( ',' );
							var row   = {};
							header.forEach(
								function (h,i) {
									row[h] = parts[i] || '';}
							);
							var data = new FormData();
							data.append( 'action','cdc_import_row' );
							data.append( 'nonce',cdcImport.nonce );
							data.append( 'row',JSON.stringify( row ) );
							fetch( cdcImport.ajaxUrl,{method:'POST',credentials:'same-origin',body:data} )
								.finally(
									function () {
										index++;
										var pct         = Math.round( (index / total) * 100 );
										bar.style.width = pct + '%';
										msg.textContent = cdcImport.progress.replace( '%1',index ).replace( '%2',total );
										next();
									}
								);
						}
						msg.textContent = cdcImport.progress.replace( '%1',0 ).replace( '%2',total );
						bar.style.width = '0%';
						next();
					};
					reader.readAsText( file.files[0] );
				}
			);
		}
	);
})( document );
