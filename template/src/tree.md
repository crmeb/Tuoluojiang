# 源代码目录结构说明

## 目录结构

```
.
├── api/                      # API接口定义
├── assets/                   # 静态资源文件
├── bootstrap/                # 应用启动编排（插件、全局组件、样式等注册）
├── components/               # 公共组件
├── composables/              # 组合式函数（useTable、usePage 等）
├── config/                   # 项目配置文件
├── constants/                # 全局常量（弹窗尺寸、文件类型等）
├── directive/                # Vue指令
├── filters/                  # Vue过滤器
├── icons/                    # 图标资源
├── lang/                     # 多语言配置
├── layout/                   # 页面布局组件
├── libs/                     # 工具库和辅助函数
├── mixins/                   # Vue混入
├── router/                   # 路由配置
├── store/                    # Vuex状态管理
├── styles/                   # 样式文件
├── utils/                    # 工具函数
└── views/                    # 页面视图组件
├── App.vue                   # 根组件
├── main.js                   # 应用入口文件
├── permission.js             # 权限管理
└── settings.js               # 项目设置
```

## 功能模块说明

### API层 (api/)
- 定义与后端交互的接口
- 包含各种业务模块的API请求方法

### 组件层 (components/)
- 封装可复用的UI组件
- 包含表单组件、业务组件等

### 视图层 (views/)
- 页面级别的组件
- 对应不同的路由页面

### 状态管理 (store/)
- 使用Vuex进行全局状态管理
- 按模块组织状态逻辑

### 工具类 (utils/)
- 提供通用工具函数
- 包含日期处理、格式化等功能