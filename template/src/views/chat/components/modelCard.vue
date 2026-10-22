<template>
  <div class="model-card">
    <div class="operate">
      <span @click="$emit('setting', data)">设置</span>
      <el-divider direction="vertical"></el-divider>
      <span @click="$emit('delete', data)">删除</span>
    </div>
    <div class="card-title">
      <el-image :src="data.logo" class="img" fit="contain" />
      <!-- <img :src="logo" alt="" class="img" /> -->
      <div class="over-text name">{{ data.name }}</div>
    </div>
    <div class="flex mt14">
      <div class="label">服务商</div>
      <div class="value">{{ providerLabel }}</div>
    </div>
    <div class="flex">
      <div class="label">模型ID</div>
      <div class="value model-id over-text">{{ data.model_id || '--' }}</div>
    </div>
    <div class="flex">
      <div class="label">模型类别</div>
      <div class="value">{{ modelTypeLabel }}</div>
    </div>
    <div class="flex flex-between">
      <div class="flex">
        <div class="label">创建者</div>
        <div class="value">{{ data.user ? data.user.name : '--' }}</div>
      </div>
      <div class="time">{{ data.created_at }}</div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { getChatImg, DEFAULT_MODEL_IMG } from '../utils/chatImg'
import { getModelTypeLabel } from '../utils/model'

export default {
  name: 'ModelCard',
  props: {
    // 模型数据，形如 { id, name, provider, model_id, model_type, user, created_at }
    data: {
      type: Object,
      required: true
    },
    // 该模型所属的供应商选项 { label, value, pic }，列表接口只返回 provider，由父组件回查后传入
    provider: {
      type: Object,
      default: null
    }
  },
  setup(props) {
    const providerLabel = computed(() => (props.provider && props.provider.label) || props.data.provider || '--')

    const modelTypeLabel = computed(() => getModelTypeLabel(props.data.model_type))

    const logo = computed(() => {
      const pic = props.provider && props.provider.pic
      return (pic && getChatImg(pic)) || DEFAULT_MODEL_IMG
    })

    return { logo, providerLabel, modelTypeLabel }
  }
}
</script>

<style scoped lang="scss">
.model-card {
  position: relative;
  // 撑满 grid 行高，整行卡片才对得齐（行高由 useGridPageSize 下发）
  height: 100%;
  overflow: hidden;
  padding: 14px;
  border: 1px solid #dcdfe6;
  border-radius: 10px;
  font-family: PingFang SC, PingFang SC;
}

.operate {
  position: absolute;
  top: 14px;
  right: 14px;
  display: flex;
  font-weight: 400;
  font-size: 13px;
  color: #1890ff;
  cursor: pointer;
}

.card-title {
  display: flex;
  align-items: center;
  font-weight: 500;
  font-size: 14px;
  color: #303133;

  .img {
    display: block;
    width: 24px;
    height: 24px;
    margin-right: 8px;
    border-radius: 4px;
    // 一号通等横版 logo 等比装入方框，避免被拉伸/裁切
    background: #f5f7fa;
  }

  .name {
    width: 70%;
  }
}

.label {
  width: 56px;
  text-align: right;
  font-weight: 400;
  font-size: 14px;
  color: #909399;
}

.value {
  margin-left: 12px;
  margin-bottom: 8px;
  font-weight: 400;
  font-size: 14px;
  color: #303133;
}

.model-id {
  // 模型 ID 常见超长串，占满剩余宽度后省略，避免撑破卡片
  flex: 1;
  min-width: 0;
}

.time {
  font-weight: 400;
  font-size: 14px;
  color: #909399;
}
</style>
