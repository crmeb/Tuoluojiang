import formCreate from '@form-create/element-ui';
import departmentTreeChange from '@/components/hr/departmentTreeChange';
import approvalBill from '@/components/hr/approvalBill';
import timeFrom from '@/components/hr/approva-time';
import city from '@/components/hr/city';
import addSel from '@/components/hr/addSel';
import moneyFrom from '@/components/hr/approva-money.vue';
import uploadFrom from '@/components/form-common/oa-upload';
import switchStatus from '@/components/switchStatus';
import holidaySetting from '@/components/hr/holidaySetting';
import infoForm from '@/components/hr/infoForm';
import { registerApprovalGroups } from './approvalGroups';

const viewForm = formCreate.factory();
const designerForm = formCreate.factory();

viewForm.component('departmentTree', departmentTreeChange);
viewForm.component('timeFrom', timeFrom);
viewForm.component('city', city);
viewForm.component('addSel', addSel);
viewForm.component('approvalBill', approvalBill);
viewForm.component('moneyFrom', moneyFrom);
viewForm.component('uploadFrom', uploadFrom);
viewForm.component('switchStatus', switchStatus);
viewForm.component('holidaySetting', holidaySetting);
viewForm.component('infoForm', infoForm);
registerApprovalGroups(viewForm);

viewForm.register('required', {
  init(inject, rule) {
    if (inject.value === false) {
      return;
    }
    if (!rule.validate) {
      rule.validate = [];
    }
    rule.validate.push({
      required: true,
      trigger: 'change',
      validator (_, val, cb) {
        if (inject.value === 'timeFrom' && ( !val || !val.dateEnd || !val.dateStart || !val.timeEnd || !val.timeStart || !val.duration)) {
          cb('请选择时间');
        } else if (val === '' || val == undefined || (Array.isArray(val) && !val.length)) {
          cb((rule.title || '') + '必填');
        } else {
          cb();
        }
        if (inject.value === 'leaveFrom' && (!val || !val.dateEnd || !val.dateStart || !val.timeEnd || !val.timeStart)) {
          cb('请选择时间');
        } else if (val == undefined || (Array.isArray(val) && !val.length)) {
          cb((rule.title || '') + '必填');
        } else {
          cb();
        }
      },
    });
  },
});

export default viewForm;
export { designerForm };
