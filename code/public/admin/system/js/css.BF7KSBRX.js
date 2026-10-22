import{t as __commonJSMin}from"./rolldown-runtime.C0FnF6B9.js";import{n as require_vue}from"./chunk-vendor.Di-Ybv3u.js";var require_mavon_editor=__commonJSMin(((exports,module)=>{(function(e,t){typeof exports==`object`&&typeof module==`object`?module.exports=t(require_vue()):typeof define==`function`&&define.amd?define(`MavonEditor`,[`vue`],t):typeof exports==`object`?exports.MavonEditor=t(require_vue()):e.MavonEditor=t(e.Vue)})(window,(function(__WEBPACK_EXTERNAL_MODULE__0__){return function(e){var t={};function n(r){if(t[r])return t[r].exports;var i=t[r]={i:r,l:!1,exports:{}};return e[r].call(i.exports,i,i.exports,n),i.l=!0,i.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){typeof Symbol<`u`&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:`Module`}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t||4&t&&typeof e==`object`&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&typeof e!=`string`)for(var i in e)n.d(r,i,function(t){return e[t]}.bind(null,i));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,`a`,t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p=``,n(n.s=79)}([function(e,t){e.exports=__WEBPACK_EXTERNAL_MODULE__0__},function(e,t,n){var r=Object.prototype.hasOwnProperty;function i(e,t){return r.call(e,t)}function a(e){return!(e>=55296&&e<=57343)&&!(e>=64976&&e<=65007)&&(65535&e)!=65535&&(65535&e)!=65534&&!(e>=0&&e<=8)&&e!==11&&!(e>=14&&e<=31)&&!(e>=127&&e<=159)&&!(e>1114111)}function o(e){if(e>65535){var t=55296+((e-=65536)>>10),n=56320+(1023&e);return String.fromCharCode(t,n)}return String.fromCharCode(e)}var s=/\\([!"#$%&'()*+,\-.\/:;<=>?@[\\\]^_`{|}~])/g,c=RegExp(s.source+`|&([a-z#][a-z0-9]{1,31});`,`gi`),l=/^#((?:x[a-f0-9]{1,8}|[0-9]{1,8}))/i,u=n(62),d=/[&<>"]/,f=/[&<>"]/g,p={"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`};function m(e){return p[e]}var h=/[.?*+^$[\]\\(){}|-]/g,g=n(40);t.lib={},t.lib.mdurl=n(63),t.lib.ucmicro=n(147),t.assign=function(e){return Array.prototype.slice.call(arguments,1).forEach((function(t){if(t){if(typeof t!=`object`)throw TypeError(t+`must be object`);Object.keys(t).forEach((function(n){e[n]=t[n]}))}})),e},t.isString=function(e){return function(e){return Object.prototype.toString.call(e)}(e)===`[object String]`},t.has=i,t.unescapeMd=function(e){return e.indexOf(`\\`)<0?e:e.replace(s,`$1`)},t.unescapeAll=function(e){return e.indexOf(`\\`)<0&&e.indexOf(`&`)<0?e:e.replace(c,(function(e,t,n){return t||function(e,t){var n=0;return i(u,t)?u[t]:t.charCodeAt(0)===35&&l.test(t)&&a(n=t[1].toLowerCase()===`x`?parseInt(t.slice(2),16):parseInt(t.slice(1),10))?o(n):e}(e,n)}))},t.isValidEntityCode=a,t.fromCodePoint=o,t.escapeHtml=function(e){return d.test(e)?e.replace(f,m):e},t.arrayReplaceAt=function(e,t,n){return[].concat(e.slice(0,t),n,e.slice(t+1))},t.isSpace=function(e){switch(e){case 9:case 32:return!0}return!1},t.isWhiteSpace=function(e){if(e>=8192&&e<=8202)return!0;switch(e){case 9:case 10:case 11:case 12:case 13:case 32:case 160:case 5760:case 8239:case 8287:case 12288:return!0}return!1},t.isMdAsciiPunct=function(e){switch(e){case 33:case 34:case 35:case 36:case 37:case 38:case 39:case 40:case 41:case 42:case 43:case 44:case 45:case 46:case 47:case 58:case 59:case 60:case 61:case 62:case 63:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 124:case 125:case 126:return!0;default:return!1}},t.isPunctChar=function(e){return g.test(e)},t.escapeRE=function(e){return e.replace(h,`\\$&`)},t.normalizeReference=function(e){return e=e.trim().replace(/\s+/g,` `),e.toLowerCase().toUpperCase()}},function(e,t){var n=e.exports=typeof window<`u`&&window.Math==Math?window:typeof self<`u`&&self.Math==Math?self:Function(`return this`)();typeof __g==`number`&&(__g=n)},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=(e,t)=>{let n=e.__vccOpts||e;for(let[e,r]of t)n[e]=r;return n}},function(e,t){var n={}.hasOwnProperty;e.exports=function(e,t){return n.call(e,t)}},function(e,t,n){var r=n(6),i=n(16);e.exports=n(7)?function(e,t,n){return r.f(e,t,i(1,n))}:function(e,t,n){return e[t]=n,e}},function(e,t,n){var r=n(14),i=n(47),a=n(28),o=Object.defineProperty;t.f=n(7)?Object.defineProperty:function(e,t,n){if(r(e),t=a(t,!0),r(n),i)try{return o(e,t,n)}catch{}if(`get`in n||`set`in n)throw TypeError(`Accessors not supported!`);return`value`in n&&(e[t]=n.value),e}},function(e,t,n){e.exports=!n(15)((function(){return Object.defineProperty({},"a",{get:function(){return 7}}).a!=7}))},function(e,t,n){var r=n(89),i=n(27);e.exports=function(e){return r(i(e))}},function(e,t,n){var r=n(32)(`wks`),i=n(17),a=n(2).Symbol,o=typeof a==`function`;(e.exports=function(e){return r[e]||(r[e]=o&&a[e]||(o?a:i)(`Symbol.`+e))}).store=r},function(e,t){e.exports=function(e){return typeof e==`object`?e!==null:typeof e==`function`}},function(module$1,exports$1,__webpack_require__){Object.defineProperty(exports$1,"__esModule",{value:!0});var _typeof2=__webpack_require__(25),_typeof3=_interopRequireDefault(_typeof2),_keydownListen=__webpack_require__(110),_langHljsCss=__webpack_require__(111),_langHljsCss2=_interopRequireDefault(_langHljsCss),_langHljs=__webpack_require__(56),_langHljs2=_interopRequireDefault(_langHljs),_extraFunction=__webpack_require__(57),_util=__webpack_require__(112),_toolbar_left_click2=__webpack_require__(113),_toolbar_right_click2=__webpack_require__(114),_config=__webpack_require__(115),_markdown=__webpack_require__(134),_markdown2=_interopRequireDefault(_markdown),_mdToolbarLeft=__webpack_require__(70),_mdToolbarLeft2=_interopRequireDefault(_mdToolbarLeft),_mdToolbarRight=__webpack_require__(72),_mdToolbarRight2=_interopRequireDefault(_mdToolbarRight),_autoTextarea=__webpack_require__(218),_autoTextarea2=_interopRequireDefault(_autoTextarea);function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}__webpack_require__(224),__webpack_require__(233),exports$1.default={emits:[`imgDel`,`change`,`fullScreen`,`readModel`,`previewToggle`,`subfieldToggle`,`htmlCode`,`helpToggle`,`save`,`navigationToggle`,`update:modelValue`],mixins:[_markdown2.default],props:{scrollStyle:{type:Boolean,default:!0},boxShadow:{type:Boolean,default:!0},transition:{type:Boolean,default:!0},autofocus:{type:Boolean,default:!0},fontSize:{type:String,default:`14px`},toolbarsBackground:{type:String,default:`#ffffff`},editorBackground:{type:String,default:`#ffffff`},previewBackground:{type:String,default:`#fbfbfb`},boxShadowStyle:{type:String,default:`0 2px 12px 0 rgba(0, 0, 0, 0.1)`},help:{type:String,default:null},modelValue:{type:String,default:``},language:{type:String,default:`zh-CN`},subfield:{type:Boolean,default:!0},navigation:{type:Boolean,default:!1},defaultOpen:{type:String,default:null},editable:{type:Boolean,default:!0},toolbarsFlag:{type:Boolean,default:!0},toolbars:{type:Object,default:function(){return _config.CONFIG.toolbars}},html:{type:Boolean,default:!0},xssOptions:{type:[Object,Boolean],default:function(){return{}}},codeStyle:{type:String,default:function(){return`github`}},placeholder:{type:String,default:null},ishljs:{type:Boolean,default:!0},externalLink:{type:[Object,Boolean],default:!0},imageFilter:{type:Function,default:null},imageClick:{type:Function,default:null},tabSize:{type:Number,default:0},shortCut:{type:Boolean,default:!0}},data:function(){var e,t,n=this;return{s_right_click_menu_show:!1,right_click_menu_top:0,right_click_menu_left:0,s_subfield:n.subfield,s_autofocus:!0,s_navigation:n.navigation,s_scrollStyle:n.scrollStyle,d_value:``,d_render:``,s_preview_switch:(t=n.defaultOpen,t||=n.subfield?`preview`:`edit`,t===`preview`),s_fullScreen:!1,s_help:!1,s_html_code:!1,d_help:null,d_words:null,edit_scroll_height:-1,s_readmodel:!1,s_table_enter:!1,d_history:(e=[],e.push(n.modelValue),e),d_history_index:0,currentTimeout:``,d_image_file:[],d_preview_imgsrc:null,s_external_link:{markdown_css:function(){return`https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.9.0/github-markdown.min.css`},hljs_js:function(){return`https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js`},hljs_lang:function(e){return`https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/languages/`+e+`.min.js`},hljs_css:function(e){return _langHljsCss2.default[e]?`https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/`+e+`.min.css`:``},katex_js:function(){return`https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.8.3/katex.min.js`},katex_css:function(){return`https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.8.3/katex.min.css`}},p_external_link:{},textarea_selectionEnd:0,textarea_selectionEnds:[0],_xssHandler:null}},created:function(){var e=this;this.initLanguage(),this.initExternalFuc(),this.$nextTick((function(){e.editableTextarea()}))},mounted:function(){var e=this;this.$el.addEventListener(`paste`,(function(t){e.$paste(t)})),this.$el.addEventListener(`drop`,(function(t){e.$drag(t)})),(0,_keydownListen.keydownListen)(this),(0,_extraFunction.ImagePreviewListener)(this),this.autofocus&&this.getTextareaDom().focus(),(0,_extraFunction.fullscreenchange)(this),this.d_value=this.modelValue||``,document.body.appendChild(this.$refs.help),this.loadExternalLink(`markdown_css`,`css`),this.loadExternalLink(`katex_css`,`css`),this.loadExternalLink(`katex_js`,`js`,(function(){e.iRender(!0)})),this.loadExternalLink(`hljs_js`,`js`,(function(){e.iRender(!0)})),(0,_typeof3.default)(e.externalLink)===`object`&&typeof e.externalLink.markdown_css==`function`||e.codeStyleChange(e.codeStyle,!0)},beforeUnmount:function(){document.body.removeChild(this.$refs.help)},getMarkdownIt:function(){var e=this.mixins[0].data().markdownIt;return e||=(0,_markdown.initMarkdown)(),e},methods:{loadExternalLink:function(e,t,n){if(typeof this.p_external_link[e]==`function`){var r={css:_extraFunction.loadLink,js:_extraFunction.loadScript};r.hasOwnProperty(t)&&r[t](this.p_external_link[e](),n)}else!1!==this.p_external_link[e]&&console.error(`external_link.`+e,`is not a function, if you want to disabled this error log, set external_link.`+e,`to function or false`)},initExternalFuc:function(){for(var e=[`markdown_css`,`hljs_js`,`hljs_css`,`hljs_lang`,`katex_js`,`katex_css`],t=(0,_typeof3.default)(this.externalLink),n=t===`object`,r=t===`boolean`,i=0;i<e.length;i++)r&&!this.externalLink||n&&!1===this.externalLink[e[i]]?this.p_external_link[e[i]]=!1:n&&typeof this.externalLink[e[i]]==`function`?this.p_external_link[e[i]]=this.externalLink[e[i]]:this.p_external_link[e[i]]=this.s_external_link[e[i]]},textAreaFocus:function(){this.$refs.vNoteTextarea.$refs.vTextarea.focus()},$drag:function(e){var t=e.dataTransfer;if(t){var n=t.files;n.length>0&&(e.preventDefault(),this.$refs.toolbar_left.$imgFilesAdd(n))}},$paste:function(e){var t=e.clipboardData;if(t){var n=t.items;if(!n)return;for(var r=t.types||[],i=null,a=0;a<r.length;a++)if(r[a]===`Files`){i=n[a];break}if(i&&i.kind===`file`){(0,_util.stopEvent)(e);var o=i.getAsFile();this.$refs.toolbar_left.$imgFilesAdd([o])}}},$imgTouch:function(e){},$imgDel:function(e){this.markdownIt.image_del(e[1]);var t=e[0],n=RegExp(`\\!\\[`+e[1]._name+`\\]\\(`+t+`\\)`,`g`);this.d_value=this.d_value.replace(n,``),this.iRender(),this.$emit(`imgDel`,e)},$imgAdd:function(e,t,n){n===void 0&&(n=!0);var r=this;if(this.__rFilter??=/^image\//i,this.__oFReader=new FileReader,this.__oFReader.onload=function(i){r.markdownIt.image_add(e,i.target.result),t.miniurl=i.target.result,!0===n&&(t._name=t.name.replace(/[\[\]\(\)\+\{\}&\|\\\*^%$#@\-]/g,``),r.insertText(r.getTextareaDom(),{prefix:`![`+t._name+`](`+e+`)`,subfix:``,str:``}),r.$nextTick((function(){r.$emit(`imgAdd`,e,t)})))},t){var i=t;this.__rFilter.test(i.type)&&this.__oFReader.readAsDataURL(i)}},$imgUpdateByUrl:function(e,t){var n=this;this.markdownIt.image_add(e,t),this.$nextTick((function(){n.d_render=this.markdownIt.render(this.d_value)}))},$imgAddByUrl:function(e,t){return!!this.$refs.toolbar_left.$imgAddByUrl(e,t)&&(this.$imgUpdateByUrl(e,t),!0)},$img2Url:function $img2Url(fileIndex,url){var reg_str=`/(!\\[[^\\[]*?\\](?=\\())\\(\\s*(`+fileIndex+`)\\s*\\)/g`,reg=eval(reg_str);this.d_value=this.d_value.replace(reg,`$1(`+url+`)`),this.$refs.toolbar_left.$changeUrl(fileIndex,url),this.iRender()},$imglst2Url:function(e){if(e instanceof Array)for(var t=0;t<e.length;t++)this.$img2Url(e[t][0],e[t][1])},toolbar_left_click:function(e){(0,_toolbar_left_click2.toolbar_left_click)(e,this)},toolbar_left_addlink:function(e,t,n){(0,_toolbar_left_click2.toolbar_left_addlink)(e,t,n,this)},toolbar_right_click:function(e){(0,_toolbar_right_click2.toolbar_right_click)(e,this)},getNavigation:function(e,t){return(0,_extraFunction.getNavigation)(e,t)},change:function(e,t){this.$emit(`change`,e,t)},fullscreen:function(e,t){this.$emit(`fullScreen`,e,t)},readmodel:function(e,t){this.$emit(`readModel`,e,t)},previewtoggle:function(e,t){this.$emit(`previewToggle`,e,t)},subfieldtoggle:function(e,t){this.$emit(`subfieldToggle`,e,t)},htmlcode:function(e,t){this.$emit(`htmlCode`,e,t)},helptoggle:function(e,t){this.$emit(`helpToggle`,e,t)},save:function(e,t){this.$emit(`save`,e,t)},navigationtoggle:function(e,t){this.$emit(`navigationToggle`,e,t)},$toolbar_right_read_change_status:function(){this.s_readmodel=!this.s_readmodel,this.readmodel&&this.readmodel(this.s_readmodel,this.d_value),this.s_readmodel&&this.toolbars.navigation&&this.getNavigation(this,!0)},$v_edit_scroll:function(e){(0,_extraFunction.scrollLink)(e,this)},getTextareaDom:function(){return this.$refs.vNoteTextarea.$refs.vTextarea},insertText:function(e,t){var n=t.prefix,r=t.subfix,i=t.str,a=t.type;(0,_extraFunction.insertTextAtCaret)(e,{prefix:n,subfix:r,str:i,type:a},this)},insertTab:function(){(0,_extraFunction.insertTab)(this,this.tabSize)},insertOl:function(){(0,_extraFunction.insertOl)(this)},removeLine:function(){(0,_extraFunction.removeLine)(this)},insertUl:function(){(0,_extraFunction.insertUl)(this)},unInsertTab:function(){(0,_extraFunction.unInsertTab)(this,this.tabSize)},insertCodeBlock:function(){(0,_extraFunction.insertCodeBlock)(this)},insertEnter:function(e){(0,_extraFunction.insertEnter)(this,e)},saveHistory:function(){this.d_history.splice(this.d_history_index+1,this.d_history.length),this.d_history.push(this.d_value),this.textarea_selectionEnds.splice(this.d_history_index+1,this.textarea_selectionEnds.length),this.textarea_selectionEnds.push(this.textarea_selectionEnd),this.d_history_index=this.d_history.length-1},saveSelectionEndsHistory:function(){var e=this.$refs.vNoteTextarea&&this.$refs.vNoteTextarea.$el.querySelector(`textarea`);this.textarea_selectionEnd=e?e.selectionEnd:this.textarea_selectionEnd},initLanguage:function(){var e=_config.CONFIG.langList.indexOf(this.language)>=0?this.language:`zh-CN`,t=this;t.$render(_config.CONFIG[`help_`+e],(function(e){t.d_help=e})),this.d_words=_config.CONFIG[`words_`+e]},editableTextarea:function(){var e=this.$refs.vNoteTextarea.$refs.vTextarea;this.editable?e.removeAttribute(`disabled`):e.setAttribute(`disabled`,`disabled`)},codeStyleChange:function(e,t){if(t||=!1,typeof this.p_external_link.hljs_css==`function`){var n=this.p_external_link.hljs_css(e);n.length===0&&t&&(console.warn(`hljs color scheme`,e,`do not exist, loading default github`),n=this.p_external_link.hljs_css(`github`)),n.length>0?(0,_extraFunction.loadLink)(n,null,`md-code-style`):console.warn(`hljs color scheme`,e,`do not exist, hljs color scheme will not change`)}else!1!==this.p_external_link.hljs_css&&console.error(`external_link.hljs_css is not a function, if you want to disabled this error log, set external_link.hljs_css to function or false`)},iRender:function(e){var t=this;this.$render(t.d_value,(function(n){t.d_render=n,e||t.change&&t.change(t.d_value,t.d_render),t.s_navigation&&(0,_extraFunction.getNavigation)(t,!1),t.$emit(`update:modelValue`,t.d_value),t.d_value!==t.d_history[t.d_history_index]&&(window.clearTimeout(t.currentTimeout),t.currentTimeout=setTimeout((function(){t.saveHistory()}),500))}))},$emptyHistory:function(){this.d_history=[this.d_value],this.d_history_index=0}},watch:{d_value:function(e,t){this.saveSelectionEndsHistory(),this.iRender()},modelValue:function(e,t){e!==this.d_value&&(this.d_value=e)},subfield:function(e,t){this.s_subfield=e},d_history_index:function(){this.d_history_index>20&&(this.d_history.shift(),--this.d_history_index),this.d_value=this.d_history[this.d_history_index]},language:function(e){this.initLanguage()},editable:function(){this.editableTextarea()},defaultOpen:function(e){var t=e;return t||=this.subfield?`preview`:`edit`,this.s_preview_switch=t===`preview`,this.s_preview_switch},codeStyle:function(e){this.codeStyleChange(e)}},components:{"v-autoTextarea":_autoTextarea2.default,"v-md-toolbar-left":_mdToolbarLeft2.default,"v-md-toolbar-right":_mdToolbarRight2.default}}},function(e,t){e.exports=!0},function(e,t){var n=e.exports={version:`2.6.12`};typeof __e==`number`&&(__e=n)},function(e,t,n){var r=n(10);e.exports=function(e){if(!r(e))throw TypeError(e+` is not an object!`);return e}},function(e,t){e.exports=function(e){try{return!!e()}catch{return!0}}},function(e,t){e.exports=function(e,t){return{enumerable:!(1&e),configurable:!(2&e),writable:!(4&e),value:t}}},function(e,t){var n=0,r=Math.random();e.exports=function(e){return`Symbol(${e===void 0?``:e})_${(++n+r).toString(36)}`}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default={emits:[`toolbar_left_addlink`,`imgTouch`,`imgAdd`,`imgDel`,`imgAdd`,`imgAdd`,`toolbar_left_click`,`toolbar_left_click`],name:`s-md-toolbar-left`,props:{editable:{type:Boolean,default:!0},transition:{type:Boolean,default:!0},toolbars:{type:Object,required:!0},d_words:{type:Object,required:!0},image_filter:{type:Function,default:null}},data:function(){return{img_file:[[0,null]],img_timer:null,header_timer:null,s_img_dropdown_open:!1,s_header_dropdown_open:!1,s_img_link_open:!1,trigger:null,num:0,link_text:``,link_addr:``,link_type:`link`}},methods:{$imgLinkAdd:function(){this.$emit(`toolbar_left_addlink`,this.link_type,this.link_text,this.link_addr),this.s_img_link_open=!1},$toggle_imgLinkAdd:function(e){var t=this;this.link_type=e,this.link_text=this.link_addr=``,this.s_img_link_open=!0,this.$nextTick((function(){t.$refs.linkTextInput.focus()})),this.s_img_dropdown_open=!1},$imgFileListClick:function(e){this.$emit(`imgTouch`,this.img_file[e])},$changeUrl:function(e,t){this.img_file[e][0]=t},$imgFileAdd:function(e){this.img_file.push([++this.num,e]),this.$emit(`imgAdd`,this.num,e),this.s_img_dropdown_open=!1},$imgFilesAdd:function(e){for(var t=typeof this.image_filter==`function`,n=0;n<e.length;n++)(t&&!0===this.image_filter(e[n])||!t&&e[n].type.match(/^image\//i))&&this.$imgFileAdd(e[n])},$imgAdd:function(e){this.$imgFilesAdd(e.target.files),e.target.value=``},$imgDel:function(e){this.$emit(`imgDel`,this.img_file[e]),this.img_file.splice(e,1),this.num--,this.s_img_dropdown_open=!1},isEqualName:function(e,t){return!(!this.img_file[t][1]||this.img_file[t][1].name!==e&&this.img_file[t][1]._name!==e)},$imgDelByFilename:function(e){for(var t=0;this.img_file.length>t;){if(this.img_file[t][1]===e||this.isEqualName(e,t))return this.$imgDel(t),!0;t+=1}return!1},$imgAddByFilename:function(e,t){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e)return!1;return this.img_file[0][0]=e,this.img_file[0][1]=t,this.img_file[0][2]=e,this.img_file.unshift([`./`+this.num,null]),this.$emit(`imgAdd`,this.img_file[1][0],t,!1),!0},$imgAddByUrl:function(e,t){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e)return!1;return this.img_file[0][0]=e,this.img_file[0][1]=t,this.img_file.unshift([`./`+this.num,null]),!0},$imgUpdateByFilename:function(e,t){for(var n=0;n<this.img_file.length;n++)if(this.img_file[n][0]===e||this.isEqualName(e,n))return this.img_file[n][1]=t,this.$emit(`imgAdd`,e,t,!1),!0;return!1},$mouseenter_img_dropdown:function(){this.editable&&(clearTimeout(this.img_timer),this.s_img_dropdown_open=!0)},$mouseleave_img_dropdown:function(){var e=this;this.img_timer=setTimeout((function(){e.s_img_dropdown_open=!1}),200)},$mouseenter_header_dropdown:function(){this.editable&&(clearTimeout(this.header_timer),this.s_header_dropdown_open=!0)},$mouseleave_header_dropdown:function(){var e=this;this.header_timer=setTimeout((function(){e.s_header_dropdown_open=!1}),200)},$clicks:function(e){this.editable&&this.$emit(`toolbar_left_click`,e)},$click_header:function(e){this.$emit(`toolbar_left_click`,e),this.s_header_dropdown_open=!1},handleClose:function(e){this.s_img_dropdown_open=!1}},watch:{s_img_link_open:function(e){this.$parent.$el.style.zIndex=e?1501:1500}}}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default={emits:[`toolbar_right_click`],name:`s-md-toolbar-right`,props:{s_subfield:{type:Boolean,required:!0},toolbars:{type:Object,required:!0},s_preview_switch:{type:Boolean,required:!0},s_fullScreen:{type:Boolean,required:!0},s_html_code:{type:Boolean,required:!0},s_navigation:{type:Boolean,required:!0},d_words:{type:Object,required:!0}},methods:{$clicks:function(e){this.$emit(`toolbar_right_click`,e)}}}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default={emits:[`update:modelValue`],data:function(){var e=this;return{temp_value:e.modelValue,s_autofocus:function(){if(e.autofocus)return`autofocus`}()}},created:function(){},props:{fullHeight:{type:Boolean,default:!1},autofocus:{type:Boolean,default:!1},modelValue:{type:String,default:``},placeholder:{type:String,default:``},border:{type:Boolean,default:!1},resize:{type:Boolean,default:!1},onchange:{type:Function,default:null},fontSize:{type:String,default:`14px`},lineHeight:{type:String,default:`18px`}},methods:{change:function(e){this.onchange&&this.onchange(this.temp_value,e)}},watch:{modelValue:function(e,t){this.temp_value=e},temp_value:function(e,t){this.$emit(`update:modelValue`,e)}}}},function(e,t,n){n.r(t);var r=n(11),i=n.n(r);for(var a in n.d(t,`default`,(function(){return i.a})),r)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return r[e]}))}(a)},function(e,t,n){n.r(t);var r=n(18),i=n.n(r);for(var a in n.d(t,`default`,(function(){return i.a})),r)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return r[e]}))}(a)},function(e,t,n){n.r(t);var r=n(19),i=n.n(r);for(var a in n.d(t,`default`,(function(){return i.a})),r)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return r[e]}))}(a)},function(e,t,n){n.r(t);var r=n(20),i=n.n(r);for(var a in n.d(t,`default`,(function(){return i.a})),r)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return r[e]}))}(a)},function(e,t,n){t.__esModule=!0;var r=o(n(81)),i=o(n(99)),a=typeof i.default==`function`&&typeof r.default==`symbol`?function(e){return typeof e}:function(e){return e&&typeof i.default==`function`&&e.constructor===i.default&&e!==i.default.prototype?`symbol`:typeof e};function o(e){return e&&e.__esModule?e:{default:e}}t.default=typeof i.default==`function`&&a(r.default)===`symbol`?function(e){return e===void 0?`undefined`:a(e)}:function(e){return e&&typeof i.default==`function`&&e.constructor===i.default&&e!==i.default.prototype?`symbol`:e===void 0?`undefined`:a(e)}},function(e,t){var n=Math.ceil,r=Math.floor;e.exports=function(e){return isNaN(e=+e)?0:(e>0?r:n)(e)}},function(e,t){e.exports=function(e){if(e==null)throw TypeError(`Can't call method on  `+e);return e}},function(e,t,n){var r=n(10);e.exports=function(e,t){if(!r(e))return e;var n,i;if(t&&typeof(n=e.toString)==`function`&&!r(i=n.call(e))||typeof(n=e.valueOf)==`function`&&!r(i=n.call(e))||!t&&typeof(n=e.toString)==`function`&&!r(i=n.call(e)))return i;throw TypeError(`Can't convert object to primitive value`)}},function(e,t){e.exports={}},function(e,t,n){var r=n(51),i=n(33);e.exports=Object.keys||function(e){return r(e,i)}},function(e,t,n){var r=n(32)(`keys`),i=n(17);e.exports=function(e){return r[e]||(r[e]=i(e))}},function(e,t,n){var r=n(13),i=n(2),a=i[`__core-js_shared__`]||={};(e.exports=function(e,t){return a[e]||(a[e]=t===void 0?{}:t)})(`versions`,[]).push({version:r.version,mode:n(12)?`pure`:`global`,copyright:`© 2020 Denis Pushkarev (zloirock.ru)`})},function(e,t){e.exports=`constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf`.split(`,`)},function(e,t,n){var r=n(6).f,i=n(4),a=n(9)(`toStringTag`);e.exports=function(e,t,n){e&&!i(e=n?e:e.prototype,a)&&r(e,a,{configurable:!0,value:t})}},function(e,t,n){t.f=n(9)},function(e,t,n){var r=n(2),i=n(13),a=n(12),o=n(35),s=n(6).f;e.exports=function(e){var t=i.Symbol||=a?{}:r.Symbol||{};e.charAt(0)==`_`||e in t||s(t,e,{value:o.f(e)})}},function(e,t){t.f={}.propertyIsEnumerable},function(e,t,n){var r=n(59),i=n(137);for(var a in(t=e.exports=function(e,t){return new i(t).process(e)}).FilterCSS=i,r)t[a]=r[a];typeof window<`u`&&(window.filterCSS=e.exports)},function(e,t){e.exports={indexOf:function(e,t){var n,r;if(Array.prototype.indexOf)return e.indexOf(t);for(n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1},forEach:function(e,t,n){var r,i;if(Array.prototype.forEach)return e.forEach(t,n);for(r=0,i=e.length;r<i;r++)t.call(n,e[r],r,e)},trim:function(e){return String.prototype.trim?e.trim():e.replace(/(^\s*)|(\s*$)/g,``)},spaceIndex:function(e){var t=/\s|\n|\t/.exec(e);return t?t.index:-1}}},function(e,t){e.exports=/[!-#%-\*,-\/:;\?@\[-\]_\{\}\xA1\xA7\xAB\xB6\xB7\xBB\xBF\u037E\u0387\u055A-\u055F\u0589\u058A\u05BE\u05C0\u05C3\u05C6\u05F3\u05F4\u0609\u060A\u060C\u060D\u061B\u061E\u061F\u066A-\u066D\u06D4\u0700-\u070D\u07F7-\u07F9\u0830-\u083E\u085E\u0964\u0965\u0970\u09FD\u0A76\u0AF0\u0C84\u0DF4\u0E4F\u0E5A\u0E5B\u0F04-\u0F12\u0F14\u0F3A-\u0F3D\u0F85\u0FD0-\u0FD4\u0FD9\u0FDA\u104A-\u104F\u10FB\u1360-\u1368\u1400\u166D\u166E\u169B\u169C\u16EB-\u16ED\u1735\u1736\u17D4-\u17D6\u17D8-\u17DA\u1800-\u180A\u1944\u1945\u1A1E\u1A1F\u1AA0-\u1AA6\u1AA8-\u1AAD\u1B5A-\u1B60\u1BFC-\u1BFF\u1C3B-\u1C3F\u1C7E\u1C7F\u1CC0-\u1CC7\u1CD3\u2010-\u2027\u2030-\u2043\u2045-\u2051\u2053-\u205E\u207D\u207E\u208D\u208E\u2308-\u230B\u2329\u232A\u2768-\u2775\u27C5\u27C6\u27E6-\u27EF\u2983-\u2998\u29D8-\u29DB\u29FC\u29FD\u2CF9-\u2CFC\u2CFE\u2CFF\u2D70\u2E00-\u2E2E\u2E30-\u2E4E\u3001-\u3003\u3008-\u3011\u3014-\u301F\u3030\u303D\u30A0\u30FB\uA4FE\uA4FF\uA60D-\uA60F\uA673\uA67E\uA6F2-\uA6F7\uA874-\uA877\uA8CE\uA8CF\uA8F8-\uA8FA\uA8FC\uA92E\uA92F\uA95F\uA9C1-\uA9CD\uA9DE\uA9DF\uAA5C-\uAA5F\uAADE\uAADF\uAAF0\uAAF1\uABEB\uFD3E\uFD3F\uFE10-\uFE19\uFE30-\uFE52\uFE54-\uFE61\uFE63\uFE68\uFE6A\uFE6B\uFF01-\uFF03\uFF05-\uFF0A\uFF0C-\uFF0F\uFF1A\uFF1B\uFF1F\uFF20\uFF3B-\uFF3D\uFF3F\uFF5B\uFF5D\uFF5F-\uFF65]|\uD800[\uDD00-\uDD02\uDF9F\uDFD0]|\uD801\uDD6F|\uD802[\uDC57\uDD1F\uDD3F\uDE50-\uDE58\uDE7F\uDEF0-\uDEF6\uDF39-\uDF3F\uDF99-\uDF9C]|\uD803[\uDF55-\uDF59]|\uD804[\uDC47-\uDC4D\uDCBB\uDCBC\uDCBE-\uDCC1\uDD40-\uDD43\uDD74\uDD75\uDDC5-\uDDC8\uDDCD\uDDDB\uDDDD-\uDDDF\uDE38-\uDE3D\uDEA9]|\uD805[\uDC4B-\uDC4F\uDC5B\uDC5D\uDCC6\uDDC1-\uDDD7\uDE41-\uDE43\uDE60-\uDE6C\uDF3C-\uDF3E]|\uD806[\uDC3B\uDE3F-\uDE46\uDE9A-\uDE9C\uDE9E-\uDEA2]|\uD807[\uDC41-\uDC45\uDC70\uDC71\uDEF7\uDEF8]|\uD809[\uDC70-\uDC74]|\uD81A[\uDE6E\uDE6F\uDEF5\uDF37-\uDF3B\uDF44]|\uD81B[\uDE97-\uDE9A]|\uD82F\uDC9F|\uD836[\uDE87-\uDE8B]|\uD83A[\uDD5E\uDD5F]/},function(e,t,n){function r(){this.__rules__=[],this.__cache__=null}r.prototype.__find__=function(e){for(var t=0;t<this.__rules__.length;t++)if(this.__rules__[t].name===e)return t;return-1},r.prototype.__compile__=function(){var e=this,t=[``];e.__rules__.forEach((function(e){e.enabled&&e.alt.forEach((function(e){t.indexOf(e)<0&&t.push(e)}))})),e.__cache__={},t.forEach((function(t){e.__cache__[t]=[],e.__rules__.forEach((function(n){n.enabled&&(t&&n.alt.indexOf(t)<0||e.__cache__[t].push(n.fn))}))}))},r.prototype.at=function(e,t,n){var r=this.__find__(e),i=n||{};if(r===-1)throw Error(`Parser rule not found: `+e);this.__rules__[r].fn=t,this.__rules__[r].alt=i.alt||[],this.__cache__=null},r.prototype.before=function(e,t,n,r){var i=this.__find__(e),a=r||{};if(i===-1)throw Error(`Parser rule not found: `+e);this.__rules__.splice(i,0,{name:t,enabled:!0,fn:n,alt:a.alt||[]}),this.__cache__=null},r.prototype.after=function(e,t,n,r){var i=this.__find__(e),a=r||{};if(i===-1)throw Error(`Parser rule not found: `+e);this.__rules__.splice(i+1,0,{name:t,enabled:!0,fn:n,alt:a.alt||[]}),this.__cache__=null},r.prototype.push=function(e,t,n){var r=n||{};this.__rules__.push({name:e,enabled:!0,fn:t,alt:r.alt||[]}),this.__cache__=null},r.prototype.enable=function(e,t){Array.isArray(e)||(e=[e]);var n=[];return e.forEach((function(e){var r=this.__find__(e);if(r<0){if(t)return;throw Error(`Rules manager: invalid rule name `+e)}this.__rules__[r].enabled=!0,n.push(e)}),this),this.__cache__=null,n},r.prototype.enableOnly=function(e,t){Array.isArray(e)||(e=[e]),this.__rules__.forEach((function(e){e.enabled=!1})),this.enable(e,t)},r.prototype.disable=function(e,t){Array.isArray(e)||(e=[e]);var n=[];return e.forEach((function(e){var r=this.__find__(e);if(r<0){if(t)return;throw Error(`Rules manager: invalid rule name `+e)}this.__rules__[r].enabled=!1,n.push(e)}),this),this.__cache__=null,n},r.prototype.getRules=function(e){return this.__cache__===null&&this.__compile__(),this.__cache__[e]||[]},e.exports=r},function(e,t,n){function r(e,t,n){this.type=e,this.tag=t,this.attrs=null,this.map=null,this.nesting=n,this.level=0,this.children=null,this.content=``,this.markup=``,this.info=``,this.meta=null,this.block=!1,this.hidden=!1}r.prototype.attrIndex=function(e){var t,n,r;if(!this.attrs)return-1;for(n=0,r=(t=this.attrs).length;n<r;n++)if(t[n][0]===e)return n;return-1},r.prototype.attrPush=function(e){this.attrs?this.attrs.push(e):this.attrs=[e]},r.prototype.attrSet=function(e,t){var n=this.attrIndex(e),r=[e,t];n<0?this.attrPush(r):this.attrs[n]=r},r.prototype.attrGet=function(e){var t=this.attrIndex(e),n=null;return t>=0&&(n=this.attrs[t][1]),n},r.prototype.attrJoin=function(e,t){var n=this.attrIndex(e);n<0?this.attrPush([e,t]):this.attrs[n][1]=this.attrs[n][1]+` `+t},e.exports=r},function(e,t,n){e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n=function(e,t){var n=e[1]||``,r=e[3];if(!r)return n;if(t&&typeof btoa==`function`){var i=(o=r,s=btoa(unescape(encodeURIComponent(JSON.stringify(o)))),c=`sourceMappingURL=data:application/json;charset=utf-8;base64,${s}`,`/*# ${c} */`),a=r.sources.map((function(e){return`/*# sourceURL=${r.sourceRoot||``}${e} */`}));return[n].concat(a,[i]).join(`
`)}var o,s,c;return[n].join(`
`)}(t,e);return t[2]?`@media ${t[2]} {${n}}`:n})).join(``)},t.i=function(e,n,r){typeof e==`string`&&(e=[[null,e,``]]);var i={};if(r)for(var a=0;a<this.length;a++){var o=this[a][0];o!=null&&(i[o]=!0)}for(var s=0;s<e.length;s++){var c=[].concat(e[s]);r&&i[c[0]]||(n&&(c[2]=c[2]?`${n} and ${c[2]}`:n),t.push(c))}},t}},function(e,t,n){function r(e,t){for(var n=[],r={},i=0;i<t.length;i++){var a=t[i],o=a[0],s={id:e+`:`+i,css:a[1],media:a[2],sourceMap:a[3]};r[o]?r[o].parts.push(s):n.push(r[o]={id:o,parts:[s]})}return n}n.r(t),n.d(t,`default`,(function(){return p}));var i=typeof document<`u`;if(typeof DEBUG<`u`&&DEBUG&&!i)throw Error(`vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.`);var a={},o=i&&(document.head||document.getElementsByTagName(`head`)[0]),s=null,c=0,l=!1,u=function(){},d=null,f=typeof navigator<`u`&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function p(e,t,n,i){l=n,d=i||{};var o=r(e,t);return m(o),function(t){for(var n=[],i=0;i<o.length;i++){var s=o[i];(c=a[s.id]).refs--,n.push(c)}for(t?m(o=r(e,t)):o=[],i=0;i<n.length;i++){var c;if((c=n[i]).refs===0){for(var l=0;l<c.parts.length;l++)c.parts[l]();delete a[c.id]}}}}function m(e){for(var t=0;t<e.length;t++){var n=e[t],r=a[n.id];if(r){r.refs++;for(var i=0;i<r.parts.length;i++)r.parts[i](n.parts[i]);for(;i<n.parts.length;i++)r.parts.push(g(n.parts[i]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var o=[];for(i=0;i<n.parts.length;i++)o.push(g(n.parts[i]));a[n.id]={id:n.id,refs:1,parts:o}}}}function h(){var e=document.createElement(`style`);return e.type=`text/css`,o.appendChild(e),e}function g(e){var t,n,r=document.querySelector(`style[data-vue-ssr-id~="`+e.id+`"]`);if(r){if(l)return u;r.parentNode.removeChild(r)}if(f){var i=c++;r=s||=h(),t=y.bind(null,r,i,!1),n=y.bind(null,r,i,!0)}else r=h(),t=b.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var _,v=(_=[],function(e,t){return _[e]=t,_.filter(Boolean).join(`
`)});function y(e,t,n,r){var i=n?``:r.css;if(e.styleSheet)e.styleSheet.cssText=v(t,i);else{var a=document.createTextNode(i),o=e.childNodes;o[t]&&e.removeChild(o[t]),o.length?e.insertBefore(a,o[t]):e.appendChild(a)}}function b(e,t){var n=t.css,r=t.media,i=t.sourceMap;if(r&&e.setAttribute(`media`,r),d.ssrId&&e.setAttribute(`data-vue-ssr-id`,t.id),i&&(n+=`
/*# sourceURL=`+i.sources[0]+` */`,n+=`
/*# sourceMappingURL=data:application/json;base64,`+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+` */`),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}},function(e,t,n){var r=n(12),i=n(46),a=n(49),o=n(5),s=n(29),c=n(87),l=n(34),u=n(94),d=n(9)(`iterator`),f=!([].keys&&`next`in[].keys()),p=function(){return this};e.exports=function(e,t,n,m,h,g,_){c(n,t,m);var v,y,b,x=function(e){if(!f&&e in T)return T[e];switch(e){case`keys`:case`values`:return function(){return new n(this,e)}}return function(){return new n(this,e)}},S=t+` Iterator`,C=h==`values`,w=!1,T=e.prototype,E=T[d]||T[`@@iterator`]||h&&T[h],D=E||x(h),O=h?C?x(`entries`):D:void 0,k=t==`Array`&&T.entries||E;if(k&&(b=u(k.call(new e)))!==Object.prototype&&b.next&&(l(b,S,!0),r||typeof b[d]==`function`||o(b,d,p)),C&&E&&E.name!==`values`&&(w=!0,D=function(){return E.call(this)}),r&&!_||!f&&!w&&T[d]||o(T,d,D),s[t]=D,s[S]=p,h){if(v={values:C?D:x(`values`),keys:g?D:x(`keys`),entries:O},_)for(y in v)y in T||a(T,y,v[y]);else i(i.P+i.F*(f||w),t,v)}return v}},function(e,t,n){var r=n(2),i=n(13),a=n(85),o=n(5),s=n(4),c=function(e,t,n){var l,u,d,f=e&c.F,p=e&c.G,m=e&c.S,h=e&c.P,g=e&c.B,_=e&c.W,v=p?i:i[t]||(i[t]={}),y=v.prototype,b=p?r:m?r[t]:(r[t]||{}).prototype;for(l in p&&(n=t),n)(u=!f&&b&&b[l]!==void 0)&&s(v,l)||(d=u?b[l]:n[l],v[l]=p&&typeof b[l]!=`function`?n[l]:g&&u?a(d,r):_&&b[l]==d?function(e){var t=function(t,n,r){if(this instanceof e){switch(arguments.length){case 0:return new e;case 1:return new e(t);case 2:return new e(t,n)}return new e(t,n,r)}return e.apply(this,arguments)};return t.prototype=e.prototype,t}(d):h&&typeof d==`function`?a(Function.call,d):d,h&&((v.virtual||={})[l]=d,e&c.R&&y&&!y[l]&&o(y,l,d)))};c.F=1,c.G=2,c.S=4,c.P=8,c.B=16,c.W=32,c.U=64,c.R=128,e.exports=c},function(e,t,n){e.exports=!n(7)&&!n(15)((function(){return Object.defineProperty(n(48)(`div`),"a",{get:function(){return 7}}).a!=7}))},function(e,t,n){var r=n(10),i=n(2).document,a=r(i)&&r(i.createElement);e.exports=function(e){return a?i.createElement(e):{}}},function(e,t,n){e.exports=n(5)},function(e,t,n){var r=n(14),i=n(88),a=n(33),o=n(31)(`IE_PROTO`),s=function(){},c=function(){var e,t=n(48)(`iframe`),r=a.length;for(t.style.display=`none`,n(93).appendChild(t),t.src=`javascript:`,(e=t.contentWindow.document).open(),e.write(`<script>document.F=Object<\/script>`),e.close(),c=e.F;r--;)delete c.prototype[a[r]];return c()};e.exports=Object.create||function(e,t){var n;return e===null?n=c():(s.prototype=r(e),n=new s,s.prototype=null,n[o]=e),t===void 0?n:i(n,t)}},function(e,t,n){var r=n(4),i=n(8),a=n(90)(!1),o=n(31)(`IE_PROTO`);e.exports=function(e,t){var n,s=i(e),c=0,l=[];for(n in s)n!=o&&r(s,n)&&l.push(n);for(;t.length>c;)r(s,n=t[c++])&&(~a(l,n)||l.push(n));return l}},function(e,t){var n={}.toString;e.exports=function(e){return n.call(e).slice(8,-1)}},function(e,t,n){var r=n(27);e.exports=function(e){return Object(r(e))}},function(e,t){t.f=Object.getOwnPropertySymbols},function(e,t,n){var r=n(51),i=n(33).concat(`length`,`prototype`);t.f=Object.getOwnPropertyNames||function(e){return r(e,i)}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default={"1c":`1c`,abnf:`abnf`,accesslog:`accesslog`,actionscript:`actionscript`,as:`actionscript`,ada:`ada`,apache:`apache`,apacheconf:`apache`,applescript:`applescript`,osascript:`applescript`,arduino:`arduino`,armasm:`armasm`,arm:`armasm`,asciidoc:`asciidoc`,adoc:`asciidoc`,aspectj:`aspectj`,autohotkey:`autohotkey`,ahk:`autohotkey`,autoit:`autoit`,avrasm:`avrasm`,awk:`awk`,axapta:`axapta`,bash:`bash`,sh:`bash`,zsh:`bash`,basic:`basic`,bnf:`bnf`,brainfuck:`brainfuck`,bf:`brainfuck`,cal:`cal`,capnproto:`capnproto`,capnp:`capnproto`,ceylon:`ceylon`,clean:`clean`,icl:`clean`,dcl:`clean`,"clojure-repl":`clojure-repl`,clojure:`clojure`,clj:`clojure`,cmake:`cmake`,"cmake.in":`cmake`,coffeescript:`coffeescript`,coffee:`coffeescript`,cson:`coffeescript`,iced:`coffeescript`,coq:`coq`,cos:`cos`,cls:`cos`,cpp:`cpp`,c:`cpp`,cc:`cpp`,h:`cpp`,"c++":`cpp`,"h++":`cpp`,hpp:`cpp`,crmsh:`crmsh`,crm:`crmsh`,pcmk:`crmsh`,crystal:`crystal`,cr:`crystal`,cs:`cs`,csharp:`cs`,csp:`csp`,css:`css`,d:`d`,dart:`dart`,delphi:`delphi`,dpr:`delphi`,dfm:`delphi`,pas:`delphi`,pascal:`delphi`,freepascal:`delphi`,lazarus:`delphi`,lpr:`delphi`,lfm:`delphi`,diff:`diff`,patch:`diff`,django:`django`,jinja:`django`,dns:`dns`,bind:`dns`,zone:`dns`,dockerfile:`dockerfile`,docker:`dockerfile`,dos:`dos`,bat:`dos`,cmd:`dos`,dsconfig:`dsconfig`,dts:`dts`,dust:`dust`,dst:`dust`,ebnf:`ebnf`,elixir:`elixir`,elm:`elm`,erb:`erb`,"erlang-repl":`erlang-repl`,erlang:`erlang`,erl:`erlang`,excel:`excel`,xlsx:`excel`,xls:`excel`,fix:`fix`,flix:`flix`,fortran:`fortran`,f90:`fortran`,f95:`fortran`,fsharp:`fsharp`,fs:`fsharp`,gams:`gams`,gms:`gams`,gauss:`gauss`,gss:`gauss`,gcode:`gcode`,nc:`gcode`,gherkin:`gherkin`,feature:`gherkin`,glsl:`glsl`,go:`go`,golang:`go`,golo:`golo`,gradle:`gradle`,groovy:`groovy`,haml:`haml`,handlebars:`handlebars`,hbs:`handlebars`,"html.hbs":`handlebars`,"html.handlebars":`handlebars`,haskell:`haskell`,hs:`haskell`,haxe:`haxe`,hx:`haxe`,hsp:`hsp`,htmlbars:`htmlbars`,http:`http`,https:`http`,hy:`hy`,hylang:`hy`,inform7:`inform7`,i7:`inform7`,ini:`ini`,toml:`ini`,irpf90:`irpf90`,java:`java`,jsp:`java`,javascript:`javascript`,js:`javascript`,jsx:`javascript`,"jboss-cli":`jboss-cli`,"wildfly-cli":`jboss-cli`,json:`json`,"julia-repl":`julia-repl`,julia:`julia`,kotlin:`kotlin`,lasso:`lasso`,ls:`livescript`,lassoscript:`lasso`,ldif:`ldif`,leaf:`leaf`,less:`less`,lisp:`lisp`,livecodeserver:`livecodeserver`,livescript:`livescript`,llvm:`llvm`,lsl:`lsl`,lua:`lua`,makefile:`makefile`,mk:`makefile`,mak:`makefile`,markdown:`markdown`,md:`markdown`,mkdown:`markdown`,mkd:`markdown`,mathematica:`mathematica`,mma:`mathematica`,matlab:`matlab`,maxima:`maxima`,mel:`mel`,mercury:`mercury`,m:`mercury`,moo:`mercury`,mipsasm:`mipsasm`,mips:`mipsasm`,mizar:`mizar`,mojolicious:`mojolicious`,monkey:`monkey`,moonscript:`moonscript`,moon:`moonscript`,n1ql:`n1ql`,nginx:`nginx`,nginxconf:`nginx`,nimrod:`nimrod`,nim:`nimrod`,nix:`nix`,nixos:`nix`,nsis:`nsis`,objectivec:`objectivec`,mm:`objectivec`,objc:`objectivec`,"obj-c":`objectivec`,ocaml:`ocaml`,ml:`sml`,openscad:`openscad`,scad:`openscad`,oxygene:`oxygene`,parser3:`parser3`,perl:`perl`,pl:`perl`,pm:`perl`,pf:`pf`,"pf.conf":`pf`,php:`php`,php3:`php`,php4:`php`,php5:`php`,php6:`php`,pony:`pony`,powershell:`powershell`,ps:`powershell`,processing:`processing`,profile:`profile`,prolog:`prolog`,protobuf:`protobuf`,puppet:`puppet`,pp:`puppet`,purebasic:`purebasic`,pb:`purebasic`,pbi:`purebasic`,python:`python`,py:`python`,gyp:`python`,q:`q`,k:`q`,kdb:`q`,qml:`qml`,qt:`qml`,r:`r`,rib:`rib`,roboconf:`roboconf`,graph:`roboconf`,instances:`roboconf`,routeros:`routeros`,mikrotik:`routeros`,rsl:`rsl`,ruby:`ruby`,rb:`ruby`,gemspec:`ruby`,podspec:`ruby`,thor:`ruby`,irb:`ruby`,ruleslanguage:`ruleslanguage`,rust:`rust`,rs:`rust`,scala:`scala`,scheme:`scheme`,scilab:`scilab`,sci:`scilab`,scss:`scss`,shell:`shell`,console:`shell`,smali:`smali`,smalltalk:`smalltalk`,st:`smalltalk`,sml:`sml`,sqf:`sqf`,sql:`sql`,stan:`stan`,stata:`stata`,do:`stata`,ado:`stata`,step21:`step21`,p21:`step21`,step:`step21`,stp:`step21`,stylus:`stylus`,styl:`stylus`,subunit:`subunit`,swift:`swift`,taggerscript:`taggerscript`,tap:`tap`,tcl:`tcl`,tk:`tcl`,tex:`tex`,thrift:`thrift`,tp:`tp`,twig:`twig`,craftcms:`twig`,typescript:`typescript`,ts:`typescript`,vala:`vala`,vbnet:`vbnet`,vb:`vbnet`,"vbscript-html":`vbscript-html`,vbscript:`vbscript`,vbs:`vbscript`,verilog:`verilog`,v:`verilog`,sv:`verilog`,svh:`verilog`,vhdl:`vhdl`,vim:`vim`,x86asm:`x86asm`,xl:`xl`,tao:`xl`,xml:`xml`,html:`xml`,xhtml:`xml`,rss:`xml`,atom:`xml`,xjb:`xml`,xsd:`xml`,xsl:`xml`,plist:`xml`,xquery:`xquery`,xpath:`xquery`,xq:`xquery`,yaml:`yaml`,yml:`yaml`,YAML:`yaml`,zephir:`zephir`,zep:`zephir`}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.loadScript=function(e,t){typeof t!=`function`&&(t=function(){});var n=document.querySelectorAll(`script[src='`+e+`']`);if(n.length>0)return n[0].addEventListener(`load`,(function(){t()})),void t();var r=document.createElement(`script`),i=document.getElementsByTagName(`head`)[0];r.type=`text/javascript`,r.charset=`UTF-8`,r.src=e,r.addEventListener?r.addEventListener(`load`,(function(){t()}),!1):r.attachEvent&&r.attachEvent(`onreadystatechange`,(function(){window.event.srcElement.readyState===`loaded`&&t()})),i.appendChild(r)},t.loadLink=function(e,t,n){if(typeof t!=`function`&&(t=function(){}),document.querySelectorAll(`link[href='`+e+`']`).length>0)return void t();if(n){var r=document.querySelectorAll(`link#`+n);if(r.length)return void(r[0].href=e)}var i=document.createElement(`link`),a=document.getElementsByTagName(`head`)[0];i.rel=`stylesheet`,i.href=e,n&&(i.id=n),i.addEventListener?i.addEventListener(`load`,(function(){t()}),!1):i.attachEvent&&i.attachEvent(`onreadystatechange`,(function(){window.event.srcElement.readyState===`loaded`&&t()})),a.appendChild(i)},t.insertTextAtCaret=function(e,t,n){var r=t.prefix,i=t.subfix,a=t.str;if(t.type,e.focus(),s(e)){var o=e.selectionStart,c=e.selectionEnd,l=e.value;o===c?(e.value=l.substring(0,o)+r+a+i+l.substring(c,l.length),e.selectionStart=o+r.length,e.selectionEnd=o+(a.length+r.length)):l.substring(o-r.length,o)===r&&l.substring(c,c+i.length)===i&&function(e,t,n,r,i){return e!==`*`||t!==`*`||n.substring(r-2,r-1)!==`*`||n.substring(i+1,i+2)!==`*`}(r,i,l,o,c)?(e.value=l.substring(0,o-r.length)+l.substring(o,c)+l.substring(c+i.length,l.length),e.selectionStart=o-r.length,e.selectionEnd=c-r.length):(e.value=l.substring(0,o)+r+l.substring(o,c)+i+l.substring(c,l.length),e.selectionStart=o+r.length,e.selectionEnd=o+(c-o+r.length))}else alert(`Error: Browser version is too low`);n.d_value=e.value,e.focus()};var r="```",i="\n```\n",a=`language`;t.insertCodeBlock=function(e){var t=e.getTextareaDom();if(s(t)){var n=a,c=t.selectionStart,l=t.selectionEnd,u=t.value;c===l?o(n,``,t):function(e){var t=e.selectionStart,n=e.selectionEnd,a=e.value,o=r,s=i;return a.substring(t-1,t)===`
`?o+=`
`:s=`
`+s,a.substring(t-o.length,t)===o&&a.substring(n,n+s.length)===s}(t)?function(e){var t=r,n=i,o=a,s=e.selectionStart,c=e.selectionEnd,l=e.value,u=l.substring(s,c);if(l.substring(s-1,s)===`
`)t+=`
`;else if(t+o+(n=`
`+n)===l.substring(s-t.length,c+n.length)){var d=l.substring(0,s-t.length);d+=l.substring(c+n.length,l.length),e.value=d,e.selectionStart=s-t.length,e.selectionEnd=e.selectionStart;return}var f=l.substring(0,s-t.length);f+=u,f+=l.substring(c+n.length,l.length),e.value=f,e.selectionStart=s-t.length,e.selectionEnd=e.selectionStart+u.length}(t):o(``,u.substring(c,l),t),e.d_value=t.value,t.focus()}else alert(`Error: Browser version is too low`)};function o(e,t,n){var a=r,o=i,s=n.selectionStart,c=n.selectionEnd,l=n.value,u=l.substring(0,s);u+=a+e+`
`,u+=t,u+=o,u+=l.substring(c,l.length),n.value=u,n.selectionStart=s+a.length+ +!e,n.selectionEnd=n.selectionStart+e.length+t.length}function s(e){return typeof e.selectionStart==`number`&&typeof e.selectionEnd==`number`}t.insertOl=function(e){var t=e.getTextareaDom();if(s(t)){var n=t.selectionStart,r=t.selectionEnd,i=t.value;if(n===r)t.value=i.substring(0,n)+`1. `+i.substring(r,i.length),t.selectionEnd=t.selectionStart=n+3;else{for(var a=n;a>0&&i.substring(a-1,a)!==`
`;)a--;for(var o=i.substring(a,r),c=o.split(`
`),l=0;l<c.length;l++)c[l]=l+1+`. `+c[l];var u=c.join(`
`);t.value=i.substring(0,a)+u+i.substring(r,i.length),t.selectionStart=a,t.selectionEnd=r+u.length-o.length}}else alert(`Error: Browser version is too low`);e.d_value=t.value,t.focus()},t.removeLine=function(e){var t=e.getTextareaDom();if(s(t)){for(var n=t.selectionStart,r=t.selectionEnd,i=t.value,a=n;a>0&&i.substring(a-1,a)!==`
`;)a--;for(var o=r;o<i.length&&i.substring(o,o+1)!==`
`;)o++;o<i.length&&o++,t.value=i.substring(0,a)+i.substring(o,i.length),t.selectionEnd=t.selectionStart=a===0?0:a-1}else alert(`Error: Browser version is too low`);e.d_value=t.value,t.focus()},t.insertUl=function(e){var t=e.getTextareaDom();if(s(t)){var n=t.selectionStart,r=t.selectionEnd,i=t.value;if(n===r)t.value=i.substring(0,n)+`- `+i.substring(r,i.length),t.selectionEnd=t.selectionStart=n+2;else{for(var a=n;a>0&&i.substring(a-1,a)!==`
`;)a--;var o=i.substring(a,r),c=o.replace(/\n/g,`
- `);c=`- `+c,t.value=i.substring(0,a)+c+i.substring(r,i.length),t.selectionStart=a,t.selectionEnd=r+c.length-o.length}}else alert(`Error: Browser version is too low`);e.d_value=t.value,t.focus()},t.insertTab=function(e,t){t=t?Array(t).fill(` `).join(``):`	`;var n=e.getTextareaDom();if(s(n)){var r=n.selectionStart,i=n.selectionEnd,a=n.value,o=a.substring(0,r).split(`
`).pop();if(o.match(/^\s*[0-9]+\.\s+\S*/)){var c=o.replace(/(\d+)/,1);n.value=a.substring(0,r-c.length)+t+c+a.substring(i,a.length)}else n.value=o.match(/^\s*-\s+\S*/)?a.substring(0,r-o.length)+t+o+a.substring(i,a.length):a.substring(0,r)+t+a.substring(i,a.length);n.selectionStart=n.selectionEnd=r+t.length}else alert(`Error: Browser version is too low`);e.d_value=n.value,n.focus()},t.unInsertTab=function(e,t){var n=RegExp(t?`\\s{`+t+`}`:`	`);console.log(`regTab:`,n);var r=e.getTextareaDom();if(s(r)){var i=r.selectionStart,a=r.selectionEnd,o=r.value,c=o.substring(0,i).split(`
`).pop();c.search(n)>=0&&(r.value=o.substring(0,i-c.length)+c.replace(n,``)+o.substring(a,o.length),r.selectionStart=r.selectionEnd=i-(t||1))}else alert(`Error: Browser version is too low`);e.d_value=r.value,r.focus()},t.insertEnter=function(e,t){var n=e.getTextareaDom();if(s(n)){var r=n.selectionStart,i=n.selectionEnd,a=n.value,o=a.substring(0,r).split(`
`).pop(),c=o.match(/^\s*(?:[0-9]+\.|-)\s+\S+/);if(c){t.preventDefault();var l=c.shift().match(/^\s*(?:[0-9]+\.|-)\s/).shift();if(l.search(/-/)>=0)n.value=a.substring(0,r)+`
`+l+a.substring(i,a.length),n.selectionStart=n.selectionEnd=r+l.length+1;else{var u=l.replace(/(\d+)/,parseInt(l)+1);n.value=a.substring(0,r)+`
`+u+a.substring(i,a.length),n.selectionStart=n.selectionEnd=r+u.length+1}}else{var d=o.match(/^\s*(?:[0-9]+\.|-)\s+$/);if(d){t.preventDefault();var f=d.shift().length;n.value=a.substring(0,r-f)+`
`+a.substring(i,a.length),n.selectionStart=n.selectionEnd=r-f}}}else alert(`Error: Browser version is too low`);e.d_value=n.value,n.focus()},t.getNavigation=function(e,t){var n=void 0;(n=e.$refs.navigationContent).innerHTML=e.d_render;var r=n.children;if(r.length)for(var i=0;i<r.length;i++)a(r[i],i,r);function a(t,n,r){/^H[1-6]{1}$/.exec(t.tagName)?t.onclick=function(){var t=e.$refs.vShowContent,r=e.$refs.vNoteEdit;e.s_subfield?e.s_preview_switch&&(r.scrollTop=t.children[n].offsetTop*(r.scrollHeight-r.offsetHeight)/(t.scrollHeight-t.offsetHeight)):e.s_preview_switch&&(t.scrollTop=t.children[n].offsetTop)}:t.style.display=`none`}},t.scrollLink=function(e,t){var n=e.srcElement?e.srcElement:e.target,r=n.scrollTop/(n.scrollHeight-n.offsetHeight);t.edit_scroll_height>=0&&n.scrollHeight!==t.edit_scroll_height&&n.scrollHeight-n.offsetHeight-n.scrollTop<=30&&(t.$refs.vNoteEdit.scrollTop=n.scrollHeight-n.offsetHeight,r=1),t.edit_scroll_height=n.scrollHeight,t.$refs.vShowContent.scrollHeight>t.$refs.vShowContent.offsetHeight&&(t.$refs.vShowContent.scrollTop=(t.$refs.vShowContent.scrollHeight-t.$refs.vShowContent.offsetHeight)*r)},t.fullscreenchange=function(e){e.$el.addEventListener(`fullscreenchange`,(function(t){e.$toolbar_right_read_change_status()}),!1),e.$el.addEventListener(`mozfullscreenchange`,(function(t){e.$toolbar_right_read_change_status()}),!1),e.$el.addEventListener(`webkitfullscreenchange`,(function(t){e.$toolbar_right_read_change_status()}),!1),e.$el.addEventListener(`msfullscreenchange`,(function(t){e.$toolbar_right_read_change_status()}),!1)},t.windowResize=function(e){function t(){e.s_subfield=e.$el.clientWidth>768&&e.subfield}t(),window.addEventListener(`resize`,t)},t.ImagePreviewListener=function(e){e.$refs.vShowContent.addEventListener(`click`,(function(t){var n=(t||=window.event).srcElement?t.srcElement:t.target;n.tagName===`IMG`&&(e.imageClick==null?e.d_preview_imgsrc=n.src:e.imageClick(n))}))}},function(e,t,n){var r=n(38).FilterCSS,i=n(38).getDefaultWhiteList,a=n(39);function o(){return{a:[`target`,`href`,`title`],abbr:[`title`],address:[],area:[`shape`,`coords`,`href`,`alt`],article:[],aside:[],audio:[`autoplay`,`controls`,`crossorigin`,`loop`,`muted`,`preload`,`src`],b:[],bdi:[`dir`],bdo:[`dir`],big:[],blockquote:[`cite`],br:[],caption:[],center:[],cite:[],code:[],col:[`align`,`valign`,`span`,`width`],colgroup:[`align`,`valign`,`span`,`width`],dd:[],del:[`datetime`],details:[`open`],div:[],dl:[],dt:[],em:[],figcaption:[],figure:[],font:[`color`,`size`,`face`],footer:[],h1:[],h2:[],h3:[],h4:[],h5:[],h6:[],header:[],hr:[],i:[],img:[`src`,`alt`,`title`,`width`,`height`,`loading`],ins:[`datetime`],kbd:[],li:[],mark:[],nav:[],ol:[],p:[],pre:[],s:[],section:[],small:[],span:[],sub:[],summary:[],sup:[],strong:[],strike:[],table:[`width`,`border`,`align`,`valign`],tbody:[`align`,`valign`],td:[`width`,`rowspan`,`colspan`,`align`,`valign`],tfoot:[`align`,`valign`],th:[`width`,`rowspan`,`colspan`,`align`,`valign`],thead:[`align`,`valign`],tr:[`rowspan`,`align`,`valign`],tt:[],u:[],ul:[],video:[`autoplay`,`controls`,`crossorigin`,`loop`,`muted`,`playsinline`,`poster`,`preload`,`src`,`height`,`width`]}}var s=new r;function c(e){return e.replace(l,`&lt;`).replace(u,`&gt;`)}var l=/</g,u=/>/g,d=/"/g,f=/&quot;/g,p=/&#([a-zA-Z0-9]*);?/gim,m=/&colon;?/gim,h=/&newline;?/gim,g=/((j\s*a\s*v\s*a|v\s*b|l\s*i\s*v\s*e)\s*s\s*c\s*r\s*i\s*p\s*t\s*|m\s*o\s*c\s*h\s*a):/gi,_=/e\s*x\s*p\s*r\s*e\s*s\s*s\s*i\s*o\s*n\s*\(.*/gi,v=/u\s*r\s*l\s*\(.*/gi;function y(e){return e.replace(d,`&quot;`)}function b(e){return e.replace(f,`"`)}function x(e){return e.replace(p,(function(e,t){return t[0]===`x`||t[0]===`X`?String.fromCharCode(parseInt(t.substr(1),16)):String.fromCharCode(parseInt(t,10))}))}function S(e){return e.replace(m,`:`).replace(h,` `)}function C(e){for(var t=``,n=0,r=e.length;n<r;n++)t+=e.charCodeAt(n)<32?` `:e.charAt(n);return a.trim(t)}function w(e){return e=C(e=S(e=x(e=b(e))))}function T(e){return e=c(e=y(e))}t.whiteList={a:[`target`,`href`,`title`],abbr:[`title`],address:[],area:[`shape`,`coords`,`href`,`alt`],article:[],aside:[],audio:[`autoplay`,`controls`,`crossorigin`,`loop`,`muted`,`preload`,`src`],b:[],bdi:[`dir`],bdo:[`dir`],big:[],blockquote:[`cite`],br:[],caption:[],center:[],cite:[],code:[],col:[`align`,`valign`,`span`,`width`],colgroup:[`align`,`valign`,`span`,`width`],dd:[],del:[`datetime`],details:[`open`],div:[],dl:[],dt:[],em:[],figcaption:[],figure:[],font:[`color`,`size`,`face`],footer:[],h1:[],h2:[],h3:[],h4:[],h5:[],h6:[],header:[],hr:[],i:[],img:[`src`,`alt`,`title`,`width`,`height`,`loading`],ins:[`datetime`],kbd:[],li:[],mark:[],nav:[],ol:[],p:[],pre:[],s:[],section:[],small:[],span:[],sub:[],summary:[],sup:[],strong:[],strike:[],table:[`width`,`border`,`align`,`valign`],tbody:[`align`,`valign`],td:[`width`,`rowspan`,`colspan`,`align`,`valign`],tfoot:[`align`,`valign`],th:[`width`,`rowspan`,`colspan`,`align`,`valign`],thead:[`align`,`valign`],tr:[`rowspan`,`align`,`valign`],tt:[],u:[],ul:[],video:[`autoplay`,`controls`,`crossorigin`,`loop`,`muted`,`playsinline`,`poster`,`preload`,`src`,`height`,`width`]},t.getDefaultWhiteList=o,t.onTag=function(e,t,n){},t.onIgnoreTag=function(e,t,n){},t.onTagAttr=function(e,t,n){},t.onIgnoreTagAttr=function(e,t,n){},t.safeAttrValue=function(e,t,n,r){if(n=w(n),t===`href`||t===`src`){if((n=a.trim(n))===`#`)return`#`;if(n.substr(0,7)!==`http://`&&n.substr(0,8)!==`https://`&&n.substr(0,7)!==`mailto:`&&n.substr(0,4)!==`tel:`&&n.substr(0,11)!==`data:image/`&&n.substr(0,6)!==`ftp://`&&n.substr(0,2)!==`./`&&n.substr(0,3)!==`../`&&n[0]!==`#`&&n[0]!==`/`)return``}else if(t===`background`){if(g.lastIndex=0,g.test(n))return``}else if(t===`style`){if(_.lastIndex=0,_.test(n)||(v.lastIndex=0,v.test(n)&&(g.lastIndex=0,g.test(n))))return``;!1!==r&&(n=(r||=s).process(n))}return n=T(n)},t.escapeHtml=c,t.escapeQuote=y,t.unescapeQuote=b,t.escapeHtmlEntities=x,t.escapeDangerHtml5Entities=S,t.clearNonPrintableCharacter=C,t.friendlyAttrValue=w,t.escapeAttrValue=T,t.onIgnoreTagStripAll=function(){return``},t.StripTagBody=function(e,t){typeof t!=`function`&&(t=function(){});var n=!Array.isArray(e),r=[],i=!1;return{onIgnoreTag:function(o,s,c){if(function(t){return!!n||a.indexOf(e,t)!==-1}(o)){if(c.isClosing){var l=`[/removed]`,u=c.position+l.length;return r.push([!1===i?c.position:i,u]),i=!1,l}return i||=c.position,`[removed]`}return t(o,s,c)},remove:function(e){var t=``,n=0;return a.forEach(r,(function(r){t+=e.slice(n,r[0]),n=r[1]})),t+=e.slice(n)}}},t.stripCommentTag=function(e){for(var t=``,n=0;n<e.length;){var r=e.indexOf(`<!--`,n);if(r===-1){t+=e.slice(n);break}t+=e.slice(n,r);var i=e.indexOf(`-->`,r);if(i===-1)break;n=i+3}return t},t.stripBlankChar=function(e){var t=e.split(``);return(t=t.filter((function(e){var t=e.charCodeAt(0);return t!==127&&(!(t<=31)||t===10||t===13)}))).join(``)},t.attributeWrapSign=`"`,t.cssFilter=s,t.getDefaultCSSWhiteList=i},function(e,t){function n(){return{"align-content":!1,"align-items":!1,"align-self":!1,"alignment-adjust":!1,"alignment-baseline":!1,all:!1,"anchor-point":!1,animation:!1,"animation-delay":!1,"animation-direction":!1,"animation-duration":!1,"animation-fill-mode":!1,"animation-iteration-count":!1,"animation-name":!1,"animation-play-state":!1,"animation-timing-function":!1,azimuth:!1,"backface-visibility":!1,background:!0,"background-attachment":!0,"background-clip":!0,"background-color":!0,"background-image":!0,"background-origin":!0,"background-position":!0,"background-repeat":!0,"background-size":!0,"baseline-shift":!1,binding:!1,bleed:!1,"bookmark-label":!1,"bookmark-level":!1,"bookmark-state":!1,border:!0,"border-bottom":!0,"border-bottom-color":!0,"border-bottom-left-radius":!0,"border-bottom-right-radius":!0,"border-bottom-style":!0,"border-bottom-width":!0,"border-collapse":!0,"border-color":!0,"border-image":!0,"border-image-outset":!0,"border-image-repeat":!0,"border-image-slice":!0,"border-image-source":!0,"border-image-width":!0,"border-left":!0,"border-left-color":!0,"border-left-style":!0,"border-left-width":!0,"border-radius":!0,"border-right":!0,"border-right-color":!0,"border-right-style":!0,"border-right-width":!0,"border-spacing":!0,"border-style":!0,"border-top":!0,"border-top-color":!0,"border-top-left-radius":!0,"border-top-right-radius":!0,"border-top-style":!0,"border-top-width":!0,"border-width":!0,bottom:!1,"box-decoration-break":!0,"box-shadow":!0,"box-sizing":!0,"box-snap":!0,"box-suppress":!0,"break-after":!0,"break-before":!0,"break-inside":!0,"caption-side":!1,chains:!1,clear:!0,clip:!1,"clip-path":!1,"clip-rule":!1,color:!0,"color-interpolation-filters":!0,"column-count":!1,"column-fill":!1,"column-gap":!1,"column-rule":!1,"column-rule-color":!1,"column-rule-style":!1,"column-rule-width":!1,"column-span":!1,"column-width":!1,columns:!1,contain:!1,content:!1,"counter-increment":!1,"counter-reset":!1,"counter-set":!1,crop:!1,cue:!1,"cue-after":!1,"cue-before":!1,cursor:!1,direction:!1,display:!0,"display-inside":!0,"display-list":!0,"display-outside":!0,"dominant-baseline":!1,elevation:!1,"empty-cells":!1,filter:!1,flex:!1,"flex-basis":!1,"flex-direction":!1,"flex-flow":!1,"flex-grow":!1,"flex-shrink":!1,"flex-wrap":!1,float:!1,"float-offset":!1,"flood-color":!1,"flood-opacity":!1,"flow-from":!1,"flow-into":!1,font:!0,"font-family":!0,"font-feature-settings":!0,"font-kerning":!0,"font-language-override":!0,"font-size":!0,"font-size-adjust":!0,"font-stretch":!0,"font-style":!0,"font-synthesis":!0,"font-variant":!0,"font-variant-alternates":!0,"font-variant-caps":!0,"font-variant-east-asian":!0,"font-variant-ligatures":!0,"font-variant-numeric":!0,"font-variant-position":!0,"font-weight":!0,grid:!1,"grid-area":!1,"grid-auto-columns":!1,"grid-auto-flow":!1,"grid-auto-rows":!1,"grid-column":!1,"grid-column-end":!1,"grid-column-start":!1,"grid-row":!1,"grid-row-end":!1,"grid-row-start":!1,"grid-template":!1,"grid-template-areas":!1,"grid-template-columns":!1,"grid-template-rows":!1,"hanging-punctuation":!1,height:!0,hyphens:!1,icon:!1,"image-orientation":!1,"image-resolution":!1,"ime-mode":!1,"initial-letters":!1,"inline-box-align":!1,"justify-content":!1,"justify-items":!1,"justify-self":!1,left:!1,"letter-spacing":!0,"lighting-color":!0,"line-box-contain":!1,"line-break":!1,"line-grid":!1,"line-height":!1,"line-snap":!1,"line-stacking":!1,"line-stacking-ruby":!1,"line-stacking-shift":!1,"line-stacking-strategy":!1,"list-style":!0,"list-style-image":!0,"list-style-position":!0,"list-style-type":!0,margin:!0,"margin-bottom":!0,"margin-left":!0,"margin-right":!0,"margin-top":!0,"marker-offset":!1,"marker-side":!1,marks:!1,mask:!1,"mask-box":!1,"mask-box-outset":!1,"mask-box-repeat":!1,"mask-box-slice":!1,"mask-box-source":!1,"mask-box-width":!1,"mask-clip":!1,"mask-image":!1,"mask-origin":!1,"mask-position":!1,"mask-repeat":!1,"mask-size":!1,"mask-source-type":!1,"mask-type":!1,"max-height":!0,"max-lines":!1,"max-width":!0,"min-height":!0,"min-width":!0,"move-to":!1,"nav-down":!1,"nav-index":!1,"nav-left":!1,"nav-right":!1,"nav-up":!1,"object-fit":!1,"object-position":!1,opacity:!1,order:!1,orphans:!1,outline:!1,"outline-color":!1,"outline-offset":!1,"outline-style":!1,"outline-width":!1,overflow:!1,"overflow-wrap":!1,"overflow-x":!1,"overflow-y":!1,padding:!0,"padding-bottom":!0,"padding-left":!0,"padding-right":!0,"padding-top":!0,page:!1,"page-break-after":!1,"page-break-before":!1,"page-break-inside":!1,"page-policy":!1,pause:!1,"pause-after":!1,"pause-before":!1,perspective:!1,"perspective-origin":!1,pitch:!1,"pitch-range":!1,"play-during":!1,position:!1,"presentation-level":!1,quotes:!1,"region-fragment":!1,resize:!1,rest:!1,"rest-after":!1,"rest-before":!1,richness:!1,right:!1,rotation:!1,"rotation-point":!1,"ruby-align":!1,"ruby-merge":!1,"ruby-position":!1,"shape-image-threshold":!1,"shape-outside":!1,"shape-margin":!1,size:!1,speak:!1,"speak-as":!1,"speak-header":!1,"speak-numeral":!1,"speak-punctuation":!1,"speech-rate":!1,stress:!1,"string-set":!1,"tab-size":!1,"table-layout":!1,"text-align":!0,"text-align-last":!0,"text-combine-upright":!0,"text-decoration":!0,"text-decoration-color":!0,"text-decoration-line":!0,"text-decoration-skip":!0,"text-decoration-style":!0,"text-emphasis":!0,"text-emphasis-color":!0,"text-emphasis-position":!0,"text-emphasis-style":!0,"text-height":!0,"text-indent":!0,"text-justify":!0,"text-orientation":!0,"text-overflow":!0,"text-shadow":!0,"text-space-collapse":!0,"text-transform":!0,"text-underline-position":!0,"text-wrap":!0,top:!1,transform:!1,"transform-origin":!1,"transform-style":!1,transition:!1,"transition-delay":!1,"transition-duration":!1,"transition-property":!1,"transition-timing-function":!1,"unicode-bidi":!1,"vertical-align":!1,visibility:!1,"voice-balance":!1,"voice-duration":!1,"voice-family":!1,"voice-pitch":!1,"voice-range":!1,"voice-rate":!1,"voice-stress":!1,"voice-volume":!1,volume:!1,"white-space":!1,widows:!1,width:!0,"will-change":!1,"word-break":!0,"word-spacing":!0,"word-wrap":!0,"wrap-flow":!1,"wrap-through":!1,"writing-mode":!1,"z-index":!1}}var r=/javascript\s*\:/gim;t.whiteList=n(),t.getDefaultWhiteList=n,t.onAttr=function(e,t,n){},t.onIgnoreAttr=function(e,t,n){},t.safeAttrValue=function(e,t){return r.test(t)?``:t}},function(e,t){e.exports={indexOf:function(e,t){var n,r;if(Array.prototype.indexOf)return e.indexOf(t);for(n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1},forEach:function(e,t,n){var r,i;if(Array.prototype.forEach)return e.forEach(t,n);for(r=0,i=e.length;r<i;r++)t.call(n,e[r],r,e)},trim:function(e){return String.prototype.trim?e.trim():e.replace(/(^\s*)|(\s*$)/g,``)},trimRight:function(e){return String.prototype.trimRight?e.trimRight():e.replace(/(\s*$)/g,``)}}},function(e,t,n){var r=n(39);function i(e){var t,n=r.spaceIndex(e);return t=n===-1?e.slice(1,-1):e.slice(1,n+1),(t=r.trim(t).toLowerCase()).slice(0,1)===`/`&&(t=t.slice(1)),t.slice(-1)===`/`&&(t=t.slice(0,-1)),t}function a(e){return e.slice(0,2)===`</`}var o=/[^a-zA-Z0-9\\_:.-]/gim;function s(e,t){for(;t<e.length;t++){var n=e[t];if(n!==` `)return n===`=`?t:-1}}function c(e,t){for(;t<e.length;t++){var n=e[t];if(n!==` `)return n===`'`||n===`"`?t:-1}}function l(e,t){for(;t>0;t--){var n=e[t];if(n!==` `)return n===`=`?t:-1}}function u(e){return function(e){return e[0]===`"`&&e[e.length-1]===`"`||e[0]===`'`&&e[e.length-1]===`'`}(e)?e.substr(1,e.length-2):e}t.parseTag=function(e,t,n){var r=``,o=0,s=!1,c=!1,l=0,u=e.length,d=``,f=``;e:for(l=0;l<u;l++){var p=e.charAt(l);if(!1===s){if(p===`<`){s=l;continue}}else if(!1===c){if(p===`<`){r+=n(e.slice(o,l)),s=l,o=l;continue}if(p===`>`||l===u-1){r+=n(e.slice(o,s)),d=i(f=e.slice(s,l+1)),r+=t(s,r.length,d,f,a(f)),o=l+1,s=!1;continue}if(p===`"`||p===`'`)for(var m=1,h=e.charAt(l-m);h.trim()===``||h===`=`;){if(h===`=`){c=p;continue e}h=e.charAt(l-++m)}}else if(p===c){c=!1;continue}}return o<u&&(r+=n(e.substr(o))),r},t.parseAttr=function(e,t){var n=0,i=0,a=[],d=!1,f=e.length;function p(e,n){if(!((e=(e=r.trim(e)).replace(o,``).toLowerCase()).length<1)){var i=t(e,n||``);i&&a.push(i)}}for(var m=0;m<f;m++){var h,g=e.charAt(m);if(!1!==d||g!==`=`){if(!1===d||m!==i){if(/\s|\n|\t/.test(g)){if(e=e.replace(/\s|\n|\t/g,` `),!1===d){if((h=s(e,m))===-1){p(r.trim(e.slice(n,m))),d=!1,n=m+1;continue}m=h-1;continue}if((h=l(e,m-1))===-1){p(d,u(r.trim(e.slice(n,m)))),d=!1,n=m+1;continue}}}else{if((h=e.indexOf(g,m+1))===-1)break;p(d,r.trim(e.slice(i+1,h))),d=!1,n=(m=h)+1}}else d=e.slice(n,m),n=m+1,i=e.charAt(n)===`"`||e.charAt(n)===`'`?n:c(e,m+1)}return n<e.length&&(!1===d?p(e.slice(n)):p(d,u(r.trim(e.slice(n))))),r.trim(a.join(` `))}},function(e,t,n){e.exports=n(142)},function(e,t,n){e.exports.encode=n(143),e.exports.decode=n(144),e.exports.format=n(145),e.exports.parse=n(146)},function(e,t){e.exports=/[\0-\uD7FF\uE000-\uFFFF]|[\uD800-\uDBFF][\uDC00-\uDFFF]|[\uD800-\uDBFF](?![\uDC00-\uDFFF])|(?:[^\uD800-\uDBFF]|^)[\uDC00-\uDFFF]/},function(e,t){e.exports=/[\0-\x1F\x7F-\x9F]/},function(e,t){e.exports=/[ \xA0\u1680\u2000-\u200A\u2028\u2029\u202F\u205F\u3000]/},function(e,t,n){var r=`<[A-Za-z][A-Za-z0-9\\-]*(?:\\s+[a-zA-Z_:][a-zA-Z0-9:._-]*(?:\\s*=\\s*(?:[^"'=<>\`\\x00-\\x20]+|'[^']*'|"[^"]*"))?)*\\s*\\/?>`,i=`<\\/[A-Za-z][A-Za-z0-9\\-]*\\s*>`,a=RegExp(`^(?:`+r+`|`+i+`|<!---->|<!--(?:-?[^>-])(?:-?[^-])*-->|<[?].*?[?]>|<![A-Z]+\\s+[^>]*>|<!\\[CDATA\\[[\\s\\S]*?\\]\\]>)`),o=RegExp(`^(?:`+r+`|`+i+`)`);e.exports.HTML_TAG_RE=a,e.exports.HTML_OPEN_CLOSE_TAG_RE=o},function(e,t,n){function r(e,t){var n,r,i,a,o,s=[],c=t.length;for(n=0;n<c;n++)(i=t[n]).marker===126&&i.end!==-1&&(a=t[i.end],(o=e.tokens[i.token]).type=`s_open`,o.tag=`s`,o.nesting=1,o.markup=`~~`,o.content=``,(o=e.tokens[a.token]).type=`s_close`,o.tag=`s`,o.nesting=-1,o.markup=`~~`,o.content=``,e.tokens[a.token-1].type===`text`&&e.tokens[a.token-1].content===`~`&&s.push(a.token-1));for(;s.length;){for(r=(n=s.pop())+1;r<e.tokens.length&&e.tokens[r].type===`s_close`;)r++;n!==--r&&(o=e.tokens[r],e.tokens[r]=e.tokens[n],e.tokens[n]=o)}}e.exports.tokenize=function(e,t){var n,r,i,a,o=e.pos,s=e.src.charCodeAt(o);if(t||s!==126||(i=(r=e.scanDelims(e.pos,!0)).length,a=String.fromCharCode(s),i<2))return!1;for(i%2&&(e.push(`text`,``,0).content=a,i--),n=0;n<i;n+=2)e.push(`text`,``,0).content=a+a,e.delimiters.push({marker:s,length:0,jump:n,token:e.tokens.length-1,end:-1,open:r.can_open,close:r.can_close});return e.pos+=r.length,!0},e.exports.postProcess=function(e){var t,n=e.tokens_meta,i=e.tokens_meta.length;for(r(e,e.delimiters),t=0;t<i;t++)n[t]&&n[t].delimiters&&r(e,n[t].delimiters)}},function(e,t,n){function r(e,t){for(var n=t.length-1,r,i,a,o,s;n>=0;n--)(r=t[n]).marker!==95&&r.marker!==42||r.end!==-1&&(i=t[r.end],s=n>0&&t[n-1].end===r.end+1&&t[n-1].token===r.token-1&&t[r.end+1].token===i.token+1&&t[n-1].marker===r.marker,o=String.fromCharCode(r.marker),(a=e.tokens[r.token]).type=s?`strong_open`:`em_open`,a.tag=s?`strong`:`em`,a.nesting=1,a.markup=s?o+o:o,a.content=``,(a=e.tokens[i.token]).type=s?`strong_close`:`em_close`,a.tag=s?`strong`:`em`,a.nesting=-1,a.markup=s?o+o:o,a.content=``,s&&(e.tokens[t[n-1].token].content=``,e.tokens[t[r.end+1].token].content=``,n--))}e.exports.tokenize=function(e,t){var n,r,i=e.pos,a=e.src.charCodeAt(i);if(t||a!==95&&a!==42)return!1;for(r=e.scanDelims(e.pos,a===42),n=0;n<r.length;n++)e.push(`text`,``,0).content=String.fromCharCode(a),e.delimiters.push({marker:a,length:r.length,jump:n,token:e.tokens.length-1,end:-1,open:r.can_open,close:r.can_close});return e.pos+=r.length,!0},e.exports.postProcess=function(e){var t,n=e.tokens_meta,i=e.tokens_meta.length;for(r(e,e.delimiters),t=0;t<i;t++)n[t]&&n[t].delimiters&&r(e,n[t].delimiters)}},function(e,t,n){n.r(t);var r=n(76),i=n(22);for(var a in i)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return i[e]}))}(a);n(216);var o=n(3);t.default=n.n(o)()(i.default,[[`render`,r.a],[`__scopeId`,`data-v-218640b4`]])},function(e,t,n){var r=n(217);r.__esModule&&(r=r.default),typeof r==`string`&&(r=[[e.i,r,``]]),r.locals&&(e.exports=r.locals),(0,n(44).default)(`74ee9320`,r,!1,{})},function(e,t,n){n.r(t);var r=n(77),i=n(23);for(var a in i)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return i[e]}))}(a);var o=n(3);t.default=n.n(o)()(i.default,[[`render`,r.a]])},function(e,t,n){var r=n(220);r.__esModule&&(r=r.default),typeof r==`string`&&(r=[[e.i,r,``]]),r.locals&&(e.exports=r.locals),(0,n(44).default)(`4958985a`,r,!1,{})},function(e,t,n){var r=n(222);r.__esModule&&(r=r.default),typeof r==`string`&&(r=[[e.i,r,``]]),r.locals&&(e.exports=r.locals),(0,n(44).default)(`200b97d3`,r,!1,{})},function(e,t,n){n.d(t,`a`,(function(){return d}));var r=n(0);let i={class:`v-note-panel`},a=[`innerHTML`],o={class:`v-note-navigation-title`},s={ref:`help`},c=[`innerHTML`],l=[`src`],u=[`innerHTML`];function d(e,t,n,d,f,p){let m=(0,r.resolveComponent)(`v-md-toolbar-left`),h=(0,r.resolveComponent)(`v-md-toolbar-right`),g=(0,r.resolveComponent)(`v-autoTextarea`);return(0,r.openBlock)(),(0,r.createElementBlock)(`div`,{class:(0,r.normalizeClass)([[{fullscreen:f.s_fullScreen,shadow:n.boxShadow}],`v-note-wrapper markdown-body`]),style:(0,r.normalizeStyle)({"box-shadow":n.boxShadow?n.boxShadowStyle:``})},[(0,r.createCommentVNode)(`工具栏`),(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:`v-note-op`,style:(0,r.normalizeStyle)({background:n.toolbarsBackground})},[(0,r.createVNode)(m,{ref:`toolbar_left`,editable:n.editable,transition:n.transition,d_words:f.d_words,onToolbar_left_click:p.toolbar_left_click,onToolbar_left_addlink:p.toolbar_left_addlink,toolbars:n.toolbars,onImgAdd:p.$imgAdd,onImgDel:p.$imgDel,onImgTouch:p.$imgTouch,image_filter:n.imageFilter,class:(0,r.normalizeClass)({transition:n.transition})},{"left-toolbar-before":(0,r.withCtx)(()=>[(0,r.renderSlot)(e.$slots,`left-toolbar-before`,{},void 0,!0)]),"left-toolbar-after":(0,r.withCtx)(()=>[(0,r.renderSlot)(e.$slots,`left-toolbar-after`,{},void 0,!0)]),_:3},8,[`editable`,`transition`,`d_words`,`onToolbar_left_click`,`onToolbar_left_addlink`,`toolbars`,`onImgAdd`,`onImgDel`,`onImgTouch`,`image_filter`,`class`]),(0,r.createVNode)(h,{ref:`toolbar_right`,d_words:f.d_words,onToolbar_right_click:p.toolbar_right_click,toolbars:n.toolbars,s_subfield:f.s_subfield,s_preview_switch:f.s_preview_switch,s_fullScreen:f.s_fullScreen,s_html_code:f.s_html_code,s_navigation:f.s_navigation,class:(0,r.normalizeClass)({transition:n.transition})},{"right-toolbar-before":(0,r.withCtx)(()=>[(0,r.renderSlot)(e.$slots,`right-toolbar-before`,{},void 0,!0)]),"right-toolbar-after":(0,r.withCtx)(()=>[(0,r.renderSlot)(e.$slots,`right-toolbar-after`,{},void 0,!0)]),_:3},8,[`d_words`,`onToolbar_right_click`,`toolbars`,`s_subfield`,`s_preview_switch`,`s_fullScreen`,`s_html_code`,`s_navigation`,`class`])],4),[[r.vShow,n.toolbarsFlag]]),(0,r.createCommentVNode)(`编辑展示区域`),(0,r.createElementVNode)(`div`,i,[(0,r.createCommentVNode)(`编辑区`),(0,r.createElementVNode)(`div`,{ref:`vNoteEdit`,onScroll:t[1]||=(...e)=>p.$v_edit_scroll&&p.$v_edit_scroll(...e),class:(0,r.normalizeClass)([`v-note-edit divarea-wrapper`,{"scroll-style":f.s_scrollStyle,"scroll-style-border-radius":f.s_scrollStyle&&!f.s_preview_switch&&!f.s_html_code,"single-edit":!f.s_preview_switch&&!f.s_html_code,"single-show":!f.s_subfield&&f.s_preview_switch||!f.s_subfield&&f.s_html_code,transition:n.transition}]),onClick:t[2]||=(...e)=>p.textAreaFocus&&p.textAreaFocus(...e)},[(0,r.createElementVNode)(`div`,{class:`content-input-wrapper`,style:(0,r.normalizeStyle)({"background-color":n.editorBackground})},[(0,r.createCommentVNode)(` 双栏 `),(0,r.createVNode)(g,{ref:`vNoteTextarea`,placeholder:n.placeholder?n.placeholder:f.d_words.start_editor,class:`content-input`,fontSize:n.fontSize,lineHeight:`1.5`,modelValue:f.d_value,"onUpdate:modelValue":t[0]||=e=>f.d_value=e,fullHeight:``,style:(0,r.normalizeStyle)({"background-color":n.editorBackground})},null,8,[`placeholder`,`fontSize`,`modelValue`,`style`])],4)],34),(0,r.createCommentVNode)(`展示区`),(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([{"single-show":!f.s_subfield&&f.s_preview_switch||!f.s_subfield&&f.s_html_code},`v-note-show`])},[(0,r.withDirectives)((0,r.createElementVNode)(`div`,{ref:`vShowContent`,innerHTML:f.d_render,class:(0,r.normalizeClass)([{"scroll-style":f.s_scrollStyle,"scroll-style-border-radius":f.s_scrollStyle},`v-show-content`]),style:(0,r.normalizeStyle)({"background-color":n.previewBackground})},null,14,a),[[r.vShow,!f.s_html_code]]),(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([{"scroll-style":f.s_scrollStyle,"scroll-style-border-radius":f.s_scrollStyle},`v-show-content-html`]),style:(0,r.normalizeStyle)({"background-color":n.previewBackground})},(0,r.toDisplayString)(f.d_render),7),[[r.vShow,f.s_html_code]])],2),[[r.vShow,f.s_preview_switch||f.s_html_code]]),(0,r.createCommentVNode)(`标题导航`),(0,r.createVNode)(r.Transition,{name:`slideTop`,persisted:``},{default:(0,r.withCtx)(()=>[(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([`v-note-navigation-wrapper`,{transition:n.transition}])},[(0,r.createElementVNode)(`div`,o,[(0,r.createTextVNode)((0,r.toDisplayString)(f.d_words.navigation_title),1),(0,r.createElementVNode)(`i`,{onClick:t[3]||=e=>p.toolbar_right_click(`navigation`),class:`fa fa-mavon-times v-note-navigation-close`,"aria-hidden":`true`})]),(0,r.createElementVNode)(`div`,{ref:`navigationContent`,class:(0,r.normalizeClass)([`v-note-navigation-content`,{"scroll-style":f.s_scrollStyle}])},null,2)],2),[[r.vShow,f.s_navigation]])]),_:1})]),(0,r.createCommentVNode)(`帮助文档`),(0,r.createVNode)(r.Transition,{name:`fade`},{default:(0,r.withCtx)(()=>[(0,r.createElementVNode)(`div`,s,[f.s_help?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,{key:0,onClick:t[5]||=(0,r.withModifiers)(e=>p.toolbar_right_click(`help`),[`self`]),class:`v-note-help-wrapper`},[(0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([`v-note-help-content markdown-body`,{shadow:n.boxShadow}])},[(0,r.createElementVNode)(`i`,{onClick:t[4]||=(0,r.withModifiers)(e=>p.toolbar_right_click(`help`),[`stop`,`prevent`]),class:`fa fa-mavon-times`,"aria-hidden":`true`}),(0,r.createElementVNode)(`div`,{class:`scroll-style v-note-help-show`,innerHTML:f.d_help},null,8,c)],2)])):(0,r.createCommentVNode)(`v-if`,!0)],512)]),_:1}),(0,r.createCommentVNode)(` 预览图片 `),(0,r.createVNode)(r.Transition,{name:`fade`},{default:(0,r.withCtx)(()=>[f.d_preview_imgsrc?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,{key:0,onClick:t[6]||=e=>f.d_preview_imgsrc=null,class:`v-note-img-wrapper`},[(0,r.createElementVNode)(`img`,{src:f.d_preview_imgsrc,alt:`none`},null,8,l)])):(0,r.createCommentVNode)(`v-if`,!0)]),_:1}),(0,r.createCommentVNode)(`阅读模式`),(0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([{show:f.s_readmodel},`v-note-read-model scroll-style`]),ref:`vReadModel`},[(0,r.createElementVNode)(`div`,{ref:`vNoteReadContent`,class:`v-note-read-content`,innerHTML:f.d_render},null,8,u)],2)],6)}},function(e,t,n){n.d(t,`a`,(function(){return H}));var r=n(0);let i={class:`v-left-item`},a=[`disabled`,`title`],o=[`disabled`,`title`],s=[`disabled`,`title`],c={key:3,class:`op-icon-divider`},l=[`disabled`,`title`],u=[`disabled`,`title`],d=[`disabled`,`title`],f=[`disabled`,`title`],p=[`disabled`,`title`],m=[`disabled`,`title`],h=[`disabled`,`title`],g=[`disabled`,`title`],_={key:12,class:`op-icon-divider`},v=[`disabled`,`title`],y=[`disabled`,`title`],b=[`disabled`,`title`],x={key:16,class:`op-icon-divider`},S=[`disabled`,`title`],C=[`disabled`],w={class:`dropdown-item`,style:{overflow:`hidden`}},T=[`title`,`onClick`],E=[`onClick`,`title`],D=[`src`],O=[`disabled`,`title`],k=[`disabled`,`title`],A={key:21,class:`op-icon-divider`},j=[`title`],M=[`title`],N=[`title`],P=[`title`],F={key:0,class:`add-image-link-wrapper`},I={class:`add-image-link`},L={class:`title`},R={class:`link-text input-wrapper`},z=[`placeholder`],B={class:`link-addr input-wrapper`},V=[`placeholder`];function H(e,t,n,H,U,W){return(0,r.openBlock)(),(0,r.createElementBlock)(`div`,i,[(0,r.renderSlot)(e.$slots,`left-toolbar-before`,{},void 0,!0),n.toolbars.bold?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:0,disabled:!n.editable,type:`button`,onClick:t[0]||=e=>W.$clicks(`bold`),class:`op-icon fa fa-mavon-bold`,"aria-hidden":`true`,title:n.d_words.tl_bold+` (ctrl+b)`},null,8,a)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.italic?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:1,disabled:!n.editable,type:`button`,onClick:t[1]||=e=>W.$clicks(`italic`),class:`op-icon fa fa-mavon-italic`,"aria-hidden":`true`,title:n.d_words.tl_italic+` (ctrl+i)`},null,8,o)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.header?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,{key:2,class:(0,r.normalizeClass)([{selected:U.s_header_dropdown_open},`op-icon fa fa-mavon-header dropdown dropdown-wrapper`]),disabled:!n.editable,type:`button`,onMouseleave:t[10]||=(...e)=>W.$mouseleave_header_dropdown&&W.$mouseleave_header_dropdown(...e),onMouseenter:t[11]||=(...e)=>W.$mouseenter_header_dropdown&&W.$mouseenter_header_dropdown(...e),"aria-hidden":`true`,title:n.d_words.tl_header+` (ctrl+h)`},[(0,r.createVNode)(r.Transition,{name:`fade`,persisted:``},{default:(0,r.withCtx)(()=>[(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([`op-header popup-dropdown`,{transition:n.transition}]),onMouseenter:t[8]||=(...e)=>W.$mouseenter_header_dropdown&&W.$mouseenter_header_dropdown(...e),onMouseleave:t[9]||=(...e)=>W.$mouseleave_header_dropdown&&W.$mouseleave_header_dropdown(...e)},[(0,r.createElementVNode)(`div`,{title:`#`,class:`dropdown-item`,onClick:t[2]||=(0,r.withModifiers)(e=>W.$click_header(`header1`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_one),1)]),(0,r.createElementVNode)(`div`,{title:`## `,class:`dropdown-item`,onClick:t[3]||=(0,r.withModifiers)(e=>W.$click_header(`header2`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_two),1)]),(0,r.createElementVNode)(`div`,{title:`### `,class:`dropdown-item`,onClick:t[4]||=(0,r.withModifiers)(e=>W.$click_header(`header3`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_three),1)]),(0,r.createElementVNode)(`div`,{title:`#### `,class:`dropdown-item`,onClick:t[5]||=(0,r.withModifiers)(e=>W.$click_header(`header4`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_four),1)]),(0,r.createElementVNode)(`div`,{title:`##### `,class:`dropdown-item`,onClick:t[6]||=(0,r.withModifiers)(e=>W.$click_header(`header5`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_five),1)]),(0,r.createElementVNode)(`div`,{title:`###### `,class:`dropdown-item`,onClick:t[7]||=(0,r.withModifiers)(e=>W.$click_header(`header6`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_header_six),1)])],34),[[r.vShow,U.s_header_dropdown_open]])]),_:1})],42,s)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.header||n.toolbars.italic||n.toolbars.bold?((0,r.openBlock)(),(0,r.createElementBlock)(`span`,c)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.underline?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:4,disabled:!n.editable,type:`button`,onClick:t[12]||=e=>W.$clicks(`underline`),class:`op-icon fa fa-mavon-underline`,title:n.d_words.tl_underline+` (ctrl+u)`,"aria-hidden":`true`},null,8,l)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.strikethrough?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:5,disabled:!n.editable,type:`button`,onClick:t[13]||=e=>W.$clicks(`strikethrough`),class:`op-icon fa fa-mavon-strikethrough`,title:n.d_words.tl_strikethrough+` (ctrl+shift+d)`,"aria-hidden":`true`},null,8,u)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.mark?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:6,disabled:!n.editable,type:`button`,onClick:t[14]||=e=>W.$clicks(`mark`),class:`op-icon fa fa-mavon-thumb-tack`,title:n.d_words.tl_mark+` (ctrl+m)`,"aria-hidden":`true`},null,8,d)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.superscript?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:7,disabled:!n.editable,type:`button`,onClick:t[15]||=e=>W.$clicks(`superscript`),class:`op-icon fa fa-mavon-superscript`,"aria-hidden":`true`,title:n.d_words.tl_superscript+` (ctrl+alt+s)`},null,8,f)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.subscript?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:8,disabled:!n.editable,type:`button`,onClick:t[16]||=e=>W.$clicks(`subscript`),class:`op-icon fa fa-mavon-subscript`,"aria-hidden":`true`,title:n.d_words.tl_subscript+` (ctrl+shift+s)`},null,8,p)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.alignleft?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:9,disabled:!n.editable,type:`button`,onClick:t[17]||=e=>W.$clicks(`alignleft`),class:`op-icon fa fa-mavon-align-left`,"aria-hidden":`true`,title:n.d_words.tl_alignleft+` (ctrl+l)`},null,8,m)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.aligncenter?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:10,disabled:!n.editable,type:`button`,onClick:t[18]||=e=>W.$clicks(`aligncenter`),class:`op-icon fa fa-mavon-align-center`,"aria-hidden":`true`,title:n.d_words.tl_aligncenter+` (ctrl+e)`},null,8,h)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.alignright?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:11,disabled:!n.editable,type:`button`,onClick:t[19]||=e=>W.$clicks(`alignright`),class:`op-icon fa fa-mavon-align-right`,"aria-hidden":`true`,title:n.d_words.tl_alignright+` (ctrl+r)`},null,8,g)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.superscript||n.toolbars.subscript||n.toolbars.underline||n.toolbars.strikethrough||n.toolbars.mark?((0,r.openBlock)(),(0,r.createElementBlock)(`span`,_)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.quote?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:13,disabled:!n.editable,type:`button`,onClick:t[20]||=e=>W.$clicks(`quote`),class:`op-icon fa fa-mavon-quote-left`,"aria-hidden":`true`,title:n.d_words.tl_quote+` (ctrl+q)`},null,8,v)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.ol?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:14,disabled:!n.editable,type:`button`,onClick:t[21]||=e=>W.$clicks(`ol`),class:`op-icon fa fa-mavon-list-ol`,"aria-hidden":`true`,title:n.d_words.tl_ol+` (ctrl+o)`},null,8,y)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.ul?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:15,disabled:!n.editable,type:`button`,onClick:t[22]||=e=>W.$clicks(`ul`),class:`op-icon fa fa-mavon-list-ul`,"aria-hidden":`true`,title:n.d_words.tl_ul+` (ctrl+alt+u)`},null,8,b)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.ul||n.toolbars.ol||n.toolbars.quote?((0,r.openBlock)(),(0,r.createElementBlock)(`span`,x)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.link?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:17,disabled:!n.editable,type:`button`,onClick:t[23]||=(0,r.withModifiers)(e=>W.$toggle_imgLinkAdd(`link`),[`stop`]),class:`op-icon fa fa-mavon-link`,"aria-hidden":`true`,title:n.d_words.tl_link+` (ctrl+l)`},null,8,S)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.imagelink?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,{key:18,disabled:!n.editable,class:(0,r.normalizeClass)([{selected:U.s_img_dropdown_open},`op-icon fa fa-mavon-picture-o dropdown dropdown-wrapper`]),type:`button`,onMouseleave:t[28]||=(...e)=>W.$mouseleave_img_dropdown&&W.$mouseleave_img_dropdown(...e),onMouseenter:t[29]||=(...e)=>W.$mouseenter_img_dropdown&&W.$mouseenter_img_dropdown(...e),"aria-hidden":`true`},[(0,r.createVNode)(r.Transition,{name:`fade`,persisted:``},{default:(0,r.withCtx)(()=>[(0,r.withDirectives)((0,r.createElementVNode)(`div`,{class:(0,r.normalizeClass)([`op-image popup-dropdown`,{transition:n.transition}]),onMouseleave:t[26]||=(...e)=>W.$mouseleave_img_dropdown&&W.$mouseleave_img_dropdown(...e),onMouseenter:t[27]||=(...e)=>W.$mouseenter_img_dropdown&&W.$mouseenter_img_dropdown(...e)},[(0,r.createElementVNode)(`div`,{class:`dropdown-item`,onClick:t[24]||=(0,r.withModifiers)(e=>W.$toggle_imgLinkAdd(`imagelink`),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(n.d_words.tl_image),1)]),(0,r.createElementVNode)(`div`,w,[(0,r.createElementVNode)(`input`,{type:`file`,accept:`image/gif,image/jpeg,image/jpg,image/png,image/svg`,onChange:t[25]||=e=>W.$imgAdd(e),multiple:`multiple`},null,32),(0,r.createTextVNode)((0,r.toDisplayString)(n.d_words.tl_upload),1)]),((0,r.openBlock)(!0),(0,r.createElementBlock)(r.Fragment,null,(0,r.renderList)(U.img_file,(e,t)=>((0,r.openBlock)(),(0,r.createElementBlock)(r.Fragment,{key:t},[e&&e[1]?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,{key:0,class:`dropdown-item dropdown-images`,title:e[1].name,onClick:(0,r.withModifiers)(e=>W.$imgFileListClick(t),[`stop`])},[(0,r.createElementVNode)(`span`,null,(0,r.toDisplayString)(e[1].name),1),(0,r.createElementVNode)(`button`,{type:`button`,onClick:(0,r.withModifiers)(e=>W.$imgDel(t),[`stop`]),class:`op-icon fa fa-mavon-times`,"aria-hidden":`true`,title:n.d_words.tl_upload_remove},null,8,E),(0,r.createCommentVNode)(` 缩略图展示 `),(0,r.createElementVNode)(`img`,{class:(0,r.normalizeClass)([`image-show`,{transition:n.transition}]),src:e[1].miniurl,alt:`none`},null,10,D)],8,T)):(0,r.createCommentVNode)(`v-if`,!0)],64))),128))],34),[[r.vShow,U.s_img_dropdown_open]])]),_:1})],42,C)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.code?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:19,disabled:!n.editable,type:`button`,onClick:t[30]||=e=>W.$clicks(`code`),class:`op-icon fa fa-mavon-code`,"aria-hidden":`true`,title:n.d_words.tl_code+` (ctrl+alt+c)`},null,8,O)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.table?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:20,disabled:!n.editable,type:`button`,onClick:t[31]||=e=>W.$clicks(`table`),class:`op-icon fa fa-mavon-table`,"aria-hidden":`true`,title:n.d_words.tl_table+` (ctrl+alt+t)`},null,8,k)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.link||n.toolbars.imagelink||n.toolbars.code||n.toolbars.table?((0,r.openBlock)(),(0,r.createElementBlock)(`span`,A)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.undo?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:22,type:`button`,onClick:t[32]||=e=>W.$clicks(`undo`),class:`op-icon fa fa-mavon-undo`,"aria-hidden":`true`,title:n.d_words.tl_undo+` (ctrl+z)`},null,8,j)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.redo?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:23,type:`button`,onClick:t[33]||=e=>W.$clicks(`redo`),class:`op-icon fa fa-mavon-repeat`,"aria-hidden":`true`,title:n.d_words.tl_redo+` (ctrl+y)`},null,8,M)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.trash?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:24,type:`button`,onClick:t[34]||=e=>W.$clicks(`trash`),class:`op-icon fa fa-mavon-trash-o`,"aria-hidden":`true`,title:n.d_words.tl_trash+` (ctrl+breakspace)`},null,8,N)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.save?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:25,type:`button`,onClick:t[35]||=e=>W.$clicks(`save`),class:`op-icon fa fa-mavon-floppy-o`,"aria-hidden":`true`,title:n.d_words.tl_save+` (ctrl+s)`},null,8,P)):(0,r.createCommentVNode)(`v-if`,!0),(0,r.renderSlot)(e.$slots,`left-toolbar-after`,{},void 0,!0),(0,r.createCommentVNode)(` 添加image链接 `),(0,r.createVNode)(r.Transition,{name:`fade`},{default:(0,r.withCtx)(()=>[U.s_img_link_open?((0,r.openBlock)(),(0,r.createElementBlock)(`div`,F,[(0,r.createElementVNode)(`div`,I,[(0,r.createElementVNode)(`i`,{onClick:t[36]||=(0,r.withModifiers)(e=>U.s_img_link_open=!1,[`stop`,`prevent`]),class:`fa fa-mavon-times`,"aria-hidden":`true`}),(0,r.createElementVNode)(`h3`,L,(0,r.toDisplayString)(U.link_type==`link`?n.d_words.tl_popup_link_title:n.d_words.tl_popup_img_link_title),1),(0,r.createElementVNode)(`div`,R,[(0,r.withDirectives)((0,r.createElementVNode)(`input`,{ref:`linkTextInput`,type:`text`,"onUpdate:modelValue":t[37]||=e=>U.link_text=e,placeholder:U.link_type==`link`?n.d_words.tl_popup_link_text:n.d_words.tl_popup_img_link_text},null,8,z),[[r.vModelText,U.link_text]])]),(0,r.createElementVNode)(`div`,B,[(0,r.withDirectives)((0,r.createElementVNode)(`input`,{type:`text`,"onUpdate:modelValue":t[38]||=e=>U.link_addr=e,placeholder:U.link_type==`link`?n.d_words.tl_popup_link_addr:n.d_words.tl_popup_img_link_addr},null,8,V),[[r.vModelText,U.link_addr]])]),(0,r.createElementVNode)(`div`,{class:`op-btn cancel`,onClick:t[39]||=(0,r.withModifiers)(e=>U.s_img_link_open=!1,[`stop`])},(0,r.toDisplayString)(n.d_words.tl_popup_link_cancel),1),(0,r.createElementVNode)(`div`,{class:`op-btn sure`,onClick:t[40]||=(0,r.withModifiers)(e=>W.$imgLinkAdd(),[`stop`])},(0,r.toDisplayString)(n.d_words.tl_popup_link_sure),1)])])):(0,r.createCommentVNode)(`v-if`,!0)]),_:1})])}},function(e,t,n){n.d(t,`a`,(function(){return _}));var r=n(0);let i={class:`v-right-item`},a=[`title`],o=[`title`],s=[`title`],c=[`title`],l=[`title`],u=[`title`],d=[`title`],f=[`title`],p={key:8,class:`op-icon-divider`},m=[`title`],h=[`title`],g=[`title`];function _(e,t,n,_,v,y){return(0,r.openBlock)(),(0,r.createElementBlock)(`div`,i,[(0,r.renderSlot)(e.$slots,`right-toolbar-before`),n.toolbars.navigation?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:0,type:`button`,onClick:t[0]||=e=>y.$clicks(`navigation`),class:`op-icon fa fa-mavon-bars`,"aria-hidden":`true`,title:n.d_words.tl_navigation_on+` (F8)`},null,8,a)),[[r.vShow,!n.s_navigation]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.navigation?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:1,type:`button`,onClick:t[1]||=e=>y.$clicks(`navigation`),class:`op-icon fa fa-mavon-bars selected`,"aria-hidden":`true`,title:n.d_words.tl_navigation_off+` (F8)`},null,8,o)),[[r.vShow,n.s_navigation]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.preview?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:2,type:`button`,onClick:t[2]||=e=>y.$clicks(`preview`),class:`op-icon fa fa-mavon-eye-slash selected`,"aria-hidden":`true`,title:n.d_words.tl_edit+` (F9)`},null,8,s)),[[r.vShow,n.s_preview_switch]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.preview?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:3,type:`button`,onClick:t[3]||=e=>y.$clicks(`preview`),class:`op-icon fa fa-mavon-eye`,"aria-hidden":`true`,title:n.d_words.tl_preview+` (F9)`},null,8,c)),[[r.vShow,!n.s_preview_switch]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.fullscreen?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:4,type:`button`,onClick:t[4]||=e=>y.$clicks(`fullscreen`),class:`op-icon fa fa-mavon-arrows-alt`,title:n.d_words.tl_fullscreen_on+` (F10)`,"aria-hidden":`true`},null,8,l)),[[r.vShow,!n.s_fullScreen]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.fullscreen?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:5,type:`button`,onClick:t[5]||=e=>y.$clicks(`fullscreen`),class:`op-icon fa fa-mavon-compress selected`,title:n.d_words.tl_fullscreen_off+` (F10)`,"aria-hidden":`true`},null,8,u)),[[r.vShow,n.s_fullScreen]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.readmodel?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:6,type:`button`,onClick:t[6]||=e=>y.$clicks(`read`),class:`op-icon fa fa-mavon-window-maximize`,"aria-hidden":`true`,title:n.d_words.tl_read+` (F11)`},null,8,d)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.subfield?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:7,type:`button`,onClick:t[7]||=e=>y.$clicks(`subfield`),class:(0,r.normalizeClass)([`op-icon fa fa-mavon-columns`,{selected:n.s_subfield}]),"aria-hidden":`true`,title:(n.s_subfield?n.d_words.tl_single_column:n.d_words.tl_double_column)+` (F12)`},null,10,f)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.help&&n.toolbars.htmlcode&&n.toolbars.readmodel&&n.toolbars.fullscreen&&n.toolbars.subfield&&n.toolbars.navigation?((0,r.openBlock)(),(0,r.createElementBlock)(`span`,p)):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.htmlcode?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:9,type:`button`,onClick:t[8]||=e=>y.$clicks(`html`),class:`op-icon fa fa-mavon-code`,title:n.d_words.tl_html_on,"aria-hidden":`true`},null,8,m)),[[r.vShow,!n.s_html_code]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.htmlcode?(0,r.withDirectives)(((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:10,type:`button`,onClick:t[9]||=e=>y.$clicks(`html`),class:`op-icon fa fa-mavon-code selected`,title:n.d_words.tl_html_off,"aria-hidden":`true`},null,8,h)),[[r.vShow,n.s_html_code]]):(0,r.createCommentVNode)(`v-if`,!0),n.toolbars.help?((0,r.openBlock)(),(0,r.createElementBlock)(`button`,{key:11,type:`button`,onClick:t[10]||=e=>y.$clicks(`help`),class:`op-icon fa fa-mavon-question-circle`,style:{"font-size":`17px`,padding:`5px 6px 5px 3px`},title:n.d_words.tl_help,"aria-hidden":`true`},null,8,g)):(0,r.createCommentVNode)(`v-if`,!0),(0,r.renderSlot)(e.$slots,`right-toolbar-after`)])}},function(e,t,n){n.d(t,`a`,(function(){return a}));var r=n(0);let i=[`autofocus`,`placeholder`];function a(e,t,n,a,o,s){return(0,r.openBlock)(),(0,r.createElementBlock)(`div`,{style:(0,r.normalizeStyle)({fontSize:n.fontSize,lineHeight:n.lineHeight,height:n.fullHeight?`100%`:`auto`}),class:`auto-textarea-wrapper`},[(0,r.createElementVNode)(`pre`,{style:(0,r.normalizeStyle)({fontSize:n.fontSize,lineHeight:n.lineHeight,minHeight:n.fullHeight?`100%`:`auto`}),class:`auto-textarea-block`},[t[2]||=(0,r.createElementVNode)(`br`,null,null,-1),(0,r.createTextVNode)((0,r.toDisplayString)(o.temp_value)+` `,1)],4),(0,r.withDirectives)((0,r.createElementVNode)(`textarea`,{ref:`vTextarea`,autofocus:o.s_autofocus,onKeyup:t[0]||=(...e)=>s.change&&s.change(...e),spellcheck:`false`,placeholder:n.placeholder,"onUpdate:modelValue":t[1]||=e=>o.temp_value=e,style:(0,r.normalizeStyle)({fontSize:n.fontSize,lineHeight:n.lineHeight}),class:(0,r.normalizeClass)([{"no-border":!n.border,"no-resize":!n.resize},`auto-textarea-input`])},`      `,46,i),[[r.vModelText,o.temp_value]])],4)}},function(e,t,n){var r=n(80).default;e.exports={markdownIt:r.mixins[0].data().markdownIt,mavonEditor:r,LeftToolbar:n(70).default,RightToolbar:n(72).default,install:function(e){e.component(`mavon-editor`,r)}}},function(e,t,n){n.r(t);var r=n(75),i=n(21);for(var a in i)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return i[e]}))}(a);n(221),n(223);var o=n(3);t.default=n.n(o)()(i.default,[[`render`,r.a],[`__scopeId`,`data-v-33c11741`]])},function(e,t,n){e.exports={default:n(82),__esModule:!0}},function(e,t,n){n(83),n(95),e.exports=n(35).f(`iterator`)},function(e,t,n){var r=n(84)(!0);n(45)(String,`String`,(function(e){this._t=String(e),this._i=0}),(function(){var e,t=this._t,n=this._i;return n>=t.length?{value:void 0,done:!0}:(e=r(t,n),this._i+=e.length,{value:e,done:!1})}))},function(e,t,n){var r=n(26),i=n(27);e.exports=function(e){return function(t,n){var a,o,s=String(i(t)),c=r(n),l=s.length;return c<0||c>=l?e?``:void 0:(a=s.charCodeAt(c))<55296||a>56319||c+1===l||(o=s.charCodeAt(c+1))<56320||o>57343?e?s.charAt(c):a:e?s.slice(c,c+2):o-56320+(a-55296<<10)+65536}}},function(e,t,n){var r=n(86);e.exports=function(e,t,n){if(r(e),t===void 0)return e;switch(n){case 1:return function(n){return e.call(t,n)};case 2:return function(n,r){return e.call(t,n,r)};case 3:return function(n,r,i){return e.call(t,n,r,i)}}return function(){return e.apply(t,arguments)}}},function(e,t){e.exports=function(e){if(typeof e!=`function`)throw TypeError(e+` is not a function!`);return e}},function(e,t,n){var r=n(50),i=n(16),a=n(34),o={};n(5)(o,n(9)(`iterator`),(function(){return this})),e.exports=function(e,t,n){e.prototype=r(o,{next:i(1,n)}),a(e,t+` Iterator`)}},function(e,t,n){var r=n(6),i=n(14),a=n(30);e.exports=n(7)?Object.defineProperties:function(e,t){i(e);for(var n,o=a(t),s=o.length,c=0;s>c;)r.f(e,n=o[c++],t[n]);return e}},function(e,t,n){var r=n(52);e.exports=Object(`z`).propertyIsEnumerable(0)?Object:function(e){return r(e)==`String`?e.split(``):Object(e)}},function(e,t,n){var r=n(8),i=n(91),a=n(92);e.exports=function(e){return function(t,n,o){var s,c=r(t),l=i(c.length),u=a(o,l);if(e&&n!=n){for(;l>u;)if((s=c[u++])!=s)return!0}else for(;l>u;u++)if((e||u in c)&&c[u]===n)return e||u||0;return!e&&-1}}},function(e,t,n){var r=n(26),i=Math.min;e.exports=function(e){return e>0?i(r(e),9007199254740991):0}},function(e,t,n){var r=n(26),i=Math.max,a=Math.min;e.exports=function(e,t){return(e=r(e))<0?i(e+t,0):a(e,t)}},function(e,t,n){var r=n(2).document;e.exports=r&&r.documentElement},function(e,t,n){var r=n(4),i=n(53),a=n(31)(`IE_PROTO`),o=Object.prototype;e.exports=Object.getPrototypeOf||function(e){return e=i(e),r(e,a)?e[a]:typeof e.constructor==`function`&&e instanceof e.constructor?e.constructor.prototype:e instanceof Object?o:null}},function(e,t,n){n(96);for(var r=n(2),i=n(5),a=n(29),o=n(9)(`toStringTag`),s=`CSSRuleList,CSSStyleDeclaration,CSSValueList,ClientRectList,DOMRectList,DOMStringList,DOMTokenList,DataTransferItemList,FileList,HTMLAllCollection,HTMLCollection,HTMLFormElement,HTMLSelectElement,MediaList,MimeTypeArray,NamedNodeMap,NodeList,PaintRequestList,Plugin,PluginArray,SVGLengthList,SVGNumberList,SVGPathSegList,SVGPointList,SVGStringList,SVGTransformList,SourceBufferList,StyleSheetList,TextTrackCueList,TextTrackList,TouchList`.split(`,`),c=0;c<s.length;c++){var l=s[c],u=r[l],d=u&&u.prototype;d&&!d[o]&&i(d,o,l),a[l]=a.Array}},function(e,t,n){var r=n(97),i=n(98),a=n(29),o=n(8);e.exports=n(45)(Array,`Array`,(function(e,t){this._t=o(e),this._i=0,this._k=t}),(function(){var e=this._t,t=this._k,n=this._i++;return!e||n>=e.length?(this._t=void 0,i(1)):i(0,t==`keys`?n:t==`values`?e[n]:[n,e[n]])}),`values`),a.Arguments=a.Array,r(`keys`),r(`values`),r(`entries`)},function(e,t){e.exports=function(){}},function(e,t){e.exports=function(e,t){return{value:t,done:!!e}}},function(e,t,n){e.exports={default:n(100),__esModule:!0}},function(e,t,n){n(101),n(107),n(108),n(109),e.exports=n(13).Symbol},function(e,t,n){var r=n(2),i=n(4),a=n(7),o=n(46),s=n(49),c=n(102).KEY,l=n(15),u=n(32),d=n(34),f=n(17),p=n(9),m=n(35),h=n(36),g=n(103),_=n(104),v=n(14),y=n(10),b=n(53),x=n(8),S=n(28),C=n(16),w=n(50),T=n(105),E=n(106),D=n(54),O=n(6),k=n(30),A=E.f,j=O.f,M=T.f,N=r.Symbol,P=r.JSON,F=P&&P.stringify,I=p(`_hidden`),L=p(`toPrimitive`),R={}.propertyIsEnumerable,z=u(`symbol-registry`),B=u(`symbols`),V=u(`op-symbols`),H=Object.prototype,U=typeof N==`function`&&!!D.f,W=r.QObject,G=!W||!W.prototype||!W.prototype.findChild,K=a&&l((function(){return w(j({},`a`,{get:function(){return j(this,`a`,{value:7}).a}})).a!=7}))?function(e,t,n){var r=A(H,t);r&&delete H[t],j(e,t,n),r&&e!==H&&j(H,t,r)}:j,q=function(e){var t=B[e]=w(N.prototype);return t._k=e,t},J=U&&typeof N.iterator==`symbol`?function(e){return typeof e==`symbol`}:function(e){return e instanceof N},Y=function(e,t,n){return e===H&&Y(V,t,n),v(e),t=S(t,!0),v(n),i(B,t)?(n.enumerable?(i(e,I)&&e[I][t]&&(e[I][t]=!1),n=w(n,{enumerable:C(0,!1)})):(i(e,I)||j(e,I,C(1,{})),e[I][t]=!0),K(e,t,n)):j(e,t,n)},X=function(e,t){v(e);for(var n,r=g(t=x(t)),i=0,a=r.length;a>i;)Y(e,n=r[i++],t[n]);return e},Z=function(e){var t=R.call(this,e=S(e,!0));return!(this===H&&i(B,e)&&!i(V,e))&&(!(t||!i(this,e)||!i(B,e)||i(this,I)&&this[I][e])||t)},Q=function(e,t){if(e=x(e),t=S(t,!0),e!==H||!i(B,t)||i(V,t)){var n=A(e,t);return!n||!i(B,t)||i(e,I)&&e[I][t]||(n.enumerable=!0),n}},$=function(e){for(var t,n=M(x(e)),r=[],a=0;n.length>a;)i(B,t=n[a++])||t==I||t==c||r.push(t);return r},ee=function(e){for(var t,n=e===H,r=M(n?V:x(e)),a=[],o=0;r.length>o;)!i(B,t=r[o++])||n&&!i(H,t)||a.push(B[t]);return a};U||(s((N=function(){if(this instanceof N)throw TypeError(`Symbol is not a constructor!`);var e=f(arguments.length>0?arguments[0]:void 0),t=function(n){this===H&&t.call(V,n),i(this,I)&&i(this[I],e)&&(this[I][e]=!1),K(this,e,C(1,n))};return a&&G&&K(H,e,{configurable:!0,set:t}),q(e)}).prototype,`toString`,(function(){return this._k})),E.f=Q,O.f=Y,n(55).f=T.f=$,n(37).f=Z,D.f=ee,a&&!n(12)&&s(H,`propertyIsEnumerable`,Z,!0),m.f=function(e){return q(p(e))}),o(o.G+o.W+o.F*!U,{Symbol:N});for(var te=`hasInstance,isConcatSpreadable,iterator,match,replace,search,species,split,toPrimitive,toStringTag,unscopables`.split(`,`),ne=0;te.length>ne;)p(te[ne++]);for(var re=k(p.store),ie=0;re.length>ie;)h(re[ie++]);o(o.S+o.F*!U,`Symbol`,{for:function(e){return i(z,e+=``)?z[e]:z[e]=N(e)},keyFor:function(e){if(!J(e))throw TypeError(e+` is not a symbol!`);for(var t in z)if(z[t]===e)return t},useSetter:function(){G=!0},useSimple:function(){G=!1}}),o(o.S+o.F*!U,`Object`,{create:function(e,t){return t===void 0?w(e):X(w(e),t)},defineProperty:Y,defineProperties:X,getOwnPropertyDescriptor:Q,getOwnPropertyNames:$,getOwnPropertySymbols:ee});var ae=l((function(){D.f(1)}));o(o.S+o.F*ae,`Object`,{getOwnPropertySymbols:function(e){return D.f(b(e))}}),P&&o(o.S+o.F*(!U||l((function(){var e=N();return F([e])!=`[null]`||F({a:e})!=`{}`||F(Object(e))!=`{}`}))),`JSON`,{stringify:function(e){for(var t,n,r=[e],i=1;arguments.length>i;)r.push(arguments[i++]);if(n=t=r[1],(y(t)||e!==void 0)&&!J(e))return _(t)||(t=function(e,t){if(typeof n==`function`&&(t=n.call(this,e,t)),!J(t))return t}),r[1]=t,F.apply(P,r)}}),N.prototype[L]||n(5)(N.prototype,L,N.prototype.valueOf),d(N,`Symbol`),d(Math,`Math`,!0),d(r.JSON,`JSON`,!0)},function(e,t,n){var r=n(17)(`meta`),i=n(10),a=n(4),o=n(6).f,s=0,c=Object.isExtensible||function(){return!0},l=!n(15)((function(){return c(Object.preventExtensions({}))})),u=function(e){o(e,r,{value:{i:`O`+ ++s,w:{}}})},d=e.exports={KEY:r,NEED:!1,fastKey:function(e,t){if(!i(e))return typeof e==`symbol`?e:(typeof e==`string`?`S`:`P`)+e;if(!a(e,r)){if(!c(e))return`F`;if(!t)return`E`;u(e)}return e[r].i},getWeak:function(e,t){if(!a(e,r)){if(!c(e))return!0;if(!t)return!1;u(e)}return e[r].w},onFreeze:function(e){return l&&d.NEED&&c(e)&&!a(e,r)&&u(e),e}}},function(e,t,n){var r=n(30),i=n(54),a=n(37);e.exports=function(e){var t=r(e),n=i.f;if(n)for(var o,s=n(e),c=a.f,l=0;s.length>l;)c.call(e,o=s[l++])&&t.push(o);return t}},function(e,t,n){var r=n(52);e.exports=Array.isArray||function(e){return r(e)==`Array`}},function(e,t,n){var r=n(8),i=n(55).f,a={}.toString,o=typeof window==`object`&&window&&Object.getOwnPropertyNames?Object.getOwnPropertyNames(window):[];e.exports.f=function(e){return o&&a.call(e)==`[object Window]`?function(e){try{return i(e)}catch{return o.slice()}}(e):i(r(e))}},function(e,t,n){var r=n(37),i=n(16),a=n(8),o=n(28),s=n(4),c=n(47),l=Object.getOwnPropertyDescriptor;t.f=n(7)?l:function(e,t){if(e=a(e),t=o(t,!0),c)try{return l(e,t)}catch{}if(s(e,t))return i(!r.f.call(e,t),e[t])}},function(e,t){},function(e,t,n){n(36)(`asyncIterator`)},function(e,t,n){n(36)(`observable`)},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0});var r=119,i=120,a=121,o=122,s=123,c=66,l=73,u=72,d=85,f=68,p=77,m=81,h=79,g=76,_=83,v=90,y=89,b=67,x=84,S=82,C=8,w=9,T=13,E=97,D=98,O=99,k=100,A=101,j=102,M=49,N=50,P=51,F=52,I=53,L=54;t.keydownListen=function(e){e.shortCut&&e.$el.addEventListener(`keydown`,(function(t){if(t.ctrlKey||t.metaKey||t.altKey||t.shiftKey){if(!t.ctrlKey&&!t.metaKey||t.altKey||t.shiftKey){if((t.ctrlKey||t.metaKey)&&t.altKey&&!t.shiftKey)switch(t.keyCode){case _:t.preventDefault(),e.toolbar_left_click(`superscript`);break;case d:t.preventDefault(),e.toolbar_left_click(`ul`);break;case g:t.preventDefault(),e.toolbar_left_click(`imagelink`);break;case b:t.preventDefault(),e.toolbar_left_click(`code`);break;case x:t.preventDefault(),e.toolbar_left_click(`table`)}else if((t.ctrlKey||t.metaKey)&&t.shiftKey&&!t.altKey)switch(t.keyCode){case _:t.preventDefault(),e.toolbar_left_click(`subscript`);break;case f:t.preventDefault(),e.toolbar_left_click(`strikethrough`);break;case g:t.preventDefault(),e.toolbar_left_click(`alignleft`);break;case S:t.preventDefault(),e.toolbar_left_click(`alignright`);break;case b:t.preventDefault(),e.toolbar_left_click(`aligncenter`)}else if(!t.ctrlKey&&!t.metaKey&&t.shiftKey&&!t.altKey)switch(t.keyCode){case w:e.$refs.toolbar_left.s_img_link_open||(t.preventDefault(),e.unInsertTab())}}else switch(t.keyCode){case c:t.preventDefault(),e.toolbar_left_click(`bold`);break;case l:t.preventDefault(),e.toolbar_left_click(`italic`);break;case u:t.preventDefault(),e.toolbar_left_click(`header`);break;case d:t.preventDefault(),e.toolbar_left_click(`underline`);break;case f:t.preventDefault(),e.toolbar_left_click(`removeLine`);break;case p:t.preventDefault(),e.toolbar_left_click(`mark`);break;case m:t.preventDefault(),e.toolbar_left_click(`quote`);break;case h:t.preventDefault(),e.toolbar_left_click(`ol`);break;case g:t.preventDefault(),e.toolbar_left_click(`link`);break;case _:t.preventDefault(),e.toolbar_left_click(`save`);break;case v:t.preventDefault(),e.toolbar_left_click(`undo`);break;case y:t.preventDefault(),e.toolbar_left_click(`redo`);break;case C:t.preventDefault(),e.toolbar_left_click(`trash`);break;case E:t.preventDefault(),e.toolbar_left_click(`header1`);break;case D:t.preventDefault(),e.toolbar_left_click(`header2`);break;case O:t.preventDefault(),e.toolbar_left_click(`header3`);break;case k:t.preventDefault(),e.toolbar_left_click(`header4`);break;case A:t.preventDefault(),e.toolbar_left_click(`header5`);break;case j:t.preventDefault(),e.toolbar_left_click(`header6`);break;case M:t.preventDefault(),e.toolbar_left_click(`header1`);break;case N:t.preventDefault(),e.toolbar_left_click(`header2`);break;case P:t.preventDefault(),e.toolbar_left_click(`header3`);break;case F:t.preventDefault(),e.toolbar_left_click(`header4`);break;case I:t.preventDefault(),e.toolbar_left_click(`header5`);break;case L:t.preventDefault(),e.toolbar_left_click(`header6`)}}else switch(t.keyCode){case r:e.toolbars.navigation&&(t.preventDefault(),e.toolbar_right_click(`navigation`));break;case i:e.toolbars.preview&&(t.preventDefault(),e.toolbar_right_click(`preview`));break;case a:e.toolbars.fullscreen&&(t.preventDefault(),e.toolbar_right_click(`fullscreen`));break;case o:e.toolbars.readmodel&&(t.preventDefault(),e.toolbar_right_click(`read`));break;case s:e.toolbars.subfield&&(t.preventDefault(),e.toolbar_right_click(`subfield`));break;case w:e.$refs.toolbar_left.s_img_link_open||(t.preventDefault(),e.insertTab());break;case T:e.$refs.toolbar_left.s_img_link_open?(t.preventDefault(),e.$refs.toolbar_left.$imgLinkAdd()):e.insertEnter(t)}}))}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default={agate:1,androidstudio:1,"arduino-light":1,arta:1,ascetic:1,"atom-one-dark":1,"atom-one-light":1,"brown-paper":1,"codepen-embed":1,"color-brewer":1,dark:1,default:1,docco:1,far:1,foundation:1,github:1,googlecode:1,grayscale:1,hybrid:1,idea:1,"ir-black":1,magula:1,"mono-blue":1,"monokai-sublime":1,monokai:1,obsidian:1,"paraiso-dark":1,"paraiso-light":1,pojoaque:1,purebasic:1,rainbow:1,routeros:1,"school-book":1,sunburst:1,"tomorrow-night-blue":1,"tomorrow-night-bright":1,vs:1,vs2015:1,xcode:1,xt256:1,"a11y-dark":1,"a11y-light":1,"an-old-hope":1,"atom-one-dark-reasonable":1,"brown-pap":1,devibeans:1,"github-dark":1,"github-dark-dimmed":1,gml:1,"gradient-dark":1,"gradient-light":1,"isbl-editor-dark":1,"isbl-editor-light":1,"kimbie-dark":1,"kimbie-light":1,lightfair:1,lioshi:1,"night-owl":1,"nnfx-dark":1,"nnfx-light":1,nord:1,pojo:1,"qtcreator-dark":1,"qtcreator-light":1,"shades-of-purple":1,srcery:1,"stackoverflow-dark":1,"stackoverflow-light":1}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0});var r,i=(r=n(25))&&r.__esModule?r:{default:r};t.p_ObjectCopy_DEEP=function e(t,n){for(var r in n){var a=(0,i.default)(n[r]);t[r]=!t[r]||a!==`Object`&&a!==`object`?n[r]:e(t[r],n[r])}return t},t.p_urlParse=function(){var e=window.location.search,t={},n=e.match(/[?&][^?&]+=[^?&]+/g);return n&&n.forEach((function(e){var n=e.substring(1).split(`=`),r=decodeURIComponent(n[0]);t[r]=decodeURIComponent(n[1])})),t},t.stopEvent=function(e){e&&(e.preventDefault&&e.preventDefault(),e.stopPropagation&&e.stopPropagation())}},function(e,t,n){function r(e){e.d_history_index>0&&e.d_history_index--,e.$nextTick((function(){var t=e.textarea_selectionEnds[e.d_history_index];e.getTextareaDom().selectionStart=t,e.getTextareaDom().selectionEnd=t})),e.getTextareaDom().focus()}function i(e){e.d_history_index<e.d_history.length-1&&e.d_history_index++,e.$nextTick((function(){var t=e.textarea_selectionEnds[e.d_history_index];e.getTextareaDom().selectionStart=t,e.getTextareaDom().selectionEnd=t})),e.getTextareaDom().focus()}function a(e){e.d_value=``,e.getTextareaDom().focus()}function o(e){e.save(e.d_value,e.d_render)}function s(e){e.insertOl()}function c(e){e.insertUl()}function l(e){e.removeLine()}function u(e){e.insertCodeBlock()}Object.defineProperty(t,"__esModule",{value:!0}),t.toolbar_left_addlink=function(e,t,n,r){var i={prefix:e===`link`?`[`+t+`](`:`![`+t+`](`,subfix:`)`,str:n};r.insertText(r.getTextareaDom(),i)},t.toolbar_left_click=function(e,t){var n={bold:{prefix:`**`,subfix:`**`,str:t.d_words.tl_bold},italic:{prefix:`*`,subfix:`*`,str:t.d_words.tl_italic},header:{prefix:`# `,subfix:``,str:t.d_words.tl_header},header1:{prefix:`# `,subfix:``,str:t.d_words.tl_header_one},header2:{prefix:`## `,subfix:``,str:t.d_words.tl_header_two},header3:{prefix:`### `,subfix:``,str:t.d_words.tl_header_three},header4:{prefix:`#### `,subfix:``,str:t.d_words.tl_header_four},header5:{prefix:`##### `,subfix:``,str:t.d_words.tl_header_five},header6:{prefix:`###### `,subfix:``,str:t.d_words.tl_header_six},underline:{prefix:`++`,subfix:`++`,str:t.d_words.tl_underline},strikethrough:{prefix:`~~`,subfix:`~~`,str:t.d_words.tl_strikethrough},mark:{prefix:`==`,subfix:`==`,str:t.d_words.tl_mark},superscript:{prefix:`^`,subfix:`^`,str:t.d_words.tl_superscript},subscript:{prefix:`~`,subfix:`~`,str:t.d_words.tl_subscript},quote:{prefix:`> `,subfix:``,str:t.d_words.tl_quote},link:{prefix:`[](`,subfix:`)`,str:t.d_words.tl_link},imagelink:{prefix:`![](`,subfix:`)`,str:t.d_words.tl_image},table:{prefix:``,subfix:``,str:`|column1|column2|column3|
|-|-|-|
|content1|content2|content3|
`},aligncenter:{prefix:`::: hljs-center

`,subfix:`

:::
`,str:t.d_words.tl_aligncenter},alignright:{prefix:`::: hljs-right

`,subfix:`

:::
`,str:t.d_words.tl_alignright},alignleft:{prefix:`::: hljs-left

`,subfix:`

:::
`,str:t.d_words.tl_alignleft}};n.hasOwnProperty(e)&&t.insertText(t.getTextareaDom(),n[e]);var d={undo:r,redo:i,trash:a,save:o,ol:s,ul:c,removeLine:l,code:u};d.hasOwnProperty(e)&&d[e](t)}},function(e,t,n){function r(e){e.s_html_code=!e.s_html_code,e.htmlcode&&e.htmlcode(e.s_html_code,e.d_value)}function i(e){e.s_help=!e.s_help,e.helptoggle&&e.helptoggle(e.s_help,e.d_value)}function a(e){var t=e.$refs.vReadModel;t.requestFullscreen?t.requestFullscreen():t.mozRequestFullScreen?t.mozRequestFullScreen():t.webkitRequestFullscreen?t.webkitRequestFullscreen():t.msRequestFullscreen&&t.msRequestFullscreen()}function o(e){e.s_preview_switch=!e.s_preview_switch,e.previewtoggle&&e.previewtoggle(e.s_preview_switch,e.d_value)}function s(e){e.s_fullScreen=!e.s_fullScreen,e.fullscreen&&e.fullscreen(e.s_fullScreen,e.d_value)}function c(e){e.s_subfield=!e.s_subfield,e.s_preview_switch=e.s_subfield,e.previewtoggle&&e.previewtoggle(e.s_preview_switch,e.d_value),e.subfieldtoggle&&e.subfieldtoggle(e.s_subfield,e.d_value)}function l(e){e.s_navigation=!e.s_navigation,e.s_navigation&&(e.s_preview_switch=!0),e.navigationtoggle&&e.navigationtoggle(e.s_navigation,e.d_value),e.s_navigation&&e.getNavigation(e,!1)}Object.defineProperty(t,"__esModule",{value:!0}),t.toolbar_right_click=function(e,t){var n={help:i,html:r,read:a,preview:o,fullscreen:s,navigation:l,subfield:c};n.hasOwnProperty(e)&&n[e](t)}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.CONFIG=void 0;var r=b(n(116)),i=b(n(117)),a=b(n(118)),o=b(n(119)),s=b(n(120)),c=b(n(121)),l=b(n(122)),u=b(n(123)),d=(b(n(124)),b(n(125))),f=b(n(126)),p=b(n(127)),m=b(n(128)),h=b(n(129)),g=b(n(130)),_=b(n(131)),v=b(n(132)),y=b(n(133));function b(e){return e&&e.__esModule?e:{default:e}}t.CONFIG={"help_zh-CN":r.default,"help_zh-TW":i.default,"help_pt-BR":s.default,help_en:a.default,help_fr:o.default,help_ru:c.default,help_de:l.default,help_ja:u.default,"words_zh-CN":d.default,"words_zh-TW":f.default,"words_pt-BR":h.default,words_en:p.default,words_fr:m.default,words_ru:g.default,words_de:_.default,words_ja:v.default,words_kr:y.default,langList:[`en`,`zh-CN`,`zh-TW`,`fr`,`pt-BR`,`ru`,`de`,`ja`,`kr`],toolbars:{bold:!0,italic:!0,header:!0,underline:!0,strikethrough:!0,mark:!0,superscript:!0,subscript:!0,quote:!0,ol:!0,ul:!0,link:!0,imagelink:!0,code:!0,table:!0,undo:!0,redo:!0,trash:!0,save:!0,alignleft:!0,aligncenter:!0,alignright:!0,navigation:!0,subfield:!0,fullscreen:!0,readmodel:!0,htmlcode:!0,help:!0,preview:!0}}},function(e,t){e.exports=`@[toc](目录)\r
\r
Markdown 语法简介\r
=============\r
> [语法详解](http://commonmark.org/help/)\r
\r
## **目录**\r
\`\`\`\r
@[toc](目录)\r
\`\`\`\r
\r
## **粗体**\r
\`\`\`\r
**粗体**\r
__粗体__\r
\`\`\`\r
## *斜体*\r
\`\`\`\r
*斜体*\r
_斜体_\r
\`\`\`\r
## 标题\r
\`\`\`\r
# 一级标题 #\r
一级标题\r
====\r
## 二级标题 ##\r
二级标题\r
----\r
### 三级标题 ###\r
#### 四级标题 ####\r
##### 五级标题 #####\r
###### 六级标题 ######\r
\`\`\`\r
## 分割线\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^上^角~下~标\r
\`\`\`\r
上角标 x^2^\r
下角标 H~2~0\r
\`\`\`\r
## ++下划线++ ~~中划线~~\r
\`\`\`\r
++下划线++\r
~~中划线~~\r
\`\`\`\r
## ==标记==\r
\`\`\`\r
==标记==\r
\`\`\`\r
## 段落引用\r
\`\`\`\r
> 一级\r
>> 二级\r
>>> 三级\r
...\r
\`\`\`\r
\r
## 列表\r
\`\`\`\r
有序列表\r
1.\r
2.\r
3.\r
...\r
无序列表\r
-\r
-\r
...\r
\`\`\`\r
\r
## 任务列表\r
\r
- [x] 已完成任务\r
- [ ] 未完成任务\r
\r
\`\`\`\r
- [x] 已完成任务\r
- [ ] 未完成任务\r
\`\`\`\r
\r
## 链接\r
\`\`\`\r
[链接](www.baidu.com)\r
![图片描述](http://www.image.com)\r
\`\`\`\r
## 代码段落\r
\\\`\`\` type\r
\r
代码段落\r
\r
\\\`\`\`\r
\r
\\\` 代码块 \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
## 表格(table)\r
\`\`\`\r
| 标题1 | 标题2 | 标题3 |\r
| :--  | :--: | ----: |\r
| 左对齐 | 居中 | 右对齐 |\r
| ---------------------- | ------------- | ----------------- |\r
\`\`\`\r
| 标题1 | 标题2 | 标题3 |\r
| :--  | :--: | ----: |\r
| 左对齐 | 居中 | 右对齐 |\r
| ---------------------- | ------------- | ----------------- |\r
## 脚注(footnote)\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
见底部脚注[^hello]\r
\r
[^hello]: 一个注脚\r
\r
## 表情(emoji)\r
[参考网站: https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$公式\r
\r
我们可以渲染公式例如：$x_i + y_i = z_i$和$\\sum_{i=1}^n a_i=0$\r
我们也可以单行渲染\r
$$\\sum_{i=1}^n a_i=0$$\r
具体可参照[katex文档](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex支持的函数](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex文档](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## 布局\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`居左\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`居中\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`居右\`\r
\`:::\`\r
:::\r
\r
## 定义\r
\r
术语一\r
\r
:   定义一\r
\r
包含有*行内标记*的术语二\r
\r
:   定义二\r
\r
        {一些定义二的文字或代码}\r
\r
    定义二的第三段\r
\r
\`\`\`\r
术语一\r
\r
:   定义一\r
\r
包含有*行内标记*的术语二\r
\r
:   定义二\r
\r
        {一些定义二的文字或代码}\r
\r
    定义二的第三段\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
HTML 规范由 W3C 维护\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
HTML 规范由 W3C 维护\r
\`\`\`\r
\r
`},function(e,t){e.exports=`@[toc](目錄)\r
\r
Markdown 語法簡介\r
=============\r
> [語法詳解](http://commonmark.org/help/)\r
\r
## **粗體**\r
\`\`\`\r
**粗體**\r
__粗體__\r
\`\`\`\r
## *斜體*\r
\`\`\`\r
*斜體*\r
_斜體_\r
\`\`\`\r
## 標題\r
\`\`\`\r
# 一級標題 #\r
一級標題\r
====\r
## 二級標題 ##\r
二級標題\r
----\r
### 三級標題 ###\r
#### 四級標題 ####\r
##### 五級標題 #####\r
###### 六級標題 ######\r
\`\`\`\r
## 分割線\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^上^角~下~標\r
\`\`\`\r
上角標 x^2^\r
下角標 H~2~0\r
\`\`\`\r
## ++下劃線++ ~~中劃線~~\r
\`\`\`\r
++下劃線++\r
~~中劃線~~\r
\`\`\`\r
## ==標記==\r
\`\`\`\r
==標記==\r
\`\`\`\r
## 段落引用\r
\`\`\`\r
> 一級\r
>> 二級\r
>>> 三級\r
...\r
\`\`\`\r
\r
## 列表\r
\`\`\`\r
有序列表\r
1.\r
2.\r
3.\r
...\r
無序列表\r
-\r
-\r
...\r
\`\`\`\r
\r
## 任務列表\r
\r
- [x] 已完成任務\r
- [ ] 未完成任務\r
\r
\`\`\`\r
- [x] 已完成任務\r
- [ ] 未完成任務\r
\`\`\`\r
\r
## 鏈接\r
\`\`\`\r
[鏈接](www.baidu.com)\r
![圖片描述](http://www.image.com)\r
\`\`\`\r
## 代碼段落\r
\\\`\`\` type\r
\r
代碼段落\r
\r
\\\`\`\`\r
\r
\\\` 代碼塊 \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
## 表格(table)\r
\`\`\`\r
| 標題1 | 標題2 | 標題3 |\r
| :--  | :--: | ----: |\r
| 左對齊 | 居中 | 右對齊 |\r
| ---------------------- | ------------- | ----------------- |\r
\`\`\`\r
| 標題1 | 標題2 | 標題3 |\r
| :--  | :--: | ----: |\r
| 左對齊 | 居中 | 右對齊 |\r
| ---------------------- | ------------- | ----------------- |\r
## 腳註(footnote)\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
見底部腳註[^hello]\r
\r
[^hello]: 一個註腳\r
\r
## 表情(emoji)\r
[參考網站: https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$公式\r
\r
我們可以渲染公式例如：$x_i + y_i = z_i$和$\\sum_{i=1}^n a_i=0$\r
我們也可以單行渲染\r
$$\\sum_{i=1}^n a_i=0$$\r
具體可參照[katex文檔](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex支持的函數](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex文檔](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## 布局\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`居左\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`居中\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`居右\`\r
\`:::\`\r
:::\r
\r
## 定義\r
\r
術語一\r
\r
:   定義一\r
\r
包含有*行內標記*的術語二\r
\r
:   定義二\r
\r
        {一些定義二的文字或代碼}\r
\r
    定義二的第三段\r
\r
\`\`\`\r
術語一\r
\r
:   定義一\r
\r
包含有*行內標記*的術語二\r
\r
:   定義二\r
\r
        {一些定義二的文字或代碼}\r
\r
    定義二的第三段\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
HTML 規範由 W3C 維護\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
HTML 規範由 W3C 維護\r
\`\`\`\r
\r
`},function(e,t){e.exports=`@[toc](Catalog)\r
\r
Markdown Guide\r
===\r
> Detailed: [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **Bold**\r
\`\`\`\r
**bold**\r
__bold__\r
\`\`\`\r
## *Italic*\r
\`\`\`\r
*italic*\r
_italic_\r
\`\`\`\r
## Header\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## Dividing line\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^Super^script & ~Sub~script\r
\`\`\`\r
super x^2^\r
sub H~2~0\r
\`\`\`\r
## ++Underline++ & ~~Strikethrough~~\r
\`\`\`\r
++underline++\r
~~strikethrough~~\r
\`\`\`\r
## ==Mark==\r
\`\`\`\r
==mark==\r
\`\`\`\r
## Quote\r
\r
\`\`\`\r
> quote 1\r
>> quote 2\r
>>> quote 3\r
...\r
\`\`\`\r
\r
## List\r
\`\`\`\r
ol\r
1.\r
2.\r
3.\r
...\r
\r
ul\r
-\r
-\r
...\r
\`\`\`\r
\r
## Todo List\r
\r
- [x] task 1\r
- [ ] task 2\r
\r
\`\`\`\r
- [x] task 1\r
- [ ] task 2\r
\`\`\`\r
\r
## Link\r
\`\`\`\r
Text Link\r
[Text](www.baidu.com)\r
\r
Image Link\r
![Text](http://www.image.com)\r
\`\`\`\r
## Code\r
\\\`\`\` type\r
\r
code block\r
\r
\\\`\`\`\r
\r
\\\` code \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## Table\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
| ---------------------- | ------------- | ----------------- |\r
## Footnote\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
Look at the bottom[^hello]\r
\r
[^hello]: footnote\r
\r
## Emojis\r
Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ Mathematics\r
\r
We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$\r
We can also single-line rendering\r
$$\\sum_{i=1}^n a_i=0$$\r
Detailed: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## Layout\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`left\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`center\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`right\`\r
\`:::\`\r
:::\r
\r
## deflist\r
\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](Catalogue)\r
\r
Guide Markdown\r
==============\r
> Détail : [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **Bold**\r
\`\`\`\r
**bold**\r
__bold__\r
\`\`\`\r
## *Italic*\r
\`\`\`\r
*italic*\r
_italic_\r
\`\`\`\r
## Header\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## Dividing line\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^Super^script & ~Sub~script\r
\`\`\`\r
super x^2^\r
sub H~2~0\r
\`\`\`\r
## ++Underline++ & ~~Strikethrough~~\r
\`\`\`\r
++underline++\r
~~strikethrough~~\r
\`\`\`\r
## ==Mark==\r
\`\`\`\r
==mark==\r
\`\`\`\r
## Quote\r
\r
\`\`\`\r
> quote 1\r
>> quote 2\r
>>> quote 3\r
...\r
\`\`\`\r
\r
## List\r
\`\`\`\r
ol\r
1.\r
2.\r
3.\r
...\r
\r
ul\r
-\r
-\r
...\r
\`\`\`\r
## Link\r
\r
## Todo List\r
\r
- [x] Équipe 1\r
- [ ] Équipe 2\r
\r
\`\`\`\r
- [x] Équipe 1\r
- [ ] Équipe 2\r
\`\`\`\r
\r
\`\`\`\r
Text Link\r
[Text](www.baidu.com)\r
\r
Image Link\r
![Text](http://www.image.com)\r
\`\`\`\r
## Code\r
\\\`\`\` type\r
\r
code block\r
\r
\\\`\`\`\r
\r
\\\` code \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## Table\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
| ---------------------- | ------------- | ----------------- |\r
## Footnote\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
Look at the bottom[^hello]\r
\r
[^hello]: footnote\r
\r
## Emojis\r
Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ Mathematics\r
\r
We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$\r
We can also single-line rendering\r
$$\\sum_{i=1}^n a_i=0$$\r
Detailed: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## Layout\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`left\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`center\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`right\`\r
\`:::\`\r
:::\r
\r
## deflist\r
\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](Directory)\r
\r
Guia Markdown\r
===\r
> Detalhes: [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **Negrito**\r
\`\`\`\r
**negrito**\r
__negrito__\r
\`\`\`\r
## *Itálico*\r
\`\`\`\r
*itálico*\r
_itálico_\r
\`\`\`\r
## Cabeçalho\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## Linha Divisora\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^Sobre^scrito & ~Sub~scrito\r
\`\`\`\r
sobre x^2^\r
sub H~2~0\r
\`\`\`\r
## ++Sublinhar++ & ~~Tachar~~\r
\`\`\`\r
++sublinhar++\r
~~tachar~~\r
\`\`\`\r
## ==Marcador==\r
\`\`\`\r
==marcador==\r
\`\`\`\r
## Citação\r
\r
\`\`\`\r
> citação 1\r
>> citação 2\r
>>> citação 3\r
...\r
\`\`\`\r
\r
## Listas\r
\`\`\`\r
lista Numerada\r
1.\r
2.\r
3.\r
...\r
\r
lista com marcadores\r
-\r
-\r
...\r
\`\`\`\r
\r
## Todo Listas\r
\r
- [x] Tarefa 1\r
- [ ] Tarefa 2\r
\r
\`\`\`\r
- [x] Tarefa 1\r
- [ ] Tarefa 2\r
\`\`\`\r
\r
## Link\r
\`\`\`\r
Link Texto\r
[Text](www.baidu.com)\r
\r
Link Imagem\r
![Text](http://www.image.com)\r
\`\`\`\r
## Código\r
\\\`\`\` tipo\r
\r
bloco de código\r
\r
\\\`\`\`\r
\r
\\\` código \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## Tabela\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| esquerda | centro | direita |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| esquerda | centro | direita |\r
| ---------------------- | ------------- | ----------------- |\r
## Rodapé\r
\`\`\`\r
olá[^olá]\r
\`\`\`\r
\r
Olhe para baixo[^olá]\r
\r
[^olá]: rodapé\r
\r
## Emojis\r
Detalhes: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ Mathematics\r
\r
Podemos mostrar fórmulas por exemplo：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$\r
Podemos também mostrar em uma única linha:\r
$$\\sum_{i=1}^n a_i=0$$\r
Detalhes: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## Layout\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`esquerda\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`centro\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`direita\`\r
\`:::\`\r
:::\r
\r
## Definições\r
\r
Termo 1\r
\r
:   Definição 1\r
\r
Termo 2 com *markup inline*\r
\r
:   Definição 2\r
\r
        { um pouco de código, parte da Definição 2 }\r
\r
    Terceiro parágrafo da definição 2.\r
\r
\`\`\`\r
Termo 1\r
\r
:   Definição 1\r
\r
Termo 2 com *markup inline*\r
\r
:   Definição 2\r
\r
        { um pouco de código, parte da Definição 2 }\r
\r
    Terceiro parágrafo da definição 2.\r
\r
\`\`\`\r
\r
## Abreviações\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
A especificação HTML\r
é mantida pela W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
é mantida pela W3C.\r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](Catalog)  \r
  \r
Markdown помощь  \r
===  \r
> Подробнее: [http://commonmark.org/help/](http://commonmark.org/help/)  \r
  \r
## **Полужирный**  \r
\`\`\`  \r
**Полужирный**  \r
__Полужирный__  \r
\`\`\`  \r
## *Курсив*  \r
\`\`\`  \r
*Курсив*  \r
_Курсив_  \r
\`\`\`  \r
## Заголовок  \r
\`\`\`  \r
# h1 #  \r
h1  \r
====  \r
## h2 ##  \r
h2  \r
----  \r
### h3 ###  \r
#### h4 ####  \r
##### h5 #####  \r
###### h6 ######  \r
\`\`\`  \r
## Разделительная линия  \r
\`\`\`  \r
***  \r
---  \r
\`\`\`  \r
****  \r
## ^Верхний^индекс & ~Нижний~индекс  \r
\`\`\`  \r
верхний x^2^  \r
нижний H~2~0  \r
\`\`\`  \r
## ++Подчеркнутый++ & ~~Зачеркнутый~~  \r
\`\`\`  \r
++Подчеркнутый++  \r
~~Зачеркнутый~~  \r
\`\`\`  \r
## ==Отметка==  \r
\`\`\`  \r
==Отметка==  \r
\`\`\`  \r
## Цитата  \r
  \r
\`\`\`  \r
> Цитата  \r
>> Цитата 2  \r
>>> Цитата 3  \r
...  \r
\`\`\`  \r
  \r
## Список  \r
\`\`\`  \r
ol  \r
1.  \r
2.  \r
3.  \r
...  \r
  \r
ul  \r
-  \r
-  \r
...  \r
\`\`\`  \r
  \r
## Список задач  \r
  \r
- [x] Задача 1  \r
- [ ] Задача 2  \r
  \r
\`\`\`  \r
- [x] Задача 1  \r
- [ ] Задача 2  \r
\`\`\`  \r
  \r
## Ссылка  \r
\`\`\`  \r
Ссылка  \r
[Текст](www.baidu.com)  \r
  \r
Ссылка изображения  \r
![Текст](http://www.image.com)  \r
\`\`\`  \r
## Код  \r
\\\`\`\` type  \r
  \r
code block  \r
  \r
\\\`\`\`  \r
  \r
\\\` code \\\`  \r
  \r
\`\`\`c++  \r
int main()  \r
{  \r
 printf("hello world!");}  \r
\`\`\`  \r
\`code\`  \r
  \r
## Таблица  \r
\`\`\`  \r
| th1 | th2 | th3 |  \r
| :--  | :--: | ----: |  \r
| left | center | right |  \r
\`\`\`  \r
| th1 | th2 | th3 |  \r
| :--  | :--: | ----: |  \r
| left | center | right |  \r
| ---------------------- | ------------- | ----------------- |  \r
## Сноска  \r
\`\`\`  \r
Привет[^Привет]  \r
\`\`\`  \r
  \r
Тут что-то непонятное[^Привет]  \r
  \r
[^Привет]: А тут объяснение  \r
  \r
## Emojis  \r
Подробнее: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)  \r
\`\`\`  \r
:laughing:  \r
:blush:  \r
:smiley:  \r
:)  \r
...  \r
\`\`\`  \r
:laughing::blush::smiley::)  \r
  \r
## $\\KaTeX$ Mathematics  \r
  \r
Можно выводить такие формулы：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$  \r
А также в одну строку:\r
$$\\sum_{i=1}^n a_i=0$$  \r
Подробнее: \r
- [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)\r
- [katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)\r
- [latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)  \r
  \r
## Разметка\r
  \r
::: hljs-left  \r
\`::: hljs-left\`  \r
\`left\`  \r
\`:::\`  \r
:::  \r
  \r
::: hljs-center  \r
\`::: hljs-center\`  \r
\`center\`  \r
\`:::\`  \r
:::  \r
  \r
::: hljs-right  \r
\`::: hljs-right\`  \r
\`right\`  \r
\`:::\`  \r
:::  \r
  \r
## Список определений\r
  \r
Термин 1  \r
  \r
:   Определение 1  \r
  \r
Термин  2 с использованием *разметки*\r
  \r
:   Определение 2  \r
  \r
 { Какой-нибудь код, часть определения 2 }  \r
 Третий параграф определения 2.  \r
\`\`\`  \r
Термин 1  \r
  \r
:   Определение 1  \r
  \r
Термин  2 с использованием *разметки*\r
  \r
:   Определение 2  \r
  \r
 { Какой-нибудь код, часть определения 2 }  \r
 Третий параграф определения 2.  \r
\`\`\`  \r
  \r
## Сокращения\r
*[HTML]: Hyper Text Markup Language  \r
*[W3C]:  World Wide Web Consortium  \r
The HTML specification  \r
is maintained by the W3C.  \r
\`\`\`  \r
*[HTML]: Hyper Text Markup Language  \r
*[W3C]:  World Wide Web Consortium  \r
The HTML specification  \r
is maintained by the W3C.  \r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](Catalog)\r
\r
Markdown Handbuch\r
===\r
> Details: [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **Fett**\r
\`\`\`\r
**fett**\r
__fett__\r
\`\`\`\r
## *Kursiv*\r
\`\`\`\r
*kursiv*\r
_kursiv_\r
\`\`\`\r
## Überschriften\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## Trennlinien\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^Hoch^gestellt & ~Tief~gestellt\r
\`\`\`\r
hochgestellt x^2^\r
tiefgestellt H~2~0\r
\`\`\`\r
## ++Unterstrichen++ & ~~Durchgestrichen~~\r
\`\`\`\r
++unterstrichen++\r
~~durchgestrichen~~\r
\`\`\`\r
## ==Markiert==\r
\`\`\`\r
==markiert==\r
\`\`\`\r
## Zitat\r
\r
\`\`\`\r
> zitat 1\r
>> zitat 2\r
>>> zitat 3\r
...\r
\`\`\`\r
\r
## Liste\r
\`\`\`\r
ol\r
1.\r
2.\r
3.\r
...\r
\r
ul\r
-\r
-\r
...\r
\`\`\`\r
\r
## Todo Liste\r
\r
- [x] aufgabe 1\r
- [ ] aufgabe 2\r
\r
\`\`\`\r
- [x] aufgabe 1\r
- [ ] aufgabe 2\r
\`\`\`\r
\r
## Link\r
\`\`\`\r
Text Link\r
[Text](www.baidu.com)\r
\r
Link mit Bild\r
![Text](http://www.image.com)\r
\`\`\`\r
## Code\r
\\\`\`\` Typ\r
\r
Codeblock\r
\r
\\\`\`\`\r
\r
\\\` code \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## Tabelle\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| links | mitte | rechts |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| links | mitte | rechts |\r
| ---------------------- | ------------- | ----------------- |\r
## Fußnote\r
\`\`\`\r
hallo[^hallo]\r
\`\`\`\r
\r
Schau zum unteren Rand[^hallo]\r
\r
[^hallo]: fussnote\r
\r
## Emojis\r
Details: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ Mathematik\r
\r
Formeln lassen sich darstellen z.b. ：$x_i + y_i = z_i$ und $\\sum_{i=1}^n a_i=0$\r
Formeln können auf einer eigenen Zeile gerendert werden\r
$$\\sum_{i=1}^n a_i=0$$\r
Details: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php)和[katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)以及[latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## Layout\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`links\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`mitte\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`rechts\`\r
\`:::\`\r
:::\r
\r
## Liste von Definitionen\r
\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 mit *inline markup*\r
\r
:   Definition 2\r
\r
        { ein wenig code, teil von Definition 2 }\r
\r
    Dritter Absatz von Definition 2.\r
\r
\`\`\`\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 mit *inline markup*\r
\r
:   Definition 2\r
\r
        { ein wenig code, teil von Definition 2 }\r
\r
    Dritter Absatz von Definition 2.\r
\r
\`\`\`\r
\r
## Abkürzungen\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
Die HTML Spezifikation\r
wird gepflegt vom W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
Die HTML Spezifikation\r
wird gepflegt vom W3C.\r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](目次)\r
\r
Markdown 文法ガイド\r
===\r
> Detailed: [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **太字**\r
\`\`\`\r
**太字**\r
__太字__\r
\`\`\`\r
## *斜体*\r
\`\`\`\r
*斜体*\r
_斜体_\r
\`\`\`\r
## 見出し\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## 横線\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^上付き^文字 & ~下付き~文字\r
\`\`\`\r
super x^2^\r
sub H~2~0\r
\`\`\`\r
## ++下線++ & ~~取り消し線~~\r
\`\`\`\r
++underline++\r
~~strikethrough~~\r
\`\`\`\r
## ==蛍光ペン==\r
\`\`\`\r
==mark==\r
\`\`\`\r
## 引用\r
\r
\`\`\`\r
> quote 1\r
>> quote 2\r
>>> quote 3\r
...\r
\`\`\`\r
\r
## リスト\r
\`\`\`\r
番号付きリスト\r
1.\r
2.\r
3.\r
...\r
\r
箇条書きリスト\r
-\r
-\r
...\r
\`\`\`\r
\r
## Todoリスト\r
\r
- [x] task 1\r
- [ ] task 2\r
\r
\`\`\`\r
- [x] task 1\r
- [ ] task 2\r
\`\`\`\r
\r
## リンク\r
\`\`\`\r
Text Link\r
[Text](www.baidu.com)\r
\r
Image Link\r
![Text](http://www.image.com)\r
\`\`\`\r
## コード\r
\\\`\`\` type\r
\r
code block\r
\r
\\\`\`\`\r
\r
\\\` code \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## 表\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
| ---------------------- | ------------- | ----------------- |\r
\r
## 脚注\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
Look at the bottom[^hello]\r
\r
[^hello]: footnote\r
\r
## 絵文字\r
> Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ 数式\r
> Detailed: [KaTeXマニュアル](http://www.intmath.com/cg5/katex-mathjax-comparison.php)、[KaTeX function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX)、[LaTeXマニュアル](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
We can render formulas for example：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$  \r
We can also single-line rendering\r
$$\\sum_{i=1}^n a_i=0$$\r
\r
## レイアウト\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`left\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`center\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`right\`\r
\`:::\`\r
:::\r
\r
## 定義リスト\r
\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
`},function(e,t){e.exports=`@[toc](Catalog)\r
\r
\r
마크다운 가이드\r
===\r
> 자세히 보기: [http://commonmark.org/help/](http://commonmark.org/help/)\r
\r
## **볼드체(굵게)**\r
\`\`\`\r
**볼드체**\r
__볼드체__\r
\`\`\`\r
## *이탈릭체(기울임꼴)*\r
\`\`\`\r
*이탈릭체*\r
_이탈릭체_\r
\`\`\`\r
## 헤더\r
\`\`\`\r
# h1 #\r
h1\r
====\r
## h2 ##\r
h2\r
----\r
### h3 ###\r
#### h4 ####\r
##### h5 #####\r
###### h6 ######\r
\`\`\`\r
## 구분선\r
\`\`\`\r
***\r
---\r
\`\`\`\r
****\r
## ^위^첨자 & ~아래~첨자\r
\`\`\`\r
위첨자 x^2^\r
아래첨자 H~2~0\r
\`\`\`\r
## ++밑줄++ & ~~취소선~~\r
\`\`\`\r
++밑줄++\r
~~취소선~~\r
\`\`\`\r
## ==마커==\r
\`\`\`\r
==마커==\r
\`\`\`\r
## 인용\r
\r
\`\`\`\r
> quote 1\r
>> quote 2\r
>>> quote 3\r
...\r
\`\`\`\r
\r
## 리스트\r
\`\`\`\r
ol\r
1.\r
2.\r
3.\r
...\r
\r
ul\r
-\r
-\r
...\r
\`\`\`\r
\r
## 할일\r
\r
- [x] task 1\r
- [ ] task 2\r
\r
\`\`\`\r
- [x] task 1\r
- [ ] task 2\r
\`\`\`\r
\r
## 링크\r
\`\`\`\r
텍스트 링크\r
[Text](www.baidu.com)\r
\r
이미지 링크\r
![Text](http://www.image.com)\r
\`\`\`\r
## 코드블럭\r
\\\`\`\` type\r
\r
code block\r
\r
\\\`\`\`\r
\r
\\\` code \\\`\r
\r
\`\`\`c++\r
int main()\r
{\r
    printf("hello world!");\r
}\r
\`\`\`\r
\`code\`\r
\r
## 테이블\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
\`\`\`\r
| th1 | th2 | th3 |\r
| :--  | :--: | ----: |\r
| left | center | right |\r
| ---------------------- | ------------- | ----------------- |\r
## Footnote\r
\`\`\`\r
hello[^hello]\r
\`\`\`\r
\r
Look at the bottom[^hello]\r
\r
[^hello]: footnote\r
\r
## 이모티콘\r
Detailed: [https://www.webpagefx.com/tools/emoji-cheat-sheet/](https://www.webpagefx.com/tools/emoji-cheat-sheet/)\r
\`\`\`\r
:laughing:\r
:blush:\r
:smiley:\r
:)\r
...\r
\`\`\`\r
:laughing::blush::smiley::)\r
\r
## $\\KaTeX$ 수학공식\r
\r
우리는 다음과 같이 함수를 만들 수 있습니다.：$x_i + y_i = z_i$ and $\\sum_{i=1}^n a_i=0$\r
단일 항으로도 가능합니다.\r
$$\\sum_{i=1}^n a_i=0$$\r
Detailed: [katex](http://www.intmath.com/cg5/katex-mathjax-comparison.php) and [katex function](https://github.com/Khan/KaTeX/wiki/Function-Support-in-KaTeX) and [latex](https://math.meta.stackexchange.com/questions/5020/mathjax-basic-tutorial-and-quick-reference)\r
\r
## 레이아웃\r
\r
::: hljs-left\r
\`::: hljs-left\`\r
\`left\`\r
\`:::\`\r
:::\r
\r
::: hljs-center\r
\`::: hljs-center\`\r
\`center\`\r
\`:::\`\r
:::\r
\r
::: hljs-right\r
\`::: hljs-right\`\r
\`right\`\r
\`:::\`\r
:::\r
\r
## deflist\r
\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
Term 1\r
\r
:   Definition 1\r
\r
Term 2 with *inline markup*\r
\r
:   Definition 2\r
\r
        { some code, part of Definition 2 }\r
\r
    Third paragraph of definition 2.\r
\r
\`\`\`\r
\r
## abbr\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
*[HTML]: Hyper Text Markup Language\r
*[W3C]:  World Wide Web Consortium\r
The HTML specification\r
is maintained by the W3C.\r
\`\`\`\r
`},function(e){e.exports=JSON.parse(`{"start_editor":"开始编辑...","navigation_title":"导航目录","tl_bold":"粗体","tl_italic":"斜体","tl_header":"标题","tl_header_one":"一级标题","tl_header_two":"二级标题","tl_header_three":"三级标题","tl_header_four":"四级标题","tl_header_five":"五级标题","tl_header_six":"六级标题","tl_underline":"下划线","tl_strikethrough":"中划线","tl_mark":"标记","tl_superscript":"上角标","tl_subscript":"下角标","tl_quote":"段落引用","tl_ol":"有序列表","tl_ul":"无序列表","tl_link":"链接","tl_image":"添加图片链接","tl_code":"代码块","tl_table":"表格","tl_undo":"上一步","tl_redo":"下一步","tl_trash":"清空","tl_save":"保存","tl_navigation_on":"开启标题导航","tl_navigation_off":"关闭标题导航","tl_preview":"预览","tl_aligncenter":"居中","tl_alignleft":"居左","tl_alignright":"居右","tl_edit":"编辑","tl_single_column":"单栏","tl_double_column":"双栏","tl_fullscreen_on":"全屏编辑","tl_fullscreen_off":"退出全屏","tl_read":"沉浸式阅读","tl_html_on":"查看html文本","tl_html_off":"返回markdown文本","tl_help":"markdown语法帮助","tl_upload":"上传图片","tl_upload_remove":"删除","tl_popup_link_title":"添加链接","tl_popup_link_text":"链接描述","tl_popup_link_addr":"链接地址","tl_popup_img_link_title":"添加图片","tl_popup_img_link_text":"图片描述","tl_popup_img_link_addr":"图片链接","tl_popup_link_sure":"确定","tl_popup_link_cancel":"取消"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"開始編輯...","navigation_title":"導航目錄","tl_bold":"粗體","tl_italic":"斜體","tl_header":"標題","tl_header_one":"一級標題","tl_header_two":"二級標題","tl_header_three":"三級標題","tl_header_four":"四級標題","tl_header_five":"五級標題","tl_header_six":"六級標題","tl_underline":"下劃線","tl_strikethrough":"中劃線","tl_mark":"標記","tl_superscript":"上角標","tl_subscript":"下角標","tl_quote":"段落引用","tl_ol":"有序列表","tl_ul":"無序列表","tl_link":"鏈接","tl_image":"添加圖片鏈接","tl_code":"代碼塊","tl_table":"表格","tl_undo":"上一步","tl_redo":"下一步","tl_trash":"清空","tl_save":"保存","tl_navigation_on":"開啟標題導航","tl_navigation_off":"關閉標題導航","tl_preview":"預覽","tl_aligncenter":"居中","tl_alignleft":"居左","tl_alignright":"居右","tl_edit":"編輯","tl_single_column":"單欄","tl_double_column":"雙欄","tl_fullscreen_on":"全屏編輯","tl_fullscreen_off":"退出全屏","tl_read":"沈浸式閱讀","tl_html_on":"查看html文本","tl_html_off":"返回markdown文本","tl_help":"markdown語法幫助","tl_upload":"上傳圖片","tl_upload_remove":"刪除","tl_popup_link_title":"添加鏈接","tl_popup_link_text":"鏈接描述","tl_popup_link_addr":"鏈接地址","tl_popup_img_link_title":"添加圖片","tl_popup_img_link_text":"圖片描述","tl_popup_img_link_addr":"圖片鏈接","tl_popup_link_sure":"確定","tl_popup_link_cancel":"取消"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"Begin editing...","navigation_title":"Navigation","tl_bold":"Bold","tl_italic":"Italic","tl_header":"Header","tl_header_one":"Header 1","tl_header_two":"Header 2","tl_header_three":"Header 3","tl_header_four":"Header 4","tl_header_five":"Header 5","tl_header_six":"Header 6","tl_underline":"Underline","tl_strikethrough":"Strikethrough","tl_mark":"Mark","tl_superscript":"Superscript","tl_subscript":"Subscript","tl_quote":"Quote","tl_ol":"Ol","tl_ul":"Ul","tl_link":"Link","tl_image":"Image Link","tl_code":"Code","tl_table":"Table","tl_undo":"Undo","tl_redo":"Redo","tl_trash":"Trash","tl_save":"Save","tl_navigation_on":"Navigation ON","tl_navigation_off":"Navigation OFF","tl_preview":"Preview","tl_aligncenter":"Center text","tl_alignleft":"Clamp text to the left","tl_alignright":"Clamp text to the right","tl_edit":"Edit","tl_single_column":"Single Column","tl_double_column":"Double Columns","tl_fullscreen_on":"FullScreen ON","tl_fullscreen_off":"FullScreen OFF","tl_read":"Read Model","tl_html_on":"HTML ON","tl_html_off":"HTML OFF","tl_help":"Markdown Guide","tl_upload":"Upload Images","tl_upload_remove":"Remove","tl_popup_link_title":"Add Link","tl_popup_link_text":"Link text","tl_popup_link_addr":"Link address","tl_popup_img_link_title":"Add Image","tl_popup_img_link_text":"Image Text","tl_popup_img_link_addr":"Image Link","tl_popup_link_sure":"Sure","tl_popup_link_cancel":"Cancel"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"Début d'édition...","navigation_title":"Navigation","tl_bold":"Gras","tl_italic":"Italique","tl_header":"Entête","tl_header_one":"Entête 1","tl_header_two":"Entête 2","tl_header_three":"Entête 3","tl_header_four":"Entête 4","tl_header_five":"Entête 5","tl_header_six":"Entête 6","tl_underline":"Souligné","tl_strikethrough":"Barré","tl_mark":"Mark","tl_superscript":"Exposant","tl_subscript":"Sous-exposant","tl_quote":"Quote","tl_ol":"Liste ","tl_ul":"Puce","tl_link":"Lien","tl_image":"Image Lien","tl_code":"Code","tl_table":"Table","tl_undo":"Annuler","tl_redo":"Refaire","tl_trash":"Supprimer","tl_save":"Sauver","tl_navigation_on":"Activer la navigation","tl_navigation_off":"Désactiver le navigation","tl_preview":"Previsualisé","tl_aligncenter":"Center le texte","tl_alignleft":"Férer le texte à gauche","tl_alignright":"Férer le texte à droite","tl_edit":"Editer","tl_single_column":"Seule Colonne","tl_double_column":"Colonnes Doubles","tl_fullscreen_on":"Activer le mode plein écran","tl_fullscreen_off":"Désactiver le mode plein écran","tl_read":"Lire le modèle","tl_html_on":"Activer le mode HTML","tl_html_off":"Désactiver le mode HTML","tl_help":"Guide Markdown","tl_upload":"Télécharger les images","tl_upload_remove":"Supprimer","tl_popup_link_title":"Ajouter un lien","tl_popup_link_text":"Description","tl_popup_link_addr":"Link","tl_popup_img_link_title":"Ajouter une image","tl_popup_img_link_text":"Description","tl_popup_img_link_addr":"Link","tl_popup_link_sure":"sûr","tl_popup_link_cancel":"Annuler"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"Começar edição...","navigation_title":"Navegação","tl_bold":"Negrito","tl_italic":"Itálico","tl_header":"Cabeçalho","tl_header_one":"Cabeçalho 1","tl_header_two":"Cabeçalho 2","tl_header_three":"Cabeçalho 3","tl_header_four":"Cabeçalho 4","tl_header_five":"Cabeçalho 5","tl_header_six":"Cabeçalho 6","tl_underline":"Sublinhar","tl_strikethrough":"Tachar","tl_mark":"Marcação","tl_superscript":"Sobrescrito","tl_subscript":"Subscrito","tl_quote":"Citação","tl_ol":"Lista Numerada","tl_ul":"Lista com marcadores","tl_link":"Link","tl_image":"Link de imagem","tl_code":"Código","tl_table":"Tabela","tl_undo":"Desfazer","tl_redo":"Refazer","tl_trash":"Lixo","tl_save":"Salvar","tl_navigation_on":"Mostrar Navegação","tl_navigation_off":"Esconder Navegação","tl_preview":"Preview","tl_aligncenter":"Alinhar no centro","tl_alignleft":"Alinhar à esquerda","tl_alignright":"Alinhar à direita","tl_edit":"Editar","tl_single_column":"Coluna Única","tl_double_column":"Duas Colunas","tl_fullscreen_on":"Ligar Tela Cheia","tl_fullscreen_off":"Desligar Tela Cheia","tl_read":"Modo de Leitura","tl_html_on":"Ligar HTML","tl_html_off":"Desligar HTML","tl_help":"Guia Markdown","tl_upload":"Upload de Imagens","tl_upload_remove":"Remover","tl_popup_link_title":"Adicionar Link","tl_popup_link_text":"Descrição","tl_popup_link_addr":"Link","tl_popup_img_link_title":"Adicionar fotos","tl_popup_img_link_text":"Descrição","tl_popup_img_link_addr":"Link","tl_popup_link_sure":"Confirmar","tl_popup_link_cancel":"Cancelar"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"Начните редактирование...","navigation_title":"Навигация","tl_bold":"Полужирный","tl_italic":"Курсив","tl_header":"Заголовки","tl_header_one":"Заголовок 1","tl_header_two":"Заголовок 2","tl_header_three":"Заголовок 3","tl_header_four":"Заголовок 4","tl_header_five":"Заголовок 5","tl_header_six":"Заголовок 6","tl_underline":"Подчеркнутый","tl_strikethrough":"Зачеркнутый","tl_mark":"Отметка","tl_superscript":"Верхний индекс","tl_subscript":"Нижний индекс","tl_quote":"Цитата","tl_ol":"Нумерованный список","tl_ul":"Список","tl_link":"Ссылка","tl_image":"Ссылка изображения","tl_code":"Код","tl_table":"Таблица","tl_undo":"Отменить","tl_redo":"Вернуть","tl_trash":"Удалить","tl_save":"Сохранить","tl_navigation_on":"Показать навигацию","tl_navigation_off":"Скрыть навигацию","tl_preview":"Предпросмотр","tl_aligncenter":"Выровнять по центру","tl_alignleft":"Выровнять по левому краю","tl_alignright":"Выровнять по правому краю","tl_edit":"Редактор","tl_single_column":"Одно поле","tl_double_column":"Два поля","tl_fullscreen_on":"Полноэкранный режим","tl_fullscreen_off":"Выключить полноэкранный режим","tl_read":"Режим чтения","tl_html_on":"Показать HTML","tl_html_off":"Убрать HTML","tl_help":"Markdown помощь","tl_upload":"Загрузить изображение","tl_upload_remove":"Удалить","tl_popup_link_title":"Добавить ссылку","tl_popup_link_text":"Текст ссылки","tl_popup_link_addr":"Адрес ссылки","tl_popup_img_link_title":"Локальное изображение","tl_popup_img_link_text":"Текст изображения","tl_popup_img_link_addr":"Ссылка изображения","tl_popup_link_sure":"Добавить","tl_popup_link_cancel":"Отменить"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"Bearbeitung beginnen...","navigation_title":"Navigation","tl_bold":"Fett","tl_italic":"Kursiv","tl_header":"Überschrift","tl_header_one":"Überschrift 1","tl_header_two":"Überschrift 2","tl_header_three":"Überschrift 3","tl_header_four":"Überschrift 4","tl_header_five":"Überschrift 5","tl_header_six":"Überschrift 6","tl_underline":"Unterstrichen","tl_strikethrough":"Durchgestrichen","tl_mark":"Markiert","tl_superscript":"Hochgestellt","tl_subscript":"Tiefgestellt","tl_quote":"Zitat","tl_ol":"Ol","tl_ul":"Ul","tl_link":"Link","tl_image":"Link mit Bild","tl_code":"Code","tl_table":"Tabelle","tl_undo":"Rückgängig","tl_redo":"Wiederherstellen","tl_trash":"Mülleimer","tl_save":"Speichern","tl_navigation_on":"Navigation AN","tl_navigation_off":"Navigation AUS","tl_preview":"Vorschau","tl_aligncenter":"Text zentrieren","tl_alignleft":"Nach links ausrichten","tl_alignright":"Nach rechts ausrichten","tl_edit":"Bearbeiten","tl_single_column":"Einspaltig","tl_double_column":"Zweispaltig","tl_fullscreen_on":"Vollbild AN","tl_fullscreen_off":"Vollbild AUS","tl_read":"Lesemodus","tl_html_on":"HTML AN","tl_html_off":"HTML AUS","tl_help":"Markdown Handbuch","tl_upload":"Bilder-Upload","tl_upload_remove":"Entfernen","tl_popup_link_title":"Link hinzufügen","tl_popup_link_text":"Text des Links","tl_popup_link_addr":"Linkziel","tl_popup_img_link_title":"Bild hinzufügen","tl_popup_img_link_text":"Text des Bildes","tl_popup_img_link_addr":"Link auf Bild","tl_popup_link_sure":"Ja","tl_popup_link_cancel":"Abbruch"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"編集を始めてね！","navigation_title":"ナビゲーション","tl_bold":"太字","tl_italic":"斜体","tl_header":"見出し","tl_header_one":"見出し1","tl_header_two":"見出し2","tl_header_three":"見出し3","tl_header_four":"見出し4","tl_header_five":"見出し5","tl_header_six":"見出し6","tl_underline":"下線","tl_strikethrough":"取り消し線","tl_mark":"蛍光ペン","tl_superscript":"上付き文字","tl_subscript":"下付き文字","tl_quote":"引用","tl_ol":"番号付きリスト","tl_ul":"箇条書きリスト","tl_link":"ハイパーリンク","tl_image":"画像のリンク","tl_code":"コードの挿入","tl_table":"表の挿入","tl_undo":"戻る","tl_redo":"進む","tl_trash":"削除","tl_save":"保存","tl_navigation_on":"ナビゲーションを表示","tl_navigation_off":"ナビゲーションを非表示","tl_preview":"プレビュー","tl_aligncenter":"中央揃え","tl_alignleft":"左揃え","tl_alignright":"右揃え","tl_edit":"編集","tl_single_column":"一列","tl_double_column":"二列","tl_fullscreen_on":"全画面表示","tl_fullscreen_off":"全画面表示の終了","tl_read":"モデルの読み込み","tl_html_on":"HTMLで表示","tl_html_off":"HTML表示の終了","tl_help":"ヘルプ","tl_upload":"画像をアップロード","tl_upload_remove":"画像の削除","tl_popup_link_title":"リンクの追加","tl_popup_link_text":"リンクテキスト","tl_popup_link_addr":"リンク先のURL","tl_popup_img_link_title":"画像の追加","tl_popup_img_link_text":"画像タイトル","tl_popup_img_link_addr":"画像URL","tl_popup_link_sure":"OK","tl_popup_link_cancel":"戻る"}`)},function(e){e.exports=JSON.parse(`{"start_editor":"작성해주세요.","navigation_title":"네비게이션","tl_bold":"굵게","tl_italic":"기울임꼴","tl_header":"헤더","tl_header_one":"헤더 1","tl_header_two":"헤더 2","tl_header_three":"헤더 3","tl_header_four":"헤더 4","tl_header_five":"헤더 5","tl_header_six":"헤더 6","tl_underline":"밑줄","tl_strikethrough":"취소선","tl_mark":"마커","tl_superscript":"위첨자","tl_subscript":"아래첨자","tl_quote":"Quote","tl_ol":"숫자 리스트","tl_ul":"점 리스트","tl_link":"링크","tl_image":"이미지 링크","tl_code":"코드블럭","tl_table":"테이블","tl_undo":"실행취소","tl_redo":"되돌리기","tl_trash":"삭제","tl_save":"저장","tl_navigation_on":"네비게이션 끔","tl_navigation_off":"네비게이션 켬","tl_preview":"미리보기","tl_aligncenter":"가운데 정렬","tl_alignleft":"왼쪽 정렬","tl_alignright":"오른쪽 정렬","tl_edit":"수정","tl_single_column":"단일 열","tl_double_column":"복수 열","tl_fullscreen_on":"전체화면 활성화","tl_fullscreen_off":"전체화면 종료","tl_read":"읽기모드","tl_html_on":"HTML 켬","tl_html_off":"HTML 끔","tl_help":"마크다운 가이드","tl_upload":"이미지 업로드","tl_upload_remove":"제거","tl_popup_link_title":"링크 삽입","tl_popup_link_text":"텍스트","tl_popup_link_addr":"링크주소","tl_popup_img_link_title":"이미지 추가","tl_popup_img_link_text":"이미지 이름","tl_popup_img_link_addr":"링크주소","tl_popup_link_sure":"삽입","tl_popup_link_cancel":"취소"}`)},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0});var r=s(n(25));t.initMarkdown=D;var i=s(n(56)),a=n(57),o=s(n(135));function s(e){return e&&e.__esModule?e:{default:e}}var c={html:!0,xhtmlOut:!0,breaks:!0,langPrefix:`lang-`,linkify:!1,typographer:!0,quotes:`“”‘’`},l=n(140),u=n(197),d=n(203),f=n(204),p=n(205),m=n(206),h=n(207),g=n(208),_=n(209),v=n(210),y=n(211),b=n(212),x=n(213),S=n(214),C=n(215),w={},T=[],E={hljs:`auto`,highlighted:!0,langCheck:function(e){e&&i.default[e]&&!w[e]&&(w[e]=1,T.push(i.default[e]))}};function D(){var e=new l(c),t=e.renderer.rules.link_open||function(e,t,n,r,i){return i.renderToken(e,t,n)};return e.renderer.rules.link_open=function(e,n,r,i,a){var o=e[n].attrIndex(`href`);if(e[n].attrs[o][1].startsWith(`#`))return t(e,n,r,i,a);var s=e[n].attrIndex(`target`);return s<0?e[n].attrPush([`target`,`_blank`]):e[n].attrs[s][1]=`_blank`,t(e,n,r,i,a)},e.use(x,E).use(u).use(f).use(d).use(y).use(y,`hljs-left`).use(y,`hljs-center`).use(y,`hljs-right`).use(p).use(m).use(h).use(g).use(_).use(y).use(C).use(S).use(v).use(b),e}t.default={data:function(){return{markdownIt:null}},created:function(){this.markdownIt=D(),this.html?(0,r.default)(this.xssOptions)===`object`&&this.markdownIt.use(o.default,this.xssOptions):(this.markdownIt.set({html:!1}),this.xssOptions=!1)},mounted:function(){E.highlighted=this.ishljs},methods:{$render:function(e,t){w={},T=[];var n=this.markdownIt.render(e);this.ishljs&&T.length>0&&this.$_render(e,t,n),t(n)},$_render:function(e,t,n){for(var r=this,i=0,o=0;o<T.length;o++){var s=r.p_external_link.hljs_lang(T[o]);(0,a.loadScript)(s,(function(){(i+=1)===T.length&&(n=r.markdownIt.render(e),t(n))}))}}},watch:{ishljs:function(e){E.highlighted=e}}}},function(e,t,n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=function(e,t){e.options.html&&(i=new r.FilterXSS(t),e.core.ruler.push(`mavoneditor_sanitizer`,a))};var r=n(136),i=void 0;function a(e){i&&function e(t,n){for(var r=void 0,a=void 0,o=0;o<t.length;o++)n.indexOf(t[o].type)!==-1&&(r=t[o].content,a=t[o].children,t[o].content=i.process(r),a&&a.length&&r!==t[o].content&&e(a,[`html_inline`]))}(e.tokens,[`inline`,`html_block`])}},function(e,t,n){var r=n(58),i=n(61),a=n(139);function o(e,t){return new a(t).process(e)}(t=e.exports=o).filterXSS=o,t.FilterXSS=a,function(){for(var e in r)t[e]=r[e];for(var n in i)t[n]=i[n]}(),typeof window<`u`&&(window.filterXSS=e.exports),typeof self<`u`&&typeof DedicatedWorkerGlobalScope<`u`&&self instanceof DedicatedWorkerGlobalScope&&(self.filterXSS=e.exports)},function(e,t,n){var r=n(59),i=n(138);n(60);function a(e){return e==null}function o(e){(e=function(e){var t={};for(var n in e)t[n]=e[n];return t}(e||{})).whiteList=e.whiteList||r.whiteList,e.onAttr=e.onAttr||r.onAttr,e.onIgnoreAttr=e.onIgnoreAttr||r.onIgnoreAttr,e.safeAttrValue=e.safeAttrValue||r.safeAttrValue,this.options=e}o.prototype.process=function(e){if(!(e=(e||=``).toString()))return``;var t=this.options,n=t.whiteList,r=t.onAttr,o=t.onIgnoreAttr,s=t.safeAttrValue;return i(e,(function(e,t,i,c,l){var u=n[i],d=!1;if(!0===u?d=u:typeof u==`function`?d=u(c):u instanceof RegExp&&(d=u.test(c)),!0!==d&&(d=!1),c=s(i,c)){var f,p={position:t,sourcePosition:e,source:l,isWhite:d};return d?a(f=r(i,c,p))?i+`:`+c:f:a(f=o(i,c,p))?void 0:f}}))},e.exports=o},function(e,t,n){var r=n(60);e.exports=function(e,t){(e=r.trimRight(e))[e.length-1]!==`;`&&(e+=`;`);var n=e.length,i=!1,a=0,o=0,s=``;function c(){if(!i){var n=r.trim(e.slice(a,o)),c=n.indexOf(`:`);if(c!==-1){var l=r.trim(n.slice(0,c)),u=r.trim(n.slice(c+1));if(l){var d=t(a,s.length,l,u,n);d&&(s+=d+`; `)}}}a=o+1}for(;o<n;o++){var l=e[o];if(l===`/`&&e[o+1]===`*`){var u=e.indexOf(`*/`,o+2);if(u===-1)break;a=(o=u+1)+1,i=!1}else l===`(`?i=!0:l===`)`?i=!1:l===`;`?i||c():l===`
`&&c()}return r.trim(s)}},function(e,t,n){var r=n(38).FilterCSS,i=n(58),a=n(61),o=a.parseTag,s=a.parseAttr,c=n(39);function l(e){return e==null}function u(e){(e=function(e){var t={};for(var n in e)t[n]=e[n];return t}(e||{})).stripIgnoreTag&&(e.onIgnoreTag&&console.error(`Notes: cannot use these two options "stripIgnoreTag" and "onIgnoreTag" at the same time`),e.onIgnoreTag=i.onIgnoreTagStripAll),e.whiteList||e.allowList?e.whiteList=function(e){var t={};for(var n in e)Array.isArray(e[n])?t[n.toLowerCase()]=e[n].map((function(e){return e.toLowerCase()})):t[n.toLowerCase()]=e[n];return t}(e.whiteList||e.allowList):e.whiteList=i.whiteList,this.attributeWrapSign=!0===e.singleQuotedAttributeValue?`'`:i.attributeWrapSign,e.onTag=e.onTag||i.onTag,e.onTagAttr=e.onTagAttr||i.onTagAttr,e.onIgnoreTag=e.onIgnoreTag||i.onIgnoreTag,e.onIgnoreTagAttr=e.onIgnoreTagAttr||i.onIgnoreTagAttr,e.safeAttrValue=e.safeAttrValue||i.safeAttrValue,e.escapeHtml=e.escapeHtml||i.escapeHtml,this.options=e,!1===e.css?this.cssFilter=!1:(e.css=e.css||{},this.cssFilter=new r(e.css))}u.prototype.process=function(e){if(!(e=(e||=``).toString()))return``;var t=this.options,n=t.whiteList,r=t.onTag,a=t.onIgnoreTag,u=t.onTagAttr,d=t.onIgnoreTagAttr,f=t.safeAttrValue,p=t.escapeHtml,m=this.attributeWrapSign,h=this.cssFilter;t.stripBlankChar&&(e=i.stripBlankChar(e)),t.allowCommentTag||(e=i.stripCommentTag(e));var g=!1;t.stripIgnoreTagBody&&(g=i.StripTagBody(t.stripIgnoreTagBody,a),a=g.onIgnoreTag);var _=o(e,(function(e,t,i,o,g){var _={sourcePosition:e,position:t,isClosing:g,isWhite:Object.prototype.hasOwnProperty.call(n,i)},v=r(i,o,_);if(!l(v))return v;if(_.isWhite){if(_.isClosing)return`</`+i+`>`;var y=function(e){var t=c.spaceIndex(e);if(t===-1)return{html:``,closing:e[e.length-2]===`/`};var n=(e=c.trim(e.slice(t+1,-1)))[e.length-1]===`/`;return n&&(e=c.trim(e.slice(0,-1))),{html:e,closing:n}}(o),b=n[i],x=s(y.html,(function(e,t){var n=c.indexOf(b,e)!==-1,r=u(i,e,t,n);return l(r)?n?(t=f(i,e,t,h))?e+`=`+m+t+m:e:l(r=d(i,e,t,n))?void 0:r:r}));return o=`<`+i,x&&(o+=` `+x),y.closing&&(o+=` /`),o+=`>`}return l(v=a(i,o,_))?p(o):v}),p);return g&&(_=g.remove(_)),_},e.exports=u},function(e,t,n){e.exports=n(141)},function(e,t,n){var r=n(1),i=n(149),a=n(153),o=n(154),s=n(162),c=n(176),l=n(189),u=n(63),d=n(191),f={default:n(194),zero:n(195),commonmark:n(196)},p=/^(vbscript|javascript|file|data):/,m=/^data:image\/(gif|png|jpeg|webp);/;function h(e){var t=e.trim().toLowerCase();return!p.test(t)||!!m.test(t)}var g=[`http:`,`https:`,`mailto:`];function _(e){var t=u.parse(e,!0);if(t.hostname&&(!t.protocol||g.indexOf(t.protocol)>=0))try{t.hostname=d.toASCII(t.hostname)}catch{}return u.encode(u.format(t))}function v(e){var t=u.parse(e,!0);if(t.hostname&&(!t.protocol||g.indexOf(t.protocol)>=0))try{t.hostname=d.toUnicode(t.hostname)}catch{}return u.decode(u.format(t))}function y(e,t){if(!(this instanceof y))return new y(e,t);t||r.isString(e)||(t=e||{},e=`default`),this.inline=new c,this.block=new s,this.core=new o,this.renderer=new a,this.linkify=new l,this.validateLink=h,this.normalizeLink=_,this.normalizeLinkText=v,this.utils=r,this.helpers=r.assign({},i),this.options={},this.configure(e),t&&this.set(t)}y.prototype.set=function(e){return r.assign(this.options,e),this},y.prototype.configure=function(e){var t,n=this;if(r.isString(e)&&!(e=f[t=e]))throw Error('Wrong `markdown-it` preset "'+t+`", check name`);if(!e)throw Error("Wrong `markdown-it` preset, can't be empty");return e.options&&n.set(e.options),e.components&&Object.keys(e.components).forEach((function(t){e.components[t].rules&&n[t].ruler.enableOnly(e.components[t].rules),e.components[t].rules2&&n[t].ruler2.enableOnly(e.components[t].rules2)})),this},y.prototype.enable=function(e,t){var n=[];Array.isArray(e)||(e=[e]),[`core`,`block`,`inline`].forEach((function(t){n=n.concat(this[t].ruler.enable(e,!0))}),this),n=n.concat(this.inline.ruler2.enable(e,!0));var r=e.filter((function(e){return n.indexOf(e)<0}));if(r.length&&!t)throw Error(`MarkdownIt. Failed to enable unknown rule(s): `+r);return this},y.prototype.disable=function(e,t){var n=[];Array.isArray(e)||(e=[e]),[`core`,`block`,`inline`].forEach((function(t){n=n.concat(this[t].ruler.disable(e,!0))}),this),n=n.concat(this.inline.ruler2.disable(e,!0));var r=e.filter((function(e){return n.indexOf(e)<0}));if(r.length&&!t)throw Error(`MarkdownIt. Failed to disable unknown rule(s): `+r);return this},y.prototype.use=function(e){var t=[this].concat(Array.prototype.slice.call(arguments,1));return e.apply(e,t),this},y.prototype.parse=function(e,t){if(typeof e!=`string`)throw Error(`Input data should be a String`);var n=new this.core.State(e,this,t);return this.core.process(n),n.tokens},y.prototype.render=function(e,t){return t||={},this.renderer.render(this.parse(e,t),this.options,t)},y.prototype.parseInline=function(e,t){var n=new this.core.State(e,this,t);return n.inlineMode=!0,this.core.process(n),n.tokens},y.prototype.renderInline=function(e,t){return t||={},this.renderer.render(this.parseInline(e,t),this.options,t)},e.exports=y},function(e){e.exports=JSON.parse('{"Aacute":"Á","aacute":"á","Abreve":"Ă","abreve":"ă","ac":"∾","acd":"∿","acE":"∾̳","Acirc":"Â","acirc":"â","acute":"´","Acy":"А","acy":"а","AElig":"Æ","aelig":"æ","af":"⁡","Afr":"𝔄","afr":"𝔞","Agrave":"À","agrave":"à","alefsym":"ℵ","aleph":"ℵ","Alpha":"Α","alpha":"α","Amacr":"Ā","amacr":"ā","amalg":"⨿","amp":"&","AMP":"&","andand":"⩕","And":"⩓","and":"∧","andd":"⩜","andslope":"⩘","andv":"⩚","ang":"∠","ange":"⦤","angle":"∠","angmsdaa":"⦨","angmsdab":"⦩","angmsdac":"⦪","angmsdad":"⦫","angmsdae":"⦬","angmsdaf":"⦭","angmsdag":"⦮","angmsdah":"⦯","angmsd":"∡","angrt":"∟","angrtvb":"⊾","angrtvbd":"⦝","angsph":"∢","angst":"Å","angzarr":"⍼","Aogon":"Ą","aogon":"ą","Aopf":"𝔸","aopf":"𝕒","apacir":"⩯","ap":"≈","apE":"⩰","ape":"≊","apid":"≋","apos":"\'","ApplyFunction":"⁡","approx":"≈","approxeq":"≊","Aring":"Å","aring":"å","Ascr":"𝒜","ascr":"𝒶","Assign":"≔","ast":"*","asymp":"≈","asympeq":"≍","Atilde":"Ã","atilde":"ã","Auml":"Ä","auml":"ä","awconint":"∳","awint":"⨑","backcong":"≌","backepsilon":"϶","backprime":"‵","backsim":"∽","backsimeq":"⋍","Backslash":"∖","Barv":"⫧","barvee":"⊽","barwed":"⌅","Barwed":"⌆","barwedge":"⌅","bbrk":"⎵","bbrktbrk":"⎶","bcong":"≌","Bcy":"Б","bcy":"б","bdquo":"„","becaus":"∵","because":"∵","Because":"∵","bemptyv":"⦰","bepsi":"϶","bernou":"ℬ","Bernoullis":"ℬ","Beta":"Β","beta":"β","beth":"ℶ","between":"≬","Bfr":"𝔅","bfr":"𝔟","bigcap":"⋂","bigcirc":"◯","bigcup":"⋃","bigodot":"⨀","bigoplus":"⨁","bigotimes":"⨂","bigsqcup":"⨆","bigstar":"★","bigtriangledown":"▽","bigtriangleup":"△","biguplus":"⨄","bigvee":"⋁","bigwedge":"⋀","bkarow":"⤍","blacklozenge":"⧫","blacksquare":"▪","blacktriangle":"▴","blacktriangledown":"▾","blacktriangleleft":"◂","blacktriangleright":"▸","blank":"␣","blk12":"▒","blk14":"░","blk34":"▓","block":"█","bne":"=⃥","bnequiv":"≡⃥","bNot":"⫭","bnot":"⌐","Bopf":"𝔹","bopf":"𝕓","bot":"⊥","bottom":"⊥","bowtie":"⋈","boxbox":"⧉","boxdl":"┐","boxdL":"╕","boxDl":"╖","boxDL":"╗","boxdr":"┌","boxdR":"╒","boxDr":"╓","boxDR":"╔","boxh":"─","boxH":"═","boxhd":"┬","boxHd":"╤","boxhD":"╥","boxHD":"╦","boxhu":"┴","boxHu":"╧","boxhU":"╨","boxHU":"╩","boxminus":"⊟","boxplus":"⊞","boxtimes":"⊠","boxul":"┘","boxuL":"╛","boxUl":"╜","boxUL":"╝","boxur":"└","boxuR":"╘","boxUr":"╙","boxUR":"╚","boxv":"│","boxV":"║","boxvh":"┼","boxvH":"╪","boxVh":"╫","boxVH":"╬","boxvl":"┤","boxvL":"╡","boxVl":"╢","boxVL":"╣","boxvr":"├","boxvR":"╞","boxVr":"╟","boxVR":"╠","bprime":"‵","breve":"˘","Breve":"˘","brvbar":"¦","bscr":"𝒷","Bscr":"ℬ","bsemi":"⁏","bsim":"∽","bsime":"⋍","bsolb":"⧅","bsol":"\\\\","bsolhsub":"⟈","bull":"•","bullet":"•","bump":"≎","bumpE":"⪮","bumpe":"≏","Bumpeq":"≎","bumpeq":"≏","Cacute":"Ć","cacute":"ć","capand":"⩄","capbrcup":"⩉","capcap":"⩋","cap":"∩","Cap":"⋒","capcup":"⩇","capdot":"⩀","CapitalDifferentialD":"ⅅ","caps":"∩︀","caret":"⁁","caron":"ˇ","Cayleys":"ℭ","ccaps":"⩍","Ccaron":"Č","ccaron":"č","Ccedil":"Ç","ccedil":"ç","Ccirc":"Ĉ","ccirc":"ĉ","Cconint":"∰","ccups":"⩌","ccupssm":"⩐","Cdot":"Ċ","cdot":"ċ","cedil":"¸","Cedilla":"¸","cemptyv":"⦲","cent":"¢","centerdot":"·","CenterDot":"·","cfr":"𝔠","Cfr":"ℭ","CHcy":"Ч","chcy":"ч","check":"✓","checkmark":"✓","Chi":"Χ","chi":"χ","circ":"ˆ","circeq":"≗","circlearrowleft":"↺","circlearrowright":"↻","circledast":"⊛","circledcirc":"⊚","circleddash":"⊝","CircleDot":"⊙","circledR":"®","circledS":"Ⓢ","CircleMinus":"⊖","CirclePlus":"⊕","CircleTimes":"⊗","cir":"○","cirE":"⧃","cire":"≗","cirfnint":"⨐","cirmid":"⫯","cirscir":"⧂","ClockwiseContourIntegral":"∲","CloseCurlyDoubleQuote":"”","CloseCurlyQuote":"’","clubs":"♣","clubsuit":"♣","colon":":","Colon":"∷","Colone":"⩴","colone":"≔","coloneq":"≔","comma":",","commat":"@","comp":"∁","compfn":"∘","complement":"∁","complexes":"ℂ","cong":"≅","congdot":"⩭","Congruent":"≡","conint":"∮","Conint":"∯","ContourIntegral":"∮","copf":"𝕔","Copf":"ℂ","coprod":"∐","Coproduct":"∐","copy":"©","COPY":"©","copysr":"℗","CounterClockwiseContourIntegral":"∳","crarr":"↵","cross":"✗","Cross":"⨯","Cscr":"𝒞","cscr":"𝒸","csub":"⫏","csube":"⫑","csup":"⫐","csupe":"⫒","ctdot":"⋯","cudarrl":"⤸","cudarrr":"⤵","cuepr":"⋞","cuesc":"⋟","cularr":"↶","cularrp":"⤽","cupbrcap":"⩈","cupcap":"⩆","CupCap":"≍","cup":"∪","Cup":"⋓","cupcup":"⩊","cupdot":"⊍","cupor":"⩅","cups":"∪︀","curarr":"↷","curarrm":"⤼","curlyeqprec":"⋞","curlyeqsucc":"⋟","curlyvee":"⋎","curlywedge":"⋏","curren":"¤","curvearrowleft":"↶","curvearrowright":"↷","cuvee":"⋎","cuwed":"⋏","cwconint":"∲","cwint":"∱","cylcty":"⌭","dagger":"†","Dagger":"‡","daleth":"ℸ","darr":"↓","Darr":"↡","dArr":"⇓","dash":"‐","Dashv":"⫤","dashv":"⊣","dbkarow":"⤏","dblac":"˝","Dcaron":"Ď","dcaron":"ď","Dcy":"Д","dcy":"д","ddagger":"‡","ddarr":"⇊","DD":"ⅅ","dd":"ⅆ","DDotrahd":"⤑","ddotseq":"⩷","deg":"°","Del":"∇","Delta":"Δ","delta":"δ","demptyv":"⦱","dfisht":"⥿","Dfr":"𝔇","dfr":"𝔡","dHar":"⥥","dharl":"⇃","dharr":"⇂","DiacriticalAcute":"´","DiacriticalDot":"˙","DiacriticalDoubleAcute":"˝","DiacriticalGrave":"`","DiacriticalTilde":"˜","diam":"⋄","diamond":"⋄","Diamond":"⋄","diamondsuit":"♦","diams":"♦","die":"¨","DifferentialD":"ⅆ","digamma":"ϝ","disin":"⋲","div":"÷","divide":"÷","divideontimes":"⋇","divonx":"⋇","DJcy":"Ђ","djcy":"ђ","dlcorn":"⌞","dlcrop":"⌍","dollar":"$","Dopf":"𝔻","dopf":"𝕕","Dot":"¨","dot":"˙","DotDot":"⃜","doteq":"≐","doteqdot":"≑","DotEqual":"≐","dotminus":"∸","dotplus":"∔","dotsquare":"⊡","doublebarwedge":"⌆","DoubleContourIntegral":"∯","DoubleDot":"¨","DoubleDownArrow":"⇓","DoubleLeftArrow":"⇐","DoubleLeftRightArrow":"⇔","DoubleLeftTee":"⫤","DoubleLongLeftArrow":"⟸","DoubleLongLeftRightArrow":"⟺","DoubleLongRightArrow":"⟹","DoubleRightArrow":"⇒","DoubleRightTee":"⊨","DoubleUpArrow":"⇑","DoubleUpDownArrow":"⇕","DoubleVerticalBar":"∥","DownArrowBar":"⤓","downarrow":"↓","DownArrow":"↓","Downarrow":"⇓","DownArrowUpArrow":"⇵","DownBreve":"̑","downdownarrows":"⇊","downharpoonleft":"⇃","downharpoonright":"⇂","DownLeftRightVector":"⥐","DownLeftTeeVector":"⥞","DownLeftVectorBar":"⥖","DownLeftVector":"↽","DownRightTeeVector":"⥟","DownRightVectorBar":"⥗","DownRightVector":"⇁","DownTeeArrow":"↧","DownTee":"⊤","drbkarow":"⤐","drcorn":"⌟","drcrop":"⌌","Dscr":"𝒟","dscr":"𝒹","DScy":"Ѕ","dscy":"ѕ","dsol":"⧶","Dstrok":"Đ","dstrok":"đ","dtdot":"⋱","dtri":"▿","dtrif":"▾","duarr":"⇵","duhar":"⥯","dwangle":"⦦","DZcy":"Џ","dzcy":"џ","dzigrarr":"⟿","Eacute":"É","eacute":"é","easter":"⩮","Ecaron":"Ě","ecaron":"ě","Ecirc":"Ê","ecirc":"ê","ecir":"≖","ecolon":"≕","Ecy":"Э","ecy":"э","eDDot":"⩷","Edot":"Ė","edot":"ė","eDot":"≑","ee":"ⅇ","efDot":"≒","Efr":"𝔈","efr":"𝔢","eg":"⪚","Egrave":"È","egrave":"è","egs":"⪖","egsdot":"⪘","el":"⪙","Element":"∈","elinters":"⏧","ell":"ℓ","els":"⪕","elsdot":"⪗","Emacr":"Ē","emacr":"ē","empty":"∅","emptyset":"∅","EmptySmallSquare":"◻","emptyv":"∅","EmptyVerySmallSquare":"▫","emsp13":" ","emsp14":" ","emsp":" ","ENG":"Ŋ","eng":"ŋ","ensp":" ","Eogon":"Ę","eogon":"ę","Eopf":"𝔼","eopf":"𝕖","epar":"⋕","eparsl":"⧣","eplus":"⩱","epsi":"ε","Epsilon":"Ε","epsilon":"ε","epsiv":"ϵ","eqcirc":"≖","eqcolon":"≕","eqsim":"≂","eqslantgtr":"⪖","eqslantless":"⪕","Equal":"⩵","equals":"=","EqualTilde":"≂","equest":"≟","Equilibrium":"⇌","equiv":"≡","equivDD":"⩸","eqvparsl":"⧥","erarr":"⥱","erDot":"≓","escr":"ℯ","Escr":"ℰ","esdot":"≐","Esim":"⩳","esim":"≂","Eta":"Η","eta":"η","ETH":"Ð","eth":"ð","Euml":"Ë","euml":"ë","euro":"€","excl":"!","exist":"∃","Exists":"∃","expectation":"ℰ","exponentiale":"ⅇ","ExponentialE":"ⅇ","fallingdotseq":"≒","Fcy":"Ф","fcy":"ф","female":"♀","ffilig":"ﬃ","fflig":"ﬀ","ffllig":"ﬄ","Ffr":"𝔉","ffr":"𝔣","filig":"ﬁ","FilledSmallSquare":"◼","FilledVerySmallSquare":"▪","fjlig":"fj","flat":"♭","fllig":"ﬂ","fltns":"▱","fnof":"ƒ","Fopf":"𝔽","fopf":"𝕗","forall":"∀","ForAll":"∀","fork":"⋔","forkv":"⫙","Fouriertrf":"ℱ","fpartint":"⨍","frac12":"½","frac13":"⅓","frac14":"¼","frac15":"⅕","frac16":"⅙","frac18":"⅛","frac23":"⅔","frac25":"⅖","frac34":"¾","frac35":"⅗","frac38":"⅜","frac45":"⅘","frac56":"⅚","frac58":"⅝","frac78":"⅞","frasl":"⁄","frown":"⌢","fscr":"𝒻","Fscr":"ℱ","gacute":"ǵ","Gamma":"Γ","gamma":"γ","Gammad":"Ϝ","gammad":"ϝ","gap":"⪆","Gbreve":"Ğ","gbreve":"ğ","Gcedil":"Ģ","Gcirc":"Ĝ","gcirc":"ĝ","Gcy":"Г","gcy":"г","Gdot":"Ġ","gdot":"ġ","ge":"≥","gE":"≧","gEl":"⪌","gel":"⋛","geq":"≥","geqq":"≧","geqslant":"⩾","gescc":"⪩","ges":"⩾","gesdot":"⪀","gesdoto":"⪂","gesdotol":"⪄","gesl":"⋛︀","gesles":"⪔","Gfr":"𝔊","gfr":"𝔤","gg":"≫","Gg":"⋙","ggg":"⋙","gimel":"ℷ","GJcy":"Ѓ","gjcy":"ѓ","gla":"⪥","gl":"≷","glE":"⪒","glj":"⪤","gnap":"⪊","gnapprox":"⪊","gne":"⪈","gnE":"≩","gneq":"⪈","gneqq":"≩","gnsim":"⋧","Gopf":"𝔾","gopf":"𝕘","grave":"`","GreaterEqual":"≥","GreaterEqualLess":"⋛","GreaterFullEqual":"≧","GreaterGreater":"⪢","GreaterLess":"≷","GreaterSlantEqual":"⩾","GreaterTilde":"≳","Gscr":"𝒢","gscr":"ℊ","gsim":"≳","gsime":"⪎","gsiml":"⪐","gtcc":"⪧","gtcir":"⩺","gt":">","GT":">","Gt":"≫","gtdot":"⋗","gtlPar":"⦕","gtquest":"⩼","gtrapprox":"⪆","gtrarr":"⥸","gtrdot":"⋗","gtreqless":"⋛","gtreqqless":"⪌","gtrless":"≷","gtrsim":"≳","gvertneqq":"≩︀","gvnE":"≩︀","Hacek":"ˇ","hairsp":" ","half":"½","hamilt":"ℋ","HARDcy":"Ъ","hardcy":"ъ","harrcir":"⥈","harr":"↔","hArr":"⇔","harrw":"↭","Hat":"^","hbar":"ℏ","Hcirc":"Ĥ","hcirc":"ĥ","hearts":"♥","heartsuit":"♥","hellip":"…","hercon":"⊹","hfr":"𝔥","Hfr":"ℌ","HilbertSpace":"ℋ","hksearow":"⤥","hkswarow":"⤦","hoarr":"⇿","homtht":"∻","hookleftarrow":"↩","hookrightarrow":"↪","hopf":"𝕙","Hopf":"ℍ","horbar":"―","HorizontalLine":"─","hscr":"𝒽","Hscr":"ℋ","hslash":"ℏ","Hstrok":"Ħ","hstrok":"ħ","HumpDownHump":"≎","HumpEqual":"≏","hybull":"⁃","hyphen":"‐","Iacute":"Í","iacute":"í","ic":"⁣","Icirc":"Î","icirc":"î","Icy":"И","icy":"и","Idot":"İ","IEcy":"Е","iecy":"е","iexcl":"¡","iff":"⇔","ifr":"𝔦","Ifr":"ℑ","Igrave":"Ì","igrave":"ì","ii":"ⅈ","iiiint":"⨌","iiint":"∭","iinfin":"⧜","iiota":"℩","IJlig":"Ĳ","ijlig":"ĳ","Imacr":"Ī","imacr":"ī","image":"ℑ","ImaginaryI":"ⅈ","imagline":"ℐ","imagpart":"ℑ","imath":"ı","Im":"ℑ","imof":"⊷","imped":"Ƶ","Implies":"⇒","incare":"℅","in":"∈","infin":"∞","infintie":"⧝","inodot":"ı","intcal":"⊺","int":"∫","Int":"∬","integers":"ℤ","Integral":"∫","intercal":"⊺","Intersection":"⋂","intlarhk":"⨗","intprod":"⨼","InvisibleComma":"⁣","InvisibleTimes":"⁢","IOcy":"Ё","iocy":"ё","Iogon":"Į","iogon":"į","Iopf":"𝕀","iopf":"𝕚","Iota":"Ι","iota":"ι","iprod":"⨼","iquest":"¿","iscr":"𝒾","Iscr":"ℐ","isin":"∈","isindot":"⋵","isinE":"⋹","isins":"⋴","isinsv":"⋳","isinv":"∈","it":"⁢","Itilde":"Ĩ","itilde":"ĩ","Iukcy":"І","iukcy":"і","Iuml":"Ï","iuml":"ï","Jcirc":"Ĵ","jcirc":"ĵ","Jcy":"Й","jcy":"й","Jfr":"𝔍","jfr":"𝔧","jmath":"ȷ","Jopf":"𝕁","jopf":"𝕛","Jscr":"𝒥","jscr":"𝒿","Jsercy":"Ј","jsercy":"ј","Jukcy":"Є","jukcy":"є","Kappa":"Κ","kappa":"κ","kappav":"ϰ","Kcedil":"Ķ","kcedil":"ķ","Kcy":"К","kcy":"к","Kfr":"𝔎","kfr":"𝔨","kgreen":"ĸ","KHcy":"Х","khcy":"х","KJcy":"Ќ","kjcy":"ќ","Kopf":"𝕂","kopf":"𝕜","Kscr":"𝒦","kscr":"𝓀","lAarr":"⇚","Lacute":"Ĺ","lacute":"ĺ","laemptyv":"⦴","lagran":"ℒ","Lambda":"Λ","lambda":"λ","lang":"⟨","Lang":"⟪","langd":"⦑","langle":"⟨","lap":"⪅","Laplacetrf":"ℒ","laquo":"«","larrb":"⇤","larrbfs":"⤟","larr":"←","Larr":"↞","lArr":"⇐","larrfs":"⤝","larrhk":"↩","larrlp":"↫","larrpl":"⤹","larrsim":"⥳","larrtl":"↢","latail":"⤙","lAtail":"⤛","lat":"⪫","late":"⪭","lates":"⪭︀","lbarr":"⤌","lBarr":"⤎","lbbrk":"❲","lbrace":"{","lbrack":"[","lbrke":"⦋","lbrksld":"⦏","lbrkslu":"⦍","Lcaron":"Ľ","lcaron":"ľ","Lcedil":"Ļ","lcedil":"ļ","lceil":"⌈","lcub":"{","Lcy":"Л","lcy":"л","ldca":"⤶","ldquo":"“","ldquor":"„","ldrdhar":"⥧","ldrushar":"⥋","ldsh":"↲","le":"≤","lE":"≦","LeftAngleBracket":"⟨","LeftArrowBar":"⇤","leftarrow":"←","LeftArrow":"←","Leftarrow":"⇐","LeftArrowRightArrow":"⇆","leftarrowtail":"↢","LeftCeiling":"⌈","LeftDoubleBracket":"⟦","LeftDownTeeVector":"⥡","LeftDownVectorBar":"⥙","LeftDownVector":"⇃","LeftFloor":"⌊","leftharpoondown":"↽","leftharpoonup":"↼","leftleftarrows":"⇇","leftrightarrow":"↔","LeftRightArrow":"↔","Leftrightarrow":"⇔","leftrightarrows":"⇆","leftrightharpoons":"⇋","leftrightsquigarrow":"↭","LeftRightVector":"⥎","LeftTeeArrow":"↤","LeftTee":"⊣","LeftTeeVector":"⥚","leftthreetimes":"⋋","LeftTriangleBar":"⧏","LeftTriangle":"⊲","LeftTriangleEqual":"⊴","LeftUpDownVector":"⥑","LeftUpTeeVector":"⥠","LeftUpVectorBar":"⥘","LeftUpVector":"↿","LeftVectorBar":"⥒","LeftVector":"↼","lEg":"⪋","leg":"⋚","leq":"≤","leqq":"≦","leqslant":"⩽","lescc":"⪨","les":"⩽","lesdot":"⩿","lesdoto":"⪁","lesdotor":"⪃","lesg":"⋚︀","lesges":"⪓","lessapprox":"⪅","lessdot":"⋖","lesseqgtr":"⋚","lesseqqgtr":"⪋","LessEqualGreater":"⋚","LessFullEqual":"≦","LessGreater":"≶","lessgtr":"≶","LessLess":"⪡","lesssim":"≲","LessSlantEqual":"⩽","LessTilde":"≲","lfisht":"⥼","lfloor":"⌊","Lfr":"𝔏","lfr":"𝔩","lg":"≶","lgE":"⪑","lHar":"⥢","lhard":"↽","lharu":"↼","lharul":"⥪","lhblk":"▄","LJcy":"Љ","ljcy":"љ","llarr":"⇇","ll":"≪","Ll":"⋘","llcorner":"⌞","Lleftarrow":"⇚","llhard":"⥫","lltri":"◺","Lmidot":"Ŀ","lmidot":"ŀ","lmoustache":"⎰","lmoust":"⎰","lnap":"⪉","lnapprox":"⪉","lne":"⪇","lnE":"≨","lneq":"⪇","lneqq":"≨","lnsim":"⋦","loang":"⟬","loarr":"⇽","lobrk":"⟦","longleftarrow":"⟵","LongLeftArrow":"⟵","Longleftarrow":"⟸","longleftrightarrow":"⟷","LongLeftRightArrow":"⟷","Longleftrightarrow":"⟺","longmapsto":"⟼","longrightarrow":"⟶","LongRightArrow":"⟶","Longrightarrow":"⟹","looparrowleft":"↫","looparrowright":"↬","lopar":"⦅","Lopf":"𝕃","lopf":"𝕝","loplus":"⨭","lotimes":"⨴","lowast":"∗","lowbar":"_","LowerLeftArrow":"↙","LowerRightArrow":"↘","loz":"◊","lozenge":"◊","lozf":"⧫","lpar":"(","lparlt":"⦓","lrarr":"⇆","lrcorner":"⌟","lrhar":"⇋","lrhard":"⥭","lrm":"‎","lrtri":"⊿","lsaquo":"‹","lscr":"𝓁","Lscr":"ℒ","lsh":"↰","Lsh":"↰","lsim":"≲","lsime":"⪍","lsimg":"⪏","lsqb":"[","lsquo":"‘","lsquor":"‚","Lstrok":"Ł","lstrok":"ł","ltcc":"⪦","ltcir":"⩹","lt":"<","LT":"<","Lt":"≪","ltdot":"⋖","lthree":"⋋","ltimes":"⋉","ltlarr":"⥶","ltquest":"⩻","ltri":"◃","ltrie":"⊴","ltrif":"◂","ltrPar":"⦖","lurdshar":"⥊","luruhar":"⥦","lvertneqq":"≨︀","lvnE":"≨︀","macr":"¯","male":"♂","malt":"✠","maltese":"✠","Map":"⤅","map":"↦","mapsto":"↦","mapstodown":"↧","mapstoleft":"↤","mapstoup":"↥","marker":"▮","mcomma":"⨩","Mcy":"М","mcy":"м","mdash":"—","mDDot":"∺","measuredangle":"∡","MediumSpace":" ","Mellintrf":"ℳ","Mfr":"𝔐","mfr":"𝔪","mho":"℧","micro":"µ","midast":"*","midcir":"⫰","mid":"∣","middot":"·","minusb":"⊟","minus":"−","minusd":"∸","minusdu":"⨪","MinusPlus":"∓","mlcp":"⫛","mldr":"…","mnplus":"∓","models":"⊧","Mopf":"𝕄","mopf":"𝕞","mp":"∓","mscr":"𝓂","Mscr":"ℳ","mstpos":"∾","Mu":"Μ","mu":"μ","multimap":"⊸","mumap":"⊸","nabla":"∇","Nacute":"Ń","nacute":"ń","nang":"∠⃒","nap":"≉","napE":"⩰̸","napid":"≋̸","napos":"ŉ","napprox":"≉","natural":"♮","naturals":"ℕ","natur":"♮","nbsp":"\xA0","nbump":"≎̸","nbumpe":"≏̸","ncap":"⩃","Ncaron":"Ň","ncaron":"ň","Ncedil":"Ņ","ncedil":"ņ","ncong":"≇","ncongdot":"⩭̸","ncup":"⩂","Ncy":"Н","ncy":"н","ndash":"–","nearhk":"⤤","nearr":"↗","neArr":"⇗","nearrow":"↗","ne":"≠","nedot":"≐̸","NegativeMediumSpace":"​","NegativeThickSpace":"​","NegativeThinSpace":"​","NegativeVeryThinSpace":"​","nequiv":"≢","nesear":"⤨","nesim":"≂̸","NestedGreaterGreater":"≫","NestedLessLess":"≪","NewLine":"\\n","nexist":"∄","nexists":"∄","Nfr":"𝔑","nfr":"𝔫","ngE":"≧̸","nge":"≱","ngeq":"≱","ngeqq":"≧̸","ngeqslant":"⩾̸","nges":"⩾̸","nGg":"⋙̸","ngsim":"≵","nGt":"≫⃒","ngt":"≯","ngtr":"≯","nGtv":"≫̸","nharr":"↮","nhArr":"⇎","nhpar":"⫲","ni":"∋","nis":"⋼","nisd":"⋺","niv":"∋","NJcy":"Њ","njcy":"њ","nlarr":"↚","nlArr":"⇍","nldr":"‥","nlE":"≦̸","nle":"≰","nleftarrow":"↚","nLeftarrow":"⇍","nleftrightarrow":"↮","nLeftrightarrow":"⇎","nleq":"≰","nleqq":"≦̸","nleqslant":"⩽̸","nles":"⩽̸","nless":"≮","nLl":"⋘̸","nlsim":"≴","nLt":"≪⃒","nlt":"≮","nltri":"⋪","nltrie":"⋬","nLtv":"≪̸","nmid":"∤","NoBreak":"⁠","NonBreakingSpace":"\xA0","nopf":"𝕟","Nopf":"ℕ","Not":"⫬","not":"¬","NotCongruent":"≢","NotCupCap":"≭","NotDoubleVerticalBar":"∦","NotElement":"∉","NotEqual":"≠","NotEqualTilde":"≂̸","NotExists":"∄","NotGreater":"≯","NotGreaterEqual":"≱","NotGreaterFullEqual":"≧̸","NotGreaterGreater":"≫̸","NotGreaterLess":"≹","NotGreaterSlantEqual":"⩾̸","NotGreaterTilde":"≵","NotHumpDownHump":"≎̸","NotHumpEqual":"≏̸","notin":"∉","notindot":"⋵̸","notinE":"⋹̸","notinva":"∉","notinvb":"⋷","notinvc":"⋶","NotLeftTriangleBar":"⧏̸","NotLeftTriangle":"⋪","NotLeftTriangleEqual":"⋬","NotLess":"≮","NotLessEqual":"≰","NotLessGreater":"≸","NotLessLess":"≪̸","NotLessSlantEqual":"⩽̸","NotLessTilde":"≴","NotNestedGreaterGreater":"⪢̸","NotNestedLessLess":"⪡̸","notni":"∌","notniva":"∌","notnivb":"⋾","notnivc":"⋽","NotPrecedes":"⊀","NotPrecedesEqual":"⪯̸","NotPrecedesSlantEqual":"⋠","NotReverseElement":"∌","NotRightTriangleBar":"⧐̸","NotRightTriangle":"⋫","NotRightTriangleEqual":"⋭","NotSquareSubset":"⊏̸","NotSquareSubsetEqual":"⋢","NotSquareSuperset":"⊐̸","NotSquareSupersetEqual":"⋣","NotSubset":"⊂⃒","NotSubsetEqual":"⊈","NotSucceeds":"⊁","NotSucceedsEqual":"⪰̸","NotSucceedsSlantEqual":"⋡","NotSucceedsTilde":"≿̸","NotSuperset":"⊃⃒","NotSupersetEqual":"⊉","NotTilde":"≁","NotTildeEqual":"≄","NotTildeFullEqual":"≇","NotTildeTilde":"≉","NotVerticalBar":"∤","nparallel":"∦","npar":"∦","nparsl":"⫽⃥","npart":"∂̸","npolint":"⨔","npr":"⊀","nprcue":"⋠","nprec":"⊀","npreceq":"⪯̸","npre":"⪯̸","nrarrc":"⤳̸","nrarr":"↛","nrArr":"⇏","nrarrw":"↝̸","nrightarrow":"↛","nRightarrow":"⇏","nrtri":"⋫","nrtrie":"⋭","nsc":"⊁","nsccue":"⋡","nsce":"⪰̸","Nscr":"𝒩","nscr":"𝓃","nshortmid":"∤","nshortparallel":"∦","nsim":"≁","nsime":"≄","nsimeq":"≄","nsmid":"∤","nspar":"∦","nsqsube":"⋢","nsqsupe":"⋣","nsub":"⊄","nsubE":"⫅̸","nsube":"⊈","nsubset":"⊂⃒","nsubseteq":"⊈","nsubseteqq":"⫅̸","nsucc":"⊁","nsucceq":"⪰̸","nsup":"⊅","nsupE":"⫆̸","nsupe":"⊉","nsupset":"⊃⃒","nsupseteq":"⊉","nsupseteqq":"⫆̸","ntgl":"≹","Ntilde":"Ñ","ntilde":"ñ","ntlg":"≸","ntriangleleft":"⋪","ntrianglelefteq":"⋬","ntriangleright":"⋫","ntrianglerighteq":"⋭","Nu":"Ν","nu":"ν","num":"#","numero":"№","numsp":" ","nvap":"≍⃒","nvdash":"⊬","nvDash":"⊭","nVdash":"⊮","nVDash":"⊯","nvge":"≥⃒","nvgt":">⃒","nvHarr":"⤄","nvinfin":"⧞","nvlArr":"⤂","nvle":"≤⃒","nvlt":"<⃒","nvltrie":"⊴⃒","nvrArr":"⤃","nvrtrie":"⊵⃒","nvsim":"∼⃒","nwarhk":"⤣","nwarr":"↖","nwArr":"⇖","nwarrow":"↖","nwnear":"⤧","Oacute":"Ó","oacute":"ó","oast":"⊛","Ocirc":"Ô","ocirc":"ô","ocir":"⊚","Ocy":"О","ocy":"о","odash":"⊝","Odblac":"Ő","odblac":"ő","odiv":"⨸","odot":"⊙","odsold":"⦼","OElig":"Œ","oelig":"œ","ofcir":"⦿","Ofr":"𝔒","ofr":"𝔬","ogon":"˛","Ograve":"Ò","ograve":"ò","ogt":"⧁","ohbar":"⦵","ohm":"Ω","oint":"∮","olarr":"↺","olcir":"⦾","olcross":"⦻","oline":"‾","olt":"⧀","Omacr":"Ō","omacr":"ō","Omega":"Ω","omega":"ω","Omicron":"Ο","omicron":"ο","omid":"⦶","ominus":"⊖","Oopf":"𝕆","oopf":"𝕠","opar":"⦷","OpenCurlyDoubleQuote":"“","OpenCurlyQuote":"‘","operp":"⦹","oplus":"⊕","orarr":"↻","Or":"⩔","or":"∨","ord":"⩝","order":"ℴ","orderof":"ℴ","ordf":"ª","ordm":"º","origof":"⊶","oror":"⩖","orslope":"⩗","orv":"⩛","oS":"Ⓢ","Oscr":"𝒪","oscr":"ℴ","Oslash":"Ø","oslash":"ø","osol":"⊘","Otilde":"Õ","otilde":"õ","otimesas":"⨶","Otimes":"⨷","otimes":"⊗","Ouml":"Ö","ouml":"ö","ovbar":"⌽","OverBar":"‾","OverBrace":"⏞","OverBracket":"⎴","OverParenthesis":"⏜","para":"¶","parallel":"∥","par":"∥","parsim":"⫳","parsl":"⫽","part":"∂","PartialD":"∂","Pcy":"П","pcy":"п","percnt":"%","period":".","permil":"‰","perp":"⊥","pertenk":"‱","Pfr":"𝔓","pfr":"𝔭","Phi":"Φ","phi":"φ","phiv":"ϕ","phmmat":"ℳ","phone":"☎","Pi":"Π","pi":"π","pitchfork":"⋔","piv":"ϖ","planck":"ℏ","planckh":"ℎ","plankv":"ℏ","plusacir":"⨣","plusb":"⊞","pluscir":"⨢","plus":"+","plusdo":"∔","plusdu":"⨥","pluse":"⩲","PlusMinus":"±","plusmn":"±","plussim":"⨦","plustwo":"⨧","pm":"±","Poincareplane":"ℌ","pointint":"⨕","popf":"𝕡","Popf":"ℙ","pound":"£","prap":"⪷","Pr":"⪻","pr":"≺","prcue":"≼","precapprox":"⪷","prec":"≺","preccurlyeq":"≼","Precedes":"≺","PrecedesEqual":"⪯","PrecedesSlantEqual":"≼","PrecedesTilde":"≾","preceq":"⪯","precnapprox":"⪹","precneqq":"⪵","precnsim":"⋨","pre":"⪯","prE":"⪳","precsim":"≾","prime":"′","Prime":"″","primes":"ℙ","prnap":"⪹","prnE":"⪵","prnsim":"⋨","prod":"∏","Product":"∏","profalar":"⌮","profline":"⌒","profsurf":"⌓","prop":"∝","Proportional":"∝","Proportion":"∷","propto":"∝","prsim":"≾","prurel":"⊰","Pscr":"𝒫","pscr":"𝓅","Psi":"Ψ","psi":"ψ","puncsp":" ","Qfr":"𝔔","qfr":"𝔮","qint":"⨌","qopf":"𝕢","Qopf":"ℚ","qprime":"⁗","Qscr":"𝒬","qscr":"𝓆","quaternions":"ℍ","quatint":"⨖","quest":"?","questeq":"≟","quot":"\\"","QUOT":"\\"","rAarr":"⇛","race":"∽̱","Racute":"Ŕ","racute":"ŕ","radic":"√","raemptyv":"⦳","rang":"⟩","Rang":"⟫","rangd":"⦒","range":"⦥","rangle":"⟩","raquo":"»","rarrap":"⥵","rarrb":"⇥","rarrbfs":"⤠","rarrc":"⤳","rarr":"→","Rarr":"↠","rArr":"⇒","rarrfs":"⤞","rarrhk":"↪","rarrlp":"↬","rarrpl":"⥅","rarrsim":"⥴","Rarrtl":"⤖","rarrtl":"↣","rarrw":"↝","ratail":"⤚","rAtail":"⤜","ratio":"∶","rationals":"ℚ","rbarr":"⤍","rBarr":"⤏","RBarr":"⤐","rbbrk":"❳","rbrace":"}","rbrack":"]","rbrke":"⦌","rbrksld":"⦎","rbrkslu":"⦐","Rcaron":"Ř","rcaron":"ř","Rcedil":"Ŗ","rcedil":"ŗ","rceil":"⌉","rcub":"}","Rcy":"Р","rcy":"р","rdca":"⤷","rdldhar":"⥩","rdquo":"”","rdquor":"”","rdsh":"↳","real":"ℜ","realine":"ℛ","realpart":"ℜ","reals":"ℝ","Re":"ℜ","rect":"▭","reg":"®","REG":"®","ReverseElement":"∋","ReverseEquilibrium":"⇋","ReverseUpEquilibrium":"⥯","rfisht":"⥽","rfloor":"⌋","rfr":"𝔯","Rfr":"ℜ","rHar":"⥤","rhard":"⇁","rharu":"⇀","rharul":"⥬","Rho":"Ρ","rho":"ρ","rhov":"ϱ","RightAngleBracket":"⟩","RightArrowBar":"⇥","rightarrow":"→","RightArrow":"→","Rightarrow":"⇒","RightArrowLeftArrow":"⇄","rightarrowtail":"↣","RightCeiling":"⌉","RightDoubleBracket":"⟧","RightDownTeeVector":"⥝","RightDownVectorBar":"⥕","RightDownVector":"⇂","RightFloor":"⌋","rightharpoondown":"⇁","rightharpoonup":"⇀","rightleftarrows":"⇄","rightleftharpoons":"⇌","rightrightarrows":"⇉","rightsquigarrow":"↝","RightTeeArrow":"↦","RightTee":"⊢","RightTeeVector":"⥛","rightthreetimes":"⋌","RightTriangleBar":"⧐","RightTriangle":"⊳","RightTriangleEqual":"⊵","RightUpDownVector":"⥏","RightUpTeeVector":"⥜","RightUpVectorBar":"⥔","RightUpVector":"↾","RightVectorBar":"⥓","RightVector":"⇀","ring":"˚","risingdotseq":"≓","rlarr":"⇄","rlhar":"⇌","rlm":"‏","rmoustache":"⎱","rmoust":"⎱","rnmid":"⫮","roang":"⟭","roarr":"⇾","robrk":"⟧","ropar":"⦆","ropf":"𝕣","Ropf":"ℝ","roplus":"⨮","rotimes":"⨵","RoundImplies":"⥰","rpar":")","rpargt":"⦔","rppolint":"⨒","rrarr":"⇉","Rrightarrow":"⇛","rsaquo":"›","rscr":"𝓇","Rscr":"ℛ","rsh":"↱","Rsh":"↱","rsqb":"]","rsquo":"’","rsquor":"’","rthree":"⋌","rtimes":"⋊","rtri":"▹","rtrie":"⊵","rtrif":"▸","rtriltri":"⧎","RuleDelayed":"⧴","ruluhar":"⥨","rx":"℞","Sacute":"Ś","sacute":"ś","sbquo":"‚","scap":"⪸","Scaron":"Š","scaron":"š","Sc":"⪼","sc":"≻","sccue":"≽","sce":"⪰","scE":"⪴","Scedil":"Ş","scedil":"ş","Scirc":"Ŝ","scirc":"ŝ","scnap":"⪺","scnE":"⪶","scnsim":"⋩","scpolint":"⨓","scsim":"≿","Scy":"С","scy":"с","sdotb":"⊡","sdot":"⋅","sdote":"⩦","searhk":"⤥","searr":"↘","seArr":"⇘","searrow":"↘","sect":"§","semi":";","seswar":"⤩","setminus":"∖","setmn":"∖","sext":"✶","Sfr":"𝔖","sfr":"𝔰","sfrown":"⌢","sharp":"♯","SHCHcy":"Щ","shchcy":"щ","SHcy":"Ш","shcy":"ш","ShortDownArrow":"↓","ShortLeftArrow":"←","shortmid":"∣","shortparallel":"∥","ShortRightArrow":"→","ShortUpArrow":"↑","shy":"­","Sigma":"Σ","sigma":"σ","sigmaf":"ς","sigmav":"ς","sim":"∼","simdot":"⩪","sime":"≃","simeq":"≃","simg":"⪞","simgE":"⪠","siml":"⪝","simlE":"⪟","simne":"≆","simplus":"⨤","simrarr":"⥲","slarr":"←","SmallCircle":"∘","smallsetminus":"∖","smashp":"⨳","smeparsl":"⧤","smid":"∣","smile":"⌣","smt":"⪪","smte":"⪬","smtes":"⪬︀","SOFTcy":"Ь","softcy":"ь","solbar":"⌿","solb":"⧄","sol":"/","Sopf":"𝕊","sopf":"𝕤","spades":"♠","spadesuit":"♠","spar":"∥","sqcap":"⊓","sqcaps":"⊓︀","sqcup":"⊔","sqcups":"⊔︀","Sqrt":"√","sqsub":"⊏","sqsube":"⊑","sqsubset":"⊏","sqsubseteq":"⊑","sqsup":"⊐","sqsupe":"⊒","sqsupset":"⊐","sqsupseteq":"⊒","square":"□","Square":"□","SquareIntersection":"⊓","SquareSubset":"⊏","SquareSubsetEqual":"⊑","SquareSuperset":"⊐","SquareSupersetEqual":"⊒","SquareUnion":"⊔","squarf":"▪","squ":"□","squf":"▪","srarr":"→","Sscr":"𝒮","sscr":"𝓈","ssetmn":"∖","ssmile":"⌣","sstarf":"⋆","Star":"⋆","star":"☆","starf":"★","straightepsilon":"ϵ","straightphi":"ϕ","strns":"¯","sub":"⊂","Sub":"⋐","subdot":"⪽","subE":"⫅","sube":"⊆","subedot":"⫃","submult":"⫁","subnE":"⫋","subne":"⊊","subplus":"⪿","subrarr":"⥹","subset":"⊂","Subset":"⋐","subseteq":"⊆","subseteqq":"⫅","SubsetEqual":"⊆","subsetneq":"⊊","subsetneqq":"⫋","subsim":"⫇","subsub":"⫕","subsup":"⫓","succapprox":"⪸","succ":"≻","succcurlyeq":"≽","Succeeds":"≻","SucceedsEqual":"⪰","SucceedsSlantEqual":"≽","SucceedsTilde":"≿","succeq":"⪰","succnapprox":"⪺","succneqq":"⪶","succnsim":"⋩","succsim":"≿","SuchThat":"∋","sum":"∑","Sum":"∑","sung":"♪","sup1":"¹","sup2":"²","sup3":"³","sup":"⊃","Sup":"⋑","supdot":"⪾","supdsub":"⫘","supE":"⫆","supe":"⊇","supedot":"⫄","Superset":"⊃","SupersetEqual":"⊇","suphsol":"⟉","suphsub":"⫗","suplarr":"⥻","supmult":"⫂","supnE":"⫌","supne":"⊋","supplus":"⫀","supset":"⊃","Supset":"⋑","supseteq":"⊇","supseteqq":"⫆","supsetneq":"⊋","supsetneqq":"⫌","supsim":"⫈","supsub":"⫔","supsup":"⫖","swarhk":"⤦","swarr":"↙","swArr":"⇙","swarrow":"↙","swnwar":"⤪","szlig":"ß","Tab":"\\t","target":"⌖","Tau":"Τ","tau":"τ","tbrk":"⎴","Tcaron":"Ť","tcaron":"ť","Tcedil":"Ţ","tcedil":"ţ","Tcy":"Т","tcy":"т","tdot":"⃛","telrec":"⌕","Tfr":"𝔗","tfr":"𝔱","there4":"∴","therefore":"∴","Therefore":"∴","Theta":"Θ","theta":"θ","thetasym":"ϑ","thetav":"ϑ","thickapprox":"≈","thicksim":"∼","ThickSpace":"  ","ThinSpace":" ","thinsp":" ","thkap":"≈","thksim":"∼","THORN":"Þ","thorn":"þ","tilde":"˜","Tilde":"∼","TildeEqual":"≃","TildeFullEqual":"≅","TildeTilde":"≈","timesbar":"⨱","timesb":"⊠","times":"×","timesd":"⨰","tint":"∭","toea":"⤨","topbot":"⌶","topcir":"⫱","top":"⊤","Topf":"𝕋","topf":"𝕥","topfork":"⫚","tosa":"⤩","tprime":"‴","trade":"™","TRADE":"™","triangle":"▵","triangledown":"▿","triangleleft":"◃","trianglelefteq":"⊴","triangleq":"≜","triangleright":"▹","trianglerighteq":"⊵","tridot":"◬","trie":"≜","triminus":"⨺","TripleDot":"⃛","triplus":"⨹","trisb":"⧍","tritime":"⨻","trpezium":"⏢","Tscr":"𝒯","tscr":"𝓉","TScy":"Ц","tscy":"ц","TSHcy":"Ћ","tshcy":"ћ","Tstrok":"Ŧ","tstrok":"ŧ","twixt":"≬","twoheadleftarrow":"↞","twoheadrightarrow":"↠","Uacute":"Ú","uacute":"ú","uarr":"↑","Uarr":"↟","uArr":"⇑","Uarrocir":"⥉","Ubrcy":"Ў","ubrcy":"ў","Ubreve":"Ŭ","ubreve":"ŭ","Ucirc":"Û","ucirc":"û","Ucy":"У","ucy":"у","udarr":"⇅","Udblac":"Ű","udblac":"ű","udhar":"⥮","ufisht":"⥾","Ufr":"𝔘","ufr":"𝔲","Ugrave":"Ù","ugrave":"ù","uHar":"⥣","uharl":"↿","uharr":"↾","uhblk":"▀","ulcorn":"⌜","ulcorner":"⌜","ulcrop":"⌏","ultri":"◸","Umacr":"Ū","umacr":"ū","uml":"¨","UnderBar":"_","UnderBrace":"⏟","UnderBracket":"⎵","UnderParenthesis":"⏝","Union":"⋃","UnionPlus":"⊎","Uogon":"Ų","uogon":"ų","Uopf":"𝕌","uopf":"𝕦","UpArrowBar":"⤒","uparrow":"↑","UpArrow":"↑","Uparrow":"⇑","UpArrowDownArrow":"⇅","updownarrow":"↕","UpDownArrow":"↕","Updownarrow":"⇕","UpEquilibrium":"⥮","upharpoonleft":"↿","upharpoonright":"↾","uplus":"⊎","UpperLeftArrow":"↖","UpperRightArrow":"↗","upsi":"υ","Upsi":"ϒ","upsih":"ϒ","Upsilon":"Υ","upsilon":"υ","UpTeeArrow":"↥","UpTee":"⊥","upuparrows":"⇈","urcorn":"⌝","urcorner":"⌝","urcrop":"⌎","Uring":"Ů","uring":"ů","urtri":"◹","Uscr":"𝒰","uscr":"𝓊","utdot":"⋰","Utilde":"Ũ","utilde":"ũ","utri":"▵","utrif":"▴","uuarr":"⇈","Uuml":"Ü","uuml":"ü","uwangle":"⦧","vangrt":"⦜","varepsilon":"ϵ","varkappa":"ϰ","varnothing":"∅","varphi":"ϕ","varpi":"ϖ","varpropto":"∝","varr":"↕","vArr":"⇕","varrho":"ϱ","varsigma":"ς","varsubsetneq":"⊊︀","varsubsetneqq":"⫋︀","varsupsetneq":"⊋︀","varsupsetneqq":"⫌︀","vartheta":"ϑ","vartriangleleft":"⊲","vartriangleright":"⊳","vBar":"⫨","Vbar":"⫫","vBarv":"⫩","Vcy":"В","vcy":"в","vdash":"⊢","vDash":"⊨","Vdash":"⊩","VDash":"⊫","Vdashl":"⫦","veebar":"⊻","vee":"∨","Vee":"⋁","veeeq":"≚","vellip":"⋮","verbar":"|","Verbar":"‖","vert":"|","Vert":"‖","VerticalBar":"∣","VerticalLine":"|","VerticalSeparator":"❘","VerticalTilde":"≀","VeryThinSpace":" ","Vfr":"𝔙","vfr":"𝔳","vltri":"⊲","vnsub":"⊂⃒","vnsup":"⊃⃒","Vopf":"𝕍","vopf":"𝕧","vprop":"∝","vrtri":"⊳","Vscr":"𝒱","vscr":"𝓋","vsubnE":"⫋︀","vsubne":"⊊︀","vsupnE":"⫌︀","vsupne":"⊋︀","Vvdash":"⊪","vzigzag":"⦚","Wcirc":"Ŵ","wcirc":"ŵ","wedbar":"⩟","wedge":"∧","Wedge":"⋀","wedgeq":"≙","weierp":"℘","Wfr":"𝔚","wfr":"𝔴","Wopf":"𝕎","wopf":"𝕨","wp":"℘","wr":"≀","wreath":"≀","Wscr":"𝒲","wscr":"𝓌","xcap":"⋂","xcirc":"◯","xcup":"⋃","xdtri":"▽","Xfr":"𝔛","xfr":"𝔵","xharr":"⟷","xhArr":"⟺","Xi":"Ξ","xi":"ξ","xlarr":"⟵","xlArr":"⟸","xmap":"⟼","xnis":"⋻","xodot":"⨀","Xopf":"𝕏","xopf":"𝕩","xoplus":"⨁","xotime":"⨂","xrarr":"⟶","xrArr":"⟹","Xscr":"𝒳","xscr":"𝓍","xsqcup":"⨆","xuplus":"⨄","xutri":"△","xvee":"⋁","xwedge":"⋀","Yacute":"Ý","yacute":"ý","YAcy":"Я","yacy":"я","Ycirc":"Ŷ","ycirc":"ŷ","Ycy":"Ы","ycy":"ы","yen":"¥","Yfr":"𝔜","yfr":"𝔶","YIcy":"Ї","yicy":"ї","Yopf":"𝕐","yopf":"𝕪","Yscr":"𝒴","yscr":"𝓎","YUcy":"Ю","yucy":"ю","yuml":"ÿ","Yuml":"Ÿ","Zacute":"Ź","zacute":"ź","Zcaron":"Ž","zcaron":"ž","Zcy":"З","zcy":"з","Zdot":"Ż","zdot":"ż","zeetrf":"ℨ","ZeroWidthSpace":"​","Zeta":"Ζ","zeta":"ζ","zfr":"𝔷","Zfr":"ℨ","ZHcy":"Ж","zhcy":"ж","zigrarr":"⇝","zopf":"𝕫","Zopf":"ℤ","Zscr":"𝒵","zscr":"𝓏","zwj":"‍","zwnj":"‌"}')},function(e,t,n){var r={};function i(e,t,n){var a,o,s,c,l,u=``;for(typeof t!=`string`&&(n=t,t=i.defaultChars),n===void 0&&(n=!0),l=function(e){var t,n,i=r[e];if(i)return i;for(i=r[e]=[],t=0;t<128;t++)n=String.fromCharCode(t),/^[0-9a-z]$/i.test(n)?i.push(n):i.push(`%`+(`0`+t.toString(16).toUpperCase()).slice(-2));for(t=0;t<e.length;t++)i[e.charCodeAt(t)]=e[t];return i}(t),a=0,o=e.length;a<o;a++)if(s=e.charCodeAt(a),n&&s===37&&a+2<o&&/^[0-9a-f]{2}$/i.test(e.slice(a+1,a+3)))u+=e.slice(a,a+3),a+=2;else if(s<128)u+=l[s];else if(s>=55296&&s<=57343){if(s>=55296&&s<=56319&&a+1<o&&(c=e.charCodeAt(a+1))>=56320&&c<=57343){u+=encodeURIComponent(e[a]+e[a+1]),a++;continue}u+=`%EF%BF%BD`}else u+=encodeURIComponent(e[a]);return u}i.defaultChars=`;/?:@&=+$,-_.!~*'()#`,i.componentChars=`-_.!~*'()`,e.exports=i},function(e,t,n){var r={};function i(e,t){var n;return typeof t!=`string`&&(t=i.defaultChars),n=function(e){var t,n,i=r[e];if(i)return i;for(i=r[e]=[],t=0;t<128;t++)n=String.fromCharCode(t),i.push(n);for(t=0;t<e.length;t++)i[n=e.charCodeAt(t)]=`%`+(`0`+n.toString(16).toUpperCase()).slice(-2);return i}(t),e.replace(/(%[a-f0-9]{2})+/gi,(function(e){var t,r,i,a,o,s,c,l=``;for(t=0,r=e.length;t<r;t+=3)(i=parseInt(e.slice(t+1,t+3),16))<128?l+=n[i]:(224&i)==192&&t+3<r&&(192&(a=parseInt(e.slice(t+4,t+6),16)))==128?(l+=(c=i<<6&1984|63&a)<128?`��`:String.fromCharCode(c),t+=3):(240&i)==224&&t+6<r&&(a=parseInt(e.slice(t+4,t+6),16),o=parseInt(e.slice(t+7,t+9),16),(192&a)==128&&(192&o)==128)?(l+=(c=i<<12&61440|a<<6&4032|63&o)<2048||c>=55296&&c<=57343?`���`:String.fromCharCode(c),t+=6):(248&i)==240&&t+9<r&&(a=parseInt(e.slice(t+4,t+6),16),o=parseInt(e.slice(t+7,t+9),16),s=parseInt(e.slice(t+10,t+12),16),(192&a)==128&&(192&o)==128&&(192&s)==128)?((c=i<<18&1835008|a<<12&258048|o<<6&4032|63&s)<65536||c>1114111?l+=`����`:(c-=65536,l+=String.fromCharCode(55296+(c>>10),56320+(1023&c))),t+=9):l+=`�`;return l}))}i.defaultChars=`;/?:@&=+$,#`,i.componentChars=``,e.exports=i},function(e,t,n){e.exports=function(e){var t=``;return t+=e.protocol||``,t+=e.slashes?`//`:``,t+=e.auth?e.auth+`@`:``,e.hostname&&e.hostname.indexOf(`:`)!==-1?t+=`[`+e.hostname+`]`:t+=e.hostname||``,t+=e.port?`:`+e.port:``,t+=e.pathname||``,t+=e.search||``,t+=e.hash||``}},function(e,t,n){function r(){this.protocol=null,this.slashes=null,this.auth=null,this.port=null,this.hostname=null,this.hash=null,this.search=null,this.pathname=null}var i=/^([a-z0-9.+-]+:)/i,a=/:[0-9]*$/,o=/^(\/\/?(?!\/)[^\?\s]*)(\?[^\s]*)?$/,s=[`%`,`/`,`?`,`;`,`#`,`'`,`{`,`}`,`|`,`\\`,`^`,"`",`<`,`>`,`"`,"`",` `,`\r`,`
`,`	`],c=[`/`,`?`,`#`],l=/^[+a-z0-9A-Z_-]{0,63}$/,u=/^([+a-z0-9A-Z_-]{0,63})(.*)$/,d={javascript:!0,"javascript:":!0},f={http:!0,https:!0,ftp:!0,gopher:!0,file:!0,"http:":!0,"https:":!0,"ftp:":!0,"gopher:":!0,"file:":!0};r.prototype.parse=function(e,t){var n,r,a,p,m,h=e;if(h=h.trim(),!t&&e.split(`#`).length===1){var g=o.exec(h);if(g)return this.pathname=g[1],g[2]&&(this.search=g[2]),this}var _=i.exec(h);if(_&&(a=(_=_[0]).toLowerCase(),this.protocol=_,h=h.substr(_.length)),(t||_||h.match(/^\/\/[^@\/]+@[^@\/]+/))&&(!(m=h.substr(0,2)===`//`)||_&&d[_]||(h=h.substr(2),this.slashes=!0)),!d[_]&&(m||_&&!f[_])){var v,y,b=-1;for(n=0;n<c.length;n++)(p=h.indexOf(c[n]))!==-1&&(b===-1||p<b)&&(b=p);for((y=b===-1?h.lastIndexOf(`@`):h.lastIndexOf(`@`,b))!==-1&&(v=h.slice(0,y),h=h.slice(y+1),this.auth=v),b=-1,n=0;n<s.length;n++)(p=h.indexOf(s[n]))!==-1&&(b===-1||p<b)&&(b=p);b===-1&&(b=h.length),h[b-1]===`:`&&b--;var x=h.slice(0,b);h=h.slice(b),this.parseHost(x),this.hostname=this.hostname||``;var S=this.hostname[0]===`[`&&this.hostname[this.hostname.length-1]===`]`;if(!S){var C=this.hostname.split(/\./);for(n=0,r=C.length;n<r;n++){var w=C[n];if(w&&!w.match(l)){for(var T=``,E=0,D=w.length;E<D;E++)w.charCodeAt(E)>127?T+=`x`:T+=w[E];if(!T.match(l)){var O=C.slice(0,n),k=C.slice(n+1),A=w.match(u);A&&(O.push(A[1]),k.unshift(A[2])),k.length&&(h=k.join(`.`)+h),this.hostname=O.join(`.`);break}}}}this.hostname.length>255&&(this.hostname=``),S&&(this.hostname=this.hostname.substr(1,this.hostname.length-2))}var j=h.indexOf(`#`);j!==-1&&(this.hash=h.substr(j),h=h.slice(0,j));var M=h.indexOf(`?`);return M!==-1&&(this.search=h.substr(M),h=h.slice(0,M)),h&&(this.pathname=h),f[a]&&this.hostname&&!this.pathname&&(this.pathname=``),this},r.prototype.parseHost=function(e){var t=a.exec(e);t&&((t=t[0])!==`:`&&(this.port=t.substr(1)),e=e.substr(0,e.length-t.length)),e&&(this.hostname=e)},e.exports=function(e,t){if(e&&e instanceof r)return e;var n=new r;return n.parse(e,t),n}},function(e,t,n){t.Any=n(64),t.Cc=n(65),t.Cf=n(148),t.P=n(40),t.Z=n(66)},function(e,t){e.exports=/[\xAD\u0600-\u0605\u061C\u06DD\u070F\u08E2\u180E\u200B-\u200F\u202A-\u202E\u2060-\u2064\u2066-\u206F\uFEFF\uFFF9-\uFFFB]|\uD804[\uDCBD\uDCCD]|\uD82F[\uDCA0-\uDCA3]|\uD834[\uDD73-\uDD7A]|\uDB40[\uDC01\uDC20-\uDC7F]/},function(e,t,n){t.parseLinkLabel=n(150),t.parseLinkDestination=n(151),t.parseLinkTitle=n(152)},function(e,t,n){e.exports=function(e,t,n){var r,i,a,o,s=-1,c=e.posMax,l=e.pos;for(e.pos=t+1,r=1;e.pos<c;){if((a=e.src.charCodeAt(e.pos))===93&&--r===0){i=!0;break}if(o=e.pos,e.md.inline.skipToken(e),a===91){if(o===e.pos-1)r++;else if(n)return e.pos=l,-1}}return i&&(s=e.pos),e.pos=l,s}},function(e,t,n){var r=n(1).unescapeAll;e.exports=function(e,t,n){var i,a,o=t,s={ok:!1,pos:0,lines:0,str:``};if(e.charCodeAt(t)===60){for(t++;t<n;){if((i=e.charCodeAt(t))===10)return s;if(i===62)return s.pos=t+1,s.str=r(e.slice(o+1,t)),s.ok=!0,s;i===92&&t+1<n?t+=2:t++}return s}for(a=0;t<n&&(i=e.charCodeAt(t))!==32&&!(i<32||i===127);)if(i===92&&t+1<n)t+=2;else{if(i===40&&a++,i===41){if(a===0)break;a--}t++}return o===t||a!==0||(s.str=r(e.slice(o,t)),s.lines=0,s.pos=t,s.ok=!0),s}},function(e,t,n){var r=n(1).unescapeAll;e.exports=function(e,t,n){var i,a,o=0,s=t,c={ok:!1,pos:0,lines:0,str:``};if(t>=n||(a=e.charCodeAt(t))!==34&&a!==39&&a!==40)return c;for(t++,a===40&&(a=41);t<n;){if((i=e.charCodeAt(t))===a)return c.pos=t+1,c.lines=o,c.str=r(e.slice(s+1,t)),c.ok=!0,c;i===10?o++:i===92&&t+1<n&&(t++,e.charCodeAt(t)===10&&o++),t++}return c}},function(e,t,n){var r=n(1).assign,i=n(1).unescapeAll,a=n(1).escapeHtml,o={};function s(){this.rules=r({},o)}o.code_inline=function(e,t,n,r,i){var o=e[t];return`<code`+i.renderAttrs(o)+`>`+a(e[t].content)+`</code>`},o.code_block=function(e,t,n,r,i){var o=e[t];return`<pre`+i.renderAttrs(o)+`><code>`+a(e[t].content)+`</code></pre>
`},o.fence=function(e,t,n,r,o){var s,c,l,u,d=e[t],f=d.info?i(d.info).trim():``,p=``;return f&&(p=f.split(/\s+/g)[0]),(s=n.highlight&&n.highlight(d.content,p)||a(d.content)).indexOf(`<pre`)===0?s+`
`:f?(c=d.attrIndex(`class`),l=d.attrs?d.attrs.slice():[],c<0?l.push([`class`,n.langPrefix+p]):l[c][1]+=` `+n.langPrefix+p,u={attrs:l},`<pre><code`+o.renderAttrs(u)+`>`+s+`</code></pre>
`):`<pre><code`+o.renderAttrs(d)+`>`+s+`</code></pre>
`},o.image=function(e,t,n,r,i){var a=e[t];return a.attrs[a.attrIndex(`alt`)][1]=i.renderInlineAsText(a.children,n,r),i.renderToken(e,t,n)},o.hardbreak=function(e,t,n){return n.xhtmlOut?`<br />
`:`<br>
`},o.softbreak=function(e,t,n){return n.breaks?n.xhtmlOut?`<br />
`:`<br>
`:`
`},o.text=function(e,t){return a(e[t].content)},o.html_block=function(e,t){return e[t].content},o.html_inline=function(e,t){return e[t].content},s.prototype.renderAttrs=function(e){var t,n,r;if(!e.attrs)return``;for(r=``,t=0,n=e.attrs.length;t<n;t++)r+=` `+a(e.attrs[t][0])+`="`+a(e.attrs[t][1])+`"`;return r},s.prototype.renderToken=function(e,t,n){var r,i=``,a=!1,o=e[t];return o.hidden?``:(o.block&&o.nesting!==-1&&t&&e[t-1].hidden&&(i+=`
`),i+=(o.nesting===-1?`</`:`<`)+o.tag,i+=this.renderAttrs(o),o.nesting===0&&n.xhtmlOut&&(i+=` /`),o.block&&(a=!0,o.nesting===1&&t+1<e.length&&((r=e[t+1]).type===`inline`||r.hidden||r.nesting===-1&&r.tag===o.tag)&&(a=!1)),i+=a?`>
`:`>`)},s.prototype.renderInline=function(e,t,n){for(var r,i=``,a=this.rules,o=0,s=e.length;o<s;o++)a[r=e[o].type]===void 0?i+=this.renderToken(e,o,t):i+=a[r](e,o,t,n,this);return i},s.prototype.renderInlineAsText=function(e,t,n){for(var r=``,i=0,a=e.length;i<a;i++)e[i].type===`text`?r+=e[i].content:e[i].type===`image`&&(r+=this.renderInlineAsText(e[i].children,t,n));return r},s.prototype.render=function(e,t,n){var r,i,a,o=``,s=this.rules;for(r=0,i=e.length;r<i;r++)(a=e[r].type)===`inline`?o+=this.renderInline(e[r].children,t,n):s[a]===void 0?o+=this.renderToken(e,r,t,n):o+=s[e[r].type](e,r,t,n,this);return o},e.exports=s},function(e,t,n){var r=n(41),i=[[`normalize`,n(155)],[`block`,n(156)],[`inline`,n(157)],[`linkify`,n(158)],[`replacements`,n(159)],[`smartquotes`,n(160)]];function a(){this.ruler=new r;for(var e=0;e<i.length;e++)this.ruler.push(i[e][0],i[e][1])}a.prototype.process=function(e){for(var t=0,n=(r=this.ruler.getRules(``)).length,r;t<n;t++)r[t](e)},a.prototype.State=n(161),e.exports=a},function(e,t,n){var r=/\r\n?|\n/g,i=/\0/g;e.exports=function(e){var t=(t=e.src.replace(r,`
`)).replace(i,`�`);e.src=t}},function(e,t,n){e.exports=function(e){var t;e.inlineMode?((t=new e.Token(`inline`,``,0)).content=e.src,t.map=[0,1],t.children=[],e.tokens.push(t)):e.md.block.parse(e.src,e.md,e.env,e.tokens)}},function(e,t,n){e.exports=function(e){var t,n,r,i=e.tokens;for(n=0,r=i.length;n<r;n++)(t=i[n]).type===`inline`&&e.md.inline.parse(t.content,e.md,e.env,t.children)}},function(e,t,n){var r=n(1).arrayReplaceAt;function i(e){return/^<\/a\s*>/i.test(e)}e.exports=function(e){var t,n,a,o,s,c,l,u,d,f,p,m,h,g,_,v,y,b,x=e.tokens;if(e.md.options.linkify){for(n=0,a=x.length;n<a;n++)if(x[n].type===`inline`&&e.md.linkify.pretest(x[n].content))for(h=0,t=(o=x[n].children).length-1;t>=0;t--)if((c=o[t]).type!==`link_close`){if(c.type===`html_inline`&&(b=c.content,/^<a[>\s]/i.test(b)&&h>0&&h--,i(c.content)&&h++),!(h>0)&&c.type===`text`&&e.md.linkify.test(c.content)){for(d=c.content,y=e.md.linkify.match(d),l=[],m=c.level,p=0,u=0;u<y.length;u++)g=y[u].url,_=e.md.normalizeLink(g),e.md.validateLink(_)&&(v=y[u].text,v=y[u].schema?y[u].schema!==`mailto:`||/^mailto:/i.test(v)?e.md.normalizeLinkText(v):e.md.normalizeLinkText(`mailto:`+v).replace(/^mailto:/,``):e.md.normalizeLinkText(`http://`+v).replace(/^http:\/\//,``),(f=y[u].index)>p&&((s=new e.Token(`text`,``,0)).content=d.slice(p,f),s.level=m,l.push(s)),(s=new e.Token(`link_open`,`a`,1)).attrs=[[`href`,_]],s.level=m++,s.markup=`linkify`,s.info=`auto`,l.push(s),(s=new e.Token(`text`,``,0)).content=v,s.level=m,l.push(s),(s=new e.Token(`link_close`,`a`,-1)).level=--m,s.markup=`linkify`,s.info=`auto`,l.push(s),p=y[u].lastIndex);p<d.length&&((s=new e.Token(`text`,``,0)).content=d.slice(p),s.level=m,l.push(s)),x[n].children=o=r(o,t,l)}}else for(t--;o[t].level!==c.level&&o[t].type!==`link_open`;)t--}}},function(e,t,n){var r=/\+-|\.\.|\?\?\?\?|!!!!|,,|--/,i=/\((c|tm|r|p)\)/i,a=/\((c|tm|r|p)\)/gi,o={c:`©`,r:`®`,p:`§`,tm:`™`};function s(e,t){return o[t.toLowerCase()]}function c(e){var t,n,r=0;for(t=e.length-1;t>=0;t--)(n=e[t]).type!==`text`||r||(n.content=n.content.replace(a,s)),n.type===`link_open`&&n.info===`auto`&&r--,n.type===`link_close`&&n.info===`auto`&&r++}function l(e){var t,n,i=0;for(t=e.length-1;t>=0;t--)(n=e[t]).type!==`text`||i||r.test(n.content)&&(n.content=n.content.replace(/\+-/g,`±`).replace(/\.{2,}/g,`…`).replace(/([?!])…/g,`$1..`).replace(/([?!]){4,}/g,`$1$1$1`).replace(/,{2,}/g,`,`).replace(/(^|[^-])---([^-]|$)/gm,`$1—$2`).replace(/(^|\s)--(\s|$)/gm,`$1–$2`).replace(/(^|[^-\s])--([^-\s]|$)/gm,`$1–$2`)),n.type===`link_open`&&n.info===`auto`&&i--,n.type===`link_close`&&n.info===`auto`&&i++}e.exports=function(e){var t;if(e.md.options.typographer)for(t=e.tokens.length-1;t>=0;t--)e.tokens[t].type===`inline`&&(i.test(e.tokens[t].content)&&c(e.tokens[t].children),r.test(e.tokens[t].content)&&l(e.tokens[t].children))}},function(e,t,n){var r=n(1).isWhiteSpace,i=n(1).isPunctChar,a=n(1).isMdAsciiPunct,o=/['"]/,s=/['"]/g;function c(e,t,n){return e.substr(0,t)+n+e.substr(t+1)}function l(e,t){var n,o,l,u,d,f,p,m,h,g,_,v,y,b,x,S,C,w,T=[],E,D;for(n=0;n<e.length;n++){for(o=e[n],p=e[n].level,C=T.length-1;C>=0&&!(T[C].level<=p);C--);if(T.length=C+1,o.type===`text`){d=0,f=(l=o.content).length;e:for(;d<f&&(s.lastIndex=d,u=s.exec(l));){if(x=S=!0,d=u.index+1,w=u[0]===`'`,h=32,u.index-1>=0)h=l.charCodeAt(u.index-1);else for(C=n-1;C>=0&&e[C].type!==`softbreak`&&e[C].type!==`hardbreak`;C--)if(e[C].type===`text`){h=e[C].content.charCodeAt(e[C].content.length-1);break}if(g=32,d<f)g=l.charCodeAt(d);else for(C=n+1;C<e.length&&e[C].type!==`softbreak`&&e[C].type!==`hardbreak`;C++)if(e[C].type===`text`){g=e[C].content.charCodeAt(0);break}if(_=a(h)||i(String.fromCharCode(h)),v=a(g)||i(String.fromCharCode(g)),y=r(h),(b=r(g))?x=!1:v&&(y||_||(x=!1)),y?S=!1:_&&(b||v||(S=!1)),g===34&&u[0]===`"`&&h>=48&&h<=57&&(S=x=!1),x&&S&&(x=!1,S=v),x||S){if(S){for(C=T.length-1;C>=0&&(m=T[C],!(T[C].level<p));C--)if(m.single===w&&T[C].level===p){m=T[C],w?(E=t.md.options.quotes[2],D=t.md.options.quotes[3]):(E=t.md.options.quotes[0],D=t.md.options.quotes[1]),o.content=c(o.content,u.index,D),e[m.token].content=c(e[m.token].content,m.pos,E),d+=D.length-1,m.token===n&&(d+=E.length-1),f=(l=o.content).length,T.length=C;continue e}}x?T.push({token:n,pos:u.index,single:w,level:p}):S&&w&&(o.content=c(o.content,u.index,`’`))}else w&&(o.content=c(o.content,u.index,`’`))}}}}e.exports=function(e){var t;if(e.md.options.typographer)for(t=e.tokens.length-1;t>=0;t--)e.tokens[t].type===`inline`&&o.test(e.tokens[t].content)&&l(e.tokens[t].children,e)}},function(e,t,n){var r=n(42);function i(e,t,n){this.src=e,this.env=n,this.tokens=[],this.inlineMode=!1,this.md=t}i.prototype.Token=r,e.exports=i},function(e,t,n){var r=n(41),i=[[`table`,n(163),[`paragraph`,`reference`]],[`code`,n(164)],[`fence`,n(165),[`paragraph`,`reference`,`blockquote`,`list`]],[`blockquote`,n(166),[`paragraph`,`reference`,`blockquote`,`list`]],[`hr`,n(167),[`paragraph`,`reference`,`blockquote`,`list`]],[`list`,n(168),[`paragraph`,`reference`,`blockquote`]],[`reference`,n(169)],[`heading`,n(170),[`paragraph`,`reference`,`blockquote`]],[`lheading`,n(171)],[`html_block`,n(172),[`paragraph`,`reference`,`blockquote`]],[`paragraph`,n(174)]];function a(){this.ruler=new r;for(var e=0;e<i.length;e++)this.ruler.push(i[e][0],i[e][1],{alt:(i[e][2]||[]).slice()})}a.prototype.tokenize=function(e,t,n){for(var r,i=this.ruler.getRules(``),a=i.length,o=t,s=!1,c=e.md.options.maxNesting;o<n&&(e.line=o=e.skipEmptyLines(o),!(o>=n))&&!(e.sCount[o]<e.blkIndent);){if(e.level>=c){e.line=n;break}for(r=0;r<a&&!i[r](e,o,n,!1);r++);e.tight=!s,e.isEmpty(e.line-1)&&(s=!0),(o=e.line)<n&&e.isEmpty(o)&&(s=!0,o++,e.line=o)}},a.prototype.parse=function(e,t,n,r){var i;e&&(i=new this.State(e,t,n,r),this.tokenize(i,i.line,i.lineMax))},a.prototype.State=n(175),e.exports=a},function(e,t,n){var r=n(1).isSpace;function i(e,t){var n=e.bMarks[t]+e.blkIndent,r=e.eMarks[t];return e.src.substr(n,r-n)}function a(e){var t,n=[],r=0,i=e.length,a=0,o=0,s=!1,c=0;for(t=e.charCodeAt(r);r<i;)t===96?s?(s=!1,c=r):a%2==0&&(s=!0,c=r):t!==124||a%2!=0||s||(n.push(e.substring(o,r)),o=r+1),t===92?a++:a=0,++r===i&&s&&(s=!1,r=c+1),t=e.charCodeAt(r);return n.push(e.substring(o)),n}e.exports=function(e,t,n,o){var s,c,l,u,d,f,p,m,h,g,_,v;if(t+2>n||(d=t+1,e.sCount[d]<e.blkIndent)||e.sCount[d]-e.blkIndent>=4||(l=e.bMarks[d]+e.tShift[d])>=e.eMarks[d]||(s=e.src.charCodeAt(l++))!==124&&s!==45&&s!==58)return!1;for(;l<e.eMarks[d];){if((s=e.src.charCodeAt(l))!==124&&s!==45&&s!==58&&!r(s))return!1;l++}for(f=(c=i(e,t+1)).split(`|`),h=[],u=0;u<f.length;u++){if(!(g=f[u].trim())){if(u===0||u===f.length-1)continue;return!1}if(!/^:?-+:?$/.test(g))return!1;g.charCodeAt(g.length-1)===58?h.push(g.charCodeAt(0)===58?`center`:`right`):g.charCodeAt(0)===58?h.push(`left`):h.push(``)}if((c=i(e,t).trim()).indexOf(`|`)===-1||e.sCount[t]-e.blkIndent>=4||(p=(f=a(c.replace(/^\||\|$/g,``))).length)>h.length)return!1;if(o)return!0;for((m=e.push(`table_open`,`table`,1)).map=_=[t,0],(m=e.push(`thead_open`,`thead`,1)).map=[t,t+1],(m=e.push(`tr_open`,`tr`,1)).map=[t,t+1],u=0;u<f.length;u++)(m=e.push(`th_open`,`th`,1)).map=[t,t+1],h[u]&&(m.attrs=[[`style`,`text-align:`+h[u]]]),(m=e.push(`inline`,``,0)).content=f[u].trim(),m.map=[t,t+1],m.children=[],m=e.push(`th_close`,`th`,-1);for(m=e.push(`tr_close`,`tr`,-1),m=e.push(`thead_close`,`thead`,-1),(m=e.push(`tbody_open`,`tbody`,1)).map=v=[t+2,0],d=t+2;d<n&&!(e.sCount[d]<e.blkIndent)&&(c=i(e,d).trim()).indexOf(`|`)!==-1&&!(e.sCount[d]-e.blkIndent>=4);d++){for(f=a(c.replace(/^\||\|$/g,``)),m=e.push(`tr_open`,`tr`,1),u=0;u<p;u++)m=e.push(`td_open`,`td`,1),h[u]&&(m.attrs=[[`style`,`text-align:`+h[u]]]),(m=e.push(`inline`,``,0)).content=f[u]?f[u].trim():``,m.children=[],m=e.push(`td_close`,`td`,-1);m=e.push(`tr_close`,`tr`,-1)}return m=e.push(`tbody_close`,`tbody`,-1),m=e.push(`table_close`,`table`,-1),_[1]=v[1]=d,e.line=d,!0}},function(e,t,n){e.exports=function(e,t,n){var r,i,a;if(e.sCount[t]-e.blkIndent<4)return!1;for(i=r=t+1;r<n;)if(e.isEmpty(r))r++;else{if(!(e.sCount[r]-e.blkIndent>=4))break;i=++r}return e.line=i,(a=e.push(`code_block`,`code`,0)).content=e.getLines(t,i,4+e.blkIndent,!0),a.map=[t,e.line],!0}},function(e,t,n){e.exports=function(e,t,n,r){var i,a,o,s,c,l,u,d=!1,f=e.bMarks[t]+e.tShift[t],p=e.eMarks[t];if(e.sCount[t]-e.blkIndent>=4||f+3>p||(i=e.src.charCodeAt(f))!==126&&i!==96||(c=f,(a=(f=e.skipChars(f,i))-c)<3)||(u=e.src.slice(c,f),o=e.src.slice(f,p),i===96&&o.indexOf(String.fromCharCode(i))>=0))return!1;if(r)return!0;for(s=t;!(++s>=n)&&!((f=c=e.bMarks[s]+e.tShift[s])<(p=e.eMarks[s])&&e.sCount[s]<e.blkIndent);)if(e.src.charCodeAt(f)===i&&!(e.sCount[s]-e.blkIndent>=4||(f=e.skipChars(f,i))-c<a||(f=e.skipSpaces(f))<p)){d=!0;break}return a=e.sCount[t],e.line=s+ +!!d,(l=e.push(`fence`,`code`,0)).info=o,l.content=e.getLines(t+1,s,a,!0),l.markup=u,l.map=[t,e.line],!0}},function(e,t,n){var r=n(1).isSpace;e.exports=function(e,t,n,i){var a,o,s,c,l,u,d,f,p,m,h,g,_,v,y,b,x,S,C,w,T=e.lineMax,E=e.bMarks[t]+e.tShift[t],D=e.eMarks[t];if(e.sCount[t]-e.blkIndent>=4||e.src.charCodeAt(E++)!==62)return!1;if(i)return!0;for(c=p=e.sCount[t]+E-(e.bMarks[t]+e.tShift[t]),e.src.charCodeAt(E)===32?(E++,c++,p++,a=!1,b=!0):e.src.charCodeAt(E)===9?(b=!0,(e.bsCount[t]+p)%4==3?(E++,c++,p++,a=!1):a=!0):b=!1,m=[e.bMarks[t]],e.bMarks[t]=E;E<D&&(o=e.src.charCodeAt(E),r(o));)o===9?p+=4-(p+e.bsCount[t]+ +!!a)%4:p++,E++;for(h=[e.bsCount[t]],e.bsCount[t]=e.sCount[t]+1+ +!!b,u=E>=D,v=[e.sCount[t]],e.sCount[t]=p-c,y=[e.tShift[t]],e.tShift[t]=E-e.bMarks[t],S=e.md.block.ruler.getRules(`blockquote`),_=e.parentType,e.parentType=`blockquote`,w=!1,f=t+1;f<n&&(e.sCount[f]<e.blkIndent&&(w=!0),!((E=e.bMarks[f]+e.tShift[f])>=(D=e.eMarks[f])));f++)if(e.src.charCodeAt(E++)!==62||w){if(u)break;for(x=!1,s=0,l=S.length;s<l;s++)if(S[s](e,f,n,!0)){x=!0;break}if(x){e.lineMax=f,e.blkIndent!==0&&(m.push(e.bMarks[f]),h.push(e.bsCount[f]),y.push(e.tShift[f]),v.push(e.sCount[f]),e.sCount[f]-=e.blkIndent);break}m.push(e.bMarks[f]),h.push(e.bsCount[f]),y.push(e.tShift[f]),v.push(e.sCount[f]),e.sCount[f]=-1}else{for(c=p=e.sCount[f]+E-(e.bMarks[f]+e.tShift[f]),e.src.charCodeAt(E)===32?(E++,c++,p++,a=!1,b=!0):e.src.charCodeAt(E)===9?(b=!0,(e.bsCount[f]+p)%4==3?(E++,c++,p++,a=!1):a=!0):b=!1,m.push(e.bMarks[f]),e.bMarks[f]=E;E<D&&(o=e.src.charCodeAt(E),r(o));)o===9?p+=4-(p+e.bsCount[f]+ +!!a)%4:p++,E++;u=E>=D,h.push(e.bsCount[f]),e.bsCount[f]=e.sCount[f]+1+ +!!b,v.push(e.sCount[f]),e.sCount[f]=p-c,y.push(e.tShift[f]),e.tShift[f]=E-e.bMarks[f]}for(g=e.blkIndent,e.blkIndent=0,(C=e.push(`blockquote_open`,`blockquote`,1)).markup=`>`,C.map=d=[t,0],e.md.block.tokenize(e,t,f),(C=e.push(`blockquote_close`,`blockquote`,-1)).markup=`>`,e.lineMax=T,e.parentType=_,d[1]=e.line,s=0;s<y.length;s++)e.bMarks[s+t]=m[s],e.tShift[s+t]=y[s],e.sCount[s+t]=v[s],e.bsCount[s+t]=h[s];return e.blkIndent=g,!0}},function(e,t,n){var r=n(1).isSpace;e.exports=function(e,t,n,i){var a,o,s,c,l=e.bMarks[t]+e.tShift[t],u=e.eMarks[t];if(e.sCount[t]-e.blkIndent>=4||(a=e.src.charCodeAt(l++))!==42&&a!==45&&a!==95)return!1;for(o=1;l<u;){if((s=e.src.charCodeAt(l++))!==a&&!r(s))return!1;s===a&&o++}return!(o<3)&&(i||(e.line=t+1,(c=e.push(`hr`,`hr`,0)).map=[t,e.line],c.markup=Array(o+1).join(String.fromCharCode(a))),!0)}},function(e,t,n){var r=n(1).isSpace;function i(e,t){var n,i=e.bMarks[t]+e.tShift[t],a=e.eMarks[t],o;return(n=e.src.charCodeAt(i++))!==42&&n!==45&&n!==43||i<a&&(o=e.src.charCodeAt(i),!r(o))?-1:i}function a(e,t){var n,i=e.bMarks[t]+e.tShift[t],a=i,o=e.eMarks[t];if(a+1>=o||(n=e.src.charCodeAt(a++))<48||n>57)return-1;for(;;){if(a>=o)return-1;if(!((n=e.src.charCodeAt(a++))>=48&&n<=57)){if(n===41||n===46)break;return-1}if(a-i>=10)return-1}return a<o&&(n=e.src.charCodeAt(a),!r(n))?-1:a}e.exports=function(e,t,n,r){var o,s,c,l,u,d,f,p,m,h,g,_,v,y,b,x,S,C,w,T,E,D,O,k,A,j,M,N,P=!1,F=!0;if(e.sCount[t]-e.blkIndent>=4||e.listIndent>=0&&e.sCount[t]-e.listIndent>=4&&e.sCount[t]<e.blkIndent)return!1;if(r&&e.parentType===`paragraph`&&e.tShift[t]>=e.blkIndent&&(P=!0),(O=a(e,t))>=0){if(f=!0,A=e.bMarks[t]+e.tShift[t],v=Number(e.src.substr(A,O-A-1)),P&&v!==1)return!1}else{if(!((O=i(e,t))>=0))return!1;f=!1}if(P&&e.skipSpaces(O)>=e.eMarks[t])return!1;if(_=e.src.charCodeAt(O-1),r)return!0;for(g=e.tokens.length,f?(N=e.push(`ordered_list_open`,`ol`,1),v!==1&&(N.attrs=[[`start`,v]])):N=e.push(`bullet_list_open`,`ul`,1),N.map=h=[t,0],N.markup=String.fromCharCode(_),b=t,k=!1,M=e.md.block.ruler.getRules(`list`),C=e.parentType,e.parentType=`list`;b<n;){for(D=O,y=e.eMarks[b],d=x=e.sCount[b]+O-(e.bMarks[t]+e.tShift[t]);D<y;){if((o=e.src.charCodeAt(D))===9)x+=4-(x+e.bsCount[b])%4;else{if(o!==32)break;x++}D++}if((u=(s=D)>=y?1:x-d)>4&&(u=1),l=d+u,(N=e.push(`list_item_open`,`li`,1)).markup=String.fromCharCode(_),N.map=p=[t,0],E=e.tight,T=e.tShift[t],w=e.sCount[t],S=e.listIndent,e.listIndent=e.blkIndent,e.blkIndent=l,e.tight=!0,e.tShift[t]=s-e.bMarks[t],e.sCount[t]=x,s>=y&&e.isEmpty(t+1)?e.line=Math.min(e.line+2,n):e.md.block.tokenize(e,t,n,!0),e.tight&&!k||(F=!1),k=e.line-t>1&&e.isEmpty(e.line-1),e.blkIndent=e.listIndent,e.listIndent=S,e.tShift[t]=T,e.sCount[t]=w,e.tight=E,(N=e.push(`list_item_close`,`li`,-1)).markup=String.fromCharCode(_),b=t=e.line,p[1]=b,s=e.bMarks[t],b>=n||e.sCount[b]<e.blkIndent||e.sCount[t]-e.blkIndent>=4)break;for(j=!1,c=0,m=M.length;c<m;c++)if(M[c](e,b,n,!0)){j=!0;break}if(j)break;if(f){if((O=a(e,b))<0)break}else if((O=i(e,b))<0)break;if(_!==e.src.charCodeAt(O-1))break}return(N=f?e.push(`ordered_list_close`,`ol`,-1):e.push(`bullet_list_close`,`ul`,-1)).markup=String.fromCharCode(_),h[1]=b,e.line=b,e.parentType=C,F&&function(e,t){var n,r,i=e.level+2;for(n=t+2,r=e.tokens.length-2;n<r;n++)e.tokens[n].level===i&&e.tokens[n].type===`paragraph_open`&&(e.tokens[n+2].hidden=!0,e.tokens[n].hidden=!0,n+=2)}(e,g),!0}},function(e,t,n){var r=n(1).normalizeReference,i=n(1).isSpace;e.exports=function(e,t,n,a){var o,s,c,l,u,d,f,p,m,h,g,_,v,y,b,x,S=0,C=e.bMarks[t]+e.tShift[t],w=e.eMarks[t],T=t+1;if(e.sCount[t]-e.blkIndent>=4||e.src.charCodeAt(C)!==91)return!1;for(;++C<w;)if(e.src.charCodeAt(C)===93&&e.src.charCodeAt(C-1)!==92){if(C+1===w||e.src.charCodeAt(C+1)!==58)return!1;break}for(l=e.lineMax,b=e.md.block.ruler.getRules(`reference`),h=e.parentType,e.parentType=`reference`;T<l&&!e.isEmpty(T);T++)if(!(e.sCount[T]-e.blkIndent>3||e.sCount[T]<0)){for(y=!1,d=0,f=b.length;d<f;d++)if(b[d](e,T,l,!0)){y=!0;break}if(y)break}for(w=(v=e.getLines(t,T,e.blkIndent,!1).trim()).length,C=1;C<w;C++){if((o=v.charCodeAt(C))===91)return!1;if(o===93){m=C;break}(o===10||o===92&&++C<w&&v.charCodeAt(C)===10)&&S++}if(m<0||v.charCodeAt(m+1)!==58)return!1;for(C=m+2;C<w;C++)if((o=v.charCodeAt(C))===10)S++;else if(!i(o))break;if(!(g=e.md.helpers.parseLinkDestination(v,C,w)).ok||(u=e.md.normalizeLink(g.str),!e.md.validateLink(u)))return!1;for(s=C=g.pos,c=S+=g.lines,_=C;C<w;C++)if((o=v.charCodeAt(C))===10)S++;else if(!i(o))break;for(g=e.md.helpers.parseLinkTitle(v,C,w),C<w&&_!==C&&g.ok?(x=g.str,C=g.pos,S+=g.lines):(x=``,C=s,S=c);C<w&&(o=v.charCodeAt(C),i(o));)C++;if(C<w&&v.charCodeAt(C)!==10&&x)for(x=``,C=s,S=c;C<w&&(o=v.charCodeAt(C),i(o));)C++;return!(C<w&&v.charCodeAt(C)!==10)&&!!(p=r(v.slice(1,m)))&&(a||(e.env.references===void 0&&(e.env.references={}),e.env.references[p]===void 0&&(e.env.references[p]={title:x,href:u}),e.parentType=h,e.line=t+S+1),!0)}},function(e,t,n){var r=n(1).isSpace;e.exports=function(e,t,n,i){var a,o,s,c,l=e.bMarks[t]+e.tShift[t],u=e.eMarks[t];if(e.sCount[t]-e.blkIndent>=4||(a=e.src.charCodeAt(l))!==35||l>=u)return!1;for(o=1,a=e.src.charCodeAt(++l);a===35&&l<u&&o<=6;)o++,a=e.src.charCodeAt(++l);return!(o>6||l<u&&!r(a))&&(i||(u=e.skipSpacesBack(u,l),(s=e.skipCharsBack(u,35,l))>l&&r(e.src.charCodeAt(s-1))&&(u=s),e.line=t+1,(c=e.push(`heading_open`,`h`+String(o),1)).markup=`########`.slice(0,o),c.map=[t,e.line],(c=e.push(`inline`,``,0)).content=e.src.slice(l,u).trim(),c.map=[t,e.line],c.children=[],(c=e.push(`heading_close`,`h`+String(o),-1)).markup=`########`.slice(0,o)),!0)}},function(e,t,n){e.exports=function(e,t,n){var r,i,a,o,s,c,l,u,d,f,p=t+1,m=e.md.block.ruler.getRules(`paragraph`);if(e.sCount[t]-e.blkIndent>=4)return!1;for(f=e.parentType,e.parentType=`paragraph`;p<n&&!e.isEmpty(p);p++)if(!(e.sCount[p]-e.blkIndent>3)){if(e.sCount[p]>=e.blkIndent&&(c=e.bMarks[p]+e.tShift[p])<(l=e.eMarks[p])&&((d=e.src.charCodeAt(c))===45||d===61)&&(c=e.skipChars(c,d),(c=e.skipSpaces(c))>=l)){u=d===61?1:2;break}if(!(e.sCount[p]<0)){for(i=!1,a=0,o=m.length;a<o;a++)if(m[a](e,p,n,!0)){i=!0;break}if(i)break}}return!!u&&(r=e.getLines(t,p,e.blkIndent,!1).trim(),e.line=p+1,(s=e.push(`heading_open`,`h`+String(u),1)).markup=String.fromCharCode(d),s.map=[t,e.line],(s=e.push(`inline`,``,0)).content=r,s.map=[t,e.line-1],s.children=[],(s=e.push(`heading_close`,`h`+String(u),-1)).markup=String.fromCharCode(d),e.parentType=f,!0)}},function(e,t,n){var r=n(173),i=n(67).HTML_OPEN_CLOSE_TAG_RE,a=[[/^<(script|pre|style)(?=(\s|>|$))/i,/<\/(script|pre|style)>/i,!0],[/^<!--/,/-->/,!0],[/^<\?/,/\?>/,!0],[/^<![A-Z]/,/>/,!0],[/^<!\[CDATA\[/,/\]\]>/,!0],[RegExp(`^</?(`+r.join(`|`)+`)(?=(\\s|/?>|$))`,`i`),/^$/,!0],[RegExp(i.source+`\\s*$`),/^$/,!1]];e.exports=function(e,t,n,r){var i,o,s,c,l=e.bMarks[t]+e.tShift[t],u=e.eMarks[t];if(e.sCount[t]-e.blkIndent>=4||!e.md.options.html||e.src.charCodeAt(l)!==60)return!1;for(c=e.src.slice(l,u),i=0;i<a.length&&!a[i][0].test(c);i++);if(i===a.length)return!1;if(r)return a[i][2];if(o=t+1,!a[i][1].test(c)){for(;o<n&&!(e.sCount[o]<e.blkIndent);o++)if(l=e.bMarks[o]+e.tShift[o],u=e.eMarks[o],c=e.src.slice(l,u),a[i][1].test(c)){c.length!==0&&o++;break}}return e.line=o,(s=e.push(`html_block`,``,0)).map=[t,o],s.content=e.getLines(t,o,e.blkIndent,!0),!0}},function(e,t,n){e.exports=`address.article.aside.base.basefont.blockquote.body.caption.center.col.colgroup.dd.details.dialog.dir.div.dl.dt.fieldset.figcaption.figure.footer.form.frame.frameset.h1.h2.h3.h4.h5.h6.head.header.hr.html.iframe.legend.li.link.main.menu.menuitem.meta.nav.noframes.ol.optgroup.option.p.param.section.source.summary.table.tbody.td.tfoot.th.thead.title.tr.track.ul`.split(`.`)},function(e,t,n){e.exports=function(e,t){var n,r,i,a,o,s,c=t+1,l=e.md.block.ruler.getRules(`paragraph`),u=e.lineMax;for(s=e.parentType,e.parentType=`paragraph`;c<u&&!e.isEmpty(c);c++)if(!(e.sCount[c]-e.blkIndent>3||e.sCount[c]<0)){for(r=!1,i=0,a=l.length;i<a;i++)if(l[i](e,c,u,!0)){r=!0;break}if(r)break}return n=e.getLines(t,c,e.blkIndent,!1).trim(),e.line=c,(o=e.push(`paragraph_open`,`p`,1)).map=[t,e.line],(o=e.push(`inline`,``,0)).content=n,o.map=[t,e.line],o.children=[],o=e.push(`paragraph_close`,`p`,-1),e.parentType=s,!0}},function(e,t,n){var r=n(42),i=n(1).isSpace;function a(e,t,n,r){var a,o,s,c,l,u,d,f;for(this.src=e,this.md=t,this.env=n,this.tokens=r,this.bMarks=[],this.eMarks=[],this.tShift=[],this.sCount=[],this.bsCount=[],this.blkIndent=0,this.line=0,this.lineMax=0,this.tight=!1,this.ddIndent=-1,this.listIndent=-1,this.parentType=`root`,this.level=0,this.result=``,f=!1,s=c=u=d=0,l=(o=this.src).length;c<l;c++){if(a=o.charCodeAt(c),!f){if(i(a)){u++,a===9?d+=4-d%4:d++;continue}f=!0}a!==10&&c!==l-1||(a!==10&&c++,this.bMarks.push(s),this.eMarks.push(c),this.tShift.push(u),this.sCount.push(d),this.bsCount.push(0),f=!1,u=0,d=0,s=c+1)}this.bMarks.push(o.length),this.eMarks.push(o.length),this.tShift.push(0),this.sCount.push(0),this.bsCount.push(0),this.lineMax=this.bMarks.length-1}a.prototype.push=function(e,t,n){var i=new r(e,t,n);return i.block=!0,n<0&&this.level--,i.level=this.level,n>0&&this.level++,this.tokens.push(i),i},a.prototype.isEmpty=function(e){return this.bMarks[e]+this.tShift[e]>=this.eMarks[e]},a.prototype.skipEmptyLines=function(e){for(var t=this.lineMax;e<t&&!(this.bMarks[e]+this.tShift[e]<this.eMarks[e]);e++);return e},a.prototype.skipSpaces=function(e){for(var t,n=this.src.length;e<n&&(t=this.src.charCodeAt(e),i(t));e++);return e},a.prototype.skipSpacesBack=function(e,t){if(e<=t)return e;for(;e>t;)if(!i(this.src.charCodeAt(--e)))return e+1;return e},a.prototype.skipChars=function(e,t){for(var n=this.src.length;e<n&&this.src.charCodeAt(e)===t;e++);return e},a.prototype.skipCharsBack=function(e,t,n){if(e<=n)return e;for(;e>n;)if(t!==this.src.charCodeAt(--e))return e+1;return e},a.prototype.getLines=function(e,t,n,r){var a,o,s,c,l,u,d,f=e;if(e>=t)return``;for(u=Array(t-e),a=0;f<t;f++,a++){for(o=0,d=c=this.bMarks[f],l=f+1<t||r?this.eMarks[f]+1:this.eMarks[f];c<l&&o<n;){if(s=this.src.charCodeAt(c),i(s))s===9?o+=4-(o+this.bsCount[f])%4:o++;else{if(!(c-d<this.tShift[f]))break;o++}c++}u[a]=o>n?Array(o-n+1).join(` `)+this.src.slice(c,l):this.src.slice(c,l)}return u.join(``)},a.prototype.Token=r,e.exports=a},function(e,t,n){var r=n(41),i=[[`text`,n(177)],[`newline`,n(178)],[`escape`,n(179)],[`backticks`,n(180)],[`strikethrough`,n(68).tokenize],[`emphasis`,n(69).tokenize],[`link`,n(181)],[`image`,n(182)],[`autolink`,n(183)],[`html_inline`,n(184)],[`entity`,n(185)]],a=[[`balance_pairs`,n(186)],[`strikethrough`,n(68).postProcess],[`emphasis`,n(69).postProcess],[`text_collapse`,n(187)]];function o(){var e;for(this.ruler=new r,e=0;e<i.length;e++)this.ruler.push(i[e][0],i[e][1]);for(this.ruler2=new r,e=0;e<a.length;e++)this.ruler2.push(a[e][0],a[e][1])}o.prototype.skipToken=function(e){var t,n,r=e.pos,i=this.ruler.getRules(``),a=i.length,o=e.md.options.maxNesting,s=e.cache;if(s[r]===void 0){if(e.level<o)for(n=0;n<a&&(e.level++,t=i[n](e,!0),e.level--,!t);n++);else e.pos=e.posMax;t||e.pos++,s[r]=e.pos}else e.pos=s[r]},o.prototype.tokenize=function(e){for(var t,n,r=this.ruler.getRules(``),i=r.length,a=e.posMax,o=e.md.options.maxNesting;e.pos<a;){if(e.level<o)for(n=0;n<i&&!(t=r[n](e,!1));n++);if(t){if(e.pos>=a)break}else e.pending+=e.src[e.pos++]}e.pending&&e.pushPending()},o.prototype.parse=function(e,t,n,r){var i,a,o,s=new this.State(e,t,n,r);for(this.tokenize(s),o=(a=this.ruler2.getRules(``)).length,i=0;i<o;i++)a[i](s)},o.prototype.State=n(188),e.exports=o},function(e,t,n){function r(e){switch(e){case 10:case 33:case 35:case 36:case 37:case 38:case 42:case 43:case 45:case 58:case 60:case 61:case 62:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 125:case 126:return!0;default:return!1}}e.exports=function(e,t){for(var n=e.pos;n<e.posMax&&!r(e.src.charCodeAt(n));)n++;return n!==e.pos&&(t||(e.pending+=e.src.slice(e.pos,n)),e.pos=n,!0)}},function(e,t,n){var r=n(1).isSpace;e.exports=function(e,t){var n,i,a=e.pos;if(e.src.charCodeAt(a)!==10)return!1;for(n=e.pending.length-1,i=e.posMax,t||(n>=0&&e.pending.charCodeAt(n)===32?n>=1&&e.pending.charCodeAt(n-1)===32?(e.pending=e.pending.replace(/ +$/,``),e.push(`hardbreak`,`br`,0)):(e.pending=e.pending.slice(0,-1),e.push(`softbreak`,`br`,0)):e.push(`softbreak`,`br`,0)),a++;a<i&&r(e.src.charCodeAt(a));)a++;return e.pos=a,!0}},function(e,t,n){for(var r=n(1).isSpace,i=[],a=0;a<256;a++)i.push(0);`\\!"#$%&'()*+,./:;<=>?@[]^_\`{|}~-`.split(``).forEach((function(e){i[e.charCodeAt(0)]=1})),e.exports=function(e,t){var n,a=e.pos,o=e.posMax;if(e.src.charCodeAt(a)!==92)return!1;if(++a<o){if((n=e.src.charCodeAt(a))<256&&i[n]!==0)return t||(e.pending+=e.src[a]),e.pos+=2,!0;if(n===10){for(t||e.push(`hardbreak`,`br`,0),a++;a<o&&(n=e.src.charCodeAt(a),r(n));)a++;return e.pos=a,!0}}return t||(e.pending+=`\\`),e.pos++,!0}},function(e,t,n){e.exports=function(e,t){var n,r,i,a,o,s,c=e.pos;if(e.src.charCodeAt(c)!==96)return!1;for(n=c,c++,r=e.posMax;c<r&&e.src.charCodeAt(c)===96;)c++;for(i=e.src.slice(n,c),a=o=c;(a=e.src.indexOf("`",o))!==-1;){for(o=a+1;o<r&&e.src.charCodeAt(o)===96;)o++;if(o-a===i.length)return t||((s=e.push(`code_inline`,`code`,0)).markup=i,s.content=e.src.slice(c,a).replace(/\n/g,` `).replace(/^ (.+) $/,`$1`)),e.pos=o,!0}return t||(e.pending+=i),e.pos+=i.length,!0}},function(e,t,n){var r=n(1).normalizeReference,i=n(1).isSpace;e.exports=function(e,t){var n,a,o,s,c,l,u,d,f,p=``,m=e.pos,h=e.posMax,g=e.pos,_=!0;if(e.src.charCodeAt(e.pos)!==91||(c=e.pos+1,(s=e.md.helpers.parseLinkLabel(e,e.pos,!0))<0))return!1;if((l=s+1)<h&&e.src.charCodeAt(l)===40){for(_=!1,l++;l<h&&(a=e.src.charCodeAt(l),i(a)||a===10);l++);if(l>=h)return!1;for(g=l,(u=e.md.helpers.parseLinkDestination(e.src,l,e.posMax)).ok&&(p=e.md.normalizeLink(u.str),e.md.validateLink(p)?l=u.pos:p=``),g=l;l<h&&(a=e.src.charCodeAt(l),i(a)||a===10);l++);if(u=e.md.helpers.parseLinkTitle(e.src,l,e.posMax),l<h&&g!==l&&u.ok)for(f=u.str,l=u.pos;l<h&&(a=e.src.charCodeAt(l),i(a)||a===10);l++);else f=``;(l>=h||e.src.charCodeAt(l)!==41)&&(_=!0),l++}if(_){if(e.env.references===void 0)return!1;if(l<h&&e.src.charCodeAt(l)===91?(g=l+1,(l=e.md.helpers.parseLinkLabel(e,l))>=0?o=e.src.slice(g,l++):l=s+1):l=s+1,o||=e.src.slice(c,s),!(d=e.env.references[r(o)]))return e.pos=m,!1;p=d.href,f=d.title}return t||(e.pos=c,e.posMax=s,e.push(`link_open`,`a`,1).attrs=n=[[`href`,p]],f&&n.push([`title`,f]),e.md.inline.tokenize(e),e.push(`link_close`,`a`,-1)),e.pos=l,e.posMax=h,!0}},function(e,t,n){var r=n(1).normalizeReference,i=n(1).isSpace;e.exports=function(e,t){var n,a,o,s,c,l,u,d,f,p,m,h,g,_=``,v=e.pos,y=e.posMax;if(e.src.charCodeAt(e.pos)!==33||e.src.charCodeAt(e.pos+1)!==91||(l=e.pos+2,(c=e.md.helpers.parseLinkLabel(e,e.pos+1,!1))<0))return!1;if((u=c+1)<y&&e.src.charCodeAt(u)===40){for(u++;u<y&&(a=e.src.charCodeAt(u),i(a)||a===10);u++);if(u>=y)return!1;for(g=u,(f=e.md.helpers.parseLinkDestination(e.src,u,e.posMax)).ok&&(_=e.md.normalizeLink(f.str),e.md.validateLink(_)?u=f.pos:_=``),g=u;u<y&&(a=e.src.charCodeAt(u),i(a)||a===10);u++);if(f=e.md.helpers.parseLinkTitle(e.src,u,e.posMax),u<y&&g!==u&&f.ok)for(p=f.str,u=f.pos;u<y&&(a=e.src.charCodeAt(u),i(a)||a===10);u++);else p=``;if(u>=y||e.src.charCodeAt(u)!==41)return e.pos=v,!1;u++}else{if(e.env.references===void 0)return!1;if(u<y&&e.src.charCodeAt(u)===91?(g=u+1,(u=e.md.helpers.parseLinkLabel(e,u))>=0?s=e.src.slice(g,u++):u=c+1):u=c+1,s||=e.src.slice(l,c),!(d=e.env.references[r(s)]))return e.pos=v,!1;_=d.href,p=d.title}return t||(o=e.src.slice(l,c),e.md.inline.parse(o,e.md,e.env,h=[]),(m=e.push(`image`,`img`,0)).attrs=n=[[`src`,_],[`alt`,``]],m.children=h,m.content=o,p&&n.push([`title`,p])),e.pos=u,e.posMax=y,!0}},function(e,t,n){var r=/^<([a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)>/,i=/^<([a-zA-Z][a-zA-Z0-9+.\-]{1,31}):([^<>\x00-\x20]*)>/;e.exports=function(e,t){var n,a,o,s,c,l,u=e.pos;return e.src.charCodeAt(u)===60&&!((n=e.src.slice(u)).indexOf(`>`)<0)&&(i.test(n)?(s=(a=n.match(i))[0].slice(1,-1),c=e.md.normalizeLink(s),!!e.md.validateLink(c)&&(t||((l=e.push(`link_open`,`a`,1)).attrs=[[`href`,c]],l.markup=`autolink`,l.info=`auto`,(l=e.push(`text`,``,0)).content=e.md.normalizeLinkText(s),(l=e.push(`link_close`,`a`,-1)).markup=`autolink`,l.info=`auto`),e.pos+=a[0].length,!0)):!!r.test(n)&&(s=(o=n.match(r))[0].slice(1,-1),c=e.md.normalizeLink(`mailto:`+s),!!e.md.validateLink(c)&&(t||((l=e.push(`link_open`,`a`,1)).attrs=[[`href`,c]],l.markup=`autolink`,l.info=`auto`,(l=e.push(`text`,``,0)).content=e.md.normalizeLinkText(s),(l=e.push(`link_close`,`a`,-1)).markup=`autolink`,l.info=`auto`),e.pos+=o[0].length,!0)))}},function(e,t,n){var r=n(67).HTML_TAG_RE;e.exports=function(e,t){var n,i,a,o=e.pos;return!!e.md.options.html&&(a=e.posMax,!(e.src.charCodeAt(o)!==60||o+2>=a)&&!((n=e.src.charCodeAt(o+1))!==33&&n!==63&&n!==47&&!function(e){var t=32|e;return t>=97&&t<=122}(n))&&!!(i=e.src.slice(o).match(r))&&(t||(e.push(`html_inline`,``,0).content=e.src.slice(o,o+i[0].length)),e.pos+=i[0].length,!0))}},function(e,t,n){var r=n(62),i=n(1).has,a=n(1).isValidEntityCode,o=n(1).fromCodePoint,s=/^&#((?:x[a-f0-9]{1,6}|[0-9]{1,7}));/i,c=/^&([a-z][a-z0-9]{1,31});/i;e.exports=function(e,t){var n,l,u=e.pos,d=e.posMax;if(e.src.charCodeAt(u)!==38)return!1;if(u+1<d){if(e.src.charCodeAt(u+1)===35){if(l=e.src.slice(u).match(s))return t||(n=l[1][0].toLowerCase()===`x`?parseInt(l[1].slice(1),16):parseInt(l[1],10),e.pending+=a(n)?o(n):o(65533)),e.pos+=l[0].length,!0}else if((l=e.src.slice(u).match(c))&&i(r,l[1]))return t||(e.pending+=r[l[1]]),e.pos+=l[0].length,!0}return t||(e.pending+=`&`),e.pos++,!0}},function(e,t,n){function r(e,t){var n,r,i,a,o,s,c,l,u={},d=t.length;for(n=0;n<d;n++)if((i=t[n]).length=i.length||0,i.close){for(u.hasOwnProperty(i.marker)||(u[i.marker]=[-1,-1,-1]),o=u[i.marker][i.length%3],s=-1,r=n-i.jump-1;r>o;r-=a.jump+1)if((a=t[r]).marker===i.marker&&(s===-1&&(s=r),a.open&&a.end<0&&a.level===i.level&&(c=!1,(a.close||i.open)&&(a.length+i.length)%3==0&&(a.length%3==0&&i.length%3==0||(c=!0)),!c))){l=r>0&&!t[r-1].open?t[r-1].jump+1:0,i.jump=n-r+l,i.open=!1,a.end=n,a.jump=l,a.close=!1,s=-1;break}s!==-1&&(u[i.marker][(i.length||0)%3]=s)}}e.exports=function(e){var t,n=e.tokens_meta,i=e.tokens_meta.length;for(r(0,e.delimiters),t=0;t<i;t++)n[t]&&n[t].delimiters&&r(0,n[t].delimiters)}},function(e,t,n){e.exports=function(e){var t,n,r=0,i=e.tokens,a=e.tokens.length;for(t=n=0;t<a;t++)i[t].nesting<0&&r--,i[t].level=r,i[t].nesting>0&&r++,i[t].type===`text`&&t+1<a&&i[t+1].type===`text`?i[t+1].content=i[t].content+i[t+1].content:(t!==n&&(i[n]=i[t]),n++);t!==n&&(i.length=n)}},function(e,t,n){var r=n(42),i=n(1).isWhiteSpace,a=n(1).isPunctChar,o=n(1).isMdAsciiPunct;function s(e,t,n,r){this.src=e,this.env=n,this.md=t,this.tokens=r,this.tokens_meta=Array(r.length),this.pos=0,this.posMax=this.src.length,this.level=0,this.pending=``,this.pendingLevel=0,this.cache={},this.delimiters=[],this._prev_delimiters=[]}s.prototype.pushPending=function(){var e=new r(`text`,``,0);return e.content=this.pending,e.level=this.pendingLevel,this.tokens.push(e),this.pending=``,e},s.prototype.push=function(e,t,n){this.pending&&this.pushPending();var i=new r(e,t,n),a=null;return n<0&&(this.level--,this.delimiters=this._prev_delimiters.pop()),i.level=this.level,n>0&&(this.level++,this._prev_delimiters.push(this.delimiters),this.delimiters=[],a={delimiters:this.delimiters}),this.pendingLevel=this.level,this.tokens.push(i),this.tokens_meta.push(a),i},s.prototype.scanDelims=function(e,t){var n,r,s,c,l,u,d,f,p,m=e,h=!0,g=!0,_=this.posMax,v=this.src.charCodeAt(e);for(n=e>0?this.src.charCodeAt(e-1):32;m<_&&this.src.charCodeAt(m)===v;)m++;return s=m-e,r=m<_?this.src.charCodeAt(m):32,d=o(n)||a(String.fromCharCode(n)),p=o(r)||a(String.fromCharCode(r)),u=i(n),(f=i(r))?h=!1:p&&(u||d||(h=!1)),u?g=!1:d&&(f||p||(g=!1)),t?(c=h,l=g):(c=h&&(!g||d),l=g&&(!h||p)),{can_open:c,can_close:l,length:s}},s.prototype.Token=r,e.exports=s},function(e,t,n){function r(e){return Array.prototype.slice.call(arguments,1).forEach((function(t){t&&Object.keys(t).forEach((function(n){e[n]=t[n]}))})),e}function i(e){return Object.prototype.toString.call(e)}function a(e){return i(e)===`[object Function]`}function o(e){return e.replace(/[.?*+^$[\]\\(){}|-]/g,`\\$&`)}var s={fuzzyLink:!0,fuzzyEmail:!0,fuzzyIP:!1},c={"http:":{validate:function(e,t,n){var r=e.slice(t);return n.re.http||(n.re.http=RegExp(`^\\/\\/`+n.re.src_auth+n.re.src_host_port_strict+n.re.src_path,`i`)),n.re.http.test(r)?r.match(n.re.http)[0].length:0}},"https:":`http:`,"ftp:":`http:`,"//":{validate:function(e,t,n){var r=e.slice(t);return n.re.no_http||(n.re.no_http=RegExp(`^`+n.re.src_auth+`(?:localhost|(?:(?:`+n.re.src_domain+`)\\.)+`+n.re.src_domain_root+`)`+n.re.src_port+n.re.src_host_terminator+n.re.src_path,`i`)),n.re.no_http.test(r)?t>=3&&e[t-3]===`:`||t>=3&&e[t-3]===`/`?0:r.match(n.re.no_http)[0].length:0}},"mailto:":{validate:function(e,t,n){var r=e.slice(t);return n.re.mailto||(n.re.mailto=RegExp(`^`+n.re.src_email_name+`@`+n.re.src_host_strict,`i`)),n.re.mailto.test(r)?r.match(n.re.mailto)[0].length:0}}},l=`biz|com|edu|gov|net|org|pro|web|xxx|aero|asia|coop|info|museum|name|shop|рф`.split(`|`);function u(e){var t=e.re=n(190)(e.__opts__),r=e.__tlds__.slice();function s(e){return e.replace(`%TLDS%`,t.src_tlds)}e.onCompile(),e.__tlds_replaced__||r.push(`a[cdefgilmnoqrstuwxz]|b[abdefghijmnorstvwyz]|c[acdfghiklmnoruvwxyz]|d[ejkmoz]|e[cegrstu]|f[ijkmor]|g[abdefghilmnpqrstuwy]|h[kmnrtu]|i[delmnoqrst]|j[emop]|k[eghimnprwyz]|l[abcikrstuvy]|m[acdeghklmnopqrstuvwxyz]|n[acefgilopruz]|om|p[aefghklmnrstwy]|qa|r[eosuw]|s[abcdeghijklmnortuvxyz]|t[cdfghjklmnortvwz]|u[agksyz]|v[aceginu]|w[fs]|y[et]|z[amw]`),r.push(t.src_xn),t.src_tlds=r.join(`|`),t.email_fuzzy=RegExp(s(t.tpl_email_fuzzy),`i`),t.link_fuzzy=RegExp(s(t.tpl_link_fuzzy),`i`),t.link_no_ip_fuzzy=RegExp(s(t.tpl_link_no_ip_fuzzy),`i`),t.host_fuzzy_test=RegExp(s(t.tpl_host_fuzzy_test),`i`);var c=[];function l(e,t){throw Error(`(LinkifyIt) Invalid schema "`+e+`": `+t)}e.__compiled__={},Object.keys(e.__schemas__).forEach((function(t){var n=e.__schemas__[t];if(n!==null){var r={validate:null,link:null};if(e.__compiled__[t]=r,i(n)===`[object Object]`)return function(e){return i(e)===`[object RegExp]`}(n.validate)?r.validate=function(e){return function(t,n){var r=t.slice(n);return e.test(r)?r.match(e)[0].length:0}}(n.validate):a(n.validate)?r.validate=n.validate:l(t,n),void(a(n.normalize)?r.normalize=n.normalize:n.normalize?l(t,n):r.normalize=function(e,t){t.normalize(e)});(function(e){return i(e)===`[object String]`})(n)?c.push(t):l(t,n)}})),c.forEach((function(t){e.__compiled__[e.__schemas__[t]]&&(e.__compiled__[t].validate=e.__compiled__[e.__schemas__[t]].validate,e.__compiled__[t].normalize=e.__compiled__[e.__schemas__[t]].normalize)})),e.__compiled__[``]={validate:null,normalize:function(e,t){t.normalize(e)}};var u=Object.keys(e.__compiled__).filter((function(t){return t.length>0&&e.__compiled__[t]})).map(o).join(`|`);e.re.schema_test=RegExp(`(^|(?!_)(?:[><｜]|`+t.src_ZPCc+`))(`+u+`)`,`i`),e.re.schema_search=RegExp(`(^|(?!_)(?:[><｜]|`+t.src_ZPCc+`))(`+u+`)`,`ig`),e.re.pretest=RegExp(`(`+e.re.schema_test.source+`)|(`+e.re.host_fuzzy_test.source+`)|@`,`i`),function(e){e.__index__=-1,e.__text_cache__=``}(e)}function d(e,t){var n=e.__index__,r=e.__last_index__,i=e.__text_cache__.slice(n,r);this.schema=e.__schema__.toLowerCase(),this.index=n+t,this.lastIndex=r+t,this.raw=i,this.text=i,this.url=i}function f(e,t){var n=new d(e,t);return e.__compiled__[n.schema].normalize(n,e),n}function p(e,t){if(!(this instanceof p))return new p(e,t);var n;t||(n=e,Object.keys(n||{}).reduce((function(e,t){return e||s.hasOwnProperty(t)}),!1)&&(t=e,e={})),this.__opts__=r({},s,t),this.__index__=-1,this.__last_index__=-1,this.__schema__=``,this.__text_cache__=``,this.__schemas__=r({},c,e),this.__compiled__={},this.__tlds__=l,this.__tlds_replaced__=!1,this.re={},u(this)}p.prototype.add=function(e,t){return this.__schemas__[e]=t,u(this),this},p.prototype.set=function(e){return this.__opts__=r(this.__opts__,e),this},p.prototype.test=function(e){if(this.__text_cache__=e,this.__index__=-1,!e.length)return!1;var t,n,r,i,a,o,s,c;if(this.re.schema_test.test(e)){for((s=this.re.schema_search).lastIndex=0;(t=s.exec(e))!==null;)if(i=this.testSchemaAt(e,t[2],s.lastIndex)){this.__schema__=t[2],this.__index__=t.index+t[1].length,this.__last_index__=t.index+t[0].length+i;break}}return this.__opts__.fuzzyLink&&this.__compiled__[`http:`]&&(c=e.search(this.re.host_fuzzy_test))>=0&&(this.__index__<0||c<this.__index__)&&(n=e.match(this.__opts__.fuzzyIP?this.re.link_fuzzy:this.re.link_no_ip_fuzzy))!==null&&(a=n.index+n[1].length,(this.__index__<0||a<this.__index__)&&(this.__schema__=``,this.__index__=a,this.__last_index__=n.index+n[0].length)),this.__opts__.fuzzyEmail&&this.__compiled__[`mailto:`]&&e.indexOf(`@`)>=0&&(r=e.match(this.re.email_fuzzy))!==null&&(a=r.index+r[1].length,o=r.index+r[0].length,(this.__index__<0||a<this.__index__||a===this.__index__&&o>this.__last_index__)&&(this.__schema__=`mailto:`,this.__index__=a,this.__last_index__=o)),this.__index__>=0},p.prototype.pretest=function(e){return this.re.pretest.test(e)},p.prototype.testSchemaAt=function(e,t,n){return this.__compiled__[t.toLowerCase()]?this.__compiled__[t.toLowerCase()].validate(e,n,this):0},p.prototype.match=function(e){var t=0,n=[];this.__index__>=0&&this.__text_cache__===e&&(n.push(f(this,t)),t=this.__last_index__);for(var r=t?e.slice(t):e;this.test(r);)n.push(f(this,t)),r=r.slice(this.__last_index__),t+=this.__last_index__;return n.length?n:null},p.prototype.tlds=function(e,t){return e=Array.isArray(e)?e:[e],t?(this.__tlds__=this.__tlds__.concat(e).sort().filter((function(e,t,n){return e!==n[t-1]})).reverse(),u(this),this):(this.__tlds__=e.slice(),this.__tlds_replaced__=!0,u(this),this)},p.prototype.normalize=function(e){e.schema||(e.url=`http://`+e.url),e.schema!==`mailto:`||/^mailto:/i.test(e.url)||(e.url=`mailto:`+e.url)},p.prototype.onCompile=function(){},e.exports=p},function(e,t,n){e.exports=function(e){var t={};return t.src_Any=n(64).source,t.src_Cc=n(65).source,t.src_Z=n(66).source,t.src_P=n(40).source,t.src_ZPCc=[t.src_Z,t.src_P,t.src_Cc].join(`|`),t.src_ZCc=[t.src_Z,t.src_Cc].join(`|`),t.src_pseudo_letter=`(?:(?![><｜]|`+t.src_ZPCc+`)`+t.src_Any+`)`,t.src_ip4=`(?:(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)`,t.src_auth=`(?:(?:(?!`+t.src_ZCc+`|[@/\\[\\]()]).)+@)?`,t.src_port=`(?::(?:6(?:[0-4]\\d{3}|5(?:[0-4]\\d{2}|5(?:[0-2]\\d|3[0-5])))|[1-5]?\\d{1,4}))?`,t.src_host_terminator=`(?=$|[><｜]|`+t.src_ZPCc+`)(?!-|_|:\\d|\\.-|\\.(?!$|`+t.src_ZPCc+`))`,t.src_path=`(?:[/?#](?:(?!`+t.src_ZCc+`|[><｜]|[()[\\]{}.,"'?!\\-]).|\\[(?:(?!`+t.src_ZCc+`|\\]).)*\\]|\\((?:(?!`+t.src_ZCc+`|[)]).)*\\)|\\{(?:(?!`+t.src_ZCc+`|[}]).)*\\}|\\"(?:(?!`+t.src_ZCc+`|["]).)+\\"|\\'(?:(?!`+t.src_ZCc+`|[']).)+\\'|\\'(?=`+t.src_pseudo_letter+`|[-]).|\\.{2,4}[a-zA-Z0-9%/]|\\.(?!`+t.src_ZCc+`|[.]).|`+(e&&e[`---`]?`\\-(?!--(?:[^-]|$))(?:-*)|`:`\\-+|`)+`\\,(?!`+t.src_ZCc+`).|\\!(?!`+t.src_ZCc+`|[!]).|\\?(?!`+t.src_ZCc+`|[?]).)+|\\/)?`,t.src_email_name=`[\\-;:&=\\+\\$,\\.a-zA-Z0-9_][\\-;:&=\\+\\$,\\"\\.a-zA-Z0-9_]*`,t.src_xn=`xn--[a-z0-9\\-]{1,59}`,t.src_domain_root=`(?:`+t.src_xn+`|`+t.src_pseudo_letter+`{1,63})`,t.src_domain=`(?:`+t.src_xn+`|(?:`+t.src_pseudo_letter+`)|(?:`+t.src_pseudo_letter+`(?:-|`+t.src_pseudo_letter+`){0,61}`+t.src_pseudo_letter+`))`,t.src_host=`(?:(?:(?:(?:`+t.src_domain+`)\\.)*`+t.src_domain+`))`,t.tpl_host_fuzzy=`(?:`+t.src_ip4+`|(?:(?:(?:`+t.src_domain+`)\\.)+(?:%TLDS%)))`,t.tpl_host_no_ip_fuzzy=`(?:(?:(?:`+t.src_domain+`)\\.)+(?:%TLDS%))`,t.src_host_strict=t.src_host+t.src_host_terminator,t.tpl_host_fuzzy_strict=t.tpl_host_fuzzy+t.src_host_terminator,t.src_host_port_strict=t.src_host+t.src_port+t.src_host_terminator,t.tpl_host_port_fuzzy_strict=t.tpl_host_fuzzy+t.src_port+t.src_host_terminator,t.tpl_host_port_no_ip_fuzzy_strict=t.tpl_host_no_ip_fuzzy+t.src_port+t.src_host_terminator,t.tpl_host_fuzzy_test=`localhost|www\\.|\\.\\d{1,3}\\.|(?:\\.(?:%TLDS%)(?:`+t.src_ZPCc+`|>|$))`,t.tpl_email_fuzzy=`(^|[><｜]|"|\\(|`+t.src_ZCc+`)(`+t.src_email_name+`@`+t.tpl_host_fuzzy_strict+`)`,t.tpl_link_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|｜]|"+t.src_ZPCc+"))((?![$+<=>^`|｜])"+t.tpl_host_port_fuzzy_strict+t.src_path+`)`,t.tpl_link_no_ip_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|｜]|"+t.src_ZPCc+"))((?![$+<=>^`|｜])"+t.tpl_host_port_no_ip_fuzzy_strict+t.src_path+`)`,t}},function(e,t,n){(function(e,r){var i;(function(a){t&&t.nodeType,e&&e.nodeType;var o=typeof r==`object`&&r;o.global!==o&&o.window!==o&&o.self;var s,c=2147483647,l=/^xn--/,u=/[^\x20-\x7E]/,d=/[\x2E\u3002\uFF0E\uFF61]/g,f={overflow:`Overflow: input needs wider integers to process`,"not-basic":`Illegal input >= 0x80 (not a basic code point)`,"invalid-input":`Invalid input`},p=Math.floor,m=String.fromCharCode;function h(e){throw RangeError(f[e])}function g(e,t){for(var n=e.length,r=[];n--;)r[n]=t(e[n]);return r}function _(e,t){var n=e.split(`@`),r=``;return n.length>1&&(r=n[0]+`@`,e=n[1]),r+g((e=e.replace(d,`.`)).split(`.`),t).join(`.`)}function v(e){for(var t,n,r=[],i=0,a=e.length;i<a;)(t=e.charCodeAt(i++))>=55296&&t<=56319&&i<a?(64512&(n=e.charCodeAt(i++)))==56320?r.push(((1023&t)<<10)+(1023&n)+65536):(r.push(t),i--):r.push(t);return r}function y(e){return g(e,(function(e){var t=``;return e>65535&&(t+=m((e-=65536)>>>10&1023|55296),e=56320|1023&e),t+=m(e)})).join(``)}function b(e,t){return e+22+75*(e<26)-((t!=0)<<5)}function x(e,t,n){var r=0;for(e=n?p(e/700):e>>1,e+=p(e/t);e>455;r+=36)e=p(e/35);return p(r+36*e/(e+38))}function S(e){var t,n,r,i,a,o,s,l,u,d,f,m=[],g=e.length,_=0,v=128,b=72;for((n=e.lastIndexOf(`-`))<0&&(n=0),r=0;r<n;++r)e.charCodeAt(r)>=128&&h(`not-basic`),m.push(e.charCodeAt(r));for(i=n>0?n+1:0;i<g;){for(a=_,o=1,s=36;i>=g&&h(`invalid-input`),((l=(f=e.charCodeAt(i++))-48<10?f-22:f-65<26?f-65:f-97<26?f-97:36)>=36||l>p((c-_)/o))&&h(`overflow`),_+=l*o,!(l<(u=s<=b?1:s>=b+26?26:s-b));s+=36)o>p(c/(d=36-u))&&h(`overflow`),o*=d;b=x(_-a,t=m.length+1,a==0),p(_/t)>c-v&&h(`overflow`),v+=p(_/t),_%=t,m.splice(_++,0,v)}return y(m)}function C(e){var t,n,r,i,a,o,s,l,u,d,f,g,_,y,S,C=[];for(g=(e=v(e)).length,t=128,n=0,a=72,o=0;o<g;++o)(f=e[o])<128&&C.push(m(f));for(r=i=C.length,i&&C.push(`-`);r<g;){for(s=c,o=0;o<g;++o)(f=e[o])>=t&&f<s&&(s=f);for(s-t>p((c-n)/(_=r+1))&&h(`overflow`),n+=(s-t)*_,t=s,o=0;o<g;++o)if((f=e[o])<t&&++n>c&&h(`overflow`),f==t){for(l=n,u=36;!(l<(d=u<=a?1:u>=a+26?26:u-a));u+=36)S=l-d,y=36-d,C.push(m(b(d+S%y,0))),l=p(S/y);C.push(m(b(l,0))),a=x(n,_,r==i),n=0,++r}++n,++t}return C.join(``)}s={version:`1.4.1`,ucs2:{decode:v,encode:y},decode:S,encode:C,toASCII:function(e){return _(e,(function(e){return u.test(e)?`xn--`+C(e):e}))},toUnicode:function(e){return _(e,(function(e){return l.test(e)?S(e.slice(4).toLowerCase()):e}))}},(i=function(){return s}.call(t,n,t,e))===void 0||(e.exports=i)})()}).call(this,n(192)(e),n(193))},function(e,t){e.exports=function(e){return e.webpackPolyfill||=(e.deprecate=function(){},e.paths=[],e.children||=[],Object.defineProperty(e,"loaded",{enumerable:!0,get:function(){return e.l}}),Object.defineProperty(e,"id",{enumerable:!0,get:function(){return e.i}}),1),e}},function(e,t){var n=function(){return this}();try{n||=Function(`return this`)()}catch{typeof window==`object`&&(n=window)}e.exports=n},function(e,t,n){e.exports={options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:`language-`,linkify:!1,typographer:!1,quotes:`“”‘’`,highlight:null,maxNesting:100},components:{core:{},block:{},inline:{}}}},function(e,t,n){e.exports={options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:`language-`,linkify:!1,typographer:!1,quotes:`“”‘’`,highlight:null,maxNesting:20},components:{core:{rules:[`normalize`,`block`,`inline`]},block:{rules:[`paragraph`]},inline:{rules:[`text`],rules2:[`balance_pairs`,`text_collapse`]}}}},function(e,t,n){e.exports={options:{html:!0,xhtmlOut:!0,breaks:!1,langPrefix:`language-`,linkify:!1,typographer:!1,quotes:`“”‘’`,highlight:null,maxNesting:20},components:{core:{rules:[`normalize`,`block`,`inline`]},block:{rules:[`blockquote`,`code`,`fence`,`heading`,`hr`,`html_block`,`lheading`,`list`,`reference`,`paragraph`]},inline:{rules:[`autolink`,`backticks`,`emphasis`,`entity`,`escape`,`html_inline`,`image`,`link`,`newline`,`text`],rules2:[`balance_pairs`,`emphasis`,`text_collapse`]}}}},function(e,t,n){var r=n(198),i=n(199),a=n(200),o=n(201),s=n(202);e.exports=function(e,t){var n={defs:r,shortcuts:i,enabled:[]},c=s(e.utils.assign({},n,t||{}));e.renderer.rules.emoji=a,e.core.ruler.push(`emoji`,o(e,c.defs,c.shortcuts,c.scanRE,c.replaceRE))}},function(e){e.exports=JSON.parse(`{"100":"💯","1234":"🔢","grinning":"😀","smiley":"😃","smile":"😄","grin":"😁","laughing":"😆","satisfied":"😆","sweat_smile":"😅","joy":"😂","rofl":"🤣","relaxed":"☺️","blush":"😊","innocent":"😇","slightly_smiling_face":"🙂","upside_down_face":"🙃","wink":"😉","relieved":"😌","heart_eyes":"😍","kissing_heart":"😘","kissing":"😗","kissing_smiling_eyes":"😙","kissing_closed_eyes":"😚","yum":"😋","stuck_out_tongue_winking_eye":"😜","stuck_out_tongue_closed_eyes":"😝","stuck_out_tongue":"😛","money_mouth_face":"🤑","hugs":"🤗","nerd_face":"🤓","sunglasses":"😎","clown_face":"🤡","cowboy_hat_face":"🤠","smirk":"😏","unamused":"😒","disappointed":"😞","pensive":"😔","worried":"😟","confused":"😕","slightly_frowning_face":"🙁","frowning_face":"☹️","persevere":"😣","confounded":"😖","tired_face":"😫","weary":"😩","triumph":"😤","angry":"😠","rage":"😡","pout":"😡","no_mouth":"😶","neutral_face":"😐","expressionless":"😑","hushed":"😯","frowning":"😦","anguished":"😧","open_mouth":"😮","astonished":"😲","dizzy_face":"😵","flushed":"😳","scream":"😱","fearful":"😨","cold_sweat":"😰","cry":"😢","disappointed_relieved":"😥","drooling_face":"🤤","sob":"😭","sweat":"😓","sleepy":"😪","sleeping":"😴","roll_eyes":"🙄","thinking":"🤔","lying_face":"🤥","grimacing":"😬","zipper_mouth_face":"🤐","nauseated_face":"🤢","sneezing_face":"🤧","mask":"😷","face_with_thermometer":"🤒","face_with_head_bandage":"🤕","smiling_imp":"😈","imp":"👿","japanese_ogre":"👹","japanese_goblin":"👺","hankey":"💩","poop":"💩","shit":"💩","ghost":"👻","skull":"💀","skull_and_crossbones":"☠️","alien":"👽","space_invader":"👾","robot":"🤖","jack_o_lantern":"🎃","smiley_cat":"😺","smile_cat":"😸","joy_cat":"😹","heart_eyes_cat":"😻","smirk_cat":"😼","kissing_cat":"😽","scream_cat":"🙀","crying_cat_face":"😿","pouting_cat":"😾","open_hands":"👐","raised_hands":"🙌","clap":"👏","pray":"🙏","handshake":"🤝","+1":"👍","thumbsup":"👍","-1":"👎","thumbsdown":"👎","fist_oncoming":"👊","facepunch":"👊","punch":"👊","fist_raised":"✊","fist":"✊","fist_left":"🤛","fist_right":"🤜","crossed_fingers":"🤞","v":"✌️","metal":"🤘","ok_hand":"👌","point_left":"👈","point_right":"👉","point_up_2":"👆","point_down":"👇","point_up":"☝️","hand":"✋","raised_hand":"✋","raised_back_of_hand":"🤚","raised_hand_with_fingers_splayed":"🖐","vulcan_salute":"🖖","wave":"👋","call_me_hand":"🤙","muscle":"💪","middle_finger":"🖕","fu":"🖕","writing_hand":"✍️","selfie":"🤳","nail_care":"💅","ring":"💍","lipstick":"💄","kiss":"💋","lips":"👄","tongue":"👅","ear":"👂","nose":"👃","footprints":"👣","eye":"👁","eyes":"👀","speaking_head":"🗣","bust_in_silhouette":"👤","busts_in_silhouette":"👥","baby":"👶","boy":"👦","girl":"👧","man":"👨","woman":"👩","blonde_woman":"👱‍♀","blonde_man":"👱","person_with_blond_hair":"👱","older_man":"👴","older_woman":"👵","man_with_gua_pi_mao":"👲","woman_with_turban":"👳‍♀","man_with_turban":"👳","policewoman":"👮‍♀","policeman":"👮","cop":"👮","construction_worker_woman":"👷‍♀","construction_worker_man":"👷","construction_worker":"👷","guardswoman":"💂‍♀","guardsman":"💂","female_detective":"🕵️‍♀️","male_detective":"🕵","detective":"🕵","woman_health_worker":"👩‍⚕","man_health_worker":"👨‍⚕","woman_farmer":"👩‍🌾","man_farmer":"👨‍🌾","woman_cook":"👩‍🍳","man_cook":"👨‍🍳","woman_student":"👩‍🎓","man_student":"👨‍🎓","woman_singer":"👩‍🎤","man_singer":"👨‍🎤","woman_teacher":"👩‍🏫","man_teacher":"👨‍🏫","woman_factory_worker":"👩‍🏭","man_factory_worker":"👨‍🏭","woman_technologist":"👩‍💻","man_technologist":"👨‍💻","woman_office_worker":"👩‍💼","man_office_worker":"👨‍💼","woman_mechanic":"👩‍🔧","man_mechanic":"👨‍🔧","woman_scientist":"👩‍🔬","man_scientist":"👨‍🔬","woman_artist":"👩‍🎨","man_artist":"👨‍🎨","woman_firefighter":"👩‍🚒","man_firefighter":"👨‍🚒","woman_pilot":"👩‍✈","man_pilot":"👨‍✈","woman_astronaut":"👩‍🚀","man_astronaut":"👨‍🚀","woman_judge":"👩‍⚖","man_judge":"👨‍⚖","mrs_claus":"🤶","santa":"🎅","princess":"👸","prince":"🤴","bride_with_veil":"👰","man_in_tuxedo":"🤵","angel":"👼","pregnant_woman":"🤰","bowing_woman":"🙇‍♀","bowing_man":"🙇","bow":"🙇","tipping_hand_woman":"💁","information_desk_person":"💁","sassy_woman":"💁","tipping_hand_man":"💁‍♂","sassy_man":"💁‍♂","no_good_woman":"🙅","no_good":"🙅","ng_woman":"🙅","no_good_man":"🙅‍♂","ng_man":"🙅‍♂","ok_woman":"🙆","ok_man":"🙆‍♂","raising_hand_woman":"🙋","raising_hand":"🙋","raising_hand_man":"🙋‍♂","woman_facepalming":"🤦‍♀","man_facepalming":"🤦‍♂","woman_shrugging":"🤷‍♀","man_shrugging":"🤷‍♂","pouting_woman":"🙎","person_with_pouting_face":"🙎","pouting_man":"🙎‍♂","frowning_woman":"🙍","person_frowning":"🙍","frowning_man":"🙍‍♂","haircut_woman":"💇","haircut":"💇","haircut_man":"💇‍♂","massage_woman":"💆","massage":"💆","massage_man":"💆‍♂","business_suit_levitating":"🕴","dancer":"💃","man_dancing":"🕺","dancing_women":"👯","dancers":"👯","dancing_men":"👯‍♂","walking_woman":"🚶‍♀","walking_man":"🚶","walking":"🚶","running_woman":"🏃‍♀","running_man":"🏃","runner":"🏃","running":"🏃","couple":"👫","two_women_holding_hands":"👭","two_men_holding_hands":"👬","couple_with_heart_woman_man":"💑","couple_with_heart":"💑","couple_with_heart_woman_woman":"👩‍❤️‍👩","couple_with_heart_man_man":"👨‍❤️‍👨","couplekiss_man_woman":"💏","couplekiss_woman_woman":"👩‍❤️‍💋‍👩","couplekiss_man_man":"👨‍❤️‍💋‍👨","family_man_woman_boy":"👪","family":"👪","family_man_woman_girl":"👨‍👩‍👧","family_man_woman_girl_boy":"👨‍👩‍👧‍👦","family_man_woman_boy_boy":"👨‍👩‍👦‍👦","family_man_woman_girl_girl":"👨‍👩‍👧‍👧","family_woman_woman_boy":"👩‍👩‍👦","family_woman_woman_girl":"👩‍👩‍👧","family_woman_woman_girl_boy":"👩‍👩‍👧‍👦","family_woman_woman_boy_boy":"👩‍👩‍👦‍👦","family_woman_woman_girl_girl":"👩‍👩‍👧‍👧","family_man_man_boy":"👨‍👨‍👦","family_man_man_girl":"👨‍👨‍👧","family_man_man_girl_boy":"👨‍👨‍👧‍👦","family_man_man_boy_boy":"👨‍👨‍👦‍👦","family_man_man_girl_girl":"👨‍👨‍👧‍👧","family_woman_boy":"👩‍👦","family_woman_girl":"👩‍👧","family_woman_girl_boy":"👩‍👧‍👦","family_woman_boy_boy":"👩‍👦‍👦","family_woman_girl_girl":"👩‍👧‍👧","family_man_boy":"👨‍👦","family_man_girl":"👨‍👧","family_man_girl_boy":"👨‍👧‍👦","family_man_boy_boy":"👨‍👦‍👦","family_man_girl_girl":"👨‍👧‍👧","womans_clothes":"👚","shirt":"👕","tshirt":"👕","jeans":"👖","necktie":"👔","dress":"👗","bikini":"👙","kimono":"👘","high_heel":"👠","sandal":"👡","boot":"👢","mans_shoe":"👞","shoe":"👞","athletic_shoe":"👟","womans_hat":"👒","tophat":"🎩","mortar_board":"🎓","crown":"👑","rescue_worker_helmet":"⛑","school_satchel":"🎒","pouch":"👝","purse":"👛","handbag":"👜","briefcase":"💼","eyeglasses":"👓","dark_sunglasses":"🕶","closed_umbrella":"🌂","open_umbrella":"☂️","dog":"🐶","cat":"🐱","mouse":"🐭","hamster":"🐹","rabbit":"🐰","fox_face":"🦊","bear":"🐻","panda_face":"🐼","koala":"🐨","tiger":"🐯","lion":"🦁","cow":"🐮","pig":"🐷","pig_nose":"🐽","frog":"🐸","monkey_face":"🐵","see_no_evil":"🙈","hear_no_evil":"🙉","speak_no_evil":"🙊","monkey":"🐒","chicken":"🐔","penguin":"🐧","bird":"🐦","baby_chick":"🐤","hatching_chick":"🐣","hatched_chick":"🐥","duck":"🦆","eagle":"🦅","owl":"🦉","bat":"🦇","wolf":"🐺","boar":"🐗","horse":"🐴","unicorn":"🦄","bee":"🐝","honeybee":"🐝","bug":"🐛","butterfly":"🦋","snail":"🐌","shell":"🐚","beetle":"🐞","ant":"🐜","spider":"🕷","spider_web":"🕸","turtle":"🐢","snake":"🐍","lizard":"🦎","scorpion":"🦂","crab":"🦀","squid":"🦑","octopus":"🐙","shrimp":"🦐","tropical_fish":"🐠","fish":"🐟","blowfish":"🐡","dolphin":"🐬","flipper":"🐬","shark":"🦈","whale":"🐳","whale2":"🐋","crocodile":"🐊","leopard":"🐆","tiger2":"🐅","water_buffalo":"🐃","ox":"🐂","cow2":"🐄","deer":"🦌","dromedary_camel":"🐪","camel":"🐫","elephant":"🐘","rhinoceros":"🦏","gorilla":"🦍","racehorse":"🐎","pig2":"🐖","goat":"🐐","ram":"🐏","sheep":"🐑","dog2":"🐕","poodle":"🐩","cat2":"🐈","rooster":"🐓","turkey":"🦃","dove":"🕊","rabbit2":"🐇","mouse2":"🐁","rat":"🐀","chipmunk":"🐿","feet":"🐾","paw_prints":"🐾","dragon":"🐉","dragon_face":"🐲","cactus":"🌵","christmas_tree":"🎄","evergreen_tree":"🌲","deciduous_tree":"🌳","palm_tree":"🌴","seedling":"🌱","herb":"🌿","shamrock":"☘️","four_leaf_clover":"🍀","bamboo":"🎍","tanabata_tree":"🎋","leaves":"🍃","fallen_leaf":"🍂","maple_leaf":"🍁","mushroom":"🍄","ear_of_rice":"🌾","bouquet":"💐","tulip":"🌷","rose":"🌹","wilted_flower":"🥀","sunflower":"🌻","blossom":"🌼","cherry_blossom":"🌸","hibiscus":"🌺","earth_americas":"🌎","earth_africa":"🌍","earth_asia":"🌏","full_moon":"🌕","waning_gibbous_moon":"🌖","last_quarter_moon":"🌗","waning_crescent_moon":"🌘","new_moon":"🌑","waxing_crescent_moon":"🌒","first_quarter_moon":"🌓","moon":"🌔","waxing_gibbous_moon":"🌔","new_moon_with_face":"🌚","full_moon_with_face":"🌝","sun_with_face":"🌞","first_quarter_moon_with_face":"🌛","last_quarter_moon_with_face":"🌜","crescent_moon":"🌙","dizzy":"💫","star":"⭐️","star2":"🌟","sparkles":"✨","zap":"⚡️","fire":"🔥","boom":"💥","collision":"💥","comet":"☄","sunny":"☀️","sun_behind_small_cloud":"🌤","partly_sunny":"⛅️","sun_behind_large_cloud":"🌥","sun_behind_rain_cloud":"🌦","rainbow":"🌈","cloud":"☁️","cloud_with_rain":"🌧","cloud_with_lightning_and_rain":"⛈","cloud_with_lightning":"🌩","cloud_with_snow":"🌨","snowman_with_snow":"☃️","snowman":"⛄️","snowflake":"❄️","wind_face":"🌬","dash":"💨","tornado":"🌪","fog":"🌫","ocean":"🌊","droplet":"💧","sweat_drops":"💦","umbrella":"☔️","green_apple":"🍏","apple":"🍎","pear":"🍐","tangerine":"🍊","orange":"🍊","mandarin":"🍊","lemon":"🍋","banana":"🍌","watermelon":"🍉","grapes":"🍇","strawberry":"🍓","melon":"🍈","cherries":"🍒","peach":"🍑","pineapple":"🍍","kiwi_fruit":"🥝","avocado":"🥑","tomato":"🍅","eggplant":"🍆","cucumber":"🥒","carrot":"🥕","corn":"🌽","hot_pepper":"🌶","potato":"🥔","sweet_potato":"🍠","chestnut":"🌰","peanuts":"🥜","honey_pot":"🍯","croissant":"🥐","bread":"🍞","baguette_bread":"🥖","cheese":"🧀","egg":"🥚","fried_egg":"🍳","bacon":"🥓","pancakes":"🥞","fried_shrimp":"🍤","poultry_leg":"🍗","meat_on_bone":"🍖","pizza":"🍕","hotdog":"🌭","hamburger":"🍔","fries":"🍟","stuffed_flatbread":"🥙","taco":"🌮","burrito":"🌯","green_salad":"🥗","shallow_pan_of_food":"🥘","spaghetti":"🍝","ramen":"🍜","stew":"🍲","fish_cake":"🍥","sushi":"🍣","bento":"🍱","curry":"🍛","rice":"🍚","rice_ball":"🍙","rice_cracker":"🍘","oden":"🍢","dango":"🍡","shaved_ice":"🍧","ice_cream":"🍨","icecream":"🍦","cake":"🍰","birthday":"🎂","custard":"🍮","lollipop":"🍭","candy":"🍬","chocolate_bar":"🍫","popcorn":"🍿","doughnut":"🍩","cookie":"🍪","milk_glass":"🥛","baby_bottle":"🍼","coffee":"☕️","tea":"🍵","sake":"🍶","beer":"🍺","beers":"🍻","clinking_glasses":"🥂","wine_glass":"🍷","tumbler_glass":"🥃","cocktail":"🍸","tropical_drink":"🍹","champagne":"🍾","spoon":"🥄","fork_and_knife":"🍴","plate_with_cutlery":"🍽","soccer":"⚽️","basketball":"🏀","football":"🏈","baseball":"⚾️","tennis":"🎾","volleyball":"🏐","rugby_football":"🏉","8ball":"🎱","ping_pong":"🏓","badminton":"🏸","goal_net":"🥅","ice_hockey":"🏒","field_hockey":"🏑","cricket":"🏏","golf":"⛳️","bow_and_arrow":"🏹","fishing_pole_and_fish":"🎣","boxing_glove":"🥊","martial_arts_uniform":"🥋","ice_skate":"⛸","ski":"🎿","skier":"⛷","snowboarder":"🏂","weight_lifting_woman":"🏋️‍♀️","weight_lifting_man":"🏋","person_fencing":"🤺","women_wrestling":"🤼‍♀","men_wrestling":"🤼‍♂","woman_cartwheeling":"🤸‍♀","man_cartwheeling":"🤸‍♂","basketball_woman":"⛹️‍♀️","basketball_man":"⛹","woman_playing_handball":"🤾‍♀","man_playing_handball":"🤾‍♂","golfing_woman":"🏌️‍♀️","golfing_man":"🏌","surfing_woman":"🏄‍♀","surfing_man":"🏄","surfer":"🏄","swimming_woman":"🏊‍♀","swimming_man":"🏊","swimmer":"🏊","woman_playing_water_polo":"🤽‍♀","man_playing_water_polo":"🤽‍♂","rowing_woman":"🚣‍♀","rowing_man":"🚣","rowboat":"🚣","horse_racing":"🏇","biking_woman":"🚴‍♀","biking_man":"🚴","bicyclist":"🚴","mountain_biking_woman":"🚵‍♀","mountain_biking_man":"🚵","mountain_bicyclist":"🚵","running_shirt_with_sash":"🎽","medal_sports":"🏅","medal_military":"🎖","1st_place_medal":"🥇","2nd_place_medal":"🥈","3rd_place_medal":"🥉","trophy":"🏆","rosette":"🏵","reminder_ribbon":"🎗","ticket":"🎫","tickets":"🎟","circus_tent":"🎪","woman_juggling":"🤹‍♀","man_juggling":"🤹‍♂","performing_arts":"🎭","art":"🎨","clapper":"🎬","microphone":"🎤","headphones":"🎧","musical_score":"🎼","musical_keyboard":"🎹","drum":"🥁","saxophone":"🎷","trumpet":"🎺","guitar":"🎸","violin":"🎻","game_die":"🎲","dart":"🎯","bowling":"🎳","video_game":"🎮","slot_machine":"🎰","car":"🚗","red_car":"🚗","taxi":"🚕","blue_car":"🚙","bus":"🚌","trolleybus":"🚎","racing_car":"🏎","police_car":"🚓","ambulance":"🚑","fire_engine":"🚒","minibus":"🚐","truck":"🚚","articulated_lorry":"🚛","tractor":"🚜","kick_scooter":"🛴","bike":"🚲","motor_scooter":"🛵","motorcycle":"🏍","rotating_light":"🚨","oncoming_police_car":"🚔","oncoming_bus":"🚍","oncoming_automobile":"🚘","oncoming_taxi":"🚖","aerial_tramway":"🚡","mountain_cableway":"🚠","suspension_railway":"🚟","railway_car":"🚃","train":"🚋","mountain_railway":"🚞","monorail":"🚝","bullettrain_side":"🚄","bullettrain_front":"🚅","light_rail":"🚈","steam_locomotive":"🚂","train2":"🚆","metro":"🚇","tram":"🚊","station":"🚉","helicopter":"🚁","small_airplane":"🛩","airplane":"✈️","flight_departure":"🛫","flight_arrival":"🛬","rocket":"🚀","artificial_satellite":"🛰","seat":"💺","canoe":"🛶","boat":"⛵️","sailboat":"⛵️","motor_boat":"🛥","speedboat":"🚤","passenger_ship":"🛳","ferry":"⛴","ship":"🚢","anchor":"⚓️","construction":"🚧","fuelpump":"⛽️","busstop":"🚏","vertical_traffic_light":"🚦","traffic_light":"🚥","world_map":"🗺","moyai":"🗿","statue_of_liberty":"🗽","fountain":"⛲️","tokyo_tower":"🗼","european_castle":"🏰","japanese_castle":"🏯","stadium":"🏟","ferris_wheel":"🎡","roller_coaster":"🎢","carousel_horse":"🎠","parasol_on_ground":"⛱","beach_umbrella":"🏖","desert_island":"🏝","mountain":"⛰","mountain_snow":"🏔","mount_fuji":"🗻","volcano":"🌋","desert":"🏜","camping":"🏕","tent":"⛺️","railway_track":"🛤","motorway":"🛣","building_construction":"🏗","factory":"🏭","house":"🏠","house_with_garden":"🏡","houses":"🏘","derelict_house":"🏚","office":"🏢","department_store":"🏬","post_office":"🏣","european_post_office":"🏤","hospital":"🏥","bank":"🏦","hotel":"🏨","convenience_store":"🏪","school":"🏫","love_hotel":"🏩","wedding":"💒","classical_building":"🏛","church":"⛪️","mosque":"🕌","synagogue":"🕍","kaaba":"🕋","shinto_shrine":"⛩","japan":"🗾","rice_scene":"🎑","national_park":"🏞","sunrise":"🌅","sunrise_over_mountains":"🌄","stars":"🌠","sparkler":"🎇","fireworks":"🎆","city_sunrise":"🌇","city_sunset":"🌆","cityscape":"🏙","night_with_stars":"🌃","milky_way":"🌌","bridge_at_night":"🌉","foggy":"🌁","watch":"⌚️","iphone":"📱","calling":"📲","computer":"💻","keyboard":"⌨️","desktop_computer":"🖥","printer":"🖨","computer_mouse":"🖱","trackball":"🖲","joystick":"🕹","clamp":"🗜","minidisc":"💽","floppy_disk":"💾","cd":"💿","dvd":"📀","vhs":"📼","camera":"📷","camera_flash":"📸","video_camera":"📹","movie_camera":"🎥","film_projector":"📽","film_strip":"🎞","telephone_receiver":"📞","phone":"☎️","telephone":"☎️","pager":"📟","fax":"📠","tv":"📺","radio":"📻","studio_microphone":"🎙","level_slider":"🎚","control_knobs":"🎛","stopwatch":"⏱","timer_clock":"⏲","alarm_clock":"⏰","mantelpiece_clock":"🕰","hourglass":"⌛️","hourglass_flowing_sand":"⏳","satellite":"📡","battery":"🔋","electric_plug":"🔌","bulb":"💡","flashlight":"🔦","candle":"🕯","wastebasket":"🗑","oil_drum":"🛢","money_with_wings":"💸","dollar":"💵","yen":"💴","euro":"💶","pound":"💷","moneybag":"💰","credit_card":"💳","gem":"💎","balance_scale":"⚖️","wrench":"🔧","hammer":"🔨","hammer_and_pick":"⚒","hammer_and_wrench":"🛠","pick":"⛏","nut_and_bolt":"🔩","gear":"⚙️","chains":"⛓","gun":"🔫","bomb":"💣","hocho":"🔪","knife":"🔪","dagger":"🗡","crossed_swords":"⚔️","shield":"🛡","smoking":"🚬","coffin":"⚰️","funeral_urn":"⚱️","amphora":"🏺","crystal_ball":"🔮","prayer_beads":"📿","barber":"💈","alembic":"⚗️","telescope":"🔭","microscope":"🔬","hole":"🕳","pill":"💊","syringe":"💉","thermometer":"🌡","toilet":"🚽","potable_water":"🚰","shower":"🚿","bathtub":"🛁","bath":"🛀","bellhop_bell":"🛎","key":"🔑","old_key":"🗝","door":"🚪","couch_and_lamp":"🛋","bed":"🛏","sleeping_bed":"🛌","framed_picture":"🖼","shopping":"🛍","shopping_cart":"🛒","gift":"🎁","balloon":"🎈","flags":"🎏","ribbon":"🎀","confetti_ball":"🎊","tada":"🎉","dolls":"🎎","izakaya_lantern":"🏮","lantern":"🏮","wind_chime":"🎐","email":"✉️","envelope":"✉️","envelope_with_arrow":"📩","incoming_envelope":"📨","e-mail":"📧","love_letter":"💌","inbox_tray":"📥","outbox_tray":"📤","package":"📦","label":"🏷","mailbox_closed":"📪","mailbox":"📫","mailbox_with_mail":"📬","mailbox_with_no_mail":"📭","postbox":"📮","postal_horn":"📯","scroll":"📜","page_with_curl":"📃","page_facing_up":"📄","bookmark_tabs":"📑","bar_chart":"📊","chart_with_upwards_trend":"📈","chart_with_downwards_trend":"📉","spiral_notepad":"🗒","spiral_calendar":"🗓","calendar":"📆","date":"📅","card_index":"📇","card_file_box":"🗃","ballot_box":"🗳","file_cabinet":"🗄","clipboard":"📋","file_folder":"📁","open_file_folder":"📂","card_index_dividers":"🗂","newspaper_roll":"🗞","newspaper":"📰","notebook":"📓","notebook_with_decorative_cover":"📔","ledger":"📒","closed_book":"📕","green_book":"📗","blue_book":"📘","orange_book":"📙","books":"📚","book":"📖","open_book":"📖","bookmark":"🔖","link":"🔗","paperclip":"📎","paperclips":"🖇","triangular_ruler":"📐","straight_ruler":"📏","pushpin":"📌","round_pushpin":"📍","scissors":"✂️","pen":"🖊","fountain_pen":"🖋","black_nib":"✒️","paintbrush":"🖌","crayon":"🖍","memo":"📝","pencil":"📝","pencil2":"✏️","mag":"🔍","mag_right":"🔎","lock_with_ink_pen":"🔏","closed_lock_with_key":"🔐","lock":"🔒","unlock":"🔓","heart":"❤️","yellow_heart":"💛","green_heart":"💚","blue_heart":"💙","purple_heart":"💜","black_heart":"🖤","broken_heart":"💔","heavy_heart_exclamation":"❣️","two_hearts":"💕","revolving_hearts":"💞","heartbeat":"💓","heartpulse":"💗","sparkling_heart":"💖","cupid":"💘","gift_heart":"💝","heart_decoration":"💟","peace_symbol":"☮️","latin_cross":"✝️","star_and_crescent":"☪️","om":"🕉","wheel_of_dharma":"☸️","star_of_david":"✡️","six_pointed_star":"🔯","menorah":"🕎","yin_yang":"☯️","orthodox_cross":"☦️","place_of_worship":"🛐","ophiuchus":"⛎","aries":"♈️","taurus":"♉️","gemini":"♊️","cancer":"♋️","leo":"♌️","virgo":"♍️","libra":"♎️","scorpius":"♏️","sagittarius":"♐️","capricorn":"♑️","aquarius":"♒️","pisces":"♓️","id":"🆔","atom_symbol":"⚛️","accept":"🉑","radioactive":"☢️","biohazard":"☣️","mobile_phone_off":"📴","vibration_mode":"📳","eight_pointed_black_star":"✴️","vs":"🆚","white_flower":"💮","ideograph_advantage":"🉐","secret":"㊙️","congratulations":"㊗️","u6e80":"🈵","a":"🅰️","b":"🅱️","ab":"🆎","cl":"🆑","o2":"🅾️","sos":"🆘","x":"❌","o":"⭕️","stop_sign":"🛑","no_entry":"⛔️","name_badge":"📛","no_entry_sign":"🚫","anger":"💢","hotsprings":"♨️","no_pedestrians":"🚷","do_not_litter":"🚯","no_bicycles":"🚳","non-potable_water":"🚱","underage":"🔞","no_mobile_phones":"📵","no_smoking":"🚭","exclamation":"❗️","heavy_exclamation_mark":"❗️","grey_exclamation":"❕","question":"❓","grey_question":"❔","bangbang":"‼️","interrobang":"⁉️","low_brightness":"🔅","high_brightness":"🔆","part_alternation_mark":"〽️","warning":"⚠️","children_crossing":"🚸","trident":"🔱","fleur_de_lis":"⚜️","beginner":"🔰","recycle":"♻️","white_check_mark":"✅","chart":"💹","sparkle":"❇️","eight_spoked_asterisk":"✳️","negative_squared_cross_mark":"❎","globe_with_meridians":"🌐","diamond_shape_with_a_dot_inside":"💠","m":"Ⓜ️","cyclone":"🌀","zzz":"💤","atm":"🏧","wc":"🚾","wheelchair":"♿️","parking":"🅿️","sa":"🈂️","passport_control":"🛂","customs":"🛃","baggage_claim":"🛄","left_luggage":"🛅","mens":"🚹","womens":"🚺","baby_symbol":"🚼","restroom":"🚻","put_litter_in_its_place":"🚮","cinema":"🎦","signal_strength":"📶","koko":"🈁","symbols":"🔣","information_source":"ℹ️","abc":"🔤","abcd":"🔡","capital_abcd":"🔠","ng":"🆖","ok":"🆗","up":"🆙","cool":"🆒","new":"🆕","free":"🆓","zero":"0️⃣","one":"1️⃣","two":"2️⃣","three":"3️⃣","four":"4️⃣","five":"5️⃣","six":"6️⃣","seven":"7️⃣","eight":"8️⃣","nine":"9️⃣","keycap_ten":"🔟","hash":"#️⃣","asterisk":"*️⃣","arrow_forward":"▶️","pause_button":"⏸","play_or_pause_button":"⏯","stop_button":"⏹","record_button":"⏺","next_track_button":"⏭","previous_track_button":"⏮","fast_forward":"⏩","rewind":"⏪","arrow_double_up":"⏫","arrow_double_down":"⏬","arrow_backward":"◀️","arrow_up_small":"🔼","arrow_down_small":"🔽","arrow_right":"➡️","arrow_left":"⬅️","arrow_up":"⬆️","arrow_down":"⬇️","arrow_upper_right":"↗️","arrow_lower_right":"↘️","arrow_lower_left":"↙️","arrow_upper_left":"↖️","arrow_up_down":"↕️","left_right_arrow":"↔️","arrow_right_hook":"↪️","leftwards_arrow_with_hook":"↩️","arrow_heading_up":"⤴️","arrow_heading_down":"⤵️","twisted_rightwards_arrows":"🔀","repeat":"🔁","repeat_one":"🔂","arrows_counterclockwise":"🔄","arrows_clockwise":"🔃","musical_note":"🎵","notes":"🎶","heavy_plus_sign":"➕","heavy_minus_sign":"➖","heavy_division_sign":"➗","heavy_multiplication_x":"✖️","heavy_dollar_sign":"💲","currency_exchange":"💱","tm":"™️","copyright":"©️","registered":"®️","wavy_dash":"〰️","curly_loop":"➰","loop":"➿","end":"🔚","back":"🔙","on":"🔛","top":"🔝","soon":"🔜","heavy_check_mark":"✔️","ballot_box_with_check":"☑️","radio_button":"🔘","white_circle":"⚪️","black_circle":"⚫️","red_circle":"🔴","large_blue_circle":"🔵","small_red_triangle":"🔺","small_red_triangle_down":"🔻","small_orange_diamond":"🔸","small_blue_diamond":"🔹","large_orange_diamond":"🔶","large_blue_diamond":"🔷","white_square_button":"🔳","black_square_button":"🔲","black_small_square":"▪️","white_small_square":"▫️","black_medium_small_square":"◾️","white_medium_small_square":"◽️","black_medium_square":"◼️","white_medium_square":"◻️","black_large_square":"⬛️","white_large_square":"⬜️","speaker":"🔈","mute":"🔇","sound":"🔉","loud_sound":"🔊","bell":"🔔","no_bell":"🔕","mega":"📣","loudspeaker":"📢","eye_speech_bubble":"👁‍🗨","speech_balloon":"💬","thought_balloon":"💭","right_anger_bubble":"🗯","spades":"♠️","clubs":"♣️","hearts":"♥️","diamonds":"♦️","black_joker":"🃏","flower_playing_cards":"🎴","mahjong":"🀄️","clock1":"🕐","clock2":"🕑","clock3":"🕒","clock4":"🕓","clock5":"🕔","clock6":"🕕","clock7":"🕖","clock8":"🕗","clock9":"🕘","clock10":"🕙","clock11":"🕚","clock12":"🕛","clock130":"🕜","clock230":"🕝","clock330":"🕞","clock430":"🕟","clock530":"🕠","clock630":"🕡","clock730":"🕢","clock830":"🕣","clock930":"🕤","clock1030":"🕥","clock1130":"🕦","clock1230":"🕧","white_flag":"🏳️","black_flag":"🏴","checkered_flag":"🏁","triangular_flag_on_post":"🚩","rainbow_flag":"🏳️‍🌈","afghanistan":"🇦🇫","aland_islands":"🇦🇽","albania":"🇦🇱","algeria":"🇩🇿","american_samoa":"🇦🇸","andorra":"🇦🇩","angola":"🇦🇴","anguilla":"🇦🇮","antarctica":"🇦🇶","antigua_barbuda":"🇦🇬","argentina":"🇦🇷","armenia":"🇦🇲","aruba":"🇦🇼","australia":"🇦🇺","austria":"🇦🇹","azerbaijan":"🇦🇿","bahamas":"🇧🇸","bahrain":"🇧🇭","bangladesh":"🇧🇩","barbados":"🇧🇧","belarus":"🇧🇾","belgium":"🇧🇪","belize":"🇧🇿","benin":"🇧🇯","bermuda":"🇧🇲","bhutan":"🇧🇹","bolivia":"🇧🇴","caribbean_netherlands":"🇧🇶","bosnia_herzegovina":"🇧🇦","botswana":"🇧🇼","brazil":"🇧🇷","british_indian_ocean_territory":"🇮🇴","british_virgin_islands":"🇻🇬","brunei":"🇧🇳","bulgaria":"🇧🇬","burkina_faso":"🇧🇫","burundi":"🇧🇮","cape_verde":"🇨🇻","cambodia":"🇰🇭","cameroon":"🇨🇲","canada":"🇨🇦","canary_islands":"🇮🇨","cayman_islands":"🇰🇾","central_african_republic":"🇨🇫","chad":"🇹🇩","chile":"🇨🇱","cn":"🇨🇳","christmas_island":"🇨🇽","cocos_islands":"🇨🇨","colombia":"🇨🇴","comoros":"🇰🇲","congo_brazzaville":"🇨🇬","congo_kinshasa":"🇨🇩","cook_islands":"🇨🇰","costa_rica":"🇨🇷","cote_divoire":"🇨🇮","croatia":"🇭🇷","cuba":"🇨🇺","curacao":"🇨🇼","cyprus":"🇨🇾","czech_republic":"🇨🇿","denmark":"🇩🇰","djibouti":"🇩🇯","dominica":"🇩🇲","dominican_republic":"🇩🇴","ecuador":"🇪🇨","egypt":"🇪🇬","el_salvador":"🇸🇻","equatorial_guinea":"🇬🇶","eritrea":"🇪🇷","estonia":"🇪🇪","ethiopia":"🇪🇹","eu":"🇪🇺","european_union":"🇪🇺","falkland_islands":"🇫🇰","faroe_islands":"🇫🇴","fiji":"🇫🇯","finland":"🇫🇮","fr":"🇫🇷","french_guiana":"🇬🇫","french_polynesia":"🇵🇫","french_southern_territories":"🇹🇫","gabon":"🇬🇦","gambia":"🇬🇲","georgia":"🇬🇪","de":"🇩🇪","ghana":"🇬🇭","gibraltar":"🇬🇮","greece":"🇬🇷","greenland":"🇬🇱","grenada":"🇬🇩","guadeloupe":"🇬🇵","guam":"🇬🇺","guatemala":"🇬🇹","guernsey":"🇬🇬","guinea":"🇬🇳","guinea_bissau":"🇬🇼","guyana":"🇬🇾","haiti":"🇭🇹","honduras":"🇭🇳","hong_kong":"🇭🇰","hungary":"🇭🇺","iceland":"🇮🇸","india":"🇮🇳","indonesia":"🇮🇩","iran":"🇮🇷","iraq":"🇮🇶","ireland":"🇮🇪","isle_of_man":"🇮🇲","israel":"🇮🇱","it":"🇮🇹","jamaica":"🇯🇲","jp":"🇯🇵","crossed_flags":"🎌","jersey":"🇯🇪","jordan":"🇯🇴","kazakhstan":"🇰🇿","kenya":"🇰🇪","kiribati":"🇰🇮","kosovo":"🇽🇰","kuwait":"🇰🇼","kyrgyzstan":"🇰🇬","laos":"🇱🇦","latvia":"🇱🇻","lebanon":"🇱🇧","lesotho":"🇱🇸","liberia":"🇱🇷","libya":"🇱🇾","liechtenstein":"🇱🇮","lithuania":"🇱🇹","luxembourg":"🇱🇺","macau":"🇲🇴","macedonia":"🇲🇰","madagascar":"🇲🇬","malawi":"🇲🇼","malaysia":"🇲🇾","maldives":"🇲🇻","mali":"🇲🇱","malta":"🇲🇹","marshall_islands":"🇲🇭","martinique":"🇲🇶","mauritania":"🇲🇷","mauritius":"🇲🇺","mayotte":"🇾🇹","mexico":"🇲🇽","micronesia":"🇫🇲","moldova":"🇲🇩","monaco":"🇲🇨","mongolia":"🇲🇳","montenegro":"🇲🇪","montserrat":"🇲🇸","morocco":"🇲🇦","mozambique":"🇲🇿","myanmar":"🇲🇲","namibia":"🇳🇦","nauru":"🇳🇷","nepal":"🇳🇵","netherlands":"🇳🇱","new_caledonia":"🇳🇨","new_zealand":"🇳🇿","nicaragua":"🇳🇮","niger":"🇳🇪","nigeria":"🇳🇬","niue":"🇳🇺","norfolk_island":"🇳🇫","northern_mariana_islands":"🇲🇵","north_korea":"🇰🇵","norway":"🇳🇴","oman":"🇴🇲","pakistan":"🇵🇰","palau":"🇵🇼","palestinian_territories":"🇵🇸","panama":"🇵🇦","papua_new_guinea":"🇵🇬","paraguay":"🇵🇾","peru":"🇵🇪","philippines":"🇵🇭","pitcairn_islands":"🇵🇳","poland":"🇵🇱","portugal":"🇵🇹","puerto_rico":"🇵🇷","qatar":"🇶🇦","reunion":"🇷🇪","romania":"🇷🇴","ru":"🇷🇺","rwanda":"🇷🇼","st_barthelemy":"🇧🇱","st_helena":"🇸🇭","st_kitts_nevis":"🇰🇳","st_lucia":"🇱🇨","st_pierre_miquelon":"🇵🇲","st_vincent_grenadines":"🇻🇨","samoa":"🇼🇸","san_marino":"🇸🇲","sao_tome_principe":"🇸🇹","saudi_arabia":"🇸🇦","senegal":"🇸🇳","serbia":"🇷🇸","seychelles":"🇸🇨","sierra_leone":"🇸🇱","singapore":"🇸🇬","sint_maarten":"🇸🇽","slovakia":"🇸🇰","slovenia":"🇸🇮","solomon_islands":"🇸🇧","somalia":"🇸🇴","south_africa":"🇿🇦","south_georgia_south_sandwich_islands":"🇬🇸","kr":"🇰🇷","south_sudan":"🇸🇸","es":"🇪🇸","sri_lanka":"🇱🇰","sudan":"🇸🇩","suriname":"🇸🇷","swaziland":"🇸🇿","sweden":"🇸🇪","switzerland":"🇨🇭","syria":"🇸🇾","taiwan":"🇹🇼","tajikistan":"🇹🇯","tanzania":"🇹🇿","thailand":"🇹🇭","timor_leste":"🇹🇱","togo":"🇹🇬","tokelau":"🇹🇰","tonga":"🇹🇴","trinidad_tobago":"🇹🇹","tunisia":"🇹🇳","tr":"🇹🇷","turkmenistan":"🇹🇲","turks_caicos_islands":"🇹🇨","tuvalu":"🇹🇻","uganda":"🇺🇬","ukraine":"🇺🇦","united_arab_emirates":"🇦🇪","gb":"🇬🇧","uk":"🇬🇧","us":"🇺🇸","us_virgin_islands":"🇻🇮","uruguay":"🇺🇾","uzbekistan":"🇺🇿","vanuatu":"🇻🇺","vatican_city":"🇻🇦","venezuela":"🇻🇪","vietnam":"🇻🇳","wallis_futuna":"🇼🇫","western_sahara":"🇪🇭","yemen":"🇾🇪","zambia":"🇿🇲","zimbabwe":"🇿🇼"}`)},function(e,t,n){e.exports={angry:[`>:(`,`>:-(`],blush:[`:")`,`:-")`],broken_heart:[`</3`,`<\\3`],confused:[`:/`,`:-/`],cry:[`:'(`,`:'-(`,`:,(`,`:,-(`],frowning:[`:(`,`:-(`],heart:[`<3`],imp:[`]:(`,`]:-(`],innocent:[`o:)`,`O:)`,`o:-)`,`O:-)`,`0:)`,`0:-)`],joy:[`:')`,`:'-)`,`:,)`,`:,-)`,`:'D`,`:'-D`,`:,D`,`:,-D`],kissing:[`:*`,`:-*`],laughing:[`x-)`,`X-)`],neutral_face:[`:|`,`:-|`],open_mouth:[`:o`,`:-o`,`:O`,`:-O`],rage:[`:@`,`:-@`],smile:[`:D`,`:-D`],smiley:[`:)`,`:-)`],smiling_imp:[`]:)`,`]:-)`],sob:[`:,'(`,`:,'-(`,`;(`,`;-(`],stuck_out_tongue:[`:P`,`:-P`],sunglasses:[`8-)`,`B-)`],sweat:[`,:(`,`,:-(`],sweat_smile:[`,:)`,`,:-)`],unamused:[`:s`,`:-S`,`:z`,`:-Z`,`:$`,`:-$`],wink:[`;)`,`;-)`]}},function(e,t,n){e.exports=function(e,t){return e[t].content}},function(e,t,n){e.exports=function(e,t,n,r,i){var a=e.utils.arrayReplaceAt,o=e.utils.lib.ucmicro,s=new RegExp([o.Z.source,o.P.source,o.Cc.source].join(`|`));function c(e,r,a){var o,c=0,l=[];return e.replace(i,(function(r,i,u){var d;if(n.hasOwnProperty(r)){if(d=n[r],i>0&&!s.test(u[i-1])||i+r.length<u.length&&!s.test(u[i+r.length]))return}else d=r.slice(1,-1);i>c&&((o=new a(`text`,``,0)).content=e.slice(c,i),l.push(o)),(o=new a(`emoji`,``,0)).markup=d,o.content=t[d],l.push(o),c=i+r.length})),c<e.length&&((o=new a(`text`,``,0)).content=e.slice(c),l.push(o)),l}return function(e){var t,n,i,o,s,l=e.tokens,u=0;for(n=0,i=l.length;n<i;n++)if(l[n].type===`inline`)for(t=(o=l[n].children).length-1;t>=0;t--)(s=o[t]).type!==`link_open`&&s.type!==`link_close`||s.info===`auto`&&(u-=s.nesting),s.type===`text`&&u===0&&r.test(s.content)&&(l[n].children=o=a(o,t,c(s.content,s.level,e.Token)))}}},function(e,t,n){e.exports=function(e){var t,n=e.defs;e.enabled.length&&(n=Object.keys(n).reduce((function(t,r){return e.enabled.indexOf(r)>=0&&(t[r]=n[r]),t}),{})),t=Object.keys(e.shortcuts).reduce((function(t,r){return n[r]?Array.isArray(e.shortcuts[r])?(e.shortcuts[r].forEach((function(e){t[e]=r})),t):(t[e.shortcuts[r]]=r,t):t}),{});var r=Object.keys(n).map((function(e){return`:`+e+`:`})).concat(Object.keys(t)).sort().reverse().map((function(e){return e.replace(/[.?*+^$[\]\\(){}|-]/g,`\\$&`)})).join(`|`),i=RegExp(r),a=RegExp(r,`g`);return{defs:n,shortcuts:t,scanRE:i,replaceRE:a}}},function(e,t,n){var r=/\\([ \\!"#$%&'()*+,.\/:;<=>?@[\]^_`{|}~-])/g;function i(e,t){var n,i,a=e.posMax,o=e.pos;if(e.src.charCodeAt(o)!==126||t||o+2>=a)return!1;for(e.pos=o+1;e.pos<a;){if(e.src.charCodeAt(e.pos)===126){n=!0;break}e.md.inline.skipToken(e)}return n&&o+1!==e.pos?(i=e.src.slice(o+1,e.pos)).match(/(^|[^\\])(\\\\)*\s/)?(e.pos=o,!1):(e.posMax=e.pos,e.pos=o+1,e.push(`sub_open`,`sub`,1).markup=`~`,e.push(`text`,``,0).content=i.replace(r,`$1`),e.push(`sub_close`,`sub`,-1).markup=`~`,e.pos=e.posMax+1,e.posMax=a,!0):(e.pos=o,!1)}e.exports=function(e){e.inline.ruler.after(`emphasis`,`sub`,i)}},function(e,t,n){var r=/\\([ \\!"#$%&'()*+,.\/:;<=>?@[\]^_`{|}~-])/g;function i(e,t){var n,i,a=e.posMax,o=e.pos;if(e.src.charCodeAt(o)!==94||t||o+2>=a)return!1;for(e.pos=o+1;e.pos<a;){if(e.src.charCodeAt(e.pos)===94){n=!0;break}e.md.inline.skipToken(e)}return n&&o+1!==e.pos?(i=e.src.slice(o+1,e.pos)).match(/(^|[^\\])(\\\\)*\s/)?(e.pos=o,!1):(e.posMax=e.pos,e.pos=o+1,e.push(`sup_open`,`sup`,1).markup=`^`,e.push(`text`,``,0).content=i.replace(r,`$1`),e.push(`sup_close`,`sup`,-1).markup=`^`,e.pos=e.posMax+1,e.posMax=a,!0):(e.pos=o,!1)}e.exports=function(e){e.inline.ruler.after(`emphasis`,`sup`,i)}},function(e,t,n){e.exports=function(e){var t=e.utils.isSpace;function n(e,t){var n,r,i=e.bMarks[t]+e.tShift[t],a=e.eMarks[t];return i>=a||(r=e.src.charCodeAt(i++))!==126&&r!==58||i===(n=e.skipSpaces(i))||n>=a?-1:i}e.block.ruler.before(`paragraph`,`deflist`,(function(e,r,i,a){var o,s,c,l,u,d,f,p,m,h,g,_,v,y,b,x,S,C,w,T;if(a)return!(e.ddIndent<0)&&n(e,r)>=0;if((m=r+1)>=i||e.isEmpty(m)&&++m>=i||e.sCount[m]<e.blkIndent||(s=n(e,m))<0)return!1;f=e.tokens.length,w=!0,(T=e.push(`dl_open`,`dl`,1)).map=d=[r,0],l=r,c=m;e:for(;;){for(C=!1,(T=e.push(`dt_open`,`dt`,1)).map=[l,l],(T=e.push(`inline`,``,0)).map=[l,l],T.content=e.getLines(l,l+1,e.blkIndent,!1).trim(),T.children=[],T=e.push(`dt_close`,`dt`,-1);;){for((T=e.push(`dd_open`,`dd`,1)).map=u=[m,0],S=s,p=e.eMarks[c],h=e.sCount[c]+s-(e.bMarks[c]+e.tShift[c]);S<p&&(o=e.src.charCodeAt(S),t(o));)o===9?h+=4-h%4:h++,S++;if(s=S,x=e.tight,g=e.ddIndent,_=e.blkIndent,b=e.tShift[c],y=e.sCount[c],v=e.parentType,e.blkIndent=e.ddIndent=e.sCount[c]+2,e.tShift[c]=s-e.bMarks[c],e.sCount[c]=h,e.tight=!0,e.parentType=`deflist`,e.md.block.tokenize(e,c,i,!0),e.tight&&!C||(w=!1),C=e.line-c>1&&e.isEmpty(e.line-1),e.tShift[c]=b,e.sCount[c]=y,e.tight=x,e.parentType=v,e.blkIndent=_,e.ddIndent=g,T=e.push(`dd_close`,`dd`,-1),u[1]=m=e.line,m>=i||e.sCount[m]<e.blkIndent)break e;if((s=n(e,m))<0)break;c=m}if(m>=i||(l=m,e.isEmpty(l))||e.sCount[l]<e.blkIndent||(c=l+1)>=i||(e.isEmpty(c)&&c++,c>=i)||e.sCount[c]<e.blkIndent||(s=n(e,c))<0)break}return T=e.push(`dl_close`,`dl`,-1),d[1]=m,e.line=m,w&&function(e,t){var n,r,i=e.level+2;for(n=t+2,r=e.tokens.length-2;n<r;n++)e.tokens[n].level===i&&e.tokens[n].type===`paragraph_open`&&(e.tokens[n+2].hidden=!0,e.tokens[n].hidden=!0,n+=2)}(e,f),!0}),{alt:[`paragraph`,`reference`,`blockquote`]})}},function(e,t,n){e.exports=function(e){var t=e.utils.escapeRE,n=e.utils.arrayReplaceAt,r=e.utils.lib.ucmicro.P.source,i=e.utils.lib.ucmicro.Z.source;e.block.ruler.before(`reference`,`abbr_def`,(function(e,t,n,r){var i,a,o,s,c,l=e.bMarks[t]+e.tShift[t],u=e.eMarks[t];if(l+2>=u||e.src.charCodeAt(l++)!==42||e.src.charCodeAt(l++)!==91)return!1;for(s=l;l<u;l++){if((o=e.src.charCodeAt(l))===91)return!1;if(o===93){c=l;break}o===92&&l++}return!(c<0||e.src.charCodeAt(c+1)!==58)&&(!!r||(i=e.src.slice(s,c).replace(/\\(.)/g,`$1`),a=e.src.slice(c+2,u).trim(),i.length!==0&&a.length!==0&&(e.env.abbreviations||(e.env.abbreviations={}),e.env.abbreviations[`:`+i]===void 0&&(e.env.abbreviations[`:`+i]=a),e.line=t+1,!0)))}),{alt:[`paragraph`,`reference`]}),e.core.ruler.after(`linkify`,`abbr_replace`,(function(e){var a,o,s,c,l,u,d,f,p,m,h,g,_,v=e.tokens;if(e.env.abbreviations){for(g=RegExp(`(?:`+Object.keys(e.env.abbreviations).map((function(e){return e.substr(1)})).sort((function(e,t){return t.length-e.length})).map(t).join(`|`)+`)`),h=`(^|`+r+`|`+i+`|[`+` \r
$+<=>^\`|~`.split(``).map(t).join(``)+`])(`+Object.keys(e.env.abbreviations).map((function(e){return e.substr(1)})).sort((function(e,t){return t.length-e.length})).map(t).join(`|`)+`)($|`+r+`|`+i+`|[`+` \r
$+<=>^\`|~`.split(``).map(t).join(``)+`])`,p=new RegExp(h,`g`),o=0,s=v.length;o<s;o++)if(v[o].type===`inline`){for(a=(c=v[o].children).length-1;a>=0;a--)if((_=c[a]).type===`text`&&(f=0,u=_.content,p.lastIndex=0,d=[],g.test(u))){for(;m=p.exec(u);)(m.index>0||m[1].length>0)&&((l=new e.Token(`text`,``,0)).content=u.slice(f,m.index+m[1].length),d.push(l)),(l=new e.Token(`abbr_open`,`abbr`,1)).attrs=[[`title`,e.env.abbreviations[`:`+m[2]]]],d.push(l),(l=new e.Token(`text`,``,0)).content=m[2],d.push(l),l=new e.Token(`abbr_close`,`abbr`,-1),d.push(l),p.lastIndex-=m[3].length,f=p.lastIndex;d.length&&(f<u.length&&((l=new e.Token(`text`,``,0)).content=u.slice(f),d.push(l)),v[o].children=c=n(c,a,d))}}}}))}},function(e,t,n){function r(e,t,n,r){var i=Number(e[t].meta.id+1).toString(),a=``;return typeof r.docId==`string`&&(a=`-`+r.docId+`-`),a+i}function i(e,t){var n=Number(e[t].meta.id+1).toString();return e[t].meta.subId>0&&(n+=`:`+e[t].meta.subId),`[`+n+`]`}function a(e,t,n,r,i){var a=i.rules.footnote_anchor_name(e,t,n,r,i),o=i.rules.footnote_caption(e,t,n,r,i),s=a;return e[t].meta.subId>0&&(s+=`:`+e[t].meta.subId),`<sup class="footnote-ref"><a href="#fn`+a+`" id="fnref`+s+`">`+o+`</a></sup>`}function o(e,t,n){return(n.xhtmlOut?`<hr class="footnotes-sep" />
`:`<hr class="footnotes-sep">
`)+`<section class="footnotes">
<ol class="footnotes-list">
`}function s(){return`</ol>
</section>
`}function c(e,t,n,r,i){var a=i.rules.footnote_anchor_name(e,t,n,r,i);return e[t].meta.subId>0&&(a+=`:`+e[t].meta.subId),`<li id="fn`+a+`" class="footnote-item">`}function l(){return`</li>
`}function u(e,t,n,r,i){var a=i.rules.footnote_anchor_name(e,t,n,r,i);return e[t].meta.subId>0&&(a+=`:`+e[t].meta.subId),` <a href="#fnref`+a+`" class="footnote-backref">↩︎</a>`}e.exports=function(e){var t=e.helpers.parseLinkLabel,n=e.utils.isSpace;e.renderer.rules.footnote_ref=a,e.renderer.rules.footnote_block_open=o,e.renderer.rules.footnote_block_close=s,e.renderer.rules.footnote_open=c,e.renderer.rules.footnote_close=l,e.renderer.rules.footnote_anchor=u,e.renderer.rules.footnote_caption=i,e.renderer.rules.footnote_anchor_name=r,e.block.ruler.before(`reference`,`footnote_def`,(function(e,t,r,i){var a,o,s,c,l,u,d,f,p,m,h,g=e.bMarks[t]+e.tShift[t],_=e.eMarks[t];if(g+4>_||e.src.charCodeAt(g)!==91||e.src.charCodeAt(g+1)!==94)return!1;for(l=g+2;l<_;l++){if(e.src.charCodeAt(l)===32)return!1;if(e.src.charCodeAt(l)===93)break}if(l===g+2||l+1>=_||e.src.charCodeAt(++l)!==58)return!1;if(i)return!0;for(l++,e.env.footnotes||(e.env.footnotes={}),e.env.footnotes.refs||(e.env.footnotes.refs={}),u=e.src.slice(g+2,l-2),e.env.footnotes.refs[`:`+u]=-1,(d=new e.Token(`footnote_reference_open`,``,1)).meta={label:u},d.level=e.level++,e.tokens.push(d),a=e.bMarks[t],o=e.tShift[t],s=e.sCount[t],c=e.parentType,h=l,f=p=e.sCount[t]+l-(e.bMarks[t]+e.tShift[t]);l<_&&(m=e.src.charCodeAt(l),n(m));)m===9?p+=4-p%4:p++,l++;return e.tShift[t]=l-h,e.sCount[t]=p-f,e.bMarks[t]=h,e.blkIndent+=4,e.parentType=`footnote`,e.sCount[t]<e.blkIndent&&(e.sCount[t]+=e.blkIndent),e.md.block.tokenize(e,t,r,!0),e.parentType=c,e.blkIndent-=4,e.tShift[t]=o,e.sCount[t]=s,e.bMarks[t]=a,(d=new e.Token(`footnote_reference_close`,``,-1)).level=--e.level,e.tokens.push(d),!0}),{alt:[`paragraph`,`reference`]}),e.inline.ruler.after(`image`,`footnote_inline`,(function(e,n){var r,i,a,o,s=e.posMax,c=e.pos;return!(c+2>=s)&&e.src.charCodeAt(c)===94&&e.src.charCodeAt(c+1)===91&&(r=c+2,!((i=t(e,c+1))<0)&&(n||(e.env.footnotes||(e.env.footnotes={}),e.env.footnotes.list||(e.env.footnotes.list=[]),a=e.env.footnotes.list.length,e.md.inline.parse(e.src.slice(r,i),e.md,e.env,o=[]),e.push(`footnote_ref`,``,0).meta={id:a},e.env.footnotes.list[a]={content:e.src.slice(r,i),tokens:o}),e.pos=i+1,e.posMax=s,!0))})),e.inline.ruler.after(`footnote_inline`,`footnote_ref`,(function(e,t){var n,r,i,a,o=e.posMax,s=e.pos;if(s+3>o||!e.env.footnotes||!e.env.footnotes.refs||e.src.charCodeAt(s)!==91||e.src.charCodeAt(s+1)!==94)return!1;for(r=s+2;r<o;r++){if(e.src.charCodeAt(r)===32||e.src.charCodeAt(r)===10)return!1;if(e.src.charCodeAt(r)===93)break}return r!==s+2&&!(r>=o)&&(r++,n=e.src.slice(s+2,r-1),e.env.footnotes.refs[`:`+n]!==void 0&&(t||(e.env.footnotes.list||(e.env.footnotes.list=[]),e.env.footnotes.refs[`:`+n]<0?(i=e.env.footnotes.list.length,e.env.footnotes.list[i]={label:n,count:0},e.env.footnotes.refs[`:`+n]=i):i=e.env.footnotes.refs[`:`+n],a=e.env.footnotes.list[i].count,e.env.footnotes.list[i].count++,e.push(`footnote_ref`,``,0).meta={id:i,subId:a,label:n}),e.pos=r,e.posMax=o,!0))})),e.core.ruler.after(`inline`,`footnote_tail`,(function(e){var t,n,r,i,a,o,s,c,l,u,d=!1,f={};if(e.env.footnotes&&(e.tokens=e.tokens.filter((function(e){return e.type===`footnote_reference_open`?(d=!0,l=[],u=e.meta.label,!1):e.type===`footnote_reference_close`?(d=!1,f[`:`+u]=l,!1):(d&&l.push(e),!d)})),e.env.footnotes.list)){for(o=e.env.footnotes.list,s=new e.Token(`footnote_block_open`,``,1),e.tokens.push(s),t=0,n=o.length;t<n;t++){for((s=new e.Token(`footnote_open`,``,1)).meta={id:t,label:o[t].label},e.tokens.push(s),o[t].tokens?(c=[],(s=new e.Token(`paragraph_open`,`p`,1)).block=!0,c.push(s),(s=new e.Token(`inline`,``,0)).children=o[t].tokens,s.content=o[t].content,c.push(s),(s=new e.Token(`paragraph_close`,`p`,-1)).block=!0,c.push(s)):o[t].label&&(c=f[`:`+o[t].label]),c&&(e.tokens=e.tokens.concat(c)),a=e.tokens[e.tokens.length-1].type===`paragraph_close`?e.tokens.pop():null,i=o[t].count>0?o[t].count:1,r=0;r<i;r++)(s=new e.Token(`footnote_anchor`,``,0)).meta={id:t,subId:r,label:o[t].label},e.tokens.push(s);a&&e.tokens.push(a),s=new e.Token(`footnote_close`,``,-1),e.tokens.push(s)}s=new e.Token(`footnote_block_close`,``,-1),e.tokens.push(s)}}))}},function(e,t,n){e.exports=function(e){e.inline.ruler.before(`emphasis`,`ins`,(function(e,t){var n,r,i,a,o=e.pos,s=e.src.charCodeAt(o);if(t||s!==43||(i=(r=e.scanDelims(e.pos,!0)).length,a=String.fromCharCode(s),i<2))return!1;for(i%2&&(e.push(`text`,``,0).content=a,i--),n=0;n<i;n+=2)e.push(`text`,``,0).content=a+a,e.delimiters.push({marker:s,jump:n,token:e.tokens.length-1,level:e.level,end:-1,open:r.can_open,close:r.can_close});return e.pos+=r.length,!0})),e.inline.ruler2.before(`emphasis`,`ins`,(function(e){var t,n,r,i,a,o=[],s=e.delimiters,c=e.delimiters.length;for(t=0;t<c;t++)(r=s[t]).marker===43&&r.end!==-1&&(i=s[r.end],(a=e.tokens[r.token]).type=`ins_open`,a.tag=`ins`,a.nesting=1,a.markup=`++`,a.content=``,(a=e.tokens[i.token]).type=`ins_close`,a.tag=`ins`,a.nesting=-1,a.markup=`++`,a.content=``,e.tokens[i.token-1].type===`text`&&e.tokens[i.token-1].content===`+`&&o.push(i.token-1));for(;o.length;){for(n=(t=o.pop())+1;n<e.tokens.length&&e.tokens[n].type===`ins_close`;)n++;t!==--n&&(a=e.tokens[n],e.tokens[n]=e.tokens[t],e.tokens[t]=a)}}))}},function(e,t,n){e.exports=function(e){e.inline.ruler.before(`emphasis`,`mark`,(function(e,t){var n,r,i,a,o=e.pos,s=e.src.charCodeAt(o);if(t||s!==61||(i=(r=e.scanDelims(e.pos,!0)).length,a=String.fromCharCode(s),i<2))return!1;for(i%2&&(e.push(`text`,``,0).content=a,i--),n=0;n<i;n+=2)e.push(`text`,``,0).content=a+a,e.delimiters.push({marker:s,jump:n,token:e.tokens.length-1,level:e.level,end:-1,open:r.can_open,close:r.can_close});return e.pos+=r.length,!0})),e.inline.ruler2.before(`emphasis`,`mark`,(function(e){var t,n,r,i,a,o=[],s=e.delimiters,c=e.delimiters.length;for(t=0;t<c;t++)(r=s[t]).marker===61&&r.end!==-1&&(i=s[r.end],(a=e.tokens[r.token]).type=`mark_open`,a.tag=`mark`,a.nesting=1,a.markup=`==`,a.content=``,(a=e.tokens[i.token]).type=`mark_close`,a.tag=`mark`,a.nesting=-1,a.markup=`==`,a.content=``,e.tokens[i.token-1].type===`text`&&e.tokens[i.token-1].content===`=`&&o.push(i.token-1));for(;o.length;){for(n=(t=o.pop())+1;n<e.tokens.length&&e.tokens[n].type===`mark_close`;)n++;t!==--n&&(a=e.tokens[n],e.tokens[n]=e.tokens[t],e.tokens[t]=a)}}))}},function(e,t){var n=!0,r=!1,i=!1;function a(e,t,n){var r=e.attrIndex(t),i=[t,n];r<0?e.attrPush(i):e.attrs[r]=i}function o(e,t){for(var n=e[t].level-1,r=t-1;r>=0;r--)if(e[r].level===n)return r;return-1}function s(e,t){return e[t].type===`inline`&&function(e){return e.type===`paragraph_open`}(e[t-1])&&function(e){return e.type===`list_item_open`}(e[t-2])&&function(e){return e.content.indexOf(`[ ] `)===0||e.content.indexOf(`[x] `)===0||e.content.indexOf(`[X] `)===0}(e[t])}function c(e,t){if(e.children.unshift(function(e,t){var r=new t(`html_inline`,``,0),i=n?` disabled="" `:``;return e.content.indexOf(`[ ] `)===0?r.content=`<input class="task-list-item-checkbox"`+i+`type="checkbox">`:e.content.indexOf(`[x] `)!==0&&e.content.indexOf(`[X] `)!==0||(r.content=`<input class="task-list-item-checkbox" checked=""`+i+`type="checkbox">`),r}(e,t)),e.children[1].content=e.children[1].content.slice(3),e.content=e.content.slice(3),r){if(i){e.children.pop();var a=`task-item-`+Math.ceil(1e7*Math.random()-1e3);e.children[0].content=e.children[0].content.slice(0,-1)+` id="`+a+`">`,e.children.push(function(e,t,n){var r=new n(`html_inline`,``,0);return r.content=`<label class="task-list-item-label" for="`+t+`">`+e+`</label>`,r.attrs=[{for:t}],r}(e.content,a,t))}else e.children.unshift(function(e){var t=new e(`html_inline`,``,0);return t.content=`<label>`,t}(t)),e.children.push(function(e){var t=new e(`html_inline`,``,0);return t.content=`</label>`,t}(t))}}e.exports=function(e,t){t&&(n=!t.enabled,r=!!t.label,i=!!t.labelAfter),e.core.ruler.after(`inline`,`github-task-lists`,(function(e){for(var t=e.tokens,r=2;r<t.length;r++)s(t,r)&&(c(t[r],e.Token),a(t[r-2],`class`,`task-list-item`+(n?``:` enabled`)),a(t[o(t,r-2)],`class`,`contains-task-list`))}))}},function(e,t,n){e.exports=function(e,t,n){var r=(n||={}).marker||`:`,i=r.charCodeAt(0),a=r.length,o=n.validate||function(e){return e.trim().split(` `,2)[0]===t},s=n.render||function(e,n,r,i,a){return e[n].nesting===1&&e[n].attrPush([`class`,t]),a.renderToken(e,n,r,i,a)};e.block.ruler.before(`fence`,`container_`+t,(function(e,n,s,c){var l,u,d,f,p,m,h,g,_=!1,v=e.bMarks[n]+e.tShift[n],y=e.eMarks[n];if(i!==e.src.charCodeAt(v))return!1;for(l=v+1;l<=y&&r[(l-v)%a]===e.src[l];l++);if((d=Math.floor((l-v)/a))<3||(l-=(l-v)%a,f=e.src.slice(v,l),p=e.src.slice(l,y),!o(p)))return!1;if(c)return!0;for(u=n;!(++u>=s)&&!((v=e.bMarks[u]+e.tShift[u])<(y=e.eMarks[u])&&e.sCount[u]<e.blkIndent);)if(i===e.src.charCodeAt(v)&&!(e.sCount[u]-e.blkIndent>=4)){for(l=v+1;l<=y&&r[(l-v)%a]===e.src[l];l++);if(!(Math.floor((l-v)/a)<d||(l-=(l-v)%a,(l=e.skipSpaces(l))<y))){_=!0;break}}return h=e.parentType,g=e.lineMax,e.parentType=`container`,e.lineMax=u,(m=e.push(`container_`+t+`_open`,`div`,1)).markup=f,m.block=!0,m.info=p,m.map=[n,u],e.md.block.tokenize(e,n+1,u),(m=e.push(`container_`+t+`_close`,`div`,-1)).markup=e.src.slice(v,l),m.block=!0,e.parentType=h,e.lineMax=g,e.line=u+ +!!_,!0}),{alt:[`paragraph`,`reference`,`blockquote`,`list`]}),e.renderer.rules[`container_`+t+`_open`]=s,e.renderer.rules[`container_`+t+`_close`]=s}},function(e,t,n){e.exports=function(e){var t,n=/^@\[toc\](?:\((?:\s+)?([^\)]+)(?:\s+)?\)?)?(?:\s+?)?$/im,r=function(e){return e.replace(/[^\w\s]/gi,``).split(` `).join(`_`)};e.renderer.rules.heading_open=function(e,t){var n=e[t].tag,i=e[t+1];return i.type===`inline`?`<`+n+`><a id="`+(r(i.content)+`_`+i.map[0])+`"></a>`:`</h1>`},e.renderer.rules.toc_open=function(e,t){return``},e.renderer.rules.toc_close=function(e,t){return``},e.renderer.rules.toc_body=function(e,n){for(var i=[],a=t.tokens,o=a.length,s=0;s<o;s++)if(a[s].type===`heading_close`){var c=a[s],l=a[s-1];l.type===`inline`&&i.push({level:+c.tag.substr(1,1),anchor:r(l.content)+`_`+l.map[0],content:l.content})}var u=0,d=i.map((function(e){var t=[];if(e.level>u)for(var n=e.level-u,r=0;r<n;r++)t.push(`<ul>`),u++;else if(e.level<u)for(n=u-e.level,r=0;r<n;r++)t.push(`</ul>`),u--;return(t=t.concat([`<li><a href="#`,e.anchor,`">`,e.content,`</a></li>`])).join(``)}));return`<h3>`+e[n].content+`</h3>`+d.join(``)+Array(u+1).join(`</ul>`)},e.core.ruler.push(`grab_state`,(function(e){t=e})),e.inline.ruler.after(`emphasis`,`toc`,(function(e,t){for(;e.src.indexOf(`
`)>=0&&e.src.indexOf(`
`)<e.src.indexOf(`@[toc]`);)e.tokens.slice(-1)[0].type===`softbreak`&&(e.src=e.src.split(`
`).slice(1).join(`
`),e.pos=0);var r;if(e.src.charCodeAt(e.pos)!==64||e.src.charCodeAt(e.pos+1)!==91)return!1;var i=n.exec(e.src);if(!i||(i=i.filter((function(e){return e}))).length<1||t)return!1;(r=e.push(`toc_open`,`toc`,1)).markup=`@[toc]`,r=e.push(`toc_body`,``,0);var a=`Table of Contents`;i.length>1&&(a=i.pop()),r.content=a,r=e.push(`toc_close`,`toc`,-1);var o=0,s=e.src.indexOf(`
`);return o=s===-1?e.pos+e.posMax+1:e.pos+s,e.pos=o,!0}))}},function(e,t){e.exports=function(e,t){(t||={}).highlighted===void 0&&(t.highlighted=!0),t.hljs===void 0&&(t.hljs=`auto`),typeof t.langCheck!=`function`&&(t.langCheck=function(){}),e.options.highlight=function(n,r){var i=t.hljs;if(t.hljs===`auto`&&(i=window.hljs),t.highlighted&&r&&i){if(i.getLanguage(r))return`<pre><div class="hljs"><code class="`+e.options.langPrefix+r+`">`+i.highlight(r,n,!0).value+`</code></div></pre>`;typeof t.langCheck==`function`&&t.langCheck(r)}return`<pre><code class="`+e.options.langPrefix+r+`">`+e.utils.escapeHtml(n)+`</code></pre>`}}},function(e,t,n){var r=null;function i(e,t){var n,r,i=e.posMax,a=!0,o=!0;return n=t>0?e.src.charCodeAt(t-1):-1,r=t+1<=i?e.src.charCodeAt(t+1):-1,(n===32||n===9||r>=48&&r<=57)&&(o=!1),r!==32&&r!==9||(a=!1),{can_open:a,can_close:o}}function a(e,t){if(!r&&window.katex&&(r=window.katex),!r)return!1;var n,a,o,s;if(e.src[e.pos]!==`$`)return!1;if(!i(e,e.pos).can_open)return t||(e.pending+=`$`),e.pos+=1,!0;for(a=n=e.pos+1;(a=e.src.indexOf(`$`,a))!==-1;){for(s=a-1;e.src[s]===`\\`;)--s;if((a-s)%2==1)break;a+=1}return a===-1?(t||(e.pending+=`$`),e.pos=n,!0):a-n==0?(t||(e.pending+=`$$`),e.pos=n+1,!0):i(e,a).can_close?(t||((o=e.push(`math_inline`,`math`,0)).markup=`$`,o.content=e.src.slice(n,a)),e.pos=a+1,!0):(t||(e.pending+=`$`),e.pos=n,!0)}function o(e,t,n,i){if(!r&&window.katex&&(r=window.katex),!r)return!1;var a,o,s,c,l,u=!1,d=e.bMarks[t]+e.tShift[t],f=e.eMarks[t];if(d+2>f||e.src.slice(d,d+2)!==`$$`)return!1;if(d+=2,a=e.src.slice(d,f),i)return!0;for(a.trim().slice(-2)===`$$`&&(a=a.trim().slice(0,-2),u=!0),s=t;!u&&!(++s>=n)&&!((d=e.bMarks[s]+e.tShift[s])<(f=e.eMarks[s])&&e.tShift[s]<e.blkIndent);)e.src.slice(d,f).trim().slice(-2)===`$$`&&(c=e.src.slice(0,f).lastIndexOf(`$$`),o=e.src.slice(d,c),u=!0);return e.line=s+1,(l=e.push(`math_block`,`math`,0)).block=!0,l.content=(a&&a.trim()?a+`
`:``)+e.getLines(t+1,s,e.tShift[t],!0)+(o&&o.trim()?o:``),l.map=[t,e.line],l.markup=`$$`,!0}e.exports=function(e,t){t||={},e.inline.ruler.after(`escape`,`math_inline`,a),e.block.ruler.after(`blockquote`,`math_block`,o,{alt:[`paragraph`,`reference`,`blockquote`,`list`]}),e.renderer.rules.math_inline=function(e,n){return function(e){!r&&window.katex&&(r=window.katex),t.displayMode=!1;try{return r.renderToString(e,t)}catch(n){return t.throwOnError&&console.log(n),e}}(e[n].content)},e.renderer.rules.math_block=function(e,n){return function(e){!r&&window.katex&&(r=window.katex),t.displayMode=!0;try{return`<p>`+r.renderToString(e,t)+`</p>`}catch(n){return t.throwOnError&&console.log(n),e}}(e[n].content)+`
`}}},function(e,t){e.exports=function(e,t){e.image_add=function(t,n){e.__image instanceof Object||(e.__image={}),e.__image[t]=n},e.image_del=function(t){e.__image instanceof Object||(e.__image={}),delete e.__image[t]};var n=e.renderer.rules.image;e.renderer.rules.image=function(t,r,i,a,o){var s=t[r].attrs;if(e.__image instanceof Object){for(var c=0;c<s.length;c++)if(s[c][0]==`src`&&e.__image.hasOwnProperty(t[r].attrs[c][1])){s.push([`rel`,s[c][1]]),s[c][1]=e.__image[t[r].attrs[c][1]];break}}return n(t,r,i,a,o)}}},function(e,t,n){n(71)},function(e,t,n){(t=n(43)(!1)).push([e.i,`.op-icon.dropdown-wrapper.dropdown[data-v-218640b4]{position:relative}.op-icon.dropdown-wrapper.dropdown[type=button][data-v-218640b4]{-webkit-appearance:unset}.op-icon.dropdown-wrapper.dropdown .popup-dropdown[data-v-218640b4]{position:absolute;display:block;background:#fff;top:32px;left:-45px;min-width:130px;z-index:1600;border:1px solid #ebeef5;border-radius:4px;box-shadow:0 2px 12px 0 rgba(0,0,0,0.1)}.op-icon.dropdown-wrapper.dropdown .popup-dropdown .dropdown-item[data-v-218640b4]:first-child{border-top-left-radius:3px;border-top-right-radius:3px}.op-icon.dropdown-wrapper.dropdown .popup-dropdown .dropdown-item[data-v-218640b4]:last-child{border-bottom-left-radius:3px;border-bottom-right-radius:3px}.op-icon.dropdown-wrapper.dropdown .popup-dropdown.op-header[data-v-218640b4]{left:-30px;min-width:90px}.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-enter-active[data-v-218640b4],.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-leave-active[data-v-218640b4]{opacity:1}.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-enter[data-v-218640b4],.op-icon.dropdown-wrapper.dropdown .popup-dropdown.fade-leave-active[data-v-218640b4]{opacity:0}.op-icon.dropdown-wrapper.dropdown .popup-dropdown.transition[data-v-218640b4],.op-icon.dropdown-wrapper.dropdown .popup-dropdown.transition .dropdown-item[data-v-218640b4]{transition:all .2s linear 0s}.op-icon.dropdown-wrapper.dropdown .dropdown-item[data-v-218640b4]{height:40px;line-height:40px;font-size:14px;color:#606266;position:relative}.op-icon.dropdown-wrapper.dropdown .dropdown-item[data-v-218640b4]:hover{color:#303133;background-color:#e9e9eb}.op-icon.dropdown-wrapper.dropdown .dropdown-item input[data-v-218640b4]{position:absolute;font-size:100px;right:0;top:0;opacity:0;cursor:pointer}.op-icon.dropdown-wrapper.dropdown .dropdown-images[data-v-218640b4]{box-sizing:border-box}.op-icon.dropdown-wrapper.dropdown .dropdown-images button[data-v-218640b4]{position:absolute;top:-1px;right:5px;font-size:14px}.op-icon.dropdown-wrapper.dropdown .dropdown-images button[data-v-218640b4]:hover{color:#f56c6c;background-color:transparent}.op-icon.dropdown-wrapper.dropdown .dropdown-images span[data-v-218640b4]{display:inline-block;width:80px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.op-icon.dropdown-wrapper.dropdown .dropdown-images:hover .image-show[data-v-218640b4]{display:block !important}.op-icon.dropdown-wrapper.dropdown .dropdown-images .image-show[data-v-218640b4]{display:none;position:absolute;left:-128px;top:0;width:120px;height:90px;object-fit:contain;border:1px solid #f2f6fc}.op-icon.dropdown-wrapper.dropdown .dropdown-images .image-show.transition[data-v-218640b4]{transition:all .2s linear 0s}.op-icon.dropdown-wrapper.dropdown .dropdown-images.transition[data-v-218640b4]{transition:all .2s linear 0s}.add-image-link-wrapper[data-v-218640b4]{position:fixed;left:0;right:0;top:0;bottom:0;background:rgba(0,0,0,0.7);z-index:1600;transition:all .1s linear 0s}.add-image-link-wrapper.fade-enter-active[data-v-218640b4],.add-image-link-wrapper.fade-leave-active[data-v-218640b4]{opacity:1}.add-image-link-wrapper.fade-enter[data-v-218640b4],.add-image-link-wrapper.fade-leave-active[data-v-218640b4]{opacity:0}.add-image-link-wrapper .add-image-link[data-v-218640b4]{position:fixed;box-sizing:border-box;text-align:center;width:24%;left:38%;height:auto;padding:40px;top:25%;transition:all .1s linear 0s;z-index:3;background:#fff;border-radius:2px}@media only screen and (max-width:1500px){.add-image-link-wrapper .add-image-link[data-v-218640b4]{width:34%;left:33%}}@media only screen and (max-width:1000px){.add-image-link-wrapper .add-image-link[data-v-218640b4]{width:50%;left:25%}}@media only screen and (max-width:600px){.add-image-link-wrapper .add-image-link[data-v-218640b4]{width:80%;left:10%}}.add-image-link-wrapper .add-image-link i[data-v-218640b4]{font-size:24px;position:absolute;right:8px;top:6px;color:rgba(0,0,0,0.7);cursor:pointer}.add-image-link-wrapper .add-image-link .title[data-v-218640b4]{font-size:20px;margin-bottom:30px;margin-top:10px;font-weight:500 !important}.add-image-link-wrapper .add-image-link .input-wrapper[data-v-218640b4]{margin-top:10px;width:80%;border:1px solid #eeece8;text-align:left;margin-left:10%;height:35px}.add-image-link-wrapper .add-image-link .input-wrapper input[data-v-218640b4]{height:32px;line-height:32px;font-size:15px;width:90%;margin-left:8px;border:none;outline:none}.add-image-link-wrapper .add-image-link .op-btn[data-v-218640b4]{width:100px;height:35px;display:inline-block;margin-top:30px;cursor:pointer;text-align:center;line-height:35px;opacity:.9;border-radius:2px;letter-spacing:1px;font-size:15px}.add-image-link-wrapper .add-image-link .op-btn.sure[data-v-218640b4]{background:#2185d0;color:#fff;margin-left:5%}.add-image-link-wrapper .add-image-link .op-btn.sure[data-v-218640b4]:hover{opacity:1}.add-image-link-wrapper .add-image-link .op-btn.cancel[data-v-218640b4]{border:1px solid #bcbcbc;color:#bcbcbc}.add-image-link-wrapper .add-image-link .op-btn.cancel[data-v-218640b4]:hover{color:#000}`,``]),e.exports=t},function(e,t,n){n.r(t);var r=n(78),i=n(24);for(var a in i)[`default`].indexOf(a)<0&&function(e){n.d(t,e,(function(){return i[e]}))}(a);n(219);var o=n(3);t.default=n.n(o)()(i.default,[[`render`,r.a]])},function(e,t,n){n(73)},function(e,t,n){(t=n(43)(!1)).push([e.i,`.auto-textarea-wrapper{position:relative;width:100%;margin:0;padding:0;line-height:normal}.auto-textarea-wrapper .auto-textarea-block{display:block;white-space:pre-wrap;word-wrap:break-word !important;visibility:hidden;overflow:hidden;margin:0;padding:0;box-sizing:border-box;font-size:100%}.auto-textarea-wrapper .auto-textarea-input{font-family:Menlo,"Ubuntu Mono",Consolas,"Courier New","Microsoft Yahei","Hiragino Sans GB","WenQuanYi Micro Hei",sans-serif;position:absolute;width:100%;height:100%;top:0;left:0;margin:0;padding:0;overflow-y:hidden;color:#2c3e50}.auto-textarea-wrapper .auto-textarea-input.no-border{outline:0 none;border:none !important}.auto-textarea-wrapper .auto-textarea-input.no-resize{resize:none}`,``]),e.exports=t},function(e,t,n){n(74)},function(e,t,n){(t=n(43)(!1)).push([e.i,`textarea:disabled{background-color:#fff}.v-note-wrapper{position:relative;min-width:300px;min-height:300px;display:flex;flex-direction:column;background-color:#fff;z-index:1500;text-align:left;border:1px solid #f2f6fc;border-radius:4px}.v-note-wrapper.fullscreen{position:fixed;left:0;right:0;bottom:0;top:0;margin:0;height:auto;z-index:1501}.v-note-wrapper .v-note-op{padding:1px;width:100%;display:flex;white-space:pre-line;flex:none;min-height:40px;user-select:none;border-bottom:1px solid #f2f6fc;border-radius:4px 4px 0 0;background-color:#fff;z-index:1}.v-note-wrapper .v-note-op .v-left-item,.v-note-wrapper .v-note-op .v-right-item{flex:1;min-height:40px;box-sizing:border-box}.v-note-wrapper .v-note-op .v-left-item .op-icon-divider,.v-note-wrapper .v-note-op .v-right-item .op-icon-divider{height:40px;border-left:1px solid #e5e5e5;margin:0 6px 0 4px}.v-note-wrapper .v-note-op .v-left-item .op-icon,.v-note-wrapper .v-note-op .v-right-item .op-icon{box-sizing:border-box;display:inline-block;cursor:pointer;height:28px;width:28px;margin:6px 0 5px 0;font-size:14px;padding:4.5px 6px 5px 3.5px;color:#757575;border-radius:5px;text-align:center;background:none;border:none;outline:none;line-height:1}.v-note-wrapper .v-note-op .v-left-item .op-icon.dropdown-wrapper,.v-note-wrapper .v-note-op .v-right-item .op-icon.dropdown-wrapper{line-height:18px}.v-note-wrapper .v-note-op .v-left-item .op-icon.selected,.v-note-wrapper .v-note-op .v-right-item .op-icon.selected{color:rgba(0,0,0,0.8);background:#eaeaea}.v-note-wrapper .v-note-op .v-left-item .op-icon:hover,.v-note-wrapper .v-note-op .v-right-item .op-icon:hover{color:rgba(0,0,0,0.8);background:#e9e9eb}.v-note-wrapper .v-note-op .v-left-item.transition .op-icon,.v-note-wrapper .v-note-op .v-right-item.transition .op-icon{transition:all .2s linear 0s}.v-note-wrapper .v-note-op .v-right-item{text-align:right;padding-right:6px;max-width:30%}.v-note-wrapper .v-note-op .v-left-item{text-align:left;padding-left:6px}.v-note-wrapper .v-note-panel{position:relative;border-top:none;display:flex;flex:1;width:100%;box-sizing:border-box;overflow:hidden}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper{flex:0 0 50%;width:50%;padding:0;overflow-y:scroll;overflow-x:hidden;box-sizing:border-box;cursor:text;border-bottom-left-radius:4px}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar{width:6px;background-color:#e5e5e5}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb{background-color:#b7b7b7;border-radius:4px}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb:hover{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-thumb:active{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style::-webkit-scrollbar-track{-webkit-box-shadow:0 0 0 #808080 inset}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.scroll-style-border-radius::-webkit-scrollbar{border-bottom-right-radius:4px}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.transition{transition:all .2s linear 0s}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.single-edit{width:100%;flex:0 0 100%;overflow-y:auto}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper.single-show{width:0;flex:0 0 0;display:none}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper .content-div{width:100%;padding:20px 25px;box-sizing:border-box;outline:0 none;border:none !important;color:#2c3e50;font-size:16px}.v-note-wrapper .v-note-panel .v-note-edit.divarea-wrapper .content-input-wrapper{width:100%;padding:8px 25px 15px 25px}.v-note-wrapper .v-note-panel .v-note-show{flex:0 0 50%;width:50%;overflow-y:auto;padding:0 0;transition:all .2s linear 0s}.v-note-wrapper .v-note-panel .v-note-show.single-show{flex:0 0 100%;width:100%}.v-note-wrapper .v-note-panel .v-note-show .v-show-content,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html{width:100%;height:100%;padding:8px 25px 15px 25px;overflow-y:auto;box-sizing:border-box;overflow-x:hidden}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar{width:6px;background-color:#e5e5e5}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb{background-color:#b7b7b7;border-radius:4px}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb:hover,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb:hover{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-thumb:active,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-thumb:active{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style::-webkit-scrollbar-track,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style::-webkit-scrollbar-track{-webkit-box-shadow:0 0 0 #808080 inset}.v-note-wrapper .v-note-panel .v-note-show .v-show-content.scroll-style-border-radius::-webkit-scrollbar,.v-note-wrapper .v-note-panel .v-note-show .v-show-content-html.scroll-style-border-radius::-webkit-scrollbar{border-bottom-right-radius:4px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper{display:flex;position:absolute;width:250px;right:0;top:0;bottom:0;height:100%;flex-direction:column;background-color:rgba(255,255,255,0.98);border-left:1px solid #f2f6fc;border-right:1px solid #f2f6fc}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.transition{transition:all .1s linear 0s}@media only screen and (max-width:768px){.v-note-wrapper .v-note-panel .v-note-navigation-wrapper{width:50%}}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-enter-active,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-leave-active{height:100%}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-enter,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper.slideTop-leave-active{height:0}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title{height:50px;width:100%;border-bottom:1px solid #f2f6fc;flex:none;line-height:50px;font-size:16px;box-sizing:border-box;padding:0 12px 0 18px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title .v-note-navigation-close{float:right;color:#606266;font-size:18px;cursor:pointer}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-title .v-note-navigation-close:hover{color:#303133}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content{overflow-y:auto;flex:1;padding:8px 0}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar{width:6px;background-color:#e5e5e5}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb{background-color:#b7b7b7;border-radius:4px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb:hover{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-thumb:active{background-color:#a1a1a1}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style::-webkit-scrollbar-track{-webkit-box-shadow:0 0 0 #808080 inset}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content.scroll-style-border-radius::-webkit-scrollbar{border-bottom-right-radius:4px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h1,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6{margin:2px 0;font-weight:500;font-size:17px;color:#2185d0;cursor:pointer;line-height:normal;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:0 12px;border-bottom:none}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h1:hover,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2:hover,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3:hover,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4:hover,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5:hover,.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6:hover{color:#483d8b;text-decoration-line:underline}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h2{padding-left:27px;font-size:17px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h3{padding-left:42px;font-size:17px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h4{padding-left:58px;font-size:15px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h5{padding-left:72px;font-size:15px}.v-note-wrapper .v-note-panel .v-note-navigation-wrapper .v-note-navigation-content h6{padding-left:87px;font-size:15px}.v-note-wrapper .v-note-read-model{position:relative;display:none;width:100%;height:100%;background:#fbfbfb;padding:30px 8% 50px 8%;overflow-y:auto;box-sizing:border-box}.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar{width:6px;background-color:#e5e5e5}.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb{background-color:#b7b7b7;border-radius:4px}.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb:hover{background-color:#a1a1a1}.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-thumb:active{background-color:#a1a1a1}.v-note-wrapper .v-note-read-model.scroll-style::-webkit-scrollbar-track{-webkit-box-shadow:0 0 0 #808080 inset}.v-note-wrapper .v-note-read-model.scroll-style-border-radius::-webkit-scrollbar{border-bottom-right-radius:4px}.v-note-wrapper .v-note-read-model.show{display:block}.v-note-wrapper.shadow{border:none}.v-note-help-wrapper{position:fixed;left:0;right:0;top:0;bottom:0;background:rgba(0,0,0,0.7);z-index:1600;transition:all .1s linear 0s}.v-note-help-wrapper.fade-enter-active,.v-note-help-wrapper.fade-leave-active{opacity:1}.v-note-help-wrapper.fade-enter,.v-note-help-wrapper.fade-leave-active{opacity:0}.v-note-help-wrapper .v-note-help-content{position:relative;width:60%;max-width:800px;margin:30px auto;height:90%;min-width:320px;transition:all .1s linear 0s;z-index:3;border:1px solid #f2f6fc}.v-note-help-wrapper .v-note-help-content.shadow{border:none;box-shadow:0 0 5px rgba(0,0,0,0.157),0 0 5px rgba(0,0,0,0.227)}.v-note-help-wrapper .v-note-help-content i{font-size:28px;position:absolute;right:15px;top:8px;color:rgba(0,0,0,0.7);cursor:pointer}.v-note-help-wrapper .v-note-help-content i:hover{color:#000}.v-note-help-wrapper .v-note-help-content .v-note-help-show{width:100%;height:100%;font-size:18px;background:#fbfbfb;overflow-y:auto;padding:2% 6%}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar{width:6px;background-color:#e5e5e5}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb{background-color:#b7b7b7;border-radius:4px}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb:hover{background-color:#a1a1a1}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-thumb:active{background-color:#a1a1a1}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style::-webkit-scrollbar-track{-webkit-box-shadow:0 0 0 #808080 inset}.v-note-help-wrapper .v-note-help-content .v-note-help-show.scroll-style-border-radius::-webkit-scrollbar{border-bottom-right-radius:4px}.v-note-img-wrapper{position:fixed;display:flex;justify-content:center;align-items:center;left:0;right:0;top:0;bottom:0;background:rgba(0,0,0,0.7);z-index:1600;transition:all .1s linear 0s}.v-note-img-wrapper.fade-enter-active,.v-note-img-wrapper.fade-leave-active{opacity:1}.v-note-img-wrapper.fade-enter,.v-note-img-wrapper.fade-leave-active{opacity:0}.v-note-img-wrapper img{flex:0 0 auto;z-index:3}.v-note-img-wrapper i{font-size:28px;position:absolute;right:15px;top:8px;color:rgba(255,255,255,0.7);cursor:pointer}.v-note-img-wrapper i:hover{color:#fff}`,``]),e.exports=t},function(e,t,n){n(235)},function(e,t){},,,,,,,,,function(e,t){},,function(e,t){}])}))}));export{require_mavon_editor as t};