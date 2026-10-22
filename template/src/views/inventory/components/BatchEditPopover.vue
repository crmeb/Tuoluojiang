<template>
  <!-- 挂在表格表头里，弹层 append-to-body（el-popover 默认）避免被表头 .cell 的 overflow 裁剪 -->
  <el-popover v-model="visible" placement="bottom" trigger="click" :width="width"
    popper-class="batch-edit-popover" @show="handleShow">
    <div class="batch-edit-popover__body">
      <p class="batch-edit-popover__title">{{ title }}</p>
      <el-input-number ref="inputRef" v-model="inputValue" :min="min" :max="max" :precision="0" :controls="false"
        :placeholder="placeholder" size="small" class="batch-edit-popover__input" @keyup.enter.native="confirm" />
    </div>
    <div class="batch-edit-popover__footer">
      <el-button size="mini" @click="close">取消</el-button>
      <el-button size="mini" type="primary" @click="confirm">确定</el-button>
    </div>

    <template #reference>
      <i class="el-icon-edit batch-edit-popover__trigger" :title="title" @click.stop />
    </template>
  </el-popover>
</template>

<script>
import { ref, getCurrentInstance, nextTick } from "vue";

export default {
  name: "BatchEditPopover",
  props: {
    // 气泡内标题，同时作为触发图标的 title 提示
    title: {
      type: String,
      default: "批量修改"
    },
    placeholder: {
      type: String,
      default: "请输入数量"
    },
    width: {
      type: [String, Number],
      default: 220
    },
    min: {
      type: Number,
      default: 0
    },
    max: {
      type: Number,
      default: Infinity
    }
  },
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance();
    const visible = ref(false);
    const inputRef = ref();
    const inputValue = ref(undefined);

    // 每次打开都从空值开始，避免沿用上次输入造成误批量覆盖
    const handleShow = () => {
      inputValue.value = undefined;
      nextTick(() => inputRef.value && inputRef.value.focus());
    };

    // 关闭前先移出焦点：气泡离场动画期间会挂 aria-hidden，焦点留在按钮上会触发无障碍告警
    const close = () => {
      if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
      visible.value = false;
    };

    const confirm = () => {
      if (inputValue.value === undefined || inputValue.value === null || inputValue.value === "") {
        proxy.$message.warning(props.placeholder);
        return;
      }
      emit("confirm", Number(inputValue.value));
      close();
    };

    return {
      visible,
      inputRef,
      inputValue,
      handleShow,
      close,
      confirm
    };
  }
};
</script>

<style lang="scss" scoped>
.batch-edit-popover {
  &__trigger {
    margin-left: 4px;
    color: #1890ff;
    cursor: pointer;

    &:hover {
      opacity: 0.8;
    }
  }
}
</style>

<style lang="scss">
// 弹层 append-to-body，样式需放在非 scoped 块内
.batch-edit-popover {
  .batch-edit-popover__title {
    margin: 0 0 8px;
    font-size: 13px;
    line-height: 18px;
    color: #303133;
  }

  .batch-edit-popover__input {
    width: 100%;

    // 去除计数器后还原为普通输入框观感
    .el-input__inner {
      text-align: left;
    }
  }

  .batch-edit-popover__footer {
    margin-top: 12px;
    text-align: right;
  }
}
</style>
