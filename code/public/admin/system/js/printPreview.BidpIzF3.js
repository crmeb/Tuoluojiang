import{g as c}from"./index.yImhtc9G.js";import{n as p}from"./chunk-vendor.PvV0voEW.js";const d={name:"PrintPreview",props:{},data(){return{visible:!1,printData:{content:[]},userinfo:c("userInfo",{}),enterpriseInfo:c("enterprise",{})}},methods:{formatDate(i){if(!i)return"";const t=new Date(i),a=t.getFullYear(),e=String(t.getMonth()+1).padStart(2,"0"),n=String(t.getDate()).padStart(2,"0"),s=String(t.getHours()).padStart(2,"0"),r=String(t.getMinutes()).padStart(2,"0"),l=String(t.getSeconds()).padStart(2,"0");return`${a}/${e}/${n} ${s}:${r}:${l}`},openBox(i){this.printData={...i,content:[]};const t=e=>Array.isArray(e)?e:e&&typeof e=="object"?Object.values(e):[];t(i?.content).forEach(e=>{if(e.type==="timeFrom"){this.printData.content.push(...t(e.value));return}if(e.type==="approvalBill"){t(e.value).forEach((n,s)=>{const r=t(n);r[0]&&(r[0].label+=`(${e.label}${s+1})`);const l=r.filter(o=>o?.type==="timeFrom").flatMap(o=>t(o.value));this.printData.content.push(...r,...l)});return}e.label&&this.printData.content.push(e)}),this.$nextTick(()=>{this.visible=!0})},getStatusText(i){switch(i){case-1:return"已撤销";case 0:return"审批中";case 1:return"已通过";case 2:return"已拒绝";default:return"--"}},handlePrint(){const i=this.$refs.printContent,t=document.body.innerHTML,a=`
        <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
          }
          .print-content {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
          }
          .header {
            text-align: center;
            margin-bottom: 20px;
          }
          .title {
            font-size: 20px;
            margin: 0 0 10px 0;
          }
          .company {
            font-size: 16px;
            color: #666;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
          }
          th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 13px;
          }
          .label {
            background-color: #f5f7fa;
            font-weight: bold;
            width: 150px;
          }
          .value {
            width: 250px;
          }
          .section-title {
            font-size: 14px;
            margin: 20px 0 15px 0;
            text-align: center;
            padding-bottom: 5px;
          }
          .footer {
          display: flex;
justify-content: flex-end;
    text-align: left;
    font-size: 13px;
    color: #606266;
  
          }
          .operation-record {
            margin-bottom: 5px;
          }
          .approver-item + .approver-item {
            margin-left: 8px;
          }
          .approval-bill-item {
            margin-bottom: 5px;
          }
          .sub-label {
            font-weight: bold;
            margin-right: 5px;
          }
          .file-item {
            margin-bottom: 3px;
            color: #1890ff;
          }
          .rich-text {
            line-height: 1.6;
          }
        </style>
      `;document.body.innerHTML=a+i.outerHTML,window.print(),document.body.innerHTML=t,window.location.reload()}}};var v=function(){var t=this,a=t._self._c;return a("el-dialog",{attrs:{title:"打印预览",top:"8vh",visible:t.visible,"append-to-body":!0,width:"800px"},on:{"update:visible":function(e){t.visible=e}},scopedSlots:t._u([{key:"footer",fn:function(){return[a("div",{staticClass:"dialog-footer"},[a("el-button",{on:{click:function(e){t.visible=!1}}},[t._v("取消")]),a("el-button",{attrs:{type:"primary"},on:{click:t.handlePrint}},[t._v("打印")])],1)]},proxy:!0}])},[a("div",{staticClass:"print-preview"},[a("div",{ref:"printContent",staticClass:"print-content"},[a("div",{staticClass:"print-inner"},[a("div",{staticClass:"header"},[a("div",{staticClass:"title"},[t._v(t._s(t.printData.approve?t.printData.approve.name:"审批"))])]),a("div",{staticClass:"flex flex-between mb10"},[a("span",[t._v(t._s(t.enterpriseInfo.enterprise_name||"--"))]),a("span",[t._v("审批编号："+t._s(t.printData.node_id))])]),a("div",{staticClass:"info-table"},[a("table",[a("tr",[a("td",{staticClass:"label"},[t._v("申请人")]),a("td",{staticClass:"value"},[t._v(t._s(t.printData.card?t.printData.card.name:"--"))]),a("td",{staticClass:"label"},[t._v("申请人部门")]),a("td",{staticClass:"value"},[t._v(t._s(t.printData.frame?t.printData.frame.name:"--"))])]),a("tr",[a("td",{staticClass:"label"},[t._v("提交时间")]),a("td",{staticClass:"value"},[t._v(t._s(t.printData.created_at?t.formatDate(t.printData.created_at):"--"))]),a("td",{staticClass:"label"},[t._v("当前审批状态")]),a("td",{staticClass:"value"},[t._v(t._s(t.getStatusText(t.printData.status)))])])])]),a("div",{staticClass:"content-section"},[a("div",{staticClass:"section-title"},[t._v("申请内容")]),a("div",{staticClass:"content-table"},[a("table",t._l(Math.ceil(t.printData.content.length/2),function(e){return a("tr",{key:e},[t.printData.content[(e-1)*2]?a("td",{staticClass:"label"},[t._v(t._s(t.printData.content[(e-1)*2].label))]):t._e(),t.printData.content[(e-1)*2]?a("td",{staticClass:"value"},[t.printData.content[(e-1)*2].type==="approvalBill"?a("div",t._l(t.printData.content[(e-1)*2].children,function(n,s){return a("div",{key:s,staticClass:"approval-bill-item"},[a("span",{staticClass:"sub-label"},[t._v(t._s(n.label)+":")]),a("span",{staticClass:"sub-value"},[t._v(t._s(n.value||"--"))])])}),0):Array.isArray(t.printData.content[(e-1)*2].value)?a("div",t._l(t.printData.content[(e-1)*2].value,function(n,s){return a("div",{key:s,staticClass:"file-item"},[t._v(" "+t._s(n.name||n.url)+" ")])}),0):t.printData.content[(e-1)*2].type==="rich_text"?a("div",{staticClass:"rich-text",domProps:{innerHTML:t._s(t.printData.content[(e-1)*2].value)}}):a("span",[t._v(t._s(t.printData.content[(e-1)*2].value||"--"))])]):t._e(),t.printData.content[(e-1)*2+1]?a("td",{staticClass:"label"},[t._v(t._s(t.printData.content[(e-1)*2+1].label))]):t._e(),t.printData.content[(e-1)*2+1]?a("td",{staticClass:"value"},[t.printData.content[(e-1)*2+1].type==="approvalBill"?a("div",t._l(t.printData.content[(e-1)*2+1].children,function(n,s){return a("div",{key:s,staticClass:"approval-bill-item"},[a("span",{staticClass:"sub-label"},[t._v(t._s(n.label)+":")]),a("span",{staticClass:"sub-value"},[t._v(t._s(n.value||"--"))])])}),0):Array.isArray(t.printData.content[(e-1)*2+1].value)?a("div",t._l(t.printData.content[(e-1)*2+1].value,function(n,s){return a("div",{key:s,staticClass:"file-item"},[t._v(" "+t._s(n.name||n.url)+" ")])}),0):t.printData.content[(e-1)*2+1].type==="rich_text"?a("div",{staticClass:"rich-text",domProps:{innerHTML:t._s(t.printData.content[(e-1)*2+1].value)}}):a("span",[t._v(t._s(t.printData.content[(e-1)*2+1].value||"--"))])]):t._e()])}),0)])]),a("div",{staticClass:"process-section"},[a("div",{staticClass:"section-title"},[t._v("审批流程-"+t._s(t.getStatusText(t.printData.status)))]),a("div",{staticClass:"process-table"},[a("table",[a("thead",[a("tr",[a("th",{staticClass:"label"},[t._v("审批节点")]),a("th",{staticClass:"label"},[t._v("处理人")]),a("th",{staticClass:"label"},[t._v("操作记录")])])]),t.printData.users&&t.printData.users.length>0?a("tbody",t._l(t.printData.users,function(e,n){return a("tr",{key:n},[a("td",{staticClass:"value"},[t._v(t._s(e.title))]),a("td",{staticClass:"value"},t._l(e.users,function(s,r){return a("span",{key:r,staticClass:"approver-item"},[t._v(t._s(s.card.name||"--"))])}),0),a("td",{staticClass:"value"},[a("div",{staticClass:"operation-record"},[t._v("--")])])])}),0):t._e()])])]),a("div",{staticClass:"footer"},[a("div",{staticClass:"print-info"},[a("div",{staticClass:"mb10"},[t._v("打印日期: "+t._s(t.formatDate(new Date)))]),a("div",[t._v("打印人: "+t._s(t.userinfo.name?t.userinfo.name:"--"))])])])])])])])},u=[],_=p(d,v,u,!1,null,"c3a14170");const m=_.exports;export{m as default};
