import {isEmptyStr, isNull} from "./util";

/** 表单设计器里的校验规则配置，额外带了字段标签与自定义提示 */
export interface DesignerRule {
  label?: string
  errorMsg?: string
  regExp?: RegExp | string
}

type ValidateCallback = (error?: Error) => void
type FieldValidator = (rule: DesignerRule, value: any, callback: ValidateCallback) => void

const commonRegExp: Record<string, RegExp> = {
  number: /^[-]?\d+(\.\d+)?$/,
  letter: /^[A-Za-z]+$/,
  letterAndNumber: /^[A-Za-z0-9]+$/,
  mobilePhone: /^[1][3-9][0-9]{9}$/,
  letterStartNumberIncluded: /^[A-Za-z]+[A-Za-z\d]*$/,
  noChinese: /^[^\u4e00-\u9fa5]+$/,
  chinese: /^[\u4e00-\u9fa5]+$/,
  email: /^([-_A-Za-z0-9.]+)@([_A-Za-z0-9]+\.)+[A-Za-z0-9]{2,3}$/,
  url: /^([hH][tT]{2}[pP]:\/\/|[hH][tT]{2}[pP][sS]:\/\/)(([A-Za-z0-9-~]+)\.)+([A-Za-z0-9-~/])+$/,
}

const getRegExp = function (validatorName: string) {
  return commonRegExp[validatorName]
}

/**
 * 把 `/pattern/flags` 形式的字符串解析成 RegExp。
 * 原实现直接 eval 正则字面量，rollup 会告警（eval 妨碍压缩且有安全风险），
 * 这里改为显式解析，行为与 eval 一致。
 */
const toRegExp = function (source: RegExp | string | null | undefined) {
  if (source instanceof RegExp) return source
  if (isNull(source) || isEmptyStr(String(source))) return null

  const literal = String(source).trim()
  const matched = /^\/([\s\S]*)\/([gimsuy]*)$/.exec(literal)
  return matched ? new RegExp(matched[1], matched[2]) : new RegExp(literal)
}

const validateFn = function (
  validatorName: string,
  rule: DesignerRule,
  value: any,
  callback: ValidateCallback,
  defaultErrorMsg: string
) {
  //空值不校验
  if (isNull(value) || (value.length <= 0)) {
    callback()
    return
  }

  const reg = getRegExp(validatorName)

  if (!reg.test(value)) {
    const errTxt = rule.errorMsg || defaultErrorMsg
    callback(new Error(errTxt))
  } else {
    callback()
  }
}

const FormValidators: Record<string, FieldValidator> = {

  /* 数字 */
  number(rule, value, callback) {
    validateFn('number', rule, value, callback, '[' + rule.label + ']包含非数字字符')
  },

  /* 字母 */
  letter(rule, value, callback) {
    validateFn('letter', rule, value, callback, '[' + rule.label + ']包含非字母字符')
  },

  /* 字母和数字 */
  letterAndNumber(rule, value, callback) {
    validateFn('letterAndNumber', rule, value, callback, '[' + rule.label + ']只能输入字母或数字')
  },

  /* 手机号码 */
  mobilePhone(rule, value, callback) {
    validateFn('mobilePhone', rule, value, callback, '[' + rule.label + ']手机号码格式有误')
  },

  /* 禁止空白字符开头 */
  noBlankStart(rule, value, callback) {
    //暂未实现
  },

  /* 禁止空白字符结尾 */
  noBlankEnd(rule, value, callback) {
    //暂未实现
  },

  /* 字母开头，仅可包含数字 */
  letterStartNumberIncluded(rule, value, callback) {
    validateFn('letterStartNumberIncluded', rule, value, callback, '[' + rule.label + ']必须以字母开头，可包含数字')
  },

  /* 禁止中文输入 */
  noChinese(rule, value, callback) {
    validateFn('noChinese', rule, value, callback, '[' + rule.label + ']不可输入中文字符')
  },

  /* 必须中文输入 */
  chinese(rule, value, callback) {
    validateFn('chinese', rule, value, callback, '[' + rule.label + ']只能输入中文字符')
  },

  /* 电子邮箱 */
  email(rule, value, callback) {
    validateFn('email', rule, value, callback, '[' + rule.label + ']邮箱格式有误')
  },

  /* URL网址 */
  url(rule, value, callback) {
    validateFn('url', rule, value, callback, '[' + rule.label + ']URL格式有误')
  },

  /*测试
  test(rule, value, callback, errorMsg) {
    //空值不校验
    if (isNull(value) || (value.length <= 0)) {
      callback()
      return
    }

    if (value < 100) {
      callback(new Error('[' + rule.label + ']不能小于100'))
    } else {
      callback()
    }
  },
  */

  regExp(rule, value, callback) {
    //空值不校验
    if (isNull(value) || (value.length <= 0)) {
      callback()
      return
    }

    const pattern = toRegExp(rule.regExp)
    if (!pattern) {
      callback()
      return
    }
    if (!pattern.test(value)) {
      const errTxt = rule.errorMsg || '[' + rule.label + ']invalid value'
      callback(new Error(errTxt))
    } else {
      callback()
    }
  },

}

export default FormValidators
