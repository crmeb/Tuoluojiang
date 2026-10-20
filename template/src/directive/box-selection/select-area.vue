<template>
  <div class="select-area" :class="{ active: !!style }" :style="style"></div>
</template>

<script>
export default {
  name: 'SelectArea',
  data() {
    return {
      style: ""
    };
  },
  methods: {
    setStyle(style) {
      this.style = style;
    }
  }
};
</script>

<style scoped lang="scss">
.select-area {
  &.active {
    border: 1px solid rgba(0, 175, 255, 0.4);
  }

  background-color: rgba(0, 175, 255, 0.2);
  position: absolute;
  z-index: 10;
  pointer-events: none;
}
</style>
