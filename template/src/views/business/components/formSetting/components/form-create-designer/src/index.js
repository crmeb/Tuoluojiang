import FcDesigner from './components/FcDesigner.vue';
import DragTool from './components/DragTool.vue';
import Struct from './components/Struct.vue';
import Fetch from './components/Fetch.vue';
import Validate from './components/Validate.vue';
import DragBox from './components/DragBox.vue';
import './style/index.css';
import draggable from 'vuedraggable';
import unique from '@form-create/utils/lib/unique';
import { makeOptionsRule } from './utils/index';
import departmentTreeChange from '@/components/hr/departmentTreeChange';
import approvalBillView from '@/components/hr/approvalBillView';
import timeFrom from '@/components/hr/approva-time';
import city from '@/components/hr/city';
import addSel from '@/components/hr/addSel';
import { designerForm } from './config/viewForm';
import moneyFrom from '@/components/hr/approva-money.vue';
import uploadFrom from '@/components/form-common/oa-upload';
import switchStatus from '@/components/switchStatus';
import holidaySetting from '@/components/hr/holidaySetting';
import infoForm from '@/components/hr/infoForm';
import { registerApprovalGroups } from './config/approvalGroups';
designerForm.component('draggable', draggable);
designerForm.component('DragTool', DragTool);
designerForm.component('DragBox', DragBox);
designerForm.component('Validate', Validate);
designerForm.component('Struct', Struct);
designerForm.component('Fetch', Fetch);
designerForm.component('departmentTree', departmentTreeChange);
designerForm.component('timeFrom', timeFrom);
designerForm.component('city', city);
designerForm.component('approvalBill', approvalBillView);
designerForm.component('addSel', addSel);
designerForm.component('moneyFrom', moneyFrom);
designerForm.component('uploadFrom', uploadFrom);
designerForm.component('switchStatus', switchStatus);
designerForm.component('holidaySetting', holidaySetting);
designerForm.component('infoForm', infoForm);
registerApprovalGroups(designerForm);
designerForm.register('_fc', {
  init(fc, rule) {
    rule._id = unique();
    if (fc.repeat) rule.field = unique();
    if (fc.value) {
      rule.effect._fc = false;
    }
  },
});

designerForm.register('_fc_tool', {
  init(_, rule) {
    rule.props.unique = unique();
  },
});

FcDesigner.install = function (app) {
  app.component('FcDesigner', FcDesigner);
};

FcDesigner.makeOptionsRule = makeOptionsRule;

export default FcDesigner;
