
<div style="text-align: center" align="center">
<img src="./help/resource/logo.png" width='120' />
<p>陀螺匠·AI协同办公系统</p>
<p> 企业AI大脑-AI时代的组织智能中枢</p>

[官网](https://tuoluojiang.com)｜[在线体验](https://demo.tuoluojiang.com/)｜[帮助文档](https://doc.crmeb.com/tuoluojiang)｜[应用市场](https://www.crmeb.com/market/)｜[技术社区](https://www.crmeb.com/ask/thread/list/157)
</div>

**用心做开源，我们也很需要你的鼓励！右上角Star🌟，等你点亮！**

---

## 📝 项目介绍

AI的极速发展，加速了企业管理从数字化走向智能化。陀螺匠以「让企业经营更简单」为团队使命，致力于打造组织自身的智能中枢。区别于传统管理软件仅被动存储数据，陀螺匠作为企业AI大脑，原生打通客户、商品、订单、库存、审批、人事、财务等全业务模块，统一归集经营数据，根除多系统带来的数据孤岛；依托 AI 智能体 Agent 连接人、业务数据、企业知识库与大模型，重塑人与系统的交互方式。

依托精细化权限体系，陀螺匠实现会话即操作。员工使用自然语言，就能查询业绩、录入客户与订单、库存查询、发起审批、提交报销等各类经营动作，AI 在权限范围内安全调取业务信息，推动管理模式从人适配系统，转变为系统主动服务于人。产品持续弱化传统界面操作，强化 AI 交互能力，把繁琐的软件点击操作，简化为一句话即可完成任务。

这颗企业 AI 大脑同时具备长期记忆与知识沉淀能力，通过向量化知识库+结构化经营数据存储，将业务文档、沟通记录、项目经验持续结构化留存，沉淀为可复用、可追溯的组织资产，规避人员流动造成经验流失。我们将深度探索AI底层能力，通过持续迭代打造企业私有的的智能经营底座，在 AI 浪潮下帮助企业落地真实可感知的组织提效工具。


[🔗功能列表](https://doc.crmeb.com/tuoluojiang/tuoluojiang20/21083)｜[📮提交反馈](https://www.crmeb.com/ask/thread/list/157)

---

## ✴️ 开源说明
 **开源说明：** 项目代码100%开源，基于 AGPL-3.0，支持免费商用！

 **技 术 栈：** 系统基于Laravel 9+Swoole+Vue.js 框架开发，前后端分离；

 **多种模型接入：** 支持无需注册，开箱即用的一号通token接入；支持市场常见的近20种大模型接入；支持自定义私有化大模型接入；

 **Agent智能体：** 通过自然语言与AI对话，完成系统常用办公操作；例如：客户订单录入、商品库存查询、提交汇报、提交审批、提交报销等；

 **MCP接入能力：** 系统支持接入外部MCP进行调用，也支持通过MCP对接外部AI办公，系统通过第三方AI办公软件完成系统操作，例如：对接workbuddy、豆包办公、百度搭子等；

 **企业知识库：** 知识库支持常见文档格式（Word / PDF / TXT / Markdown / PPTX / XLSX 等）上传，系统自动向量化处理，可用于智能体调用及外部智能客服调用。

 **低 代 码：** 低代码可以通过可视化界面和预定义组件快速构建模块应用，减少编码工作，缩短应用开发周期；

 **功能模块：** 系统非插件模式，所见即所得，系统所有功能全部开源，并支持免费商用；

---

## 🐳 Docker 运行

本地环境只使用 `code/docker`。在 `code/docker` 目录执行：

```bash
./manage.sh start
```

启动后访问 http://localhost:20300 ，安装页为 http://localhost:20300/install 。端口、Redis Stack 与安装说明见 `code/docker/README.md`。


## 💯 技术亮点

1.前后端分离：后端Laravel9+Swoole，前端ElementUI+Vue；

2.代码规范：遵循PSR-2命名规范、Restful标准接口、代码严格分层、注释齐全；

3.权限管理：内置强大灵活的权限管理，RBAC权限模型；

4.开发配置：低代码增加配置、系统组合数据模块；

5.高性能：支持Redis缓存、队列、长连接、多种云储存；

6.低代码：自定义表单、自定义字段、自定义数据字典、自定义审批流、自定义列表展示、自定义触发事件、自定义图表统计、自定义对接接口，快速开发企业常见应用。


---

## 🏠 产品架构图

![输入图片说明](./help/resource/framework.jpg)

---

## 🖼️ 页面预览

![输入图片说明](help/resource/%E5%B7%A5%E4%BD%9C%E5%8F%B0-%E6%96%B0%E6%A1%86%E6%9E%B6-%E8%93%9D.png)
![输入图片说明](help/resource/%E6%96%B0%E5%A2%9E-%E7%9C%8B%E6%9D%BF%E8%AE%BE%E8%AE%A1.png)
![输入图片说明](help/resource/%E5%AE%A2%E6%88%B7%E7%AE%A1%E7%90%86%E5%88%97%E8%A1%A8-%E4%BD%8E%E4%BB%A3%E7%A0%81.png)
![输入图片说明](help/resource/%E5%BC%80%E5%8F%91-%E5%AE%9E%E4%BD%93%E7%AE%A1%E7%90%86-%E8%A1%A8%E5%8D%95%E8%AE%BE%E8%AE%A1.png)
![输入图片说明](help/resource/%E6%96%B0%E5%BB%BA.png)
![输入图片说明](help/resource/%E5%BC%80%E5%8F%91-%E8%A7%A6%E5%8F%91%E5%99%A8%E7%AE%A1%E7%90%86.png)
![输入图片说明](help/resource/%E5%AE%A2%E6%88%B7-%E5%8F%91%E7%A5%A8%E7%AE%A1%E7%90%86.png)
![输入图片说明](help/resource/%E5%8A%9E%E5%85%AC-%E6%88%91%E7%9A%84%E6%97%A5%E7%A8%8B%EF%BC%88%E5%A4%A9%EF%BC%89.png)
![输入图片说明](help/resource/%E9%A1%B9%E7%9B%AE-%E6%88%91%E7%9A%84%E4%BB%BB%E5%8A%A1.png)
![输入图片说明](help/resource/%E5%BA%94%E7%94%A8%E5%88%97%E8%A1%A8%E8%AE%BE%E7%BD%AE.png)
![输入图片说明](help/resource/AI%E5%8A%A9%E6%89%8B%E5%88%97%E8%A1%A8%E6%A0%B7%E5%BC%8F.png)

---

## ✅ 帮助文档

官网地址：https://tuoluojiang.com
文档地址：https://doc.crmeb.com/tuoluojiang

---

## ✳️ 系统演示

PC端：**https://demo.tuoluojiang.com**

账号：**18888888888**  密码：**888888**

H5端：**https://demo.tuoluojiang.com/work**

账号：**18888888888**  密码：**888888**

项目会不定时进行更新，建议⭐star⭐和👁️watch👁️一份。

---

## 📚 运行配置

**服务器运行环境：**

PHP Nginx

PHP 8.0-8.2

MySQL 5.7-8.0

Redis


**前端运行环境：**

node版本：14.21.3

npm版本：6.14.18


**推荐新手刚入门使用如下配置：**

宝塔Linux控制面板

Nginx 1.1.8

MySQL 5.7

PHP 8.0

Supervisor管理器

---

## ⭕ 技术开发框架

**后端框架**

| 技术     | 版本号 | 官网 
| ----------- | ----------- | ----------- 
| laravel      |  9.52.16       | https://laravel.com 
| laravel-s    |  3.7.39      | https://github.com/hhxsv5/laravel-s
| laravel-route-attributes      |  1.20       | https://github.com/spatie/laravel-route-attributes 
| jwt-auth      |   2.1       | https://github.com/tymondesigns/jwt-auth 
| form-builder     |  2.0       | https://github.com/xaboy/form-builder 

**前端框架**

|技术| 名称 | 版本号| 官网 |
|----------- |----------- |----------- |-----------  |
| vue2      |  MVVM框架   | 2.6.10   | https://v2.cn.vuejs.org/v2/guide |
| elementUI      |  UI组件库  | 2.15.14  | https://element.eleme.cn |
| vuex      |  状态管理模式 + 库   | 3.1.0  | https://vuex.vuejs.org|
| vue-router  |  路由管理   | 3.0.2  | https://router.vuejs.org |
| webpack     | 静态模块打包工具  | 4.44.2   | https://www.webpackjs.com |



## 👊想要快速安装，教程来助攻！

宝塔安装教程：https://doc.crmeb.com/tuoluojiang/tuoluojiang19/19107

Linux安装教程：https://doc.crmeb.com/tuoluojiang/tuoluojiang19/19108

前端打包教程：https://doc.crmeb.com/tuoluojiang/tuoluojiang19/19212

---

## ⛽️二开支持

使用文档：https://doc.crmeb.com/tuoluojiang

接口文档：https://apifox.com/apidoc/shared-c280d902-20d4-491e-987f-c7aa7e6150e1

视频教程：https://www.bilibili.com/video/BV1t7qzYsE4a

技术社区：https://www.crmeb.com/ask/thread/list/157

---


## 📞 陀螺匠互动

**陀螺匠开源技术交流群**


![输入图片说明](help/resource/image1.jpg)

**技术社区！找方法、提bug、看官方消息、拿活跃大奖！都在 **[**CRMEB 技术社区**](https://www.crmeb.com/ask/thread/list/157)** 应有尽有~**

---

## 📸 特别鸣谢

排名不分先后，感谢这些软件的开发者：Laravel、Element-UI、Vue、Mysql、Redis、Echarts、Form-Create等，如有遗漏请联系我！

---

## 💾 版权信息


本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2017-2025 by 陀螺匠 (https://www.tuoluojiang.com)

All rights reserved。

陀螺匠商标和著作权所有者为西安众邦网络科技有限公司。


## 欢迎了解CRMEB多商户商城系统

![输入图片说明](help/resource/%E5%BC%80%E6%BA%90%E7%AB%99%E5%A4%9A%E5%95%86%E6%88%B7PHP.jpg)


