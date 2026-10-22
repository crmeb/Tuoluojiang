import{p as getDefaultExportFromCjs}from"./chunk-vendor.PvV0voEW.js";var mavonEditor$2={exports:{}},mavonEditor$1=mavonEditor$2.exports,hasRequiredMavonEditor;function requireMavonEditor(){return hasRequiredMavonEditor||(hasRequiredMavonEditor=1,(function(module,exports){(function(f,d){module.exports=d()})(mavonEditor$1,function(){return(function(f){function d(e){if(s[e])return s[e].exports;var r=s[e]={i:e,l:!1,exports:{}};return f[e].call(r.exports,r,r.exports,d),r.l=!0,r.exports}var s={};return d.m=f,d.c=s,d.i=function(e){return e},d.d=function(e,r,n){d.o(e,r)||Object.defineProperty(e,r,{configurable:!1,enumerable:!0,get:n})},d.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return d.d(r,"a",r),r},d.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},d.p="",d(d.s=68)})([function(f,d,s){function e(E){return Object.prototype.toString.call(E)}function r(E){return e(E)==="[object String]"}function n(E,q){return x.call(E,q)}function i(E){return Array.prototype.slice.call(arguments,1).forEach(function(q){if(q){if(typeof q!="object")throw new TypeError(q+"must be object");Object.keys(q).forEach(function(O){E[O]=q[O]})}}),E}function t(E,q,O){return[].concat(E.slice(0,q),O,E.slice(q+1))}function a(E){return!(E>=55296&&E<=57343)&&!(E>=64976&&E<=65007)&&(65535&E)!=65535&&(65535&E)!=65534&&!(E>=0&&E<=8)&&E!==11&&!(E>=14&&E<=31)&&!(E>=127&&E<=159)&&!(E>1114111)}function u(E){if(E>65535){E-=65536;var q=55296+(E>>10),O=56320+(1023&E);return String.fromCharCode(q,O)}return String.fromCharCode(E)}function c(E,q){var O=0;return n(T,q)?T[q]:q.charCodeAt(0)===35&&A.test(q)&&(O=q[1].toLowerCase()==="x"?parseInt(q.slice(2),16):parseInt(q.slice(1),10),a(O))?u(O):E}function o(E){return E.indexOf("\\")<0?E:E.replace(k,"$1")}function l(E){return E.indexOf("\\")<0&&E.indexOf("&")<0?E:E.replace(y,function(q,O,P){return O||c(q,P)})}function p(E){return S[E]}function _(E){return L.test(E)?E.replace(j,p):E}function h(E){return E.replace(M,"\\$&")}function b(E){switch(E){case 9:case 32:return!0}return!1}function m(E){if(E>=8192&&E<=8202)return!0;switch(E){case 9:case 10:case 11:case 12:case 13:case 32:case 160:case 5760:case 8239:case 8287:case 12288:return!0}return!1}function g(E){return F.test(E)}function w(E){switch(E){case 33:case 34:case 35:case 36:case 37:case 38:case 39:case 40:case 41:case 42:case 43:case 44:case 45:case 46:case 47:case 58:case 59:case 60:case 61:case 62:case 63:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 124:case 125:case 126:return!0;default:return!1}}function v(E){return E=E.trim().replace(/\s+/g," "),"ẞ".toLowerCase()==="Ṿ"&&(E=E.replace(/ẞ/g,"ß")),E.toLowerCase().toUpperCase()}var x=Object.prototype.hasOwnProperty,k=/\\([!"#$%&'()*+,\-.\/:;<=>?@[\\\]^_`{|}~])/g,C=/&([a-z#][a-z0-9]{1,31});/gi,y=new RegExp(k.source+"|"+C.source,"gi"),A=/^#((?:x[a-f0-9]{1,8}|[0-9]{1,8}))/i,T=s(52),L=/[&<>"]/,j=/[&<>"]/g,S={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"},M=/[.?*+^$[\]\\(){}|-]/g,F=s(34);d.lib={},d.lib.mdurl=s(56),d.lib.ucmicro=s(196),d.assign=i,d.isString=r,d.has=n,d.unescapeMd=o,d.unescapeAll=l,d.isValidEntityCode=a,d.fromCodePoint=u,d.escapeHtml=_,d.arrayReplaceAt=t,d.isSpace=b,d.isWhiteSpace=m,d.isMdAsciiPunct=w,d.isPunctChar=g,d.escapeRE=h,d.normalizeReference=v},function(f,d){var s=f.exports=typeof window<"u"&&window.Math==Math?window:typeof self<"u"&&self.Math==Math?self:Function("return this")();typeof __g=="number"&&(__g=s)},function(f,d){var s={}.hasOwnProperty;f.exports=function(e,r){return s.call(e,r)}},function(f,d,s){f.exports=!s(11)(function(){return Object.defineProperty({},"a",{get:function(){return 7}}).a!=7})},function(f,d,s){var e=s(5),r=s(13);f.exports=s(3)?function(n,i,t){return e.f(n,i,r(1,t))}:function(n,i,t){return n[i]=t,n}},function(f,d,s){var e=s(9),r=s(42),n=s(28),i=Object.defineProperty;d.f=s(3)?Object.defineProperty:function(t,a,u){if(e(t),a=n(a,!0),e(u),r)try{return i(t,a,u)}catch{}if("get"in u||"set"in u)throw TypeError("Accessors not supported!");return"value"in u&&(t[a]=u.value),t}},function(f,d,s){var e=s(88),r=s(19);f.exports=function(n){return e(r(n))}},function(f,d,s){var e=s(26)("wks"),r=s(14),n=s(1).Symbol,i=typeof n=="function";(f.exports=function(t){return e[t]||(e[t]=i&&n[t]||(i?n:r)("Symbol."+t))}).store=e},function(f,d){f.exports=function(s){return typeof s=="object"?s!==null:typeof s=="function"}},function(f,d,s){var e=s(8);f.exports=function(r){if(!e(r))throw TypeError(r+" is not an object!");return r}},function(f,d){var s=f.exports={version:"2.6.11"};typeof __e=="number"&&(__e=s)},function(f,d){f.exports=function(s){try{return!!s()}catch{return!0}}},function(f,d){f.exports=!0},function(f,d){f.exports=function(s,e){return{enumerable:!(1&s),configurable:!(2&s),writable:!(4&s),value:e}}},function(f,d){var s=0,e=Math.random();f.exports=function(r){return"Symbol(".concat(r===void 0?"":r,")_",(++s+e).toString(36))}},function(f,d){function s(r,n){var i=r[1]||"",t=r[3];if(!t)return i;if(n&&typeof btoa=="function"){var a=e(t);return[i].concat(t.sources.map(function(u){return"/*# sourceURL="+t.sourceRoot+u+" */"})).concat([a]).join(`
`)}return[i].join(`
`)}function e(r){return"/*# sourceMappingURL=data:application/json;charset=utf-8;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"}f.exports=function(r){var n=[];return n.toString=function(){return this.map(function(i){var t=s(i,r);return i[2]?"@media "+i[2]+"{"+t+"}":t}).join("")},n.i=function(i,t){typeof i=="string"&&(i=[[null,i,""]]);for(var a={},u=0;u<this.length;u++){var c=this[u][0];typeof c=="number"&&(a[c]=!0)}for(u=0;u<i.length;u++){var o=i[u];typeof o[0]=="number"&&a[o[0]]||(t&&!o[2]?o[2]=t:t&&(o[2]="("+o[2]+") and ("+t+")"),n.push(o))}},n}},function(f,d){f.exports=function(s,e,r,n,i){var t,a=s=s||{},u=typeof s.default;u!=="object"&&u!=="function"||(t=s,a=s.default);var c=typeof a=="function"?a.options:a;e&&(c.render=e.render,c.staticRenderFns=e.staticRenderFns),n&&(c._scopeId=n);var o;if(i?(o=function(_){_=_||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,_||typeof __VUE_SSR_CONTEXT__>"u"||(_=__VUE_SSR_CONTEXT__),r&&r.call(this,_),_&&_._registeredComponents&&_._registeredComponents.add(i)},c._ssrRegister=o):r&&(o=r),o){var l=c.functional,p=l?c.render:c.beforeCreate;l?c.render=function(_,h){return o.call(h),p(_,h)}:c.beforeCreate=p?[].concat(p,o):[o]}return{esModule:t,exports:a,options:c}}},function(f,d,s){function e(v){for(var x=0;x<v.length;x++){var k=v[x],C=c[k.id];if(C){C.refs++;for(var y=0;y<C.parts.length;y++)C.parts[y](k.parts[y]);for(;y<k.parts.length;y++)C.parts.push(n(k.parts[y]));C.parts.length>k.parts.length&&(C.parts.length=k.parts.length)}else{for(var A=[],y=0;y<k.parts.length;y++)A.push(n(k.parts[y]));c[k.id]={id:k.id,refs:1,parts:A}}}}function r(){var v=document.createElement("style");return v.type="text/css",o.appendChild(v),v}function n(v){var x,k,C=document.querySelector("style["+m+'~="'+v.id+'"]');if(C){if(_)return h;C.parentNode.removeChild(C)}if(g){var y=p++;C=l||(l=r()),x=i.bind(null,C,y,!1),k=i.bind(null,C,y,!0)}else C=r(),x=t.bind(null,C),k=function(){C.parentNode.removeChild(C)};return x(v),function(A){if(A){if(A.css===v.css&&A.media===v.media&&A.sourceMap===v.sourceMap)return;x(v=A)}else k()}}function i(v,x,k,C){var y=k?"":C.css;if(v.styleSheet)v.styleSheet.cssText=w(x,y);else{var A=document.createTextNode(y),T=v.childNodes;T[x]&&v.removeChild(T[x]),T.length?v.insertBefore(A,T[x]):v.appendChild(A)}}function t(v,x){var k=x.css,C=x.media,y=x.sourceMap;if(C&&v.setAttribute("media",C),b.ssrId&&v.setAttribute(m,x.id),y&&(k+=`
/*# sourceURL=`+y.sources[0]+" */",k+=`
/*# sourceMappingURL=data:application/json;base64,`+btoa(unescape(encodeURIComponent(JSON.stringify(y))))+" */"),v.styleSheet)v.styleSheet.cssText=k;else{for(;v.firstChild;)v.removeChild(v.firstChild);v.appendChild(document.createTextNode(k))}}var a=typeof document<"u";if(typeof DEBUG<"u"&&DEBUG&&!a)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var u=s(206),c={},o=a&&(document.head||document.getElementsByTagName("head")[0]),l=null,p=0,_=!1,h=function(){},b=null,m="data-vue-ssr-id",g=typeof navigator<"u"&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());f.exports=function(v,x,k,C){_=k,b=C||{};var y=u(v,x);return e(y),function(A){for(var T=[],L=0;L<y.length;L++){var j=y[L],S=c[j.id];S.refs--,T.push(S)}A?(y=u(v,A),e(y)):y=[];for(var L=0;L<T.length;L++){var S=T[L];if(S.refs===0){for(var M=0;M<S.parts.length;M++)S.parts[M]();delete c[S.id]}}}};var w=(function(){var v=[];return function(x,k){return v[x]=k,v.filter(Boolean).join(`
`)}})()},function(f,d,s){function e(u){return u&&u.__esModule?u:{default:u}}d.__esModule=!0;var r=s(79),n=e(r),i=s(78),t=e(i),a=typeof t.default=="function"&&typeof n.default=="symbol"?function(u){return typeof u}:function(u){return u&&typeof t.default=="function"&&u.constructor===t.default&&u!==t.default.prototype?"symbol":typeof u};d.default=typeof t.default=="function"&&a(n.default)==="symbol"?function(u){return u===void 0?"undefined":a(u)}:function(u){return u&&typeof t.default=="function"&&u.constructor===t.default&&u!==t.default.prototype?"symbol":u===void 0?"undefined":a(u)}},function(f,d){f.exports=function(s){if(s==null)throw TypeError("Can't call method on  "+s);return s}},function(f,d){f.exports="constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf".split(",")},function(f,d){f.exports={}},function(f,d,s){var e=s(47),r=s(20);f.exports=Object.keys||function(n){return e(n,r)}},function(f,d){d.f={}.propertyIsEnumerable},function(f,d,s){var e=s(5).f,r=s(2),n=s(7)("toStringTag");f.exports=function(i,t,a){i&&!r(i=a?i:i.prototype,n)&&e(i,n,{configurable:!0,value:t})}},function(f,d,s){var e=s(26)("keys"),r=s(14);f.exports=function(n){return e[n]||(e[n]=r(n))}},function(f,d,s){var e=s(10),r=s(1),n=r["__core-js_shared__"]||(r["__core-js_shared__"]={});(f.exports=function(i,t){return n[i]||(n[i]=t!==void 0?t:{})})("versions",[]).push({version:e.version,mode:s(12)?"pure":"global",copyright:"© 2019 Denis Pushkarev (zloirock.ru)"})},function(f,d){var s=Math.ceil,e=Math.floor;f.exports=function(r){return isNaN(r=+r)?0:(r>0?e:s)(r)}},function(f,d,s){var e=s(8);f.exports=function(r,n){if(!e(r))return r;var i,t;if(n&&typeof(i=r.toString)=="function"&&!e(t=i.call(r))||typeof(i=r.valueOf)=="function"&&!e(t=i.call(r))||!n&&typeof(i=r.toString)=="function"&&!e(t=i.call(r)))return t;throw TypeError("Can't convert object to primitive value")}},function(f,d,s){var e=s(1),r=s(10),n=s(12),i=s(30),t=s(5).f;f.exports=function(a){var u=r.Symbol||(r.Symbol=n?{}:e.Symbol||{});a.charAt(0)=="_"||a in u||t(u,a,{value:i.f(a)})}},function(f,d,s){d.f=s(7)},function(f,d,s){function e(t,a){return new n(a).process(t)}var r=s(50),n=s(111);d=f.exports=e,d.FilterCSS=n;for(var i in r)d[i]=r[i];typeof window<"u"&&(window.filterCSS=f.exports)},function(f,d,s){function e(){this.__rules__=[],this.__cache__=null}e.prototype.__find__=function(r){for(var n=0;n<this.__rules__.length;n++)if(this.__rules__[n].name===r)return n;return-1},e.prototype.__compile__=function(){var r=this,n=[""];r.__rules__.forEach(function(i){i.enabled&&i.alt.forEach(function(t){n.indexOf(t)<0&&n.push(t)})}),r.__cache__={},n.forEach(function(i){r.__cache__[i]=[],r.__rules__.forEach(function(t){t.enabled&&(i&&t.alt.indexOf(i)<0||r.__cache__[i].push(t.fn))})})},e.prototype.at=function(r,n,i){var t=this.__find__(r),a=i||{};if(t===-1)throw new Error("Parser rule not found: "+r);this.__rules__[t].fn=n,this.__rules__[t].alt=a.alt||[],this.__cache__=null},e.prototype.before=function(r,n,i,t){var a=this.__find__(r),u=t||{};if(a===-1)throw new Error("Parser rule not found: "+r);this.__rules__.splice(a,0,{name:n,enabled:!0,fn:i,alt:u.alt||[]}),this.__cache__=null},e.prototype.after=function(r,n,i,t){var a=this.__find__(r),u=t||{};if(a===-1)throw new Error("Parser rule not found: "+r);this.__rules__.splice(a+1,0,{name:n,enabled:!0,fn:i,alt:u.alt||[]}),this.__cache__=null},e.prototype.push=function(r,n,i){var t=i||{};this.__rules__.push({name:r,enabled:!0,fn:n,alt:t.alt||[]}),this.__cache__=null},e.prototype.enable=function(r,n){Array.isArray(r)||(r=[r]);var i=[];return r.forEach(function(t){var a=this.__find__(t);if(a<0){if(n)return;throw new Error("Rules manager: invalid rule name "+t)}this.__rules__[a].enabled=!0,i.push(t)},this),this.__cache__=null,i},e.prototype.enableOnly=function(r,n){Array.isArray(r)||(r=[r]),this.__rules__.forEach(function(i){i.enabled=!1}),this.enable(r,n)},e.prototype.disable=function(r,n){Array.isArray(r)||(r=[r]);var i=[];return r.forEach(function(t){var a=this.__find__(t);if(a<0){if(n)return;throw new Error("Rules manager: invalid rule name "+t)}this.__rules__[a].enabled=!1,i.push(t)},this),this.__cache__=null,i},e.prototype.getRules=function(r){return this.__cache__===null&&this.__compile__(),this.__cache__[r]||[]},f.exports=e},function(f,d,s){function e(r,n,i){this.type=r,this.tag=n,this.attrs=null,this.map=null,this.nesting=i,this.level=0,this.children=null,this.content="",this.markup="",this.info="",this.meta=null,this.block=!1,this.hidden=!1}e.prototype.attrIndex=function(r){var n,i,t;if(!this.attrs)return-1;for(n=this.attrs,i=0,t=n.length;i<t;i++)if(n[i][0]===r)return i;return-1},e.prototype.attrPush=function(r){this.attrs?this.attrs.push(r):this.attrs=[r]},e.prototype.attrSet=function(r,n){var i=this.attrIndex(r),t=[r,n];i<0?this.attrPush(t):this.attrs[i]=t},e.prototype.attrGet=function(r){var n=this.attrIndex(r),i=null;return n>=0&&(i=this.attrs[n][1]),i},e.prototype.attrJoin=function(r,n){var i=this.attrIndex(r);i<0?this.attrPush([r,n]):this.attrs[i][1]=this.attrs[i][1]+" "+n},f.exports=e},function(f,d){f.exports=/[!-#%-\*,-\/:;\?@\[-\]_\{\}\xA1\xA7\xAB\xB6\xB7\xBB\xBF\u037E\u0387\u055A-\u055F\u0589\u058A\u05BE\u05C0\u05C3\u05C6\u05F3\u05F4\u0609\u060A\u060C\u060D\u061B\u061E\u061F\u066A-\u066D\u06D4\u0700-\u070D\u07F7-\u07F9\u0830-\u083E\u085E\u0964\u0965\u0970\u09FD\u0A76\u0AF0\u0C84\u0DF4\u0E4F\u0E5A\u0E5B\u0F04-\u0F12\u0F14\u0F3A-\u0F3D\u0F85\u0FD0-\u0FD4\u0FD9\u0FDA\u104A-\u104F\u10FB\u1360-\u1368\u1400\u166D\u166E\u169B\u169C\u16EB-\u16ED\u1735\u1736\u17D4-\u17D6\u17D8-\u17DA\u1800-\u180A\u1944\u1945\u1A1E\u1A1F\u1AA0-\u1AA6\u1AA8-\u1AAD\u1B5A-\u1B60\u1BFC-\u1BFF\u1C3B-\u1C3F\u1C7E\u1C7F\u1CC0-\u1CC7\u1CD3\u2010-\u2027\u2030-\u2043\u2045-\u2051\u2053-\u205E\u207D\u207E\u208D\u208E\u2308-\u230B\u2329\u232A\u2768-\u2775\u27C5\u27C6\u27E6-\u27EF\u2983-\u2998\u29D8-\u29DB\u29FC\u29FD\u2CF9-\u2CFC\u2CFE\u2CFF\u2D70\u2E00-\u2E2E\u2E30-\u2E4E\u3001-\u3003\u3008-\u3011\u3014-\u301F\u3030\u303D\u30A0\u30FB\uA4FE\uA4FF\uA60D-\uA60F\uA673\uA67E\uA6F2-\uA6F7\uA874-\uA877\uA8CE\uA8CF\uA8F8-\uA8FA\uA8FC\uA92E\uA92F\uA95F\uA9C1-\uA9CD\uA9DE\uA9DF\uAA5C-\uAA5F\uAADE\uAADF\uAAF0\uAAF1\uABEB\uFD3E\uFD3F\uFE10-\uFE19\uFE30-\uFE52\uFE54-\uFE61\uFE63\uFE68\uFE6A\uFE6B\uFF01-\uFF03\uFF05-\uFF0A\uFF0C-\uFF0F\uFF1A\uFF1B\uFF1F\uFF20\uFF3B-\uFF3D\uFF3F\uFF5B\uFF5D\uFF5F-\uFF65]|\uD800[\uDD00-\uDD02\uDF9F\uDFD0]|\uD801\uDD6F|\uD802[\uDC57\uDD1F\uDD3F\uDE50-\uDE58\uDE7F\uDEF0-\uDEF6\uDF39-\uDF3F\uDF99-\uDF9C]|\uD803[\uDF55-\uDF59]|\uD804[\uDC47-\uDC4D\uDCBB\uDCBC\uDCBE-\uDCC1\uDD40-\uDD43\uDD74\uDD75\uDDC5-\uDDC8\uDDCD\uDDDB\uDDDD-\uDDDF\uDE38-\uDE3D\uDEA9]|\uD805[\uDC4B-\uDC4F\uDC5B\uDC5D\uDCC6\uDDC1-\uDDD7\uDE41-\uDE43\uDE60-\uDE6C\uDF3C-\uDF3E]|\uD806[\uDC3B\uDE3F-\uDE46\uDE9A-\uDE9C\uDE9E-\uDEA2]|\uD807[\uDC41-\uDC45\uDC70\uDC71\uDEF7\uDEF8]|\uD809[\uDC70-\uDC74]|\uD81A[\uDE6E\uDE6F\uDEF5\uDF37-\uDF3B\uDF44]|\uD81B[\uDE97-\uDE9A]|\uD82F\uDC9F|\uD836[\uDE87-\uDE8B]|\uD83A[\uDD5E\uDD5F]/},function(f,d){f.exports={indexOf:function(s,e){var r,n;if(Array.prototype.indexOf)return s.indexOf(e);for(r=0,n=s.length;r<n;r++)if(s[r]===e)return r;return-1},forEach:function(s,e,r){var n,i;if(Array.prototype.forEach)return s.forEach(e,r);for(n=0,i=s.length;n<i;n++)e.call(r,s[n],n,s)},trim:function(s){return String.prototype.trim?s.trim():s.replace(/(^\s*)|(\s*$)/g,"")},spaceIndex:function(s){var e=/\s|\n|\t/,r=e.exec(s);return r?r.index:-1}}},function(f,d,s){function e(n){s(203)}var r=s(16)(s(65),s(200),e,"data-v-548e2160",null);r.options.__file="D:\\work\\songwang\\yuangongji\\mavonEditor\\src\\components\\md-toolbar-left.vue",r.esModule&&Object.keys(r.esModule).some(function(n){return n!=="default"&&n.substr(0,2)!=="__"})&&console.error("named exports are not supported in *.vue files."),r.options.functional&&console.error("[vue-loader] md-toolbar-left.vue: functional components are not supported with templates, they should use render functions."),f.exports=r.exports},function(f,d,s){var e=s(16)(s(66),s(198),null,null,null);e.options.__file="D:\\work\\songwang\\yuangongji\\mavonEditor\\src\\components\\md-toolbar-right.vue",e.esModule&&Object.keys(e.esModule).some(function(r){return r!=="default"&&r.substr(0,2)!=="__"})&&console.error("named exports are not supported in *.vue files."),e.options.functional&&console.error("[vue-loader] md-toolbar-right.vue: functional components are not supported with templates, they should use render functions."),f.exports=e.exports},function(f,d,s){function e(o,l,p){var _=c.prefix,h=c.subfix,b=p.selectionStart,m=p.selectionEnd,g=p.value,w=g.substring(0,b);w+=_+o+`
`,w+=l,w+=h,w+=g.substring(m,g.length),p.value=w,p.selectionStart=b+_.length+(o?0:1),p.selectionEnd=p.selectionStart+o.length+l.length}function r(o){var l=c.prefix,p=c.subfix,_=c.defaultLanguageText,h=o.selectionStart,b=o.selectionEnd,m=o.value,g=m.substring(h,b);if(m.substring(h-1,h)===`
`)l+=`
`;else if(p=`
`+p,l+_+p===m.substring(h-l.length,b+p.length)){var w=m.substring(0,h-l.length);return w+=m.substring(b+p.length,m.length),o.value=w,o.selectionStart=h-l.length,void(o.selectionEnd=o.selectionStart)}var v=m.substring(0,h-l.length);v+=g,v+=m.substring(b+p.length,m.length),o.value=v,o.selectionStart=h-l.length,o.selectionEnd=o.selectionStart+g.length}function n(o){return typeof o.selectionStart=="number"&&typeof o.selectionEnd=="number"}function i(o){var l=o.selectionStart,p=o.selectionEnd,_=o.value,h=c.prefix,b=c.subfix;return _.substring(l-1,l)===`
`?h+=`
`:b=`
`+b,_.substring(l-h.length,l)===h&&_.substring(p,p+b.length)===b}function t(o,l,p,_,h){return o!=="*"||l!=="*"||p.substring(_-2,_-1)!=="*"||p.substring(h+1,h+2)!=="*"}function a(o,l){typeof l!="function"&&(l=function(){});var p=document.querySelectorAll("script[src='"+o+"']");if(p.length>0)return p[0].addEventListener("load",function(){l()}),void l();var _=document.createElement("script"),h=document.getElementsByTagName("head")[0];_.type="text/javascript",_.charset="UTF-8",_.src=o,_.addEventListener?_.addEventListener("load",function(){l()},!1):_.attachEvent&&_.attachEvent("onreadystatechange",function(){window.event.srcElement.readyState==="loaded"&&l()}),h.appendChild(_)}function u(o,l,p){if(typeof l!="function"&&(l=function(){}),document.querySelectorAll("link[href='"+o+"']").length>0)return void l();if(p){var _=document.querySelectorAll("link#"+p);if(_.length)return void(_[0].href=o)}var h=document.createElement("link"),b=document.getElementsByTagName("head")[0];h.rel="stylesheet",h.href=o,p&&(h.id=p),h.addEventListener?h.addEventListener("load",function(){l()},!1):h.attachEvent&&h.attachEvent("onreadystatechange",function(){window.event.srcElement.readyState==="loaded"&&l()}),b.appendChild(h)}Object.defineProperty(d,"__esModule",{value:!0}),d.loadScript=a,d.loadLink=u;var c=(d.insertTextAtCaret=function(o,l,p){var _=l.prefix,h=l.subfix,b=l.str;if(l.type,o.focus(),n(o)){var m=o.selectionStart,g=o.selectionEnd,w=o.value;m===g?(o.value=w.substring(0,m)+_+b+h+w.substring(g,w.length),o.selectionStart=m+_.length,o.selectionEnd=m+(b.length+_.length)):w.substring(m-_.length,m)===_&&w.substring(g,g+h.length)===h&&t(_,h,w,m,g)?(o.value=w.substring(0,m-_.length)+w.substring(m,g)+w.substring(g+h.length,w.length),o.selectionStart=m-_.length,o.selectionEnd=g-_.length):(o.value=w.substring(0,m)+_+w.substring(m,g)+h+w.substring(g,w.length),o.selectionStart=m+_.length,o.selectionEnd=m+(g-m+_.length))}else alert("Error: Browser version is too low");p.d_value=o.value,o.focus()},{prefix:"```",subfix:"\n```\n",defaultLanguageText:"language"});d.insertCodeBlock=function(o){var l=o.getTextareaDom();if(!n(l))return void alert("Error: Browser version is too low");var p=c.defaultLanguageText,_=l.selectionStart,h=l.selectionEnd,b=l.value;if(_===h)e(p,"",l);else if(i(l))r(l);else{var m=b.substring(_,h);e("",m,l)}o.d_value=l.value,l.focus()},d.insertOl=function(o){var l=o.getTextareaDom();if(n(l)){var p=l.selectionStart,_=l.selectionEnd,h=l.value;if(p===_)l.value=h.substring(0,p)+"1. "+h.substring(_,h.length),l.selectionEnd=l.selectionStart=p+3;else{for(var b=p;b>0&&h.substring(b-1,b)!==`
`;)b--;for(var m=h.substring(b,_),g=m.split(`
`),w=0;w<g.length;w++)g[w]=w+1+". "+g[w];var v=g.join(`
`);l.value=h.substring(0,b)+v+h.substring(_,h.length),l.selectionStart=b,l.selectionEnd=_+v.length-m.length}}else alert("Error: Browser version is too low");o.d_value=l.value,l.focus()},d.removeLine=function(o){var l=o.getTextareaDom();if(n(l)){for(var p=l.selectionStart,_=l.selectionEnd,h=l.value,b=p;b>0&&h.substring(b-1,b)!==`
`;)b--;for(var m=_;m<h.length&&h.substring(m,m+1)!==`
`;)m++;m<h.length&&m++,l.value=h.substring(0,b)+h.substring(m,h.length),l.selectionEnd=l.selectionStart=b===0?0:b-1}else alert("Error: Browser version is too low");o.d_value=l.value,l.focus()},d.insertUl=function(o){var l=o.getTextareaDom();if(n(l)){var p=l.selectionStart,_=l.selectionEnd,h=l.value;if(p===_)l.value=h.substring(0,p)+"- "+h.substring(_,h.length),l.selectionEnd=l.selectionStart=p+2;else{for(var b=p;b>0&&h.substring(b-1,b)!==`
`;)b--;var m=h.substring(b,_),g=m.replace(/\n/g,`
- `);g="- "+g,l.value=h.substring(0,b)+g+h.substring(_,h.length),l.selectionStart=b,l.selectionEnd=_+g.length-m.length}}else alert("Error: Browser version is too low");o.d_value=l.value,l.focus()},d.insertTab=function(o,l){l=l?new Array(l).fill(" ").join(""):"	";var p=o.getTextareaDom();if(n(p)){var _=p.selectionStart,h=p.selectionEnd,b=p.value,m=b.substring(0,_).split(`
`).pop();if(m.match(/^\s*[0-9]+\.\s+\S*/)){var g=m.replace(/(\d+)/,1);p.value=b.substring(0,_-g.length)+l+g+b.substring(h,b.length)}else m.match(/^\s*-\s+\S*/)?p.value=b.substring(0,_-m.length)+l+m+b.substring(h,b.length):p.value=b.substring(0,_)+l+b.substring(h,b.length);p.selectionStart=p.selectionEnd=_+l.length}else alert("Error: Browser version is too low");o.d_value=p.value,p.focus()},d.unInsertTab=function(o,l){var p=new RegExp(l?"\\s{"+l+"}":"	");console.log("regTab:",p);var _=o.getTextareaDom();if(n(_)){var h=_.selectionStart,b=_.selectionEnd,m=_.value,g=m.substring(0,h).split(`
`).pop();g.search(p)>=0&&(_.value=m.substring(0,h-g.length)+g.replace(p,"")+m.substring(b,m.length),_.selectionStart=_.selectionEnd=h-(l||1))}else alert("Error: Browser version is too low");o.d_value=_.value,_.focus()},d.insertEnter=function(o,l){var p=o.getTextareaDom();if(n(p)){var _=p.selectionStart,h=p.selectionEnd,b=p.value,m=b.substring(0,_).split(`
`).pop(),g=m.match(/^\s*(?:[0-9]+\.|-)\s+\S+/);if(g){l.preventDefault();var w=g.shift().match(/^\s*(?:[0-9]+\.|-)\s/).shift();if(w.search(/-/)>=0)p.value=b.substring(0,_)+`
`+w+b.substring(h,b.length),p.selectionStart=p.selectionEnd=_+w.length+1;else{var v=w.replace(/(\d+)/,parseInt(w)+1);p.value=b.substring(0,_)+`
`+v+b.substring(h,b.length),p.selectionStart=p.selectionEnd=_+v.length+1}}else{var x=m.match(/^\s*(?:[0-9]+\.|-)\s+$/);if(x){l.preventDefault();var k=x.shift().length;p.value=b.substring(0,_-k)+`
`+b.substring(h,b.length),p.selectionStart=p.selectionEnd=_-k}}}else alert("Error: Browser version is too low");o.d_value=p.value,p.focus()},d.getNavigation=function(o,l){var p=void 0;p=o.$refs.navigationContent,p.innerHTML=o.d_render;var _=p.children;if(_.length)for(var h=0;h<_.length;h++)(function(b,m,g){/^H[1-6]{1}$/.exec(b.tagName)?b.onclick=function(){var w=o.$refs.vShowContent,v=o.$refs.vNoteEdit;o.s_subfield?o.s_preview_switch&&(v.scrollTop=w.children[m].offsetTop*(v.scrollHeight-v.offsetHeight)/(w.scrollHeight-w.offsetHeight)):o.s_preview_switch&&(w.scrollTop=w.children[m].offsetTop)}:b.style.display="none"})(_[h],h)},d.scrollLink=function(o,l){var p=o.srcElement?o.srcElement:o.target,_=p.scrollTop/(p.scrollHeight-p.offsetHeight);l.edit_scroll_height>=0&&p.scrollHeight!==l.edit_scroll_height&&p.scrollHeight-p.offsetHeight-p.scrollTop<=30&&(l.$refs.vNoteEdit.scrollTop=p.scrollHeight-p.offsetHeight,_=1),l.edit_scroll_height=p.scrollHeight,l.$refs.vShowContent.scrollHeight>l.$refs.vShowContent.offsetHeight&&(l.$refs.vShowContent.scrollTop=(l.$refs.vShowContent.scrollHeight-l.$refs.vShowContent.offsetHeight)*_)},d.fullscreenchange=function(o){o.$el.addEventListener("fullscreenchange",function(l){o.$toolbar_right_read_change_status()},!1),o.$el.addEventListener("mozfullscreenchange",function(l){o.$toolbar_right_read_change_status()},!1),o.$el.addEventListener("webkitfullscreenchange",function(l){o.$toolbar_right_read_change_status()},!1),o.$el.addEventListener("msfullscreenchange",function(l){o.$toolbar_right_read_change_status()},!1)},d.windowResize=function(o){function l(){o.$el.clientWidth>768?o.s_subfield=o.subfield:o.s_subfield=!1}l(),window.addEventListener("resize",l)},d.ImagePreviewListener=function(o){o.$refs.vShowContent.addEventListener("click",function(l){l=l||window.event;var p=l.srcElement?l.srcElement:l.target;p.tagName==="IMG"&&(o.imageClick!=null?o.imageClick(p):o.d_preview_imgsrc=p.src)})}},function(f,d){var s={}.toString;f.exports=function(e){return s.call(e).slice(8,-1)}},function(f,d,s){var e=s(8),r=s(1).document,n=e(r)&&e(r.createElement);f.exports=function(i){return n?r.createElement(i):{}}},function(f,d,s){var e=s(1),r=s(10),n=s(85),i=s(4),t=s(2),a=function(u,c,o){var l,p,_,h=u&a.F,b=u&a.G,m=u&a.S,g=u&a.P,w=u&a.B,v=u&a.W,x=b?r:r[c]||(r[c]={}),k=x.prototype,C=b?e:m?e[c]:(e[c]||{}).prototype;b&&(o=c);for(l in o)(p=!h&&C&&C[l]!==void 0)&&t(x,l)||(_=p?C[l]:o[l],x[l]=b&&typeof C[l]!="function"?o[l]:w&&p?n(_,e):v&&C[l]==_?(function(y){var A=function(T,L,j){if(this instanceof y){switch(arguments.length){case 0:return new y;case 1:return new y(T);case 2:return new y(T,L)}return new y(T,L,j)}return y.apply(this,arguments)};return A.prototype=y.prototype,A})(_):g&&typeof _=="function"?n(Function.call,_):_,g&&((x.virtual||(x.virtual={}))[l]=_,u&a.R&&k&&!k[l]&&i(k,l,_)))};a.F=1,a.G=2,a.S=4,a.P=8,a.B=16,a.W=32,a.U=64,a.R=128,f.exports=a},function(f,d,s){f.exports=!s(3)&&!s(11)(function(){return Object.defineProperty(s(40)("div"),"a",{get:function(){return 7}}).a!=7})},function(f,d,s){var e=s(12),r=s(41),n=s(48),i=s(4),t=s(21),a=s(90),u=s(24),c=s(96),o=s(7)("iterator"),l=!([].keys&&"next"in[].keys()),p=function(){return this};f.exports=function(_,h,b,m,g,w,v){a(b,h,m);var x,k,C,y=function(q){if(!l&&q in j)return j[q];switch(q){case"keys":case"values":return function(){return new b(this,q)}}return function(){return new b(this,q)}},A=h+" Iterator",T=g=="values",L=!1,j=_.prototype,S=j[o]||j["@@iterator"]||g&&j[g],M=S||y(g),F=g?T?y("entries"):M:void 0,E=h=="Array"&&j.entries||S;if(E&&(C=c(E.call(new _)))!==Object.prototype&&C.next&&(u(C,A,!0),e||typeof C[o]=="function"||i(C,o,p)),T&&S&&S.name!=="values"&&(L=!0,M=function(){return S.call(this)}),e&&!v||!l&&!L&&j[o]||i(j,o,M),t[h]=M,t[A]=p,g)if(x={values:T?M:y("values"),keys:w?M:y("keys"),entries:F},v)for(k in x)k in j||n(j,k,x[k]);else r(r.P+r.F*(l||L),h,x);return x}},function(f,d,s){var e=s(9),r=s(93),n=s(20),i=s(25)("IE_PROTO"),t=function(){},a=function(){var u,c=s(40)("iframe"),o=n.length;for(c.style.display="none",s(87).appendChild(c),c.src="javascript:",u=c.contentWindow.document,u.open(),u.write("<script>document.F=Object<\/script>"),u.close(),a=u.F;o--;)delete a.prototype[n[o]];return a()};f.exports=Object.create||function(u,c){var o;return u!==null?(t.prototype=e(u),o=new t,t.prototype=null,o[i]=u):o=a(),c===void 0?o:r(o,c)}},function(f,d,s){var e=s(47),r=s(20).concat("length","prototype");d.f=Object.getOwnPropertyNames||function(n){return e(n,r)}},function(f,d){d.f=Object.getOwnPropertySymbols},function(f,d,s){var e=s(2),r=s(6),n=s(84)(!1),i=s(25)("IE_PROTO");f.exports=function(t,a){var u,c=r(t),o=0,l=[];for(u in c)u!=i&&e(c,u)&&l.push(u);for(;a.length>o;)e(c,u=a[o++])&&(~n(l,u)||l.push(u));return l}},function(f,d,s){f.exports=s(4)},function(f,d,s){var e=s(19);f.exports=function(r){return Object(e(r))}},function(f,d){function s(){var t={};return t["align-content"]=!1,t["align-items"]=!1,t["align-self"]=!1,t["alignment-adjust"]=!1,t["alignment-baseline"]=!1,t.all=!1,t["anchor-point"]=!1,t.animation=!1,t["animation-delay"]=!1,t["animation-direction"]=!1,t["animation-duration"]=!1,t["animation-fill-mode"]=!1,t["animation-iteration-count"]=!1,t["animation-name"]=!1,t["animation-play-state"]=!1,t["animation-timing-function"]=!1,t.azimuth=!1,t["backface-visibility"]=!1,t.background=!0,t["background-attachment"]=!0,t["background-clip"]=!0,t["background-color"]=!0,t["background-image"]=!0,t["background-origin"]=!0,t["background-position"]=!0,t["background-repeat"]=!0,t["background-size"]=!0,t["baseline-shift"]=!1,t.binding=!1,t.bleed=!1,t["bookmark-label"]=!1,t["bookmark-level"]=!1,t["bookmark-state"]=!1,t.border=!0,t["border-bottom"]=!0,t["border-bottom-color"]=!0,t["border-bottom-left-radius"]=!0,t["border-bottom-right-radius"]=!0,t["border-bottom-style"]=!0,t["border-bottom-width"]=!0,t["border-collapse"]=!0,t["border-color"]=!0,t["border-image"]=!0,t["border-image-outset"]=!0,t["border-image-repeat"]=!0,t["border-image-slice"]=!0,t["border-image-source"]=!0,t["border-image-width"]=!0,t["border-left"]=!0,t["border-left-color"]=!0,t["border-left-style"]=!0,t["border-left-width"]=!0,t["border-radius"]=!0,t["border-right"]=!0,t["border-right-color"]=!0,t["border-right-style"]=!0,t["border-right-width"]=!0,t["border-spacing"]=!0,t["border-style"]=!0,t["border-top"]=!0,t["border-top-color"]=!0,t["border-top-left-radius"]=!0,t["border-top-right-radius"]=!0,t["border-top-style"]=!0,t["border-top-width"]=!0,t["border-width"]=!0,t.bottom=!1,t["box-decoration-break"]=!0,t["box-shadow"]=!0,t["box-sizing"]=!0,t["box-snap"]=!0,t["box-suppress"]=!0,t["break-after"]=!0,t["break-before"]=!0,t["break-inside"]=!0,t["caption-side"]=!1,t.chains=!1,t.clear=!0,t.clip=!1,t["clip-path"]=!1,t["clip-rule"]=!1,t.color=!0,t["color-interpolation-filters"]=!0,t["column-count"]=!1,t["column-fill"]=!1,t["column-gap"]=!1,t["column-rule"]=!1,t["column-rule-color"]=!1,t["column-rule-style"]=!1,t["column-rule-width"]=!1,t["column-span"]=!1,t["column-width"]=!1,t.columns=!1,t.contain=!1,t.content=!1,t["counter-increment"]=!1,t["counter-reset"]=!1,t["counter-set"]=!1,t.crop=!1,t.cue=!1,t["cue-after"]=!1,t["cue-before"]=!1,t.cursor=!1,t.direction=!1,t.display=!0,t["display-inside"]=!0,t["display-list"]=!0,t["display-outside"]=!0,t["dominant-baseline"]=!1,t.elevation=!1,t["empty-cells"]=!1,t.filter=!1,t.flex=!1,t["flex-basis"]=!1,t["flex-direction"]=!1,t["flex-flow"]=!1,t["flex-grow"]=!1,t["flex-shrink"]=!1,t["flex-wrap"]=!1,t.float=!1,t["float-offset"]=!1,t["flood-color"]=!1,t["flood-opacity"]=!1,t["flow-from"]=!1,t["flow-into"]=!1,t.font=!0,t["font-family"]=!0,t["font-feature-settings"]=!0,t["font-kerning"]=!0,t["font-language-override"]=!0,t["font-size"]=!0,t["font-size-adjust"]=!0,t["font-stretch"]=!0,t["font-style"]=!0,t["font-synthesis"]=!0,t["font-variant"]=!0,t["font-variant-alternates"]=!0,t["font-variant-caps"]=!0,t["font-variant-east-asian"]=!0,t["font-variant-ligatures"]=!0,t["font-variant-numeric"]=!0,t["font-variant-position"]=!0,t["font-weight"]=!0,t.grid=!1,t["grid-area"]=!1,t["grid-auto-columns"]=!1,t["grid-auto-flow"]=!1,t["grid-auto-rows"]=!1,t["grid-column"]=!1,t["grid-column-end"]=!1,t["grid-column-start"]=!1,t["grid-row"]=!1,t["grid-row-end"]=!1,t["grid-row-start"]=!1,t["grid-template"]=!1,t["grid-template-areas"]=!1,t["grid-template-columns"]=!1,t["grid-template-rows"]=!1,t["hanging-punctuation"]=!1,t.height=!0,t.hyphens=!1,t.icon=!1,t["image-orientation"]=!1,t["image-resolution"]=!1,t["ime-mode"]=!1,t["initial-letters"]=!1,t["inline-box-align"]=!1,t["justify-content"]=!1,t["justify-items"]=!1,t["justify-self"]=!1,t.left=!1,t["letter-spacing"]=!0,t["lighting-color"]=!0,t["line-box-contain"]=!1,t["line-break"]=!1,t["line-grid"]=!1,t["line-height"]=!1,t["line-snap"]=!1,t["line-stacking"]=!1,t["line-stacking-ruby"]=!1,t["line-stacking-shift"]=!1,t["line-stacking-strategy"]=!1,t["list-style"]=!0,t["list-style-image"]=!0,t["list-style-position"]=!0,t["list-style-type"]=!0,t.margin=!0,t["margin-bottom"]=!0,t["margin-left"]=!0,t["margin-right"]=!0,t["margin-top"]=!0,t["marker-offset"]=!1,t["marker-side"]=!1,t.marks=!1,t.mask=!1,t["mask-box"]=!1,t["mask-box-outset"]=!1,t["mask-box-repeat"]=!1,t["mask-box-slice"]=!1,t["mask-box-source"]=!1,t["mask-box-width"]=!1,t["mask-clip"]=!1,t["mask-image"]=!1,t["mask-origin"]=!1,t["mask-position"]=!1,t["mask-repeat"]=!1,t["mask-size"]=!1,t["mask-source-type"]=!1,t["mask-type"]=!1,t["max-height"]=!0,t["max-lines"]=!1,t["max-width"]=!0,t["min-height"]=!0,t["min-width"]=!0,t["move-to"]=!1,t["nav-down"]=!1,t["nav-index"]=!1,t["nav-left"]=!1,t["nav-right"]=!1,t["nav-up"]=!1,t["object-fit"]=!1,t["object-position"]=!1,t.opacity=!1,t.order=!1,t.orphans=!1,t.outline=!1,t["outline-color"]=!1,t["outline-offset"]=!1,t["outline-style"]=!1,t["outline-width"]=!1,t.overflow=!1,t["overflow-wrap"]=!1,t["overflow-x"]=!1,t["overflow-y"]=!1,t.padding=!0,t["padding-bottom"]=!0,t["padding-left"]=!0,t["padding-right"]=!0,t["padding-top"]=!0,t.page=!1,t["page-break-after"]=!1,t["page-break-before"]=!1,t["page-break-inside"]=!1,t["page-policy"]=!1,t.pause=!1,t["pause-after"]=!1,t["pause-before"]=!1,t.perspective=!1,t["perspective-origin"]=!1,t.pitch=!1,t["pitch-range"]=!1,t["play-during"]=!1,t.position=!1,t["presentation-level"]=!1,t.quotes=!1,t["region-fragment"]=!1,t.resize=!1,t.rest=!1,t["rest-after"]=!1,t["rest-before"]=!1,t.richness=!1,t.right=!1,t.rotation=!1,t["rotation-point"]=!1,t["ruby-align"]=!1,t["ruby-merge"]=!1,t["ruby-position"]=!1,t["shape-image-threshold"]=!1,t["shape-outside"]=!1,t["shape-margin"]=!1,t.size=!1,t.speak=!1,t["speak-as"]=!1,t["speak-header"]=!1,t["speak-numeral"]=!1,t["speak-punctuation"]=!1,t["speech-rate"]=!1,t.stress=!1,t["string-set"]=!1,t["tab-size"]=!1,t["table-layout"]=!1,t["text-align"]=!0,t["text-align-last"]=!0,t["text-combine-upright"]=!0,t["text-decoration"]=!0,t["text-decoration-color"]=!0,t["text-decoration-line"]=!0,t["text-decoration-skip"]=!0,t["text-decoration-style"]=!0,t["text-emphasis"]=!0,t["text-emphasis-color"]=!0,t["text-emphasis-position"]=!0,t["text-emphasis-style"]=!0,t["text-height"]=!0,t["text-indent"]=!0,t["text-justify"]=!0,t["text-orientation"]=!0,t["text-overflow"]=!0,t["text-shadow"]=!0,t["text-space-collapse"]=!0,t["text-transform"]=!0,t["text-underline-position"]=!0,t["text-wrap"]=!0,t.top=!1,t.transform=!1,t["transform-origin"]=!1,t["transform-style"]=!1,t.transition=!1,t["transition-delay"]=!1,t["transition-duration"]=!1,t["transition-property"]=!1,t["transition-timing-function"]=!1,t["unicode-bidi"]=!1,t["vertical-align"]=!1,t.visibility=!1,t["voice-balance"]=!1,t["voice-duration"]=!1,t["voice-family"]=!1,t["voice-pitch"]=!1,t["voice-range"]=!1,t["voice-rate"]=!1,t["voice-stress"]=!1,t["voice-volume"]=!1,t.volume=!1,t["white-space"]=!1,t.widows=!1,t.width=!0,t["will-change"]=!1,t["word-break"]=!0,t["word-spacing"]=!0,t["word-wrap"]=!0,t["wrap-flow"]=!1,t["wrap-through"]=!1,t["writing-mode"]=!1,t["z-index"]=!1,t}function e(t,a,u){}function r(t,a,u){}function n(t,a){return i.test(a)?"":a}var i=/javascript\s*\:/gim;d.whiteList=s(),d.getDefaultWhiteList=s,d.onAttr=e,d.onIgnoreAttr=r,d.safeAttrValue=n},function(f,d){f.exports={indexOf:function(s,e){var r,n;if(Array.prototype.indexOf)return s.indexOf(e);for(r=0,n=s.length;r<n;r++)if(s[r]===e)return r;return-1},forEach:function(s,e,r){var n,i;if(Array.prototype.forEach)return s.forEach(e,r);for(n=0,i=s.length;n<i;n++)e.call(r,s[n],n,s)},trim:function(s){return String.prototype.trim?s.trim():s.replace(/(^\s*)|(\s*$)/g,"")},trimRight:function(s){return String.prototype.trimRight?s.trimRight():s.replace(/(\s*$)/g,"")}}},function(f,d,s){f.exports=s(113)},function(f,d,s){var e=`<[A-Za-z][A-Za-z0-9\\-]*(?:\\s+[a-zA-Z_:][a-zA-Z0-9:._-]*(?:\\s*=\\s*(?:[^"'=<>\`\\x00-\\x20]+|'[^']*'|"[^"]*"))?)*\\s*\\/?>`,r="<\\/[A-Za-z][A-Za-z0-9\\-]*\\s*>",n=new RegExp("^(?:"+e+"|"+r+"|<!---->|<!--(?:-?[^>-])(?:-?[^-])*-->|<[?].*?[?]>|<![A-Z]+\\s+[^>]*>|<!\\[CDATA\\[[\\s\\S]*?\\]\\]>)"),i=new RegExp("^(?:"+e+"|"+r+")");f.exports.HTML_TAG_RE=n,f.exports.HTML_OPEN_CLOSE_TAG_RE=i},function(f,d,s){function e(r,n){var i,t,a,u,c,o,l=n.length;for(i=l-1;i>=0;i--)t=n[i],t.marker!==95&&t.marker!==42||t.end!==-1&&(a=n[t.end],o=i>0&&n[i-1].end===t.end+1&&n[i-1].token===t.token-1&&n[t.end+1].token===a.token+1&&n[i-1].marker===t.marker,c=String.fromCharCode(t.marker),u=r.tokens[t.token],u.type=o?"strong_open":"em_open",u.tag=o?"strong":"em",u.nesting=1,u.markup=o?c+c:c,u.content="",u=r.tokens[a.token],u.type=o?"strong_close":"em_close",u.tag=o?"strong":"em",u.nesting=-1,u.markup=o?c+c:c,u.content="",o&&(r.tokens[n[i-1].token].content="",r.tokens[n[t.end+1].token].content="",i--))}f.exports.tokenize=function(r,n){var i,t,a,u=r.pos,c=r.src.charCodeAt(u);if(n||c!==95&&c!==42)return!1;for(t=r.scanDelims(r.pos,c===42),i=0;i<t.length;i++)a=r.push("text","",0),a.content=String.fromCharCode(c),r.delimiters.push({marker:c,length:t.length,jump:i,token:r.tokens.length-1,end:-1,open:t.can_open,close:t.can_close});return r.pos+=t.length,!0},f.exports.postProcess=function(r){var n,i=r.tokens_meta,t=r.tokens_meta.length;for(e(r,r.delimiters),n=0;n<t;n++)i[n]&&i[n].delimiters&&e(r,i[n].delimiters)}},function(f,d,s){function e(r,n){var i,t,a,u,c,o=[],l=n.length;for(i=0;i<l;i++)a=n[i],a.marker===126&&a.end!==-1&&(u=n[a.end],c=r.tokens[a.token],c.type="s_open",c.tag="s",c.nesting=1,c.markup="~~",c.content="",c=r.tokens[u.token],c.type="s_close",c.tag="s",c.nesting=-1,c.markup="~~",c.content="",r.tokens[u.token-1].type==="text"&&r.tokens[u.token-1].content==="~"&&o.push(u.token-1));for(;o.length;){for(i=o.pop(),t=i+1;t<r.tokens.length&&r.tokens[t].type==="s_close";)t++;t--,i!==t&&(c=r.tokens[t],r.tokens[t]=r.tokens[i],r.tokens[i]=c)}}f.exports.tokenize=function(r,n){var i,t,a,u,c,o=r.pos,l=r.src.charCodeAt(o);if(n||l!==126||(t=r.scanDelims(r.pos,!0),u=t.length,c=String.fromCharCode(l),u<2))return!1;for(u%2&&(a=r.push("text","",0),a.content=c,u--),i=0;i<u;i+=2)a=r.push("text","",0),a.content=c+c,r.delimiters.push({marker:l,length:0,jump:i,token:r.tokens.length-1,end:-1,open:t.can_open,close:t.can_close});return r.pos+=t.length,!0},f.exports.postProcess=function(r){var n,i=r.tokens_meta,t=r.tokens_meta.length;for(e(r,r.delimiters),n=0;n<t;n++)i[n]&&i[n].delimiters&&e(r,i[n].delimiters)}},function(f,d,s){f.exports.encode=s(183),f.exports.decode=s(182),f.exports.format=s(184),f.exports.parse=s(185)},function(f,d){f.exports=/[\0-\x1F\x7F-\x9F]/},function(f,d){f.exports=/[ \xA0\u1680\u2000-\u200A\u2028\u2029\u202F\u205F\u3000]/},function(f,d){f.exports=/[\0-\uD7FF\uE000-\uFFFF]|[\uD800-\uDBFF][\uDC00-\uDFFF]|[\uD800-\uDBFF](?![\uDC00-\uDFFF])|(?:[^\uD800-\uDBFF]|^)[\uDC00-\uDFFF]/},function(f,d,s){function e(){return{a:["target","href","title"],abbr:["title"],address:[],area:["shape","coords","href","alt"],article:[],aside:[],audio:["autoplay","controls","loop","preload","src"],b:[],bdi:["dir"],bdo:["dir"],big:[],blockquote:["cite"],br:[],caption:[],center:[],cite:[],code:[],col:["align","valign","span","width"],colgroup:["align","valign","span","width"],dd:[],del:["datetime"],details:["open"],div:[],dl:[],dt:[],em:[],font:["color","size","face"],footer:[],h1:[],h2:[],h3:[],h4:[],h5:[],h6:[],header:[],hr:[],i:[],img:["src","alt","title","width","height"],ins:["datetime"],li:[],mark:[],nav:[],ol:[],p:[],pre:[],s:[],section:[],small:[],span:[],sub:[],sup:[],strong:[],table:["width","border","align","valign"],tbody:["align","valign"],td:["width","rowspan","colspan","align","valign"],tfoot:["align","valign"],th:["width","rowspan","colspan","align","valign"],thead:["align","valign"],tr:["rowspan","align","valign"],tt:[],u:[],ul:[],video:["autoplay","controls","loop","preload","src","height","width"]}}function r(D,z,$){}function n(D,z,$){}function i(D,z,$){}function t(D,z,$){}function a(D){return D.replace(A,"&lt;").replace(T,"&gt;")}function u(D,z,$,R){if($=h($),z==="href"||z==="src"){if(($=C.trim($))==="#")return"#";if($.substr(0,7)!=="http://"&&$.substr(0,8)!=="https://"&&$.substr(0,7)!=="mailto:"&&$.substr(0,4)!=="tel:"&&$[0]!=="#"&&$[0]!=="/")return""}else if(z==="background"){if(E.lastIndex=0,E.test($))return""}else if(z==="style"){if(q.lastIndex=0,q.test($)||(O.lastIndex=0,O.test($)&&(E.lastIndex=0,E.test($))))return"";R!==!1&&(R=R||y,$=R.process($))}return $=b($)}function c(D){return D.replace(L,"&quot;")}function o(D){return D.replace(j,'"')}function l(D){return D.replace(S,function(z,$){return $[0]==="x"||$[0]==="X"?String.fromCharCode(parseInt($.substr(1),16)):String.fromCharCode(parseInt($,10))})}function p(D){return D.replace(M,":").replace(F," ")}function _(D){for(var z="",$=0,R=D.length;$<R;$++)z+=D.charCodeAt($)<32?" ":D.charAt($);return C.trim(z)}function h(D){return D=o(D),D=l(D),D=p(D),D=_(D)}function b(D){return D=c(D),D=a(D)}function m(){return""}function g(D,z){function $(H){return!!R||C.indexOf(D,H)!==-1}typeof z!="function"&&(z=function(){});var R=!Array.isArray(D),B=[],U=!1;return{onIgnoreTag:function(H,G,V){if($(H)){if(V.isClosing){var K="[/removed]",Q=V.position+K.length;return B.push([U!==!1?U:V.position,Q]),U=!1,K}return U||(U=V.position),"[removed]"}return z(H,G,V)},remove:function(H){var G="",V=0;return C.forEach(B,function(K){G+=H.slice(V,K[0]),V=K[1]}),G+=H.slice(V)}}}function w(D){return D.replace(P,"")}function v(D){var z=D.split("");return z=z.filter(function($){var R=$.charCodeAt(0);return R!==127&&(!(R<=31)||R===10||R===13)}),z.join("")}var x=s(31).FilterCSS,k=s(31).getDefaultWhiteList,C=s(35),y=new x,A=/</g,T=/>/g,L=/"/g,j=/&quot;/g,S=/&#([a-zA-Z0-9]*);?/gim,M=/&colon;?/gim,F=/&newline;?/gim,E=/((j\s*a\s*v\s*a|v\s*b|l\s*i\s*v\s*e)\s*s\s*c\s*r\s*i\s*p\s*t\s*|m\s*o\s*c\s*h\s*a)\:/gi,q=/e\s*x\s*p\s*r\s*e\s*s\s*s\s*i\s*o\s*n\s*\(.*/gi,O=/u\s*r\s*l\s*\(.*/gi,P=/<!--[\s\S]*?-->/g;d.whiteList=e(),d.getDefaultWhiteList=e,d.onTag=r,d.onIgnoreTag=n,d.onTagAttr=i,d.onIgnoreTagAttr=t,d.safeAttrValue=u,d.escapeHtml=a,d.escapeQuote=c,d.unescapeQuote=o,d.escapeHtmlEntities=l,d.escapeDangerHtml5Entities=p,d.clearNonPrintableCharacter=_,d.friendlyAttrValue=h,d.escapeAttrValue=b,d.onIgnoreTagStripAll=m,d.StripTagBody=g,d.stripCommentTag=w,d.stripBlankChar=v,d.cssFilter=y,d.getDefaultCSSWhiteList=k},function(f,d,s){function e(p){var _=o.spaceIndex(p);if(_===-1)var h=p.slice(1,-1);else var h=p.slice(1,_+1);return h=o.trim(h).toLowerCase(),h.slice(0,1)==="/"&&(h=h.slice(1)),h.slice(-1)==="/"&&(h=h.slice(0,-1)),h}function r(p){return p.slice(0,2)==="</"}function n(p,_,h){"user strict";var b="",m=0,g=!1,w=!1,v=0,x=p.length,k="",C="";for(v=0;v<x;v++){var y=p.charAt(v);if(g===!1){if(y==="<"){g=v;continue}}else if(w===!1){if(y==="<"){b+=h(p.slice(m,v)),g=v,m=v;continue}if(y===">"){b+=h(p.slice(m,g)),C=p.slice(g,v+1),k=e(C),b+=_(g,b.length,k,C,r(C)),m=v+1,g=!1;continue}if((y==='"'||y==="'")&&p.charAt(v-1)==="="){w=y;continue}}else if(y===w){w=!1;continue}}return m<p.length&&(b+=h(p.substr(m))),b}function i(p,_){"user strict";function h(y,A){if(y=o.trim(y),y=y.replace(l,"").toLowerCase(),!(y.length<1)){var T=_(y,A||"");T&&m.push(T)}}for(var b=0,m=[],g=!1,w=p.length,v=0;v<w;v++){var x,k,C=p.charAt(v);if(g!==!1||C!=="=")if(g===!1||v!==b||C!=='"'&&C!=="'"||p.charAt(v-1)!=="="){if(/\s|\n|\t/.test(C)){if(p=p.replace(/\s|\n|\t/g," "),g===!1){if((k=t(p,v))===-1){x=o.trim(p.slice(b,v)),h(x),g=!1,b=v+1;continue}v=k-1;continue}if((k=a(p,v-1))===-1){x=o.trim(p.slice(b,v)),x=c(x),h(g,x),g=!1,b=v+1;continue}}}else{if((k=p.indexOf(C,v+1))===-1)break;x=o.trim(p.slice(b+1,k)),h(g,x),g=!1,v=k,b=v+1}else g=p.slice(b,v),b=v+1}return b<p.length&&(g===!1?h(p.slice(b)):h(g,c(o.trim(p.slice(b))))),o.trim(m.join(" "))}function t(p,_){for(;_<p.length;_++){var h=p[_];if(h!==" ")return h==="="?_:-1}}function a(p,_){for(;_>0;_--){var h=p[_];if(h!==" ")return h==="="?_:-1}}function u(p){return p[0]==='"'&&p[p.length-1]==='"'||p[0]==="'"&&p[p.length-1]==="'"}function c(p){return u(p)?p.substr(1,p.length-2):p}var o=s(35),l=/[^a-zA-Z0-9_:\.\-]/gim;d.parseTag=n,d.parseAttr=i},function(f,d,s){function e(n){s(204),s(205)}var r=s(16)(s(67),s(201),e,"data-v-7a63e4b3",null);r.options.__file="D:\\work\\songwang\\yuangongji\\mavonEditor\\src\\mavon-editor.vue",r.esModule&&Object.keys(r.esModule).some(function(n){return n!=="default"&&n.substr(0,2)!=="__"})&&console.error("named exports are not supported in *.vue files."),r.options.functional&&console.error("[vue-loader] mavon-editor.vue: functional components are not supported with templates, they should use render functions."),f.exports=r.exports},function(f,d,s){var e=s(197),r={autoTextarea:e,install:function(n){n.component("auto-textarea",e)}};f.exports=r},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0}),d.default={data:function(){var e=this;return{temp_value:(function(){return e.value})(),s_autofocus:(function(){if(e.autofocus)return"autofocus"})()}},created:function(){},props:{fullHeight:{type:Boolean,default:!1},autofocus:{type:Boolean,default:!1},value:{type:String,default:""},placeholder:{type:String,default:""},border:{type:Boolean,default:!1},resize:{type:Boolean,default:!1},onchange:{type:Function,default:null},fontSize:{type:String,default:"14px"},lineHeight:{type:String,default:"18px"}},methods:{change:function(e){this.onchange&&this.onchange(this.temp_value,e)}},watch:{value:function(e,r){this.temp_value=e},temp_value:function(e,r){this.$emit("input",e)}}}},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0}),d.default={name:"s-md-toolbar-left",props:{editable:{type:Boolean,default:!0},transition:{type:Boolean,default:!0},toolbars:{type:Object,required:!0},d_words:{type:Object,required:!0},image_filter:{type:Function,default:null}},data:function(){return{img_file:[[0,null]],img_timer:null,header_timer:null,s_img_dropdown_open:!1,s_header_dropdown_open:!1,s_img_link_open:!1,trigger:null,num:0,link_text:"",link_addr:"",link_type:"link"}},methods:{$imgLinkAdd:function(){this.$emit("toolbar_left_addlink",this.link_type,this.link_text,this.link_addr),this.s_img_link_open=!1},$toggle_imgLinkAdd:function(e){var r=this;this.link_type=e,this.link_text=this.link_addr="",this.s_img_link_open=!0,this.$nextTick(function(){r.$refs.linkTextInput.focus()}),this.s_img_dropdown_open=!1},$imgFileListClick:function(e){this.$emit("imgTouch",this.img_file[e])},$changeUrl:function(e,r){this.img_file[e][0]=r},$imgFileAdd:function(e){this.img_file.push([++this.num,e]),this.$emit("imgAdd",this.num,e),this.s_img_dropdown_open=!1},$imgFilesAdd:function(e){for(var r=typeof this.image_filter=="function",n=0;n<e.length;n++)r&&this.image_filter(e[n])===!0?this.$imgFileAdd(e[n]):!r&&e[n].type.match(/^image\//i)&&this.$imgFileAdd(e[n])},$imgAdd:function(e){this.$imgFilesAdd(e.target.files),e.target.value=""},$imgDel:function(e){this.$emit("imgDel",this.img_file[e]),this.img_file.splice(e,1),this.num--,this.s_img_dropdown_open=!1},isEqualName:function(e,r){return!(!this.img_file[r][1]||this.img_file[r][1].name!==e&&this.img_file[r][1]._name!==e)},$imgDelByFilename:function(e){for(var r=0;this.img_file.length>r;){if(this.img_file[r][1]===e||this.isEqualName(e,r))return this.$imgDel(r),!0;r+=1}return!1},$imgAddByFilename:function(e,r){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e)return!1;return this.img_file[0][0]=e,this.img_file[0][1]=r,this.img_file[0][2]=e,this.img_file.unshift(["./"+this.num,null]),this.$emit("imgAdd",this.img_file[1][0],r,!1),!0},$imgAddByUrl:function(e,r){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e)return!1;return this.img_file[0][0]=e,this.img_file[0][1]=r,this.img_file.unshift(["./"+this.num,null]),!0},$imgUpdateByFilename:function(e,r){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e||this.isEqualName(e,n))return this.img_file[n][1]=r,this.$emit("imgAdd",e,r,!1),!0;return!1},$mouseenter_img_dropdown:function(){this.editable&&(clearTimeout(this.img_timer),this.s_img_dropdown_open=!0)},$mouseleave_img_dropdown:function(){var e=this;this.img_timer=setTimeout(function(){e.s_img_dropdown_open=!1},200)},$mouseenter_header_dropdown:function(){this.editable&&(clearTimeout(this.header_timer),this.s_header_dropdown_open=!0)},$mouseleave_header_dropdown:function(){var e=this;this.header_timer=setTimeout(function(){e.s_header_dropdown_open=!1},200)},$clicks:function(e){this.editable&&this.$emit("toolbar_left_click",e)},$click_header:function(e){this.$emit("toolbar_left_click",e),this.s_header_dropdown_open=!1},handleClose:function(e){this.s_img_dropdown_open=!1}},watch:{s_img_link_open:function(e){this.$parent.$el.style.zIndex=e?1501:1500}}}},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0}),d.default={name:"s-md-toolbar-right",props:{s_subfield:{type:Boolean,required:!0},toolbars:{type:Object,required:!0},s_preview_switch:{type:Boolean,required:!0},s_fullScreen:{type:Boolean,required:!0},s_html_code:{type:Boolean,required:!0},s_navigation:{type:Boolean,required:!0},d_words:{type:Object,required:!0}},methods:{$clicks:function(e){this.$emit("toolbar_right_click",e)}}}},function(module,exports,__webpack_require__){function _interopRequireDefault(f){return f&&f.__esModule?f:{default:f}}Object.defineProperty(exports,"__esModule",{value:!0});var _typeof2=__webpack_require__(18),_typeof3=_interopRequireDefault(_typeof2),_autoTextarea=__webpack_require__(63),_keydownListen=__webpack_require__(72),_langHljsCss=__webpack_require__(70),_langHljsCss2=_interopRequireDefault(_langHljsCss),_extraFunction=__webpack_require__(38),_util=__webpack_require__(77),_toolbar_left_click2=__webpack_require__(75),_toolbar_right_click2=__webpack_require__(76),_config=__webpack_require__(69),_markdown=__webpack_require__(74),_markdown2=_interopRequireDefault(_markdown),_mdToolbarLeft=__webpack_require__(36),_mdToolbarLeft2=_interopRequireDefault(_mdToolbarLeft),_mdToolbarRight=__webpack_require__(37),_mdToolbarRight2=_interopRequireDefault(_mdToolbarRight);__webpack_require__(115),__webpack_require__(114),exports.default={mixins:[_markdown2.default],props:{scrollStyle:{type:Boolean,default:!0},boxShadow:{type:Boolean,default:!0},transition:{type:Boolean,default:!0},autofocus:{type:Boolean,default:!0},fontSize:{type:String,default:"14px"},toolbarsBackground:{type:String,default:"#ffffff"},editorBackground:{type:String,default:"#ffffff"},previewBackground:{type:String,default:"#fbfbfb"},boxShadowStyle:{type:String,default:"0 2px 12px 0 rgba(0, 0, 0, 0.1)"},help:{type:String,default:null},value:{type:String,default:""},language:{type:String,default:"zh-CN"},subfield:{type:Boolean,default:!0},navigation:{type:Boolean,default:!1},defaultOpen:{type:String,default:null},editable:{type:Boolean,default:!0},toolbarsFlag:{type:Boolean,default:!0},toolbars:{type:Object,default:function(){return _config.CONFIG.toolbars}},html:{type:Boolean,default:!0},xssOptions:{type:[Object,Boolean],default:function(){return{}}},codeStyle:{type:String,default:function(){return"github"}},placeholder:{type:String,default:null},ishljs:{type:Boolean,default:!0},externalLink:{type:[Object,Boolean],default:!0},imageFilter:{type:Function,default:null},imageClick:{type:Function,default:null},tabSize:{type:Number,default:0},shortCut:{type:Boolean,default:!0}},data:function(){var f=this;return{s_right_click_menu_show:!1,right_click_menu_top:0,right_click_menu_left:0,s_subfield:(function(){return f.subfield})(),s_autofocus:!0,s_navigation:(function(){return f.navigation})(),s_scrollStyle:(function(){return f.scrollStyle})(),d_value:"",d_render:"",s_preview_switch:(function(){var d=f.defaultOpen;return d||(d=f.subfield?"preview":"edit"),d==="preview"})(),s_fullScreen:!1,s_help:!1,s_html_code:!1,d_help:null,d_words:null,edit_scroll_height:-1,s_readmodel:!1,s_table_enter:!1,d_history:(function(){var d=[];return d.push(f.value),d})(),d_history_index:0,currentTimeout:"",d_image_file:[],d_preview_imgsrc:null,s_external_link:{markdown_css:function(){return"https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.9.0/github-markdown.min.css"},hljs_js:function(){return"https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"},hljs_lang:function(d){return"https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/languages/"+d+".min.js"},hljs_css:function(d){return _langHljsCss2.default[d]?"https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/"+d+".min.css":""},katex_js:function(){return"https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.8.3/katex.min.js"},katex_css:function(){return"https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.8.3/katex.min.css"}},p_external_link:{},textarea_selectionEnd:0,textarea_selectionEnds:[0],_xssHandler:null}},created:function(){var f=this;this.initLanguage(),this.initExternalFuc(),this.$nextTick(function(){f.editableTextarea()})},mounted:function(){var f=this;this.$el.addEventListener("paste",function(d){f.$paste(d)}),this.$el.addEventListener("drop",function(d){f.$drag(d)}),(0,_keydownListen.keydownListen)(this),(0,_extraFunction.ImagePreviewListener)(this),this.autofocus&&this.getTextareaDom().focus(),(0,_extraFunction.fullscreenchange)(this),this.d_value=this.value||"",document.body.appendChild(this.$refs.help),this.loadExternalLink("markdown_css","css"),this.loadExternalLink("katex_css","css"),this.loadExternalLink("katex_js","js",function(){f.iRender(!0)}),this.loadExternalLink("hljs_js","js",function(){f.iRender(!0)}),f.codeStyleChange(f.codeStyle,!0)},beforeDestroy:function(){document.body.removeChild(this.$refs.help)},getMarkdownIt:function(){var f=this.mixins[0].data().markdownIt;return f||(f=(0,_markdown.initMarkdown)()),f},methods:{loadExternalLink:function(f,d,s){if(typeof this.p_external_link[f]!="function")return void(this.p_external_link[f]!==!1&&console.error("external_link."+f,"is not a function, if you want to disabled this error log, set external_link."+f,"to function or false"));var e={css:_extraFunction.loadLink,js:_extraFunction.loadScript};e.hasOwnProperty(d)&&e[d](this.p_external_link[f](),s)},initExternalFuc:function(){for(var f=this,d=["markdown_css","hljs_js","hljs_css","hljs_lang","katex_js","katex_css"],s=(0,_typeof3.default)(f.externalLink),e=s==="object",r=s==="boolean",n=0;n<d.length;n++)r&&!f.externalLink||e&&f.externalLink[d[n]]===!1?f.p_external_link[d[n]]=!1:e&&typeof f.externalLink[d[n]]=="function"?f.p_external_link[d[n]]=f.externalLink[d[n]]:f.p_external_link[d[n]]=f.s_external_link[d[n]]},textAreaFocus:function(){this.$refs.vNoteTextarea.$refs.vTextarea.focus()},$drag:function(f){var d=f.dataTransfer;if(d){var s=d.files;s.length>0&&(f.preventDefault(),this.$refs.toolbar_left.$imgFilesAdd(s))}},$paste:function(f){var d=f.clipboardData;if(d){var s=d.items;if(!s)return;for(var e=d.types||[],r=null,n=0;n<e.length;n++)if(e[n]==="Files"){r=s[n];break}if(r&&r.kind==="file"){(0,_util.stopEvent)(f);var i=r.getAsFile();this.$refs.toolbar_left.$imgFilesAdd([i])}}},$imgTouch:function(f){},$imgDel:function(f){this.markdownIt.image_del(f[1]);var d=f[0],s=new RegExp("\\!\\["+f[1]._name+"\\]\\("+d+"\\)","g");this.d_value=this.d_value.replace(s,""),this.iRender(),this.$emit("imgDel",f)},$imgAdd:function(f,d,s){s===void 0&&(s=!0);var e=this;if(this.__rFilter==null&&(this.__rFilter=/^image\//i),this.__oFReader=new FileReader,this.__oFReader.onload=function(n){e.markdownIt.image_add(f,n.target.result),d.miniurl=n.target.result,s===!0&&(d._name=d.name.replace(/[\[\]\(\)\+\{\}&\|\\\*^%$#@\-]/g,""),e.insertText(e.getTextareaDom(),{prefix:"!["+d._name+"]("+f+")",subfix:"",str:""}),e.$nextTick(function(){e.$emit("imgAdd",f,d)}))},d){var r=d;this.__rFilter.test(r.type)&&this.__oFReader.readAsDataURL(r)}},$imgUpdateByUrl:function(f,d){var s=this;this.markdownIt.image_add(f,d),this.$nextTick(function(){s.d_render=this.markdownIt.render(this.d_value)})},$imgAddByUrl:function(f,d){return!!this.$refs.toolbar_left.$imgAddByUrl(f,d)&&(this.$imgUpdateByUrl(f,d),!0)},$img2Url:function $img2Url(fileIndex,url){var reg_str="/(!\\[[^\\[]*?\\](?=\\())\\(\\s*("+fileIndex+")\\s*\\)/g",reg=eval(reg_str);this.d_value=this.d_value.replace(reg,"$1("+url+")"),this.$refs.toolbar_left.$changeUrl(fileIndex,url),this.iRender()},$imglst2Url:function(f){if(f instanceof Array)for(var d=0;d<f.length;d++)this.$img2Url(f[d][0],f[d][1])},toolbar_left_click:function(f){(0,_toolbar_left_click2.toolbar_left_click)(f,this)},toolbar_left_addlink:function(f,d,s){(0,_toolbar_left_click2.toolbar_left_addlink)(f,d,s,this)},toolbar_right_click:function(f){(0,_toolbar_right_click2.toolbar_right_click)(f,this)},getNavigation:function(f,d){return(0,_extraFunction.getNavigation)(f,d)},change:function(f,d){this.$emit("change",f,d)},fullscreen:function(f,d){this.$emit("fullScreen",f,d)},readmodel:function(f,d){this.$emit("readModel",f,d)},previewtoggle:function(f,d){this.$emit("previewToggle",f,d)},subfieldtoggle:function(f,d){this.$emit("subfieldToggle",f,d)},htmlcode:function(f,d){this.$emit("htmlCode",f,d)},helptoggle:function(f,d){this.$emit("helpToggle",f,d)},save:function(f,d){this.$emit("save",f,d)},navigationtoggle:function(f,d){this.$emit("navigationToggle",f,d)},$toolbar_right_read_change_status:function(){this.s_readmodel=!this.s_readmodel,this.readmodel&&this.readmodel(this.s_readmodel,this.d_value),this.s_readmodel&&this.toolbars.navigation&&this.getNavigation(this,!0)},$v_edit_scroll:function(f){(0,_extraFunction.scrollLink)(f,this)},getTextareaDom:function(){return this.$refs.vNoteTextarea.$refs.vTextarea},insertText:function(f,d){var s=d.prefix,e=d.subfix,r=d.str,n=d.type;(0,_extraFunction.insertTextAtCaret)(f,{prefix:s,subfix:e,str:r,type:n},this)},insertTab:function(){(0,_extraFunction.insertTab)(this,this.tabSize)},insertOl:function(){(0,_extraFunction.insertOl)(this)},removeLine:function(){(0,_extraFunction.removeLine)(this)},insertUl:function(){(0,_extraFunction.insertUl)(this)},unInsertTab:function(){(0,_extraFunction.unInsertTab)(this,this.tabSize)},insertCodeBlock:function(){(0,_extraFunction.insertCodeBlock)(this)},insertEnter:function(f){(0,_extraFunction.insertEnter)(this,f)},saveHistory:function(){this.d_history.splice(this.d_history_index+1,this.d_history.length),this.d_history.push(this.d_value),this.textarea_selectionEnds.splice(this.d_history_index+1,this.textarea_selectionEnds.length),this.textarea_selectionEnds.push(this.textarea_selectionEnd),this.d_history_index=this.d_history.length-1},saveSelectionEndsHistory:function(){var f=this.$refs.vNoteTextarea&&this.$refs.vNoteTextarea.$el.querySelector("textarea");this.textarea_selectionEnd=f?f.selectionEnd:this.textarea_selectionEnd},initLanguage:function(){var f=_config.CONFIG.langList.indexOf(this.language)>=0?this.language:"zh-CN",d=this;d.$render(_config.CONFIG["help_"+f],function(s){d.d_help=s}),this.d_words=_config.CONFIG["words_"+f]},editableTextarea:function(){var f=this.$refs.vNoteTextarea.$refs.vTextarea;this.editable?f.removeAttribute("disabled"):f.setAttribute("disabled","disabled")},codeStyleChange:function(f,d){if(d=d||!1,typeof this.p_external_link.hljs_css!="function")return void(this.p_external_link.hljs_css!==!1&&console.error("external_link.hljs_css is not a function, if you want to disabled this error log, set external_link.hljs_css to function or false"));var s=this.p_external_link.hljs_css(f);s.length===0&&d&&(console.warn("hljs color scheme",f,"do not exist, loading default github"),s=this.p_external_link.hljs_css("github")),s.length>0?(0,_extraFunction.loadLink)(s,null,"md-code-style"):console.warn("hljs color scheme",f,"do not exist, hljs color scheme will not change")},iRender:function(f){var d=this;this.$render(d.d_value,function(s){d.d_render=s,f||d.change&&d.change(d.d_value,d.d_render),d.s_navigation&&(0,_extraFunction.getNavigation)(d,!1),d.$emit("input",d.d_value),d.d_value!==d.d_history[d.d_history_index]&&(window.clearTimeout(d.currentTimeout),d.currentTimeout=setTimeout(function(){d.saveHistory()},500))})},$emptyHistory:function(){this.d_history=[this.d_value],this.d_history_index=0}},watch:{d_value:function(f,d){this.saveSelectionEndsHistory(),this.iRender()},value:function(f,d){f!==this.d_value&&(this.d_value=f)},subfield:function(f,d){this.s_subfield=f},d_history_index:function(){this.d_history_index>20&&(this.d_history.shift(),this.d_history_index=this.d_history_index-1),this.d_value=this.d_history[this.d_history_index]},language:function(f){this.initLanguage()},editable:function(){this.editableTextarea()},defaultOpen:function(f){var d=f;return d||(d=this.subfield?"preview":"edit"),this.s_preview_switch=d==="preview",this.s_preview_switch},codeStyle:function(f){this.codeStyleChange(f)}},components:{"v-autoTextarea":_autoTextarea.autoTextarea,"v-md-toolbar-left":_mdToolbarLeft2.default,"v-md-toolbar-right":_mdToolbarRight2.default}}},function(f,d,s){var e=s(62),r={markdownIt:e.mixins[0].data().markdownIt,mavonEditor:e,LeftToolbar:s(36),RightToolbar:s(37),install:function(n){n.component("mavon-editor",e)}};f.exports=r},function(f,d,s){function e(D){return D&&D.__esModule?D:{default:D}}Object.defineProperty(d,"__esModule",{value:!0}),d.CONFIG=void 0;var r=s(193),n=e(r),i=s(194),t=e(i),a=s(188),u=e(a),c=s(189),o=e(c),l=s(191),p=e(l),_=s(192),h=e(_),b=s(187),m=e(b),g=s(190),w=e(g),v=s(217),x=e(v),k=s(218),C=e(k),y=s(212),A=e(y),T=s(213),L=e(T),j=s(215),S=e(j),M=s(216),F=e(M),E=s(211),q=e(E),O=s(214),P=e(O);d.CONFIG={"help_zh-CN":n.default,"help_zh-TW":t.default,"help_pt-BR":p.default,help_en:u.default,help_fr:o.default,help_ru:h.default,help_de:m.default,help_ja:w.default,"words_zh-CN":x.default,"words_zh-TW":C.default,"words_pt-BR":S.default,words_en:A.default,words_fr:L.default,words_ru:F.default,words_de:q.default,words_ja:P.default,langList:["en","zh-CN","zh-TW","fr","pt-BR","ru","de","ja"],toolbars:{bold:!0,italic:!0,header:!0,underline:!0,strikethrough:!0,mark:!0,superscript:!0,subscript:!0,quote:!0,ol:!0,ul:!0,link:!0,imagelink:!0,code:!0,table:!0,undo:!0,redo:!0,trash:!0,save:!0,alignleft:!0,aligncenter:!0,alignright:!0,navigation:!0,subfield:!0,fullscreen:!0,readmodel:!0,htmlcode:!0,help:!0,preview:!0}}},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0}),d.default={agate:1,androidstudio:1,"arduino-light":1,arta:1,ascetic:1,"atom-one-dark":1,"atom-one-light":1,"brown-paper":1,"codepen-embed":1,"color-brewer":1,dark:1,default:1,docco:1,far:1,foundation:1,github:1,googlecode:1,grayscale:1,hybrid:1,idea:1,"ir-black":1,magula:1,"mono-blue":1,"monokai-sublime":1,monokai:1,obsidian:1,"paraiso-dark":1,"paraiso-light":1,pojoaque:1,purebasic:1,rainbow:1,routeros:1,"school-book":1,sunburst:1,"tomorrow-night-blue":1,"tomorrow-night-bright":1,vs:1,vs2015:1,xcode:1,xt256:1,"a11y-dark":1,"a11y-light":1,"an-old-hope":1,"atom-one-dark-reasonable":1,"brown-pap":1,devibeans:1,"github-dark":1,"github-dark-dimmed":1,gml:1,"gradient-dark":1,"gradient-light":1,"isbl-editor-dark":1,"isbl-editor-light":1,"kimbie-dark":1,"kimbie-light":1,lightfair:1,lioshi:1,"night-owl":1,"nnfx-dark":1,"nnfx-light":1,nord:1,pojo:1,"qtcreator-dark":1,"qtcreator-light":1,"shades-of-purple":1,srcery:1,"stackoverflow-dark":1,"stackoverflow-light":1}},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0}),d.default={"1c":"1c",abnf:"abnf",accesslog:"accesslog",actionscript:"actionscript",as:"actionscript",ada:"ada",apache:"apache",apacheconf:"apache",applescript:"applescript",osascript:"applescript",arduino:"arduino",armasm:"armasm",arm:"armasm",asciidoc:"asciidoc",adoc:"asciidoc",aspectj:"aspectj",autohotkey:"autohotkey",ahk:"autohotkey",autoit:"autoit",avrasm:"avrasm",awk:"awk",axapta:"axapta",bash:"bash",sh:"bash",zsh:"bash",basic:"basic",bnf:"bnf",brainfuck:"brainfuck",bf:"brainfuck",cal:"cal",capnproto:"capnproto",capnp:"capnproto",ceylon:"ceylon",clean:"clean",icl:"clean",dcl:"clean","clojure-repl":"clojure-repl",clojure:"clojure",clj:"clojure",cmake:"cmake","cmake.in":"cmake",coffeescript:"coffeescript",coffee:"coffeescript",cson:"coffeescript",iced:"coffeescript",coq:"coq",cos:"cos",cls:"cos",cpp:"cpp",c:"cpp",cc:"cpp",h:"cpp","c++":"cpp","h++":"cpp",hpp:"cpp",crmsh:"crmsh",crm:"crmsh",pcmk:"crmsh",crystal:"crystal",cr:"crystal",cs:"cs",csharp:"cs",csp:"csp",css:"css",d:"d",dart:"dart",delphi:"delphi",dpr:"delphi",dfm:"delphi",pas:"delphi",pascal:"delphi",freepascal:"delphi",lazarus:"delphi",lpr:"delphi",lfm:"delphi",diff:"diff",patch:"diff",django:"django",jinja:"django",dns:"dns",bind:"dns",zone:"dns",dockerfile:"dockerfile",docker:"dockerfile",dos:"dos",bat:"dos",cmd:"dos",dsconfig:"dsconfig",dts:"dts",dust:"dust",dst:"dust",ebnf:"ebnf",elixir:"elixir",elm:"elm",erb:"erb","erlang-repl":"erlang-repl",erlang:"erlang",erl:"erlang",excel:"excel",xlsx:"excel",xls:"excel",fix:"fix",flix:"flix",fortran:"fortran",f90:"fortran",f95:"fortran",fsharp:"fsharp",fs:"fsharp",gams:"gams",gms:"gams",gauss:"gauss",gss:"gauss",gcode:"gcode",nc:"gcode",gherkin:"gherkin",feature:"gherkin",glsl:"glsl",go:"go",golang:"go",golo:"golo",gradle:"gradle",groovy:"groovy",haml:"haml",handlebars:"handlebars",hbs:"handlebars","html.hbs":"handlebars","html.handlebars":"handlebars",haskell:"haskell",hs:"haskell",haxe:"haxe",hx:"haxe",hsp:"hsp",htmlbars:"htmlbars",http:"http",https:"http",hy:"hy",hylang:"hy",inform7:"inform7",i7:"inform7",ini:"ini",toml:"ini",irpf90:"irpf90",java:"java",jsp:"java",javascript:"javascript",js:"javascript",jsx:"javascript","jboss-cli":"jboss-cli","wildfly-cli":"jboss-cli",json:"json","julia-repl":"julia-repl",julia:"julia",kotlin:"kotlin",lasso:"lasso",ls:"livescript",lassoscript:"lasso",ldif:"ldif",leaf:"leaf",less:"less",lisp:"lisp",livecodeserver:"livecodeserver",livescript:"livescript",llvm:"llvm",lsl:"lsl",lua:"lua",makefile:"makefile",mk:"makefile",mak:"makefile",markdown:"markdown",md:"markdown",mkdown:"markdown",mkd:"markdown",mathematica:"mathematica",mma:"mathematica",matlab:"matlab",maxima:"maxima",mel:"mel",mercury:"mercury",m:"mercury",moo:"mercury",mipsasm:"mipsasm",mips:"mipsasm",mizar:"mizar",mojolicious:"mojolicious",monkey:"monkey",moonscript:"moonscript",moon:"moonscript",n1ql:"n1ql",nginx:"nginx",nginxconf:"nginx",nimrod:"nimrod",nim:"nimrod",nix:"nix",nixos:"nix",nsis:"nsis",objectivec:"objectivec",mm:"objectivec",objc:"objectivec","obj-c":"objectivec",ocaml:"ocaml",ml:"sml",openscad:"openscad",scad:"openscad",oxygene:"oxygene",parser3:"parser3",perl:"perl",pl:"perl",pm:"perl",pf:"pf","pf.conf":"pf",php:"php",php3:"php",php4:"php",php5:"php",php6:"php",pony:"pony",powershell:"powershell",ps:"powershell",processing:"processing",profile:"profile",prolog:"prolog",protobuf:"protobuf",puppet:"puppet",pp:"puppet",purebasic:"purebasic",pb:"purebasic",pbi:"purebasic",python:"python",py:"python",gyp:"python",q:"q",k:"q",kdb:"q",qml:"qml",qt:"qml",r:"r",rib:"rib",roboconf:"roboconf",graph:"roboconf",instances:"roboconf",routeros:"routeros",mikrotik:"routeros",rsl:"rsl",ruby:"ruby",rb:"ruby",gemspec:"ruby",podspec:"ruby",thor:"ruby",irb:"ruby",ruleslanguage:"ruleslanguage",rust:"rust",rs:"rust",scala:"scala",scheme:"scheme",scilab:"scilab",sci:"scilab",scss:"scss",shell:"shell",console:"shell",smali:"smali",smalltalk:"smalltalk",st:"smalltalk",sml:"sml",sqf:"sqf",sql:"sql",stan:"stan",stata:"stata",do:"stata",ado:"stata",step21:"step21",p21:"step21",step:"step21",stp:"step21",stylus:"stylus",styl:"stylus",subunit:"subunit",swift:"swift",taggerscript:"taggerscript",tap:"tap",tcl:"tcl",tk:"tcl",tex:"tex",thrift:"thrift",tp:"tp",twig:"twig",craftcms:"twig",typescript:"typescript",ts:"typescript",vala:"vala",vbnet:"vbnet",vb:"vbnet","vbscript-html":"vbscript-html",vbscript:"vbscript",vbs:"vbscript",verilog:"verilog",v:"verilog",sv:"verilog",svh:"verilog",vhdl:"vhdl",vim:"vim",x86asm:"x86asm",xl:"xl",tao:"xl",xml:"xml",html:"xml",xhtml:"xml",rss:"xml",atom:"xml",xjb:"xml",xsd:"xml",xsl:"xml",plist:"xml",xquery:"xquery",xpath:"xquery",xq:"xquery",yaml:"yaml",yml:"yaml",YAML:"yaml",zephir:"zephir",zep:"zephir"}},function(f,d,s){Object.defineProperty(d,"__esModule",{value:!0});var e={F8:119,F9:120,F10:121,F11:122,F12:123,B:66,I:73,H:72,U:85,D:68,M:77,Q:81,O:79,L:76,S:83,Z:90,Y:89,C:67,T:84,R:82,DELETE:8,TAB:9,ENTER:13,ONE:97,TWO:98,THREE:99,FOUR:100,FIVE:101,SIX:102,_ONE:49,_TWO:50,_THREE:51,_FOUR:52,_FIVE:53,_SIX:54};d.keydownListen=function(r){r.shortCut&&r.$el.addEventListener("keydown",function(n){if(n.ctrlKey||n.metaKey||n.altKey||n.shiftKey)if(!n.ctrlKey&&!n.metaKey||n.altKey||n.shiftKey)if((n.ctrlKey||n.metaKey)&&n.altKey&&!n.shiftKey)switch(n.keyCode){case e.S:n.preventDefault(),r.toolbar_left_click("superscript");break;case e.U:n.preventDefault(),r.toolbar_left_click("ul");break;case e.L:n.preventDefault(),r.toolbar_left_click("imagelink");break;case e.C:n.preventDefault(),r.toolbar_left_click("code");break;case e.T:n.preventDefault(),r.toolbar_left_click("table")}else if((n.ctrlKey||n.metaKey)&&n.shiftKey&&!n.altKey)switch(n.keyCode){case e.S:n.preventDefault(),r.toolbar_left_click("subscript");break;case e.D:n.preventDefault(),r.toolbar_left_click("strikethrough");break;case e.L:n.preventDefault(),r.toolbar_left_click("alignleft");break;case e.R:n.preventDefault(),r.toolbar_left_click("alignright");break;case e.C:n.preventDefault(),r.toolbar_left_click("aligncenter")}else!n.ctrlKey&&!n.metaKey&&n.shiftKey&&!n.altKey&&n.keyCode===e.TAB&&(r.$refs.toolbar_left.s_img_link_open||(n.preventDefault(),r.unInsertTab()));else switch(n.keyCode){case e.B:n.preventDefault(),r.toolbar_left_click("bold");break;case e.I:n.preventDefault(),r.toolbar_left_click("italic");break;case e.H:n.preventDefault(),r.toolbar_left_click("header");break;case e.U:n.preventDefault(),r.toolbar_left_click("underline");break;case e.D:n.preventDefault(),r.toolbar_left_click("removeLine");break;case e.M:n.preventDefault(),r.toolbar_left_click("mark");break;case e.Q:n.preventDefault(),r.toolbar_left_click("quote");break;case e.O:n.preventDefault(),r.toolbar_left_click("ol");break;case e.L:n.preventDefault(),r.toolbar_left_click("link");break;case e.S:n.preventDefault(),r.toolbar_left_click("save");break;case e.Z:n.preventDefault(),r.toolbar_left_click("undo");break;case e.Y:n.preventDefault(),r.toolbar_left_click("redo");break;case e.DELETE:n.preventDefault(),r.toolbar_left_click("trash");break;case e.ONE:n.preventDefault(),r.toolbar_left_click("header1");break;case e.TWO:n.preventDefault(),r.toolbar_left_click("header2");break;case e.THREE:n.preventDefault(),r.toolbar_left_click("header3");break;case e.FOUR:n.preventDefault(),r.toolbar_left_click("header4");break;case e.FIVE:n.preventDefault(),r.toolbar_left_click("header5");break;case e.SIX:n.preventDefault(),r.toolbar_left_click("header6");break;case e._ONE:n.preventDefault(),r.toolbar_left_click("header1");break;case e._TWO:n.preventDefault(),r.toolbar_left_click("header2");break;case e._THREE:n.preventDefault(),r.toolbar_left_click("header3");break;case e._FOUR:n.preventDefault(),r.toolbar_left_click("header4");break;case e._FIVE:n.preventDefault(),r.toolbar_left_click("header5");break;case e._SIX:n.preventDefault(),r.toolbar_left_click("header6")}else switch(n.keyCode){case e.F8:r.toolbars.navigation&&(n.preventDefault(),r.toolbar_right_click("navigation"));break;case e.F9:r.toolbars.preview&&(n.preventDefault(),r.toolbar_right_click("preview"));break;case e.F10:r.toolbars.fullscreen&&(n.preventDefault(),r.toolbar_right_click("fullscreen"));break;case e.F11:r.toolbars.readmodel&&(n.preventDefault(),r.toolbar_right_click("read"));break;case e.F12:r.toolbars.subfield&&(n.preventDefault(),r.toolbar_right_click("subfield"));break;case e.TAB:r.$refs.toolbar_left.s_img_link_open||(n.preventDefault(),r.insertTab());break;case e.ENTER:r.$refs.toolbar_left.s_img_link_open?(n.preventDefault(),r.$refs.toolbar_left.$imgLinkAdd()):r.insertEnter(n)}})}},function(f,d,s){function e(t){i&&r(t.tokens,["inline","html_block"])}function r(t,a){for(var u=void 0,c=void 0,o=0;o<t.length;o++)a.indexOf(t[o].type)!==-1&&(u=t[o].content,c=t[o].children,t[o].content=i.process(u),c&&c.length&&u!==t[o].content&&r(c,["html_inline"]))}Object.defineProperty(d,"__esModule",{value:!0}),d.default=function(t,a){t.options.html&&(i=new n.FilterXSS(a),t.core.ruler.push("mavoneditor_sanitizer",e))};var n=s(209),i=void 0},function(f,d,s){function e(F){return F&&F.__esModule?F:{default:F}}function r(){var F=new p(l),E=F.renderer.rules.link_open||function(q,O,P,D,z){return z.renderToken(q,O,P)};return F.renderer.rules.link_open=function(q,O,P,D,z){var $=q[O].attrIndex("href");if(q[O].attrs[$][1].startsWith("#"))return E(q,O,P,D,z);var R=q[O].attrIndex("target");return R<0?q[O].attrPush(["target","_blank"]):q[O].attrs[R][1]="_blank",E(q,O,P,D,z)},F.use(A,M).use(_).use(b).use(h).use(C).use(C,"hljs-left").use(C,"hljs-center").use(C,"hljs-right").use(m).use(g).use(w).use(v).use(x).use(C).use(L).use(T).use(k).use(y),F}Object.defineProperty(d,"__esModule",{value:!0});var n=s(18),i=e(n);d.initMarkdown=r;var t=s(71),a=e(t),u=s(38),c=s(73),o=e(c),l={html:!0,xhtmlOut:!0,breaks:!0,langPrefix:"lang-",linkify:!1,typographer:!0,quotes:"“”‘’"},p=s(137),_=s(121),h=s(133),b=s(134),m=s(120),g=s(118),w=s(127),v=s(130),x=s(132),k=s(135),C=s(119),y=s(136),A=s(128),T=s(131),L=s(129),j={},S=[],M={hljs:"auto",highlighted:!0,langCheck:function(F){F&&a.default[F]&&!j[F]&&(j[F]=1,S.push(a.default[F]))}};d.default={data:function(){return{markdownIt:null}},created:function(){this.markdownIt=r(),this.html?(0,i.default)(this.xssOptions)==="object"&&this.markdownIt.use(o.default,this.xssOptions):(this.markdownIt.set({html:!1}),this.xssOptions=!1)},mounted:function(){M.highlighted=this.ishljs},methods:{$render:function(F,E){var q=this;j={},S=[];var O=this.markdownIt.render(F);this.ishljs&&S.length>0&&q.$_render(F,E,O),E(O)},$_render:function(F,E,q){for(var O=this,P=0,D=0;D<S.length;D++){var z=O.p_external_link.hljs_lang(S[D]);(0,u.loadScript)(z,function(){(P+=1)===S.length&&(q=this.markdownIt.render(F),E(q))})}}},watch:{ishljs:function(F){M.highlighted=F}}}},function(f,d,s){function e(o){o.d_history_index>0&&o.d_history_index--,o.$nextTick(function(){var l=o.textarea_selectionEnds[o.d_history_index];o.getTextareaDom().selectionStart=l,o.getTextareaDom().selectionEnd=l}),o.getTextareaDom().focus()}function r(o){o.d_history_index<o.d_history.length-1&&o.d_history_index++,o.$nextTick(function(){var l=o.textarea_selectionEnds[o.d_history_index];o.getTextareaDom().selectionStart=l,o.getTextareaDom().selectionEnd=l}),o.getTextareaDom().focus()}function n(o){o.d_value="",o.getTextareaDom().focus()}function i(o){o.save(o.d_value,o.d_render)}function t(o){o.insertOl()}function a(o){o.insertUl()}function u(o){o.removeLine()}function c(o){o.insertCodeBlock()}Object.defineProperty(d,"__esModule",{value:!0}),d.toolbar_left_addlink=function(o,l,p,_){var h={prefix:o==="link"?"["+l+"](":"!["+l+"](",subfix:")",str:p};_.insertText(_.getTextareaDom(),h)},d.toolbar_left_click=function(o,l){var p={bold:{prefix:"**",subfix:"**",str:l.d_words.tl_bold},italic:{prefix:"*",subfix:"*",str:l.d_words.tl_italic},header:{prefix:"# ",subfix:"",str:l.d_words.tl_header},header1:{prefix:"# ",subfix:"",str:l.d_words.tl_header_one},header2:{prefix:"## ",subfix:"",str:l.d_words.tl_header_two},header3:{prefix:"### ",subfix:"",str:l.d_words.tl_header_three},header4:{prefix:"#### ",subfix:"",str:l.d_words.tl_header_four},header5:{prefix:"##### ",subfix:"",str:l.d_words.tl_header_five},header6:{prefix:"###### ",subfix:"",str:l.d_words.tl_header_six},underline:{prefix:"++",subfix:"++",str:l.d_words.tl_underline},strikethrough:{prefix:"~~",subfix:"~~",str:l.d_words.tl_strikethrough},mark:{prefix:"==",subfix:"==",str:l.d_words.tl_mark},superscript:{prefix:"^",subfix:"^",str:l.d_words.tl_superscript},subscript:{prefix:"~",subfix:"~",str:l.d_words.tl_subscript},quote:{prefix:"> ",subfix:"",str:l.d_words.tl_quote},link:{prefix:"[](",subfix:")",str:l.d_words.tl_link},imagelink:{prefix:"![](",subfix:")",str:l.d_words.tl_image},table:{prefix:"",subfix:"",str:`|column1|column2|column3|
|-|-|-|
|content1|content2|content3|
`},aligncenter:{prefix:`::: hljs-center

`,subfix:`

:::
`,str:l.d_words.tl_aligncenter},alignright:{prefix:`::: hljs-right

`,subfix:`

:::
`,str:l.d_words.tl_alignright},alignleft:{prefix:`::: hljs-left

`,subfix:`

:::
`,str:l.d_words.tl_alignleft}};p.hasOwnProperty(o)&&l.insertText(l.getTextareaDom(),p[o]);var _={undo:e,redo:r,trash:n,save:i,ol:t,ul:a,removeLine:u,code:c};_.hasOwnProperty(o)&&_[o](l)}},function(f,d,s){function e(c){c.s_html_code=!c.s_html_code,c.htmlcode&&c.htmlcode(c.s_html_code,c.d_value)}function r(c){c.s_help=!c.s_help,c.helptoggle&&c.helptoggle(c.s_help,c.d_value)}function n(c){var o=c.$refs.vReadModel;o.requestFullscreen?o.requestFullscreen():o.mozRequestFullScreen?o.mozRequestFullScreen():o.webkitRequestFullscreen?o.webkitRequestFullscreen():o.msRequestFullscreen&&o.msRequestFullscreen()}function i(c){c.s_preview_switch=!c.s_preview_switch,c.previewtoggle&&c.previewtoggle(c.s_preview_switch,c.d_value)}function t(c){c.s_fullScreen=!c.s_fullScreen,c.fullscreen&&c.fullscreen(c.s_fullScreen,c.d_value)}function a(c){c.s_subfield=!c.s_subfield,c.s_preview_switch=c.s_subfield,c.previewtoggle&&c.previewtoggle(c.s_preview_switch,c.d_value),c.subfieldtoggle&&c.subfieldtoggle(c.s_subfield,c.d_value)}function u(c){c.s_navigation=!c.s_navigation,c.s_navigation&&(c.s_preview_switch=!0),c.navigationtoggle&&c.navigationtoggle(c.s_navigation,c.d_value),c.s_navigation&&c.getNavigation(c,!1)}Object.defineProperty(d,"__esModule",{value:!0}),d.toolbar_right_click=function(c,o){var l={help:r,html:e,read:n,preview:i,fullscreen:t,navigation:u,subfield:a};l.hasOwnProperty(c)&&l[c](o)}},function(f,d,s){function e(a,u){for(var c in u){var o=(0,t.default)(u[c]);!a[c]||o!=="Object"&&o!=="object"?a[c]=u[c]:a[c]=e(a[c],u[c])}return a}function r(){var a=window.location.search,u={},c=/[?&][^?&]+=[^?&]+/g,o=a.match(c);return o&&o.forEach(function(l){var p=l.substring(1).split("="),_=decodeURIComponent(p[0]),h=decodeURIComponent(p[1]);u[_]=h}),u}function n(a){a&&(a.preventDefault&&a.preventDefault(),a.stopPropagation&&a.stopPropagation())}Object.defineProperty(d,"__esModule",{value:!0});var i=s(18),t=(function(a){return a&&a.__esModule?a:{default:a}})(i);d.p_ObjectCopy_DEEP=e,d.p_urlParse=r,d.stopEvent=n},function(f,d,s){f.exports={default:s(80),__esModule:!0}},function(f,d,s){f.exports={default:s(81),__esModule:!0}},function(f,d,s){s(103),s(101),s(104),s(105),f.exports=s(10).Symbol},function(f,d,s){s(102),s(106),f.exports=s(30).f("iterator")},function(f,d){f.exports=function(s){if(typeof s!="function")throw TypeError(s+" is not a function!");return s}},function(f,d){f.exports=function(){}},function(f,d,s){var e=s(6),r=s(99),n=s(98);f.exports=function(i){return function(t,a,u){var c,o=e(t),l=r(o.length),p=n(u,l);if(i&&a!=a){for(;l>p;)if((c=o[p++])!=c)return!0}else for(;l>p;p++)if((i||p in o)&&o[p]===a)return i||p||0;return!i&&-1}}},function(f,d,s){var e=s(82);f.exports=function(r,n,i){if(e(r),n===void 0)return r;switch(i){case 1:return function(t){return r.call(n,t)};case 2:return function(t,a){return r.call(n,t,a)};case 3:return function(t,a,u){return r.call(n,t,a,u)}}return function(){return r.apply(n,arguments)}}},function(f,d,s){var e=s(22),r=s(46),n=s(23);f.exports=function(i){var t=e(i),a=r.f;if(a)for(var u,c=a(i),o=n.f,l=0;c.length>l;)o.call(i,u=c[l++])&&t.push(u);return t}},function(f,d,s){var e=s(1).document;f.exports=e&&e.documentElement},function(f,d,s){var e=s(39);f.exports=Object("z").propertyIsEnumerable(0)?Object:function(r){return e(r)=="String"?r.split(""):Object(r)}},function(f,d,s){var e=s(39);f.exports=Array.isArray||function(r){return e(r)=="Array"}},function(f,d,s){var e=s(44),r=s(13),n=s(24),i={};s(4)(i,s(7)("iterator"),function(){return this}),f.exports=function(t,a,u){t.prototype=e(i,{next:r(1,u)}),n(t,a+" Iterator")}},function(f,d){f.exports=function(s,e){return{value:e,done:!!s}}},function(f,d,s){var e=s(14)("meta"),r=s(8),n=s(2),i=s(5).f,t=0,a=Object.isExtensible||function(){return!0},u=!s(11)(function(){return a(Object.preventExtensions({}))}),c=function(h){i(h,e,{value:{i:"O"+ ++t,w:{}}})},o=function(h,b){if(!r(h))return typeof h=="symbol"?h:(typeof h=="string"?"S":"P")+h;if(!n(h,e)){if(!a(h))return"F";if(!b)return"E";c(h)}return h[e].i},l=function(h,b){if(!n(h,e)){if(!a(h))return!0;if(!b)return!1;c(h)}return h[e].w},p=function(h){return u&&_.NEED&&a(h)&&!n(h,e)&&c(h),h},_=f.exports={KEY:e,NEED:!1,fastKey:o,getWeak:l,onFreeze:p}},function(f,d,s){var e=s(5),r=s(9),n=s(22);f.exports=s(3)?Object.defineProperties:function(i,t){r(i);for(var a,u=n(t),c=u.length,o=0;c>o;)e.f(i,a=u[o++],t[a]);return i}},function(f,d,s){var e=s(23),r=s(13),n=s(6),i=s(28),t=s(2),a=s(42),u=Object.getOwnPropertyDescriptor;d.f=s(3)?u:function(c,o){if(c=n(c),o=i(o,!0),a)try{return u(c,o)}catch{}if(t(c,o))return r(!e.f.call(c,o),c[o])}},function(f,d,s){var e=s(6),r=s(45).f,n={}.toString,i=typeof window=="object"&&window&&Object.getOwnPropertyNames?Object.getOwnPropertyNames(window):[],t=function(a){try{return r(a)}catch{return i.slice()}};f.exports.f=function(a){return i&&n.call(a)=="[object Window]"?t(a):r(e(a))}},function(f,d,s){var e=s(2),r=s(49),n=s(25)("IE_PROTO"),i=Object.prototype;f.exports=Object.getPrototypeOf||function(t){return t=r(t),e(t,n)?t[n]:typeof t.constructor=="function"&&t instanceof t.constructor?t.constructor.prototype:t instanceof Object?i:null}},function(f,d,s){var e=s(27),r=s(19);f.exports=function(n){return function(i,t){var a,u,c=String(r(i)),o=e(t),l=c.length;return o<0||o>=l?n?"":void 0:(a=c.charCodeAt(o),a<55296||a>56319||o+1===l||(u=c.charCodeAt(o+1))<56320||u>57343?n?c.charAt(o):a:n?c.slice(o,o+2):u-56320+(a-55296<<10)+65536)}}},function(f,d,s){var e=s(27),r=Math.max,n=Math.min;f.exports=function(i,t){return i=e(i),i<0?r(i+t,0):n(i,t)}},function(f,d,s){var e=s(27),r=Math.min;f.exports=function(n){return n>0?r(e(n),9007199254740991):0}},function(f,d,s){var e=s(83),r=s(91),n=s(21),i=s(6);f.exports=s(43)(Array,"Array",function(t,a){this._t=i(t),this._i=0,this._k=a},function(){var t=this._t,a=this._k,u=this._i++;return!t||u>=t.length?(this._t=void 0,r(1)):a=="keys"?r(0,u):a=="values"?r(0,t[u]):r(0,[u,t[u]])},"values"),n.Arguments=n.Array,e("keys"),e("values"),e("entries")},function(f,d){},function(f,d,s){var e=s(97)(!0);s(43)(String,"String",function(r){this._t=String(r),this._i=0},function(){var r,n=this._t,i=this._i;return i>=n.length?{value:void 0,done:!0}:(r=e(n,i),this._i+=r.length,{value:r,done:!1})})},function(f,d,s){var e=s(1),r=s(2),n=s(3),i=s(41),t=s(48),a=s(92).KEY,u=s(11),c=s(26),o=s(24),l=s(14),p=s(7),_=s(30),h=s(29),b=s(86),m=s(89),g=s(9),w=s(8),v=s(49),x=s(6),k=s(28),C=s(13),y=s(44),A=s(95),T=s(94),L=s(46),j=s(5),S=s(22),M=T.f,F=j.f,E=A.f,q=e.Symbol,O=e.JSON,P=O&&O.stringify,D=p("_hidden"),z=p("toPrimitive"),$={}.propertyIsEnumerable,R=c("symbol-registry"),B=c("symbols"),U=c("op-symbols"),H=Object.prototype,G=typeof q=="function"&&!!L.f,V=e.QObject,K=!V||!V.prototype||!V.prototype.findChild,Q=n&&u(function(){return y(F({},"a",{get:function(){return F(this,"a",{value:7}).a}})).a!=7})?function(I,N,W){var X=M(H,N);X&&delete H[N],F(I,N,W),X&&I!==H&&F(H,N,X)}:F,J=function(I){var N=B[I]=y(q.prototype);return N._k=I,N},Y=G&&typeof q.iterator=="symbol"?function(I){return typeof I=="symbol"}:function(I){return I instanceof q},Z=function(I,N,W){return I===H&&Z(U,N,W),g(I),N=k(N,!0),g(W),r(B,N)?(W.enumerable?(r(I,D)&&I[D][N]&&(I[D][N]=!1),W=y(W,{enumerable:C(0,!1)})):(r(I,D)||F(I,D,C(1,{})),I[D][N]=!0),Q(I,N,W)):F(I,N,W)},te=function(I,N){g(I);for(var W,X=b(N=x(N)),ee=0,oe=X.length;oe>ee;)Z(I,W=X[ee++],N[W]);return I},ne=function(I,N){return N===void 0?y(I):te(y(I),N)},re=function(I){var N=$.call(this,I=k(I,!0));return!(this===H&&r(B,I)&&!r(U,I))&&(!(N||!r(this,I)||!r(B,I)||r(this,D)&&this[D][I])||N)},ae=function(I,N){if(I=x(I),N=k(N,!0),I!==H||!r(B,N)||r(U,N)){var W=M(I,N);return!W||!r(B,N)||r(I,D)&&I[D][N]||(W.enumerable=!0),W}},se=function(I){for(var N,W=E(x(I)),X=[],ee=0;W.length>ee;)r(B,N=W[ee++])||N==D||N==a||X.push(N);return X},le=function(I){for(var N,W=I===H,X=E(W?U:x(I)),ee=[],oe=0;X.length>oe;)!r(B,N=X[oe++])||W&&!r(H,N)||ee.push(B[N]);return ee};G||(q=function(){if(this instanceof q)throw TypeError("Symbol is not a constructor!");var I=l(arguments.length>0?arguments[0]:void 0),N=function(W){this===H&&N.call(U,W),r(this,D)&&r(this[D],I)&&(this[D][I]=!1),Q(this,I,C(1,W))};return n&&K&&Q(H,I,{configurable:!0,set:N}),J(I)},t(q.prototype,"toString",function(){return this._k}),T.f=ae,j.f=Z,s(45).f=A.f=se,s(23).f=re,L.f=le,n&&!s(12)&&t(H,"propertyIsEnumerable",re,!0),_.f=function(I){return J(p(I))}),i(i.G+i.W+i.F*!G,{Symbol:q});for(var ce="hasInstance,isConcatSpreadable,iterator,match,replace,search,species,split,toPrimitive,toStringTag,unscopables".split(","),ue=0;ce.length>ue;)p(ce[ue++]);for(var pe=S(p.store),fe=0;pe.length>fe;)h(pe[fe++]);i(i.S+i.F*!G,"Symbol",{for:function(I){return r(R,I+="")?R[I]:R[I]=q(I)},keyFor:function(I){if(!Y(I))throw TypeError(I+" is not a symbol!");for(var N in R)if(R[N]===I)return N},useSetter:function(){K=!0},useSimple:function(){K=!1}}),i(i.S+i.F*!G,"Object",{create:ne,defineProperty:Z,defineProperties:te,getOwnPropertyDescriptor:ae,getOwnPropertyNames:se,getOwnPropertySymbols:le});var de=u(function(){L.f(1)});i(i.S+i.F*de,"Object",{getOwnPropertySymbols:function(I){return L.f(v(I))}}),O&&i(i.S+i.F*(!G||u(function(){var I=q();return P([I])!="[null]"||P({a:I})!="{}"||P(Object(I))!="{}"})),"JSON",{stringify:function(I){for(var N,W,X=[I],ee=1;arguments.length>ee;)X.push(arguments[ee++]);if(W=N=X[1],(w(N)||I!==void 0)&&!Y(I))return m(N)||(N=function(oe,ie){if(typeof W=="function"&&(ie=W.call(this,oe,ie)),!Y(ie))return ie}),X[1]=N,P.apply(O,X)}}),q.prototype[z]||s(4)(q.prototype,z,q.prototype.valueOf),o(q,"Symbol"),o(Math,"Math",!0),o(e.JSON,"JSON",!0)},function(f,d,s){s(29)("asyncIterator")},function(f,d,s){s(29)("observable")},function(f,d,s){s(100);for(var e=s(1),r=s(4),n=s(21),i=s(7)("toStringTag"),t="CSSRuleList,CSSStyleDeclaration,CSSValueList,ClientRectList,DOMRectList,DOMStringList,DOMTokenList,DataTransferItemList,FileList,HTMLAllCollection,HTMLCollection,HTMLFormElement,HTMLSelectElement,MediaList,MimeTypeArray,NamedNodeMap,NodeList,PaintRequestList,Plugin,PluginArray,SVGLengthList,SVGNumberList,SVGPathSegList,SVGPointList,SVGStringList,SVGTransformList,SourceBufferList,StyleSheetList,TextTrackCueList,TextTrackList,TouchList".split(","),a=0;a<t.length;a++){var u=t[a],c=e[u],o=c&&c.prototype;o&&!o[i]&&r(o,i,u),n[u]=n.Array}},function(f,d,s){d=f.exports=s(15)(!1),d.push([f.i,`
.auto-textarea-wrapper {
  position: relative;
  width: 100%;
  margin: 0;
  padding: 0;
  line-height: normal;
}
.auto-textarea-wrapper .auto-textarea-block {
  display: block;
  white-space: pre-wrap;
  word-wrap: break-word !important;
  visibility: hidden;
  overflow: hidden;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-size: 100%;
}
.auto-textarea-wrapper .auto-textarea-input {
  font-family: Menlo, "Ubuntu Mono", Consolas, "Courier New", "Microsoft Yahei", "Hiragino Sans GB", "WenQuanYi Micro Hei", sans-serif;
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  margin: 0;
  padding: 0;
  overflow-y: hidden;
  color: #2c3e50;
}
.auto-textarea-wrapper .auto-textarea-input.no-border {
  outline: 0 none;
  border: none !important;
}
.auto-textarea-wrapper .auto-textarea-input.no-resize {
  resize: none;
}
`,""])},function(f,d,s){d=f.exports=s(15)(!1),d.push([f.i,`
.op-icon.dropdown-wrapper.dropdown[data-v-548e2160] {
  position: relative;
}
.op-icon.dropdown-wrapper.dropdown[type=button][data-v-548e2160] {
  -webkit-appearance: unset;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown[data-v-548e2160] {
  position: absolute;
  display: block;
  background: #fff;
  top: 32px;
  left: -45px;
  min-width: 130px;
  z-index: 1600;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0,0,0,0.1);
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown .dropdown-item[data-v-548e2160]:first-child {
  border-top-left-radius: 3px;
  border-top-right-radius: 3px;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown .dropdown-item[data-v-548e2160]:last-child {
  border-bottom-left-radius: 3px;
  border-bottom-right-radius: 3px;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.op-header[data-v-548e2160] {
  left: -30px;
  min-width: 90px;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-enter-active[data-v-548e2160],
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-leave-active[data-v-548e2160] {
  opacity: 1;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-enter[data-v-548e2160],
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-leave-active[data-v-548e2160] {
  opacity: 0;
}
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.transition[data-v-548e2160],
.op-icon.dropdown-wrapper.dropdown .popup-dropdown.transition .dropdown-item[data-v-548e2160] {
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-item[data-v-548e2160] {
  height: 40px;
  line-height: 40px;
  font-size: 14px;
  color: #606266;
  position: relative;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-item[data-v-548e2160]:hover {
  color: #303133;
  background-color: #e9e9eb;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-item input[data-v-548e2160] {
  position: absolute;
  font-size: 100px;
  right: 0;
  top: 0;
  opacity: 0;
  cursor: pointer;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images[data-v-548e2160] {
  box-sizing: border-box;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images button[data-v-548e2160] {
  position: absolute;
  top: -1px;
  right: 5px;
  font-size: 14px;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images button[data-v-548e2160]:hover {
  color: #f56c6c;
  background-color: transparent;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images span[data-v-548e2160] {
  display: inline-block;
  width: 80px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images:hover .image-show[data-v-548e2160] {
  display: block !important;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images .image-show[data-v-548e2160] {
  display: none;
  position: absolute;
  left: -128px;
  top: 0;
  width: 120px;
  height: 90px;
  object-fit: contain;
  border: 1px solid #f2f6fc;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images .image-show.transition[data-v-548e2160] {
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.op-icon.dropdown-wrapper.dropdown .dropdown-images.transition[data-v-548e2160] {
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.add-image-link-wrapper[data-v-548e2160] {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 1600;
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
}
.add-image-link-wrapper.fade-enter-active[data-v-548e2160],
.add-image-link-wrapper.fade-leave-active[data-v-548e2160] {
  opacity: 1;
}
.add-image-link-wrapper.fade-enter[data-v-548e2160],
.add-image-link-wrapper.fade-leave-active[data-v-548e2160] {
  opacity: 0;
}
.add-image-link-wrapper .add-image-link[data-v-548e2160] {
  position: fixed;
  box-sizing: border-box;
  text-align: center;
  width: 24%;
  left: 38%;
  height: auto;
  padding: 40px;
  top: 25%;
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
  z-index: 3;
  background: #fff;
  border-radius: 2px;
}
@media only screen and (max-width: 1500px) {
.add-image-link-wrapper .add-image-link[data-v-548e2160] {
    width: 34%;
    left: 33%;
}
}
@media only screen and (max-width: 1000px) {
.add-image-link-wrapper .add-image-link[data-v-548e2160] {
    width: 50%;
    left: 25%;
}
}
@media only screen and (max-width: 600px) {
.add-image-link-wrapper .add-image-link[data-v-548e2160] {
    width: 80%;
    left: 10%;
}
}
.add-image-link-wrapper .add-image-link i[data-v-548e2160] {
  font-size: 24px;
  position: absolute;
  right: 8px;
  top: 6px;
  color: rgba(0,0,0,0.7);
  cursor: pointer;
}
.add-image-link-wrapper .add-image-link .title[data-v-548e2160] {
  font-size: 20px;
  margin-bottom: 30px;
  margin-top: 10px;
  font-weight: 500 !important;
}
.add-image-link-wrapper .add-image-link .input-wrapper[data-v-548e2160] {
  margin-top: 10px;
  width: 80%;
  border: 1px solid #eeece8;
  text-align: left;
  margin-left: 10%;
  height: 35px;
}
.add-image-link-wrapper .add-image-link .input-wrapper input[data-v-548e2160] {
  height: 32px;
  line-height: 32px;
  font-size: 15px;
  width: 90%;
  margin-left: 8px;
  border: none;
  outline: none;
}
.add-image-link-wrapper .add-image-link .op-btn[data-v-548e2160] {
  width: 100px;
  height: 35px;
  display: inline-block;
  margin-top: 30px;
  cursor: pointer;
  text-align: center;
  line-height: 35px;
  opacity: 0.9;
  border-radius: 2px;
  letter-spacing: 1px;
  font-size: 15px;
}
.add-image-link-wrapper .add-image-link .op-btn.sure[data-v-548e2160] {
  background: #2185d0;
  color: #fff;
  margin-left: 5%;
}
.add-image-link-wrapper .add-image-link .op-btn.sure[data-v-548e2160]:hover {
  opacity: 1;
}
.add-image-link-wrapper .add-image-link .op-btn.cancel[data-v-548e2160] {
  border: 1px solid #bcbcbc;
  color: #bcbcbc;
}
.add-image-link-wrapper .add-image-link .op-btn.cancel[data-v-548e2160]:hover {
  color: #000;
}
`,""])},function(f,d,s){d=f.exports=s(15)(!1),d.push([f.i,`
textarea:disabled {
  background-color: #fff;
}
.v-note-wrapper {
  position: relative;
  min-width: 300px;
  min-height: 300px;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column;
  background-color: #fff;
  z-index: 1500;
  text-align: left;
  border: 1px solid #f2f6fc;
  border-radius: 4px;
}
.v-note-wrapper.fullscreen {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  top: 0;
  margin: 0;
  height: auto;
  z-index: 1501;
}
.v-note-wrapper .v-note-op {
  padding: 1px;
  width: 100%;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  white-space: pre-line;
  -webkit-box-flex: 0;
  -webkit-flex: none;
      -ms-flex: none;
          flex: none;
  min-height: 40px;
  -webkit-user-select: none;
      -ms-user-select: none;
          user-select: none;
  border-bottom: 1px solid #f2f6fc;
  border-radius: 4px 4px 0 0;
  background-color: #fff;
  z-index: 1;
}
.v-note-wrapper .v-note-op .v-left-item,
.v-note-wrapper .v-note-op .v-right-item {
  -webkit-box-flex: 1;
  -webkit-flex: 1;
      -ms-flex: 1;
          flex: 1;
  min-height: 40px;
  box-sizing: border-box;
}
.v-note-wrapper .v-note-op .v-left-item .op-icon-divider,
.v-note-wrapper .v-note-op .v-right-item .op-icon-divider {
  height: 40px;
  border-left: 1px solid #e5e5e5;
  margin: 0 6px 0 4px;
}
.v-note-wrapper .v-note-op .v-left-item .op-icon,
.v-note-wrapper .v-note-op .v-right-item .op-icon {
  box-sizing: border-box;
  display: inline-block;
  cursor: pointer;
  height: 28px;
  width: 28px;
  margin: 6px 0 5px 0px;
  font-size: 14px;
  padding: 4.5px 6px 5px 3.5px;
  color: #757575;
  border-radius: 5px;
  text-align: center;
  background: none;
  border: none;
  outline: none;
  line-height: 1;
}
.v-note-wrapper .v-note-op .v-left-item .op-icon.dropdown-wrapper,
.v-note-wrapper .v-note-op .v-right-item .op-icon.dropdown-wrapper {
  line-height: 18px;
}
.v-note-wrapper .v-note-op .v-left-item .op-icon.selected,
.v-note-wrapper .v-note-op .v-right-item .op-icon.selected {
  color: rgba(0,0,0,0.8);
  background: #eaeaea;
}
.v-note-wrapper .v-note-op .v-left-item .op-icon:hover,
.v-note-wrapper .v-note-op .v-right-item .op-icon:hover {
  color: rgba(0,0,0,0.8);
  background: #e9e9eb;
}
.v-note-wrapper .v-note-op .v-left-item.transition .op-icon,
.v-note-wrapper .v-note-op .v-right-item.transition .op-icon {
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.v-note-wrapper .v-note-op .v-right-item {
  text-align: right;
  padding-right: 6px;
  max-width: 30%;
}
.v-note-wrapper .v-note-op .v-left-item {
  text-align: left;
  padding-left: 6px;
}
.v-note-wrapper .v-note-panel {
  position: relative;
  border-top: none;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-flex: 1;
  -webkit-flex: 1;
      -ms-flex: 1;
          flex: 1;
  width: 100%;
  box-sizing: border-box;
  overflow: hidden;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper {
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 50%;
      -ms-flex: 0 0 50%;
          flex: 0 0 50%;
  width: 50%;
  padding: 0;
  overflow-y: scroll;
  overflow-x: hidden;
  box-sizing: border-box;
  cursor: text;
  border-bottom-left-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar {
  width: 6px;
  background-color: #e5e5e5;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb {
  background-color: #b7b7b7;
  border-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb:hover {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb:active {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-track {
  -webkit-box-shadow: 0 0 0px #808080 inset;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style-border-radius::-webkit-scrollbar {
  border-bottom-right-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.transition {
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.single-edit {
  width: 100%;
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 100%;
      -ms-flex: 0 0 100%;
          flex: 0 0 100%;
  overflow-y: auto;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.single-show {
  width: 0;
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 0;
      -ms-flex: 0 0 0px;
          flex: 0 0 0;
  display: none;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper .content-div {
  width: 100%;
  padding: 20px 25px;
  box-sizing: border-box;
  outline: 0 none;
  border: none !important;
  color: #2c3e50;
  font-size: 16px;
}
.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper .content-input-wrapper {
  width: 100%;
  padding: 8px 25px 15px 25px;
}
.v-note-wrapper .v-note-panel .v-note-show {
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 50%;
      -ms-flex: 0 0 50%;
          flex: 0 0 50%;
  width: 50%;
  overflow-y: auto;
  padding: 0 0;
  -webkit-transition: all 0.2s linear 0s;
  transition: all 0.2s linear 0s;
}
.v-note-wrapper .v-note-panel .v-note-show.single-show {
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 100%;
      -ms-flex: 0 0 100%;
          flex: 0 0 100%;
  width: 100%;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html {
  width: 100%;
  height: 100%;
  padding: 8px 25px 15px 25px;
  overflow-y: auto;
  box-sizing: border-box;
  overflow-x: hidden;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar {
  width: 6px;
  background-color: #e5e5e5;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb {
  background-color: #b7b7b7;
  border-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb:hover,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb:hover {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb:active,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb:active {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-track,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-track {
  -webkit-box-shadow: 0 0 0px #808080 inset;
}
.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style-border-radius::-webkit-scrollbar,
.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style-border-radius::-webkit-scrollbar {
  border-bottom-right-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  position: absolute;
  width: 250px;
  right: 0;
  top: 0;
  bottom: 0;
  height: 100%;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column;
  background-color: rgba(255,255,255,0.98);
  border-left: 1px solid #f2f6fc;
  border-right: 1px solid #f2f6fc;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.transition {
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
}
@media only screen and (max-width: 768px) {
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper {
    width: 50%;
}
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-enter-active,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-leave-active {
  height: 100%;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-enter,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-leave-active {
  height: 0;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title {
  height: 50px;
  width: 100%;
  border-bottom: 1px solid #f2f6fc;
  -webkit-box-flex: 0;
  -webkit-flex: none;
      -ms-flex: none;
          flex: none;
  line-height: 50px;
  font-size: 16px;
  box-sizing: border-box;
  padding: 0 12px 0 18px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title .v-note-navigation-close {
  float: right;
  color: #606266;
  font-size: 18px;
  cursor: pointer;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title .v-note-navigation-close:hover {
  color: #303133;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content {
  overflow-y: auto;
  -webkit-box-flex: 1;
  -webkit-flex: 1;
      -ms-flex: 1;
          flex: 1;
  padding: 8px 0;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar {
  width: 6px;
  background-color: #e5e5e5;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb {
  background-color: #b7b7b7;
  border-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb:hover {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb:active {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-track {
  -webkit-box-shadow: 0 0 0px #808080 inset;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style-border-radius::-webkit-scrollbar {
  border-bottom-right-radius: 4px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h1,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6 {
  margin: 2px 0;
  font-weight: 500;
  font-size: 17px;
  color: #2185d0;
  cursor: pointer;
  line-height: normal;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 0 12px;
  border-bottom: none;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h1:hover,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2:hover,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3:hover,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4:hover,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5:hover,
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6:hover {
  color: #483d8b;
  -webkit-text-decoration-line: underline;
          text-decoration-line: underline;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2 {
  padding-left: 27px;
  font-size: 17px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3 {
  padding-left: 42px;
  font-size: 17px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4 {
  padding-left: 58px;
  font-size: 15px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5 {
  padding-left: 72px;
  font-size: 15px;
}
.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6 {
  padding-left: 87px;
  font-size: 15px;
}
.v-note-wrapper .v-note-read-model {
  position: relative;
  display: none;
  width: 100%;
  height: 100%;
  background: #fbfbfb;
  padding: 30px 8% 50px 8%;
  overflow-y: auto;
  box-sizing: border-box;
}
.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar {
  width: 6px;
  background-color: #e5e5e5;
}
.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb {
  background-color: #b7b7b7;
  border-radius: 4px;
}
.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb:hover {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb:active {
  background-color: #a1a1a1;
}
.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-track {
  -webkit-box-shadow: 0 0 0px #808080 inset;
}
.v-note-wrapper .v-note-read-model.scroll-style-border-radius::-webkit-scrollbar {
  border-bottom-right-radius: 4px;
}
.v-note-wrapper .v-note-read-model.show {
  display: block;
}
.v-note-wrapper.shadow {
  border: none;
}
.v-note-help-wrapper {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 1600;
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
}
.v-note-help-wrapper.fade-enter-active,
.v-note-help-wrapper.fade-leave-active {
  opacity: 1;
}
.v-note-help-wrapper.fade-enter,
.v-note-help-wrapper.fade-leave-active {
  opacity: 0;
}
.v-note-help-wrapper .v-note-help-content {
  position: relative;
  width: 60%;
  max-width: 800px;
  margin: 30px auto;
  height: 90%;
  min-width: 320px;
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
  z-index: 3;
  border: 1px solid #f2f6fc;
}
.v-note-help-wrapper .v-note-help-content.shadow {
  border: none;
  box-shadow: 0 0px 5px rgba(0,0,0,0.157), 0 0px 5px rgba(0,0,0,0.227);
}
.v-note-help-wrapper .v-note-help-content i {
  font-size: 28px;
  position: absolute;
  right: 15px;
  top: 8px;
  color: rgba(0,0,0,0.7);
  cursor: pointer;
}
.v-note-help-wrapper .v-note-help-content i:hover {
  color: #000;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show {
  width: 100%;
  height: 100%;
  font-size: 18px;
  background: #fbfbfb;
  overflow-y: auto;
  padding: 2% 6%;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar {
  width: 6px;
  background-color: #e5e5e5;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb {
  background-color: #b7b7b7;
  border-radius: 4px;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb:hover {
  background-color: #a1a1a1;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb:active {
  background-color: #a1a1a1;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-track {
  -webkit-box-shadow: 0 0 0px #808080 inset;
}
.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style-border-radius::-webkit-scrollbar {
  border-bottom-right-radius: 4px;
}
.v-note-img-wrapper {
  position: fixed;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: center;
  -webkit-justify-content: center;
      -ms-flex-pack: center;
          justify-content: center;
  -webkit-box-align: center;
  -webkit-align-items: center;
      -ms-flex-align: center;
          align-items: center;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 1600;
  -webkit-transition: all 0.1s linear 0s;
  transition: all 0.1s linear 0s;
}
.v-note-img-wrapper.fade-enter-active,
.v-note-img-wrapper.fade-leave-active {
  opacity: 1;
}
.v-note-img-wrapper.fade-enter,
.v-note-img-wrapper.fade-leave-active {
  opacity: 0;
}
.v-note-img-wrapper img {
  -webkit-box-flex: 0;
  -webkit-flex: 0 0 auto;
      -ms-flex: 0 0 auto;
          flex: 0 0 auto;
  z-index: 3;
}
.v-note-img-wrapper i {
  font-size: 28px;
  position: absolute;
  right: 15px;
  top: 8px;
  color: rgba(255,255,255,0.7);
  cursor: pointer;
}
.v-note-img-wrapper i:hover {
  color: #fff;
}
`,""])},function(f,d,s){d=f.exports=s(15)(!1),d.push([f.i,`
.auto-textarea-wrapper[data-v-7a63e4b3] {
    height: 100%;
}
`,""])},function(f,d,s){function e(a){return a==null}function r(a){var u={};for(var c in a)u[c]=a[c];return u}function n(a){a=r(a||{}),a.whiteList=a.whiteList||i.whiteList,a.onAttr=a.onAttr||i.onAttr,a.onIgnoreAttr=a.onIgnoreAttr||i.onIgnoreAttr,a.safeAttrValue=a.safeAttrValue||i.safeAttrValue,this.options=a}var i=s(50),t=s(112);s(51),n.prototype.process=function(a){if(a=a||"",!(a=a.toString()))return"";var u=this,c=u.options,o=c.whiteList,l=c.onAttr,p=c.onIgnoreAttr,_=c.safeAttrValue;return t(a,function(h,b,m,g,w){var v=o[m],x=!1;if(v===!0?x=v:typeof v=="function"?x=v(g):v instanceof RegExp&&(x=v.test(g)),x!==!0&&(x=!1),g=_(m,g)){var k={position:b,sourcePosition:h,source:w,isWhite:x};if(x){var C=l(m,g,k);return e(C)?m+":"+g:C}var C=p(m,g,k);return e(C)?void 0:C}})},f.exports=n},function(f,d,s){function e(n,i){function t(){if(!u){var h=r.trim(n.slice(c,o)),b=h.indexOf(":");if(b!==-1){var m=r.trim(h.slice(0,b)),g=r.trim(h.slice(b+1));if(m){var w=i(c,l.length,m,g,h);w&&(l+=w+"; ")}}}c=o+1}n=r.trimRight(n),n[n.length-1]!==";"&&(n+=";");for(var a=n.length,u=!1,c=0,o=0,l="";o<a;o++){var p=n[o];if(p==="/"&&n[o+1]==="*"){var _=n.indexOf("*/",o+2);if(_===-1)break;o=_+1,c=o+1,u=!1}else p==="("?u=!0:p===")"?u=!1:p===";"?u||t():p===`
`&&t()}return r.trim(l)}var r=s(51);f.exports=e},function(f,d){f.exports={Aacute:"Á",aacute:"á",Abreve:"Ă",abreve:"ă",ac:"∾",acd:"∿",acE:"∾̳",Acirc:"Â",acirc:"â",acute:"´",Acy:"А",acy:"а",AElig:"Æ",aelig:"æ",af:"⁡",Afr:"𝔄",afr:"𝔞",Agrave:"À",agrave:"à",alefsym:"ℵ",aleph:"ℵ",Alpha:"Α",alpha:"α",Amacr:"Ā",amacr:"ā",amalg:"⨿",amp:"&",AMP:"&",andand:"⩕",And:"⩓",and:"∧",andd:"⩜",andslope:"⩘",andv:"⩚",ang:"∠",ange:"⦤",angle:"∠",angmsdaa:"⦨",angmsdab:"⦩",angmsdac:"⦪",angmsdad:"⦫",angmsdae:"⦬",angmsdaf:"⦭",angmsdag:"⦮",angmsdah:"⦯",angmsd:"∡",angrt:"∟",angrtvb:"⊾",angrtvbd:"⦝",angsph:"∢",angst:"Å",angzarr:"⍼",Aogon:"Ą",aogon:"ą",Aopf:"𝔸",aopf:"𝕒",apacir:"⩯",ap:"≈",apE:"⩰",ape:"≊",apid:"≋",apos:"'",ApplyFunction:"⁡",approx:"≈",approxeq:"≊",Aring:"Å",aring:"å",Ascr:"𝒜",ascr:"𝒶",Assign:"≔",ast:"*",asymp:"≈",asympeq:"≍",Atilde:"Ã",atilde:"ã",Auml:"Ä",auml:"ä",awconint:"∳",awint:"⨑",backcong:"≌",backepsilon:"϶",backprime:"‵",backsim:"∽",backsimeq:"⋍",Backslash:"∖",Barv:"⫧",barvee:"⊽",barwed:"⌅",Barwed:"⌆",barwedge:"⌅",bbrk:"⎵",bbrktbrk:"⎶",bcong:"≌",Bcy:"Б",bcy:"б",bdquo:"„",becaus:"∵",because:"∵",Because:"∵",bemptyv:"⦰",bepsi:"϶",bernou:"ℬ",Bernoullis:"ℬ",Beta:"Β",beta:"β",beth:"ℶ",between:"≬",Bfr:"𝔅",bfr:"𝔟",bigcap:"⋂",bigcirc:"◯",bigcup:"⋃",bigodot:"⨀",bigoplus:"⨁",bigotimes:"⨂",bigsqcup:"⨆",bigstar:"★",bigtriangledown:"▽",bigtriangleup:"△",biguplus:"⨄",bigvee:"⋁",bigwedge:"⋀",bkarow:"⤍",blacklozenge:"⧫",blacksquare:"▪",blacktriangle:"▴",blacktriangledown:"▾",blacktriangleleft:"◂",blacktriangleright:"▸",blank:"␣",blk12:"▒",blk14:"░",blk34:"▓",block:"█",bne:"=⃥",bnequiv:"≡⃥",bNot:"⫭",bnot:"⌐",Bopf:"𝔹",bopf:"𝕓",bot:"⊥",bottom:"⊥",bowtie:"⋈",boxbox:"⧉",boxdl:"┐",boxdL:"╕",boxDl:"╖",boxDL:"╗",boxdr:"┌",boxdR:"╒",boxDr:"╓",boxDR:"╔",boxh:"─",boxH:"═",boxhd:"┬",boxHd:"╤",boxhD:"╥",boxHD:"╦",boxhu:"┴",boxHu:"╧",boxhU:"╨",boxHU:"╩",boxminus:"⊟",boxplus:"⊞",boxtimes:"⊠",boxul:"┘",boxuL:"╛",boxUl:"╜",boxUL:"╝",boxur:"└",boxuR:"╘",boxUr:"╙",boxUR:"╚",boxv:"│",boxV:"║",boxvh:"┼",boxvH:"╪",boxVh:"╫",boxVH:"╬",boxvl:"┤",boxvL:"╡",boxVl:"╢",boxVL:"╣",boxvr:"├",boxvR:"╞",boxVr:"╟",boxVR:"╠",bprime:"‵",breve:"˘",Breve:"˘",brvbar:"¦",bscr:"𝒷",Bscr:"ℬ",bsemi:"⁏",bsim:"∽",bsime:"⋍",bsolb:"⧅",bsol:"\\",bsolhsub:"⟈",bull:"•",bullet:"•",bump:"≎",bumpE:"⪮",bumpe:"≏",Bumpeq:"≎",bumpeq:"≏",Cacute:"Ć",cacute:"ć",capand:"⩄",capbrcup:"⩉",capcap:"⩋",cap:"∩",Cap:"⋒",capcup:"⩇",capdot:"⩀",CapitalDifferentialD:"ⅅ",caps:"∩︀",caret:"⁁",caron:"ˇ",Cayleys:"ℭ",ccaps:"⩍",Ccaron:"Č",ccaron:"č",Ccedil:"Ç",ccedil:"ç",Ccirc:"Ĉ",ccirc:"ĉ",Cconint:"∰",ccups:"⩌",ccupssm:"⩐",Cdot:"Ċ",cdot:"ċ",cedil:"¸",Cedilla:"¸",cemptyv:"⦲",cent:"¢",centerdot:"·",CenterDot:"·",cfr:"𝔠",Cfr:"ℭ",CHcy:"Ч",chcy:"ч",check:"✓",checkmark:"✓",Chi:"Χ",chi:"χ",circ:"ˆ",circeq:"≗",circlearrowleft:"↺",circlearrowright:"↻",circledast:"⊛",circledcirc:"⊚",circleddash:"⊝",CircleDot:"⊙",circledR:"®",circledS:"Ⓢ",CircleMinus:"⊖",CirclePlus:"⊕",CircleTimes:"⊗",cir:"○",cirE:"⧃",cire:"≗",cirfnint:"⨐",cirmid:"⫯",cirscir:"⧂",ClockwiseContourIntegral:"∲",CloseCurlyDoubleQuote:"”",CloseCurlyQuote:"’",clubs:"♣",clubsuit:"♣",colon:":",Colon:"∷",Colone:"⩴",colone:"≔",coloneq:"≔",comma:",",commat:"@",comp:"∁",compfn:"∘",complement:"∁",complexes:"ℂ",cong:"≅",congdot:"⩭",Congruent:"≡",conint:"∮",Conint:"∯",ContourIntegral:"∮",copf:"𝕔",Copf:"ℂ",coprod:"∐",Coproduct:"∐",copy:"©",COPY:"©",copysr:"℗",CounterClockwiseContourIntegral:"∳",crarr:"↵",cross:"✗",Cross:"⨯",Cscr:"𝒞",cscr:"𝒸",csub:"⫏",csube:"⫑",csup:"⫐",csupe:"⫒",ctdot:"⋯",cudarrl:"⤸",cudarrr:"⤵",cuepr:"⋞",cuesc:"⋟",cularr:"↶",cularrp:"⤽",cupbrcap:"⩈",cupcap:"⩆",CupCap:"≍",cup:"∪",Cup:"⋓",cupcup:"⩊",cupdot:"⊍",cupor:"⩅",cups:"∪︀",curarr:"↷",curarrm:"⤼",curlyeqprec:"⋞",curlyeqsucc:"⋟",curlyvee:"⋎",curlywedge:"⋏",curren:"¤",curvearrowleft:"↶",curvearrowright:"↷",cuvee:"⋎",cuwed:"⋏",cwconint:"∲",cwint:"∱",cylcty:"⌭",dagger:"†",Dagger:"‡",daleth:"ℸ",darr:"↓",Darr:"↡",dArr:"⇓",dash:"‐",Dashv:"⫤",dashv:"⊣",dbkarow:"⤏",dblac:"˝",Dcaron:"Ď",dcaron:"ď",Dcy:"Д",dcy:"д",ddagger:"‡",ddarr:"⇊",DD:"ⅅ",dd:"ⅆ",DDotrahd:"⤑",ddotseq:"⩷",deg:"°",Del:"∇",Delta:"Δ",delta:"δ",demptyv:"⦱",dfisht:"⥿",Dfr:"𝔇",dfr:"𝔡",dHar:"⥥",dharl:"⇃",dharr:"⇂",DiacriticalAcute:"´",DiacriticalDot:"˙",DiacriticalDoubleAcute:"˝",DiacriticalGrave:"`",DiacriticalTilde:"˜",diam:"⋄",diamond:"⋄",Diamond:"⋄",diamondsuit:"♦",diams:"♦",die:"¨",DifferentialD:"ⅆ",digamma:"ϝ",disin:"⋲",div:"÷",divide:"÷",divideontimes:"⋇",divonx:"⋇",DJcy:"Ђ",djcy:"ђ",dlcorn:"⌞",dlcrop:"⌍",dollar:"$",Dopf:"𝔻",dopf:"𝕕",Dot:"¨",dot:"˙",DotDot:"⃜",doteq:"≐",doteqdot:"≑",DotEqual:"≐",dotminus:"∸",dotplus:"∔",dotsquare:"⊡",doublebarwedge:"⌆",DoubleContourIntegral:"∯",DoubleDot:"¨",DoubleDownArrow:"⇓",DoubleLeftArrow:"⇐",DoubleLeftRightArrow:"⇔",DoubleLeftTee:"⫤",DoubleLongLeftArrow:"⟸",DoubleLongLeftRightArrow:"⟺",DoubleLongRightArrow:"⟹",DoubleRightArrow:"⇒",DoubleRightTee:"⊨",DoubleUpArrow:"⇑",DoubleUpDownArrow:"⇕",DoubleVerticalBar:"∥",DownArrowBar:"⤓",downarrow:"↓",DownArrow:"↓",Downarrow:"⇓",DownArrowUpArrow:"⇵",DownBreve:"̑",downdownarrows:"⇊",downharpoonleft:"⇃",downharpoonright:"⇂",DownLeftRightVector:"⥐",DownLeftTeeVector:"⥞",DownLeftVectorBar:"⥖",DownLeftVector:"↽",DownRightTeeVector:"⥟",DownRightVectorBar:"⥗",DownRightVector:"⇁",DownTeeArrow:"↧",DownTee:"⊤",drbkarow:"⤐",drcorn:"⌟",drcrop:"⌌",Dscr:"𝒟",dscr:"𝒹",DScy:"Ѕ",dscy:"ѕ",dsol:"⧶",Dstrok:"Đ",dstrok:"đ",dtdot:"⋱",dtri:"▿",dtrif:"▾",duarr:"⇵",duhar:"⥯",dwangle:"⦦",DZcy:"Џ",dzcy:"џ",dzigrarr:"⟿",Eacute:"É",eacute:"é",easter:"⩮",Ecaron:"Ě",ecaron:"ě",Ecirc:"Ê",ecirc:"ê",ecir:"≖",ecolon:"≕",Ecy:"Э",ecy:"э",eDDot:"⩷",Edot:"Ė",edot:"ė",eDot:"≑",ee:"ⅇ",efDot:"≒",Efr:"𝔈",efr:"𝔢",eg:"⪚",Egrave:"È",egrave:"è",egs:"⪖",egsdot:"⪘",el:"⪙",Element:"∈",elinters:"⏧",ell:"ℓ",els:"⪕",elsdot:"⪗",Emacr:"Ē",emacr:"ē",empty:"∅",emptyset:"∅",EmptySmallSquare:"◻",emptyv:"∅",EmptyVerySmallSquare:"▫",emsp13:" ",emsp14:" ",emsp:" ",ENG:"Ŋ",eng:"ŋ",ensp:" ",Eogon:"Ę",eogon:"ę",Eopf:"𝔼",eopf:"𝕖",epar:"⋕",eparsl:"⧣",eplus:"⩱",epsi:"ε",Epsilon:"Ε",epsilon:"ε",epsiv:"ϵ",eqcirc:"≖",eqcolon:"≕",eqsim:"≂",eqslantgtr:"⪖",eqslantless:"⪕",Equal:"⩵",equals:"=",EqualTilde:"≂",equest:"≟",Equilibrium:"⇌",equiv:"≡",equivDD:"⩸",eqvparsl:"⧥",erarr:"⥱",erDot:"≓",escr:"ℯ",Escr:"ℰ",esdot:"≐",Esim:"⩳",esim:"≂",Eta:"Η",eta:"η",ETH:"Ð",eth:"ð",Euml:"Ë",euml:"ë",euro:"€",excl:"!",exist:"∃",Exists:"∃",expectation:"ℰ",exponentiale:"ⅇ",ExponentialE:"ⅇ",fallingdotseq:"≒",Fcy:"Ф",fcy:"ф",female:"♀",ffilig:"ﬃ",fflig:"ﬀ",ffllig:"ﬄ",Ffr:"𝔉",ffr:"𝔣",filig:"ﬁ",FilledSmallSquare:"◼",FilledVerySmallSquare:"▪",fjlig:"fj",flat:"♭",fllig:"ﬂ",fltns:"▱",fnof:"ƒ",Fopf:"𝔽",fopf:"𝕗",forall:"∀",ForAll:"∀",fork:"⋔",forkv:"⫙",Fouriertrf:"ℱ",fpartint:"⨍",frac12:"½",frac13:"⅓",frac14:"¼",frac15:"⅕",frac16:"⅙",frac18:"⅛",frac23:"⅔",frac25:"⅖",frac34:"¾",frac35:"⅗",frac38:"⅜",frac45:"⅘",frac56:"⅚",frac58:"⅝",frac78:"⅞",frasl:"⁄",frown:"⌢",fscr:"𝒻",Fscr:"ℱ",gacute:"ǵ",Gamma:"Γ",gamma:"γ",Gammad:"Ϝ",gammad:"ϝ",gap:"⪆",Gbreve:"Ğ",gbreve:"ğ",Gcedil:"Ģ",Gcirc:"Ĝ",gcirc:"ĝ",Gcy:"Г",gcy:"г",Gdot:"Ġ",gdot:"ġ",ge:"≥",gE:"≧",gEl:"⪌",gel:"⋛",geq:"≥",geqq:"≧",geqslant:"⩾",gescc:"⪩",ges:"⩾",gesdot:"⪀",gesdoto:"⪂",gesdotol:"⪄",gesl:"⋛︀",gesles:"⪔",Gfr:"𝔊",gfr:"𝔤",gg:"≫",Gg:"⋙",ggg:"⋙",gimel:"ℷ",GJcy:"Ѓ",gjcy:"ѓ",gla:"⪥",gl:"≷",glE:"⪒",glj:"⪤",gnap:"⪊",gnapprox:"⪊",gne:"⪈",gnE:"≩",gneq:"⪈",gneqq:"≩",gnsim:"⋧",Gopf:"𝔾",gopf:"𝕘",grave:"`",GreaterEqual:"≥",GreaterEqualLess:"⋛",GreaterFullEqual:"≧",GreaterGreater:"⪢",GreaterLess:"≷",GreaterSlantEqual:"⩾",GreaterTilde:"≳",Gscr:"𝒢",gscr:"ℊ",gsim:"≳",gsime:"⪎",gsiml:"⪐",gtcc:"⪧",gtcir:"⩺",gt:">",GT:">",Gt:"≫",gtdot:"⋗",gtlPar:"⦕",gtquest:"⩼",gtrapprox:"⪆",gtrarr:"⥸",gtrdot:"⋗",gtreqless:"⋛",gtreqqless:"⪌",gtrless:"≷",gtrsim:"≳",gvertneqq:"≩︀",gvnE:"≩︀",Hacek:"ˇ",hairsp:" ",half:"½",hamilt:"ℋ",HARDcy:"Ъ",hardcy:"ъ",harrcir:"⥈",harr:"↔",hArr:"⇔",harrw:"↭",Hat:"^",hbar:"ℏ",Hcirc:"Ĥ",hcirc:"ĥ",hearts:"♥",heartsuit:"♥",hellip:"…",hercon:"⊹",hfr:"𝔥",Hfr:"ℌ",HilbertSpace:"ℋ",hksearow:"⤥",hkswarow:"⤦",hoarr:"⇿",homtht:"∻",hookleftarrow:"↩",hookrightarrow:"↪",hopf:"𝕙",Hopf:"ℍ",horbar:"―",HorizontalLine:"─",hscr:"𝒽",Hscr:"ℋ",hslash:"ℏ",Hstrok:"Ħ",hstrok:"ħ",HumpDownHump:"≎",HumpEqual:"≏",hybull:"⁃",hyphen:"‐",Iacute:"Í",iacute:"í",ic:"⁣",Icirc:"Î",icirc:"î",Icy:"И",icy:"и",Idot:"İ",IEcy:"Е",iecy:"е",iexcl:"¡",iff:"⇔",ifr:"𝔦",Ifr:"ℑ",Igrave:"Ì",igrave:"ì",ii:"ⅈ",iiiint:"⨌",iiint:"∭",iinfin:"⧜",iiota:"℩",IJlig:"Ĳ",ijlig:"ĳ",Imacr:"Ī",imacr:"ī",image:"ℑ",ImaginaryI:"ⅈ",imagline:"ℐ",imagpart:"ℑ",imath:"ı",Im:"ℑ",imof:"⊷",imped:"Ƶ",Implies:"⇒",incare:"℅",in:"∈",infin:"∞",infintie:"⧝",inodot:"ı",intcal:"⊺",int:"∫",Int:"∬",integers:"ℤ",Integral:"∫",intercal:"⊺",Intersection:"⋂",intlarhk:"⨗",intprod:"⨼",InvisibleComma:"⁣",InvisibleTimes:"⁢",IOcy:"Ё",iocy:"ё",Iogon:"Į",iogon:"į",Iopf:"𝕀",iopf:"𝕚",Iota:"Ι",iota:"ι",iprod:"⨼",iquest:"¿",iscr:"𝒾",Iscr:"ℐ",isin:"∈",isindot:"⋵",isinE:"⋹",isins:"⋴",isinsv:"⋳",isinv:"∈",it:"⁢",Itilde:"Ĩ",itilde:"ĩ",Iukcy:"І",iukcy:"і",Iuml:"Ï",iuml:"ï",Jcirc:"Ĵ",jcirc:"ĵ",Jcy:"Й",jcy:"й",Jfr:"𝔍",jfr:"𝔧",jmath:"ȷ",Jopf:"𝕁",jopf:"𝕛",Jscr:"𝒥",jscr:"𝒿",Jsercy:"Ј",jsercy:"ј",Jukcy:"Є",jukcy:"є",Kappa:"Κ",kappa:"κ",kappav:"ϰ",Kcedil:"Ķ",kcedil:"ķ",Kcy:"К",kcy:"к",Kfr:"𝔎",kfr:"𝔨",kgreen:"ĸ",KHcy:"Х",khcy:"х",KJcy:"Ќ",kjcy:"ќ",Kopf:"𝕂",kopf:"𝕜",Kscr:"𝒦",kscr:"𝓀",lAarr:"⇚",Lacute:"Ĺ",lacute:"ĺ",laemptyv:"⦴",lagran:"ℒ",Lambda:"Λ",lambda:"λ",lang:"⟨",Lang:"⟪",langd:"⦑",langle:"⟨",lap:"⪅",Laplacetrf:"ℒ",laquo:"«",larrb:"⇤",larrbfs:"⤟",larr:"←",Larr:"↞",lArr:"⇐",larrfs:"⤝",larrhk:"↩",larrlp:"↫",larrpl:"⤹",larrsim:"⥳",larrtl:"↢",latail:"⤙",lAtail:"⤛",lat:"⪫",late:"⪭",lates:"⪭︀",lbarr:"⤌",lBarr:"⤎",lbbrk:"❲",lbrace:"{",lbrack:"[",lbrke:"⦋",lbrksld:"⦏",lbrkslu:"⦍",Lcaron:"Ľ",lcaron:"ľ",Lcedil:"Ļ",lcedil:"ļ",lceil:"⌈",lcub:"{",Lcy:"Л",lcy:"л",ldca:"⤶",ldquo:"“",ldquor:"„",ldrdhar:"⥧",ldrushar:"⥋",ldsh:"↲",le:"≤",lE:"≦",LeftAngleBracket:"⟨",LeftArrowBar:"⇤",leftarrow:"←",LeftArrow:"←",Leftarrow:"⇐",LeftArrowRightArrow:"⇆",leftarrowtail:"↢",LeftCeiling:"⌈",LeftDoubleBracket:"⟦",LeftDownTeeVector:"⥡",LeftDownVectorBar:"⥙",LeftDownVector:"⇃",LeftFloor:"⌊",leftharpoondown:"↽",leftharpoonup:"↼",leftleftarrows:"⇇",leftrightarrow:"↔",LeftRightArrow:"↔",Leftrightarrow:"⇔",leftrightarrows:"⇆",leftrightharpoons:"⇋",leftrightsquigarrow:"↭",LeftRightVector:"⥎",LeftTeeArrow:"↤",LeftTee:"⊣",LeftTeeVector:"⥚",leftthreetimes:"⋋",LeftTriangleBar:"⧏",LeftTriangle:"⊲",LeftTriangleEqual:"⊴",LeftUpDownVector:"⥑",LeftUpTeeVector:"⥠",LeftUpVectorBar:"⥘",LeftUpVector:"↿",LeftVectorBar:"⥒",LeftVector:"↼",lEg:"⪋",leg:"⋚",leq:"≤",leqq:"≦",leqslant:"⩽",lescc:"⪨",les:"⩽",lesdot:"⩿",lesdoto:"⪁",lesdotor:"⪃",lesg:"⋚︀",lesges:"⪓",lessapprox:"⪅",lessdot:"⋖",lesseqgtr:"⋚",lesseqqgtr:"⪋",LessEqualGreater:"⋚",LessFullEqual:"≦",LessGreater:"≶",lessgtr:"≶",LessLess:"⪡",lesssim:"≲",LessSlantEqual:"⩽",LessTilde:"≲",lfisht:"⥼",lfloor:"⌊",Lfr:"𝔏",lfr:"𝔩",lg:"≶",lgE:"⪑",lHar:"⥢",lhard:"↽",lharu:"↼",lharul:"⥪",lhblk:"▄",LJcy:"Љ",ljcy:"љ",llarr:"⇇",ll:"≪",Ll:"⋘",llcorner:"⌞",Lleftarrow:"⇚",llhard:"⥫",lltri:"◺",Lmidot:"Ŀ",lmidot:"ŀ",lmoustache:"⎰",lmoust:"⎰",lnap:"⪉",lnapprox:"⪉",lne:"⪇",lnE:"≨",lneq:"⪇",lneqq:"≨",lnsim:"⋦",loang:"⟬",loarr:"⇽",lobrk:"⟦",longleftarrow:"⟵",LongLeftArrow:"⟵",Longleftarrow:"⟸",longleftrightarrow:"⟷",LongLeftRightArrow:"⟷",Longleftrightarrow:"⟺",longmapsto:"⟼",longrightarrow:"⟶",LongRightArrow:"⟶",Longrightarrow:"⟹",looparrowleft:"↫",looparrowright:"↬",lopar:"⦅",Lopf:"𝕃",lopf:"𝕝",loplus:"⨭",lotimes:"⨴",lowast:"∗",lowbar:"_",LowerLeftArrow:"↙",LowerRightArrow:"↘",loz:"◊",lozenge:"◊",lozf:"⧫",lpar:"(",lparlt:"⦓",lrarr:"⇆",lrcorner:"⌟",lrhar:"⇋",lrhard:"⥭",lrm:"‎",lrtri:"⊿",lsaquo:"‹",lscr:"𝓁",Lscr:"ℒ",lsh:"↰",Lsh:"↰",lsim:"≲",lsime:"⪍",lsimg:"⪏",lsqb:"[",lsquo:"‘",lsquor:"‚",Lstrok:"Ł",lstrok:"ł",ltcc:"⪦",ltcir:"⩹",lt:"<",LT:"<",Lt:"≪",ltdot:"⋖",lthree:"⋋",ltimes:"⋉",ltlarr:"⥶",ltquest:"⩻",ltri:"◃",ltrie:"⊴",ltrif:"◂",ltrPar:"⦖",lurdshar:"⥊",luruhar:"⥦",lvertneqq:"≨︀",lvnE:"≨︀",macr:"¯",male:"♂",malt:"✠",maltese:"✠",Map:"⤅",map:"↦",mapsto:"↦",mapstodown:"↧",mapstoleft:"↤",mapstoup:"↥",marker:"▮",mcomma:"⨩",Mcy:"М",mcy:"м",mdash:"—",mDDot:"∺",measuredangle:"∡",MediumSpace:" ",Mellintrf:"ℳ",Mfr:"𝔐",mfr:"𝔪",mho:"℧",micro:"µ",midast:"*",midcir:"⫰",mid:"∣",middot:"·",minusb:"⊟",minus:"−",minusd:"∸",minusdu:"⨪",MinusPlus:"∓",mlcp:"⫛",mldr:"…",mnplus:"∓",models:"⊧",Mopf:"𝕄",mopf:"𝕞",mp:"∓",mscr:"𝓂",Mscr:"ℳ",mstpos:"∾",Mu:"Μ",mu:"μ",multimap:"⊸",mumap:"⊸",nabla:"∇",Nacute:"Ń",nacute:"ń",nang:"∠⃒",nap:"≉",napE:"⩰̸",napid:"≋̸",napos:"ŉ",napprox:"≉",natural:"♮",naturals:"ℕ",natur:"♮",nbsp:" ",nbump:"≎̸",nbumpe:"≏̸",ncap:"⩃",Ncaron:"Ň",ncaron:"ň",Ncedil:"Ņ",ncedil:"ņ",ncong:"≇",ncongdot:"⩭̸",ncup:"⩂",Ncy:"Н",ncy:"н",ndash:"–",nearhk:"⤤",nearr:"↗",neArr:"⇗",nearrow:"↗",ne:"≠",nedot:"≐̸",NegativeMediumSpace:"​",NegativeThickSpace:"​",NegativeThinSpace:"​",NegativeVeryThinSpace:"​",nequiv:"≢",nesear:"⤨",nesim:"≂̸",NestedGreaterGreater:"≫",NestedLessLess:"≪",NewLine:`
`,nexist:"∄",nexists:"∄",Nfr:"𝔑",nfr:"𝔫",ngE:"≧̸",nge:"≱",ngeq:"≱",ngeqq:"≧̸",ngeqslant:"⩾̸",nges:"⩾̸",nGg:"⋙̸",ngsim:"≵",nGt:"≫⃒",ngt:"≯",ngtr:"≯",nGtv:"≫̸",nharr:"↮",nhArr:"⇎",nhpar:"⫲",ni:"∋",nis:"⋼",nisd:"⋺",niv:"∋",NJcy:"Њ",njcy:"њ",nlarr:"↚",nlArr:"⇍",nldr:"‥",nlE:"≦̸",nle:"≰",nleftarrow:"↚",nLeftarrow:"⇍",nleftrightarrow:"↮",nLeftrightarrow:"⇎",nleq:"≰",nleqq:"≦̸",nleqslant:"⩽̸",nles:"⩽̸",nless:"≮",nLl:"⋘̸",nlsim:"≴",nLt:"≪⃒",nlt:"≮",nltri:"⋪",nltrie:"⋬",nLtv:"≪̸",nmid:"∤",NoBreak:"⁠",NonBreakingSpace:" ",nopf:"𝕟",Nopf:"ℕ",Not:"⫬",not:"¬",NotCongruent:"≢",NotCupCap:"≭",NotDoubleVerticalBar:"∦",NotElement:"∉",NotEqual:"≠",NotEqualTilde:"≂̸",NotExists:"∄",NotGreater:"≯",NotGreaterEqual:"≱",NotGreaterFullEqual:"≧̸",NotGreaterGreater:"≫̸",NotGreaterLess:"≹",NotGreaterSlantEqual:"⩾̸",NotGreaterTilde:"≵",NotHumpDownHump:"≎̸",NotHumpEqual:"≏̸",notin:"∉",notindot:"⋵̸",notinE:"⋹̸",notinva:"∉",notinvb:"⋷",notinvc:"⋶",NotLeftTriangleBar:"⧏̸",NotLeftTriangle:"⋪",NotLeftTriangleEqual:"⋬",NotLess:"≮",NotLessEqual:"≰",NotLessGreater:"≸",NotLessLess:"≪̸",NotLessSlantEqual:"⩽̸",NotLessTilde:"≴",NotNestedGreaterGreater:"⪢̸",NotNestedLessLess:"⪡̸",notni:"∌",notniva:"∌",notnivb:"⋾",notnivc:"⋽",NotPrecedes:"⊀",NotPrecedesEqual:"⪯̸",NotPrecedesSlantEqual:"⋠",NotReverseElement:"∌",NotRightTriangleBar:"⧐̸",NotRightTriangle:"⋫",NotRightTriangleEqual:"⋭",NotSquareSubset:"⊏̸",NotSquareSubsetEqual:"⋢",NotSquareSuperset:"⊐̸",NotSquareSupersetEqual:"⋣",NotSubset:"⊂⃒",NotSubsetEqual:"⊈",NotSucceeds:"⊁",NotSucceedsEqual:"⪰̸",NotSucceedsSlantEqual:"⋡",NotSucceedsTilde:"≿̸",NotSuperset:"⊃⃒",NotSupersetEqual:"⊉",NotTilde:"≁",NotTildeEqual:"≄",NotTildeFullEqual:"≇",NotTildeTilde:"≉",NotVerticalBar:"∤",nparallel:"∦",npar:"∦",nparsl:"⫽⃥",npart:"∂̸",npolint:"⨔",npr:"⊀",nprcue:"⋠",nprec:"⊀",npreceq:"⪯̸",npre:"⪯̸",nrarrc:"⤳̸",nrarr:"↛",nrArr:"⇏",nrarrw:"↝̸",nrightarrow:"↛",nRightarrow:"⇏",nrtri:"⋫",nrtrie:"⋭",nsc:"⊁",nsccue:"⋡",nsce:"⪰̸",Nscr:"𝒩",nscr:"𝓃",nshortmid:"∤",nshortparallel:"∦",nsim:"≁",nsime:"≄",nsimeq:"≄",nsmid:"∤",nspar:"∦",nsqsube:"⋢",nsqsupe:"⋣",nsub:"⊄",nsubE:"⫅̸",nsube:"⊈",nsubset:"⊂⃒",nsubseteq:"⊈",nsubseteqq:"⫅̸",nsucc:"⊁",nsucceq:"⪰̸",nsup:"⊅",nsupE:"⫆̸",nsupe:"⊉",nsupset:"⊃⃒",nsupseteq:"⊉",nsupseteqq:"⫆̸",ntgl:"≹",Ntilde:"Ñ",ntilde:"ñ",ntlg:"≸",ntriangleleft:"⋪",ntrianglelefteq:"⋬",ntriangleright:"⋫",ntrianglerighteq:"⋭",Nu:"Ν",nu:"ν",num:"#",numero:"№",numsp:" ",nvap:"≍⃒",nvdash:"⊬",nvDash:"⊭",nVdash:"⊮",nVDash:"⊯",nvge:"≥⃒",nvgt:">⃒",nvHarr:"⤄",nvinfin:"⧞",nvlArr:"⤂",nvle:"≤⃒",nvlt:"<⃒",nvltrie:"⊴⃒",nvrArr:"⤃",nvrtrie:"⊵⃒",nvsim:"∼⃒",nwarhk:"⤣",nwarr:"↖",nwArr:"⇖",nwarrow:"↖",nwnear:"⤧",Oacute:"Ó",oacute:"ó",oast:"⊛",Ocirc:"Ô",ocirc:"ô",ocir:"⊚",Ocy:"О",ocy:"о",odash:"⊝",Odblac:"Ő",odblac:"ő",odiv:"⨸",odot:"⊙",odsold:"⦼",OElig:"Œ",oelig:"œ",ofcir:"⦿",Ofr:"𝔒",ofr:"𝔬",ogon:"˛",Ograve:"Ò",ograve:"ò",ogt:"⧁",ohbar:"⦵",ohm:"Ω",oint:"∮",olarr:"↺",olcir:"⦾",olcross:"⦻",oline:"‾",olt:"⧀",Omacr:"Ō",omacr:"ō",Omega:"Ω",omega:"ω",Omicron:"Ο",omicron:"ο",omid:"⦶",ominus:"⊖",Oopf:"𝕆",oopf:"𝕠",opar:"⦷",OpenCurlyDoubleQuote:"“",OpenCurlyQuote:"‘",operp:"⦹",oplus:"⊕",orarr:"↻",Or:"⩔",or:"∨",ord:"⩝",order:"ℴ",orderof:"ℴ",ordf:"ª",ordm:"º",origof:"⊶",oror:"⩖",orslope:"⩗",orv:"⩛",oS:"Ⓢ",Oscr:"𝒪",oscr:"ℴ",Oslash:"Ø",oslash:"ø",osol:"⊘",Otilde:"Õ",otilde:"õ",otimesas:"⨶",Otimes:"⨷",otimes:"⊗",Ouml:"Ö",ouml:"ö",ovbar:"⌽",OverBar:"‾",OverBrace:"⏞",OverBracket:"⎴",OverParenthesis:"⏜",para:"¶",parallel:"∥",par:"∥",parsim:"⫳",parsl:"⫽",part:"∂",PartialD:"∂",Pcy:"П",pcy:"п",percnt:"%",period:".",permil:"‰",perp:"⊥",pertenk:"‱",Pfr:"𝔓",pfr:"𝔭",Phi:"Φ",phi:"φ",phiv:"ϕ",phmmat:"ℳ",phone:"☎",Pi:"Π",pi:"π",pitchfork:"⋔",piv:"ϖ",planck:"ℏ",planckh:"ℎ",plankv:"ℏ",plusacir:"⨣",plusb:"⊞",pluscir:"⨢",plus:"+",plusdo:"∔",plusdu:"⨥",pluse:"⩲",PlusMinus:"±",plusmn:"±",plussim:"⨦",plustwo:"⨧",pm:"±",Poincareplane:"ℌ",pointint:"⨕",popf:"𝕡",Popf:"ℙ",pound:"£",prap:"⪷",Pr:"⪻",pr:"≺",prcue:"≼",precapprox:"⪷",prec:"≺",preccurlyeq:"≼",Precedes:"≺",PrecedesEqual:"⪯",PrecedesSlantEqual:"≼",PrecedesTilde:"≾",preceq:"⪯",precnapprox:"⪹",precneqq:"⪵",precnsim:"⋨",pre:"⪯",prE:"⪳",precsim:"≾",prime:"′",Prime:"″",primes:"ℙ",prnap:"⪹",prnE:"⪵",prnsim:"⋨",prod:"∏",Product:"∏",profalar:"⌮",profline:"⌒",profsurf:"⌓",prop:"∝",Proportional:"∝",Proportion:"∷",propto:"∝",prsim:"≾",prurel:"⊰",Pscr:"𝒫",pscr:"𝓅",Psi:"Ψ",psi:"ψ",puncsp:" ",Qfr:"𝔔",qfr:"𝔮",qint:"⨌",qopf:"𝕢",Qopf:"ℚ",qprime:"⁗",Qscr:"𝒬",qscr:"𝓆",quaternions:"ℍ",quatint:"⨖",quest:"?",questeq:"≟",quot:'"',QUOT:'"',rAarr:"⇛",race:"∽̱",Racute:"Ŕ",racute:"ŕ",radic:"√",raemptyv:"⦳",rang:"⟩",Rang:"⟫",rangd:"⦒",range:"⦥",rangle:"⟩",raquo:"»",rarrap:"⥵",rarrb:"⇥",rarrbfs:"⤠",rarrc:"⤳",rarr:"→",Rarr:"↠",rArr:"⇒",rarrfs:"⤞",rarrhk:"↪",rarrlp:"↬",rarrpl:"⥅",rarrsim:"⥴",Rarrtl:"⤖",rarrtl:"↣",rarrw:"↝",ratail:"⤚",rAtail:"⤜",ratio:"∶",rationals:"ℚ",rbarr:"⤍",rBarr:"⤏",RBarr:"⤐",rbbrk:"❳",rbrace:"}",rbrack:"]",rbrke:"⦌",rbrksld:"⦎",rbrkslu:"⦐",Rcaron:"Ř",rcaron:"ř",Rcedil:"Ŗ",rcedil:"ŗ",rceil:"⌉",rcub:"}",Rcy:"Р",rcy:"р",rdca:"⤷",rdldhar:"⥩",rdquo:"”",rdquor:"”",rdsh:"↳",real:"ℜ",realine:"ℛ",realpart:"ℜ",reals:"ℝ",Re:"ℜ",rect:"▭",reg:"®",REG:"®",ReverseElement:"∋",ReverseEquilibrium:"⇋",ReverseUpEquilibrium:"⥯",rfisht:"⥽",rfloor:"⌋",rfr:"𝔯",Rfr:"ℜ",rHar:"⥤",rhard:"⇁",rharu:"⇀",rharul:"⥬",Rho:"Ρ",rho:"ρ",rhov:"ϱ",RightAngleBracket:"⟩",RightArrowBar:"⇥",rightarrow:"→",RightArrow:"→",Rightarrow:"⇒",RightArrowLeftArrow:"⇄",rightarrowtail:"↣",RightCeiling:"⌉",RightDoubleBracket:"⟧",RightDownTeeVector:"⥝",RightDownVectorBar:"⥕",RightDownVector:"⇂",RightFloor:"⌋",rightharpoondown:"⇁",rightharpoonup:"⇀",rightleftarrows:"⇄",rightleftharpoons:"⇌",rightrightarrows:"⇉",rightsquigarrow:"↝",RightTeeArrow:"↦",RightTee:"⊢",RightTeeVector:"⥛",rightthreetimes:"⋌",RightTriangleBar:"⧐",RightTriangle:"⊳",RightTriangleEqual:"⊵",RightUpDownVector:"⥏",RightUpTeeVector:"⥜",RightUpVectorBar:"⥔",RightUpVector:"↾",RightVectorBar:"⥓",RightVector:"⇀",ring:"˚",risingdotseq:"≓",rlarr:"⇄",rlhar:"⇌",rlm:"‏",rmoustache:"⎱",rmoust:"⎱",rnmid:"⫮",roang:"⟭",roarr:"⇾",robrk:"⟧",ropar:"⦆",ropf:"𝕣",Ropf:"ℝ",roplus:"⨮",rotimes:"⨵",RoundImplies:"⥰",rpar:")",rpargt:"⦔",rppolint:"⨒",rrarr:"⇉",Rrightarrow:"⇛",rsaquo:"›",rscr:"𝓇",Rscr:"ℛ",rsh:"↱",Rsh:"↱",rsqb:"]",rsquo:"’",rsquor:"’",rthree:"⋌",rtimes:"⋊",rtri:"▹",rtrie:"⊵",rtrif:"▸",rtriltri:"⧎",RuleDelayed:"⧴",ruluhar:"⥨",rx:"℞",Sacute:"Ś",sacute:"ś",sbquo:"‚",scap:"⪸",Scaron:"Š",scaron:"š",Sc:"⪼",sc:"≻",sccue:"≽",sce:"⪰",scE:"⪴",Scedil:"Ş",scedil:"ş",Scirc:"Ŝ",scirc:"ŝ",scnap:"⪺",scnE:"⪶",scnsim:"⋩",scpolint:"⨓",scsim:"≿",Scy:"С",scy:"с",sdotb:"⊡",sdot:"⋅",sdote:"⩦",searhk:"⤥",searr:"↘",seArr:"⇘",searrow:"↘",sect:"§",semi:";",seswar:"⤩",setminus:"∖",setmn:"∖",sext:"✶",Sfr:"𝔖",sfr:"𝔰",sfrown:"⌢",sharp:"♯",SHCHcy:"Щ",shchcy:"щ",SHcy:"Ш",shcy:"ш",ShortDownArrow:"↓",ShortLeftArrow:"←",shortmid:"∣",shortparallel:"∥",ShortRightArrow:"→",ShortUpArrow:"↑",shy:"­",Sigma:"Σ",sigma:"σ",sigmaf:"ς",sigmav:"ς",sim:"∼",simdot:"⩪",sime:"≃",simeq:"≃",simg:"⪞",simgE:"⪠",siml:"⪝",simlE:"⪟",simne:"≆",simplus:"⨤",simrarr:"⥲",slarr:"←",SmallCircle:"∘",smallsetminus:"∖",smashp:"⨳",smeparsl:"⧤",smid:"∣",smile:"⌣",smt:"⪪",smte:"⪬",smtes:"⪬︀",SOFTcy:"Ь",softcy:"ь",solbar:"⌿",solb:"⧄",sol:"/",Sopf:"𝕊",sopf:"𝕤",spades:"♠",spadesuit:"♠",spar:"∥",sqcap:"⊓",sqcaps:"⊓︀",sqcup:"⊔",sqcups:"⊔︀",Sqrt:"√",sqsub:"⊏",sqsube:"⊑",sqsubset:"⊏",sqsubseteq:"⊑",sqsup:"⊐",sqsupe:"⊒",sqsupset:"⊐",sqsupseteq:"⊒",square:"□",Square:"□",SquareIntersection:"⊓",SquareSubset:"⊏",SquareSubsetEqual:"⊑",SquareSuperset:"⊐",SquareSupersetEqual:"⊒",SquareUnion:"⊔",squarf:"▪",squ:"□",squf:"▪",srarr:"→",Sscr:"𝒮",sscr:"𝓈",ssetmn:"∖",ssmile:"⌣",sstarf:"⋆",Star:"⋆",star:"☆",starf:"★",straightepsilon:"ϵ",straightphi:"ϕ",strns:"¯",sub:"⊂",Sub:"⋐",subdot:"⪽",subE:"⫅",sube:"⊆",subedot:"⫃",submult:"⫁",subnE:"⫋",subne:"⊊",subplus:"⪿",subrarr:"⥹",subset:"⊂",Subset:"⋐",subseteq:"⊆",subseteqq:"⫅",SubsetEqual:"⊆",subsetneq:"⊊",subsetneqq:"⫋",subsim:"⫇",subsub:"⫕",subsup:"⫓",succapprox:"⪸",succ:"≻",succcurlyeq:"≽",Succeeds:"≻",SucceedsEqual:"⪰",SucceedsSlantEqual:"≽",SucceedsTilde:"≿",succeq:"⪰",succnapprox:"⪺",succneqq:"⪶",succnsim:"⋩",succsim:"≿",SuchThat:"∋",sum:"∑",Sum:"∑",sung:"♪",sup1:"¹",sup2:"²",sup3:"³",sup:"⊃",Sup:"⋑",supdot:"⪾",supdsub:"⫘",supE:"⫆",supe:"⊇",supedot:"⫄",Superset:"⊃",SupersetEqual:"⊇",suphsol:"⟉",suphsub:"⫗",suplarr:"⥻",supmult:"⫂",supnE:"⫌",supne:"⊋",supplus:"⫀",supset:"⊃",Supset:"⋑",supseteq:"⊇",supseteqq:"⫆",supsetneq:"⊋",supsetneqq:"⫌",supsim:"⫈",supsub:"⫔",supsup:"⫖",swarhk:"⤦",swarr:"↙",swArr:"⇙",swarrow:"↙",swnwar:"⤪",szlig:"ß",Tab:"	",target:"⌖",Tau:"Τ",tau:"τ",tbrk:"⎴",Tcaron:"Ť",tcaron:"ť",Tcedil:"Ţ",tcedil:"ţ",Tcy:"Т",tcy:"т",tdot:"⃛",telrec:"⌕",Tfr:"𝔗",tfr:"𝔱",there4:"∴",therefore:"∴",Therefore:"∴",Theta:"Θ",theta:"θ",thetasym:"ϑ",thetav:"ϑ",thickapprox:"≈",thicksim:"∼",ThickSpace:"  ",ThinSpace:" ",thinsp:" ",thkap:"≈",thksim:"∼",THORN:"Þ",thorn:"þ",tilde:"˜",Tilde:"∼",TildeEqual:"≃",TildeFullEqual:"≅",TildeTilde:"≈",timesbar:"⨱",timesb:"⊠",times:"×",timesd:"⨰",tint:"∭",toea:"⤨",topbot:"⌶",topcir:"⫱",top:"⊤",Topf:"𝕋",topf:"𝕥",topfork:"⫚",tosa:"⤩",tprime:"‴",trade:"™",TRADE:"™",triangle:"▵",triangledown:"▿",triangleleft:"◃",trianglelefteq:"⊴",triangleq:"≜",triangleright:"▹",trianglerighteq:"⊵",tridot:"◬",trie:"≜",triminus:"⨺",TripleDot:"⃛",triplus:"⨹",trisb:"⧍",tritime:"⨻",trpezium:"⏢",Tscr:"𝒯",tscr:"𝓉",TScy:"Ц",tscy:"ц",TSHcy:"Ћ",tshcy:"ћ",Tstrok:"Ŧ",tstrok:"ŧ",twixt:"≬",twoheadleftarrow:"↞",twoheadrightarrow:"↠",Uacute:"Ú",uacute:"ú",uarr:"↑",Uarr:"↟",uArr:"⇑",Uarrocir:"⥉",Ubrcy:"Ў",ubrcy:"ў",Ubreve:"Ŭ",ubreve:"ŭ",Ucirc:"Û",ucirc:"û",Ucy:"У",ucy:"у",udarr:"⇅",Udblac:"Ű",udblac:"ű",udhar:"⥮",ufisht:"⥾",Ufr:"𝔘",ufr:"𝔲",Ugrave:"Ù",ugrave:"ù",uHar:"⥣",uharl:"↿",uharr:"↾",uhblk:"▀",ulcorn:"⌜",ulcorner:"⌜",ulcrop:"⌏",ultri:"◸",Umacr:"Ū",umacr:"ū",uml:"¨",UnderBar:"_",UnderBrace:"⏟",UnderBracket:"⎵",UnderParenthesis:"⏝",Union:"⋃",UnionPlus:"⊎",Uogon:"Ų",uogon:"ų",Uopf:"𝕌",uopf:"𝕦",UpArrowBar:"⤒",uparrow:"↑",UpArrow:"↑",Uparrow:"⇑",UpArrowDownArrow:"⇅",updownarrow:"↕",UpDownArrow:"↕",Updownarrow:"⇕",UpEquilibrium:"⥮",upharpoonleft:"↿",upharpoonright:"↾",uplus:"⊎",UpperLeftArrow:"↖",UpperRightArrow:"↗",upsi:"υ",Upsi:"ϒ",upsih:"ϒ",Upsilon:"Υ",upsilon:"υ",UpTeeArrow:"↥",UpTee:"⊥",upuparrows:"⇈",urcorn:"⌝",urcorner:"⌝",urcrop:"⌎",Uring:"Ů",uring:"ů",urtri:"◹",Uscr:"𝒰",uscr:"𝓊",utdot:"⋰",Utilde:"Ũ",utilde:"ũ",utri:"▵",utrif:"▴",uuarr:"⇈",Uuml:"Ü",uuml:"ü",uwangle:"⦧",vangrt:"⦜",varepsilon:"ϵ",varkappa:"ϰ",varnothing:"∅",varphi:"ϕ",varpi:"ϖ",varpropto:"∝",varr:"↕",vArr:"⇕",varrho:"ϱ",varsigma:"ς",varsubsetneq:"⊊︀",varsubsetneqq:"⫋︀",varsupsetneq:"⊋︀",varsupsetneqq:"⫌︀",vartheta:"ϑ",vartriangleleft:"⊲",vartriangleright:"⊳",vBar:"⫨",Vbar:"⫫",vBarv:"⫩",Vcy:"В",vcy:"в",vdash:"⊢",vDash:"⊨",Vdash:"⊩",VDash:"⊫",Vdashl:"⫦",veebar:"⊻",vee:"∨",Vee:"⋁",veeeq:"≚",vellip:"⋮",verbar:"|",Verbar:"‖",vert:"|",Vert:"‖",VerticalBar:"∣",VerticalLine:"|",VerticalSeparator:"❘",VerticalTilde:"≀",VeryThinSpace:" ",Vfr:"𝔙",vfr:"𝔳",vltri:"⊲",vnsub:"⊂⃒",vnsup:"⊃⃒",Vopf:"𝕍",vopf:"𝕧",vprop:"∝",vrtri:"⊳",Vscr:"𝒱",vscr:"𝓋",vsubnE:"⫋︀",vsubne:"⊊︀",vsupnE:"⫌︀",vsupne:"⊋︀",Vvdash:"⊪",vzigzag:"⦚",Wcirc:"Ŵ",wcirc:"ŵ",wedbar:"⩟",wedge:"∧",Wedge:"⋀",wedgeq:"≙",weierp:"℘",Wfr:"𝔚",wfr:"𝔴",Wopf:"𝕎",wopf:"𝕨",wp:"℘",wr:"≀",wreath:"≀",Wscr:"𝒲",wscr:"𝓌",xcap:"⋂",xcirc:"◯",xcup:"⋃",xdtri:"▽",Xfr:"𝔛",xfr:"𝔵",xharr:"⟷",xhArr:"⟺",Xi:"Ξ",xi:"ξ",xlarr:"⟵",xlArr:"⟸",xmap:"⟼",xnis:"⋻",xodot:"⨀",Xopf:"𝕏",xopf:"𝕩",xoplus:"⨁",xotime:"⨂",xrarr:"⟶",xrArr:"⟹",Xscr:"𝒳",xscr:"𝓍",xsqcup:"⨆",xuplus:"⨄",xutri:"△",xvee:"⋁",xwedge:"⋀",Yacute:"Ý",yacute:"ý",YAcy:"Я",yacy:"я",Ycirc:"Ŷ",ycirc:"ŷ",Ycy:"Ы",ycy:"ы",yen:"¥",Yfr:"𝔜",yfr:"𝔶",YIcy:"Ї",yicy:"ї",Yopf:"𝕐",yopf:"𝕪",Yscr:"𝒴",yscr:"𝓎",YUcy:"Ю",yucy:"ю",yuml:"ÿ",Yuml:"Ÿ",Zacute:"Ź",zacute:"ź",Zcaron:"Ž",zcaron:"ž",Zcy:"З",zcy:"з",Zdot:"Ż",zdot:"ż",zeetrf:"ℨ",ZeroWidthSpace:"​",Zeta:"Ζ",zeta:"ζ",zfr:"𝔷",Zfr:"ℨ",ZHcy:"Ж",zhcy:"ж",zigrarr:"⇝",zopf:"𝕫",Zopf:"ℤ",Zscr:"𝒵",zscr:"𝓏",zwj:"‍",zwnj:"‌"}},function(f,d){},function(f,d){},function(f,d,s){function e(k){return Array.prototype.slice.call(arguments,1).forEach(function(C){C&&Object.keys(C).forEach(function(y){k[y]=C[y]})}),k}function r(k){return Object.prototype.toString.call(k)}function n(k){return r(k)==="[object String]"}function i(k){return r(k)==="[object Object]"}function t(k){return r(k)==="[object RegExp]"}function a(k){return r(k)==="[object Function]"}function u(k){return k.replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&")}function c(k){return Object.keys(k||{}).reduce(function(C,y){return C||g.hasOwnProperty(y)},!1)}function o(k){k.__index__=-1,k.__text_cache__=""}function l(k){return function(C,y){var A=C.slice(y);return k.test(A)?A.match(k)[0].length:0}}function p(){return function(k,C){C.normalize(k)}}function _(k){function C(S){return S.replace("%TLDS%",A.src_tlds)}function y(S,M){throw new Error('(LinkifyIt) Invalid schema "'+S+'": '+M)}var A=k.re=s(117)(k.__opts__),T=k.__tlds__.slice();k.onCompile(),k.__tlds_replaced__||T.push(v),T.push(A.src_xn),A.src_tlds=T.join("|"),A.email_fuzzy=RegExp(C(A.tpl_email_fuzzy),"i"),A.link_fuzzy=RegExp(C(A.tpl_link_fuzzy),"i"),A.link_no_ip_fuzzy=RegExp(C(A.tpl_link_no_ip_fuzzy),"i"),A.host_fuzzy_test=RegExp(C(A.tpl_host_fuzzy_test),"i");var L=[];k.__compiled__={},Object.keys(k.__schemas__).forEach(function(S){var M=k.__schemas__[S];if(M!==null){var F={validate:null,link:null};return k.__compiled__[S]=F,i(M)?(t(M.validate)?F.validate=l(M.validate):a(M.validate)?F.validate=M.validate:y(S,M),void(a(M.normalize)?F.normalize=M.normalize:M.normalize?y(S,M):F.normalize=p())):n(M)?void L.push(S):void y(S,M)}}),L.forEach(function(S){k.__compiled__[k.__schemas__[S]]&&(k.__compiled__[S].validate=k.__compiled__[k.__schemas__[S]].validate,k.__compiled__[S].normalize=k.__compiled__[k.__schemas__[S]].normalize)}),k.__compiled__[""]={validate:null,normalize:p()};var j=Object.keys(k.__compiled__).filter(function(S){return S.length>0&&k.__compiled__[S]}).map(u).join("|");k.re.schema_test=RegExp("(^|(?!_)(?:[><｜]|"+A.src_ZPCc+"))("+j+")","i"),k.re.schema_search=RegExp("(^|(?!_)(?:[><｜]|"+A.src_ZPCc+"))("+j+")","ig"),k.re.pretest=RegExp("("+k.re.schema_test.source+")|("+k.re.host_fuzzy_test.source+")|@","i"),o(k)}function h(k,C){var y=k.__index__,A=k.__last_index__,T=k.__text_cache__.slice(y,A);this.schema=k.__schema__.toLowerCase(),this.index=y+C,this.lastIndex=A+C,this.raw=T,this.text=T,this.url=T}function b(k,C){var y=new h(k,C);return k.__compiled__[y.schema].normalize(y,k),y}function m(k,C){if(!(this instanceof m))return new m(k,C);C||c(k)&&(C=k,k={}),this.__opts__=e({},g,C),this.__index__=-1,this.__last_index__=-1,this.__schema__="",this.__text_cache__="",this.__schemas__=e({},w,k),this.__compiled__={},this.__tlds__=x,this.__tlds_replaced__=!1,this.re={},_(this)}var g={fuzzyLink:!0,fuzzyEmail:!0,fuzzyIP:!1},w={"http:":{validate:function(k,C,y){var A=k.slice(C);return y.re.http||(y.re.http=new RegExp("^\\/\\/"+y.re.src_auth+y.re.src_host_port_strict+y.re.src_path,"i")),y.re.http.test(A)?A.match(y.re.http)[0].length:0}},"https:":"http:","ftp:":"http:","//":{validate:function(k,C,y){var A=k.slice(C);return y.re.no_http||(y.re.no_http=new RegExp("^"+y.re.src_auth+"(?:localhost|(?:(?:"+y.re.src_domain+")\\.)+"+y.re.src_domain_root+")"+y.re.src_port+y.re.src_host_terminator+y.re.src_path,"i")),y.re.no_http.test(A)?C>=3&&k[C-3]===":"||C>=3&&k[C-3]==="/"?0:A.match(y.re.no_http)[0].length:0}},"mailto:":{validate:function(k,C,y){var A=k.slice(C);return y.re.mailto||(y.re.mailto=new RegExp("^"+y.re.src_email_name+"@"+y.re.src_host_strict,"i")),y.re.mailto.test(A)?A.match(y.re.mailto)[0].length:0}}},v="a[cdefgilmnoqrstuwxz]|b[abdefghijmnorstvwyz]|c[acdfghiklmnoruvwxyz]|d[ejkmoz]|e[cegrstu]|f[ijkmor]|g[abdefghilmnpqrstuwy]|h[kmnrtu]|i[delmnoqrst]|j[emop]|k[eghimnprwyz]|l[abcikrstuvy]|m[acdeghklmnopqrstuvwxyz]|n[acefgilopruz]|om|p[aefghklmnrstwy]|qa|r[eosuw]|s[abcdeghijklmnortuvxyz]|t[cdfghjklmnortvwz]|u[agksyz]|v[aceginu]|w[fs]|y[et]|z[amw]",x="biz|com|edu|gov|net|org|pro|web|xxx|aero|asia|coop|info|museum|name|shop|рф".split("|");m.prototype.add=function(k,C){return this.__schemas__[k]=C,_(this),this},m.prototype.set=function(k){return this.__opts__=e(this.__opts__,k),this},m.prototype.test=function(k){if(this.__text_cache__=k,this.__index__=-1,!k.length)return!1;var C,y,A,T,L,j,S,M;if(this.re.schema_test.test(k)){for(S=this.re.schema_search,S.lastIndex=0;(C=S.exec(k))!==null;)if(T=this.testSchemaAt(k,C[2],S.lastIndex)){this.__schema__=C[2],this.__index__=C.index+C[1].length,this.__last_index__=C.index+C[0].length+T;break}}return this.__opts__.fuzzyLink&&this.__compiled__["http:"]&&(M=k.search(this.re.host_fuzzy_test))>=0&&(this.__index__<0||M<this.__index__)&&(y=k.match(this.__opts__.fuzzyIP?this.re.link_fuzzy:this.re.link_no_ip_fuzzy))!==null&&(L=y.index+y[1].length,(this.__index__<0||L<this.__index__)&&(this.__schema__="",this.__index__=L,this.__last_index__=y.index+y[0].length)),this.__opts__.fuzzyEmail&&this.__compiled__["mailto:"]&&k.indexOf("@")>=0&&(A=k.match(this.re.email_fuzzy))!==null&&(L=A.index+A[1].length,j=A.index+A[0].length,(this.__index__<0||L<this.__index__||L===this.__index__&&j>this.__last_index__)&&(this.__schema__="mailto:",this.__index__=L,this.__last_index__=j)),this.__index__>=0},m.prototype.pretest=function(k){return this.re.pretest.test(k)},m.prototype.testSchemaAt=function(k,C,y){return this.__compiled__[C.toLowerCase()]?this.__compiled__[C.toLowerCase()].validate(k,y,this):0},m.prototype.match=function(k){var C=0,y=[];this.__index__>=0&&this.__text_cache__===k&&(y.push(b(this,C)),C=this.__last_index__);for(var A=C?k.slice(C):k;this.test(A);)y.push(b(this,C)),A=A.slice(this.__last_index__),C+=this.__last_index__;return y.length?y:null},m.prototype.tlds=function(k,C){return k=Array.isArray(k)?k:[k],C?(this.__tlds__=this.__tlds__.concat(k).sort().filter(function(y,A,T){return y!==T[A-1]}).reverse(),_(this),this):(this.__tlds__=k.slice(),this.__tlds_replaced__=!0,_(this),this)},m.prototype.normalize=function(k){k.schema||(k.url="http://"+k.url),k.schema!=="mailto:"||/^mailto:/i.test(k.url)||(k.url="mailto:"+k.url)},m.prototype.onCompile=function(){},f.exports=m},function(f,d,s){f.exports=function(e){var r={};return r.src_Any=s(59).source,r.src_Cc=s(57).source,r.src_Z=s(58).source,r.src_P=s(34).source,r.src_ZPCc=[r.src_Z,r.src_P,r.src_Cc].join("|"),r.src_ZCc=[r.src_Z,r.src_Cc].join("|"),r.src_pseudo_letter="(?:(?![><｜]|"+r.src_ZPCc+")"+r.src_Any+")",r.src_ip4="(?:(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)",r.src_auth="(?:(?:(?!"+r.src_ZCc+"|[@/\\[\\]()]).)+@)?",r.src_port="(?::(?:6(?:[0-4]\\d{3}|5(?:[0-4]\\d{2}|5(?:[0-2]\\d|3[0-5])))|[1-5]?\\d{1,4}))?",r.src_host_terminator="(?=$|[><｜]|"+r.src_ZPCc+")(?!-|_|:\\d|\\.-|\\.(?!$|"+r.src_ZPCc+"))",r.src_path="(?:[/?#](?:(?!"+r.src_ZCc+`|[><｜]|[()[\\]{}.,"'?!\\-]).|\\[(?:(?!`+r.src_ZCc+"|\\]).)*\\]|\\((?:(?!"+r.src_ZCc+"|[)]).)*\\)|\\{(?:(?!"+r.src_ZCc+'|[}]).)*\\}|\\"(?:(?!'+r.src_ZCc+`|["]).)+\\"|\\'(?:(?!`+r.src_ZCc+"|[']).)+\\'|\\'(?="+r.src_pseudo_letter+"|[-]).|\\.{2,4}[a-zA-Z0-9%/]|\\.(?!"+r.src_ZCc+"|[.]).|"+(e&&e["---"]?"\\-(?!--(?:[^-]|$))(?:-*)|":"\\-+|")+"\\,(?!"+r.src_ZCc+").|\\!(?!"+r.src_ZCc+"|[!]).|\\?(?!"+r.src_ZCc+"|[?]).)+|\\/)?",r.src_email_name='[\\-;:&=\\+\\$,\\.a-zA-Z0-9_][\\-;:&=\\+\\$,\\"\\.a-zA-Z0-9_]*',r.src_xn="xn--[a-z0-9\\-]{1,59}",r.src_domain_root="(?:"+r.src_xn+"|"+r.src_pseudo_letter+"{1,63})",r.src_domain="(?:"+r.src_xn+"|(?:"+r.src_pseudo_letter+")|(?:"+r.src_pseudo_letter+"(?:-|"+r.src_pseudo_letter+"){0,61}"+r.src_pseudo_letter+"))",r.src_host="(?:(?:(?:(?:"+r.src_domain+")\\.)*"+r.src_domain+"))",r.tpl_host_fuzzy="(?:"+r.src_ip4+"|(?:(?:(?:"+r.src_domain+")\\.)+(?:%TLDS%)))",r.tpl_host_no_ip_fuzzy="(?:(?:(?:"+r.src_domain+")\\.)+(?:%TLDS%))",r.src_host_strict=r.src_host+r.src_host_terminator,r.tpl_host_fuzzy_strict=r.tpl_host_fuzzy+r.src_host_terminator,r.src_host_port_strict=r.src_host+r.src_port+r.src_host_terminator,r.tpl_host_port_fuzzy_strict=r.tpl_host_fuzzy+r.src_port+r.src_host_terminator,r.tpl_host_port_no_ip_fuzzy_strict=r.tpl_host_no_ip_fuzzy+r.src_port+r.src_host_terminator,r.tpl_host_fuzzy_test="localhost|www\\.|\\.\\d{1,3}\\.|(?:\\.(?:%TLDS%)(?:"+r.src_ZPCc+"|>|$))",r.tpl_email_fuzzy='(^|[><｜]|"|\\(|'+r.src_ZCc+")("+r.src_email_name+"@"+r.tpl_host_fuzzy_strict+")",r.tpl_link_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|｜]|"+r.src_ZPCc+"))((?![$+<=>^`|｜])"+r.tpl_host_port_fuzzy_strict+r.src_path+")",r.tpl_link_no_ip_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|｜]|"+r.src_ZPCc+"))((?![$+<=>^`|｜])"+r.tpl_host_port_no_ip_fuzzy_strict+r.src_path+")",r}},function(f,d,s){f.exports=function(e){function r(o,l,p,_){var h,b,m,g,w,v=o.bMarks[l]+o.tShift[l],x=o.eMarks[l];if(v+2>=x||o.src.charCodeAt(v++)!==42||o.src.charCodeAt(v++)!==91)return!1;for(g=v;v<x;v++){if((m=o.src.charCodeAt(v))===91)return!1;if(m===93){w=v;break}m===92&&v++}return!(w<0||o.src.charCodeAt(w+1)!==58)&&(!!_||(h=o.src.slice(g,w).replace(/\\(.)/g,"$1"),b=o.src.slice(w+2,x).trim(),h.length!==0&&b.length!==0&&(o.env.abbreviations||(o.env.abbreviations={}),o.env.abbreviations[":"+h]===void 0&&(o.env.abbreviations[":"+h]=b),o.line=l+1,!0)))}function n(o){var l,p,_,h,b,m,g,w,v,x,k,C,y,A=o.tokens;if(o.env.abbreviations){for(C=new RegExp("(?:"+Object.keys(o.env.abbreviations).map(function(T){return T.substr(1)}).sort(function(T,L){return L.length-T.length}).map(i).join("|")+")"),k="(^|"+u+"|"+c+"|["+a.split("").map(i).join("")+"])("+Object.keys(o.env.abbreviations).map(function(T){return T.substr(1)}).sort(function(T,L){return L.length-T.length}).map(i).join("|")+")($|"+u+"|"+c+"|["+a.split("").map(i).join("")+"])",v=new RegExp(k,"g"),p=0,_=A.length;p<_;p++)if(A[p].type==="inline"){for(h=A[p].children,l=h.length-1;l>=0;l--)if(y=h[l],y.type==="text"&&(w=0,m=y.content,v.lastIndex=0,g=[],C.test(m))){for(;x=v.exec(m);)(x.index>0||x[1].length>0)&&(b=new o.Token("text","",0),b.content=m.slice(w,x.index+x[1].length),g.push(b)),b=new o.Token("abbr_open","abbr",1),b.attrs=[["title",o.env.abbreviations[":"+x[2]]]],g.push(b),b=new o.Token("text","",0),b.content=x[2],g.push(b),b=new o.Token("abbr_close","abbr",-1),g.push(b),v.lastIndex-=x[3].length,w=v.lastIndex;g.length&&(w<m.length&&(b=new o.Token("text","",0),b.content=m.slice(w),g.push(b)),A[p].children=h=t(h,l,g))}}}}var i=e.utils.escapeRE,t=e.utils.arrayReplaceAt,a=" \r\n$+<=>^`|~",u=e.utils.lib.ucmicro.P.source,c=e.utils.lib.ucmicro.Z.source;e.block.ruler.before("reference","abbr_def",r,{alt:["paragraph","reference"]}),e.core.ruler.after("linkify","abbr_replace",n)}},function(f,d,s){f.exports=function(e,r,n){function i(h){return h.trim().split(" ",2)[0]===r}function t(h,b,m,g,w){return h[b].nesting===1&&h[b].attrPush(["class",r]),w.renderToken(h,b,m,g,w)}function a(h,b,m,g){var w,v,x,k,C,y,A,T,L=!1,j=h.bMarks[b]+h.tShift[b],S=h.eMarks[b];if(o!==h.src.charCodeAt(j))return!1;for(w=j+1;w<=S&&c[(w-j)%l]===h.src[w];w++);if((x=Math.floor((w-j)/l))<u||(w-=(w-j)%l,k=h.src.slice(j,w),C=h.src.slice(w,S),!p(C)))return!1;if(g)return!0;for(v=b;!(++v>=m)&&(j=h.bMarks[v]+h.tShift[v],S=h.eMarks[v],!(j<S&&h.sCount[v]<h.blkIndent));)if(o===h.src.charCodeAt(j)&&!(h.sCount[v]-h.blkIndent>=4)){for(w=j+1;w<=S&&c[(w-j)%l]===h.src[w];w++);if(!(Math.floor((w-j)/l)<x||(w-=(w-j)%l,(w=h.skipSpaces(w))<S))){L=!0;break}}return A=h.parentType,T=h.lineMax,h.parentType="container",h.lineMax=v,y=h.push("container_"+r+"_open","div",1),y.markup=k,y.block=!0,y.info=C,y.map=[b,v],h.md.block.tokenize(h,b+1,v),y=h.push("container_"+r+"_close","div",-1),y.markup=h.src.slice(j,w),y.block=!0,h.parentType=A,h.lineMax=T,h.line=v+(L?1:0),!0}n=n||{};var u=3,c=n.marker||":",o=c.charCodeAt(0),l=c.length,p=n.validate||i,_=n.render||t;e.block.ruler.before("fence","container_"+r,a,{alt:["paragraph","reference","blockquote","list"]}),e.renderer.rules["container_"+r+"_open"]=_,e.renderer.rules["container_"+r+"_close"]=_}},function(f,d,s){f.exports=function(e){function r(a,u){var c,o,l=a.bMarks[u]+a.tShift[u],p=a.eMarks[u];return l>=p||(o=a.src.charCodeAt(l++))!==126&&o!==58?-1:(c=a.skipSpaces(l),l===c||c>=p?-1:l)}function n(a,u){var c,o,l=a.level+2;for(c=u+2,o=a.tokens.length-2;c<o;c++)a.tokens[c].level===l&&a.tokens[c].type==="paragraph_open"&&(a.tokens[c+2].hidden=!0,a.tokens[c].hidden=!0,c+=2)}function i(a,u,c,o){var l,p,_,h,b,m,g,w,v,x,k,C,y,A,T,L,j,S,M,F;if(o)return!(a.ddIndent<0)&&r(a,u)>=0;if((v=u+1)>=c||a.isEmpty(v)&&++v>=c||a.sCount[v]<a.blkIndent||(p=r(a,v))<0)return!1;g=a.tokens.length,M=!0,F=a.push("dl_open","dl",1),F.map=m=[u,0],h=u,_=v;e:for(;;){for(S=!1,F=a.push("dt_open","dt",1),F.map=[h,h],F=a.push("inline","",0),F.map=[h,h],F.content=a.getLines(h,h+1,a.blkIndent,!1).trim(),F.children=[],F=a.push("dt_close","dt",-1);;){for(F=a.push("dd_open","dd",1),F.map=b=[v,0],j=p,w=a.eMarks[_],x=a.sCount[_]+p-(a.bMarks[_]+a.tShift[_]);j<w&&(l=a.src.charCodeAt(j),t(l));)l===9?x+=4-x%4:x++,j++;if(p=j,L=a.tight,k=a.ddIndent,C=a.blkIndent,T=a.tShift[_],A=a.sCount[_],y=a.parentType,a.blkIndent=a.ddIndent=a.sCount[_]+2,a.tShift[_]=p-a.bMarks[_],a.sCount[_]=x,a.tight=!0,a.parentType="deflist",a.md.block.tokenize(a,_,c,!0),a.tight&&!S||(M=!1),S=a.line-_>1&&a.isEmpty(a.line-1),a.tShift[_]=T,a.sCount[_]=A,a.tight=L,a.parentType=y,a.blkIndent=C,a.ddIndent=k,F=a.push("dd_close","dd",-1),b[1]=v=a.line,v>=c||a.sCount[v]<a.blkIndent)break e;if((p=r(a,v))<0)break;_=v}if(v>=c||(h=v,a.isEmpty(h))||a.sCount[h]<a.blkIndent||(_=h+1)>=c||(a.isEmpty(_)&&_++,_>=c)||a.sCount[_]<a.blkIndent||(p=r(a,_))<0)break}return F=a.push("dl_close","dl",-1),m[1]=v,a.line=v,M&&n(a,g),!0}var t=e.utils.isSpace;e.block.ruler.before("paragraph","deflist",i,{alt:["paragraph","reference"]})}},function(f,d,s){var e=s(122),r=s(123),n=s(125),i=s(126),t=s(124);f.exports=function(a,u){var c={defs:e,shortcuts:r,enabled:[]},o=t(a.utils.assign({},c,u||{}));a.renderer.rules.emoji=n,a.core.ruler.push("emoji",i(a,o.defs,o.shortcuts,o.scanRE,o.replaceRE))}},function(f,d){f.exports={100:"💯",1234:"🔢",grinning:"😀",smiley:"😃",smile:"😄",grin:"😁",laughing:"😆",satisfied:"😆",sweat_smile:"😅",joy:"😂",rofl:"🤣",relaxed:"☺️",blush:"😊",innocent:"😇",slightly_smiling_face:"🙂",upside_down_face:"🙃",wink:"😉",relieved:"😌",heart_eyes:"😍",kissing_heart:"😘",kissing:"😗",kissing_smiling_eyes:"😙",kissing_closed_eyes:"😚",yum:"😋",stuck_out_tongue_winking_eye:"😜",stuck_out_tongue_closed_eyes:"😝",stuck_out_tongue:"😛",money_mouth_face:"🤑",hugs:"🤗",nerd_face:"🤓",sunglasses:"😎",clown_face:"🤡",cowboy_hat_face:"🤠",smirk:"😏",unamused:"😒",disappointed:"😞",pensive:"😔",worried:"😟",confused:"😕",slightly_frowning_face:"🙁",frowning_face:"☹️",persevere:"😣",confounded:"😖",tired_face:"😫",weary:"😩",triumph:"😤",angry:"😠",rage:"😡",pout:"😡",no_mouth:"😶",neutral_face:"😐",expressionless:"😑",hushed:"😯",frowning:"😦",anguished:"😧",open_mouth:"😮",astonished:"😲",dizzy_face:"😵",flushed:"😳",scream:"😱",fearful:"😨",cold_sweat:"😰",cry:"😢",disappointed_relieved:"😥",drooling_face:"🤤",sob:"😭",sweat:"😓",sleepy:"😪",sleeping:"😴",roll_eyes:"🙄",thinking:"🤔",lying_face:"🤥",grimacing:"😬",zipper_mouth_face:"🤐",nauseated_face:"🤢",sneezing_face:"🤧",mask:"😷",face_with_thermometer:"🤒",face_with_head_bandage:"🤕",smiling_imp:"😈",imp:"👿",japanese_ogre:"👹",japanese_goblin:"👺",hankey:"💩",poop:"💩",shit:"💩",ghost:"👻",skull:"💀",skull_and_crossbones:"☠️",alien:"👽",space_invader:"👾",robot:"🤖",jack_o_lantern:"🎃",smiley_cat:"😺",smile_cat:"😸",joy_cat:"😹",heart_eyes_cat:"😻",smirk_cat:"😼",kissing_cat:"😽",scream_cat:"🙀",crying_cat_face:"😿",pouting_cat:"😾",open_hands:"👐",raised_hands:"🙌",clap:"👏",pray:"🙏",handshake:"🤝","+1":"👍",thumbsup:"👍","-1":"👎",thumbsdown:"👎",fist_oncoming:"👊",facepunch:"👊",punch:"👊",fist_raised:"✊",fist:"✊",fist_left:"🤛",fist_right:"🤜",crossed_fingers:"🤞",v:"✌️",metal:"🤘",ok_hand:"👌",point_left:"👈",point_right:"👉",point_up_2:"👆",point_down:"👇",point_up:"☝️",hand:"✋",raised_hand:"✋",raised_back_of_hand:"🤚",raised_hand_with_fingers_splayed:"🖐",vulcan_salute:"🖖",wave:"👋",call_me_hand:"🤙",muscle:"💪",middle_finger:"🖕",fu:"🖕",writing_hand:"✍️",selfie:"🤳",nail_care:"💅",ring:"💍",lipstick:"💄",kiss:"💋",lips:"👄",tongue:"👅",ear:"👂",nose:"👃",footprints:"👣",eye:"👁",eyes:"👀",speaking_head:"🗣",bust_in_silhouette:"👤",busts_in_silhouette:"👥",baby:"👶",boy:"👦",girl:"👧",man:"👨",woman:"👩",blonde_woman:"👱‍♀",blonde_man:"👱",person_with_blond_hair:"👱",older_man:"👴",older_woman:"👵",man_with_gua_pi_mao:"👲",woman_with_turban:"👳‍♀",man_with_turban:"👳",policewoman:"👮‍♀",policeman:"👮",cop:"👮",construction_worker_woman:"👷‍♀",construction_worker_man:"👷",construction_worker:"👷",guardswoman:"💂‍♀",guardsman:"💂",female_detective:"🕵️‍♀️",male_detective:"🕵",detective:"🕵",woman_health_worker:"👩‍⚕",man_health_worker:"👨‍⚕",woman_farmer:"👩‍🌾",man_farmer:"👨‍🌾",woman_cook:"👩‍🍳",man_cook:"👨‍🍳",woman_student:"👩‍🎓",man_student:"👨‍🎓",woman_singer:"👩‍🎤",man_singer:"👨‍🎤",woman_teacher:"👩‍🏫",man_teacher:"👨‍🏫",woman_factory_worker:"👩‍🏭",man_factory_worker:"👨‍🏭",woman_technologist:"👩‍💻",man_technologist:"👨‍💻",woman_office_worker:"👩‍💼",man_office_worker:"👨‍💼",woman_mechanic:"👩‍🔧",man_mechanic:"👨‍🔧",woman_scientist:"👩‍🔬",man_scientist:"👨‍🔬",woman_artist:"👩‍🎨",man_artist:"👨‍🎨",woman_firefighter:"👩‍🚒",man_firefighter:"👨‍🚒",woman_pilot:"👩‍✈",man_pilot:"👨‍✈",woman_astronaut:"👩‍🚀",man_astronaut:"👨‍🚀",woman_judge:"👩‍⚖",man_judge:"👨‍⚖",mrs_claus:"🤶",santa:"🎅",princess:"👸",prince:"🤴",bride_with_veil:"👰",man_in_tuxedo:"🤵",angel:"👼",pregnant_woman:"🤰",bowing_woman:"🙇‍♀",bowing_man:"🙇",bow:"🙇",tipping_hand_woman:"💁",information_desk_person:"💁",sassy_woman:"💁",tipping_hand_man:"💁‍♂",sassy_man:"💁‍♂",no_good_woman:"🙅",no_good:"🙅",ng_woman:"🙅",no_good_man:"🙅‍♂",ng_man:"🙅‍♂",ok_woman:"🙆",ok_man:"🙆‍♂",raising_hand_woman:"🙋",raising_hand:"🙋",raising_hand_man:"🙋‍♂",woman_facepalming:"🤦‍♀",man_facepalming:"🤦‍♂",woman_shrugging:"🤷‍♀",man_shrugging:"🤷‍♂",pouting_woman:"🙎",person_with_pouting_face:"🙎",pouting_man:"🙎‍♂",frowning_woman:"🙍",person_frowning:"🙍",frowning_man:"🙍‍♂",haircut_woman:"💇",haircut:"💇",haircut_man:"💇‍♂",massage_woman:"💆",massage:"💆",massage_man:"💆‍♂",business_suit_levitating:"🕴",dancer:"💃",man_dancing:"🕺",dancing_women:"👯",dancers:"👯",dancing_men:"👯‍♂",walking_woman:"🚶‍♀",walking_man:"🚶",walking:"🚶",running_woman:"🏃‍♀",running_man:"🏃",runner:"🏃",running:"🏃",couple:"👫",two_women_holding_hands:"👭",two_men_holding_hands:"👬",couple_with_heart_woman_man:"💑",couple_with_heart:"💑",couple_with_heart_woman_woman:"👩‍❤️‍👩",couple_with_heart_man_man:"👨‍❤️‍👨",couplekiss_man_woman:"💏",couplekiss_woman_woman:"👩‍❤️‍💋‍👩",couplekiss_man_man:"👨‍❤️‍💋‍👨",family_man_woman_boy:"👪",family:"👪",family_man_woman_girl:"👨‍👩‍👧",family_man_woman_girl_boy:"👨‍👩‍👧‍👦",family_man_woman_boy_boy:"👨‍👩‍👦‍👦",family_man_woman_girl_girl:"👨‍👩‍👧‍👧",family_woman_woman_boy:"👩‍👩‍👦",family_woman_woman_girl:"👩‍👩‍👧",family_woman_woman_girl_boy:"👩‍👩‍👧‍👦",family_woman_woman_boy_boy:"👩‍👩‍👦‍👦",family_woman_woman_girl_girl:"👩‍👩‍👧‍👧",family_man_man_boy:"👨‍👨‍👦",family_man_man_girl:"👨‍👨‍👧",family_man_man_girl_boy:"👨‍👨‍👧‍👦",family_man_man_boy_boy:"👨‍👨‍👦‍👦",family_man_man_girl_girl:"👨‍👨‍👧‍👧",family_woman_boy:"👩‍👦",family_woman_girl:"👩‍👧",family_woman_girl_boy:"👩‍👧‍👦",family_woman_boy_boy:"👩‍👦‍👦",family_woman_girl_girl:"👩‍👧‍👧",family_man_boy:"👨‍👦",family_man_girl:"👨‍👧",family_man_girl_boy:"👨‍👧‍👦",family_man_boy_boy:"👨‍👦‍👦",family_man_girl_girl:"👨‍👧‍👧",womans_clothes:"👚",shirt:"👕",tshirt:"👕",jeans:"👖",necktie:"👔",dress:"👗",bikini:"👙",kimono:"👘",high_heel:"👠",sandal:"👡",boot:"👢",mans_shoe:"👞",shoe:"👞",athletic_shoe:"👟",womans_hat:"👒",tophat:"🎩",mortar_board:"🎓",crown:"👑",rescue_worker_helmet:"⛑",school_satchel:"🎒",pouch:"👝",purse:"👛",handbag:"👜",briefcase:"💼",eyeglasses:"👓",dark_sunglasses:"🕶",closed_umbrella:"🌂",open_umbrella:"☂️",dog:"🐶",cat:"🐱",mouse:"🐭",hamster:"🐹",rabbit:"🐰",fox_face:"🦊",bear:"🐻",panda_face:"🐼",koala:"🐨",tiger:"🐯",lion:"🦁",cow:"🐮",pig:"🐷",pig_nose:"🐽",frog:"🐸",monkey_face:"🐵",see_no_evil:"🙈",hear_no_evil:"🙉",speak_no_evil:"🙊",monkey:"🐒",chicken:"🐔",penguin:"🐧",bird:"🐦",baby_chick:"🐤",hatching_chick:"🐣",hatched_chick:"🐥",duck:"🦆",eagle:"🦅",owl:"🦉",bat:"🦇",wolf:"🐺",boar:"🐗",horse:"🐴",unicorn:"🦄",bee:"🐝",honeybee:"🐝",bug:"🐛",butterfly:"🦋",snail:"🐌",shell:"🐚",beetle:"🐞",ant:"🐜",spider:"🕷",spider_web:"🕸",turtle:"🐢",snake:"🐍",lizard:"🦎",scorpion:"🦂",crab:"🦀",squid:"🦑",octopus:"🐙",shrimp:"🦐",tropical_fish:"🐠",fish:"🐟",blowfish:"🐡",dolphin:"🐬",flipper:"🐬",shark:"🦈",whale:"🐳",whale2:"🐋",crocodile:"🐊",leopard:"🐆",tiger2:"🐅",water_buffalo:"🐃",ox:"🐂",cow2:"🐄",deer:"🦌",dromedary_camel:"🐪",camel:"🐫",elephant:"🐘",rhinoceros:"🦏",gorilla:"🦍",racehorse:"🐎",pig2:"🐖",goat:"🐐",ram:"🐏",sheep:"🐑",dog2:"🐕",poodle:"🐩",cat2:"🐈",rooster:"🐓",turkey:"🦃",dove:"🕊",rabbit2:"🐇",mouse2:"🐁",rat:"🐀",chipmunk:"🐿",feet:"🐾",paw_prints:"🐾",dragon:"🐉",dragon_face:"🐲",cactus:"🌵",christmas_tree:"🎄",evergreen_tree:"🌲",deciduous_tree:"🌳",palm_tree:"🌴",seedling:"🌱",herb:"🌿",shamrock:"☘️",four_leaf_clover:"🍀",bamboo:"🎍",tanabata_tree:"🎋",leaves:"🍃",fallen_leaf:"🍂",maple_leaf:"🍁",mushroom:"🍄",ear_of_rice:"🌾",bouquet:"💐",tulip:"🌷",rose:"🌹",wilted_flower:"🥀",sunflower:"🌻",blossom:"🌼",cherry_blossom:"🌸",hibiscus:"🌺",earth_americas:"🌎",earth_africa:"🌍",earth_asia:"🌏",full_moon:"🌕",waning_gibbous_moon:"🌖",last_quarter_moon:"🌗",waning_crescent_moon:"🌘",new_moon:"🌑",waxing_crescent_moon:"🌒",first_quarter_moon:"🌓",moon:"🌔",waxing_gibbous_moon:"🌔",new_moon_with_face:"🌚",full_moon_with_face:"🌝",sun_with_face:"🌞",first_quarter_moon_with_face:"🌛",last_quarter_moon_with_face:"🌜",crescent_moon:"🌙",dizzy:"💫",star:"⭐️",star2:"🌟",sparkles:"✨",zap:"⚡️",fire:"🔥",boom:"💥",collision:"💥",comet:"☄",sunny:"☀️",sun_behind_small_cloud:"🌤",partly_sunny:"⛅️",sun_behind_large_cloud:"🌥",sun_behind_rain_cloud:"🌦",rainbow:"🌈",cloud:"☁️",cloud_with_rain:"🌧",cloud_with_lightning_and_rain:"⛈",cloud_with_lightning:"🌩",cloud_with_snow:"🌨",snowman_with_snow:"☃️",snowman:"⛄️",snowflake:"❄️",wind_face:"🌬",dash:"💨",tornado:"🌪",fog:"🌫",ocean:"🌊",droplet:"💧",sweat_drops:"💦",umbrella:"☔️",green_apple:"🍏",apple:"🍎",pear:"🍐",tangerine:"🍊",orange:"🍊",mandarin:"🍊",lemon:"🍋",banana:"🍌",watermelon:"🍉",grapes:"🍇",strawberry:"🍓",melon:"🍈",cherries:"🍒",peach:"🍑",pineapple:"🍍",kiwi_fruit:"🥝",avocado:"🥑",tomato:"🍅",eggplant:"🍆",cucumber:"🥒",carrot:"🥕",corn:"🌽",hot_pepper:"🌶",potato:"🥔",sweet_potato:"🍠",chestnut:"🌰",peanuts:"🥜",honey_pot:"🍯",croissant:"🥐",bread:"🍞",baguette_bread:"🥖",cheese:"🧀",egg:"🥚",fried_egg:"🍳",bacon:"🥓",pancakes:"🥞",fried_shrimp:"🍤",poultry_leg:"🍗",meat_on_bone:"🍖",pizza:"🍕",hotdog:"🌭",hamburger:"🍔",fries:"🍟",stuffed_flatbread:"🥙",taco:"🌮",burrito:"🌯",green_salad:"🥗",shallow_pan_of_food:"🥘",spaghetti:"🍝",ramen:"🍜",stew:"🍲",fish_cake:"🍥",sushi:"🍣",bento:"🍱",curry:"🍛",rice:"🍚",rice_ball:"🍙",rice_cracker:"🍘",oden:"🍢",dango:"🍡",shaved_ice:"🍧",ice_cream:"🍨",icecream:"🍦",cake:"🍰",birthday:"🎂",custard:"🍮",lollipop:"🍭",candy:"🍬",chocolate_bar:"🍫",popcorn:"🍿",doughnut:"🍩",cookie:"🍪",milk_glass:"🥛",baby_bottle:"🍼",coffee:"☕️",tea:"🍵",sake:"🍶",beer:"🍺",beers:"🍻",clinking_glasses:"🥂",wine_glass:"🍷",tumbler_glass:"🥃",cocktail:"🍸",tropical_drink:"🍹",champagne:"🍾",spoon:"🥄",fork_and_knife:"🍴",plate_with_cutlery:"🍽",soccer:"⚽️",basketball:"🏀",football:"🏈",baseball:"⚾️",tennis:"🎾",volleyball:"🏐",rugby_football:"🏉","8ball":"🎱",ping_pong:"🏓",badminton:"🏸",goal_net:"🥅",ice_hockey:"🏒",field_hockey:"🏑",cricket:"🏏",golf:"⛳️",bow_and_arrow:"🏹",fishing_pole_and_fish:"🎣",boxing_glove:"🥊",martial_arts_uniform:"🥋",ice_skate:"⛸",ski:"🎿",skier:"⛷",snowboarder:"🏂",weight_lifting_woman:"🏋️‍♀️",weight_lifting_man:"🏋",person_fencing:"🤺",women_wrestling:"🤼‍♀",men_wrestling:"🤼‍♂",woman_cartwheeling:"🤸‍♀",man_cartwheeling:"🤸‍♂",basketball_woman:"⛹️‍♀️",basketball_man:"⛹",woman_playing_handball:"🤾‍♀",man_playing_handball:"🤾‍♂",golfing_woman:"🏌️‍♀️",golfing_man:"🏌",surfing_woman:"🏄‍♀",surfing_man:"🏄",surfer:"🏄",swimming_woman:"🏊‍♀",swimming_man:"🏊",swimmer:"🏊",woman_playing_water_polo:"🤽‍♀",man_playing_water_polo:"🤽‍♂",rowing_woman:"🚣‍♀",rowing_man:"🚣",rowboat:"🚣",horse_racing:"🏇",biking_woman:"🚴‍♀",biking_man:"🚴",bicyclist:"🚴",mountain_biking_woman:"🚵‍♀",mountain_biking_man:"🚵",mountain_bicyclist:"🚵",running_shirt_with_sash:"🎽",medal_sports:"🏅",medal_military:"🎖","1st_place_medal":"🥇","2nd_place_medal":"🥈","3rd_place_medal":"🥉",trophy:"🏆",rosette:"🏵",reminder_ribbon:"🎗",ticket:"🎫",tickets:"🎟",circus_tent:"🎪",woman_juggling:"🤹‍♀",man_juggling:"🤹‍♂",performing_arts:"🎭",art:"🎨",clapper:"🎬",microphone:"🎤",headphones:"🎧",musical_score:"🎼",musical_keyboard:"🎹",drum:"🥁",saxophone:"🎷",trumpet:"🎺",guitar:"🎸",violin:"🎻",game_die:"🎲",dart:"🎯",bowling:"🎳",video_game:"🎮",slot_machine:"🎰",car:"🚗",red_car:"🚗",taxi:"🚕",blue_car:"🚙",bus:"🚌",trolleybus:"🚎",racing_car:"🏎",police_car:"🚓",ambulance:"🚑",fire_engine:"🚒",minibus:"🚐",truck:"🚚",articulated_lorry:"🚛",tractor:"🚜",kick_scooter:"🛴",bike:"🚲",motor_scooter:"🛵",motorcycle:"🏍",rotating_light:"🚨",oncoming_police_car:"🚔",oncoming_bus:"🚍",oncoming_automobile:"🚘",oncoming_taxi:"🚖",aerial_tramway:"🚡",mountain_cableway:"🚠",suspension_railway:"🚟",railway_car:"🚃",train:"🚋",mountain_railway:"🚞",monorail:"🚝",bullettrain_side:"🚄",bullettrain_front:"🚅",light_rail:"🚈",steam_locomotive:"🚂",train2:"🚆",metro:"🚇",tram:"🚊",station:"🚉",helicopter:"🚁",small_airplane:"🛩",airplane:"✈️",flight_departure:"🛫",flight_arrival:"🛬",rocket:"🚀",artificial_satellite:"🛰",seat:"💺",canoe:"🛶",boat:"⛵️",sailboat:"⛵️",motor_boat:"🛥",speedboat:"🚤",passenger_ship:"🛳",ferry:"⛴",ship:"🚢",anchor:"⚓️",construction:"🚧",fuelpump:"⛽️",busstop:"🚏",vertical_traffic_light:"🚦",traffic_light:"🚥",world_map:"🗺",moyai:"🗿",statue_of_liberty:"🗽",fountain:"⛲️",tokyo_tower:"🗼",european_castle:"🏰",japanese_castle:"🏯",stadium:"🏟",ferris_wheel:"🎡",roller_coaster:"🎢",carousel_horse:"🎠",parasol_on_ground:"⛱",beach_umbrella:"🏖",desert_island:"🏝",mountain:"⛰",mountain_snow:"🏔",mount_fuji:"🗻",volcano:"🌋",desert:"🏜",camping:"🏕",tent:"⛺️",railway_track:"🛤",motorway:"🛣",building_construction:"🏗",factory:"🏭",house:"🏠",house_with_garden:"🏡",houses:"🏘",derelict_house:"🏚",office:"🏢",department_store:"🏬",post_office:"🏣",european_post_office:"🏤",hospital:"🏥",bank:"🏦",hotel:"🏨",convenience_store:"🏪",school:"🏫",love_hotel:"🏩",wedding:"💒",classical_building:"🏛",church:"⛪️",mosque:"🕌",synagogue:"🕍",kaaba:"🕋",shinto_shrine:"⛩",japan:"🗾",rice_scene:"🎑",national_park:"🏞",sunrise:"🌅",sunrise_over_mountains:"🌄",stars:"🌠",sparkler:"🎇",fireworks:"🎆",city_sunrise:"🌇",city_sunset:"🌆",cityscape:"🏙",night_with_stars:"🌃",milky_way:"🌌",bridge_at_night:"🌉",foggy:"🌁",watch:"⌚️",iphone:"📱",calling:"📲",computer:"💻",keyboard:"⌨️",desktop_computer:"🖥",printer:"🖨",computer_mouse:"🖱",trackball:"🖲",joystick:"🕹",clamp:"🗜",minidisc:"💽",floppy_disk:"💾",cd:"💿",dvd:"📀",vhs:"📼",camera:"📷",camera_flash:"📸",video_camera:"📹",movie_camera:"🎥",film_projector:"📽",film_strip:"🎞",telephone_receiver:"📞",phone:"☎️",telephone:"☎️",pager:"📟",fax:"📠",tv:"📺",radio:"📻",studio_microphone:"🎙",level_slider:"🎚",control_knobs:"🎛",stopwatch:"⏱",timer_clock:"⏲",alarm_clock:"⏰",mantelpiece_clock:"🕰",hourglass:"⌛️",hourglass_flowing_sand:"⏳",satellite:"📡",battery:"🔋",electric_plug:"🔌",bulb:"💡",flashlight:"🔦",candle:"🕯",wastebasket:"🗑",oil_drum:"🛢",money_with_wings:"💸",dollar:"💵",yen:"💴",euro:"💶",pound:"💷",moneybag:"💰",credit_card:"💳",gem:"💎",balance_scale:"⚖️",wrench:"🔧",hammer:"🔨",hammer_and_pick:"⚒",hammer_and_wrench:"🛠",pick:"⛏",nut_and_bolt:"🔩",gear:"⚙️",chains:"⛓",gun:"🔫",bomb:"💣",hocho:"🔪",knife:"🔪",dagger:"🗡",crossed_swords:"⚔️",shield:"🛡",smoking:"🚬",coffin:"⚰️",funeral_urn:"⚱️",amphora:"🏺",crystal_ball:"🔮",prayer_beads:"📿",barber:"💈",alembic:"⚗️",telescope:"🔭",microscope:"🔬",hole:"🕳",pill:"💊",syringe:"💉",thermometer:"🌡",toilet:"🚽",potable_water:"🚰",shower:"🚿",bathtub:"🛁",bath:"🛀",bellhop_bell:"🛎",key:"🔑",old_key:"🗝",door:"🚪",couch_and_lamp:"🛋",bed:"🛏",sleeping_bed:"🛌",framed_picture:"🖼",shopping:"🛍",shopping_cart:"🛒",gift:"🎁",balloon:"🎈",flags:"🎏",ribbon:"🎀",confetti_ball:"🎊",tada:"🎉",dolls:"🎎",izakaya_lantern:"🏮",lantern:"🏮",wind_chime:"🎐",email:"✉️",envelope:"✉️",envelope_with_arrow:"📩",incoming_envelope:"📨","e-mail":"📧",love_letter:"💌",inbox_tray:"📥",outbox_tray:"📤",package:"📦",label:"🏷",mailbox_closed:"📪",mailbox:"📫",mailbox_with_mail:"📬",mailbox_with_no_mail:"📭",postbox:"📮",postal_horn:"📯",scroll:"📜",page_with_curl:"📃",page_facing_up:"📄",bookmark_tabs:"📑",bar_chart:"📊",chart_with_upwards_trend:"📈",chart_with_downwards_trend:"📉",spiral_notepad:"🗒",spiral_calendar:"🗓",calendar:"📆",date:"📅",card_index:"📇",card_file_box:"🗃",ballot_box:"🗳",file_cabinet:"🗄",clipboard:"📋",file_folder:"📁",open_file_folder:"📂",card_index_dividers:"🗂",newspaper_roll:"🗞",newspaper:"📰",notebook:"📓",notebook_with_decorative_cover:"📔",ledger:"📒",closed_book:"📕",green_book:"📗",blue_book:"📘",orange_book:"📙",books:"📚",book:"📖",open_book:"📖",bookmark:"🔖",link:"🔗",paperclip:"📎",paperclips:"🖇",triangular_ruler:"📐",straight_ruler:"📏",pushpin:"📌",round_pushpin:"📍",scissors:"✂️",pen:"🖊",fountain_pen:"🖋",black_nib:"✒️",paintbrush:"🖌",crayon:"🖍",memo:"📝",pencil:"📝",pencil2:"✏️",mag:"🔍",mag_right:"🔎",lock_with_ink_pen:"🔏",closed_lock_with_key:"🔐",lock:"🔒",unlock:"🔓",heart:"❤️",yellow_heart:"💛",green_heart:"💚",blue_heart:"💙",purple_heart:"💜",black_heart:"🖤",broken_heart:"💔",heavy_heart_exclamation:"❣️",two_hearts:"💕",revolving_hearts:"💞",heartbeat:"💓",heartpulse:"💗",sparkling_heart:"💖",cupid:"💘",gift_heart:"💝",heart_decoration:"💟",peace_symbol:"☮️",latin_cross:"✝️",star_and_crescent:"☪️",om:"🕉",wheel_of_dharma:"☸️",star_of_david:"✡️",six_pointed_star:"🔯",menorah:"🕎",yin_yang:"☯️",orthodox_cross:"☦️",place_of_worship:"🛐",ophiuchus:"⛎",aries:"♈️",taurus:"♉️",gemini:"♊️",cancer:"♋️",leo:"♌️",virgo:"♍️",libra:"♎️",scorpius:"♏️",sagittarius:"♐️",capricorn:"♑️",aquarius:"♒️",pisces:"♓️",id:"🆔",atom_symbol:"⚛️",accept:"🉑",radioactive:"☢️",biohazard:"☣️",mobile_phone_off:"📴",vibration_mode:"📳",eight_pointed_black_star:"✴️",vs:"🆚",white_flower:"💮",ideograph_advantage:"🉐",secret:"㊙️",congratulations:"㊗️",u6e80:"🈵",a:"🅰️",b:"🅱️",ab:"🆎",cl:"🆑",o2:"🅾️",sos:"🆘",x:"❌",o:"⭕️",stop_sign:"🛑",no_entry:"⛔️",name_badge:"📛",no_entry_sign:"🚫",anger:"💢",hotsprings:"♨️",no_pedestrians:"🚷",do_not_litter:"🚯",no_bicycles:"🚳","non-potable_water":"🚱",underage:"🔞",no_mobile_phones:"📵",no_smoking:"🚭",exclamation:"❗️",heavy_exclamation_mark:"❗️",grey_exclamation:"❕",question:"❓",grey_question:"❔",bangbang:"‼️",interrobang:"⁉️",low_brightness:"🔅",high_brightness:"🔆",part_alternation_mark:"〽️",warning:"⚠️",children_crossing:"🚸",trident:"🔱",fleur_de_lis:"⚜️",beginner:"🔰",recycle:"♻️",white_check_mark:"✅",chart:"💹",sparkle:"❇️",eight_spoked_asterisk:"✳️",negative_squared_cross_mark:"❎",globe_with_meridians:"🌐",diamond_shape_with_a_dot_inside:"💠",m:"Ⓜ️",cyclone:"🌀",zzz:"💤",atm:"🏧",wc:"🚾",wheelchair:"♿️",parking:"🅿️",sa:"🈂️",passport_control:"🛂",customs:"🛃",baggage_claim:"🛄",left_luggage:"🛅",mens:"🚹",womens:"🚺",baby_symbol:"🚼",restroom:"🚻",put_litter_in_its_place:"🚮",cinema:"🎦",signal_strength:"📶",koko:"🈁",symbols:"🔣",information_source:"ℹ️",abc:"🔤",abcd:"🔡",capital_abcd:"🔠",ng:"🆖",ok:"🆗",up:"🆙",cool:"🆒",new:"🆕",free:"🆓",zero:"0️⃣",one:"1️⃣",two:"2️⃣",three:"3️⃣",four:"4️⃣",five:"5️⃣",six:"6️⃣",seven:"7️⃣",eight:"8️⃣",nine:"9️⃣",keycap_ten:"🔟",hash:"#️⃣",asterisk:"*️⃣",arrow_forward:"▶️",pause_button:"⏸",play_or_pause_button:"⏯",stop_button:"⏹",record_button:"⏺",next_track_button:"⏭",previous_track_button:"⏮",fast_forward:"⏩",rewind:"⏪",arrow_double_up:"⏫",arrow_double_down:"⏬",arrow_backward:"◀️",arrow_up_small:"🔼",arrow_down_small:"🔽",arrow_right:"➡️",arrow_left:"⬅️",arrow_up:"⬆️",arrow_down:"⬇️",arrow_upper_right:"↗️",arrow_lower_right:"↘️",arrow_lower_left:"↙️",arrow_upper_left:"↖️",arrow_up_down:"↕️",left_right_arrow:"↔️",arrow_right_hook:"↪️",leftwards_arrow_with_hook:"↩️",arrow_heading_up:"⤴️",arrow_heading_down:"⤵️",twisted_rightwards_arrows:"🔀",repeat:"🔁",repeat_one:"🔂",arrows_counterclockwise:"🔄",arrows_clockwise:"🔃",musical_note:"🎵",notes:"🎶",heavy_plus_sign:"➕",heavy_minus_sign:"➖",heavy_division_sign:"➗",heavy_multiplication_x:"✖️",heavy_dollar_sign:"💲",currency_exchange:"💱",tm:"™️",copyright:"©️",registered:"®️",wavy_dash:"〰️",curly_loop:"➰",loop:"➿",end:"🔚",back:"🔙",on:"🔛",top:"🔝",soon:"🔜",heavy_check_mark:"✔️",ballot_box_with_check:"☑️",radio_button:"🔘",white_circle:"⚪️",black_circle:"⚫️",red_circle:"🔴",large_blue_circle:"🔵",small_red_triangle:"🔺",small_red_triangle_down:"🔻",small_orange_diamond:"🔸",small_blue_diamond:"🔹",large_orange_diamond:"🔶",large_blue_diamond:"🔷",white_square_button:"🔳",black_square_button:"🔲",black_small_square:"▪️",white_small_square:"▫️",black_medium_small_square:"◾️",white_medium_small_square:"◽️",black_medium_square:"◼️",white_medium_square:"◻️",black_large_square:"⬛️",white_large_square:"⬜️",speaker:"🔈",mute:"🔇",sound:"🔉",loud_sound:"🔊",bell:"🔔",no_bell:"🔕",mega:"📣",loudspeaker:"📢",eye_speech_bubble:"👁‍🗨",speech_balloon:"💬",thought_balloon:"💭",right_anger_bubble:"🗯",spades:"♠️",clubs:"♣️",hearts:"♥️",diamonds:"♦️",black_joker:"🃏",flower_playing_cards:"🎴",mahjong:"🀄️",clock1:"🕐",clock2:"🕑",clock3:"🕒",clock4:"🕓",clock5:"🕔",clock6:"🕕",clock7:"🕖",clock8:"🕗",clock9:"🕘",clock10:"🕙",clock11:"🕚",clock12:"🕛",clock130:"🕜",clock230:"🕝",clock330:"🕞",clock430:"🕟",clock530:"🕠",clock630:"🕡",clock730:"🕢",clock830:"🕣",clock930:"🕤",clock1030:"🕥",clock1130:"🕦",clock1230:"🕧",white_flag:"🏳️",black_flag:"🏴",checkered_flag:"🏁",triangular_flag_on_post:"🚩",rainbow_flag:"🏳️‍🌈",afghanistan:"🇦🇫",aland_islands:"🇦🇽",albania:"🇦🇱",algeria:"🇩🇿",american_samoa:"🇦🇸",andorra:"🇦🇩",angola:"🇦🇴",anguilla:"🇦🇮",antarctica:"🇦🇶",antigua_barbuda:"🇦🇬",argentina:"🇦🇷",armenia:"🇦🇲",aruba:"🇦🇼",australia:"🇦🇺",austria:"🇦🇹",azerbaijan:"🇦🇿",bahamas:"🇧🇸",bahrain:"🇧🇭",bangladesh:"🇧🇩",barbados:"🇧🇧",belarus:"🇧🇾",belgium:"🇧🇪",belize:"🇧🇿",benin:"🇧🇯",bermuda:"🇧🇲",bhutan:"🇧🇹",bolivia:"🇧🇴",caribbean_netherlands:"🇧🇶",bosnia_herzegovina:"🇧🇦",botswana:"🇧🇼",brazil:"🇧🇷",british_indian_ocean_territory:"🇮🇴",british_virgin_islands:"🇻🇬",brunei:"🇧🇳",bulgaria:"🇧🇬",burkina_faso:"🇧🇫",burundi:"🇧🇮",cape_verde:"🇨🇻",cambodia:"🇰🇭",cameroon:"🇨🇲",canada:"🇨🇦",canary_islands:"🇮🇨",cayman_islands:"🇰🇾",central_african_republic:"🇨🇫",chad:"🇹🇩",chile:"🇨🇱",cn:"🇨🇳",christmas_island:"🇨🇽",cocos_islands:"🇨🇨",colombia:"🇨🇴",comoros:"🇰🇲",congo_brazzaville:"🇨🇬",congo_kinshasa:"🇨🇩",cook_islands:"🇨🇰",costa_rica:"🇨🇷",cote_divoire:"🇨🇮",croatia:"🇭🇷",cuba:"🇨🇺",curacao:"🇨🇼",cyprus:"🇨🇾",czech_republic:"🇨🇿",denmark:"🇩🇰",djibouti:"🇩🇯",dominica:"🇩🇲",dominican_republic:"🇩🇴",ecuador:"🇪🇨",egypt:"🇪🇬",el_salvador:"🇸🇻",equatorial_guinea:"🇬🇶",eritrea:"🇪🇷",estonia:"🇪🇪",ethiopia:"🇪🇹",eu:"🇪🇺",european_union:"🇪🇺",falkland_islands:"🇫🇰",faroe_islands:"🇫🇴",fiji:"🇫🇯",finland:"🇫🇮",fr:"🇫🇷",french_guiana:"🇬🇫",french_polynesia:"🇵🇫",french_southern_territories:"🇹🇫",gabon:"🇬🇦",gambia:"🇬🇲",georgia:"🇬🇪",de:"🇩🇪",ghana:"🇬🇭",gibraltar:"🇬🇮",greece:"🇬🇷",greenland:"🇬🇱",grenada:"🇬🇩",guadeloupe:"🇬🇵",guam:"🇬🇺",guatemala:"🇬🇹",guernsey:"🇬🇬",guinea:"🇬🇳",guinea_bissau:"🇬🇼",guyana:"🇬🇾",haiti:"🇭🇹",honduras:"🇭🇳",hong_kong:"🇭🇰",hungary:"🇭🇺",iceland:"🇮🇸",india:"🇮🇳",indonesia:"🇮🇩",iran:"🇮🇷",iraq:"🇮🇶",ireland:"🇮🇪",isle_of_man:"🇮🇲",israel:"🇮🇱",it:"🇮🇹",jamaica:"🇯🇲",jp:"🇯🇵",crossed_flags:"🎌",jersey:"🇯🇪",jordan:"🇯🇴",kazakhstan:"🇰🇿",kenya:"🇰🇪",kiribati:"🇰🇮",kosovo:"🇽🇰",kuwait:"🇰🇼",kyrgyzstan:"🇰🇬",laos:"🇱🇦",latvia:"🇱🇻",lebanon:"🇱🇧",lesotho:"🇱🇸",liberia:"🇱🇷",libya:"🇱🇾",liechtenstein:"🇱🇮",lithuania:"🇱🇹",luxembourg:"🇱🇺",macau:"🇲🇴",macedonia:"🇲🇰",madagascar:"🇲🇬",malawi:"🇲🇼",malaysia:"🇲🇾",maldives:"🇲🇻",mali:"🇲🇱",malta:"🇲🇹",marshall_islands:"🇲🇭",martinique:"🇲🇶",mauritania:"🇲🇷",mauritius:"🇲🇺",mayotte:"🇾🇹",mexico:"🇲🇽",micronesia:"🇫🇲",moldova:"🇲🇩",monaco:"🇲🇨",mongolia:"🇲🇳",montenegro:"🇲🇪",montserrat:"🇲🇸",morocco:"🇲🇦",mozambique:"🇲🇿",myanmar:"🇲🇲",namibia:"🇳🇦",nauru:"🇳🇷",nepal:"🇳🇵",netherlands:"🇳🇱",new_caledonia:"🇳🇨",new_zealand:"🇳🇿",nicaragua:"🇳🇮",niger:"🇳🇪",nigeria:"🇳🇬",niue:"🇳🇺",norfolk_island:"🇳🇫",northern_mariana_islands:"🇲🇵",north_korea:"🇰🇵",norway:"🇳🇴",oman:"🇴🇲",pakistan:"🇵🇰",palau:"🇵🇼",palestinian_territories:"🇵🇸",panama:"🇵🇦",papua_new_guinea:"🇵🇬",paraguay:"🇵🇾",peru:"🇵🇪",philippines:"🇵🇭",pitcairn_islands:"🇵🇳",poland:"🇵🇱",portugal:"🇵🇹",puerto_rico:"🇵🇷",qatar:"🇶🇦",reunion:"🇷🇪",romania:"🇷🇴",ru:"🇷🇺",rwanda:"🇷🇼",st_barthelemy:"🇧🇱",st_helena:"🇸🇭",st_kitts_nevis:"🇰🇳",st_lucia:"🇱🇨",st_pierre_miquelon:"🇵🇲",st_vincent_grenadines:"🇻🇨",samoa:"🇼🇸",san_marino:"🇸🇲",sao_tome_principe:"🇸🇹",saudi_arabia:"🇸🇦",senegal:"🇸🇳",serbia:"🇷🇸",seychelles:"🇸🇨",sierra_leone:"🇸🇱",singapore:"🇸🇬",sint_maarten:"🇸🇽",slovakia:"🇸🇰",slovenia:"🇸🇮",solomon_islands:"🇸🇧",somalia:"🇸🇴",south_africa:"🇿🇦",south_georgia_south_sandwich_islands:"🇬🇸",kr:"🇰🇷",south_sudan:"🇸🇸",es:"🇪🇸",sri_lanka:"🇱🇰",sudan:"🇸🇩",suriname:"🇸🇷",swaziland:"🇸🇿",sweden:"🇸🇪",switzerland:"🇨🇭",syria:"🇸🇾",taiwan:"🇹🇼",tajikistan:"🇹🇯",tanzania:"🇹🇿",thailand:"🇹🇭",timor_leste:"🇹🇱",togo:"🇹🇬",tokelau:"🇹🇰",tonga:"🇹🇴",trinidad_tobago:"🇹🇹",tunisia:"🇹🇳",tr:"🇹🇷",turkmenistan:"🇹🇲",turks_caicos_islands:"🇹🇨",tuvalu:"🇹🇻",uganda:"🇺🇬",ukraine:"🇺🇦",united_arab_emirates:"🇦🇪",gb:"🇬🇧",uk:"🇬🇧",us:"🇺🇸",us_virgin_islands:"🇻🇮",uruguay:"🇺🇾",uzbekistan:"🇺🇿",vanuatu:"🇻🇺",vatican_city:"🇻🇦",venezuela:"🇻🇪",vietnam:"🇻🇳",wallis_futuna:"🇼🇫",western_sahara:"🇪🇭",yemen:"🇾🇪",zambia:"🇿🇲",zimbabwe:"🇿🇼"}},function(f,d,s){f.exports={angry:[">:(",">:-("],blush:[':")',':-")'],broken_heart:["</3","<\\3"],confused:[":/",":-/"],cry:[":'(",":'-(",":,(",":,-("],frowning:[":(",":-("],heart:["<3"],imp:["]:(","]:-("],innocent:["o:)","O:)","o:-)","O:-)","0:)","0:-)"],joy:[":')",":'-)",":,)",":,-)",":'D",":'-D",":,D",":,-D"],kissing:[":*",":-*"],laughing:["x-)","X-)"],neutral_face:[":|",":-|"],open_mouth:[":o",":-o",":O",":-O"],rage:[":@",":-@"],smile:[":D",":-D"],smiley:[":)",":-)"],smiling_imp:["]:)","]:-)"],sob:[":,'(",":,'-(",";(",";-("],stuck_out_tongue:[":P",":-P"],sunglasses:["8-)","B-)"],sweat:[",:(",",:-("],sweat_smile:[",:)",",:-)"],unamused:[":s",":-S",":z",":-Z",":$",":-$"],wink:[";)",";-)"]}},function(f,d,s){function e(r){return r.replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&")}f.exports=function(r){var n,i=r.defs;r.enabled.length&&(i=Object.keys(i).reduce(function(c,o){return r.enabled.indexOf(o)>=0&&(c[o]=i[o]),c},{})),n=Object.keys(r.shortcuts).reduce(function(c,o){return i[o]?Array.isArray(r.shortcuts[o])?(r.shortcuts[o].forEach(function(l){c[l]=o}),c):(c[r.shortcuts[o]]=o,c):c},{});var t=Object.keys(i).map(function(c){return":"+c+":"}).concat(Object.keys(n)).sort().reverse().map(function(c){return e(c)}).join("|"),a=RegExp(t),u=RegExp(t,"g");return{defs:i,shortcuts:n,scanRE:a,replaceRE:u}}},function(f,d,s){f.exports=function(e,r){return e[r].content}},function(f,d,s){f.exports=function(e,r,n,i,t){function a(l,p,_){var h,b=0,m=[];return l.replace(t,function(g,w,v){var x;if(n.hasOwnProperty(g)){if(x=n[g],w>0&&!o.test(v[w-1])||w+g.length<v.length&&!o.test(v[w+g.length]))return}else x=g.slice(1,-1);w>b&&(h=new _("text","",0),h.content=l.slice(b,w),m.push(h)),h=new _("emoji","",0),h.markup=x,h.content=r[x],m.push(h),b=w+g.length}),b<l.length&&(h=new _("text","",0),h.content=l.slice(b),m.push(h)),m}var u=e.utils.arrayReplaceAt,c=e.utils.lib.ucmicro,o=new RegExp([c.Z.source,c.P.source,c.Cc.source].join("|"));return function(l){var p,_,h,b,m,g=l.tokens,w=0;for(_=0,h=g.length;_<h;_++)if(g[_].type==="inline")for(b=g[_].children,p=b.length-1;p>=0;p--)m=b[p],m.type!=="link_open"&&m.type!=="link_close"||m.info==="auto"&&(w-=m.nesting),m.type==="text"&&w===0&&i.test(m.content)&&(g[_].children=b=u(b,p,a(m.content,m.level,l.Token)))}}},function(f,d,s){function e(o,l,p,_){var h=Number(o[l].meta.id+1).toString(),b="";return typeof _.docId=="string"&&(b="-"+_.docId+"-"),b+h}function r(o,l){var p=Number(o[l].meta.id+1).toString();return o[l].meta.subId>0&&(p+=":"+o[l].meta.subId),"["+p+"]"}function n(o,l,p,_,h){var b=h.rules.footnote_anchor_name(o,l,p,_,h),m=h.rules.footnote_caption(o,l,p,_,h),g=b;return o[l].meta.subId>0&&(g+=":"+o[l].meta.subId),'<sup class="footnote-ref"><a href="#fn'+b+'" id="fnref'+g+'">'+m+"</a></sup>"}function i(o,l,p){return(p.xhtmlOut?`<hr class="footnotes-sep" />
`:`<hr class="footnotes-sep">
`)+`<section class="footnotes">
<ol class="footnotes-list">
`}function t(){return`</ol>
</section>
`}function a(o,l,p,_,h){var b=h.rules.footnote_anchor_name(o,l,p,_,h);return o[l].meta.subId>0&&(b+=":"+o[l].meta.subId),'<li id="fn'+b+'" class="footnote-item">'}function u(){return`</li>
`}function c(o,l,p,_,h){var b=h.rules.footnote_anchor_name(o,l,p,_,h);return o[l].meta.subId>0&&(b+=":"+o[l].meta.subId),' <a href="#fnref'+b+'" class="footnote-backref">↩︎</a>'}f.exports=function(o){function l(g,w,v,x){var k,C,y,A,T,L,j,S,M,F,E,q=g.bMarks[w]+g.tShift[w],O=g.eMarks[w];if(q+4>O||g.src.charCodeAt(q)!==91||g.src.charCodeAt(q+1)!==94)return!1;for(T=q+2;T<O;T++){if(g.src.charCodeAt(T)===32)return!1;if(g.src.charCodeAt(T)===93)break}if(T===q+2||T+1>=O||g.src.charCodeAt(++T)!==58)return!1;if(x)return!0;for(T++,g.env.footnotes||(g.env.footnotes={}),g.env.footnotes.refs||(g.env.footnotes.refs={}),L=g.src.slice(q+2,T-2),g.env.footnotes.refs[":"+L]=-1,j=new g.Token("footnote_reference_open","",1),j.meta={label:L},j.level=g.level++,g.tokens.push(j),k=g.bMarks[w],C=g.tShift[w],y=g.sCount[w],A=g.parentType,E=T,S=M=g.sCount[w]+T-(g.bMarks[w]+g.tShift[w]);T<O&&(F=g.src.charCodeAt(T),m(F));)F===9?M+=4-M%4:M++,T++;return g.tShift[w]=T-E,g.sCount[w]=M-S,g.bMarks[w]=E,g.blkIndent+=4,g.parentType="footnote",g.sCount[w]<g.blkIndent&&(g.sCount[w]+=g.blkIndent),g.md.block.tokenize(g,w,v,!0),g.parentType=A,g.blkIndent-=4,g.tShift[w]=C,g.sCount[w]=y,g.bMarks[w]=k,j=new g.Token("footnote_reference_close","",-1),j.level=--g.level,g.tokens.push(j),!0}function p(g,w){var v,x,k,C,y,A=g.posMax,T=g.pos;return!(T+2>=A)&&g.src.charCodeAt(T)===94&&g.src.charCodeAt(T+1)===91&&(v=T+2,!((x=b(g,T+1))<0)&&(w||(g.env.footnotes||(g.env.footnotes={}),g.env.footnotes.list||(g.env.footnotes.list=[]),k=g.env.footnotes.list.length,g.md.inline.parse(g.src.slice(v,x),g.md,g.env,y=[]),C=g.push("footnote_ref","",0),C.meta={id:k},g.env.footnotes.list[k]={content:g.src.slice(v,x),tokens:y}),g.pos=x+1,g.posMax=A,!0))}function _(g,w){var v,x,k,C,y,A=g.posMax,T=g.pos;if(T+3>A||!g.env.footnotes||!g.env.footnotes.refs||g.src.charCodeAt(T)!==91||g.src.charCodeAt(T+1)!==94)return!1;for(x=T+2;x<A;x++){if(g.src.charCodeAt(x)===32||g.src.charCodeAt(x)===10)return!1;if(g.src.charCodeAt(x)===93)break}return x!==T+2&&!(x>=A)&&(x++,v=g.src.slice(T+2,x-1),g.env.footnotes.refs[":"+v]!==void 0&&(w||(g.env.footnotes.list||(g.env.footnotes.list=[]),g.env.footnotes.refs[":"+v]<0?(k=g.env.footnotes.list.length,g.env.footnotes.list[k]={label:v,count:0},g.env.footnotes.refs[":"+v]=k):k=g.env.footnotes.refs[":"+v],C=g.env.footnotes.list[k].count,g.env.footnotes.list[k].count++,y=g.push("footnote_ref","",0),y.meta={id:k,subId:C,label:v}),g.pos=x,g.posMax=A,!0))}function h(g){var w,v,x,k,C,y,A,T,L,j,S=!1,M={};if(g.env.footnotes&&(g.tokens=g.tokens.filter(function(F){return F.type==="footnote_reference_open"?(S=!0,L=[],j=F.meta.label,!1):F.type==="footnote_reference_close"?(S=!1,M[":"+j]=L,!1):(S&&L.push(F),!S)}),g.env.footnotes.list)){for(y=g.env.footnotes.list,A=new g.Token("footnote_block_open","",1),g.tokens.push(A),w=0,v=y.length;w<v;w++){for(A=new g.Token("footnote_open","",1),A.meta={id:w,label:y[w].label},g.tokens.push(A),y[w].tokens?(T=[],A=new g.Token("paragraph_open","p",1),A.block=!0,T.push(A),A=new g.Token("inline","",0),A.children=y[w].tokens,A.content=y[w].content,T.push(A),A=new g.Token("paragraph_close","p",-1),A.block=!0,T.push(A)):y[w].label&&(T=M[":"+y[w].label]),g.tokens=g.tokens.concat(T),C=g.tokens[g.tokens.length-1].type==="paragraph_close"?g.tokens.pop():null,k=y[w].count>0?y[w].count:1,x=0;x<k;x++)A=new g.Token("footnote_anchor","",0),A.meta={id:w,subId:x,label:y[w].label},g.tokens.push(A);C&&g.tokens.push(C),A=new g.Token("footnote_close","",-1),g.tokens.push(A)}A=new g.Token("footnote_block_close","",-1),g.tokens.push(A)}}var b=o.helpers.parseLinkLabel,m=o.utils.isSpace;o.renderer.rules.footnote_ref=n,o.renderer.rules.footnote_block_open=i,o.renderer.rules.footnote_block_close=t,o.renderer.rules.footnote_open=a,o.renderer.rules.footnote_close=u,o.renderer.rules.footnote_anchor=c,o.renderer.rules.footnote_caption=r,o.renderer.rules.footnote_anchor_name=e,o.block.ruler.before("reference","footnote_def",l,{alt:["paragraph","reference"]}),o.inline.ruler.after("image","footnote_inline",p),o.inline.ruler.after("footnote_inline","footnote_ref",_),o.core.ruler.after("inline","footnote_tail",h)}},function(f,d){var s=function(e,r){r=r||{},r.highlighted===void 0&&(r.highlighted=!0),r.hljs===void 0&&(r.hljs="auto"),typeof r.langCheck!="function"&&(r.langCheck=function(){}),e.options.highlight=function(n,i){var t=r.hljs;if(r.hljs==="auto"&&(t=window.hljs),r.highlighted&&i&&t){if(t.getLanguage(i))return'<pre><div class="hljs"><code class="'+e.options.langPrefix+i+'">'+t.highlight(i,n,!0).value+"</code></div></pre>";typeof r.langCheck=="function"&&r.langCheck(i)}return'<pre><code class="'+e.options.langPrefix+i+'">'+e.utils.escapeHtml(n)+"</code></pre>"}};f.exports=s},function(f,d){f.exports=function(s,e){s.image_add=function(n,i){s.__image instanceof Object||(s.__image={}),s.__image[n]=i},s.image_del=function(n){s.__image instanceof Object||(s.__image={}),delete s.__image[n]};var r=s.renderer.rules.image;s.renderer.rules.image=function(n,i,t,a,u){var c=n[i].attrs;if(s.__image instanceof Object){for(var o=0;o<c.length;o++)if(c[o][0]=="src"&&s.__image.hasOwnProperty(n[i].attrs[o][1])){c.push(["rel",c[o][1]]),c[o][1]=s.__image[n[i].attrs[o][1]];break}}return r(n,i,t,a,u)}}},function(f,d,s){f.exports=function(e){function r(i,t){var a,u,c,o,l,p=i.pos,_=i.src.charCodeAt(p);if(t||_!==43||(u=i.scanDelims(i.pos,!0),o=u.length,l=String.fromCharCode(_),o<2))return!1;for(o%2&&(c=i.push("text","",0),c.content=l,o--),a=0;a<o;a+=2)c=i.push("text","",0),c.content=l+l,i.delimiters.push({marker:_,jump:a,token:i.tokens.length-1,level:i.level,end:-1,open:u.can_open,close:u.can_close});return i.pos+=u.length,!0}function n(i){var t,a,u,c,o,l=[],p=i.delimiters,_=i.delimiters.length;for(t=0;t<_;t++)u=p[t],u.marker===43&&u.end!==-1&&(c=p[u.end],o=i.tokens[u.token],o.type="ins_open",o.tag="ins",o.nesting=1,o.markup="++",o.content="",o=i.tokens[c.token],o.type="ins_close",o.tag="ins",o.nesting=-1,o.markup="++",o.content="",i.tokens[c.token-1].type==="text"&&i.tokens[c.token-1].content==="+"&&l.push(c.token-1));for(;l.length;){for(t=l.pop(),a=t+1;a<i.tokens.length&&i.tokens[a].type==="ins_close";)a++;a--,t!==a&&(o=i.tokens[a],i.tokens[a]=i.tokens[t],i.tokens[t]=o)}}e.inline.ruler.before("emphasis","ins",r),e.inline.ruler2.before("emphasis","ins",n)}},function(f,d,s){function e(t,a){var u,c,o=t.posMax,l=!0,p=!0;return u=a>0?t.src.charCodeAt(a-1):-1,c=a+1<=o?t.src.charCodeAt(a+1):-1,(u===32||u===9||c>=48&&c<=57)&&(p=!1),c!==32&&c!==9||(l=!1),{can_open:l,can_close:p}}function r(t,a){if(!i&&window.katex&&(i=window.katex),!i)return!1;var u,c,o,l,p;if(t.src[t.pos]!=="$")return!1;if(l=e(t,t.pos),!l.can_open)return a||(t.pending+="$"),t.pos+=1,!0;for(u=t.pos+1,c=u;(c=t.src.indexOf("$",c))!==-1;){for(p=c-1;t.src[p]==="\\";)p-=1;if((c-p)%2==1)break;c+=1}return c===-1?(a||(t.pending+="$"),t.pos=u,!0):c-u==0?(a||(t.pending+="$$"),t.pos=u+1,!0):(l=e(t,c),l.can_close?(a||(o=t.push("math_inline","math",0),o.markup="$",o.content=t.src.slice(u,c)),t.pos=c+1,!0):(a||(t.pending+="$"),t.pos=u,!0))}function n(t,a,u,c){if(!i&&window.katex&&(i=window.katex),!i)return!1;var o,l,p,_,h,b=!1,m=t.bMarks[a]+t.tShift[a],g=t.eMarks[a];if(m+2>g||t.src.slice(m,m+2)!=="$$")return!1;if(m+=2,o=t.src.slice(m,g),c)return!0;for(o.trim().slice(-2)==="$$"&&(o=o.trim().slice(0,-2),b=!0),p=a;!b&&!(++p>=u)&&(m=t.bMarks[p]+t.tShift[p],g=t.eMarks[p],!(m<g&&t.tShift[p]<t.blkIndent));)t.src.slice(m,g).trim().slice(-2)==="$$"&&(_=t.src.slice(0,g).lastIndexOf("$$"),l=t.src.slice(m,_),b=!0);return t.line=p+1,h=t.push("math_block","math",0),h.block=!0,h.content=(o&&o.trim()?o+`
`:"")+t.getLines(a+1,p,t.tShift[a],!0)+(l&&l.trim()?l:""),h.map=[a,t.line],h.markup="$$",!0}var i=null;f.exports=function(t,a){a=a||{};var u=function(p){!i&&window.katex&&(i=window.katex),a.displayMode=!1;try{return i.renderToString(p,a)}catch(_){return a.throwOnError&&console.log(_),p}},c=function(p,_){return u(p[_].content)},o=function(p){!i&&window.katex&&(i=window.katex),a.displayMode=!0;try{return"<p>"+i.renderToString(p,a)+"</p>"}catch(_){return a.throwOnError&&console.log(_),p}},l=function(p,_){return o(p[_].content)+`
`};t.inline.ruler.after("escape","math_inline",r),t.block.ruler.after("blockquote","math_block",n,{alt:["paragraph","reference","blockquote","list"]}),t.renderer.rules.math_inline=c,t.renderer.rules.math_block=l}},function(f,d,s){f.exports=function(e){function r(i,t){var a,u,c,o,l,p=i.pos,_=i.src.charCodeAt(p);if(t||_!==61||(u=i.scanDelims(i.pos,!0),o=u.length,l=String.fromCharCode(_),o<2))return!1;for(o%2&&(c=i.push("text","",0),c.content=l,o--),a=0;a<o;a+=2)c=i.push("text","",0),c.content=l+l,i.delimiters.push({marker:_,jump:a,token:i.tokens.length-1,level:i.level,end:-1,open:u.can_open,close:u.can_close});return i.pos+=u.length,!0}function n(i){var t,a,u,c,o,l=[],p=i.delimiters,_=i.delimiters.length;for(t=0;t<_;t++)u=p[t],u.marker===61&&u.end!==-1&&(c=p[u.end],o=i.tokens[u.token],o.type="mark_open",o.tag="mark",o.nesting=1,o.markup="==",o.content="",o=i.tokens[c.token],o.type="mark_close",o.tag="mark",o.nesting=-1,o.markup="==",o.content="",i.tokens[c.token-1].type==="text"&&i.tokens[c.token-1].content==="="&&l.push(c.token-1));for(;l.length;){for(t=l.pop(),a=t+1;a<i.tokens.length&&i.tokens[a].type==="mark_close";)a++;a--,t!==a&&(o=i.tokens[a],i.tokens[a]=i.tokens[t],i.tokens[t]=o)}}e.inline.ruler.before("emphasis","mark",r),e.inline.ruler2.before("emphasis","mark",n)}},function(f,d,s){function e(n,i){var t,a,u,c=n.posMax,o=n.pos;if(n.src.charCodeAt(o)!==126||i||o+2>=c)return!1;for(n.pos=o+1;n.pos<c;){if(n.src.charCodeAt(n.pos)===126){t=!0;break}n.md.inline.skipToken(n)}return t&&o+1!==n.pos?(a=n.src.slice(o+1,n.pos),a.match(/(^|[^\\])(\\\\)*\s/)?(n.pos=o,!1):(n.posMax=n.pos,n.pos=o+1,u=n.push("sub_open","sub",1),u.markup="~",u=n.push("text","",0),u.content=a.replace(r,"$1"),u=n.push("sub_close","sub",-1),u.markup="~",n.pos=n.posMax+1,n.posMax=c,!0)):(n.pos=o,!1)}var r=/\\([ \\!"#$%&'()*+,.\/:;<=>?@[\]^_`{|}~-])/g;f.exports=function(n){n.inline.ruler.after("emphasis","sub",e)}},function(f,d,s){function e(n,i){var t,a,u,c=n.posMax,o=n.pos;if(n.src.charCodeAt(o)!==94||i||o+2>=c)return!1;for(n.pos=o+1;n.pos<c;){if(n.src.charCodeAt(n.pos)===94){t=!0;break}n.md.inline.skipToken(n)}return t&&o+1!==n.pos?(a=n.src.slice(o+1,n.pos),a.match(/(^|[^\\])(\\\\)*\s/)?(n.pos=o,!1):(n.posMax=n.pos,n.pos=o+1,u=n.push("sup_open","sup",1),u.markup="^",u=n.push("text","",0),u.content=a.replace(r,"$1"),u=n.push("sup_close","sup",-1),u.markup="^",n.pos=n.posMax+1,n.posMax=c,!0)):(n.pos=o,!1)}var r=/\\([ \\!"#$%&'()*+,.\/:;<=>?@[\]^_`{|}~-])/g;f.exports=function(n){n.inline.ruler.after("emphasis","sup",e)}},function(f,d){function s(m,g,w){var v=m.attrIndex(g),x=[g,w];v<0?m.attrPush(x):m.attrs[v]=x}function e(m,g){for(var w=m[g].level-1,v=g-1;v>=0;v--)if(m[v].level===w)return v;return-1}function r(m,g){return c(m[g])&&o(m[g-1])&&l(m[g-2])&&p(m[g])}function n(m,g){if(m.children.unshift(i(m,g)),m.children[1].content=m.children[1].content.slice(3),m.content=m.content.slice(3),h)if(b){m.children.pop();var w="task-item-"+Math.ceil(1e7*Math.random()-1e3);m.children[0].content=m.children[0].content.slice(0,-1)+' id="'+w+'">',m.children.push(u(m.content,w,g))}else m.children.unshift(t(g)),m.children.push(a(g))}function i(m,g){var w=new g("html_inline","",0),v=_?' disabled="" ':"";return m.content.indexOf("[ ] ")===0?w.content='<input class="task-list-item-checkbox"'+v+'type="checkbox">':m.content.indexOf("[x] ")!==0&&m.content.indexOf("[X] ")!==0||(w.content='<input class="task-list-item-checkbox" checked=""'+v+'type="checkbox">'),w}function t(m){var g=new m("html_inline","",0);return g.content="<label>",g}function a(m){var g=new m("html_inline","",0);return g.content="</label>",g}function u(m,g,w){var v=new w("html_inline","",0);return v.content='<label class="task-list-item-label" for="'+g+'">'+m+"</label>",v.attrs=[{for:g}],v}function c(m){return m.type==="inline"}function o(m){return m.type==="paragraph_open"}function l(m){return m.type==="list_item_open"}function p(m){return m.content.indexOf("[ ] ")===0||m.content.indexOf("[x] ")===0||m.content.indexOf("[X] ")===0}var _=!0,h=!1,b=!1;f.exports=function(m,g){g&&(_=!g.enabled,h=!!g.label,b=!!g.labelAfter),m.core.ruler.after("inline","github-task-lists",function(w){for(var v=w.tokens,x=2;x<v.length;x++)r(v,x)&&(n(v[x],w.Token),s(v[x-2],"class","task-list-item"+(_?"":" enabled")),s(v[e(v,x-2)],"class","contains-task-list"))})}},function(f,d,s){f.exports=function(e){function r(u,c){for(;u.src.indexOf(`
`)>=0&&u.src.indexOf(`
`)<u.src.indexOf("@[toc]");)u.tokens.slice(-1)[0].type==="softbreak"&&(u.src=u.src.split(`
`).slice(1).join(`
`),u.pos=0);var o;if(u.src.charCodeAt(u.pos)!==64||u.src.charCodeAt(u.pos+1)!==91)return!1;var l=i.exec(u.src);if(!l||(l=l.filter(function(b){return b}),l.length<1)||c)return!1;o=u.push("toc_open","toc",1),o.markup="@[toc]",o=u.push("toc_body","",0);var p=t;l.length>1&&(p=l.pop()),o.content=p,o=u.push("toc_close","toc",-1);var _=0,h=u.src.indexOf(`
`);return _=h!==-1?u.pos+h:u.pos+u.posMax+1,u.pos=_,!0}var n,i=/^@\[toc\](?:\((?:\s+)?([^\)]+)(?:\s+)?\)?)?(?:\s+?)?$/im,t="Table of Contents",a=function(u){return u.replace(/[^\w\s]/gi,"").split(" ").join("_")};e.renderer.rules.heading_open=function(u,c){var o=u[c].tag,l=u[c+1];return l.type==="inline"?"<"+o+'><a id="'+(a(l.content)+"_"+l.map[0])+'"></a>':"</h1>"},e.renderer.rules.toc_open=function(u,c){return""},e.renderer.rules.toc_close=function(u,c){return""},e.renderer.rules.toc_body=function(u,c){for(var o=[],l=n.tokens,p=l.length,_=0;_<p;_++)if(l[_].type==="heading_close"){var h=l[_],b=l[_-1];b.type==="inline"&&o.push({level:+h.tag.substr(1,1),anchor:a(b.content)+"_"+b.map[0],content:b.content})}var m=0,g=o.map(function(w){var v=[];if(w.level>m)for(var x=w.level-m,k=0;k<x;k++)v.push("<ul>"),m++;else if(w.level<m)for(var x=m-w.level,k=0;k<x;k++)v.push("</ul>"),m--;return v=v.concat(['<li><a href="#',w.anchor,'">',w.content,"</a></li>"]),v.join("")});return"<h3>"+u[c].content+"</h3>"+g.join("")+new Array(m+1).join("</ul>")},e.core.ruler.push("grab_state",function(u){n=u}),e.inline.ruler.after("emphasis","toc",r)}},function(f,d,s){f.exports=s(143)},function(f,d,s){f.exports=["address","article","aside","base","basefont","blockquote","body","caption","center","col","colgroup","dd","details","dialog","dir","div","dl","dt","fieldset","figcaption","figure","footer","form","frame","frameset","h1","h2","h3","h4","h5","h6","head","header","hr","html","iframe","legend","li","link","main","menu","menuitem","meta","nav","noframes","ol","optgroup","option","p","param","section","source","summary","table","tbody","td","tfoot","th","thead","title","tr","track","ul"]},function(f,d,s){d.parseLinkLabel=s(141),d.parseLinkDestination=s(140),d.parseLinkTitle=s(142)},function(f,d,s){var e=s(0).unescapeAll;f.exports=function(r,n,i){var t,a,u=n,c={ok:!1,pos:0,lines:0,str:""};if(r.charCodeAt(n)===60){for(n++;n<i;){if((t=r.charCodeAt(n))===10)return c;if(t===62)return c.pos=n+1,c.str=e(r.slice(u+1,n)),c.ok=!0,c;t===92&&n+1<i?n+=2:n++}return c}for(a=0;n<i&&(t=r.charCodeAt(n))!==32&&!(t<32||t===127);)if(t===92&&n+1<i)n+=2;else{if(t===40&&a++,t===41){if(a===0)break;a--}n++}return u===n||a!==0||(c.str=e(r.slice(u,n)),c.lines=0,c.pos=n,c.ok=!0),c}},function(f,d,s){f.exports=function(e,r,n){var i,t,a,u,c=-1,o=e.posMax,l=e.pos;for(e.pos=r+1,i=1;e.pos<o;){if((a=e.src.charCodeAt(e.pos))===93&&--i===0){t=!0;break}if(u=e.pos,e.md.inline.skipToken(e),a===91){if(u===e.pos-1)i++;else if(n)return e.pos=l,-1}}return t&&(c=e.pos),e.pos=l,c}},function(f,d,s){var e=s(0).unescapeAll;f.exports=function(r,n,i){var t,a,u=0,c=n,o={ok:!1,pos:0,lines:0,str:""};if(n>=i||(a=r.charCodeAt(n))!==34&&a!==39&&a!==40)return o;for(n++,a===40&&(a=41);n<i;){if((t=r.charCodeAt(n))===a)return o.pos=n+1,o.lines=u,o.str=e(r.slice(c+1,n)),o.ok=!0,o;t===10?u++:t===92&&n+1<i&&(n++,r.charCodeAt(n)===10&&u++),n++}return o}},function(f,d,s){function e(v){var x=v.trim().toLowerCase();return!m.test(x)||!!g.test(x)}function r(v){var x=_.parse(v,!0);if(x.hostname&&(!x.protocol||w.indexOf(x.protocol)>=0))try{x.hostname=h.toASCII(x.hostname)}catch{}return _.encode(_.format(x))}function n(v){var x=_.parse(v,!0);if(x.hostname&&(!x.protocol||w.indexOf(x.protocol)>=0))try{x.hostname=h.toUnicode(x.hostname)}catch{}return _.decode(_.format(x))}function i(v,x){if(!(this instanceof i))return new i(v,x);x||t.isString(v)||(x=v||{},v="default"),this.inline=new l,this.block=new o,this.core=new c,this.renderer=new u,this.linkify=new p,this.validateLink=e,this.normalizeLink=r,this.normalizeLinkText=n,this.utils=t,this.helpers=t.assign({},a),this.options={},this.configure(v),x&&this.set(x)}var t=s(0),a=s(139),u=s(150),c=s(145),o=s(144),l=s(146),p=s(116),_=s(56),h=s(186),b={default:s(148),zero:s(149),commonmark:s(147)},m=/^(vbscript|javascript|file|data):/,g=/^data:image\/(gif|png|jpeg|webp);/,w=["http:","https:","mailto:"];i.prototype.set=function(v){return t.assign(this.options,v),this},i.prototype.configure=function(v){var x,k=this;if(t.isString(v)&&(x=v,!(v=b[x])))throw new Error('Wrong `markdown-it` preset "'+x+'", check name');if(!v)throw new Error("Wrong `markdown-it` preset, can't be empty");return v.options&&k.set(v.options),v.components&&Object.keys(v.components).forEach(function(C){v.components[C].rules&&k[C].ruler.enableOnly(v.components[C].rules),v.components[C].rules2&&k[C].ruler2.enableOnly(v.components[C].rules2)}),this},i.prototype.enable=function(v,x){var k=[];Array.isArray(v)||(v=[v]),["core","block","inline"].forEach(function(y){k=k.concat(this[y].ruler.enable(v,!0))},this),k=k.concat(this.inline.ruler2.enable(v,!0));var C=v.filter(function(y){return k.indexOf(y)<0});if(C.length&&!x)throw new Error("MarkdownIt. Failed to enable unknown rule(s): "+C);return this},i.prototype.disable=function(v,x){var k=[];Array.isArray(v)||(v=[v]),["core","block","inline"].forEach(function(y){k=k.concat(this[y].ruler.disable(v,!0))},this),k=k.concat(this.inline.ruler2.disable(v,!0));var C=v.filter(function(y){return k.indexOf(y)<0});if(C.length&&!x)throw new Error("MarkdownIt. Failed to disable unknown rule(s): "+C);return this},i.prototype.use=function(v){var x=[this].concat(Array.prototype.slice.call(arguments,1));return v.apply(v,x),this},i.prototype.parse=function(v,x){if(typeof v!="string")throw new Error("Input data should be a String");var k=new this.core.State(v,this,x);return this.core.process(k),k.tokens},i.prototype.render=function(v,x){return x=x||{},this.renderer.render(this.parse(v,x),this.options,x)},i.prototype.parseInline=function(v,x){var k=new this.core.State(v,this,x);return k.inlineMode=!0,this.core.process(k),k.tokens},i.prototype.renderInline=function(v,x){return x=x||{},this.renderer.render(this.parseInline(v,x),this.options,x)},f.exports=i},function(f,d,s){function e(){this.ruler=new r;for(var i=0;i<n.length;i++)this.ruler.push(n[i][0],n[i][1],{alt:(n[i][2]||[]).slice()})}var r=s(32),n=[["table",s(162),["paragraph","reference"]],["code",s(152)],["fence",s(153),["paragraph","reference","blockquote","list"]],["blockquote",s(151),["paragraph","reference","blockquote","list"]],["hr",s(155),["paragraph","reference","blockquote","list"]],["list",s(158),["paragraph","reference","blockquote"]],["reference",s(160)],["heading",s(154),["paragraph","reference","blockquote"]],["lheading",s(157)],["html_block",s(156),["paragraph","reference","blockquote"]],["paragraph",s(159)]];e.prototype.tokenize=function(i,t,a){for(var u,c=this.ruler.getRules(""),o=c.length,l=t,p=!1,_=i.md.options.maxNesting;l<a&&(i.line=l=i.skipEmptyLines(l),!(l>=a))&&!(i.sCount[l]<i.blkIndent);){if(i.level>=_){i.line=a;break}for(u=0;u<o&&!c[u](i,l,a,!1);u++);i.tight=!p,i.isEmpty(i.line-1)&&(p=!0),(l=i.line)<a&&i.isEmpty(l)&&(p=!0,l++,i.line=l)}},e.prototype.parse=function(i,t,a,u){var c;i&&(c=new this.State(i,t,a,u),this.tokenize(c,c.line,c.lineMax))},e.prototype.State=s(161),f.exports=e},function(f,d,s){function e(){this.ruler=new r;for(var i=0;i<n.length;i++)this.ruler.push(n[i][0],n[i][1])}var r=s(32),n=[["normalize",s(166)],["block",s(163)],["inline",s(164)],["linkify",s(165)],["replacements",s(167)],["smartquotes",s(168)]];e.prototype.process=function(i){var t,a,u;for(u=this.ruler.getRules(""),t=0,a=u.length;t<a;t++)u[t](i)},e.prototype.State=s(169),f.exports=e},function(f,d,s){function e(){var t;for(this.ruler=new r,t=0;t<n.length;t++)this.ruler.push(n[t][0],n[t][1]);for(this.ruler2=new r,t=0;t<i.length;t++)this.ruler2.push(i[t][0],i[t][1])}var r=s(32),n=[["text",s(180)],["newline",s(178)],["escape",s(174)],["backticks",s(171)],["strikethrough",s(55).tokenize],["emphasis",s(54).tokenize],["link",s(177)],["image",s(176)],["autolink",s(170)],["html_inline",s(175)],["entity",s(173)]],i=[["balance_pairs",s(172)],["strikethrough",s(55).postProcess],["emphasis",s(54).postProcess],["text_collapse",s(181)]];e.prototype.skipToken=function(t){var a,u,c=t.pos,o=this.ruler.getRules(""),l=o.length,p=t.md.options.maxNesting,_=t.cache;if(_[c]!==void 0)return void(t.pos=_[c]);if(t.level<p)for(u=0;u<l&&(t.level++,a=o[u](t,!0),t.level--,!a);u++);else t.pos=t.posMax;a||t.pos++,_[c]=t.pos},e.prototype.tokenize=function(t){for(var a,u,c=this.ruler.getRules(""),o=c.length,l=t.posMax,p=t.md.options.maxNesting;t.pos<l;){if(t.level<p)for(u=0;u<o&&!(a=c[u](t,!1));u++);if(a){if(t.pos>=l)break}else t.pending+=t.src[t.pos++]}t.pending&&t.pushPending()},e.prototype.parse=function(t,a,u,c){var o,l,p,_=new this.State(t,a,u,c);for(this.tokenize(_),l=this.ruler2.getRules(""),p=l.length,o=0;o<p;o++)l[o](_)},e.prototype.State=s(179),f.exports=e},function(f,d,s){f.exports={options:{html:!0,xhtmlOut:!0,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"“”‘’",highlight:null,maxNesting:20},components:{core:{rules:["normalize","block","inline"]},block:{rules:["blockquote","code","fence","heading","hr","html_block","lheading","list","reference","paragraph"]},inline:{rules:["autolink","backticks","emphasis","entity","escape","html_inline","image","link","newline","text"],rules2:["balance_pairs","emphasis","text_collapse"]}}}},function(f,d,s){f.exports={options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"“”‘’",highlight:null,maxNesting:100},components:{core:{},block:{},inline:{}}}},function(f,d,s){f.exports={options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"“”‘’",highlight:null,maxNesting:20},components:{core:{rules:["normalize","block","inline"]},block:{rules:["paragraph"]},inline:{rules:["text"],rules2:["balance_pairs","text_collapse"]}}}},function(f,d,s){function e(){this.rules=r({},t)}var r=s(0).assign,n=s(0).unescapeAll,i=s(0).escapeHtml,t={};t.code_inline=function(a,u,c,o,l){var p=a[u];return"<code"+l.renderAttrs(p)+">"+i(a[u].content)+"</code>"},t.code_block=function(a,u,c,o,l){var p=a[u];return"<pre"+l.renderAttrs(p)+"><code>"+i(a[u].content)+`</code></pre>
`},t.fence=function(a,u,c,o,l){var p,_,h,b,m=a[u],g=m.info?n(m.info).trim():"",w="";return g&&(w=g.split(/\s+/g)[0]),p=c.highlight&&c.highlight(m.content,w)||i(m.content),p.indexOf("<pre")===0?p+`
`:g?(_=m.attrIndex("class"),h=m.attrs?m.attrs.slice():[],_<0?h.push(["class",c.langPrefix+w]):h[_][1]+=" "+c.langPrefix+w,b={attrs:h},"<pre><code"+l.renderAttrs(b)+">"+p+`</code></pre>
`):"<pre><code"+l.renderAttrs(m)+">"+p+`</code></pre>
`},t.image=function(a,u,c,o,l){var p=a[u];return p.attrs[p.attrIndex("alt")][1]=l.renderInlineAsText(p.children,c,o),l.renderToken(a,u,c)},t.hardbreak=function(a,u,c){return c.xhtmlOut?`<br />
`:`<br>
`},t.softbreak=function(a,u,c){return c.breaks?c.xhtmlOut?`<br />
`:`<br>
`:`
`},t.text=function(a,u){return i(a[u].content)},t.html_block=function(a,u){return a[u].content},t.html_inline=function(a,u){return a[u].content},e.prototype.renderAttrs=function(a){var u,c,o;if(!a.attrs)return"";for(o="",u=0,c=a.attrs.length;u<c;u++)o+=" "+i(a.attrs[u][0])+'="'+i(a.attrs[u][1])+'"';return o},e.prototype.renderToken=function(a,u,c){var o,l="",p=!1,_=a[u];return _.hidden?"":(_.block&&_.nesting!==-1&&u&&a[u-1].hidden&&(l+=`
`),l+=(_.nesting===-1?"</":"<")+_.tag,l+=this.renderAttrs(_),_.nesting===0&&c.xhtmlOut&&(l+=" /"),_.block&&(p=!0,_.nesting===1&&u+1<a.length&&(o=a[u+1],(o.type==="inline"||o.hidden||o.nesting===-1&&o.tag===_.tag)&&(p=!1))),l+=p?`>
`:">")},e.prototype.renderInline=function(a,u,c){for(var o,l="",p=this.rules,_=0,h=a.length;_<h;_++)o=a[_].type,p[o]!==void 0?l+=p[o](a,_,u,c,this):l+=this.renderToken(a,_,u);return l},e.prototype.renderInlineAsText=function(a,u,c){for(var o="",l=0,p=a.length;l<p;l++)a[l].type==="text"?o+=a[l].content:a[l].type==="image"&&(o+=this.renderInlineAsText(a[l].children,u,c));return o},e.prototype.render=function(a,u,c){var o,l,p,_="",h=this.rules;for(o=0,l=a.length;o<l;o++)p=a[o].type,p==="inline"?_+=this.renderInline(a[o].children,u,c):h[p]!==void 0?_+=h[a[o].type](a,o,u,c,this):_+=this.renderToken(a,o,u,c);return _},f.exports=e},function(f,d,s){var e=s(0).isSpace;f.exports=function(r,n,i,t){var a,u,c,o,l,p,_,h,b,m,g,w,v,x,k,C,y,A,T,L,j=r.lineMax,S=r.bMarks[n]+r.tShift[n],M=r.eMarks[n];if(r.sCount[n]-r.blkIndent>=4||r.src.charCodeAt(S++)!==62)return!1;if(t)return!0;for(o=b=r.sCount[n]+S-(r.bMarks[n]+r.tShift[n]),r.src.charCodeAt(S)===32?(S++,o++,b++,a=!1,C=!0):r.src.charCodeAt(S)===9?(C=!0,(r.bsCount[n]+b)%4==3?(S++,o++,b++,a=!1):a=!0):C=!1,m=[r.bMarks[n]],r.bMarks[n]=S;S<M&&(u=r.src.charCodeAt(S),e(u));)u===9?b+=4-(b+r.bsCount[n]+(a?1:0))%4:b++,S++;for(g=[r.bsCount[n]],r.bsCount[n]=r.sCount[n]+1+(C?1:0),p=S>=M,x=[r.sCount[n]],r.sCount[n]=b-o,k=[r.tShift[n]],r.tShift[n]=S-r.bMarks[n],A=r.md.block.ruler.getRules("blockquote"),v=r.parentType,r.parentType="blockquote",L=!1,h=n+1;h<i&&(r.sCount[h]<r.blkIndent&&(L=!0),S=r.bMarks[h]+r.tShift[h],M=r.eMarks[h],!(S>=M));h++)if(r.src.charCodeAt(S++)!==62||L){if(p)break;for(y=!1,c=0,l=A.length;c<l;c++)if(A[c](r,h,i,!0)){y=!0;break}if(y){r.lineMax=h,r.blkIndent!==0&&(m.push(r.bMarks[h]),g.push(r.bsCount[h]),k.push(r.tShift[h]),x.push(r.sCount[h]),r.sCount[h]-=r.blkIndent);break}m.push(r.bMarks[h]),g.push(r.bsCount[h]),k.push(r.tShift[h]),x.push(r.sCount[h]),r.sCount[h]=-1}else{for(o=b=r.sCount[h]+S-(r.bMarks[h]+r.tShift[h]),r.src.charCodeAt(S)===32?(S++,o++,b++,a=!1,C=!0):r.src.charCodeAt(S)===9?(C=!0,(r.bsCount[h]+b)%4==3?(S++,o++,b++,a=!1):a=!0):C=!1,m.push(r.bMarks[h]),r.bMarks[h]=S;S<M&&(u=r.src.charCodeAt(S),e(u));)u===9?b+=4-(b+r.bsCount[h]+(a?1:0))%4:b++,S++;p=S>=M,g.push(r.bsCount[h]),r.bsCount[h]=r.sCount[h]+1+(C?1:0),x.push(r.sCount[h]),r.sCount[h]=b-o,k.push(r.tShift[h]),r.tShift[h]=S-r.bMarks[h]}for(w=r.blkIndent,r.blkIndent=0,T=r.push("blockquote_open","blockquote",1),T.markup=">",T.map=_=[n,0],r.md.block.tokenize(r,n,h),T=r.push("blockquote_close","blockquote",-1),T.markup=">",r.lineMax=j,r.parentType=v,_[1]=r.line,c=0;c<k.length;c++)r.bMarks[c+n]=m[c],r.tShift[c+n]=k[c],r.sCount[c+n]=x[c],r.bsCount[c+n]=g[c];return r.blkIndent=w,!0}},function(f,d,s){f.exports=function(e,r,n){var i,t,a;if(e.sCount[r]-e.blkIndent<4)return!1;for(t=i=r+1;i<n;)if(e.isEmpty(i))i++;else{if(!(e.sCount[i]-e.blkIndent>=4))break;i++,t=i}return e.line=t,a=e.push("code_block","code",0),a.content=e.getLines(r,t,4+e.blkIndent,!0),a.map=[r,e.line],!0}},function(f,d,s){f.exports=function(e,r,n,i){var t,a,u,c,o,l,p,_=!1,h=e.bMarks[r]+e.tShift[r],b=e.eMarks[r];if(e.sCount[r]-e.blkIndent>=4||h+3>b||(t=e.src.charCodeAt(h))!==126&&t!==96||(o=h,h=e.skipChars(h,t),(a=h-o)<3)||(p=e.src.slice(o,h),u=e.src.slice(h,b),t===96&&u.indexOf(String.fromCharCode(t))>=0))return!1;if(i)return!0;for(c=r;!(++c>=n)&&(h=o=e.bMarks[c]+e.tShift[c],b=e.eMarks[c],!(h<b&&e.sCount[c]<e.blkIndent));)if(e.src.charCodeAt(h)===t&&!(e.sCount[c]-e.blkIndent>=4||(h=e.skipChars(h,t))-o<a||(h=e.skipSpaces(h))<b)){_=!0;break}return a=e.sCount[r],e.line=c+(_?1:0),l=e.push("fence","code",0),l.info=u,l.content=e.getLines(r+1,c,a,!0),l.markup=p,l.map=[r,e.line],!0}},function(f,d,s){var e=s(0).isSpace;f.exports=function(r,n,i,t){var a,u,c,o,l=r.bMarks[n]+r.tShift[n],p=r.eMarks[n];if(r.sCount[n]-r.blkIndent>=4||(a=r.src.charCodeAt(l))!==35||l>=p)return!1;for(u=1,a=r.src.charCodeAt(++l);a===35&&l<p&&u<=6;)u++,a=r.src.charCodeAt(++l);return!(u>6||l<p&&!e(a))&&(!!t||(p=r.skipSpacesBack(p,l),c=r.skipCharsBack(p,35,l),c>l&&e(r.src.charCodeAt(c-1))&&(p=c),r.line=n+1,o=r.push("heading_open","h"+String(u),1),o.markup="########".slice(0,u),o.map=[n,r.line],o=r.push("inline","",0),o.content=r.src.slice(l,p).trim(),o.map=[n,r.line],o.children=[],o=r.push("heading_close","h"+String(u),-1),o.markup="########".slice(0,u),!0))}},function(f,d,s){var e=s(0).isSpace;f.exports=function(r,n,i,t){var a,u,c,o,l=r.bMarks[n]+r.tShift[n],p=r.eMarks[n];if(r.sCount[n]-r.blkIndent>=4||(a=r.src.charCodeAt(l++))!==42&&a!==45&&a!==95)return!1;for(u=1;l<p;){if((c=r.src.charCodeAt(l++))!==a&&!e(c))return!1;c===a&&u++}return!(u<3)&&(!!t||(r.line=n+1,o=r.push("hr","hr",0),o.map=[n,r.line],o.markup=Array(u+1).join(String.fromCharCode(a)),!0))}},function(f,d,s){var e=s(138),r=s(53).HTML_OPEN_CLOSE_TAG_RE,n=[[/^<(script|pre|style)(?=(\s|>|$))/i,/<\/(script|pre|style)>/i,!0],[/^<!--/,/-->/,!0],[/^<\?/,/\?>/,!0],[/^<![A-Z]/,/>/,!0],[/^<!\[CDATA\[/,/\]\]>/,!0],[new RegExp("^</?("+e.join("|")+")(?=(\\s|/?>|$))","i"),/^$/,!0],[new RegExp(r.source+"\\s*$"),/^$/,!1]];f.exports=function(i,t,a,u){var c,o,l,p,_=i.bMarks[t]+i.tShift[t],h=i.eMarks[t];if(i.sCount[t]-i.blkIndent>=4||!i.md.options.html||i.src.charCodeAt(_)!==60)return!1;for(p=i.src.slice(_,h),c=0;c<n.length&&!n[c][0].test(p);c++);if(c===n.length)return!1;if(u)return n[c][2];if(o=t+1,!n[c][1].test(p)){for(;o<a&&!(i.sCount[o]<i.blkIndent);o++)if(_=i.bMarks[o]+i.tShift[o],h=i.eMarks[o],p=i.src.slice(_,h),n[c][1].test(p)){p.length!==0&&o++;break}}return i.line=o,l=i.push("html_block","",0),l.map=[t,o],l.content=i.getLines(t,o,i.blkIndent,!0),!0}},function(f,d,s){f.exports=function(e,r,n){var i,t,a,u,c,o,l,p,_,h,b=r+1,m=e.md.block.ruler.getRules("paragraph");if(e.sCount[r]-e.blkIndent>=4)return!1;for(h=e.parentType,e.parentType="paragraph";b<n&&!e.isEmpty(b);b++)if(!(e.sCount[b]-e.blkIndent>3)){if(e.sCount[b]>=e.blkIndent&&(o=e.bMarks[b]+e.tShift[b],l=e.eMarks[b],o<l&&((_=e.src.charCodeAt(o))===45||_===61)&&(o=e.skipChars(o,_),(o=e.skipSpaces(o))>=l))){p=_===61?1:2;break}if(!(e.sCount[b]<0)){for(t=!1,a=0,u=m.length;a<u;a++)if(m[a](e,b,n,!0)){t=!0;break}if(t)break}}return!!p&&(i=e.getLines(r,b,e.blkIndent,!1).trim(),e.line=b+1,c=e.push("heading_open","h"+String(p),1),c.markup=String.fromCharCode(_),c.map=[r,e.line],c=e.push("inline","",0),c.content=i,c.map=[r,e.line-1],c.children=[],c=e.push("heading_close","h"+String(p),-1),c.markup=String.fromCharCode(_),e.parentType=h,!0)}},function(f,d,s){function e(t,a){var u,c,o,l;return c=t.bMarks[a]+t.tShift[a],o=t.eMarks[a],u=t.src.charCodeAt(c++),u!==42&&u!==45&&u!==43||c<o&&(l=t.src.charCodeAt(c),!i(l))?-1:c}function r(t,a){var u,c=t.bMarks[a]+t.tShift[a],o=c,l=t.eMarks[a];if(o+1>=l||(u=t.src.charCodeAt(o++))<48||u>57)return-1;for(;;){if(o>=l)return-1;u=t.src.charCodeAt(o++);{if(!(u>=48&&u<=57)){if(u===41||u===46)break;return-1}if(o-c>=10)return-1}}return o<l&&(u=t.src.charCodeAt(o),!i(u))?-1:o}function n(t,a){var u,c,o=t.level+2;for(u=a+2,c=t.tokens.length-2;u<c;u++)t.tokens[u].level===o&&t.tokens[u].type==="paragraph_open"&&(t.tokens[u+2].hidden=!0,t.tokens[u].hidden=!0,u+=2)}var i=s(0).isSpace;f.exports=function(t,a,u,c){var o,l,p,_,h,b,m,g,w,v,x,k,C,y,A,T,L,j,S,M,F,E,q,O,P,D,z,$,R=!1,B=!0;if(t.sCount[a]-t.blkIndent>=4||t.listIndent>=0&&t.sCount[a]-t.listIndent>=4&&t.sCount[a]<t.blkIndent)return!1;if(c&&t.parentType==="paragraph"&&t.tShift[a]>=t.blkIndent&&(R=!0),(q=r(t,a))>=0){if(m=!0,P=t.bMarks[a]+t.tShift[a],C=Number(t.src.substr(P,q-P-1)),R&&C!==1)return!1}else{if(!((q=e(t,a))>=0))return!1;m=!1}if(R&&t.skipSpaces(q)>=t.eMarks[a])return!1;if(k=t.src.charCodeAt(q-1),c)return!0;for(x=t.tokens.length,m?($=t.push("ordered_list_open","ol",1),C!==1&&($.attrs=[["start",C]])):$=t.push("bullet_list_open","ul",1),$.map=v=[a,0],$.markup=String.fromCharCode(k),A=a,O=!1,z=t.md.block.ruler.getRules("list"),j=t.parentType,t.parentType="list";A<u;){for(E=q,y=t.eMarks[A],b=T=t.sCount[A]+q-(t.bMarks[a]+t.tShift[a]);E<y;){if((o=t.src.charCodeAt(E))===9)T+=4-(T+t.bsCount[A])%4;else{if(o!==32)break;T++}E++}if(l=E,h=l>=y?1:T-b,h>4&&(h=1),_=b+h,$=t.push("list_item_open","li",1),$.markup=String.fromCharCode(k),$.map=g=[a,0],F=t.tight,M=t.tShift[a],S=t.sCount[a],L=t.listIndent,t.listIndent=t.blkIndent,t.blkIndent=_,t.tight=!0,t.tShift[a]=l-t.bMarks[a],t.sCount[a]=T,l>=y&&t.isEmpty(a+1)?t.line=Math.min(t.line+2,u):t.md.block.tokenize(t,a,u,!0),t.tight&&!O||(B=!1),O=t.line-a>1&&t.isEmpty(t.line-1),t.blkIndent=t.listIndent,t.listIndent=L,t.tShift[a]=M,t.sCount[a]=S,t.tight=F,$=t.push("list_item_close","li",-1),$.markup=String.fromCharCode(k),A=a=t.line,g[1]=A,l=t.bMarks[a],A>=u||t.sCount[A]<t.blkIndent||t.sCount[a]-t.blkIndent>=4)break;for(D=!1,p=0,w=z.length;p<w;p++)if(z[p](t,A,u,!0)){D=!0;break}if(D)break;if(m){if((q=r(t,A))<0)break}else if((q=e(t,A))<0)break;if(k!==t.src.charCodeAt(q-1))break}return $=m?t.push("ordered_list_close","ol",-1):t.push("bullet_list_close","ul",-1),$.markup=String.fromCharCode(k),v[1]=A,t.line=A,t.parentType=j,B&&n(t,x),!0}},function(f,d,s){f.exports=function(e,r){var n,i,t,a,u,c,o=r+1,l=e.md.block.ruler.getRules("paragraph"),p=e.lineMax;for(c=e.parentType,e.parentType="paragraph";o<p&&!e.isEmpty(o);o++)if(!(e.sCount[o]-e.blkIndent>3||e.sCount[o]<0)){for(i=!1,t=0,a=l.length;t<a;t++)if(l[t](e,o,p,!0)){i=!0;break}if(i)break}return n=e.getLines(r,o,e.blkIndent,!1).trim(),e.line=o,u=e.push("paragraph_open","p",1),u.map=[r,e.line],u=e.push("inline","",0),u.content=n,u.map=[r,e.line],u.children=[],u=e.push("paragraph_close","p",-1),e.parentType=c,!0}},function(f,d,s){var e=s(0).normalizeReference,r=s(0).isSpace;f.exports=function(n,i,t,a){var u,c,o,l,p,_,h,b,m,g,w,v,x,k,C,y,A=0,T=n.bMarks[i]+n.tShift[i],L=n.eMarks[i],j=i+1;if(n.sCount[i]-n.blkIndent>=4||n.src.charCodeAt(T)!==91)return!1;for(;++T<L;)if(n.src.charCodeAt(T)===93&&n.src.charCodeAt(T-1)!==92){if(T+1===L||n.src.charCodeAt(T+1)!==58)return!1;break}for(l=n.lineMax,C=n.md.block.ruler.getRules("reference"),g=n.parentType,n.parentType="reference";j<l&&!n.isEmpty(j);j++)if(!(n.sCount[j]-n.blkIndent>3||n.sCount[j]<0)){for(k=!1,_=0,h=C.length;_<h;_++)if(C[_](n,j,l,!0)){k=!0;break}if(k)break}for(x=n.getLines(i,j,n.blkIndent,!1).trim(),L=x.length,T=1;T<L;T++){if((u=x.charCodeAt(T))===91)return!1;if(u===93){m=T;break}(u===10||u===92&&++T<L&&x.charCodeAt(T)===10)&&A++}if(m<0||x.charCodeAt(m+1)!==58)return!1;for(T=m+2;T<L;T++)if((u=x.charCodeAt(T))===10)A++;else if(!r(u))break;if(w=n.md.helpers.parseLinkDestination(x,T,L),!w.ok||(p=n.md.normalizeLink(w.str),!n.md.validateLink(p)))return!1;for(T=w.pos,A+=w.lines,c=T,o=A,v=T;T<L;T++)if((u=x.charCodeAt(T))===10)A++;else if(!r(u))break;for(w=n.md.helpers.parseLinkTitle(x,T,L),T<L&&v!==T&&w.ok?(y=w.str,T=w.pos,A+=w.lines):(y="",T=c,A=o);T<L&&(u=x.charCodeAt(T),r(u));)T++;if(T<L&&x.charCodeAt(T)!==10&&y)for(y="",T=c,A=o;T<L&&(u=x.charCodeAt(T),r(u));)T++;return!(T<L&&x.charCodeAt(T)!==10)&&!!(b=e(x.slice(1,m)))&&(!!a||(n.env.references===void 0&&(n.env.references={}),n.env.references[b]===void 0&&(n.env.references[b]={title:y,href:p}),n.parentType=g,n.line=i+A+1,!0))}},function(f,d,s){function e(i,t,a,u){var c,o,l,p,_,h,b,m;for(this.src=i,this.md=t,this.env=a,this.tokens=u,this.bMarks=[],this.eMarks=[],this.tShift=[],this.sCount=[],this.bsCount=[],this.blkIndent=0,this.line=0,this.lineMax=0,this.tight=!1,this.ddIndent=-1,this.listIndent=-1,this.parentType="root",this.level=0,this.result="",o=this.src,m=!1,l=p=h=b=0,_=o.length;p<_;p++){if(c=o.charCodeAt(p),!m){if(n(c)){h++,c===9?b+=4-b%4:b++;continue}m=!0}c!==10&&p!==_-1||(c!==10&&p++,this.bMarks.push(l),this.eMarks.push(p),this.tShift.push(h),this.sCount.push(b),this.bsCount.push(0),m=!1,h=0,b=0,l=p+1)}this.bMarks.push(o.length),this.eMarks.push(o.length),this.tShift.push(0),this.sCount.push(0),this.bsCount.push(0),this.lineMax=this.bMarks.length-1}var r=s(33),n=s(0).isSpace;e.prototype.push=function(i,t,a){var u=new r(i,t,a);return u.block=!0,a<0&&this.level--,u.level=this.level,a>0&&this.level++,this.tokens.push(u),u},e.prototype.isEmpty=function(i){return this.bMarks[i]+this.tShift[i]>=this.eMarks[i]},e.prototype.skipEmptyLines=function(i){for(var t=this.lineMax;i<t&&!(this.bMarks[i]+this.tShift[i]<this.eMarks[i]);i++);return i},e.prototype.skipSpaces=function(i){for(var t,a=this.src.length;i<a&&(t=this.src.charCodeAt(i),n(t));i++);return i},e.prototype.skipSpacesBack=function(i,t){if(i<=t)return i;for(;i>t;)if(!n(this.src.charCodeAt(--i)))return i+1;return i},e.prototype.skipChars=function(i,t){for(var a=this.src.length;i<a&&this.src.charCodeAt(i)===t;i++);return i},e.prototype.skipCharsBack=function(i,t,a){if(i<=a)return i;for(;i>a;)if(t!==this.src.charCodeAt(--i))return i+1;return i},e.prototype.getLines=function(i,t,a,u){var c,o,l,p,_,h,b,m=i;if(i>=t)return"";for(h=new Array(t-i),c=0;m<t;m++,c++){for(o=0,b=p=this.bMarks[m],_=m+1<t||u?this.eMarks[m]+1:this.eMarks[m];p<_&&o<a;){if(l=this.src.charCodeAt(p),n(l))l===9?o+=4-(o+this.bsCount[m])%4:o++;else{if(!(p-b<this.tShift[m]))break;o++}p++}h[c]=o>a?new Array(o-a+1).join(" ")+this.src.slice(p,_):this.src.slice(p,_)}return h.join("")},e.prototype.Token=r,f.exports=e},function(f,d,s){function e(i,t){var a=i.bMarks[t]+i.blkIndent,u=i.eMarks[t];return i.src.substr(a,u-a)}function r(i){var t,a=[],u=0,c=i.length,o=0,l=0,p=!1,_=0;for(t=i.charCodeAt(u);u<c;)t===96?p?(p=!1,_=u):o%2==0&&(p=!0,_=u):t!==124||o%2!=0||p||(a.push(i.substring(l,u)),l=u+1),t===92?o++:o=0,u++,u===c&&p&&(p=!1,u=_+1),t=i.charCodeAt(u);return a.push(i.substring(l)),a}var n=s(0).isSpace;f.exports=function(i,t,a,u){var c,o,l,p,_,h,b,m,g,w,v,x;if(t+2>a||(_=t+1,i.sCount[_]<i.blkIndent)||i.sCount[_]-i.blkIndent>=4||(l=i.bMarks[_]+i.tShift[_])>=i.eMarks[_]||(c=i.src.charCodeAt(l++))!==124&&c!==45&&c!==58)return!1;for(;l<i.eMarks[_];){if((c=i.src.charCodeAt(l))!==124&&c!==45&&c!==58&&!n(c))return!1;l++}for(o=e(i,t+1),h=o.split("|"),g=[],p=0;p<h.length;p++){if(!(w=h[p].trim())){if(p===0||p===h.length-1)continue;return!1}if(!/^:?-+:?$/.test(w))return!1;w.charCodeAt(w.length-1)===58?g.push(w.charCodeAt(0)===58?"center":"right"):w.charCodeAt(0)===58?g.push("left"):g.push("")}if(o=e(i,t).trim(),o.indexOf("|")===-1||i.sCount[t]-i.blkIndent>=4||(h=r(o.replace(/^\||\|$/g,"")),(b=h.length)>g.length))return!1;if(u)return!0;for(m=i.push("table_open","table",1),m.map=v=[t,0],m=i.push("thead_open","thead",1),m.map=[t,t+1],m=i.push("tr_open","tr",1),m.map=[t,t+1],p=0;p<h.length;p++)m=i.push("th_open","th",1),m.map=[t,t+1],g[p]&&(m.attrs=[["style","text-align:"+g[p]]]),m=i.push("inline","",0),m.content=h[p].trim(),m.map=[t,t+1],m.children=[],m=i.push("th_close","th",-1);for(m=i.push("tr_close","tr",-1),m=i.push("thead_close","thead",-1),m=i.push("tbody_open","tbody",1),m.map=x=[t+2,0],_=t+2;_<a&&!(i.sCount[_]<i.blkIndent)&&(o=e(i,_).trim(),o.indexOf("|")!==-1)&&!(i.sCount[_]-i.blkIndent>=4);_++){for(h=r(o.replace(/^\||\|$/g,"")),m=i.push("tr_open","tr",1),p=0;p<b;p++)m=i.push("td_open","td",1),g[p]&&(m.attrs=[["style","text-align:"+g[p]]]),m=i.push("inline","",0),m.content=h[p]?h[p].trim():"",m.children=[],m=i.push("td_close","td",-1);m=i.push("tr_close","tr",-1)}return m=i.push("tbody_close","tbody",-1),m=i.push("table_close","table",-1),v[1]=x[1]=_,i.line=_,!0}},function(f,d,s){f.exports=function(e){var r;e.inlineMode?(r=new e.Token("inline","",0),r.content=e.src,r.map=[0,1],r.children=[],e.tokens.push(r)):e.md.block.parse(e.src,e.md,e.env,e.tokens)}},function(f,d,s){f.exports=function(e){var r,n,i,t=e.tokens;for(n=0,i=t.length;n<i;n++)r=t[n],r.type==="inline"&&e.md.inline.parse(r.content,e.md,e.env,r.children)}},function(f,d,s){function e(i){return/^<a[>\s]/i.test(i)}function r(i){return/^<\/a\s*>/i.test(i)}var n=s(0).arrayReplaceAt;f.exports=function(i){var t,a,u,c,o,l,p,_,h,b,m,g,w,v,x,k,C,y=i.tokens;if(i.md.options.linkify){for(a=0,u=y.length;a<u;a++)if(y[a].type==="inline"&&i.md.linkify.pretest(y[a].content))for(c=y[a].children,w=0,t=c.length-1;t>=0;t--)if(l=c[t],l.type!=="link_close"){if(l.type==="html_inline"&&(e(l.content)&&w>0&&w--,r(l.content)&&w++),!(w>0)&&l.type==="text"&&i.md.linkify.test(l.content)){for(h=l.content,C=i.md.linkify.match(h),p=[],g=l.level,m=0,_=0;_<C.length;_++)v=C[_].url,x=i.md.normalizeLink(v),i.md.validateLink(x)&&(k=C[_].text,k=C[_].schema?C[_].schema!=="mailto:"||/^mailto:/i.test(k)?i.md.normalizeLinkText(k):i.md.normalizeLinkText("mailto:"+k).replace(/^mailto:/,""):i.md.normalizeLinkText("http://"+k).replace(/^http:\/\//,""),b=C[_].index,b>m&&(o=new i.Token("text","",0),o.content=h.slice(m,b),o.level=g,p.push(o)),o=new i.Token("link_open","a",1),o.attrs=[["href",x]],o.level=g++,o.markup="linkify",o.info="auto",p.push(o),o=new i.Token("text","",0),o.content=k,o.level=g,p.push(o),o=new i.Token("link_close","a",-1),o.level=--g,o.markup="linkify",o.info="auto",p.push(o),m=C[_].lastIndex);m<h.length&&(o=new i.Token("text","",0),o.content=h.slice(m),o.level=g,p.push(o)),y[a].children=c=n(c,t,p)}}else for(t--;c[t].level!==l.level&&c[t].type!=="link_open";)t--}}},function(f,d,s){var e=/\r\n?|\n/g,r=/\0/g;f.exports=function(n){var i;i=n.src.replace(e,`
`),i=i.replace(r,"�"),n.src=i}},function(f,d,s){function e(c,o){return u[o.toLowerCase()]}function r(c){var o,l,p=0;for(o=c.length-1;o>=0;o--)l=c[o],l.type!=="text"||p||(l.content=l.content.replace(a,e)),l.type==="link_open"&&l.info==="auto"&&p--,l.type==="link_close"&&l.info==="auto"&&p++}function n(c){var o,l,p=0;for(o=c.length-1;o>=0;o--)l=c[o],l.type!=="text"||p||i.test(l.content)&&(l.content=l.content.replace(/\+-/g,"±").replace(/\.{2,}/g,"…").replace(/([?!])…/g,"$1..").replace(/([?!]){4,}/g,"$1$1$1").replace(/,{2,}/g,",").replace(/(^|[^-])---([^-]|$)/gm,"$1—$2").replace(/(^|\s)--(\s|$)/gm,"$1–$2").replace(/(^|[^-\s])--([^-\s]|$)/gm,"$1–$2")),l.type==="link_open"&&l.info==="auto"&&p--,l.type==="link_close"&&l.info==="auto"&&p++}var i=/\+-|\.\.|\?\?\?\?|!!!!|,,|--/,t=/\((c|tm|r|p)\)/i,a=/\((c|tm|r|p)\)/gi,u={c:"©",r:"®",p:"§",tm:"™"};f.exports=function(c){var o;if(c.md.options.typographer)for(o=c.tokens.length-1;o>=0;o--)c.tokens[o].type==="inline"&&(t.test(c.tokens[o].content)&&r(c.tokens[o].children),i.test(c.tokens[o].content)&&n(c.tokens[o].children))}},function(f,d,s){function e(o,l,p){return o.substr(0,l)+p+o.substr(l+1)}function r(o,l){var p,_,h,b,m,g,w,v,x,k,C,y,A,T,L,j,S,M,F,E,q;for(F=[],p=0;p<o.length;p++){for(_=o[p],w=o[p].level,S=F.length-1;S>=0&&!(F[S].level<=w);S--);if(F.length=S+1,_.type==="text"){h=_.content,m=0,g=h.length;e:for(;m<g&&(u.lastIndex=m,b=u.exec(h));){if(L=j=!0,m=b.index+1,M=b[0]==="'",x=32,b.index-1>=0)x=h.charCodeAt(b.index-1);else for(S=p-1;S>=0&&o[S].type!=="softbreak"&&o[S].type!=="hardbreak";S--)if(o[S].type==="text"){x=o[S].content.charCodeAt(o[S].content.length-1);break}if(k=32,m<g)k=h.charCodeAt(m);else for(S=p+1;S<o.length&&o[S].type!=="softbreak"&&o[S].type!=="hardbreak";S++)if(o[S].type==="text"){k=o[S].content.charCodeAt(0);break}if(C=t(x)||i(String.fromCharCode(x)),y=t(k)||i(String.fromCharCode(k)),A=n(x),T=n(k),T?L=!1:y&&(A||C||(L=!1)),A?j=!1:C&&(T||y||(j=!1)),k===34&&b[0]==='"'&&x>=48&&x<=57&&(j=L=!1),L&&j&&(L=!1,j=y),L||j){if(j){for(S=F.length-1;S>=0&&(v=F[S],!(F[S].level<w));S--)if(v.single===M&&F[S].level===w){v=F[S],M?(E=l.md.options.quotes[2],q=l.md.options.quotes[3]):(E=l.md.options.quotes[0],q=l.md.options.quotes[1]),_.content=e(_.content,b.index,q),o[v.token].content=e(o[v.token].content,v.pos,E),m+=q.length-1,v.token===p&&(m+=E.length-1),h=_.content,g=h.length,F.length=S;continue e}}L?F.push({token:p,pos:b.index,single:M,level:w}):j&&M&&(_.content=e(_.content,b.index,c))}else M&&(_.content=e(_.content,b.index,c))}}}}var n=s(0).isWhiteSpace,i=s(0).isPunctChar,t=s(0).isMdAsciiPunct,a=/['"]/,u=/['"]/g,c="’";f.exports=function(o){var l;if(o.md.options.typographer)for(l=o.tokens.length-1;l>=0;l--)o.tokens[l].type==="inline"&&a.test(o.tokens[l].content)&&r(o.tokens[l].children,o)}},function(f,d,s){function e(n,i,t){this.src=n,this.env=t,this.tokens=[],this.inlineMode=!1,this.md=i}var r=s(33);e.prototype.Token=r,f.exports=e},function(f,d,s){var e=/^<([a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)>/,r=/^<([a-zA-Z][a-zA-Z0-9+.\-]{1,31}):([^<>\x00-\x20]*)>/;f.exports=function(n,i){var t,a,u,c,o,l,p=n.pos;return n.src.charCodeAt(p)===60&&(t=n.src.slice(p),!(t.indexOf(">")<0)&&(r.test(t)?(a=t.match(r),c=a[0].slice(1,-1),o=n.md.normalizeLink(c),!!n.md.validateLink(o)&&(i||(l=n.push("link_open","a",1),l.attrs=[["href",o]],l.markup="autolink",l.info="auto",l=n.push("text","",0),l.content=n.md.normalizeLinkText(c),l=n.push("link_close","a",-1),l.markup="autolink",l.info="auto"),n.pos+=a[0].length,!0)):!!e.test(t)&&(u=t.match(e),c=u[0].slice(1,-1),o=n.md.normalizeLink("mailto:"+c),!!n.md.validateLink(o)&&(i||(l=n.push("link_open","a",1),l.attrs=[["href",o]],l.markup="autolink",l.info="auto",l=n.push("text","",0),l.content=n.md.normalizeLinkText(c),l=n.push("link_close","a",-1),l.markup="autolink",l.info="auto"),n.pos+=u[0].length,!0))))}},function(f,d,s){f.exports=function(e,r){var n,i,t,a,u,c,o=e.pos;if(e.src.charCodeAt(o)!==96)return!1;for(n=o,o++,i=e.posMax;o<i&&e.src.charCodeAt(o)===96;)o++;for(t=e.src.slice(n,o),a=u=o;(a=e.src.indexOf("`",u))!==-1;){for(u=a+1;u<i&&e.src.charCodeAt(u)===96;)u++;if(u-a===t.length)return r||(c=e.push("code_inline","code",0),c.markup=t,c.content=e.src.slice(o,a).replace(/\n/g," ").replace(/^ (.+) $/,"$1")),e.pos=u,!0}return r||(e.pending+=t),e.pos+=t.length,!0}},function(f,d,s){function e(r,n){var i,t,a,u,c,o,l,p,_={},h=n.length;for(i=0;i<h;i++)if(a=n[i],a.length=a.length||0,a.close){for(_.hasOwnProperty(a.marker)||(_[a.marker]=[-1,-1,-1]),c=_[a.marker][a.length%3],o=-1,t=i-a.jump-1;t>c;t-=u.jump+1)if(u=n[t],u.marker===a.marker&&(o===-1&&(o=t),u.open&&u.end<0&&u.level===a.level&&(l=!1,(u.close||a.open)&&(u.length+a.length)%3==0&&(u.length%3==0&&a.length%3==0||(l=!0)),!l))){p=t>0&&!n[t-1].open?n[t-1].jump+1:0,a.jump=i-t+p,a.open=!1,u.end=i,u.jump=p,u.close=!1,o=-1;break}o!==-1&&(_[a.marker][(a.length||0)%3]=o)}}f.exports=function(r){var n,i=r.tokens_meta,t=r.tokens_meta.length;for(e(r,r.delimiters),n=0;n<t;n++)i[n]&&i[n].delimiters&&e(r,i[n].delimiters)}},function(f,d,s){var e=s(52),r=s(0).has,n=s(0).isValidEntityCode,i=s(0).fromCodePoint,t=/^&#((?:x[a-f0-9]{1,6}|[0-9]{1,7}));/i,a=/^&([a-z][a-z0-9]{1,31});/i;f.exports=function(u,c){var o,l,p=u.pos,_=u.posMax;if(u.src.charCodeAt(p)!==38)return!1;if(p+1<_){if(u.src.charCodeAt(p+1)===35){if(l=u.src.slice(p).match(t))return c||(o=l[1][0].toLowerCase()==="x"?parseInt(l[1].slice(1),16):parseInt(l[1],10),u.pending+=i(n(o)?o:65533)),u.pos+=l[0].length,!0}else if((l=u.src.slice(p).match(a))&&r(e,l[1]))return c||(u.pending+=e[l[1]]),u.pos+=l[0].length,!0}return c||(u.pending+="&"),u.pos++,!0}},function(f,d,s){for(var e=s(0).isSpace,r=[],n=0;n<256;n++)r.push(0);"\\!\"#$%&'()*+,./:;<=>?@[]^_`{|}~-".split("").forEach(function(i){r[i.charCodeAt(0)]=1}),f.exports=function(i,t){var a,u=i.pos,c=i.posMax;if(i.src.charCodeAt(u)!==92)return!1;if(++u<c){if((a=i.src.charCodeAt(u))<256&&r[a]!==0)return t||(i.pending+=i.src[u]),i.pos+=2,!0;if(a===10){for(t||i.push("hardbreak","br",0),u++;u<c&&(a=i.src.charCodeAt(u),e(a));)u++;return i.pos=u,!0}}return t||(i.pending+="\\"),i.pos++,!0}},function(f,d,s){function e(n){var i=32|n;return i>=97&&i<=122}var r=s(53).HTML_TAG_RE;f.exports=function(n,i){var t,a,u,c,o=n.pos;return!!n.md.options.html&&(u=n.posMax,!(n.src.charCodeAt(o)!==60||o+2>=u)&&!((t=n.src.charCodeAt(o+1))!==33&&t!==63&&t!==47&&!e(t))&&!!(a=n.src.slice(o).match(r))&&(i||(c=n.push("html_inline","",0),c.content=n.src.slice(o,o+a[0].length)),n.pos+=a[0].length,!0))}},function(f,d,s){var e=s(0).normalizeReference,r=s(0).isSpace;f.exports=function(n,i){var t,a,u,c,o,l,p,_,h,b,m,g,w,v="",x=n.pos,k=n.posMax;if(n.src.charCodeAt(n.pos)!==33||n.src.charCodeAt(n.pos+1)!==91||(l=n.pos+2,(o=n.md.helpers.parseLinkLabel(n,n.pos+1,!1))<0))return!1;if((p=o+1)<k&&n.src.charCodeAt(p)===40){for(p++;p<k&&(a=n.src.charCodeAt(p),r(a)||a===10);p++);if(p>=k)return!1;for(w=p,h=n.md.helpers.parseLinkDestination(n.src,p,n.posMax),h.ok&&(v=n.md.normalizeLink(h.str),n.md.validateLink(v)?p=h.pos:v=""),w=p;p<k&&(a=n.src.charCodeAt(p),r(a)||a===10);p++);if(h=n.md.helpers.parseLinkTitle(n.src,p,n.posMax),p<k&&w!==p&&h.ok)for(b=h.str,p=h.pos;p<k&&(a=n.src.charCodeAt(p),r(a)||a===10);p++);else b="";if(p>=k||n.src.charCodeAt(p)!==41)return n.pos=x,!1;p++}else{if(n.env.references===void 0)return!1;if(p<k&&n.src.charCodeAt(p)===91?(w=p+1,p=n.md.helpers.parseLinkLabel(n,p),p>=0?c=n.src.slice(w,p++):p=o+1):p=o+1,c||(c=n.src.slice(l,o)),!(_=n.env.references[e(c)]))return n.pos=x,!1;v=_.href,b=_.title}return i||(u=n.src.slice(l,o),n.md.inline.parse(u,n.md,n.env,g=[]),m=n.push("image","img",0),m.attrs=t=[["src",v],["alt",""]],m.children=g,m.content=u,b&&t.push(["title",b])),n.pos=p,n.posMax=k,!0}},function(f,d,s){var e=s(0).normalizeReference,r=s(0).isSpace;f.exports=function(n,i){var t,a,u,c,o,l,p,_,h,b,m="",g=n.pos,w=n.posMax,v=n.pos,x=!0;if(n.src.charCodeAt(n.pos)!==91||(o=n.pos+1,(c=n.md.helpers.parseLinkLabel(n,n.pos,!0))<0))return!1;if((l=c+1)<w&&n.src.charCodeAt(l)===40){for(x=!1,l++;l<w&&(a=n.src.charCodeAt(l),r(a)||a===10);l++);if(l>=w)return!1;for(v=l,p=n.md.helpers.parseLinkDestination(n.src,l,n.posMax),p.ok&&(m=n.md.normalizeLink(p.str),n.md.validateLink(m)?l=p.pos:m=""),v=l;l<w&&(a=n.src.charCodeAt(l),r(a)||a===10);l++);if(p=n.md.helpers.parseLinkTitle(n.src,l,n.posMax),l<w&&v!==l&&p.ok)for(h=p.str,l=p.pos;l<w&&(a=n.src.charCodeAt(l),r(a)||a===10);l++);else h="";(l>=w||n.src.charCodeAt(l)!==41)&&(x=!0),l++}if(x){if(n.env.references===void 0)return!1;if(l<w&&n.src.charCodeAt(l)===91?(v=l+1,l=n.md.helpers.parseLinkLabel(n,l),l>=0?u=n.src.slice(v,l++):l=c+1):l=c+1,u||(u=n.src.slice(o,c)),!(_=n.env.references[e(u)]))return n.pos=g,!1;m=_.href,h=_.title}return i||(n.pos=o,n.posMax=c,b=n.push("link_open","a",1),b.attrs=t=[["href",m]],h&&t.push(["title",h]),n.md.inline.tokenize(n),b=n.push("link_close","a",-1)),n.pos=l,n.posMax=w,!0}},function(f,d,s){var e=s(0).isSpace;f.exports=function(r,n){var i,t,a=r.pos;if(r.src.charCodeAt(a)!==10)return!1;for(i=r.pending.length-1,t=r.posMax,n||(i>=0&&r.pending.charCodeAt(i)===32?i>=1&&r.pending.charCodeAt(i-1)===32?(r.pending=r.pending.replace(/ +$/,""),r.push("hardbreak","br",0)):(r.pending=r.pending.slice(0,-1),r.push("softbreak","br",0)):r.push("softbreak","br",0)),a++;a<t&&e(r.src.charCodeAt(a));)a++;return r.pos=a,!0}},function(f,d,s){function e(a,u,c,o){this.src=a,this.env=c,this.md=u,this.tokens=o,this.tokens_meta=Array(o.length),this.pos=0,this.posMax=this.src.length,this.level=0,this.pending="",this.pendingLevel=0,this.cache={},this.delimiters=[],this._prev_delimiters=[]}var r=s(33),n=s(0).isWhiteSpace,i=s(0).isPunctChar,t=s(0).isMdAsciiPunct;e.prototype.pushPending=function(){var a=new r("text","",0);return a.content=this.pending,a.level=this.pendingLevel,this.tokens.push(a),this.pending="",a},e.prototype.push=function(a,u,c){this.pending&&this.pushPending();var o=new r(a,u,c),l=null;return c<0&&(this.level--,this.delimiters=this._prev_delimiters.pop()),o.level=this.level,c>0&&(this.level++,this._prev_delimiters.push(this.delimiters),this.delimiters=[],l={delimiters:this.delimiters}),this.pendingLevel=this.level,this.tokens.push(o),this.tokens_meta.push(l),o},e.prototype.scanDelims=function(a,u){var c,o,l,p,_,h,b,m,g,w=a,v=!0,x=!0,k=this.posMax,C=this.src.charCodeAt(a);for(c=a>0?this.src.charCodeAt(a-1):32;w<k&&this.src.charCodeAt(w)===C;)w++;return l=w-a,o=w<k?this.src.charCodeAt(w):32,b=t(c)||i(String.fromCharCode(c)),g=t(o)||i(String.fromCharCode(o)),h=n(c),m=n(o),m?v=!1:g&&(h||b||(v=!1)),h?x=!1:b&&(m||g||(x=!1)),u?(p=v,_=x):(p=v&&(!x||b),_=x&&(!v||g)),{can_open:p,can_close:_,length:l}},e.prototype.Token=r,f.exports=e},function(f,d,s){function e(r){switch(r){case 10:case 33:case 35:case 36:case 37:case 38:case 42:case 43:case 45:case 58:case 60:case 61:case 62:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 125:case 126:return!0;default:return!1}}f.exports=function(r,n){for(var i=r.pos;i<r.posMax&&!e(r.src.charCodeAt(i));)i++;return i!==r.pos&&(n||(r.pending+=r.src.slice(r.pos,i)),r.pos=i,!0)}},function(f,d,s){f.exports=function(e){var r,n,i=0,t=e.tokens,a=e.tokens.length;for(r=n=0;r<a;r++)t[r].nesting<0&&i--,t[r].level=i,t[r].nesting>0&&i++,t[r].type==="text"&&r+1<a&&t[r+1].type==="text"?t[r+1].content=t[r].content+t[r+1].content:(r!==n&&(t[n]=t[r]),n++);r!==n&&(t.length=n)}},function(f,d,s){function e(i){var t,a,u=n[i];if(u)return u;for(u=n[i]=[],t=0;t<128;t++)a=String.fromCharCode(t),u.push(a);for(t=0;t<i.length;t++)a=i.charCodeAt(t),u[a]="%"+("0"+a.toString(16).toUpperCase()).slice(-2);return u}function r(i,t){var a;return typeof t!="string"&&(t=r.defaultChars),a=e(t),i.replace(/(%[a-f0-9]{2})+/gi,function(u){var c,o,l,p,_,h,b,m="";for(c=0,o=u.length;c<o;c+=3)l=parseInt(u.slice(c+1,c+3),16),l<128?m+=a[l]:(224&l)==192&&c+3<o&&(192&(p=parseInt(u.slice(c+4,c+6),16)))==128?(b=l<<6&1984|63&p,m+=b<128?"��":String.fromCharCode(b),c+=3):(240&l)==224&&c+6<o&&(p=parseInt(u.slice(c+4,c+6),16),_=parseInt(u.slice(c+7,c+9),16),(192&p)==128&&(192&_)==128)?(b=l<<12&61440|p<<6&4032|63&_,m+=b<2048||b>=55296&&b<=57343?"���":String.fromCharCode(b),c+=6):(248&l)==240&&c+9<o&&(p=parseInt(u.slice(c+4,c+6),16),_=parseInt(u.slice(c+7,c+9),16),h=parseInt(u.slice(c+10,c+12),16),(192&p)==128&&(192&_)==128&&(192&h)==128)?(b=l<<18&1835008|p<<12&258048|_<<6&4032|63&h,b<65536||b>1114111?m+="����":(b-=65536,m+=String.fromCharCode(55296+(b>>10),56320+(1023&b))),c+=9):m+="�";return m})}var n={};r.defaultChars=";/?:@&=+$,#",r.componentChars="",f.exports=r},function(f,d,s){function e(i){var t,a,u=n[i];if(u)return u;for(u=n[i]=[],t=0;t<128;t++)a=String.fromCharCode(t),/^[0-9a-z]$/i.test(a)?u.push(a):u.push("%"+("0"+t.toString(16).toUpperCase()).slice(-2));for(t=0;t<i.length;t++)u[i.charCodeAt(t)]=i[t];return u}function r(i,t,a){var u,c,o,l,p,_="";for(typeof t!="string"&&(a=t,t=r.defaultChars),a===void 0&&(a=!0),p=e(t),u=0,c=i.length;u<c;u++)if(o=i.charCodeAt(u),a&&o===37&&u+2<c&&/^[0-9a-f]{2}$/i.test(i.slice(u+1,u+3)))_+=i.slice(u,u+3),u+=2;else if(o<128)_+=p[o];else if(o>=55296&&o<=57343){if(o>=55296&&o<=56319&&u+1<c&&(l=i.charCodeAt(u+1))>=56320&&l<=57343){_+=encodeURIComponent(i[u]+i[u+1]),u++;continue}_+="%EF%BF%BD"}else _+=encodeURIComponent(i[u]);return _}var n={};r.defaultChars=";/?:@&=+$,-_.!~*'()#",r.componentChars="-_.!~*'()",f.exports=r},function(f,d,s){f.exports=function(e){var r="";return r+=e.protocol||"",r+=e.slashes?"//":"",r+=e.auth?e.auth+"@":"",e.hostname&&e.hostname.indexOf(":")!==-1?r+="["+e.hostname+"]":r+=e.hostname||"",r+=e.port?":"+e.port:"",r+=e.pathname||"",r+=e.search||"",r+=e.hash||""}},function(f,d,s){function e(){this.protocol=null,this.slashes=null,this.auth=null,this.port=null,this.hostname=null,this.hash=null,this.search=null,this.pathname=null}function r(m,g){if(m&&m instanceof e)return m;var w=new e;return w.parse(m,g),w}var n=/^([a-z0-9.+-]+:)/i,i=/:[0-9]*$/,t=/^(\/\/?(?!\/)[^\?\s]*)(\?[^\s]*)?$/,a=["<",">",'"',"`"," ","\r",`
`,"	"],u=["{","}","|","\\","^","`"].concat(a),c=["'"].concat(u),o=["%","/","?",";","#"].concat(c),l=["/","?","#"],p=/^[+a-z0-9A-Z_-]{0,63}$/,_=/^([+a-z0-9A-Z_-]{0,63})(.*)$/,h={javascript:!0,"javascript:":!0},b={http:!0,https:!0,ftp:!0,gopher:!0,file:!0,"http:":!0,"https:":!0,"ftp:":!0,"gopher:":!0,"file:":!0};e.prototype.parse=function(m,g){var w,v,x,k,C,y=m;if(y=y.trim(),!g&&m.split("#").length===1){var A=t.exec(y);if(A)return this.pathname=A[1],A[2]&&(this.search=A[2]),this}var T=n.exec(y);if(T&&(T=T[0],x=T.toLowerCase(),this.protocol=T,y=y.substr(T.length)),(g||T||y.match(/^\/\/[^@\/]+@[^@\/]+/))&&(!(C=y.substr(0,2)==="//")||T&&h[T]||(y=y.substr(2),this.slashes=!0)),!h[T]&&(C||T&&!b[T])){var L=-1;for(w=0;w<l.length;w++)(k=y.indexOf(l[w]))!==-1&&(L===-1||k<L)&&(L=k);var j,S;for(S=L===-1?y.lastIndexOf("@"):y.lastIndexOf("@",L),S!==-1&&(j=y.slice(0,S),y=y.slice(S+1),this.auth=j),L=-1,w=0;w<o.length;w++)(k=y.indexOf(o[w]))!==-1&&(L===-1||k<L)&&(L=k);L===-1&&(L=y.length),y[L-1]===":"&&L--;var M=y.slice(0,L);y=y.slice(L),this.parseHost(M),this.hostname=this.hostname||"";var F=this.hostname[0]==="["&&this.hostname[this.hostname.length-1]==="]";if(!F){var E=this.hostname.split(/\./);for(w=0,v=E.length;w<v;w++){var q=E[w];if(q&&!q.match(p)){for(var O="",P=0,D=q.length;P<D;P++)q.charCodeAt(P)>127?O+="x":O+=q[P];if(!O.match(p)){var z=E.slice(0,w),$=E.slice(w+1),R=q.match(_);R&&(z.push(R[1]),$.unshift(R[2])),$.length&&(y=$.join(".")+y),this.hostname=z.join(".");break}}}}this.hostname.length>255&&(this.hostname=""),F&&(this.hostname=this.hostname.substr(1,this.hostname.length-2))}var B=y.indexOf("#");B!==-1&&(this.hash=y.substr(B),y=y.slice(0,B));var U=y.indexOf("?");return U!==-1&&(this.search=y.substr(U),y=y.slice(0,U)),y&&(this.pathname=y),b[x]&&this.hostname&&!this.pathname&&(this.pathname=""),this},e.prototype.parseHost=function(m){var g=i.exec(m);g&&(g=g[0],g!==":"&&(this.port=g.substr(1)),m=m.substr(0,m.length-g.length)),m&&(this.hostname=m)},f.exports=r},function(f,d,s){(function(e,r){var n;(function(i){function t(D){throw new RangeError(E[D])}function a(D,z){for(var $=D.length,R=[];$--;)R[$]=z(D[$]);return R}function u(D,z){var $=D.split("@"),R="";return $.length>1&&(R=$[0]+"@",D=$[1]),D=D.replace(F,"."),R+a(D.split("."),z).join(".")}function c(D){for(var z,$,R=[],B=0,U=D.length;B<U;)z=D.charCodeAt(B++),z>=55296&&z<=56319&&B<U?($=D.charCodeAt(B++),(64512&$)==56320?R.push(((1023&z)<<10)+(1023&$)+65536):(R.push(z),B--)):R.push(z);return R}function o(D){return a(D,function(z){var $="";return z>65535&&(z-=65536,$+=P(z>>>10&1023|55296),z=56320|1023&z),$+=P(z)}).join("")}function l(D){return D-48<10?D-22:D-65<26?D-65:D-97<26?D-97:x}function p(D,z){return D+22+75*(D<26)-((z!=0)<<5)}function _(D,z,$){var R=0;for(D=$?O(D/A):D>>1,D+=O(D/z);D>q*C>>1;R+=x)D=O(D/q);return O(R+(q+1)*D/(D+y))}function h(D){var z,$,R,B,U,H,G,V,K,Q,J=[],Y=D.length,Z=0,te=L,ne=T;for($=D.lastIndexOf(j),$<0&&($=0),R=0;R<$;++R)D.charCodeAt(R)>=128&&t("not-basic"),J.push(D.charCodeAt(R));for(B=$>0?$+1:0;B<Y;){for(U=Z,H=1,G=x;B>=Y&&t("invalid-input"),V=l(D.charCodeAt(B++)),(V>=x||V>O((v-Z)/H))&&t("overflow"),Z+=V*H,K=G<=ne?k:G>=ne+C?C:G-ne,!(V<K);G+=x)Q=x-K,H>O(v/Q)&&t("overflow"),H*=Q;z=J.length+1,ne=_(Z-U,z,U==0),O(Z/z)>v-te&&t("overflow"),te+=O(Z/z),Z%=z,J.splice(Z++,0,te)}return o(J)}function b(D){var z,$,R,B,U,H,G,V,K,Q,J,Y,Z,te,ne,re=[];for(D=c(D),Y=D.length,z=L,$=0,U=T,H=0;H<Y;++H)(J=D[H])<128&&re.push(P(J));for(R=B=re.length,B&&re.push(j);R<Y;){for(G=v,H=0;H<Y;++H)(J=D[H])>=z&&J<G&&(G=J);for(Z=R+1,G-z>O((v-$)/Z)&&t("overflow"),$+=(G-z)*Z,z=G,H=0;H<Y;++H)if(J=D[H],J<z&&++$>v&&t("overflow"),J==z){for(V=$,K=x;Q=K<=U?k:K>=U+C?C:K-U,!(V<Q);K+=x)ne=V-Q,te=x-Q,re.push(P(p(Q+ne%te,0))),V=O(ne/te);re.push(P(p(V,0))),U=_($,Z,R==B),$=0,++R}++$,++z}return re.join("")}function m(D){return u(D,function(z){return S.test(z)?h(z.slice(4).toLowerCase()):z})}function g(D){return u(D,function(z){return M.test(z)?"xn--"+b(z):z})}typeof d=="object"&&d&&d.nodeType,typeof e=="object"&&e&&e.nodeType;var w,v=2147483647,x=36,k=1,C=26,y=38,A=700,T=72,L=128,j="-",S=/^xn--/,M=/[^\x20-\x7E]/,F=/[\x2E\u3002\uFF0E\uFF61]/g,E={overflow:"Overflow: input needs wider integers to process","not-basic":"Illegal input >= 0x80 (not a basic code point)","invalid-input":"Invalid input"},q=x-k,O=Math.floor,P=String.fromCharCode;w={version:"1.4.1",ucs2:{decode:c,encode:o},decode:h,encode:b,toASCII:g,toUnicode:m},(n=(function(){return w}).call(d,s,d,e))!==void 0&&(e.exports=n)})()}).call(d,s(208)(f),s(207))},function(f,d){f.exports=`@[toc](Catalog)

Markdown Handbuch
===
> Details: [http://commonmark.org/help/](http://commonmark.org/help/)

## **Fett**
\`\`\`
**fett**
__fett__
\`\`\`
## *Kursiv*
\`\`\`
*kursiv*
_kursiv_
\`\`\`
## Überschriften
\`\`\`
# h1 #
h1
====
## h2 ##
h2
----
### h3 ###
#### h4 ####
##### h5 #####
###### h6 ######
\`\`\`
## Trennlinien
\`\`\`
***
---
\`\`\`
****
## ^Hoch^gestellt & ~Tief~gestellt
\`\`\`
hochgestellt x^2^
tiefgestellt H~2~0
\`\`\`
## ++Unterstrichen++ & ~~Durchgestrichen~~
\`\`\`
++unterstrichen++
~~durchgestrichen~~
\`\`\`
## ==Markiert==
\`\`\`
==markiert==
\`\`\`
## Zitat

\`\`\`
> zitat 1
>> zitat 2
>>> zitat 3
...
\`\`\`

## Liste
\`\`\`
ol
1.
2.
3.
...

ul
-
-
...
\`\`\`

## Todo Liste

- [x] aufgabe 1
- [ ] aufgabe 2

\`\`\`
- [x] aufgabe 1
- [ ] aufgabe 2
\`\`\`

## Link
\`\`\`
Text Link
[Text](www.baidu.com)

Link mit Bild
![Text](http://www.image.com)
\`\`\`
## Code
\\\`\`\` Typ

Codeblock

\\\`\`\`

\\\` code \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`

## Tabelle
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| links | mitte | rechts |
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| links | mitte | rechts |
| ---------------------- | ------------- | ----------------- |
## Fußnote
\`\`\`
hallo[^hallo]
\`\`\`

Schau zum unteren Rand[^hallo]

[^hallo]: fussnote

## Emojis
Details: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$ Mathematik

Formeln lassen sich darstellen z.b. ：$x_i + y_i = z_i$ und $\\sum_{i=1}^n a_i=0$
Formeln können auf einer eigenen Zeile gerendert werden
$$\\sum_{i=1}^n a_i=0$$
Details: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## Layout

::: hljs-left
\`::: hljs-left\`
\`links\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`mitte\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`rechts\`
\`:::\`
:::

## Liste von Definitionen

Term 1

:   Definition 1

Term 2 mit *inline markup*

:   Definition 2

        { ein wenig code, teil von Definition 2 }

    Dritter Absatz von Definition 2.

\`\`\`
Term 1

:   Definition 1

Term 2 mit *inline markup*

:   Definition 2

        { ein wenig code, teil von Definition 2 }

    Dritter Absatz von Definition 2.

\`\`\`

## Abkürzungen
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
Die HTML Spezifikation
wird gepflegt vom W3C.
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
Die HTML Spezifikation
wird gepflegt vom W3C.
\`\`\`
`},function(f,d){f.exports=`@[toc](Catalog)

Markdown Guide
===
> Detailed: [http://commonmark.org/help/](http://commonmark.org/help/)

## **Bold**
\`\`\`
**bold**
__bold__
\`\`\`
## *Italic*
\`\`\`
*italic*
_italic_
\`\`\`
## Header
\`\`\`
# h1 #
h1
====
## h2 ##
h2
----
### h3 ###
#### h4 ####
##### h5 #####
###### h6 ######
\`\`\`
## Dividing line
\`\`\`
***
---
\`\`\`
****
## ^Super^script & ~Sub~script
\`\`\`
super x^2^
sub H~2~0
\`\`\`
## ++Underline++ & ~~Strikethrough~~
\`\`\`
++underline++
~~strikethrough~~
\`\`\`
## ==Mark==
\`\`\`
==mark==
\`\`\`
## Quote

\`\`\`
> quote 1
>> quote 2
>>> quote 3
...
\`\`\`

## List
\`\`\`
ol
1.
2.
3.
...

ul
-
-
...
\`\`\`

## Todo List

- [x] task 1
- [ ] task 2

\`\`\`
- [x] task 1
- [ ] task 2
\`\`\`

## Link
\`\`\`
Text Link
[Text](www.baidu.com)

Image Link
![Text](http://www.image.com)
\`\`\`
## Code
\\\`\`\` type

code block

\\\`\`\`

\\\` code \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`

## Table
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
| ---------------------- | ------------- | ----------------- |
## Footnote
\`\`\`
hello[^hello]
\`\`\`

Look at the bottom[^hello]

[^hello]: footnote

## Emojis
Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$ Mathematics

We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$
We can also single-line rendering
$$\\sum_{i=1}^n a_i=0$$
Detailed: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## Layout

::: hljs-left
\`::: hljs-left\`
\`left\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`center\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`right\`
\`:::\`
:::

## deflist

Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`
Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`

## abbr
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
`},function(f,d){f.exports=`@[toc](Catalogue)

Guide Markdown
==============
> Détail : [http://commonmark.org/help/](http://commonmark.org/help/)

## **Bold**
\`\`\`
**bold**
__bold__
\`\`\`
## *Italic*
\`\`\`
*italic*
_italic_
\`\`\`
## Header
\`\`\`
# h1 #
h1
====
## h2 ##
h2
----
### h3 ###
#### h4 ####
##### h5 #####
###### h6 ######
\`\`\`
## Dividing line
\`\`\`
***
---
\`\`\`
****
## ^Super^script & ~Sub~script
\`\`\`
super x^2^
sub H~2~0
\`\`\`
## ++Underline++ & ~~Strikethrough~~
\`\`\`
++underline++
~~strikethrough~~
\`\`\`
## ==Mark==
\`\`\`
==mark==
\`\`\`
## Quote

\`\`\`
> quote 1
>> quote 2
>>> quote 3
...
\`\`\`

## List
\`\`\`
ol
1.
2.
3.
...

ul
-
-
...
\`\`\`
## Link

## Todo List

- [x] Équipe 1
- [ ] Équipe 2

\`\`\`
- [x] Équipe 1
- [ ] Équipe 2
\`\`\`

\`\`\`
Text Link
[Text](www.baidu.com)

Image Link
![Text](http://www.image.com)
\`\`\`
## Code
\\\`\`\` type

code block

\\\`\`\`

\\\` code \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`

## Table
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
| ---------------------- | ------------- | ----------------- |
## Footnote
\`\`\`
hello[^hello]
\`\`\`

Look at the bottom[^hello]

[^hello]: footnote

## Emojis
Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$ Mathematics

We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$
We can also single-line rendering
$$\\sum_{i=1}^n a_i=0$$
Detailed: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## Layout

::: hljs-left
\`::: hljs-left\`
\`left\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`center\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`right\`
\`:::\`
:::

## deflist

Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`
Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`

## abbr
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
`},function(f,d){f.exports=`@[toc](目次)

Markdown 文法ガイド
===
> Detailed: [http://commonmark.org/help/](http://commonmark.org/help/)

## **太字**
\`\`\`
**太字**
__太字__
\`\`\`
## *斜体*
\`\`\`
*斜体*
_斜体_
\`\`\`
## 見出し
\`\`\`
# h1 #
h1
====
## h2 ##
h2
----
### h3 ###
#### h4 ####
##### h5 #####
###### h6 ######
\`\`\`
## 横線
\`\`\`
***
---
\`\`\`
****
## ^上付き^文字 & ~下付き~文字
\`\`\`
super x^2^
sub H~2~0
\`\`\`
## ++下線++ & ~~取り消し線~~
\`\`\`
++underline++
~~strikethrough~~
\`\`\`
## ==蛍光ペン==
\`\`\`
==mark==
\`\`\`
## 引用

\`\`\`
> quote 1
>> quote 2
>>> quote 3
...
\`\`\`

## リスト
\`\`\`
番号付きリスト
1.
2.
3.
...

箇条書きリスト
-
-
...
\`\`\`

## Todoリスト

- [x] task 1
- [ ] task 2

\`\`\`
- [x] task 1
- [ ] task 2
\`\`\`

## リンク
\`\`\`
Text Link
[Text](www.baidu.com)

Image Link
![Text](http://www.image.com)
\`\`\`
## コード
\\\`\`\` type

code block

\\\`\`\`

\\\` code \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`

## 表
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| left | center | right |
| ---------------------- | ------------- | ----------------- |

## 脚注
\`\`\`
hello[^hello]
\`\`\`

Look at the bottom[^hello]

[^hello]: footnote

## 絵文字
> Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$ 数式
> Detailed: [KaTeXマニュアル](http://www.intmath.com/cg5/katex-mathjax-comparison.php)、[KaTeX function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)、[LaTeXマニュアル](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$  
We can also single-line rendering
$$\\sum_{i=1}^n a_i=0$$

## レイアウト

::: hljs-left
\`::: hljs-left\`
\`left\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`center\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`right\`
\`:::\`
:::

## 定義リスト

Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`
Term 1

:   Definition 1

Term 2 with *inline markup*

:   Definition 2

        { some code, part of Definition 2 }

    Third paragraph of definition 2.

\`\`\`

## abbr
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
is maintained by the W3C.
\`\`\`
`},function(f,d){f.exports=`@[toc](Directory)

Guia Markdown
===
> Detalhes: [http://commonmark.org/help/](http://commonmark.org/help/)

## **Negrito**
\`\`\`
**negrito**
__negrito__
\`\`\`
## *Itálico*
\`\`\`
*itálico*
_itálico_
\`\`\`
## Cabeçalho
\`\`\`
# h1 #
h1
====
## h2 ##
h2
----
### h3 ###
#### h4 ####
##### h5 #####
###### h6 ######
\`\`\`
## Linha Divisora
\`\`\`
***
---
\`\`\`
****
## ^Sobre^scrito & ~Sub~scrito
\`\`\`
sobre x^2^
sub H~2~0
\`\`\`
## ++Sublinhar++ & ~~Tachar~~
\`\`\`
++sublinhar++
~~tachar~~
\`\`\`
## ==Marcador==
\`\`\`
==marcador==
\`\`\`
## Citação

\`\`\`
> citação 1
>> citação 2
>>> citação 3
...
\`\`\`

## Listas
\`\`\`
lista Numerada
1.
2.
3.
...

lista com marcadores
-
-
...
\`\`\`

## Todo Listas

- [x] Tarefa 1
- [ ] Tarefa 2

\`\`\`
- [x] Tarefa 1
- [ ] Tarefa 2
\`\`\`

## Link
\`\`\`
Link Texto
[Text](www.baidu.com)

Link Imagem
![Text](http://www.image.com)
\`\`\`
## Código
\\\`\`\` tipo

bloco de código

\\\`\`\`

\\\` código \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`

## Tabela
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| esquerda | centro | direita |
\`\`\`
| th1 | th2 | th3 |
| :--  | :--: | ----: |
| esquerda | centro | direita |
| ---------------------- | ------------- | ----------------- |
## Rodapé
\`\`\`
olá[^olá]
\`\`\`

Olhe para baixo[^olá]

[^olá]: rodapé

## Emojis
Detalhes: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$ Mathematics

Podemos mostrar fórmulas por exemplo：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$
Podemos também mostrar em uma única linha:
$$\\sum_{i=1}^n a_i=0$$
Detalhes: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## Layout

::: hljs-left
\`::: hljs-left\`
\`esquerda\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`centro\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`direita\`
\`:::\`
:::

## Definições

Termo 1

:   Definição 1

Termo 2 com *markup inline*

:   Definição 2

        { um pouco de código, parte da Definição 2 }

    Terceiro parágrafo da definição 2.

\`\`\`
Termo 1

:   Definição 1

Termo 2 com *markup inline*

:   Definição 2

        { um pouco de código, parte da Definição 2 }

    Terceiro parágrafo da definição 2.

\`\`\`

## Abreviações
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
A especificação HTML
é mantida pela W3C.
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
The HTML specification
é mantida pela W3C.
\`\`\`
`},function(f,d){f.exports=`@[toc](Catalog)  
  
Markdown помощь  
===  
> Подробнее: [http://commonmark.org/help/](http://commonmark.org/help/)  
  
## **Полужирный**  
\`\`\`  
**Полужирный**  
__Полужирный__  
\`\`\`  
## *Курсив*  
\`\`\`  
*Курсив*  
_Курсив_  
\`\`\`  
## Заголовок  
\`\`\`  
# h1 #  
h1  
====  
## h2 ##  
h2  
----  
### h3 ###  
#### h4 ####  
##### h5 #####  
###### h6 ######  
\`\`\`  
## Разделительная линия  
\`\`\`  
***  
---  
\`\`\`  
****  
## ^Верхний^индекс & ~Нижний~индекс  
\`\`\`  
верхний x^2^  
нижний H~2~0  
\`\`\`  
## ++Подчеркнутый++ & ~~Зачеркнутый~~  
\`\`\`  
++Подчеркнутый++  
~~Зачеркнутый~~  
\`\`\`  
## ==Отметка==  
\`\`\`  
==Отметка==  
\`\`\`  
## Цитата  
  
\`\`\`  
> Цитата  
>> Цитата 2  
>>> Цитата 3  
...  
\`\`\`  
  
## Список  
\`\`\`  
ol  
1.  
2.  
3.  
...  
  
ul  
-  
-  
...  
\`\`\`  
  
## Список задач  
  
- [x] Задача 1  
- [ ] Задача 2  
  
\`\`\`  
- [x] Задача 1  
- [ ] Задача 2  
\`\`\`  
  
## Ссылка  
\`\`\`  
Ссылка  
[Текст](www.baidu.com)  
  
Ссылка изображения  
![Текст](http://www.image.com)  
\`\`\`  
## Код  
\\\`\`\` type  
  
code block  
  
\\\`\`\`  
  
\\\` code \\\`  
  
\`\`\`c++  
int main()  
{  
 printf("hello world!");}  
\`\`\`  
\`code\`  
  
## Таблица  
\`\`\`  
| th1 | th2 | th3 |  
| :--  | :--: | ----: |  
| left | center | right |  
\`\`\`  
| th1 | th2 | th3 |  
| :--  | :--: | ----: |  
| left | center | right |  
| ---------------------- | ------------- | ----------------- |  
## Сноска  
\`\`\`  
Привет[^Привет]  
\`\`\`  
  
Тут что-то непонятное[^Привет]  
  
[^Привет]: А тут объяснение  
  
## Emojis  
Подробнее: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)  
\`\`\`  
:laughing:  
:blush:  
:smiley:  
:)  
...  
\`\`\`  
:laughing::blush::smiley::)  
  
## $\\KaTeX$ Mathematics  
  
Можно выводить такие формулы：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$  
А также в одну строку:
$$\\sum_{i=1}^n a_i=0$$  
Подробнее: 
- [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)
- [katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)
- [latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)  
  
## Разметка
  
::: hljs-left  
\`::: hljs-left\`  
\`left\`  
\`:::\`  
:::  
  
::: hljs-center  
\`::: hljs-center\`  
\`center\`  
\`:::\`  
:::  
  
::: hljs-right  
\`::: hljs-right\`  
\`right\`  
\`:::\`  
:::  
  
## Список определений
  
Термин 1  
  
:   Определение 1  
  
Термин  2 с использованием *разметки*
  
:   Определение 2  
  
 { Какой-нибудь код, часть определения 2 }  
 Третий параграф определения 2.  
\`\`\`  
Термин 1  
  
:   Определение 1  
  
Термин  2 с использованием *разметки*
  
:   Определение 2  
  
 { Какой-нибудь код, часть определения 2 }  
 Третий параграф определения 2.  
\`\`\`  
  
## Сокращения
*[HTML]: Hyper Text Markup Language  
*[W3C]:  World Wide Web Consortium  
The HTML specification  
is maintained by the W3C.  
\`\`\`  
*[HTML]: Hyper Text Markup Language  
*[W3C]:  World Wide Web Consortium  
The HTML specification  
is maintained by the W3C.  
\`\`\`
`},function(f,d){f.exports=`@[toc](目录)

Markdown 语法简介
=============
> [语法详解](http://commonmark.org/help/)

## **目录**
\`\`\`
@[toc](目录)
\`\`\`

## **粗体**
\`\`\`
**粗体**
__粗体__
\`\`\`
## *斜体*
\`\`\`
*斜体*
_斜体_
\`\`\`
## 标题
\`\`\`
# 一级标题 #
一级标题
====
## 二级标题 ##
二级标题
----
### 三级标题 ###
#### 四级标题 ####
##### 五级标题 #####
###### 六级标题 ######
\`\`\`
## 分割线
\`\`\`
***
---
\`\`\`
****
## ^上^角~下~标
\`\`\`
上角标 x^2^
下角标 H~2~0
\`\`\`
## ++下划线++ ~~中划线~~
\`\`\`
++下划线++
~~中划线~~
\`\`\`
## ==标记==
\`\`\`
==标记==
\`\`\`
## 段落引用
\`\`\`
> 一级
>> 二级
>>> 三级
...
\`\`\`

## 列表
\`\`\`
有序列表
1.
2.
3.
...
无序列表
-
-
...
\`\`\`

## 任务列表

- [x] 已完成任务
- [ ] 未完成任务

\`\`\`
- [x] 已完成任务
- [ ] 未完成任务
\`\`\`

## 链接
\`\`\`
[链接](www.baidu.com)
![图片描述](http://www.image.com)
\`\`\`
## 代码段落
\\\`\`\` type

代码段落

\\\`\`\`

\\\` 代码块 \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`
## 表格(table)
\`\`\`
| 标题1 | 标题2 | 标题3 |
| :--  | :--: | ----: |
| 左对齐 | 居中 | 右对齐 |
| ---------------------- | ------------- | ----------------- |
\`\`\`
| 标题1 | 标题2 | 标题3 |
| :--  | :--: | ----: |
| 左对齐 | 居中 | 右对齐 |
| ---------------------- | ------------- | ----------------- |
## 脚注(footnote)
\`\`\`
hello[^hello]
\`\`\`

见底部脚注[^hello]

[^hello]: 一个注脚

## 表情(emoji)
[参考网站: https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$公式

我们可以渲染公式例如：$x_i + y_i = z_i$和$\\sum_{i=1}^n a_i=0$
我们也可以单行渲染
$$\\sum_{i=1}^n a_i=0$$
具体可参照[katex文档](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex支持的函数](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex文档](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## 布局

::: hljs-left
\`::: hljs-left\`
\`居左\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`居中\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`居右\`
\`:::\`
:::

## 定义

术语一

:   定义一

包含有*行内标记*的术语二

:   定义二

        {一些定义二的文字或代码}

    定义二的第三段

\`\`\`
术语一

:   定义一

包含有*行内标记*的术语二

:   定义二

        {一些定义二的文字或代码}

    定义二的第三段

\`\`\`

## abbr
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
HTML 规范由 W3C 维护
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
HTML 规范由 W3C 维护
\`\`\`

`},function(f,d){f.exports=`@[toc](目錄)

Markdown 語法簡介
=============
> [語法詳解](http://commonmark.org/help/)

## **粗體**
\`\`\`
**粗體**
__粗體__
\`\`\`
## *斜體*
\`\`\`
*斜體*
_斜體_
\`\`\`
## 標題
\`\`\`
# 一級標題 #
一級標題
====
## 二級標題 ##
二級標題
----
### 三級標題 ###
#### 四級標題 ####
##### 五級標題 #####
###### 六級標題 ######
\`\`\`
## 分割線
\`\`\`
***
---
\`\`\`
****
## ^上^角~下~標
\`\`\`
上角標 x^2^
下角標 H~2~0
\`\`\`
## ++下劃線++ ~~中劃線~~
\`\`\`
++下劃線++
~~中劃線~~
\`\`\`
## ==標記==
\`\`\`
==標記==
\`\`\`
## 段落引用
\`\`\`
> 一級
>> 二級
>>> 三級
...
\`\`\`

## 列表
\`\`\`
有序列表
1.
2.
3.
...
無序列表
-
-
...
\`\`\`

## 任務列表

- [x] 已完成任務
- [ ] 未完成任務

\`\`\`
- [x] 已完成任務
- [ ] 未完成任務
\`\`\`

## 鏈接
\`\`\`
[鏈接](www.baidu.com)
![圖片描述](http://www.image.com)
\`\`\`
## 代碼段落
\\\`\`\` type

代碼段落

\\\`\`\`

\\\` 代碼塊 \\\`

\`\`\`c++
int main()
{
    printf("hello world!");
}
\`\`\`
\`code\`
## 表格(table)
\`\`\`
| 標題1 | 標題2 | 標題3 |
| :--  | :--: | ----: |
| 左對齊 | 居中 | 右對齊 |
| ---------------------- | ------------- | ----------------- |
\`\`\`
| 標題1 | 標題2 | 標題3 |
| :--  | :--: | ----: |
| 左對齊 | 居中 | 右對齊 |
| ---------------------- | ------------- | ----------------- |
## 腳註(footnote)
\`\`\`
hello[^hello]
\`\`\`

見底部腳註[^hello]

[^hello]: 一個註腳

## 表情(emoji)
[參考網站: https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)
\`\`\`
:laughing:
:blush:
:smiley:
:)
...
\`\`\`
:laughing::blush::smiley::)

## $\\KaTeX$公式

我們可以渲染公式例如：$x_i + y_i = z_i$和$\\sum_{i=1}^n a_i=0$
我們也可以單行渲染
$$\\sum_{i=1}^n a_i=0$$
具體可參照[katex文檔](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex支持的函數](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex文檔](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)

## 布局

::: hljs-left
\`::: hljs-left\`
\`居左\`
\`:::\`
:::

::: hljs-center
\`::: hljs-center\`
\`居中\`
\`:::\`
:::

::: hljs-right
\`::: hljs-right\`
\`居右\`
\`:::\`
:::

## 定義

術語一

:   定義一

包含有*行內標記*的術語二

:   定義二

        {一些定義二的文字或代碼}

    定義二的第三段

\`\`\`
術語一

:   定義一

包含有*行內標記*的術語二

:   定義二

        {一些定義二的文字或代碼}

    定義二的第三段

\`\`\`

## abbr
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
HTML 規範由 W3C 維護
\`\`\`
*[HTML]: Hyper Text Markup Language
*[W3C]:  World Wide Web Consortium
HTML 規範由 W3C 維護
\`\`\`

`},function(f,d){f.exports=/[\xAD\u0600-\u0605\u061C\u06DD\u070F\u08E2\u180E\u200B-\u200F\u202A-\u202E\u2060-\u2064\u2066-\u206F\uFEFF\uFFF9-\uFFFB]|\uD804[\uDCBD\uDCCD]|\uD82F[\uDCA0-\uDCA3]|\uD834[\uDD73-\uDD7A]|\uDB40[\uDC01\uDC20-\uDC7F]/},function(f,d,s){d.Any=s(59),d.Cc=s(57),d.Cf=s(195),d.P=s(34),d.Z=s(58)},function(f,d,s){function e(n){s(202)}var r=s(16)(s(64),s(199),e,null,null);r.options.__file="D:\\work\\songwang\\yuangongji\\mavonEditor\\node_modules\\auto-textarea\\auto-textarea.vue",r.esModule&&Object.keys(r.esModule).some(function(n){return n!=="default"&&n.substr(0,2)!=="__"})&&console.error("named exports are not supported in *.vue files."),r.options.functional&&console.error("[vue-loader] auto-textarea.vue: functional components are not supported with templates, they should use render functions."),f.exports=r.exports},function(f,d,s){f.exports={render:function(){var e=this,r=e.$createElement,n=e._self._c||r;return n("div",{staticClass:"v-right-item"},[e._t("right-toolbar-before"),e._v(" "),e.toolbars.navigation?n("button",{directives:[{name:"show",rawName:"v-show",value:!e.s_navigation,expression:"!s_navigation"}],staticClass:"op-icon fa fa-mavon-bars",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_navigation_on+" (F8)"},on:{click:function(i){return e.$clicks("navigation")}}}):e._e(),e._v(" "),e.toolbars.navigation?n("button",{directives:[{name:"show",rawName:"v-show",value:e.s_navigation,expression:"s_navigation"}],staticClass:"op-icon fa fa-mavon-bars selected",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_navigation_off+" (F8)"},on:{click:function(i){return e.$clicks("navigation")}}}):e._e(),e._v(" "),e.toolbars.preview?n("button",{directives:[{name:"show",rawName:"v-show",value:e.s_preview_switch,expression:"s_preview_switch"}],staticClass:"op-icon fa fa-mavon-eye-slash selected",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_edit+" (F9)"},on:{click:function(i){return e.$clicks("preview")}}}):e._e(),e._v(" "),e.toolbars.preview?n("button",{directives:[{name:"show",rawName:"v-show",value:!e.s_preview_switch,expression:"!s_preview_switch"}],staticClass:"op-icon fa fa-mavon-eye",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_preview+" (F9)"},on:{click:function(i){return e.$clicks("preview")}}}):e._e(),e._v(" "),e.toolbars.fullscreen?n("button",{directives:[{name:"show",rawName:"v-show",value:!e.s_fullScreen,expression:"!s_fullScreen"}],staticClass:"op-icon fa fa-mavon-arrows-alt",attrs:{type:"button",title:e.d_words.tl_fullscreen_on+" (F10)","aria-hidden":"true"},on:{click:function(i){return e.$clicks("fullscreen")}}}):e._e(),e._v(" "),e.toolbars.fullscreen?n("button",{directives:[{name:"show",rawName:"v-show",value:e.s_fullScreen,expression:"s_fullScreen"}],staticClass:"op-icon fa fa-mavon-compress selected",attrs:{type:"button",title:e.d_words.tl_fullscreen_off+" (F10)","aria-hidden":"true"},on:{click:function(i){return e.$clicks("fullscreen")}}}):e._e(),e._v(" "),e.toolbars.readmodel?n("button",{staticClass:"op-icon fa fa-mavon-window-maximize",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_read+" (F11)"},on:{click:function(i){return e.$clicks("read")}}}):e._e(),e._v(" "),e.toolbars.subfield?n("button",{staticClass:"op-icon fa fa-mavon-columns",class:{selected:e.s_subfield},attrs:{type:"button","aria-hidden":"true",title:(e.s_subfield?e.d_words.tl_single_column:e.d_words.tl_double_column)+" (F12)"},on:{click:function(i){return e.$clicks("subfield")}}}):e._e(),e._v(" "),e.toolbars.help&&e.toolbars.htmlcode&&e.toolbars.readmodel&&e.toolbars.fullscreen&&e.toolbars.subfield&&e.toolbars.navigation?n("span",{staticClass:"op-icon-divider"}):e._e(),e._v(" "),e.toolbars.htmlcode?n("button",{directives:[{name:"show",rawName:"v-show",value:!e.s_html_code,expression:"!s_html_code"}],staticClass:"op-icon fa fa-mavon-code",attrs:{type:"button",title:e.d_words.tl_html_on,"aria-hidden":"true"},on:{click:function(i){return e.$clicks("html")}}}):e._e(),e._v(" "),e.toolbars.htmlcode?n("button",{directives:[{name:"show",rawName:"v-show",value:e.s_html_code,expression:"s_html_code"}],staticClass:"op-icon fa fa-mavon-code selected",attrs:{type:"button",title:e.d_words.tl_html_off,"aria-hidden":"true"},on:{click:function(i){return e.$clicks("html")}}}):e._e(),e._v(" "),e.toolbars.help?n("button",{staticClass:"op-icon fa fa-mavon-question-circle",staticStyle:{"font-size":"17px",padding:"5px 6px 5px 3px"},attrs:{type:"button",title:e.d_words.tl_help,"aria-hidden":"true"},on:{click:function(i){return e.$clicks("help")}}}):e._e(),e._v(" "),e._t("right-toolbar-after")],2)},staticRenderFns:[]},f.exports.render._withStripped=!0},function(f,d,s){f.exports={render:function(){var e=this,r=e.$createElement,n=e._self._c||r;return n("div",{staticClass:"auto-textarea-wrapper",style:{fontSize:e.fontSize,lineHeight:e.lineHeight,height:e.fullHeight?"100%":"auto"}},[n("pre",{staticClass:"auto-textarea-block",style:{fontSize:e.fontSize,lineHeight:e.lineHeight,minHeight:e.fullHeight?"100%":"auto"}},[n("br"),e._v(e._s(e.temp_value)+" ")]),e._v(" "),n("textarea",{directives:[{name:"model",rawName:"v-model",value:e.temp_value,expression:"temp_value"}],ref:"vTextarea",staticClass:"auto-textarea-input",class:{"no-border":!e.border,"no-resize":!e.resize},style:{fontSize:e.fontSize,lineHeight:e.lineHeight},attrs:{autofocus:e.s_autofocus,spellcheck:"false",placeholder:e.placeholder},domProps:{value:e.temp_value},on:{keyup:e.change,input:function(i){i.target.composing||(e.temp_value=i.target.value)}}})])},staticRenderFns:[]},f.exports.render._withStripped=!0},function(f,d,s){f.exports={render:function(){var e=this,r=e.$createElement,n=e._self._c||r;return n("div",{staticClass:"v-left-item"},[e._t("left-toolbar-before"),e._v(" "),e.toolbars.bold?n("button",{staticClass:"op-icon fa fa-mavon-bold",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_bold+" (ctrl+b)"},on:{click:function(i){return e.$clicks("bold")}}}):e._e(),e._v(" "),e.toolbars.italic?n("button",{staticClass:"op-icon fa fa-mavon-italic",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_italic+" (ctrl+i)"},on:{click:function(i){return e.$clicks("italic")}}}):e._e(),e._v(" "),e.toolbars.header?n("div",{staticClass:"op-icon fa fa-mavon-header dropdown dropdown-wrapper",class:{selected:e.s_header_dropdown_open},attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_header+" (ctrl+h)"},on:{mouseleave:e.$mouseleave_header_dropdown,mouseenter:e.$mouseenter_header_dropdown}},[n("transition",{attrs:{name:"fade"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:e.s_header_dropdown_open,expression:"s_header_dropdown_open"}],staticClass:"op-header popup-dropdown",class:{transition:e.transition},on:{mouseenter:e.$mouseenter_header_dropdown,mouseleave:e.$mouseleave_header_dropdown}},[n("div",{staticClass:"dropdown-item",attrs:{title:"#"},on:{click:function(i){return i.stopPropagation(),e.$click_header("header1")}}},[n("span",[e._v(e._s(e.d_words.tl_header_one))])]),e._v(" "),n("div",{staticClass:"dropdown-item",attrs:{title:"## "},on:{click:function(i){return i.stopPropagation(),e.$click_header("header2")}}},[n("span",[e._v(e._s(e.d_words.tl_header_two))])]),e._v(" "),n("div",{staticClass:"dropdown-item",attrs:{title:"### "},on:{click:function(i){return i.stopPropagation(),e.$click_header("header3")}}},[n("span",[e._v(e._s(e.d_words.tl_header_three))])]),e._v(" "),n("div",{staticClass:"dropdown-item",attrs:{title:"#### "},on:{click:function(i){return i.stopPropagation(),e.$click_header("header4")}}},[n("span",[e._v(e._s(e.d_words.tl_header_four))])]),e._v(" "),n("div",{staticClass:"dropdown-item",attrs:{title:"##### "},on:{click:function(i){return i.stopPropagation(),e.$click_header("header5")}}},[n("span",[e._v(e._s(e.d_words.tl_header_five))])]),e._v(" "),n("div",{staticClass:"dropdown-item",attrs:{title:"###### "},on:{click:function(i){return i.stopPropagation(),e.$click_header("header6")}}},[n("span",[e._v(e._s(e.d_words.tl_header_six))])])])])],1):e._e(),e._v(" "),e.toolbars.header||e.toolbars.italic||e.toolbars.bold?n("span",{staticClass:"op-icon-divider"}):e._e(),e._v(" "),e.toolbars.underline?n("button",{staticClass:"op-icon fa fa-mavon-underline",attrs:{disabled:!e.editable,type:"button",title:e.d_words.tl_underline+" (ctrl+u)","aria-hidden":"true"},on:{click:function(i){return e.$clicks("underline")}}}):e._e(),e._v(" "),e.toolbars.strikethrough?n("button",{staticClass:"op-icon fa fa-mavon-strikethrough",attrs:{disabled:!e.editable,type:"button",title:e.d_words.tl_strikethrough+" (ctrl+shift+d)","aria-hidden":"true"},on:{click:function(i){return e.$clicks("strikethrough")}}}):e._e(),e._v(" "),e.toolbars.mark?n("button",{staticClass:"op-icon fa fa-mavon-thumb-tack",attrs:{disabled:!e.editable,type:"button",title:e.d_words.tl_mark+" (ctrl+m)","aria-hidden":"true"},on:{click:function(i){return e.$clicks("mark")}}}):e._e(),e._v(" "),e.toolbars.superscript?n("button",{staticClass:"op-icon fa fa-mavon-superscript",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_superscript+" (ctrl+alt+s)"},on:{click:function(i){return e.$clicks("superscript")}}}):e._e(),e._v(" "),e.toolbars.subscript?n("button",{staticClass:"op-icon fa fa-mavon-subscript",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_subscript+" (ctrl+shift+s)"},on:{click:function(i){return e.$clicks("subscript")}}}):e._e(),e._v(" "),e.toolbars.alignleft?n("button",{staticClass:"op-icon fa fa-mavon-align-left",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_alignleft+" (ctrl+l)"},on:{click:function(i){return e.$clicks("alignleft")}}}):e._e(),e._v(" "),e.toolbars.aligncenter?n("button",{staticClass:"op-icon fa fa-mavon-align-center",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_aligncenter+" (ctrl+e)"},on:{click:function(i){return e.$clicks("aligncenter")}}}):e._e(),e._v(" "),e.toolbars.alignright?n("button",{staticClass:"op-icon fa fa-mavon-align-right",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_alignright+" (ctrl+r)"},on:{click:function(i){return e.$clicks("alignright")}}}):e._e(),e._v(" "),e.toolbars.superscript||e.toolbars.subscript||e.toolbars.underline||e.toolbars.strikethrough||e.toolbars.mark?n("span",{staticClass:"op-icon-divider"}):e._e(),e._v(" "),e.toolbars.quote?n("button",{staticClass:"op-icon fa fa-mavon-quote-left",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_quote+" (ctrl+q)"},on:{click:function(i){return e.$clicks("quote")}}}):e._e(),e._v(" "),e.toolbars.ol?n("button",{staticClass:"op-icon fa fa-mavon-list-ol",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_ol+" (ctrl+o)"},on:{click:function(i){return e.$clicks("ol")}}}):e._e(),e._v(" "),e.toolbars.ul?n("button",{staticClass:"op-icon fa fa-mavon-list-ul",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_ul+" (ctrl+alt+u)"},on:{click:function(i){return e.$clicks("ul")}}}):e._e(),e._v(" "),e.toolbars.ul||e.toolbars.ol||e.toolbars.quote?n("span",{staticClass:"op-icon-divider"}):e._e(),e._v(" "),e.toolbars.link?n("button",{staticClass:"op-icon fa fa-mavon-link",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_link+" (ctrl+l)"},on:{click:function(i){return i.stopPropagation(),e.$toggle_imgLinkAdd("link")}}}):e._e(),e._v(" "),e.toolbars.imagelink?n("div",{staticClass:"op-icon fa fa-mavon-picture-o dropdown dropdown-wrapper",class:{selected:e.s_img_dropdown_open},attrs:{disabled:!e.editable,type:"button","aria-hidden":"true"},on:{mouseleave:e.$mouseleave_img_dropdown,mouseenter:e.$mouseenter_img_dropdown}},[n("transition",{attrs:{name:"fade"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:e.s_img_dropdown_open,expression:"s_img_dropdown_open"}],staticClass:"op-image popup-dropdown",class:{transition:e.transition},on:{mouseleave:e.$mouseleave_img_dropdown,mouseenter:e.$mouseenter_img_dropdown}},[n("div",{staticClass:"dropdown-item",on:{click:function(i){return i.stopPropagation(),e.$toggle_imgLinkAdd("imagelink")}}},[n("span",[e._v(e._s(e.d_words.tl_image))])]),e._v(" "),n("div",{staticClass:"dropdown-item",staticStyle:{overflow:"hidden"}},[n("input",{attrs:{type:"file",accept:"image/gif,image/jpeg,image/jpg,image/png,image/svg",multiple:"multiple"},on:{change:function(i){return e.$imgAdd(i)}}}),e._v(e._s(e.d_words.tl_upload)+`
                `)]),e._v(" "),e._l(e.img_file,function(i,t){return i&&i[1]?n("div",{key:t,staticClass:"dropdown-item dropdown-images",attrs:{title:i[1].name},on:{click:function(a){return a.stopPropagation(),e.$imgFileListClick(t)}}},[n("span",[e._v(e._s(i[1].name))]),e._v(" "),n("button",{staticClass:"op-icon fa fa-mavon-times",attrs:{slot:"right",type:"button","aria-hidden":"true",title:e.d_words.tl_upload_remove},on:{click:function(a){return a.stopPropagation(),e.$imgDel(t)}},slot:"right"}),e._v(" "),n("img",{staticClass:"image-show",class:{transition:e.transition},attrs:{src:i[1].miniurl,alt:"none"}})]):e._e()})],2)])],1):e._e(),e._v(" "),e.toolbars.code?n("button",{staticClass:"op-icon fa fa-mavon-code",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_code+" (ctrl+alt+c)"},on:{click:function(i){return e.$clicks("code")}}}):e._e(),e._v(" "),e.toolbars.table?n("button",{staticClass:"op-icon fa fa-mavon-table",attrs:{disabled:!e.editable,type:"button","aria-hidden":"true",title:e.d_words.tl_table+" (ctrl+alt+t)"},on:{click:function(i){return e.$clicks("table")}}}):e._e(),e._v(" "),e.toolbars.link||e.toolbars.imagelink||e.toolbars.code||e.toolbars.table?n("span",{staticClass:"op-icon-divider"}):e._e(),e._v(" "),e.toolbars.undo?n("button",{staticClass:"op-icon fa fa-mavon-undo",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_undo+" (ctrl+z)"},on:{click:function(i){return e.$clicks("undo")}}}):e._e(),e._v(" "),e.toolbars.redo?n("button",{staticClass:"op-icon fa fa-mavon-repeat",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_redo+" (ctrl+y)"},on:{click:function(i){return e.$clicks("redo")}}}):e._e(),e._v(" "),e.toolbars.trash?n("button",{staticClass:"op-icon fa fa-mavon-trash-o",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_trash+" (ctrl+breakspace)"},on:{click:function(i){return e.$clicks("trash")}}}):e._e(),e._v(" "),e.toolbars.save?n("button",{staticClass:"op-icon fa fa-mavon-floppy-o",attrs:{type:"button","aria-hidden":"true",title:e.d_words.tl_save+" (ctrl+s)"},on:{click:function(i){return e.$clicks("save")}}}):e._e(),e._v(" "),e._t("left-toolbar-after"),e._v(" "),n("transition",{attrs:{name:"fade"}},[e.s_img_link_open?n("div",{staticClass:"add-image-link-wrapper"},[n("div",{staticClass:"add-image-link"},[n("i",{staticClass:"fa fa-mavon-times",attrs:{"aria-hidden":"true"},on:{click:function(i){i.stopPropagation(),i.preventDefault(),e.s_img_link_open=!1}}}),e._v(" "),n("h3",{staticClass:"title"},[e._v(e._s(e.link_type=="link"?e.d_words.tl_popup_link_title:e.d_words.tl_popup_img_link_title))]),e._v(" "),n("div",{staticClass:"link-text input-wrapper"},[n("input",{directives:[{name:"model",rawName:"v-model",value:e.link_text,expression:"link_text"}],ref:"linkTextInput",attrs:{type:"text",placeholder:e.link_type=="link"?e.d_words.tl_popup_link_text:e.d_words.tl_popup_img_link_text},domProps:{value:e.link_text},on:{input:function(i){i.target.composing||(e.link_text=i.target.value)}}})]),e._v(" "),n("div",{staticClass:"link-addr input-wrapper"},[n("input",{directives:[{name:"model",rawName:"v-model",value:e.link_addr,expression:"link_addr"}],attrs:{type:"text",placeholder:e.link_type=="link"?e.d_words.tl_popup_link_addr:e.d_words.tl_popup_img_link_addr},domProps:{value:e.link_addr},on:{input:function(i){i.target.composing||(e.link_addr=i.target.value)}}})]),e._v(" "),n("div",{staticClass:"op-btn cancel",on:{click:function(i){i.stopPropagation(),e.s_img_link_open=!1}}},[e._v(e._s(e.d_words.tl_popup_link_cancel))]),e._v(" "),n("div",{staticClass:"op-btn sure",on:{click:function(i){return i.stopPropagation(),e.$imgLinkAdd()}}},[e._v(e._s(e.d_words.tl_popup_link_sure))])])]):e._e()])],2)},staticRenderFns:[]},f.exports.render._withStripped=!0},function(f,d,s){f.exports={render:function(){var e=this,r=e.$createElement,n=e._self._c||r;return n("div",{staticClass:"v-note-wrapper markdown-body",class:[{fullscreen:e.s_fullScreen,shadow:e.boxShadow}],style:{"box-shadow":e.boxShadow?e.boxShadowStyle:""}},[n("div",{directives:[{name:"show",rawName:"v-show",value:e.toolbarsFlag,expression:"toolbarsFlag"}],staticClass:"v-note-op",style:{background:e.toolbarsBackground}},[n("v-md-toolbar-left",{ref:"toolbar_left",class:{transition:e.transition},attrs:{editable:e.editable,transition:e.transition,d_words:e.d_words,toolbars:e.toolbars,image_filter:e.imageFilter},on:{toolbar_left_click:e.toolbar_left_click,toolbar_left_addlink:e.toolbar_left_addlink,imgAdd:e.$imgAdd,imgDel:e.$imgDel,imgTouch:e.$imgTouch}},[e._t("left-toolbar-before",null,{slot:"left-toolbar-before"}),e._v(" "),e._t("left-toolbar-after",null,{slot:"left-toolbar-after"})],2),e._v(" "),n("v-md-toolbar-right",{ref:"toolbar_right",class:{transition:e.transition},attrs:{d_words:e.d_words,toolbars:e.toolbars,s_subfield:e.s_subfield,s_preview_switch:e.s_preview_switch,s_fullScreen:e.s_fullScreen,s_html_code:e.s_html_code,s_navigation:e.s_navigation},on:{toolbar_right_click:e.toolbar_right_click}},[e._t("right-toolbar-before",null,{slot:"right-toolbar-before"}),e._v(" "),e._t("right-toolbar-after",null,{slot:"right-toolbar-after"})],2)],1),e._v(" "),n("div",{staticClass:"v-note-panel"},[n("div",{ref:"vNoteEdit",staticClass:"v-note-edit divarea-wrapper",class:{"scroll-style":e.s_scrollStyle,"scroll-style-border-radius":e.s_scrollStyle&&!e.s_preview_switch&&!e.s_html_code,"single-edit":!e.s_preview_switch&&!e.s_html_code,"single-show":!e.s_subfield&&e.s_preview_switch||!e.s_subfield&&e.s_html_code,transition:e.transition},on:{scroll:e.$v_edit_scroll,click:e.textAreaFocus}},[n("div",{staticClass:"content-input-wrapper",style:{"background-color":e.editorBackground}},[n("v-autoTextarea",{ref:"vNoteTextarea",staticClass:"content-input",style:{"background-color":e.editorBackground},attrs:{placeholder:e.placeholder?e.placeholder:e.d_words.start_editor,fontSize:e.fontSize,lineHeight:"1.5",fullHeight:""},model:{value:e.d_value,callback:function(i){e.d_value=i},expression:"d_value"}})],1)]),e._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:e.s_preview_switch||e.s_html_code,expression:"s_preview_switch || s_html_code"}],staticClass:"v-note-show",class:{"single-show":!e.s_subfield&&e.s_preview_switch||!e.s_subfield&&e.s_html_code}},[n("div",{directives:[{name:"show",rawName:"v-show",value:!e.s_html_code,expression:"!s_html_code"}],ref:"vShowContent",staticClass:"v-show-content",class:{"scroll-style":e.s_scrollStyle,"scroll-style-border-radius":e.s_scrollStyle},style:{"background-color":e.previewBackground},domProps:{innerHTML:e._s(e.d_render)}}),e._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:e.s_html_code,expression:"s_html_code"}],staticClass:"v-show-content-html",class:{"scroll-style":e.s_scrollStyle,"scroll-style-border-radius":e.s_scrollStyle},style:{"background-color":e.previewBackground}},[e._v(`
                `+e._s(e.d_render)+`
            `)])]),e._v(" "),n("transition",{attrs:{name:"slideTop"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:e.s_navigation,expression:"s_navigation"}],staticClass:"v-note-navigation-wrapper",class:{transition:e.transition}},[n("div",{staticClass:"v-note-navigation-title"},[e._v(`
                    `+e._s(e.d_words.navigation_title)),n("i",{staticClass:"fa fa-mavon-times v-note-navigation-close",attrs:{"aria-hidden":"true"},on:{click:function(i){return e.toolbar_right_click("navigation")}}})]),e._v(" "),n("div",{ref:"navigationContent",staticClass:"v-note-navigation-content",class:{"scroll-style":e.s_scrollStyle}})])])],1),e._v(" "),n("transition",{attrs:{name:"fade"}},[n("div",{ref:"help"},[e.s_help?n("div",{staticClass:"v-note-help-wrapper",on:{click:function(i){return i.target!==i.currentTarget?null:e.toolbar_right_click("help")}}},[n("div",{staticClass:"v-note-help-content markdown-body",class:{shadow:e.boxShadow}},[n("i",{staticClass:"fa fa-mavon-times",attrs:{"aria-hidden":"true"},on:{click:function(i){return i.stopPropagation(),i.preventDefault(),e.toolbar_right_click("help")}}}),e._v(" "),n("div",{staticClass:"scroll-style v-note-help-show",domProps:{innerHTML:e._s(e.d_help)}})])]):e._e()])]),e._v(" "),n("transition",{attrs:{name:"fade"}},[e.d_preview_imgsrc?n("div",{staticClass:"v-note-img-wrapper",on:{click:function(i){e.d_preview_imgsrc=null}}},[n("img",{attrs:{src:e.d_preview_imgsrc,alt:"none"}})]):e._e()]),e._v(" "),n("div",{ref:"vReadModel",staticClass:"v-note-read-model scroll-style",class:{show:e.s_readmodel}},[n("div",{ref:"vNoteReadContent",staticClass:"v-note-read-content",domProps:{innerHTML:e._s(e.d_render)}})])],1)},staticRenderFns:[]},f.exports.render._withStripped=!0},function(f,d,s){var e=s(107);typeof e=="string"&&(e=[[f.i,e,""]]),e.locals&&(f.exports=e.locals),s(17)("c0faed68",e,!1,{})},function(f,d,s){var e=s(108);typeof e=="string"&&(e=[[f.i,e,""]]),e.locals&&(f.exports=e.locals),s(17)("118de024",e,!1,{})},function(f,d,s){var e=s(109);typeof e=="string"&&(e=[[f.i,e,""]]),e.locals&&(f.exports=e.locals),s(17)("2f84471f",e,!1,{})},function(f,d,s){var e=s(110);typeof e=="string"&&(e=[[f.i,e,""]]),e.locals&&(f.exports=e.locals),s(17)("6daa4aa0",e,!1,{})},function(f,d){f.exports=function(s,e){for(var r=[],n={},i=0;i<e.length;i++){var t=e[i],a=t[0],u=t[1],c=t[2],o=t[3],l={id:s+":"+i,css:u,media:c,sourceMap:o};n[a]?n[a].parts.push(l):r.push(n[a]={id:a,parts:[l]})}return r}},function(f,d){var s;s=(function(){return this})();try{s=s||Function("return this")()||(0,eval)("this")}catch{typeof window=="object"&&(s=window)}f.exports=s},function(f,d){f.exports=function(s){return s.webpackPolyfill||(s.deprecate=function(){},s.paths=[],s.children||(s.children=[]),Object.defineProperty(s,"loaded",{enumerable:!0,get:function(){return s.l}}),Object.defineProperty(s,"id",{enumerable:!0,get:function(){return s.i}}),s.webpackPolyfill=1),s}},function(f,d,s){function e(a,u){return new i(u).process(a)}var r=s(60),n=s(61),i=s(210);d=f.exports=e,d.filterXSS=e,d.FilterXSS=i;for(var t in r)d[t]=r[t];for(var t in n)d[t]=n[t];typeof window<"u"&&(window.filterXSS=f.exports),(function(){return typeof self<"u"&&typeof DedicatedWorkerGlobalScope<"u"&&self instanceof DedicatedWorkerGlobalScope})()&&(self.filterXSS=f.exports)},function(f,d,s){function e(p){return p==null}function r(p){var _=l.spaceIndex(p);if(_===-1)return{html:"",closing:p[p.length-2]==="/"};p=l.trim(p.slice(_+1,-1));var h=p[p.length-1]==="/";return h&&(p=l.trim(p.slice(0,-1))),{html:p,closing:h}}function n(p){var _={};for(var h in p)_[h]=p[h];return _}function i(p){p=n(p||{}),p.stripIgnoreTag&&(p.onIgnoreTag&&console.error('Notes: cannot use these two options "stripIgnoreTag" and "onIgnoreTag" at the same time'),p.onIgnoreTag=a.onIgnoreTagStripAll),p.whiteList=p.whiteList||a.whiteList,p.onTag=p.onTag||a.onTag,p.onTagAttr=p.onTagAttr||a.onTagAttr,p.onIgnoreTag=p.onIgnoreTag||a.onIgnoreTag,p.onIgnoreTagAttr=p.onIgnoreTagAttr||a.onIgnoreTagAttr,p.safeAttrValue=p.safeAttrValue||a.safeAttrValue,p.escapeHtml=p.escapeHtml||a.escapeHtml,this.options=p,p.css===!1?this.cssFilter=!1:(p.css=p.css||{},this.cssFilter=new t(p.css))}var t=s(31).FilterCSS,a=s(60),u=s(61),c=u.parseTag,o=u.parseAttr,l=s(35);i.prototype.process=function(p){if(p=p||"",!(p=p.toString()))return"";var _=this,h=_.options,b=h.whiteList,m=h.onTag,g=h.onIgnoreTag,w=h.onTagAttr,v=h.onIgnoreTagAttr,x=h.safeAttrValue,k=h.escapeHtml,C=_.cssFilter;h.stripBlankChar&&(p=a.stripBlankChar(p)),h.allowCommentTag||(p=a.stripCommentTag(p));var y=!1;if(h.stripIgnoreTagBody){var y=a.StripTagBody(h.stripIgnoreTagBody,g);g=y.onIgnoreTag}var A=c(p,function(T,L,j,S,M){var F={sourcePosition:T,position:L,isClosing:M,isWhite:b.hasOwnProperty(j)},P=m(j,S,F);if(!e(P))return P;if(F.isWhite){if(F.isClosing)return"</"+j+">";var E=r(S),q=b[j],O=o(E.html,function(z,$){var R=l.indexOf(q,z)!==-1,B=w(j,z,$,R);if(!e(B))return B;if(R)return $=x(j,z,$,C),$?z+'="'+$+'"':z;var B=v(j,z,$,R);return e(B)?void 0:B}),S="<"+j;return O&&(S+=" "+O),E.closing&&(S+=" /"),S+=">"}var P=g(j,S,F);return e(P)?k(S):P},k);return y&&(A=y.remove(A)),A},f.exports=i},function(f,d){f.exports={start_editor:"Bearbeitung beginnen...",navigation_title:"Navigation",tl_bold:"Fett",tl_italic:"Kursiv",tl_header:"Überschrift",tl_header_one:"Überschrift 1",tl_header_two:"Überschrift 2",tl_header_three:"Überschrift 3",tl_header_four:"Überschrift 4",tl_header_five:"Überschrift 5",tl_header_six:"Überschrift 6",tl_underline:"Unterstrichen",tl_strikethrough:"Durchgestrichen",tl_mark:"Markiert",tl_superscript:"Hochgestellt",tl_subscript:"Tiefgestellt",tl_quote:"Zitat",tl_ol:"Ol",tl_ul:"Ul",tl_link:"Link",tl_image:"Link mit Bild",tl_code:"Code",tl_table:"Tabelle",tl_undo:"Rückgängig",tl_redo:"Wiederherstellen",tl_trash:"Mülleimer",tl_save:"Speichern",tl_navigation_on:"Navigation AN",tl_navigation_off:"Navigation AUS",tl_preview:"Vorschau",tl_aligncenter:"Text zentrieren",tl_alignleft:"Nach links ausrichten",tl_alignright:"Nach rechts ausrichten",tl_edit:"Bearbeiten",tl_single_column:"Einspaltig",tl_double_column:"Zweispaltig",tl_fullscreen_on:"Vollbild AN",tl_fullscreen_off:"Vollbild AUS",tl_read:"Lesemodus",tl_html_on:"HTML AN",tl_html_off:"HTML AUS",tl_help:"Markdown Handbuch",tl_upload:"Bilder-Upload",tl_upload_remove:"Entfernen",tl_popup_link_title:"Link hinzufügen",tl_popup_link_text:"Text des Links",tl_popup_link_addr:"Linkziel",tl_popup_img_link_title:"Bild hinzufügen",tl_popup_img_link_text:"Text des Bildes",tl_popup_img_link_addr:"Link auf Bild",tl_popup_link_sure:"Ja",tl_popup_link_cancel:"Abbruch"}},function(f,d){f.exports={start_editor:"Begin editing...",navigation_title:"Navigation",tl_bold:"Bold",tl_italic:"Italic",tl_header:"Header",tl_header_one:"Header 1",tl_header_two:"Header 2",tl_header_three:"Header 3",tl_header_four:"Header 4",tl_header_five:"Header 5",tl_header_six:"Header 6",tl_underline:"Underline",tl_strikethrough:"Strikethrough",tl_mark:"Mark",tl_superscript:"Superscript",tl_subscript:"Subscript",tl_quote:"Quote",tl_ol:"Ol",tl_ul:"Ul",tl_link:"Link",tl_image:"Image Link",tl_code:"Code",tl_table:"Table",tl_undo:"Undo",tl_redo:"Redo",tl_trash:"Trash",tl_save:"Save",tl_navigation_on:"Navigation ON",tl_navigation_off:"Navigation OFF",tl_preview:"Preview",tl_aligncenter:"Center text",tl_alignleft:"Clamp text to the left",tl_alignright:"Clamp text to the right",tl_edit:"Edit",tl_single_column:"Single Column",tl_double_column:"Double Columns",tl_fullscreen_on:"FullScreen ON",tl_fullscreen_off:"FullScreen OFF",tl_read:"Read Model",tl_html_on:"HTML ON",tl_html_off:"HTML OFF",tl_help:"Markdown Guide",tl_upload:"Upload Images",tl_upload_remove:"Remove",tl_popup_link_title:"Add Link",tl_popup_link_text:"Link text",tl_popup_link_addr:"Link address",tl_popup_img_link_title:"Add Image",tl_popup_img_link_text:"Image Text",tl_popup_img_link_addr:"Image Link",tl_popup_link_sure:"Sure",tl_popup_link_cancel:"Cancel"}},function(f,d){f.exports={start_editor:"Début d'édition...",navigation_title:"Navigation",tl_bold:"Gras",tl_italic:"Italique",tl_header:"Entête",tl_header_one:"Entête 1",tl_header_two:"Entête 2",tl_header_three:"Entête 3",tl_header_four:"Entête 4",tl_header_five:"Entête 5",tl_header_six:"Entête 6",tl_underline:"Souligné",tl_strikethrough:"Barré",tl_mark:"Mark",tl_superscript:"Exposant",tl_subscript:"Sous-exposant",tl_quote:"Quote",tl_ol:"Liste ",tl_ul:"Puce",tl_link:"Lien",tl_image:"Image Lien",tl_code:"Code",tl_table:"Table",tl_undo:"Annuler",tl_redo:"Refaire",tl_trash:"Supprimer",tl_save:"Sauver",tl_navigation_on:"Activer la navigation",tl_navigation_off:"Désactiver le navigation",tl_preview:"Previsualisé",tl_aligncenter:"Center le texte",tl_alignleft:"Férer le texte à gauche",tl_alignright:"Férer le texte à droite",tl_edit:"Editer",tl_single_column:"Seule Colonne",tl_double_column:"Colonnes Doubles",tl_fullscreen_on:"Activer le mode plein écran",tl_fullscreen_off:"Désactiver le mode plein écran",tl_read:"Lire le modèle",tl_html_on:"Activer le mode HTML",tl_html_off:"Désactiver le mode HTML",tl_help:"Guide Markdown",tl_upload:"Télécharger les images",tl_upload_remove:"Supprimer",tl_popup_link_title:"Ajouter un lien",tl_popup_link_text:"Description",tl_popup_link_addr:"Link",tl_popup_img_link_title:"Ajouter une image",tl_popup_img_link_text:"Description",tl_popup_img_link_addr:"Link",tl_popup_link_sure:"sûr",tl_popup_link_cancel:"Annuler"}},function(f,d){f.exports={start_editor:"編集を始めてね！",navigation_title:"ナビゲーション",tl_bold:"太字",tl_italic:"斜体",tl_header:"見出し",tl_header_one:"見出し1",tl_header_two:"見出し2",tl_header_three:"見出し3",tl_header_four:"見出し4",tl_header_five:"見出し5",tl_header_six:"見出し6",tl_underline:"下線",tl_strikethrough:"取り消し線",tl_mark:"蛍光ペン",tl_superscript:"上付き文字",tl_subscript:"下付き文字",tl_quote:"引用",tl_ol:"番号付きリスト",tl_ul:"箇条書きリスト",tl_link:"ハイパーリンク",tl_image:"画像のリンク",tl_code:"コードの挿入",tl_table:"表の挿入",tl_undo:"戻る",tl_redo:"進む",tl_trash:"削除",tl_save:"保存",tl_navigation_on:"ナビゲーションを表示",tl_navigation_off:"ナビゲーションを非表示",tl_preview:"プレビュー",tl_aligncenter:"中央揃え",tl_alignleft:"左揃え",tl_alignright:"右揃え",tl_edit:"編集",tl_single_column:"一列",tl_double_column:"二列",tl_fullscreen_on:"全画面表示",tl_fullscreen_off:"全画面表示の終了",tl_read:"モデルの読み込み",tl_html_on:"HTMLで表示",tl_html_off:"HTML表示の終了",tl_help:"ヘルプ",tl_upload:"画像をアップロード",tl_upload_remove:"画像の削除",tl_popup_link_title:"リンクの追加",tl_popup_link_text:"リンクテキスト",tl_popup_link_addr:"リンク先のURL",tl_popup_img_link_title:"画像の追加",tl_popup_img_link_text:"画像タイトル",tl_popup_img_link_addr:"画像URL",tl_popup_link_sure:"OK",tl_popup_link_cancel:"戻る"}},function(f,d){f.exports={start_editor:"Começar edição...",navigation_title:"Navegação",tl_bold:"Negrito",tl_italic:"Itálico",tl_header:"Cabeçalho",tl_header_one:"Cabeçalho 1",tl_header_two:"Cabeçalho 2",tl_header_three:"Cabeçalho 3",tl_header_four:"Cabeçalho 4",tl_header_five:"Cabeçalho 5",tl_header_six:"Cabeçalho 6",tl_underline:"Sublinhar",tl_strikethrough:"Tachar",tl_mark:"Marcação",tl_superscript:"Sobrescrito",tl_subscript:"Subscrito",tl_quote:"Citação",tl_ol:"Lista Numerada",tl_ul:"Lista com marcadores",tl_link:"Link",tl_image:"Link de imagem",tl_code:"Código",tl_table:"Tabela",tl_undo:"Desfazer",tl_redo:"Refazer",tl_trash:"Lixo",tl_save:"Salvar",tl_navigation_on:"Mostrar Navegação",tl_navigation_off:"Esconder Navegação",tl_preview:"Preview",tl_aligncenter:"Alinhar no centro",tl_alignleft:"Alinhar à esquerda",tl_alignright:"Alinhar à direita",tl_edit:"Editar",tl_single_column:"Coluna Única",tl_double_column:"Duas Colunas",tl_fullscreen_on:"Ligar Tela Cheia",tl_fullscreen_off:"Desligar Tela Cheia",tl_read:"Modo de Leitura",tl_html_on:"Ligar HTML",tl_html_off:"Desligar HTML",tl_help:"Guia Markdown",tl_upload:"Upload de Imagens",tl_upload_remove:"Remover",tl_popup_link_title:"Adicionar Link",tl_popup_link_text:"Descrição",tl_popup_link_addr:"Link",tl_popup_img_link_title:"Adicionar fotos",tl_popup_img_link_text:"Descrição",tl_popup_img_link_addr:"Link",tl_popup_link_sure:"Confirmar",tl_popup_link_cancel:"Cancelar"}},function(f,d){f.exports={start_editor:"Начните редактирование...",navigation_title:"Навигация",tl_bold:"Полужирный",tl_italic:"Курсив",tl_header:"Заголовки",tl_header_one:"Заголовок 1",tl_header_two:"Заголовок 2",tl_header_three:"Заголовок 3",tl_header_four:"Заголовок 4",tl_header_five:"Заголовок 5",tl_header_six:"Заголовок 6",tl_underline:"Подчеркнутый",tl_strikethrough:"Зачеркнутый",tl_mark:"Отметка",tl_superscript:"Верхний индекс",tl_subscript:"Нижний индекс",tl_quote:"Цитата",tl_ol:"Нумерованный список",tl_ul:"Список",tl_link:"Ссылка",tl_image:"Ссылка изображения",tl_code:"Код",tl_table:"Таблица",tl_undo:"Отменить",tl_redo:"Вернуть",tl_trash:"Удалить",tl_save:"Сохранить",tl_navigation_on:"Показать навигацию",tl_navigation_off:"Скрыть навигацию",tl_preview:"Предпросмотр",tl_aligncenter:"Выровнять по центру",tl_alignleft:"Выровнять по левому краю",tl_alignright:"Выровнять по правому краю",tl_edit:"Редактор",tl_single_column:"Одно поле",tl_double_column:"Два поля",tl_fullscreen_on:"Полноэкранный режим",tl_fullscreen_off:"Выключить полноэкранный режим",tl_read:"Режим чтения",tl_html_on:"Показать HTML",tl_html_off:"Убрать HTML",tl_help:"Markdown помощь",tl_upload:"Загрузить изображение",tl_upload_remove:"Удалить",tl_popup_link_title:"Добавить ссылку",tl_popup_link_text:"Текст ссылки",tl_popup_link_addr:"Адрес ссылки",tl_popup_img_link_title:"Локальное изображение",tl_popup_img_link_text:"Текст изображения",tl_popup_img_link_addr:"Ссылка изображения",tl_popup_link_sure:"Добавить",tl_popup_link_cancel:"Отменить"}},function(f,d){f.exports={start_editor:"开始编辑...",navigation_title:"导航目录",tl_bold:"粗体",tl_italic:"斜体",tl_header:"标题",tl_header_one:"一级标题",tl_header_two:"二级标题",tl_header_three:"三级标题",tl_header_four:"四级标题",tl_header_five:"五级标题",tl_header_six:"六级标题",tl_underline:"下划线",tl_strikethrough:"中划线",tl_mark:"标记",tl_superscript:"上角标",tl_subscript:"下角标",tl_quote:"段落引用",tl_ol:"有序列表",tl_ul:"无序列表",tl_link:"链接",tl_image:"添加图片链接",tl_code:"代码块",tl_table:"表格",tl_undo:"上一步",tl_redo:"下一步",tl_trash:"清空",tl_save:"保存",tl_navigation_on:"开启标题导航",tl_navigation_off:"关闭标题导航",tl_preview:"预览",tl_aligncenter:"居中",tl_alignleft:"居左",tl_alignright:"居右",tl_edit:"编辑",tl_single_column:"单栏",tl_double_column:"双栏",tl_fullscreen_on:"全屏编辑",tl_fullscreen_off:"退出全屏",tl_read:"沉浸式阅读",tl_html_on:"查看html文本",tl_html_off:"返回markdown文本",tl_help:"markdown语法帮助",tl_upload:"上传图片",tl_upload_remove:"删除",tl_popup_link_title:"添加链接",tl_popup_link_text:"链接描述",tl_popup_link_addr:"链接地址",tl_popup_img_link_title:"添加图片",tl_popup_img_link_text:"图片描述",tl_popup_img_link_addr:"图片链接",tl_popup_link_sure:"确定",tl_popup_link_cancel:"取消"}},function(f,d){f.exports={start_editor:"開始編輯...",navigation_title:"導航目錄",tl_bold:"粗體",tl_italic:"斜體",tl_header:"標題",tl_header_one:"一級標題",tl_header_two:"二級標題",tl_header_three:"三級標題",tl_header_four:"四級標題",tl_header_five:"五級標題",tl_header_six:"六級標題",tl_underline:"下劃線",tl_strikethrough:"中劃線",tl_mark:"標記",tl_superscript:"上角標",tl_subscript:"下角標",tl_quote:"段落引用",tl_ol:"有序列表",tl_ul:"無序列表",tl_link:"鏈接",tl_image:"添加圖片鏈接",tl_code:"代碼塊",tl_table:"表格",tl_undo:"上一步",tl_redo:"下一步",tl_trash:"清空",tl_save:"保存",tl_navigation_on:"開啟標題導航",tl_navigation_off:"關閉標題導航",tl_preview:"預覽",tl_aligncenter:"居中",tl_alignleft:"居左",tl_alignright:"居右",tl_edit:"編輯",tl_single_column:"單欄",tl_double_column:"雙欄",tl_fullscreen_on:"全屏編輯",tl_fullscreen_off:"退出全屏",tl_read:"沈浸式閱讀",tl_html_on:"查看html文本",tl_html_off:"返回markdown文本",tl_help:"markdown語法幫助",tl_upload:"上傳圖片",tl_upload_remove:"刪除",tl_popup_link_title:"添加鏈接",tl_popup_link_text:"鏈接描述",tl_popup_link_addr:"鏈接地址",tl_popup_img_link_title:"添加圖片",tl_popup_img_link_text:"圖片描述",tl_popup_img_link_addr:"圖片鏈接",tl_popup_link_sure:"確定",tl_popup_link_cancel:"取消"}}])})})(mavonEditor$2)),mavonEditor$2.exports}var mavonEditorExports=requireMavonEditor();const mavonEditor=getDefaultExportFromCjs(mavonEditorExports);export{mavonEditor as a,mavonEditorExports as m};
