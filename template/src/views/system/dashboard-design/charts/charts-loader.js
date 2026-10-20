import zhLang from './lang/zh-CN.js'
import enLang from './lang/en-US.js'
import zhCNExtension from './lang/zh-CN_extension'
import './runtime'
import { registerChartLibProperties, registerPEWidgets } from '@/views/system/dashboard-design/charts/property-editor'

import {
  addZHExtensionLang,
  addENExtensionLang,
  PERegister
} from '../utils.js'

export const loadChartsExtension = (app) => {
  //加载语言文件
  addZHExtensionLang(zhLang)
  addZHExtensionLang(zhCNExtension)
  addENExtensionLang(enLang)
  registerChartLibProperties(app, PERegister)
  registerPEWidgets(app)
}
