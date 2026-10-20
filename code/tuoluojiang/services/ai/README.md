# AiService - AI 服务统一封装类

基于 `phpais/ai-plugin` 扩展包，提供统一的 AI 接口调用能力。

## 功能特性

- ✅ 支持 10+ 主流 AI 模型（文心、ChatGPT、DeepSeek、Gemini 等）
- ✅ 统一的调用接口，无缝切换不同 AI 服务
- ✅ 支持普通聊天和流式聊天
- ✅ 多轮对话历史管理
- ✅ 单例模式，提高性能
- ✅ 灵活的配置管理
- ✅ 完善的错误处理

## 支持的 AI 模型

| AI 类型 | 常量 | 说明 |
|---------|------|------|
| 百度文心一言 | `TYPE_WENXIN` | 支持 3.5/4.0 系列 |
| OpenAI ChatGPT | `TYPE_CHATGPT` | 支持 GPT-3.5/4 系列 |
| DeepSeek | `TYPE_DEEPSEEK` | 支持 V2/V3 系列 |
| Google Gemini | `TYPE_GEMINI` | 支持 Pro/Vision 系列 |
| 阿里云火山引擎 | `TYPE_VOLCANO` | 支持通义千问系列 |
| 腾讯混元 | `TYPE_HUNYUAN` | 支持 Pro/Standard |
| 字节跳动 Kimi | `TYPE_KIMI` | 支持 8K/32K/128K |
| 智谱 AI | `TYPE_ZHIPU` | 支持 GLM-3/4 系列 |
| 阿里通义千问 | `TYPE_QIANWEN` | 支持 Turbo/Plus/Max |
| MinMax | `TYPE_MINMAX` | 支持 ABAB 系列 |

## 快速开始

### 1. 基础用法 - 快速聊天

```php
use Tuoluojiang\Open\services\ai\AiService;

$config = [
    'api_key' => 'your_api_key_here',
    'model' => 'ernie-4.0-8k-latest',
];

$result = AiService::quickChat(
    '你好，请介绍一下你自己',
    [],
    AiService::TYPE_WENXIN,
    $config
);

echo $result['text']; // 输出 AI 回复
```

### 2. 流式聊天（实时响应）

```php
AiService::streamChat(
    '请写一首关于春天的诗',
    function($chunk) {
        echo $chunk; // 实时输出每个数据块
    },
    ['temperature' => 0.8],
    AiService::TYPE_DEEPSEEK,
    $config
);
```

### 3. 多轮对话

```php
$service = AiService::getInstance(AiService::TYPE_CHATGPT, $config);

// 第一轮
$service->addSystemMessage('你是一个 PHP 专家')
        ->addUserMessage('Laravel 如何使用依赖注入？');
$result1 = $service->chatWithHistory();

// 第二轮（基于历史）
$service->addAssistantMessage($result1['text'])
        ->addUserMessage('能给我一个代码示例吗？');
$result2 = $service->chatWithHistory();
```

### 4. 带系统消息的快速聊天

```php
$result = AiService::quickChatWithSystem(
    '帮我写一个登录函数',
    '你是一个 Laravel 开发专家',
    ['max_tokens' => 1000],
    AiService::TYPE_ZHIPU,
    $config
);
```

## 核心方法说明

### 静态方法（快速调用）

#### quickChat()
快速聊天，适合单次对话

```php
AiService::quickChat(
    string $prompt,           // 提示词
    array $options = [],      // 可选参数
    string $type = 'wenxin',  // AI 类型
    array $config = []        // 配置信息
): array
```

#### streamChat()
流式聊天，适合需要实时响应的场景

```php
AiService::streamChat(
    string $prompt,
    callable $callback,       // 回调函数处理数据块
    array $options = [],
    string $type = 'wenxin',
    array $config = []
): void
```

#### quickChatWithSystem()
带系统消息的快速聊天

```php
AiService::quickChatWithSystem(
    string $message,          // 用户消息
    string $systemMessage,    // 系统消息（角色设定）
    array $options = [],
    string $type = 'wenxin',
    array $config = []
): array
```

#### quickStreamChatWithSystem()
带系统消息的流式聊天

```php
AiService::quickStreamChatWithSystem(
    string $message,
    callable $callback,
    string $systemMessage = '',
    array $options = [],
    string $type = 'wenxin',
    array $config = []
): void
```

### 实例方法（多轮对话）

#### 获取实例

```php
$service = AiService::getInstance(string $type, array $config);
```

#### 消息管理

```php
// 添加消息
$service->addUserMessage('用户消息');
$service->addAssistantMessage('助手消息');
$service->addSystemMessage('系统消息');

// 批量设置消息
$service->setMessages([
    ['role' => 'system', 'content' => '你是助手'],
    ['role' => 'user', 'content' => '问题1'],
    ['role' => 'assistant', 'content' => '回答1'],
]);

// 获取消息
$messages = $service->getMessages();
$lastMsg = $service->getLastMessage('user');
$count = $service->getMessageCount('user');

// 清空消息
$service->clearMessages();
```

#### 发送聊天

```php
// 普通聊天
$result = $service->chatWithHistory($options);

// 流式聊天
$service->streamChatWithHistory($callback, $options);
```

#### 模型切换

```php
$service->setModel('deepseek-v3');
```

### 工具方法

#### 获取模型信息

```php
// 获取所有 AI 类型
$types = AiService::getAllTypes();

// 获取常用模型
$models = AiService::getCommonModels(AiService::TYPE_DEEPSEEK);

// 获取模型详情
$detail = AiService::getModelDetail(AiService::TYPE_DEEPSEEK, 'deepseek-v3');
```

