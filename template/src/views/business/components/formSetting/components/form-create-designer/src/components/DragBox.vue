<script>
import { defineComponent, h } from 'vue';
import draggable from 'vuedraggable';

// vuedraggable 4 要求通过 list/modelValue + #item 插槽渲染子项：这里把 form-create 渲染好的子节点
// 作为列表传入，item 插槽原样输出。拖拽后的规则变更由设计器的 add/end 回调处理，不依赖 update:modelValue。
export default defineComponent({
  name: 'DragBox',
  props: ['rule', 'subRule', 'tag'],
  render() {
    const rule = this.rule || {};
    const children = (this.$slots.default && this.$slots.default()) || [];
    let _class = this.tag + '-drag drag-box';
    if (!children.length) {
      _class += ' ' + this.tag + '-holder';
    }
    return h(
      draggable,
      {
        ...(rule.props || {}),
        ...(rule.attrs || {}),
        class: _class,
        modelValue: children,
        itemKey: (vnode) => (vnode.key != null ? vnode.key : children.indexOf(vnode)),
      },
      { item: ({ element }) => element }
    );
  },
});
</script>
