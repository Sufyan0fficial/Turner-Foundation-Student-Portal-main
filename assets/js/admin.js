jQuery(document).ready(function($) {
    // Admin functionality will be added here
    console.log('TFSP Admin loaded');
});
// TFSP Admin sanitization: remove broken glyphs from buttons and headings
(function(){
  function cleanText(s){
    if(!s) return s;
    // Remove non-ASCII control/mis-encoded chars
    s = s.replace(/[^\x20-\x7E]/g, '');
    // Drop common corrupted prefixes like dY... at the start of a label
    s = s.replace(/^dY[^A-Za-z0-9]+\s*/, '');
    // Collapse spaces
    return s.replace(/\s{2,}/g,' ').trim();
  }
  function sanitizeSelectors(){
    var selectors = [
      'h1','h2','h3','.button','.page-title-action','.tab-btn','.nav-link',
      '.section h2','.section h3','.wp-heading-inline','.tfsp-admin-dashboard h1',
      '.tfsp-admin-dashboard .nav-link','.stat-card .stat-label','.stat-number',
      '.resource-type','.btn-small','.tfsp-admin-btn'
    ];
    selectors.forEach(function(sel){
      document.querySelectorAll(sel).forEach(function(el){
        // Clean text nodes
        el.childNodes.forEach(function(n){
          if(n.nodeType === 3){ n.nodeValue = cleanText(n.nodeValue); }
        });
        // Also clean title attribute if any
        if(el.title){ el.title = cleanText(el.title); }
      });
    });
  }
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', sanitizeSelectors);
  } else {
    sanitizeSelectors();
  }
})();
