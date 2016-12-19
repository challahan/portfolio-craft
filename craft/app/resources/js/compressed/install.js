!function(a){Craft.Installer=Garnish.Base.extend({$bg:null,$screens:null,$currentScreen:null,$accountSubmitBtn:null,$siteSubmitBtn:null,loading:!1,/**
	* Constructor
	*/
init:function(){this.$bg=a("#bg"),this.$screens=Garnish.$bod.children(".modal"),this.addListener(a("#beginbtn"),"activate","showAccountScreen")},showAccountScreen:function(b){this.showScreen(1,a.proxy(function(){a("#beginbtn").remove(),this.$accountSubmitBtn=a("#accountsubmit"),this.addListener(this.$accountSubmitBtn,"activate","validateAccount"),this.addListener(a("#accountform"),"submit","validateAccount")},this))},validateAccount:function(b){b.preventDefault();var c=["username","email","password"];this.validate("account",c,a.proxy(this,"showSiteScreen"))},showSiteScreen:function(){this.showScreen(2,a.proxy(function(){this.$siteSubmitBtn=a("#sitesubmit"),this.addListener(this.$siteSubmitBtn,"activate","validateSite"),this.addListener(a("#siteform"),"submit","validateSite")},this))},validateSite:function(b){b.preventDefault();var c=["siteName","siteUrl","siteLanguage"];this.validate("site",c,a.proxy(this,"showInstallScreen"))},showInstallScreen:function(){this.showScreen(3,a.proxy(function(){for(var b=["username","email","password","siteName","siteUrl","siteLanguage"],c={},d=0;d<b.length;d++){var e=b[d],f=a("#"+e);c[e]=Garnish.getInputPostVal(f)}Craft.postActionRequest("install/install",c,a.proxy(this,"allDone"),{complete:a.noop})},this))},allDone:function(b,c){if("success"==c&&b.success){this.$currentScreen.find("h1:first").text(Craft.t("app","All done!"));var d=a('<div class="buttons"/>'),e=a('<div class="btn big submit">'+Craft.t("app","Go to Craft CMS")+"</div>").appendTo(d);a("#spinner").replaceWith(d),this.addListener(e,"click",function(){this.showScreen(30,null,1e3),setTimeout(function(){window.location.href=Craft.getUrl("dashboard")},Craft.Installer.duration)})}else this.$currentScreen.find("h1:first").text("Oops.")},showScreen:function(b,c,d){d||(d=Craft.Installer.duration),
// Slide the BG
this.$bg.velocity({left:"-"+5*b+"%"},d);
// Slide out the old screen
var e=Garnish.$win.width(),f=Math.floor(e/2);this.$currentScreen&&this.$currentScreen.css("left",f).velocity({left:-400},Craft.Installer.duration),
// Slide in the new screen
this.$currentScreen=a(this.$screens[b-1]).css({display:"block",left:e+400}).velocity({left:f},Craft.Installer.duration,a.proxy(function(){
// Relax the screen
this.$currentScreen.css("left","50%"),
// Give focus to the first input
this.focusFirstInput(),
// Call the callback
c()},this))},validate:function(b,c,d){
// Prevent double-clicks
if(!this.loading){this.loading=!0,
// Clear any previous error lists
a("#"+b+"form").find(".errors").remove();var e=this["$"+b+"SubmitBtn"];e.addClass("sel loading");for(var f="install/validate-"+b,g={},h=0;h<c.length;h++){var i=c[h],j=a("#"+i);g[i]=Garnish.getInputPostVal(j)}Craft.postActionRequest(f,g,a.proxy(function(b,c){if(this.loading=!1,e.removeClass("sel loading"),"success"==c)if(b.validates)d();else{for(var f in b.errors)if(b.errors.hasOwnProperty(f)){for(var g=b.errors[f],h=a("#"+f),i=h.closest(".field"),j=a('<ul class="errors"/>').appendTo(i),k=0;k<g.length;k++){var l=g[k];a("<li>"+l+"</li>").appendTo(j)}h.is(":focus")||(h.addClass("error"),a.proxy(function(a){this.addListener(a,"focus",function(){a.removeClass("error"),this.removeListener(a,"focus")})},this)(h))}Garnish.shake(this.$currentScreen)}},this))}},focusFirstInput:function(){setTimeout(a.proxy(function(){this.$currentScreen.find("input:first").focus()},this),Craft.Installer.duration)}},{duration:300}),Garnish.$win.on("load",function(){Craft.installer=new Craft.Installer})}(jQuery);
//# sourceMappingURL=install.js.map