#### 配置管理

```php
// 验证配置
$validation = AiService::validateConfig($config);

// 从环境变量加载
$config = AiService::loadConfigFromEnv(AiService::TYPE_DEEPSEEK);

// 测试连接
$result = AiService::testConnection(AiService::TYPE_DEEPSEEK, $config);
```

#### 其他工具

```php
// 检查是否支持
$isSupported = AiService::isSupported('deepseek');

// 获取类型名称
$name = AiService::getTypeName(AiService::TYPE_DEEPSEEK);

// 清理实例缓存
AiService::clearInstances();
```

## 配置说明

### 配置参数

```php
$config = [
    'api_key' => 'your_api_key',      // API 密钥（必填）
    'model' => 'model_name',          // 模型名称（可选，默认使用第一个常用模型）
    'base_url' => 'https://...',      // API 基础 URL（可选）
    'timeout' => 30,                  // 超时时间（秒）
];
```

### 从环境变量加载

在 `.env` 文件中配置：

```env
# DeepSeek 配置
DEEPSEEK_API_KEY=sk-xxx
DEEPSEEK_MODEL=deepseek-v3
DEEPSEEK_BASE_URL=https://api.deepseek.com
DEEPSEEK_TIMEOUT=30

# 文心一言配置
WENXIN_API_KEY=your_key
WENXIN_MODEL=ernie-4.0-8k-latest
```

代码中使用：

```php
$config = AiService::loadConfigFromEnv(AiService::TYPE_DEEPSEEK);
$result = AiService::quickChat('你好', [], AiService::TYPE_DEEPSEEK, $config);
```

## 高级用法

### 1. 对比不同 AI 的回答

```php
$question = '什么是 Laravel 的服务容器？';
$aiTypes = [
    AiService::TYPE_WENXIN,
    AiService::TYPE_DEEPSEEK,
    AiService::TYPE_CHATGPT,
];

foreach ($aiTypes as $type) {
    $config = AiService::loadConfigFromEnv($type);
    $typeName = AiService::getTypeName($type);
    
    echo "==== {$typeName} ====\n";
    $result = AiService::quickChat($question, [], $type, $config);
    echo $result['text'] . "\n\n";
}
```

### 2. 构建复杂的多轮对话

```php
$service = AiService::getInstance(AiService::TYPE_CHATGPT, $config);

$service->addSystemMessage('你是一个代码审查专家');

// 第一轮
$service->addUserMessage('这段代码有什么问题？\n' . $code);
$result1 = $service->chatWithHistory();
$service->addAssistantMessage($result1['text']);

// 第二轮
$service->addUserMessage('如何优化？');
$result2 = $service->chatWithHistory();
$service->addAssistantMessage($result2['text']);

// 第三轮
$service->addUserMessage('给我改进后的代码');
$result3 = $service->chatWithHistory();
```

### 3. 流式响应与 Laravel StreamedResponse 结合

```php
use Illuminate\Support\Facades\Response;

public function stream()
{
    return Response::stream(function () {
        $config = AiService::loadConfigFromEnv(AiService::TYPE_DEEPSEEK);
        
        AiService::streamChat(
            '讲一个故事',
            function($chunk) {
                echo "data: {$chunk}\n\n";
                ob_flush();
                flush();
            },
            [],
            AiService::TYPE_DEEPSEEK,
            $config
        );
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

## 常见问题

### Q: 如何切换不同的模型？

A: 使用 `setModel()` 方法：

```php
$service = AiService::getInstance(AiService::TYPE_DEEPSEEK, $config);
$service->setModel('deepseek-v2');
```

### Q: 如何处理 API 错误？

A: 使用 try-catch 捕获异常：

```php
try {
    $result = AiService::quickChat($prompt, [], $type, $config);
} catch (\Exception $e) {
    Log::error('AI 调用失败: ' . $e->getMessage());
}
```

### Q: 消息历史会占用太多内存吗？

A: 可以定期清理：

```php
// 保留最近 10 条消息
$messages = $service->getMessages();
if (count($messages) > 10) {
    $service->setMessages(array_slice($messages, -10));
}
```

### Q: 如何测试配置是否正确？

A: 使用 `testConnection()` 方法：

```php
$result = AiService::testConnection($type, $config);
if (!$result['success']) {
    echo "配置错误: " . $result['message'];
}
```

## 完整示例

查看 `AiServiceUsageExample.php` 获取 15+ 个完整的使用示例。

## 注意事项

1. **API 密钥安全**：不要将 API 密钥硬编码到代码中，使用环境变量
2. **错误处理**：始终使用 try-catch 处理可能的异常
3. **超时设置**：根据网络情况调整 timeout 参数
4. **内存管理**：长时间运行时注意清理实例缓存和消息历史
5. **协程安全**：在 Swoole 环境下避免使用全局变量

## 更新日志

### v1.1.0 (2025-02-02)

新增功能：
- ✅ 流式聊天支持（带消息历史）
- ✅ 快速流式聊天（带系统消息）
- ✅ 批量设置消息历史
- ✅ 获取最后一条消息
- ✅ 获取消息数量统计
- ✅ 获取 AI 类型名称
- ✅ 获取所有 AI 类型列表
- ✅ 获取模型详细信息
- ✅ 配置验证
- ✅ 连接测试
- ✅ 从环境变量加载配置
- ✅ 检查 AI 类型支持

### v1.0.0

- 初始版本

## 许可

陀螺匠企业版权所有 © 2016-2025
