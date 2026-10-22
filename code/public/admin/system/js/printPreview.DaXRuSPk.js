import{At as e,B as t,F as n,G as r,H as i,Pt as a,V as o,W as s,Z as c,_t as l,dt as u,lt as d,st as f,z as p}from"./chunk-vendor.Di-Ybv3u.js";import{t as m}from"./_plugin-vue_export-helper.BDNMzG2s.js";import{t as h}from"./storage.BQUCYtvq.js";e(),c();var g={name:`PrintPreview`,props:{},data(){return{visible:!1,printData:{content:[]},userinfo:h(`userInfo`,{}),enterpriseInfo:h(`enterprise`,{})}},methods:{formatDate(e){if(!e)return``;let t=new Date(e);return`${t.getFullYear()}/${String(t.getMonth()+1).padStart(2,`0`)}/${String(t.getDate()).padStart(2,`0`)} ${String(t.getHours()).padStart(2,`0`)}:${String(t.getMinutes()).padStart(2,`0`)}:${String(t.getSeconds()).padStart(2,`0`)}`},openBox(e){this.printData={...e,content:[]};let t=e=>Array.isArray(e)?e:e&&typeof e==`object`?Object.values(e):[];t(e?.content).forEach(e=>{if(e.type===`timeFrom`){this.printData.content.push(...t(e.value));return}if(e.type===`approvalBill`){t(e.value).forEach((n,r)=>{let i=t(n);i[0]&&(i[0].label+=`(${e.label}${r+1})`);let a=i.filter(e=>e?.type===`timeFrom`).flatMap(e=>t(e.value));this.printData.content.push(...i,...a)});return}e.label&&this.printData.content.push(e)}),this.$nextTick(()=>{this.visible=!0})},getStatusText(e){switch(e){case-1:return`已撤销`;case 0:return`审批中`;case 1:return`已通过`;case 2:return`已拒绝`;default:return`--`}},handlePrint(){let e=this.$refs.printContent,t=document.body.innerHTML;document.body.innerHTML=`
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
      `+e.outerHTML,window.print(),document.body.innerHTML=t,window.location.reload()}}},ee={class:`print-preview`},te={class:`print-content`,ref:`printContent`},_={class:`print-inner`},v={class:`header`},y={class:`title`},b={class:`flex flex-between mb10`},x={class:`info-table`},S={class:`value`},C={class:`value`},w={class:`value`},T={class:`value`},E={class:`content-section`},D={class:`content-table`},O={key:0,class:`label`},k={key:1,class:`value`},A={key:0},j={class:`sub-label`},M={class:`sub-value`},N={key:1},P=[`innerHTML`],F={key:3},I={key:2,class:`label`},ne={key:3,class:`value`},L={key:0},R={class:`sub-label`},z={class:`sub-value`},B={key:1},V=[`innerHTML`],H={key:3},U={class:`process-section`},W={class:`section-title`},G={class:`process-table`},K={key:0},q={class:`value`},J={class:`value`},Y={class:`footer`},X={class:`print-info`},Z={class:`mb10`},re={class:`dialog-footer`};function Q(e,c,m,h,g,Q){let $=u(`el-button`),ie=u(`el-dialog`);return f(),t(ie,{title:`打印预览`,top:`8vh`,modelValue:g.visible,"onUpdate:modelValue":c[1]||=e=>g.visible=e,"append-to-body":!0,width:`800px`},{footer:l(()=>[p(`div`,re,[r($,{onClick:c[0]||=e=>g.visible=!1},{default:l(()=>[...c[9]||=[s(`取消`,-1)]]),_:1}),r($,{type:`primary`,onClick:Q.handlePrint},{default:l(()=>[...c[10]||=[s(`打印`,-1)]]),_:1},8,[`onClick`])])]),default:l(()=>[p(`div`,ee,[p(`div`,te,[p(`div`,_,[p(`div`,v,[p(`div`,y,a(g.printData.approve?g.printData.approve.name:`审批`),1)]),p(`div`,b,[p(`span`,null,a(g.enterpriseInfo.enterprise_name||`--`),1),p(`span`,null,`审批编号：`+a(g.printData.node_id),1)]),p(`div`,x,[p(`table`,null,[p(`tbody`,null,[p(`tr`,null,[c[2]||=p(`td`,{class:`label`},`申请人`,-1),p(`td`,S,a(g.printData.card?g.printData.card.name:`--`),1),c[3]||=p(`td`,{class:`label`},`申请人部门`,-1),p(`td`,C,a(g.printData.frame?g.printData.frame.name:`--`),1)]),p(`tr`,null,[c[4]||=p(`td`,{class:`label`},`提交时间`,-1),p(`td`,w,a(g.printData.created_at?Q.formatDate(g.printData.created_at):`--`),1),c[5]||=p(`td`,{class:`label`},`当前审批状态`,-1),p(`td`,T,a(Q.getStatusText(g.printData.status)),1)])])])]),p(`div`,E,[c[6]||=p(`div`,{class:`section-title`},`申请内容`,-1),p(`div`,D,[p(`table`,null,[p(`tbody`,null,[(f(!0),i(n,null,d(Math.ceil(g.printData.content.length/2),e=>(f(),i(`tr`,{key:e},[g.printData.content[(e-1)*2]?(f(),i(`td`,O,a(g.printData.content[(e-1)*2].label),1)):o(``,!0),g.printData.content[(e-1)*2]?(f(),i(`td`,k,[g.printData.content[(e-1)*2].type===`approvalBill`?(f(),i(`div`,A,[(f(!0),i(n,null,d(g.printData.content[(e-1)*2].children,(e,t)=>(f(),i(`div`,{key:t,class:`approval-bill-item`},[p(`span`,j,a(e.label)+`:`,1),p(`span`,M,a(e.value||`--`),1)]))),128))])):Array.isArray(g.printData.content[(e-1)*2].value)?(f(),i(`div`,N,[(f(!0),i(n,null,d(g.printData.content[(e-1)*2].value,(e,t)=>(f(),i(`div`,{key:t,class:`file-item`},a(e.name||e.url),1))),128))])):g.printData.content[(e-1)*2].type===`rich_text`?(f(),i(`div`,{key:2,class:`rich-text`,innerHTML:g.printData.content[(e-1)*2].value},null,8,P)):(f(),i(`span`,F,a(g.printData.content[(e-1)*2].value||`--`),1))])):o(``,!0),g.printData.content[(e-1)*2+1]?(f(),i(`td`,I,a(g.printData.content[(e-1)*2+1].label),1)):o(``,!0),g.printData.content[(e-1)*2+1]?(f(),i(`td`,ne,[g.printData.content[(e-1)*2+1].type===`approvalBill`?(f(),i(`div`,L,[(f(!0),i(n,null,d(g.printData.content[(e-1)*2+1].children,(e,t)=>(f(),i(`div`,{key:t,class:`approval-bill-item`},[p(`span`,R,a(e.label)+`:`,1),p(`span`,z,a(e.value||`--`),1)]))),128))])):Array.isArray(g.printData.content[(e-1)*2+1].value)?(f(),i(`div`,B,[(f(!0),i(n,null,d(g.printData.content[(e-1)*2+1].value,(e,t)=>(f(),i(`div`,{key:t,class:`file-item`},a(e.name||e.url),1))),128))])):g.printData.content[(e-1)*2+1].type===`rich_text`?(f(),i(`div`,{key:2,class:`rich-text`,innerHTML:g.printData.content[(e-1)*2+1].value},null,8,V)):(f(),i(`span`,H,a(g.printData.content[(e-1)*2+1].value||`--`),1))])):o(``,!0)]))),128))])])])]),p(`div`,U,[p(`div`,W,`审批流程-`+a(Q.getStatusText(g.printData.status)),1),p(`div`,G,[p(`table`,null,[c[8]||=p(`thead`,null,[p(`tr`,null,[p(`th`,{class:`label`},`审批节点`),p(`th`,{class:`label`},`处理人`),p(`th`,{class:`label`},`操作记录`)])],-1),g.printData.users&&g.printData.users.length>0?(f(),i(`tbody`,K,[(f(!0),i(n,null,d(g.printData.users,(e,t)=>(f(),i(`tr`,{key:t},[p(`td`,q,a(e.title),1),p(`td`,J,[(f(!0),i(n,null,d(e.users,(e,t)=>(f(),i(`span`,{key:t,class:`approver-item`},a(e.card.name||`--`),1))),128))]),c[7]||=p(`td`,{class:`value`},[p(`div`,{class:`operation-record`},`--`)],-1)]))),128))])):o(``,!0)])])]),p(`div`,Y,[p(`div`,X,[p(`div`,Z,`打印日期: `+a(Q.formatDate(new Date)),1),p(`div`,null,`打印人: `+a(g.userinfo.name?g.userinfo.name:`--`),1)])])])],512)])]),_:1},8,[`modelValue`])}var $=m(g,[[`render`,Q],[`__scopeId`,`data-v-3b921624`]]);export{$ as default};