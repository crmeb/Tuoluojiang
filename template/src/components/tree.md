# 组件目录结构说明

## 目录结构

```
.
├── BaseContainer/            # 页面容器基础组件
├── BaseTable/                # 表格基础组件
├── FormPageHeader/           # 表单页头部组件
├── FormPageFooter/           # 表单页底部操作栏
├── code-editor/              # 代码编辑器组件
├── common/                   # 通用基础组件
├── customer/                 # 客户相关组件
├── demoSupport/              # 演示站悬浮客服组件
├── develop/                  # 开发工具组件
├── form-common/              # 表单通用组件
├── form-designer/            # 表单设计器组件
├── form-render/              # 表单渲染组件
├── hr/                       # 人力资源组件
├── invoice/                  # 发票相关组件
├── isFullScreen/             # 全屏切换组件
├── mlReferenceSearch/        # 智能搜索组件
├── openFile/                 # 文件打开组件
├── scEcharts/                # 图表组件
├── setting/                  # 设置相关组件
├── simpleTable/              # 简单表格组件
├── svg-icon/                 # SVG图标组件
├── svg-icon-nc/              # SVG图标组件(非标准)
├── switchStatus/             # 状态切换组件
├── systemAuth/               # 系统授权组件
├── uploadPicture/            # 图片上传组件
├── user/                     # 用户相关组件
├── verifition/               # 验证码组件
├── workFlow/                 # 工作流组件
├── xmind-editor/             # 思维导图编辑器
├── zbDialog/                 # 自定义对话框组件
├── zbSetConditions/          # 条件设置组件
```

## 组件分类说明

### 通用组件 (common/)
- 提供基础UI元素
- 包含按钮、输入框、表格等基础组件

### 表单组件 (form-*)
- form-common: 表单通用组件
- form-designer: 可视化表单设计工具
- form-render: 表单渲染引擎

### 业务组件 (*)
- 按业务领域划分的专用组件
- 如客户管理、人力资源、工作流等