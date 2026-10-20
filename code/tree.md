# 陀螺匠 OA 系统 - 目录结构与模块功能说明

## 项目概述
这是一个基于Laravel框架和Swoole扩展的企业级OA系统，采用高性能的LaravelS扩展运行，支持WebSocket实时通信和定时任务等功能。

## 项目目录结构

```
.
├── app/                          # 应用程序核心目录
│   ├── Console/                  # 命令行工具
│   │   ├── Commands/             # 自定义Artisan命令
│   │   └── Kernel.php            # 命令行内核
│   ├── Constants/                # 系统常量枚举
│   │   ├── Crud/                 # 低代码枚举
│   │   ├── CustomEnum/           # 客户相关枚举
│   │   ├── ProgramEnum/          # 项目管理相关枚举
│   │   ├── System/               # 系统相关枚举
│   │   └── Work/                 # 工作相关枚举
│   ├── Exceptions/               # 异常处理模块
│   │   └── Handler.php           # 全局异常处理器
│   ├── Exports/                  # 数据导出功能
│   │   ├── CustomerExport.php    # 客户数据导出
│   │   └── LogsExport.php        # 日志数据导出
│   ├── Helpers/                  # 全局辅助函数
│   │   └── common.php            # 通用辅助函数
│   ├── Http/                     # HTTP相关组件
│   │   ├── Contract/             # 接口契约
│   │   ├── Controller/           # 控制器层
│   │   ├── Dao/                  # 数据访问对象层
│   │   ├── Middleware/           # HTTP中间件
│   │   ├── Model/                # 数据模型层
│   │   ├── Requests/             # 表单请求验证
│   │   └── Service/              # 业务逻辑服务层
│   ├── Imports/                  # 数据导入功能
│   │   └── CustomerImport.php    # 客户数据导入
│   ├── Jobs/                     # 队列任务
│   │   ├── Assess/               # 绩效考核任务
│   │   ├── Attend/               # 考勤任务
│   │   ├── Client/               # 客户管理任务
│   │   ├── Config/               # 配置任务
│   │   ├── Crud/                 # 低代码任务
│   │   ├── Work/                 # 工作任务
│   │   ├── WorkExternalContact/  # 外部联系人任务
│   │   └── 其他任务类
│   ├── Listeners/                # 事件监听器
│   │   ├── socket/               # Socket事件监听器
│   │   ├── swoole/               # Swoole事件监听器
│   │   └── wechat/               # 微信相关事件监听器
│   ├── Mail/                     # 邮件服务
│   │   ├── ClientInvoice.php     # 客户发票邮件
│   │   └── User.php              # 用户相关邮件
│   ├── Notifications/            # 消息通知
│   │   └── MessageNotice.php     # 消息通知类
│   ├── Observers/                # 模型观察者
│   │   └── 各种模型观察者
│   ├── Providers/                # 服务提供者
│   │   └── 各种服务提供者
│   └── Task/                     # 业务任务处理
│       ├── approve/              # 审批任务
│       ├── crud/                 # 低代码任务
│       ├── customer/             # 客户任务
│       ├── daily/                # 日常任务
│       ├── export/               # 导出任务
│       ├── financial/            # 财务任务
│       ├── folder/               # 文件夹任务
│       ├── frame/                # 框架任务
│       ├── message/              # 消息任务
│       ├── report/               # 报告任务
│       ├── system/               # 系统任务
│       ├── user/                 # 用户任务
│       └── TestTask.php          # 测试任务
├── bin/                          # 可执行二进制文件
├── bootstrap/                    # 启动脚本和缓存
│   └── cache/                    # 启动缓存文件
├── config/                       # 应用配置文件
│   └── 各种配置文件
├── crmeb/                        # 系统核心类库
│   ├── exceptions/               # 异常类
│   ├── interfaces/               # 接口定义
│   ├── options/                  # 选项配置
│   ├── services/                 # 核心服务
│   ├── socket/                   # Socket相关
│   ├── swoole/                   # Swoole扩展相关
│   ├── traits/                   # 特性类
│   └── utils/                    # 工具类
├── database/                     # 数据库相关
│   ├── migrations/               # 数据库迁移文件
│   ├── schema/                   # 数据库模式
│   └── seeders/                  # 数据填充文件
├── helper/                       # 辅助工具
├── public/                       # 公共资源目录
├── resources/                    # 应用资源文件
│   ├── css/                      # CSS样式文件
│   ├── js/                       # JavaScript文件
│   ├── lang/                     # 多语言文件
│   └── views/                    # 视图模板文件
├── routes/                       # 路由定义
├── storage/                      # 存储目录
│   ├── app/                      # 应用存储
│   └── logs/                     # 日志文件
├── upgrade/                      # 升级相关文件
└── vendor/                       # Composer依赖包
```

## 主要模块功能说明

### 1. app/Console/
- **功能**：提供命令行工具，用于执行各种后台任务、数据处理、系统维护等
- **Commands/**：包含各种自定义Artisan命令，用于系统运维和数据处理

### 2. app/Constants/
- **功能**：定义系统中使用的各种常量和枚举值
- **Crud/**：低代码平台相关的枚举定义
- **CustomEnum/**：客户管理相关的枚举定义
- **ProgramEnum/**：项目管理相关的枚举定义
- **System/**：系统级枚举定义
- **Work/**：工作流相关的枚举定义

### 3. app/Http/
- **Controller/**：处理HTTP请求和响应，实现业务逻辑控制
- **Dao/**：数据访问对象层，封装数据库操作
- **Middleware/**：HTTP请求中间件，实现认证、授权、日志等功能
- **Model/**：数据模型层，定义数据表映射和关系
- **Service/**：业务逻辑服务层，处理复杂的业务逻辑

### 4. app/Jobs/
- **功能**：异步队列任务处理，用于处理耗时操作
- **Assess/**：绩效考核相关的异步任务
- **Attend/**：考勤相关的异步任务
- **Client/**：客户管理相关的异步任务
- **Crud/**：低代码平台相关的异步任务
- **Work/**：工作流相关的异步任务

### 5. app/Listeners/
- **功能**：事件监听器，响应系统中发生的各种事件
- **socket/**：处理Socket相关事件
- **swoole/**：处理Swoole相关事件
- **wechat/**：处理微信相关事件

### 6. app/Exports & app/Imports/
- **功能**：数据导入导出功能，支持Excel等格式的数据批量处理

### 7. crmeb/
- **功能**：系统核心类库，封装常用的基础功能
- **无 basic/**：基类已拆至 App\Http / App\Support / tuoluojiang/services|traits
- **services/**：核心服务实现
- **utils/**：实用工具类
- **swoole/**：Swoole扩展相关功能

### 8. config/laravels.php
- **功能**：LaravelS扩展配置，用于高性能Swoole服务器运行
- 支持WebSocket实时通信
- 提供毫秒级定时任务功能
- 支持内存共享表
- 包含队列处理配置

### 9. Routes
- **web.php**：Web界面路由定义
- **api.php**：API接口路由定义
- 使用属性路由自动注册功能

### 10. 主要技术特性
- **Swoole支持**：基于Swoole扩展提供高性能并发处理能力
- **WebSocket**：实时通信功能，支持在线消息推送
- **定时任务**：毫秒级定时任务系统，替代传统Crontab
- **低代码平台**：支持快速构建业务功能的CRUD系统
- **内存共享**：使用Swoole Table提供高性能内存数据库
- **队列系统**：异步任务处理，提升系统响应性能
- **多租户支持**：支持企业级多组织架构
- **微信集成**：与企业微信深度集成