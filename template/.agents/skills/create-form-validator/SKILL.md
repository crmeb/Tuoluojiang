---
name: create-form-validator
description: 为 el-form 编写校验规则，复用项目既有正则（utils/validators.ts 的 getRegExp）与 utils/validate.ts 的 valid* 函数，沉淀手机号/邮箱/金额/正整数/中文/URL/身份证等通用业务校验，统一 required/message/trigger 风格。当用户说"加表单校验/这个字段要校验/手机号/金额校验规则/必填校验"时触发。
---

# create-form-validator

为本项目（Vue 3 + Element Plus）的 `el-form` 生成校验规则，统一风格、复用既有正则，避免每个表单重复手写。

## 先分清两条路（别用错）

| 场景 | 用什么 | 说明 |
|---|---|---|
| **手写业务 `el-form`**（CRUD 弹窗、设置页等） | **普通 `rules` 对象 + 自定义 `validator`**（本 skill 主线） | 最常见，下面模板 |
| 表单设计器（form-designer）里的字段 | `@/utils/validators` 的 `FormValidators` | 它依赖 `rule.label`/`rule.errorMsg`，只服务 `form-designer/.../fieldMixin.js`，**手写表单不要直接套** |

## 可复用的既有资产（优先复用，不要重造正则）

- 正则来源 `src/utils/validators.ts` 的 `getRegExp(name)`：`number / letter / letterAndNumber / mobilePhone / email / url / chinese / noChinese / letterStartNumberIncluded`。
- 校验函数 `src/utils/validate.ts`：`validEmail / validURL / validUsername / validUpperCase / validLowerCase / validAlphabets / isExternal` 等。
- 手机号正则（项目口径）：`/^[1][3-9][0-9]{9}$/`；邮箱、URL 同 `getRegExp`。

## 基础规则写法

```js
// <script setup lang="ts"> 里
const rules = {
  name: [
    { required: true, message: '请输入名称', trigger: 'blur' },
    { max: 50, message: '不超过 50 个字符', trigger: 'blur' }
  ],
  // 简单格式用 pattern（复用项目正则口径）
  mobile: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^[1][3-9][0-9]{9}$/, message: '手机号格式有误', trigger: 'blur' }
  ],
  // 下拉/选择用 change
  type: [{ required: true, message: '请选择类型', trigger: 'change' }]
}
```

约定：
- 文案统一"请输入xx"/"请选择xx"；输入类 `trigger: 'blur'`，选择/日期类 `trigger: 'change'`。
- 长度限制优先在 `el-input` 上加 `maxlength + show-word-limit`，规则里再兜底 `max`。
- 金额/数量等数字，`el-form` 的 `v-model` 是字符串，规则里用 `type: 'number'` 时要先 `:model` 转数字，否则用自定义 `validator` 更稳。

## 通用业务校验工厂（建议沉淀）

需要在多页复用的非内置规则（金额、正整数、身份证等），抽成工厂函数放到 `src/utils/`（如新建 `formRules.js`），不要每页 copy 一段 `validator`：

```js
// src/utils/formRules.js（按需新建）
export const required = (msg, trigger = 'blur') => ({ required: true, message: msg, trigger })

export const mobileRule = (trigger = 'blur') => ({
  pattern: /^[1][3-9][0-9]{9}$/, message: '手机号格式有误', trigger
})

// 金额：最多两位小数、>0
export const amountRule = (trigger = 'blur') => ({
  validator(rule, value, cb) {
    if (value === '' || value == null) return cb()
    if (!/^\d+(\.\d{1,2})?$/.test(String(value))) return cb(new Error('金额最多两位小数'))
    if (Number(value) <= 0) return cb(new Error('金额需大于 0'))
    cb()
  },
  trigger
})

// 正整数
export const positiveIntRule = (trigger = 'blur') => ({
  validator(rule, value, cb) {
    if (value === '' || value == null) return cb()
    if (!/^[1-9]\d*$/.test(String(value))) return cb(new Error('请输入正整数'))
    cb()
  },
  trigger
})
```

页面使用：
```js
import { required, mobileRule, amountRule } from '@/utils/formRules'
const rules = {
  contact: [required('请输入联系人')],
  mobile: [required('请输入手机号'), mobileRule()],
  price: [required('请输入金额'), amountRule()]
}
```

## 校验执行

```js
formRef.value.validate(async (ok) => {
  if (!ok) return
  // 通过后提交
})
// 重置时清空校验态
formRef.value.clearValidate()
```

## 不要做的事

- 不要把表单设计器的 `FormValidators`（`@/utils/validators`）硬塞进手写 `el-form` 的 `rules`，它依赖 `rule.label` 等字段。
- 不要在多个页面重复粘贴同一段 `validator`，抽到 `src/utils/formRules.js` 复用。
- 不要新造一套手机号/邮箱正则，复用 `getRegExp` 与 `validate.ts` 的既有口径。
- 不要用 `type: 'number'` 校验字符串型 `v-model`（Element 会判失败），数字校验优先自定义 `validator`。
- 校验只挡格式，必填/唯一性等业务约束以后端返回为准，不在前端臆造"已通过"。
