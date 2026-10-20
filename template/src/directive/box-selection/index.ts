import type { Directive } from 'vue';
import SelectArea from './select-area.vue';
import { mountComponent } from '@/app-context';

const dataKey = Symbol('box-selection');

interface Rect {
  top: number;
  left: number;
  width: number;
  height: number;
}

/** 指令绑定值 */
export interface BoxSelectionOptions {
  /** 用于识别被框选子元素的选择器 */
  itemSelector: string;
  /** 子元素上标识 id 的属性名 */
  itemIdField: string;
  /** 选中子元素变化时回调，参数为选中的 id 列表 */
  itemChangeCallback: (ids: number[]) => void;
}

type BoxSelectionElement = HTMLElement & {
  [dataKey]?: { clearupDirective: () => void };
};

class BoxSelection {
  startPointInfo = {
    x: 0,
    y: 0
  };
  isMouseDown = false;
  isMouseMove = false;
  scrollInfo = {
    isScroll: false,
    originPageY: 0,
    beforeScrollTop: 0
  };

  currentPointInfo = {
    x: 0,
    y: 0
  };

  randomId: string;
  setStyle: (style: string) => void;
  el: HTMLElement;
  itemSelector: string;
  itemIdField: string;
  itemChangeCallback: (ids: number[]) => void;

  constructor(
    el: HTMLElement,
    setStyle: (style: string) => void,
    itemSelector: string,
    itemIdField: string,
    itemChangeCallback: (ids: number[]) => void
  ) {
    this.randomId = Math.random().toString(36).substring(2, 15);
    this.setStyle = setStyle;
    this.el = el;
    this.el.setAttribute('data-select-box-id', this.randomId);
    this.itemSelector = itemSelector;
    this.itemIdField = itemIdField;
    this.itemChangeCallback = itemChangeCallback;
    this.handleMouseDown = this.handleMouseDown.bind(this);
    this.handleMouseUp = this.handleMouseUp.bind(this);
    this.handleMouseMove = this.handleMouseMove.bind(this);
    this.handleScroll = this.handleScroll.bind(this);
    this.checkIntersection = this.checkIntersection.bind(this);
    this.init();
  }

  handleMouseDown(e: MouseEvent) {
    if (!(e.target as Element).closest(`[data-select-box-id="${this.randomId}"]`)) return;
    this.startPointInfo.x = e.pageX;
    this.startPointInfo.y = e.pageY;
    this.isMouseDown = true;
    document.body.style.userSelect = "none";
  }

  handleMouseUp(_e?: MouseEvent) {
    if (!this.isMouseDown) return;
    this.isMouseDown = false;
    this.isMouseMove = false;
    this.updateSelectArea();
    document.body.style.userSelect = "auto";
  }

  handleMouseMove(e: MouseEvent) {
    if (!this.isMouseDown) return;
    this.isMouseMove = true;
    if (this.scrollInfo.isScroll) {
      this.scrollInfo.isScroll = false;
    }
    this.currentPointInfo.x = e.pageX;
    this.currentPointInfo.y = e.pageY;
    this.updateSelectArea(true);
  }

  handleScroll(_e?: Event) {
    if (!this.isMouseDown) return;
    if (!this.scrollInfo.isScroll) {
      this.scrollInfo.isScroll = true;
      this.scrollInfo.originPageY = this.currentPointInfo.y;
      this.scrollInfo.beforeScrollTop = document.documentElement.scrollTop;
    }

    const scrollTop = document.documentElement.scrollTop;
    this.currentPointInfo.y = scrollTop - this.scrollInfo.beforeScrollTop + (this.scrollInfo.originPageY || 0);
    this.updateSelectArea();
  }

