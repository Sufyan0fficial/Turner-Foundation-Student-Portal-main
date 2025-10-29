/* Turner Foundation Student Dashboard (ES5 only) */
(function(){
  function byId(id){ return (window.document||document).getElementById(id); }
  function qsa(sel, root){ return (root||document).querySelectorAll(sel); }

  function postFormData(fd){
    return fetch(tfspCfg.ajax_url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }
    }).then(function(resp){
      if(!resp.ok){
        return resp.text().then(function(t){ throw new Error('HTTP '+resp.status+': '+t); });
      }
      return resp.json();
    });
  }

  function wireUpload(){
    var fileInput = byId('documentFile');
    var chooseBtn = byId('chooseFileBtn');
    var uploadBtn = byId('uploadBtn');
    var form = byId('documentUploadForm');
    var msg = byId('uploadMessage');

    if (chooseBtn){
      chooseBtn.addEventListener('click', function(e){
        e.preventDefault(); if(fileInput){ fileInput.click(); }
      });
    }
    if (fileInput){
      fileInput.addEventListener('change', function(){
        var fn = (this.files && this.files[0]) ? this.files[0].name : '';
        if (fn){ if (chooseBtn) chooseBtn.textContent = fn; if(uploadBtn) uploadBtn.style.display='inline-block'; }
        else { if (chooseBtn) chooseBtn.textContent = 'Choose File'; if(uploadBtn) uploadBtn.style.display='none'; }
      });
    }
    if (form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!msg) msg = byId('uploadMessage');
        var typeSel = form.querySelector('select[name="document_type"]');
        if (!typeSel || !typeSel.value){
          if (msg){ msg.style.display='block'; msg.className='ca-message error'; msg.textContent='Please select a document type before uploading.'; }
          return;
        }
        if (!fileInput || !fileInput.files || !fileInput.files.length){
          if (msg){ msg.style.display='block'; msg.className='ca-message error'; msg.textContent='Please choose a file to upload.'; }
          return;
        }

        var submitBtn = form.querySelector('button[type="submit"]');
        var original = submitBtn ? submitBtn.textContent : '';
        if (submitBtn){ submitBtn.disabled = true; submitBtn.textContent='Uploading...'; }

        var fd = new FormData(form);
        fd.append('action','tfsp_upload_document');
        fd.append('nonce', tfspCfg.nonce);
        postFormData(fd)
          .then(function(data){
            if (msg){ msg.style.display='block'; msg.className = data.success ? 'ca-message success' : 'ca-message error'; msg.textContent = data.success ? (data.data||'Uploaded') : (data.data||'Upload failed.'); }
            if (data.success){ setTimeout(function(){ location.reload(); }, 1200); }
          })
          .catch(function(err){ if (msg){ msg.style.display='block'; msg.className='ca-message error'; msg.textContent='Upload failed. '+(err&&err.message?err.message:''); } })
          .finally(function(){ if (submitBtn){ submitBtn.disabled=false; submitBtn.textContent=original; } });
      });
    }
  }

  function wireChecklist(){
    var btns = qsa('.ca-status-btn');
    if (!btns || !btns.length) return;
    for (var i=0;i<btns.length;i++){
      btns[i].addEventListener('click', function(e){
        e.preventDefault();
        var card = this.closest ? this.closest('.ca-checklist-card') : null;
        if (!card) return;
        var itemKey = card.getAttribute('data-item');
        var status = (this.textContent||'').indexOf('Complete') !== -1 ? 'Yes' : 'No';
        var fd = new FormData();
        fd.append('action','tfsp_update_checklist_status');
        fd.append('nonce', tfspCfg.checklist_nonce);
        fd.append('item_key', itemKey);
        fd.append('status', status);
        postFormData(fd).then(function(data){ if (data.success){ setTimeout(function(){ location.reload(); }, 400); } else { alert('Error: '+(data.data||'Failed')); } });
      });
    }
  }

  // Prevent undefined error on themes calling this
  window.showNotifications = function(){
    var fd = new URLSearchParams();
    fd.append('action','tfsp_get_notifications');
    fd.append('nonce', tfspCfg.nonce);
    fetch(tfspCfg.ajax_url, { method:'POST', body: fd, headers:{'Content-Type':'application/x-www-form-urlencoded'} })
      .then(function(r){ return r.json(); })
      .then(function(data){
        var msg = 'Notifications:\n';
        if (data && data.success && data.data && data.data.length){
          for (var i=0;i<data.data.length;i++){ msg += '- '+data.data[i]+'\n'; }
        } else { msg += 'No new notifications'; }
        alert(msg);
      });
  };

  document.addEventListener('DOMContentLoaded', function(){
    wireUpload();
    wireChecklist();
  });
})();

