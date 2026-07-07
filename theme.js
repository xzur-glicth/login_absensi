(function(){
  const storageKey = 'site-theme';
  const html = document.documentElement;

  function applyTheme(theme){
    if(theme === 'light' || theme === 'dark'){
      html.setAttribute('data-theme', theme);
      try{ localStorage.setItem(storageKey, theme); }catch(e){}
    } else {
      html.removeAttribute('data-theme');
      try{ localStorage.removeItem(storageKey); }catch(e){}
    }
  }

  function getPreferred(){
    try{
      const saved = localStorage.getItem(storageKey);
      if(saved) return saved;
    }catch(e){}
    if(window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) return 'light';
    if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    return 'dark';
  }

  // initialize
  applyTheme(getPreferred());

  // expose toggle for buttons
  window.themeToggle = function(){
    const current = html.getAttribute('data-theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    const next = current === 'light' ? 'dark' : 'light';
    applyTheme(next);
  };

  // react to system changes
  if(window.matchMedia){
    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', e=>{
      const saved = (function(){ try{return localStorage.getItem(storageKey);}catch(e){return null;} })();
      if(!saved) applyTheme(e.matches ? 'light' : 'dark');
    });
  }
})();
