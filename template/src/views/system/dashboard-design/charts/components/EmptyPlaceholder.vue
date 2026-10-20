<template>
  <div class="no-widget-hint" :class="{ fixed }">
    <img src="@/assets/images/no-widget-hint.png" alt="" />
    <div class="trip">暂无图表,选择左侧图表组件，鼠标点击即可加入</div>
  </div>
</template>

<script>
export default {
  name: "EmptyPlaceholder",
  props: {
    fixed: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      msg: ''
    }
  }
}
</script>

<style scoped lang="scss">
.no-widget-hint {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: calc(100vh - 64px);
  pointer-events: none;

  &.fixed {
    position: absolute;
    inset: 0;
    background-color: #fff;
    z-index: 1000;
  }

  img {
    width: 129px;
  }

  // 提示
  .trip {
    font-weight: 400;
    font-size: 13px;
    color: #9e9e9e;
    margin-top: 13px;
  }
}
</style>