  calcSelectAreaStyle(): Rect | Record<string, never> {
    if (!this.isMouseDown || !this.isMouseMove) return {};
    let { x: startX, y: startY } = this.startPointInfo;
    let { x: endX, y: endY } = this.currentPointInfo;

    if (startX > endX) {
      [startX, endX] = [endX, startX];
    }

    if (startY > endY) {
      [startY, endY] = [endY, startY];
    }

    const width = Math.abs(endX - startX);
    const height = Math.abs(endY - startY);

    const MIN_SIZE = 3;

    if (width < MIN_SIZE || height < MIN_SIZE) return {};

    const style = {
      top: startY,
      left: startX,
      width,
      height
    };

    return style;
  }

  styleAddUnit(styles: Partial<Rect>) {
    return Object.entries(styles).map(([key, value]) => {
      return `${key}: ${value}px`;
    }).join(";");
  }

  checkIntersection(rect1: Rect, rect2: Partial<Rect>) {
    const { top: top1, left: left1, width: width1, height: height1 } = rect1;
    const { top: top2, left: left2, width: width2, height: height2 } = rect2 as Rect;

    const maxX = Math.max(left1 + width1, left2 + width2);
    const minX = Math.min(left1, left2);
    const maxY = Math.max(top1 + height1, top2 + height2);
    const minY = Math.min(top1, top2);

    return maxX - minX <= (width1 + width2) && maxY - minY <= (height1 + height2);
  }

  getSelectedItems(selectAreaStyle: Partial<Rect>) {
    const items = this.el.querySelectorAll(this.itemSelector);
    const inAreaItemIdList: number[] = [];
    items.forEach(item => {
      let rect: Rect = item.getBoundingClientRect().toJSON();

      rect.top += window.scrollY;
      rect.left += window.scrollX;
      const result = this.checkIntersection(rect, selectAreaStyle);
      if (result) {
        inAreaItemIdList.push(Number(item.getAttribute(this.itemIdField)));
      }
    });
    return inAreaItemIdList;
  }

  updateSelectArea(updateSelectItems = false) {
    const style = this.calcSelectAreaStyle();
    this.setStyle(this.styleAddUnit(style));

    if (updateSelectItems) {
      const inAreaItemIdList = this.getSelectedItems(style);
      this.itemChangeCallback(inAreaItemIdList);
    }
  }

  init() {
    window.addEventListener("mousedown", this.handleMouseDown);
    window.addEventListener("mouseup", this.handleMouseUp);
    window.addEventListener("mousemove", this.handleMouseMove);
    window.addEventListener("scroll", this.handleScroll);
  }

  clearup() {
    window.removeEventListener("mousedown", this.handleMouseDown);
    window.removeEventListener("mouseup", this.handleMouseUp);
    window.removeEventListener("mousemove", this.handleMouseMove);
    window.removeEventListener("scroll", this.handleScroll);
  }
}

/**
 * 区域选择指令，用于在指定区域中通过鼠标框选范围来选择元素
 * 指令需要传入一个对象，包含三个参数
 * 1、itemSelector：选择器，用于识别被框选的子元素
 * 2、itemIdField：子元素的id字段名称，用于标识子元素
 * 3、itemChangeCallback：选择的子元素列表变化时触发的回调函数，回调函数会传入一个数组，数组中包含被选中的子元素的id
 */

const boxSelectionDirective: Directive<BoxSelectionElement, BoxSelectionOptions> = {
  beforeMount(el, binding) {
    const { itemSelector, itemIdField, itemChangeCallback } = binding.value;
    const selectArea = mountComponent<{ setStyle: (style: string) => void }>(SelectArea);
    const boxSelection = new BoxSelection(el, selectArea.vm.setStyle, itemSelector, itemIdField, itemChangeCallback);
    const clearupDirective = () => {
      selectArea.unmount();
      boxSelection.clearup();
    }

    el[dataKey] = {
      clearupDirective
    };
  },
  unmounted(el) {
    const { clearupDirective } = el[dataKey]!;
    clearupDirective();
  }
}

export default boxSelectionDirective