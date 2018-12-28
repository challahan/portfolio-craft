// Load Google Fonts

WebFontConfig = {
    google: { families: [ 'Roboto+Condensed:300,400,700', 'Roboto:300,300i,700' ] }
  };
  (function() {
    var wf = document.createElement('script');
    wf.src = '//ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
    wf.type = 'text/javascript';
    wf.async = 'true';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(wf, s);
  })();

//Async Load CSS //

(function(w){
  "use strict";
  var loadCSS = function( href, before, media ){
    var doc = w.document;
    var ss = doc.createElement( "link" );
    var ref;
    if( before ){
      ref = before;
    }
    else {
      var refs = ( doc.body || doc.getElementsByTagName( "head" )[ 0 ] ).childNodes;
      ref = refs[ refs.length - 1];
    }

    var sheets = doc.styleSheets;
    ss.rel = "stylesheet";
    ss.href = href;
    ss.media = "only x";

    ref.parentNode.insertBefore( ss, ( before ? ref : ref.nextSibling ) );
    var onloadcssdefined = function( cb ){
      var resolvedHref = ss.href;
      var i = sheets.length;
      while( i-- ){
        if( sheets[ i ].href === resolvedHref ){
          return cb();
        }
      }
      setTimeout(function() {
        onloadcssdefined( cb );
      });
    };
    ss.onloadcssdefined = onloadcssdefined;
    onloadcssdefined(function() {
      ss.media = media || "all";
    });
    return ss;
  };
  if( typeof module !== "undefined" ){
    module.exports = loadCSS;
  }
  else {
    w.loadCSS = loadCSS;
  }
}( typeof global !== "undefined" ? global : this ));
loadCSS( "/css/landing.css" );


  // Delighters Script https://github.com/Q42/delighters

  var Delighters=new function(){var t=this.dels=[],e={attribute:"data-delighter",classNames:["delighter","started","ended"],start:.75,end:.75,autoInit:!0};function s(){document.addEventListener("scroll",d);for(var s=document.querySelectorAll("["+e.attribute+"]"),a=0;a<s.length;a++){var n=s[a],i=n.getAttribute(e.attribute,2).split(";"),r={};r.start=e.start,r.end=e.end;for(var l=0;l<i.length;l++){var o=i[l].split(":"),c=o[0],u=isNaN(1*o[1])?o[1]:1*o[1];c&&(r[c]=void 0===u||u)}r.el=n,r.id=t.length,t.push(r),n.classList.add(e.classNames[0]),r.debug&&(n.style.outline="solid red 4px")}d()}function d(){for(var s=window.innerHeight,d=0;d<t.length;d++){var a=t[d],n=a.el.getBoundingClientRect(),i=n.top/s,r=n.bottom/s;a.debug&&(i>=0&&i<=1&&(a.startLine||(a.startLine=document.createElement("div"),document.body.appendChild(a.startLine),a.startLine.style="position:fixed;height:0;width:100%;border-bottom:dotted red 2px;top:"+100*a.start+"vh")),(r<a.end||i>1)&&a.startLine&&(a.startLine.parentNode.removeChild(a.startLine),delete a.startLine)),i<a.start&&!a.started?(a.started=!0,a.el.classList.add(e.classNames[1])):i>a.start&&a.started&&(a.started=!1,a.el.classList.remove(e.classNames[1])),r<a.end&&!a.ended?(a.ended=!0,a.el.classList.add(e.classNames[2])):r>a.end&&a.ended&&(a.ended=!1,a.el.classList.remove(e.classNames[2]))}}document.addEventListener("DOMContentLoaded",function(){e.autoInit&&s()}),this.init=s,this.config=function(t){for(var s in t)e[s]=t[s]}};
