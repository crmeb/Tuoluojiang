# 开源框架核心包（`Tuoluojiang\Open`）

本目录对应 Composer PSR-4：`Tuoluojiang\Open\` → `tuoluojiang/`。

与商业版 `crmeb/`（含 `crmeb/basic` 加密基类）为**不同命名空间与目录结构**，不可互相整包替换。

## 目录结构

```
tuoluojiang/
├── exceptions/
├── interfaces/
├── options/
├── services/     # 含 BaseManager、BaseStorage、upload、serve 等
├── socket/
├── swoole/
├── traits/       # 含 VersionHeaderTrait 等
└── utils/
```

## 原 `basic/` 已拆分到

| 原类 | 现位置 |
|------|--------|
| BaseController | `App\Http\Controller\BaseController` |
| BaseAuth | `App\Support\OpenAuth` |
| BaseRequest | `App\Http\Requests\BaseRequest` |
| BaseDao / BaseModel / BaseService | `App\Http\{Dao,Model,Service}` |
| BaseManager | `Tuoluojiang\Open\services\BaseManager` |
| BaseStorage | `Tuoluojiang\Open\services\BaseStorage` |
| BaseToken | `Tuoluojiang\Open\traits\VersionHeaderTrait` |

未再使用的 `BaseEntService` / `BaseMessage` / `BaseSmss` / 重复 `BaseUpload` 已删除。
