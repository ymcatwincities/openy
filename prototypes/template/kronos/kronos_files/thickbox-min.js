var tb_pathToImage="static/core/images/tbimages/loadingAnimation.gif";var tbArray=new Array();
/*!!!!!!!!!!!!!!!! edit below this line at your own risk !!!!!!!!!!!!!!!!!!!!!!!*/
function doNothing(a){return false;}function initializeThickBox(){tb_init("a.thickbox, area.thickbox, input.thickbox");imgLoader=new Image();
imgLoader.src=tb_pathToImage;}function initializeThickBoxForAutomatedTests(){tb_initForAutomatedTests("a.thickbox, area.thickbox, input.thickbox");imgLoader=new Image();imgLoader.src=tb_pathToImage;}function tb_init(b){var d=$(b);for(var a=0;a<d.length;a++){var c=new Object();c.href=d[a].href;tbArray[d[a].id]=c;
d[a].href="javascript:void(doNothing('"+c.href+"'));";}d.click(function(){var f=this.title||this.name||null;var e=tbArray[this.id].href||this.alt;var h=this.rel||false;tb_show(f,e,h);this.blur();return false;});}function tb_initForAutomatedTests(a){$(a).click(function(){var c=this.title||this.name||null;
var b=this.href||this.alt;var d=this.rel||false;tb_show(c,b,d);this.blur();return false;});}function tb_show(l,c,j){try{if(typeof document.body.style.maxHeight==="undefined"){$("body","html").css({height:"100%",width:"100%"});$("html").css("overflow","hidden");if(document.getElementById("TB_HideSelect")===null){$("body").append("<iframe src='javascript:false;' id='TB_HideSelect'></iframe><div id='TB_overlay'></div><div id='TB_window'></div>");
$("#TB_overlay").click(tb_remove);}}else{if(document.getElementById("TB_overlay")===null){$("body").append("<div id='TB_overlay'></div><div id='TB_window'></div>");$("#TB_overlay").click(tb_remove);}}if(tb_detectMacXFF()){$("#TB_overlay").addClass("TB_overlayMacFFBGHack");}else{$("#TB_overlay").addClass("TB_overlayBG");
}if(l===null){l="";}$("body").append("<div id='TB_load'><img src='"+imgLoader.src+"' /></div>");$("#TB_load").show();var d;if(c.indexOf("?")!==-1){d=c.substr(0,c.indexOf("?"));}else{d=c;}var g=/\.jpg$|\.jpeg$|\.png$|\.gif$|\.bmp$/;var m=d.toLowerCase().match(g);if(m==".jpg"||m==".jpeg"||m==".png"||m==".gif"||m==".bmp"){TB_PrevCaption="";
TB_PrevURL="";TB_PrevHTML="";TB_NextCaption="";TB_NextURL="";TB_NextHTML="";TB_imageCount="";TB_FoundURL=false;if(j){TB_TempArray=$("a[@rel="+j+"]").get();for(TB_Counter=0;((TB_Counter<TB_TempArray.length)&&(TB_NextHTML===""));TB_Counter++){var h=TB_TempArray[TB_Counter].href.toLowerCase().match(g);if(!(TB_TempArray[TB_Counter].href==c)){if(TB_FoundURL){TB_NextCaption=TB_TempArray[TB_Counter].title;
TB_NextURL=TB_TempArray[TB_Counter].href;TB_NextHTML="<span id='TB_next'>&nbsp;&nbsp;<a href='#'>Next &gt;</a></span>";}else{TB_PrevCaption=TB_TempArray[TB_Counter].title;TB_PrevURL=TB_TempArray[TB_Counter].href;TB_PrevHTML="<span id='TB_prev'>&nbsp;&nbsp;<a href='#'>&lt; Prev</a></span>";}}else{TB_FoundURL=true;
TB_imageCount="Image "+(TB_Counter+1)+" of "+(TB_TempArray.length);}}}imgPreloader=new Image();imgPreloader.onload=function(){imgPreloader.onload=null;var p=tb_getPageSize();var n=p[0]-150;var s=p[1]-150;var o=imgPreloader.width;var e=imgPreloader.height;if(o>n){e=e*(n/o);o=n;if(e>s){o=o*(s/e);e=s;}}else{if(e>s){o=o*(s/e);
e=s;if(o>n){e=e*(n/o);o=n;}}}TB_WIDTH=o+30;TB_HEIGHT=e+60;$("#TB_window").append("<a href='' id='TB_ImageOff' title='Close'><img id='TB_Image' src='"+c+"' width='"+o+"' height='"+e+"' alt='"+l+"'/></a>"+"<div id='TB_caption'>"+l+"<div id='TB_secondLine'>"+TB_imageCount+TB_PrevHTML+TB_NextHTML+"</div></div><div id='TB_closeWindow'><a href='#' id='TB_closeWindowButton' title='Close'>close</a> or Esc Key</div>");
$("#TB_closeWindowButton").click(tb_remove);if(!(TB_PrevHTML==="")){function r(){if($(document).unbind("click",r)){$(document).unbind("click",r);}$("#TB_window").remove();$("body").append("<div id='TB_window'></div>");tb_show(TB_PrevCaption,TB_PrevURL,j);return false;}$("#TB_prev").click(r);}if(!(TB_NextHTML==="")){function q(){$("#TB_window").remove();
$("body").append("<div id='TB_window'></div>");tb_show(TB_NextCaption,TB_NextURL,j);return false;}$("#TB_next").click(q);}document.onkeydown=function(t){if(t==null){keycode=event.keyCode;}else{keycode=t.which;}if(keycode==27){tb_remove();}else{if(keycode==190){if(!(TB_NextHTML=="")){document.onkeydown="";
q();}}else{if(keycode==188){if(!(TB_PrevHTML=="")){document.onkeydown="";r();}}}}};tb_position();$("#TB_load").remove();$("#TB_ImageOff").click(tb_remove);$("#TB_window").css({display:"block"});};imgPreloader.src=c;}else{var b=c.replace(/^[^\?]+\??/,"");var f=tb_parseQuery(b);TB_WIDTH=(f["width"]*1)+30||630;
TB_HEIGHT=(f["height"]*1)+40||440;if(null!=document.documentElement){var a=document.documentElement.clientHeight;if(a>400){if(TB_HEIGHT>=a){TB_HEIGHT=a-10;ajaxContentH=TB_HEIGHT-45;}else{ajaxContentH=(TB_HEIGHT-45)+17;}}else{ajaxContentH=TB_HEIGHT-45;}}else{ajaxContentH=TB_HEIGHT-45;}ajaxContentW=TB_WIDTH-30;
if(c.indexOf("TB_iframe")!=-1){urlNoQuery=c.split("TB_");$("#TB_iframeContent").remove();$("#TB_overlay").unbind();if(c.indexOf("closeModalButton")==-1||f["closeModalButton"]=="true"){$("#TB_window").append("<div id='TB_title' ><div id='TB_ajaxWindowTitle'>"+l+"</div><div id='TB_closeAjaxWindow'><a href='#' id='TB_closeWindowButton' class='iconCancelThickbox icon-16 noline' alt='"+tbCloseLabel+"' title='"+tbCloseLabel+"'>&nbsp;</a></div></div>");
}var i=navigator.userAgent.match(/iPad/i)!=null;$("#TB_title, #TB_overlay").bind("touchmove",function(event){event.preventDefault();});if(i){$("#TB_window").append("<div id='TB_scroll' style='border: 0; height: "+(ajaxContentH)+"px; width: "+(ajaxContentW+29)+"px; overflow-x: scroll; -webkit-overflow-scrolling: touch;'>"+"  <iframe frameborder='0' hspace='0' src='"+urlNoQuery[0]+"' id='TB_iframeContent' name='TB_iframeContent"+Math.round(Math.random()*1000)+"' onload='tb_showIframe()' style='width:"+(ajaxContentW+29)+"px;height:"+(ajaxContentH)+"px;' > </iframe>"+"</div>");
}else{$("#TB_window").append("<iframe frameborder='0' hspace='0' src='"+urlNoQuery[0]+"' id='TB_iframeContent' name='TB_iframeContent"+Math.round(Math.random()*1000)+"' onload='tb_showIframe()' style='width:"+(ajaxContentW+29)+"px;height:"+(ajaxContentH)+"px;' > </iframe>");}}else{if($("#TB_window").css("display")!="block"){if(f["modal"]!="true"){$("#TB_window").append("<div id='TB_title'><div id='TB_ajaxWindowTitle'>"+l+"</div><div id='TB_closeAjaxWindow'><a href='#' id='TB_closeWindowButton'>close</a> or Esc Key</div></div><div id='TB_ajaxContent' style='width:"+ajaxContentW+"px;height:"+ajaxContentH+"px'></div>");
}else{$("#TB_overlay").unbind();$("#TB_window").append("<div id='TB_ajaxContent' class='TB_modal' style='width:"+ajaxContentW+"px;height:"+ajaxContentH+"px;'></div>");}}else{$("#TB_ajaxContent")[0].style.width=ajaxContentW+"px";$("#TB_ajaxContent")[0].style.height=ajaxContentH+"px";$("#TB_ajaxContent")[0].scrollTop=0;
$("#TB_ajaxWindowTitle").html(l);}}$("#TB_closeWindowButton").click(tb_remove);if(c.indexOf("TB_inline")!=-1){$("#TB_ajaxContent").append($("#"+f["inlineId"]).children());$("#TB_window").unload(function(){$("#"+f["inlineId"]).append($("#TB_ajaxContent").children());});tb_position();$("#TB_load").remove();
$("#TB_window").css({display:"block"});}else{if(c.indexOf("TB_iframe")!=-1){tb_position();if($.browser.safari){$("#TB_load").remove();$("#TB_window").css({display:"block"});}}else{$("#TB_ajaxContent").load(c+="&random="+(new Date().getTime()),function(){tb_position();$("#TB_load").remove();tb_init("#TB_ajaxContent a.thickbox");
$("#TB_window").css({display:"block"});});}}}if(!f["modal"]){document.onkeyup=function(n){if(n==null){keycode=event.keyCode;}else{keycode=n.which;}if(keycode==27){tb_remove();}};}}catch(k){}}function tb_showIframe(){$("#TB_load").remove();$("#TB_window").css({display:"block"});}function tb_remove(){$("#TB_imageOff").unbind("click");
$("#TB_closeWindowButton").unbind("click");$("#TB_window").fadeOut("fast",function(){$("#TB_window,#TB_overlay,#TB_HideSelect").trigger("unload").unbind().remove();});$("#TB_load").remove();if(typeof document.body.style.maxHeight=="undefined"){$("body","html").css({height:"auto",width:"auto"});$("html").css("overflow","");
}document.onkeydown="";document.onkeyup="";return false;}function tb_position(){$("#TB_window").css({marginLeft:"-"+parseInt((TB_WIDTH/2),10)+"px",width:TB_WIDTH+"px"});if(!(jQuery.browser&&jQuery.browser.msie&&jQuery.browser.version<7)){$("#TB_window").css({marginTop:"-"+parseInt((TB_HEIGHT/2),10)+"px"});
}var a=document.documentElement.clientHeight;if(TB_HEIGHT>=a){$("#TB_window").css({marginTop:"10px",top:"0px"});}}function tb_parseQuery(d){var e={};if(!d){return e;}var a=d.split(/[;&]/);for(var c=0;c<a.length;c++){var g=a[c].split("=");if(!g||g.length!=2){continue;}var b=unescape(g[0]);var f=unescape(g[1]);
f=f.replace(/\+/g," ");e[b]=f;}return e;}function tb_getPageSize(){var c=document.documentElement;var a=window.innerWidth||self.innerWidth||(c&&c.clientWidth)||document.body.clientWidth;var b=window.innerHeight||self.innerHeight||(c&&c.clientHeight)||document.body.clientHeight;arrayPageSize=[a,b];return arrayPageSize;
}function tb_detectMacXFF(){var a=navigator.userAgent.toLowerCase();if(a.indexOf("mac")!=-1&&a.indexOf("firefox")!=-1){return true;}}