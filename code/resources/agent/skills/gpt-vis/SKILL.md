---
name: gpt-vis
description: 统计、趋势、占比、排行、对比等可量化数据，或流程/组织/关系需要图表展示时启用
role: format
when:
  intents:
    - chat
    - data_query
    - multi_step
  triggers:
    - 画图
    - 可视化
    - 折线图
    - 柱状图
    - 饼图
    - 条形图
    - 趋势图
    - 雷达图
    - 漏斗图
    - 散点图
    - 热力图
    - 桑基图
    - 思维导图
    - 统计
    - 趋势
    - 占比
    - 排行
    - 对比
    - 同比
    - 环比
    - 分布
    - 柱状
    - 折线
    - 组织架构
    - 流程图
    - 关系图
    - 层级
    - /画(一)?个?(图|表|趋势)/
    - /用(图表|折线|柱状|饼图|可视化)/
    - /做成(图|表|图表)/
    - /多少|数量/
  exclude_triggers:
    - 优势
    - 简介
    - 是什么
    - 介绍一下
    - 介绍下
    - 产品定位
    - 服务对象
    - 核心理念
    - 适用场景
    - 能做什么
    - 有什么功能
  context_triggers:
    - /查询结果|工具结果|数据|SQL|rows|count/i
prefer:
  mcp: false
priority: 85
enabled: true
output:
  format: markdown
  template: |
    ```GPT-Vis
    vis {{type}}
    data
      - ...
    title {{title}}
    ```
---

# GPT-Vis 图表可视化

本 skill 由会话自动匹配注入，约束最终回答中的图表交付形态。

## 何时输出

默认不要输出图表；但当系统意图为 `data_query`/`multi_step` 且工具结果含金额、比例、趋势、排行或对比等量化字段时，必须在文字结论后追加 **一个** ` ```GPT-Vis ` 代码块。无量化结果或仅有概念说明时不要输出。

- 必须使用 GPT-Vis Syntax（首行 `vis <type>`）
- 禁止输出 JSON 代替围栏（除非用户明确只要配置对象）
- 禁止已弃用的 ` ```infographic ` 语法
- 禁止产品简介/优势介绍/概念说明等无数值装饰图
- 禁止空占位骨架图
- 无可视化价值或数据不足时不要输出代码块

## 步骤

1. 按数据特征选 type（见下表）
2. 默认用 **Markdown 语法模式**（` ```GPT-Vis ` 围栏 + Syntax）
3. 用户明确要可运行代码时再用代码模式（HTML + CDN）

## 支持的图表类型

| type | 适用场景 |
| --- | --- |
| line | 时间序列趋势 |
| area | 时间序列趋势+总量 |
| column | 分类数据对比 |
| bar | 分类对比（标签长） |
| pie | 部分占整体比例 |
| scatter | 两变量关系 |
| dual-axes | 不同量级数据对比 |
| histogram | 连续数值频次分布 |
| boxplot | 数据分布与异常值 |
| violin | 数据分布密度 |
| radar | 多维度对比 |
| funnel | 流程转化率 |
| waterfall | 累计增减变化 |
| liquid | 百分比/进度 |
| word-cloud | 词频展示 |
| venn | 集合交并关系 |
| treemap | 层级数据占比 |
| sankey | 流量流向 |
| flow-diagram | 流程步骤 |
| mindmap | 层级知识梳理 |
| indented-tree | 树节点层级/目录 |
| network-graph | 实体间关联关系 |
| organization-chart | 组织层级 |
| fishbone-diagram | 根因分析 |
| summary | 内容总结（T8，见 references/summary.md） |

## Markdown 输出格式（默认）

语言标记固定为 `GPT-Vis`，内容区用 Syntax，**首行必须** `vis <type>`：

````markdown
```GPT-Vis
vis line
data
  - time 2020
    value 100
  - time 2021
    value 120
title 年度趋势
```
````

## Syntax 规则

- 基本属性：每行 `key value`
- 对象数组：`data` 下用 `- ` 开头，子字段缩进
- 纯值数组：每项 `- value`
- 含空格字符串：加引号
- 嵌套对象：对象名独占一行，子属性缩进
- 树形：`children` 下用 `- ` 递归

示例：

```
vis column
data
  - category A产品
    value 30
    group 线上
  - category B产品
    value 50
    group 线上
title 产品销量对比
axisXTitle 产品
axisYTitle 销量（万）
stack true
theme light
style
  palette
    - #5B8FF9
    - #61DDAA
```


## 通用配置

所有图表均可含：`title`、`theme`（`default|light|dark|academy`）、`style.backgroundColor`、`style.palette`。

### line / area

`data: { time, value, group? }[]`；`axisXTitle`/`axisYTitle`；`stack` 仅 area；`style.lineWidth?`

### column / bar

`data: { category, value, group? }[]`；`stack?`；`group?`

### pie

`data: { category, value }[]`（value 禁止百分比数字）；`innerRadius`=0.6 为环图

### scatter

`data: { x, y, group? }[]`

### dual-axes

`categories: string[]`；`series: { type: line|column, data: number[], axisYTitle? }[]`

### histogram

`data: number[]`；`binNumber?`

### boxplot / violin

`data: { category, value, group? }[]`（同一 category 需多条）

### radar

`data: { name, value, group? }[]`；`align?`（true 时各轴共享最大值）

### funnel

`data: { category, value }[]`

### waterfall

`data: { category, value }[]`（可为负）；palette 顺序 [正值色, 负值色, 汇总色]

### liquid

`percent: 0~1`；`shape?: rect|circle|pin|triangle`

### word-cloud

`data: { text, value }[]`

### venn

`data: { sets: string|string[], value, label? }[]`（交集如 `sets: "A,B"`）

### treemap

`data: { name, value, children? }[]`

### sankey

`data: { source, target, value }[]`；`nodeAlign?: left|center|right|justify`

### flow-diagram / network-graph

`data: { nodes: { name }[], edges: { source, target, name? }[] }`；network-graph 可加 `layout`

### mindmap / indented-tree / organization-chart

树形 `data: { name, children?, description? }`；mindmap `direction` 默认 H；indented-tree 默认 LR

### fishbone-diagram

`data: { name, children? }`；`style.texture?: rough|default`

### summary

**禁止**以 `vis summary` 开头。直接写 Markdown + T8 语义标注。完整规则先读 `references/summary.md`。

标注形如 `[显示文本](实体类型)` 或 `[显示文本](实体类型, key=value)`。常用实体：`metric_name`、`metric_value`、`delta_value`、`ratio_value`、`dim_value`、`time_desc`、`trend_desc` 等。

## 最佳实践

1. 饼图分类不超过 5 个，超出合并为「其它」或改用 bar
2. 不用饼图展示趋势，不用折线展示无序分类
3. 数值必须是数字类型，分类必须是文本
4. 连续数值分布用 histogram
5. 两个分类维度时：x 用 `time`/`category`，另一维用 `group`
6. 会话默认 Markdown Syntax；只有用户要可运行页面时才给 CDN 代码
7. 可先查数再画图：图表 skill 不收窄 MCP 工具白名单，可与查询类 skill 并存
