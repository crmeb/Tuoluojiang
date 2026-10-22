// 转换类型枚举值（对齐「新增正次转换」接口 convert_type）
export const CONVERT_TYPE = {
  GOOD_TO_DEFECT: 1, // 良品转残次
  DEFECT_TO_GOOD: 2 // 残次转良品
};

// 转换类型完整配置：label / 剩余库存列名（随类型切换展示良品或残次剩余库存）/ 标签主题（StatusTag 面性标签）
export const CONVERT_TYPE_OPTIONS = [
  {
    value: CONVERT_TYPE.GOOD_TO_DEFECT,
    label: "良品转残次",
    stockLabel: "良品剩余库存",
    theme: "primary",
    variant: "filled"
  },
  {
    value: CONVERT_TYPE.DEFECT_TO_GOOD,
    label: "残次转良品",
    stockLabel: "残次剩余库存",
    theme: "success",
    variant: "filled"
  }
];

// 派生：value -> 配置，渲染标签 / 列名时 O(1) 查询
export const CONVERT_TYPE_MAP = CONVERT_TYPE_OPTIONS.reduce((map, item) => {
  map[item.value] = item;
  return map;
}, {});

// 转换类型说明（固定文案，展示在类型选择下方）
export const CONVERT_TYPE_DESC =
  "良品转残次，该商品良品剩余库存对应减少，残次品剩余库存对应增加；反之残次转良品，则良品增加，残次品减少";

// 单个转换商品的凭证图片上限（对齐接口 details[].images 的 maxItems）
export const CONVERT_ATTACH_MAX = 10;
