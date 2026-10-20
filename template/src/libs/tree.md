# 工具库目录结构说明

## 目录结构

```
.
├── ai/                       # AI 运行时与浮球控制
│   ├── plugin-loader.js      # Embed/Preview SDK 脚本加载
│   └── index.js              # OA 开关、路由显隐与实例生命周期适配
├── bus.js                    # 全局事件总线
├── customer.js               # 客户相关功能
├── helper.js                 # 辅助函数库
├── iconfont-icons.js         # IconFont图标库
├── modal-form.js             # 弹窗表单功能
├── notice.js                 # 通知功能
├── noticeHandle.js           # 通知处理
├── pickerOptions.js          # 选择器选项
├── public.js                 # 公共功能
├── settingMer.js             # 设置合并功能
├── start.js                  # 启动初始化
└── waterMark.js              # 水印功能
```

## 功能说明

### 通用工具类
- bus.js: 全局事件通信总线
- helper.js: 通用辅助函数
- public.js: 公共功能函数

### UI相关类
- notice.js: 通知提醒功能
- modal-form.js: 弹窗表单功能
- waterMark.js: 水印生成功能

### 业务相关类
- ai/: AI 功能集成与悬浮入口控制
- customer.js: 客户相关业务逻辑
- settingMer.js: 设置合并处理
