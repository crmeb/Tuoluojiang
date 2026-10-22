var e={INTERNAL:0,EXTERNAL:1};e.INTERNAL,e.EXTERNAL;var t={DATABASE:1,MCP:2,KNOWLEDGE:4},n={PERSONA_REPLY:`persona_reply`,PROLOGUE:`prologue`,PROLOGUE_QUESTIONS:`prologue_questions`},r={TOOLTIP:3e3,PROLOGUE:200,PROLOGUE_ITEM:50,DATA_ARRANGE:1e3,KEYWORD:20},i=`#角色规范
你是一个 XXXX 小助手，你的任务是 XXXX。

#思考规范

- 在回答问题时，你需要分析用户的问题，确保理解需求和上下文。
- 当用户的需求不明确时，你应该主动优先明确用户需求。
- 对于超出 本角色 小助手服务范围的需求，你需要按如下话术委婉拒答：抱歉，并引导用户提出关于 本角色 相关的问题。

#回复规范

- 你需要以 简洁高效的语气风格 回复用户。
- 在每次结束对话时你可以向用户提问并引导相关话题深入。`,a={NAME:`AI应用展示名称`,INFO:`请输入一段简明扼要又吸睛的介绍，可以快速吸引用户使用你的智能体`,TOOLTIP:`通过提示词，你能精确设定应用的作用范围。包括指定应用将扮演的角色，能够使用的组件以及输出结果的格式与风格；此外你还可以规定应用不得执行哪些操作等`,EDIT:`拥有编辑权限的成员，可以进行AI应用编排`,AUTH:`请选择允许使用该应用的人员`,USE_LIMIT:`用于设置允许用户使用频次，到达限制以后不允许继续访问，0表示不限制`,SORT:`应用顺序调整，数字越大越靠前`,MODEL:`选择模型设置中配置好的AI模型`,ENABLE_THINKING:`关闭后，对支持深度思考的模型（如豆包 Seed / 火山方舟）将不再输出思考过程，响应更快、Token 更省；不支持的模型此开关无效`,PROLOGUE:`将在用户开启对话时展示，引导用户快速了解功能并开启对话`,PROLOGUE_LIST:`至少写3个，超出后随机取3个展示`,DATABASE:`扩展智能体的数据库知识储备，为用户提供更针对性的答案，智能体公开发布后可生成优质问答，用于调优`,DATA_ARRANGE:`您可以对数据库表查询之后，返回内容的整理格式进行描述`,KEYWORD:`用户提问触发关键字，才会根据数据库进行查询`,MCP:`通过 MCP 服务扩展智能体的工具能力，可同时绑定多个服务`,KNOWLEDGE:`绑定知识库后，智能体会在知识库中检索相关内容再作答，可同时绑定多个知识库`,COUNT_NUMBER:`本次回答内容根据最近轮次的参考对话`};export{n as a,t as i,a as n,r as o,i as r,e as t};