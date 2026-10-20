# 工具函数目录结构说明

## 目录结构

```
.
├── smart-vue-i18n/           # 智能国际化工具
├── wangeditor/               # wangEditor 扩展（表格粘贴处理）
├── auth.js                   # 登录 token 存取
├── authDialog.js             # 认证弹窗工具
├── avatar.js                 # 默认头像识别与兜底处理
├── compressImg.js            # 图片压缩工具
├── directive.js              # 指令工具
├── emitter.js                # 事件发射器
├── file.js                   # 文件操作工具
├── filePreview.js            # 文件预览单例服务
├── formDesignerUtils.js      # 表单设计器工具
├── format.js                 # 格式化工具
├── get-page-title.js         # 页面标题获取
├── i18n.js                   # 国际化工具
├── i18ns.js                  # 国际化配置
├── index.js                  # 工具函数集合
├── luckysheetLoader.js       # Luckysheet 资源动态加载
├── menu-sync.js              # 菜单变更的跨标签页同步
├── menu.js                   # 菜单与权限加载
├── numberFormat.js           # 数字格式化工具
├── path.js                   # 路径拼接工具
├── preload.js                # 预加载工具
├── resourceUtil.js           # 资源工具
├── rgbaster.js               # 颜色提取工具
├── route-access.js           # 路由可访问性判断
├── storage.js                # 安全的 localStorage 读写
├── tips.js                   # 提示工具
├── uploadChunk.js            # 分片上传工具
├── uploadCloud.js            # 云上传工具
├── util.js                   # 通用工具
├── validate.js               # 验证工具
├── validators.js             # 验证器集合
└── watermark.util.js         # 水印工具
```

## 工具分类说明

### 数据处理类
- format.js: 数据格式化工具
- numberFormat.js: 数字格式化工具
- validate.js: 数据验证工具

### 文件处理类
- file.js: 文件操作工具
- filePreview.js: 文件预览服务
- compressImg.js: 图片压缩工具
- uploadChunk.js: 分片上传工具

### 路由与权限类
- auth.js: 登录 token 存取
- menu.js: 菜单与权限加载
- route-access.js: 路由可访问性判断

### UI相关类
- watermark.util.js: 水印工具
- authDialog.js: 认证弹窗工具
- avatar.js: 头像兜底处理
