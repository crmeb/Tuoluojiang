---
name: create-api-module
description: 在 src/api/ 下新建或扩展 API 模块文件，按本项目约定生成 xxxApi 命名的接口函数。当用户说"加一个接口/API/请求"、"新建/扩展 api 文件"、"对接后端接口"时触发。
---

# create-api-module

为本项目（Vue 2.7 + axios 二次封装）生成 / 扩展 `src/api/<module>.js` 文件，确保新接口与现有风格完全一致：使用 `@/api/request` 封装、`xxxApi` 命名、中文 JSDoc 注释。

## 项目约定（必须遵守）

1. **统一封装**：所有接口通过 `import request from '@/api/request'` 调用，**不允许**直接 `import axios`。
2. **方法**：`request.get / post / put / delete / patch / head`，签名为 `(url, dataOrParams, options?)`：
   - `get` / `head` 的第二个参数是 `params`（query string）
   - `post` / `put` / `patch` / `delete` 的第二个参数是 `data`（请求体）
3. **重复请求去重**：`request.js` 内部默认会按 method+url+params 去重；若同一接口需要并发（例如轮询、文件分片），在第三个参数显式声明 `{ allowRepeat: true }`。
4. **命名约定**：导出函数名一律 `驼峰命名 + Api` 后缀，例如：
   - 列表：`xxxListApi` / `xxxDataApi`
   - 详情：`xxxDetailApi` / `xxxInfoApi` / `xxxBaseApi`
   - 新增：`xxxCreateApi` / `xxxSaveApi`
   - 修改：`xxxUpdateApi` / `xxxEditApi`
   - 删除：`xxxDeleteApi` / `xxxDelApi`
   - 操作：`xxxExportApi` / `xxxImportApi` / `xxxClaimApi`
5. **JSDoc 注释**：每个函数上方加多行注释，格式：
   ```js
   /**
    * <模块>--<子功能>--<动作>
    * @return {*}
    */
   ```
   注释正文使用中文，模块层级用 `--` 分隔（与现有文件一致，例如 `客户管理--线索-保存表单`）。
6. **URL 不带 baseURL**：`baseURL` 由 `SettingMer.https` 注入，写相对路径即可（如 `client/clues`、`client/customer/${id}`）。
7. **路径参数**：使用模板字符串 `` `client/clues/${id}` ``，**不要**手动拼接字符串。
8. **文件归类**：按业务领域分文件，参考现有划分：
   - `client.js` 客户管理（客户/线索/产品/合同）
   - `enterprise.js` 企业相关
   - `business.js` 商机
   - `system.js` 系统设置
   - `user.js` 用户与权限
   - `develop.js` 低代码 / 应用
   - `form.js` / `systemForm.js` 表单与字典
   - `public.js` 公共接口（部门、人员树等）
   - 新增模块原则上新建一个 `src/api/<scope>.js`，除非属于已有领域。

## 工作流程

1. **询问/收集信息**（若用户没给全）：
   - 业务模块（中文名 + 英文 scope，决定文件名）
   - 接口清单：每条包含 `中文说明 / HTTP method / URL / 参数形态 (query | body | path) / 函数名建议`
   - 是否需要 `allowRepeat`、自定义 headers、`login: false` 等 option
2. **判断是新建文件还是扩展**：
   - 用 Glob 查 `src/api/<scope>.js`，存在则用 Edit 追加（保持原有 import 与风格）
   - 不存在则用 Write 新建，文件首行 `import request from '@/api/request'`，第二行 `//todo <模块中文名>相关接口`
3. **写入接口**，每个函数严格按下面模板：

   ```js
   /**
    * <模块>--<子功能>--<动作>
    * @return {*}
    */
   export function xxxListApi(data) {
     return request.get(`scope/resource`, data)
   }

   export function xxxDetailApi(id) {
     return request.get(`scope/resource/${id}`)
   }

   export function xxxCreateApi(data) {
     return request.post(`scope/resource`, data)
   }

   export function xxxUpdateApi(id, data) {
     return request.put(`scope/resource/${id}`, data)
   }

   export function xxxDeleteApi(id) {
     return request.delete(`scope/resource/${id}`)
   }
   ```

4. **不要做的事**：
   - 不要写 `try/catch`，错误由 `request.js` 与 `Tips` 统一处理
   - 不要在 API 文件里写业务字段映射或 UI 提示，保持纯 HTTP 层
   - 不要新增 `axios.create`、不要改 `baseURL`
   - 不要给函数参数加 TypeScript 类型（项目是 JS）
5. **提示用户**对接调用点（视图/store）的位置，但**不修改**，除非用户明确要求。

## 示例输出（节选）

```js
import request from '@/api/request'
//todo 售后工单相关接口

/**
 * 售后工单--列表
 * @return {*}
 */
export function ticketListApi(data) {
  return request.get(`aftersale/ticket`, data)
}

/**
 * 售后工单--详情
 * @return {*}
 */
export function ticketDetailApi(id) {
  return request.get(`aftersale/ticket/${id}`)
}

/**
 * 售后工单--创建
 * @return {*}
 */
export function ticketCreateApi(data) {
  return request.post(`aftersale/ticket`, data)
}

/**
 * 售后工单--更新
 * @return {*}
 */
export function ticketUpdateApi(id, data) {
  return request.put(`aftersale/ticket/${id}`, data)
}

/**
 * 售后工单--删除
 * @return {*}
 */
export function ticketDeleteApi(id) {
  return request.delete(`aftersale/ticket/${id}`)
}

/**
 * 售后工单--导出
 * @return {*}
 */
export function ticketExportApi(data) {
  return request.get(`aftersale/ticket/export`, data, { allowRepeat: true })
}
```

## 完成后

- 用 `rg "import.*from '@/api/<scope>'" src` 提示用户哪些视图已经引用了同模块，便于继续接线
- 不要执行 lint / build，让用户自行决定是否运行
