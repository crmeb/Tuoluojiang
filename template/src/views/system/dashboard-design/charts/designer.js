import { deepClone, generateId, getDefaultFormConfig, overwriteObj } from '@/utils/formDesignerUtils'
import {
  containers,
  advancedFields,
  basicFields,
  customFields
} from '@/components/form-designer/widget-panel/widgetsConfig.js'

export function createDesigner(vueInstance) {
  let defaultFormConfig = deepClone(getDefaultFormConfig())
  return {
    widgetList: [],
    formConfig: { cssCode: '' },
    selectedId: null,
    isTabSelected: false,
    selectedWidget: null,
    selectedWidgetName: null, //选中组件名称（唯一）
    vueInstance: vueInstance,
    selectNames: [], //已添加组件名称数组
    formWidget: null, //表单设计容器
    cssClassList: [], //自定义样式列表
    tabIsShow: '',
    containersNames: containers.map((con) => con.type),
    chartContainers: [],
    chartWidgets: [],
    historyData: {
      index: -1, //index: 0,
      maxStep: 20,
      steps: []
    },
    widgetRefList: {},
    dropTargetContext: null,
    addChartContainerSchema(o) {
      this.chartContainers.push(o)
    },
    addChartSchema(o) {
      this.chartWidgets.push(o)
    },
    initDesigner(resetFormJson) {
      this.widgetList = []
      this.formConfig = deepClone(defaultFormConfig)
      if (!resetFormJson) {
        this.initHistoryData()
      }
    },
    setFormJson(o) {
      let e = !1
      o &&
        (typeof o == 'string'
          ? (e = this.designer.loadFormJson(JSON.parse(o)))
          : o.constructor === Object && (e = this.designer.loadFormJson(o)),
        e && this.designer.emitHistoryChange())
    },
    clearDesigner(skipHistoryChange) {
      let emptyWidgetListFlag = this.widgetList.length === 0
      this.widgetList = []
      this.selectedId = null
      this.selectNames = []
      this.selectedWidgetName = null
      this.selectedWidget = {} //this.selectedWidget = null
      overwriteObj(this.formConfig, defaultFormConfig) //

      if (!!skipHistoryChange) {
        //什么也不做！！
      } else if (!emptyWidgetListFlag) {
        this.emitHistoryChange()
      } else {
        this.saveCurrentHistoryStep()
      }
    },
    getLayoutNumber(value, defaultValue = 0) {
      const num = Number(value)
      return Number.isFinite(num) ? num : defaultValue
    },
    getLayoutBottom(layout = []) {
      if (!Array.isArray(layout) || layout.length === 0) {
        return 0
      }
      return layout.reduce((maxBottom, item) => {
        const itemBottom = this.getLayoutNumber(item.y) + this.getLayoutNumber(item.h)
        return Math.max(maxBottom, itemBottom)
      }, 0)
    },
    getTabAppendPosition(layout = [], widgetOptions = {}) {
      const maxCols = 12
      const targetW = this.getLayoutNumber(widgetOptions.w, maxCols)
      const defaultPos = {
        x: this.getLayoutNumber(widgetOptions.x, 0),
        y: this.getLayoutNumber(widgetOptions.y, 0)
      }

      if (!Array.isArray(layout) || layout.length === 0) {
        return defaultPos
      }

      const lastRowY = layout.reduce((maxY, item) => {
        return Math.max(maxY, this.getLayoutNumber(item.y))
      }, 0)
      const lastRowItems = layout.filter((item) => this.getLayoutNumber(item.y) === lastRowY)
      const lastRowRight = lastRowItems.reduce((maxRight, item) => {
        const right = this.getLayoutNumber(item.x) + this.getLayoutNumber(item.w)
        return Math.max(maxRight, right)
      }, 0)

      if (lastRowRight + targetW <= maxCols) {
        return { x: lastRowRight, y: lastRowY }
      }

      return { x: 0, y: this.getLayoutBottom(layout) }
    },
    findSelectedTabWidget(container) {
      if (!container || !Array.isArray(container.widgetList)) {
        return null
      }
      return (
        container.widgetList.find((item) => {
          if (item.type !== 'tab') {
            return false
          }
          if (item.id === this.selectedId) {
            return true
          }
          const tabList = item.options && Array.isArray(item.options.tabList) ? item.options.tabList : []
          return tabList.some((tab) => {
            const list = Array.isArray(tab.widgetList) ? tab.widgetList : []
            return list.some((widget) => widget.id === this.selectedId)
          })
        }) || null
      )
    },
    getActiveTabPane(tabWidget) {
      const tabList =
        tabWidget && tabWidget.options && Array.isArray(tabWidget.options.tabList) ? tabWidget.options.tabList : []
      if (tabList.length === 0) {
        return null
      }
      const activeValue = tabWidget.options.activeValue
      return tabList.find((tab) => tab.value === activeValue) || tabList.find((tab) => tab.status === 1) || tabList[0]
    },
    ensureTabPaneData(tabPane) {
      if (!tabPane.options) {
        tabPane.options = {}
      }
      if (!Array.isArray(tabPane.widgetList)) {
        tabPane.widgetList = []
      }
      if (!Array.isArray(tabPane.options.layout)) {
        tabPane.options.layout = []
      }
    },
    appendWidgetToTabPane(tabPane, widget) {
      this.ensureTabPaneData(tabPane)
      const position = this.getTabAppendPosition(tabPane.options.layout, widget.options || {})
      const layoutItem = {
        x: position.x,
        y: position.y,
        w: this.getLayoutNumber(widget.options && widget.options.w, 12),
        h: this.getLayoutNumber(widget.options && widget.options.h, 4),
        i: widget.id
      }
      tabPane.widgetList.push(widget)
      tabPane.options.layout.push(layoutItem)
    },

    // 点击图表容器组件添加到设计器
    addChartContainerByDbClick(n, payload = {}) {
      if (!n || !n.type) {
        return
      }
      if (n.type === 'tab') {
        this.tabIsShow = n.type
      }
      const l = this.findDashboardContainer()
      if (!l) {
        return
      }

      // 克隆并初始化新图表容器
      const s = deepClone(n)
      s.id = 'chartCon' + generateId()
      if (!s.options) {
        s.options = {}
      }
      s.options.name = s.id
      const dropContext = payload && payload.dropContext ? payload.dropContext : null
      const useSelectedTabFallback = !(payload && payload.respectSelectedTab === false)

      let added = false
      const targetTabPane = this.getTabPaneByDropContext(l, dropContext)
      if (targetTabPane && s.type !== 'tab') {
        if (!targetTabPane.widgetList) {
          targetTabPane.widgetList = []
        }
        if (!targetTabPane.options) {
          targetTabPane.options = {}
        }
        if (!targetTabPane.options.layout) {
          targetTabPane.options.layout = []
        }
        const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, targetTabPane.options.layout)
        targetTabPane.widgetList.push(s)
        targetTabPane.options.layout.push(c)
        this.setSelected(s)
        this.emitHistoryChange()
        this.ensureDashboardContainerExists(l)
        added = true
      }

      const selectedTabWidget = this.findSelectedTabWidget(l)
      if (!added && !dropContext && useSelectedTabFallback && selectedTabWidget && s.type !== 'tab') {
        const activeTab = this.getActiveTabPane(selectedTabWidget)
        if (activeTab) {
          // 确保tab有必要的结构
          if (!activeTab.widgetList) {
            activeTab.widgetList = []
          }
          if (!activeTab.options) {
            activeTab.options = {}
          }
          if (!activeTab.options.layout) {
            activeTab.options.layout = []
          }

          const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, activeTab.options.layout)
          activeTab.widgetList.push(s)
          activeTab.options.layout.push(c)
          this.setSelected(s)
          this.emitHistoryChange()
          this.ensureDashboardContainerExists(l)
          added = true
        }
      }

      // 如果没有添加到tab，则添加到dashboard容器
      if (!added) {
        if (!l.options) {
          l.options = {}
        }
        if (!l.options.layout) {
          l.options.layout = []
        }
        if (!l.widgetList) {
          l.widgetList = []
        }

        const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, l.options.layout)
        l.options.layout.push(c)
        l.widgetList.push(s)
        this.setSelected(s)
        this.emitHistoryChange()
        this.ensureDashboardContainerExists(l)
      }
    },
    addChartByDbClick(n, payload = {}) {
      if (!n || !n.type) {
        return
      }
      const l = this.findDashboardContainer()
      if (l) {
        const s = deepClone(n)
        s.id = 'chart' + generateId()
        if (!s.options) {
          s.options = {}
        }
        s.options.name = s.id
        const dropContext = payload && payload.dropContext ? payload.dropContext : null
        const useSelectedTabFallback = !(payload && payload.respectSelectedTab === false)

        let added = false

        const targetTabPane = this.getTabPaneByDropContext(l, dropContext)
        if (targetTabPane) {
          if (!targetTabPane.widgetList) {
            targetTabPane.widgetList = []
          }
          if (!targetTabPane.options) {
            targetTabPane.options = {}
          }
          if (!targetTabPane.options.layout) {
            targetTabPane.options.layout = []
          }

          const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, targetTabPane.options.layout)
          targetTabPane.widgetList.push(s)
          targetTabPane.options.layout.push(c)
          this.setSelected(s)
          this.emitHistoryChange()
          this.ensureDashboardContainerExists(l)
          added = true
        }

        const selectedTabWidget = this.findSelectedTabWidget(l)
        if (!added && !dropContext && useSelectedTabFallback && selectedTabWidget) {
          const activeTab = this.getActiveTabPane(selectedTabWidget)
          if (activeTab) {
            // 确保tab有必要的结构
            if (!activeTab.widgetList) {
              activeTab.widgetList = []
            }
            if (!activeTab.options) {
              activeTab.options = {}
            }
            if (!activeTab.options.layout) {
              activeTab.options.layout = []
            }

            const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, activeTab.options.layout)
            activeTab.widgetList.push(s)
            activeTab.options.layout.push(c)
            this.setSelected(s)
            this.emitHistoryChange()
            this.ensureDashboardContainerExists(l)
            added = true
          }
        }

        // 如果没有添加到tab，则添加到dashboard容器
        if (!added) {
          // 默认添加到 dashboard 容器
          if (!l.options) {
            l.options = {}
          }
          if (!l.options.layout) {
            l.options.layout = []
          }
          if (!l.widgetList) {
            l.widgetList = []
          }

          const c = this.buildGridLayoutItem(s, { x: 0, y: 0, w: 4, h: 4 }, l.options.layout)
          l.options.layout.push(c)
          l.widgetList.push(s)
          this.setSelected(s)
          this.emitHistoryChange()
          this.ensureDashboardContainerExists(l)
        }
      }
    },
    findDashboardContainer() {
      if (this.widgetList.length < 1) {
        let s = generateId()
        const container = {
          type: 'dashboard-container',
          category: 'container',
          icon: 'section',
          internal: true,
          widgetList: [],
          options: {
            name: 'dbCon' + s,
            layout: []
          },
          id: 'dbCon' + s
        }
        return container
      }

      // 从widgetList中查找dashboard-container类型的容器
      for (let i = 0; i < this.widgetList.length; i++) {
        if (this.widgetList[i].type === 'dashboard-container') {
          return this.widgetList[i]
        }
      }

      // 如果找不到，返回默认的dashboard容器
      let s = generateId()
      const container = {
        type: 'dashboard-container',
        category: 'container',
        icon: 'section',
        internal: true,
        widgetList: [],
        options: {
          name: 'dbCon' + s,
          layout: []
        },
        id: 'dbCon' + s
      }
      return container
    },
    loadPresetCssCode(preCssCode) {
      if (this.formConfig.cssCode === '' && !!preCssCode) {
        this.formConfig.cssCode = preCssCode
      }
    },
    clearWidgetRefList() {
      Object.keys(this.widgetRefList).forEach((n) => {
        delete this.widgetRefList[n]
      })
    },
    getLayoutType() {
      return this.formConfig.layoutType || 'PC'
    },

    changeLayoutType(newType) {
      this.formConfig.layoutType = newType
    },

    getImportTemplate() {
      return {
        widgetList: [],
        // formConfig: deepClone(this.formConfig)
        formConfig: deepClone(defaultFormConfig)
      }
    },

    loadFormJson(formJson) {
      let modifiedFlag = false

      if (!!formJson && !!formJson.widgetList) {
        this.clearWidgetRefList()
        formJson.widgetList.forEach((item) => {
          item.widgetList.forEach((el) => {})
        })
        this.widgetList = formJson.widgetList
        modifiedFlag = true
      }
      if (!!formJson && !!formJson.formConfig) {
        //this.formConfig = importObj.formConfig
        overwriteObj(
          this.formConfig,
          formJson.formConfig
        ) /* 用=赋值，会导致inject依赖注入的formConfig属性变成非响应式 */
        modifiedFlag = true
      }

      if (modifiedFlag) {
        this.emitEvent('form-json-imported', []) // 通知其他组件
      }

      return modifiedFlag
    },
    setNamesList(selected) {
      if (!selected) {
        return
      }
      if (!!selected.id && !this.containersNames.includes(selected.type)) {
        this.selectNames.push(selected.options.name)
      }
    },
    setSelected(selected) {
      if (!selected) {
        this.clearSelected()
        return
      }
      this.selectedWidget = selected
      this.isTabSelected = false

      // 判断当前高亮的组件是否在tab标签里面
      const top = this.widgetList[0]
      if (!!top && !!top.widgetList) {
        top.widgetList.forEach((item) => {
          // 直接选中 tab 本身

          if (item.id === selected.id && item.type === 'tab') {
            this.isTabSelected = true
            return
          }

          if (item.type === 'tab' && item.options?.tabList) {
            // 选中 tab 内部组件
            item.options.tabList.forEach((tab) => {
              if (tab.widgetList?.some((w) => w.id === selected.id)) {
                this.isTabSelected = true
              }
            })
          }
        })
      }

      if (!!selected.id) {
        this.selectedId = selected.id

        this.selectedWidgetName = selected.options.name
      }
    },

    updateSelectedWidgetNameAndLabel(selectedWidget, newName, newLabel) {
      this.selectedWidgetName = newName
      //selectedWidget.options.name = newName  //此行多余
      if (!!newLabel && Object.keys(selectedWidget.options).indexOf('label') > -1) {
        selectedWidget.options.label = newLabel
      }
    },

    clearSelected() {
      this.selectedId = null
      this.selectedWidgetName = null
      this.tabIsShow = ''
      this.isTabSelected = false
      this.selectedWidget = null //this.selectedWidget = null
    },
    setDropTargetContext(context) {
      this.dropTargetContext = context || null
    },
    consumeDropTargetContext() {
      const context = this.dropTargetContext
      this.dropTargetContext = null
      return context
    },
    getTabPaneByDropContext(dashboardContainer, dropContext) {
      if (
        !dropContext ||
        dropContext.type !== 'tab-pane' ||
        !dashboardContainer ||
        !Array.isArray(dashboardContainer.widgetList)
      ) {
        return null
      }

      const tabWidget = dashboardContainer.widgetList.find(
        (widget) => widget && widget.type === 'tab' && widget.id === dropContext.tabId
      )
      if (!tabWidget || !tabWidget.options || !Array.isArray(tabWidget.options.tabList)) {
        return null
      }

      const normalizeVal = (val) => (val === undefined || val === null ? '' : String(val))
      const dropPaneValue = normalizeVal(dropContext.paneValue || tabWidget.options.activeValue)
      const pane = tabWidget.options.tabList.find((tab) => tab && normalizeVal(tab.value) === dropPaneValue)
      return pane || null
    },
    findSelectedTabWidget(dashboardContainer) {
      if (!dashboardContainer || !dashboardContainer.widgetList || !this.selectedId) {
        return null
      }

      for (let i = 0; i < dashboardContainer.widgetList.length; i++) {
        const widget = dashboardContainer.widgetList[i]
        if (widget.type !== 'tab' || !widget.options || !widget.options.tabList) {
          continue
        }

        if (widget.id === this.selectedId) {
          return widget
        }

        const selectedInside = widget.options.tabList.some(
          (tab) => !!tab.widgetList && tab.widgetList.some((child) => child.id === this.selectedId)
        )
        if (selectedInside) {
          return widget
        }
      }

      return null
    },
    getActiveTabPane(tabWidget) {
      if (!tabWidget || !tabWidget.options || !tabWidget.options.tabList || tabWidget.options.tabList.length === 0) {
        return null
      }

      const tabList = tabWidget.options.tabList
      const activeTab = tabList.find((tab) => tab.value === tabWidget.options.activeValue)
      if (activeTab) {
        return activeTab
      }

      const firstEnabledTab = tabList.find((tab) => tab.status === 1)
      if (firstEnabledTab) {
        return firstEnabledTab
      }

      return tabList[0]
    },
    ensureDashboardContainerExists(dashboardContainer) {
      if (!dashboardContainer) {
        return
      }

      const existed = this.widgetList.some((widget) => widget.id === dashboardContainer.id)
      if (!existed) {
        this.widgetList.push(dashboardContainer)
      }
    },
    buildGridLayoutItem(widget, fallback = {}, targetLayout = null) {
      const options = (widget && widget.options) || {}
      const normalizeNum = (val, defaultVal) => {
        const num = Number(val)
        return Number.isFinite(num) ? num : defaultVal
      }

      const gridCols = 12
      const w = Math.min(Math.max(normalizeNum(options.w, normalizeNum(fallback.w, 4)), 1), gridCols)
      const h = Math.max(normalizeNum(options.h, normalizeNum(fallback.h, 4)), 0.5)
      const autoPos = this.findAvailableGridPosition(targetLayout, w, h, gridCols)

      return {
        x: autoPos.x,
        y: autoPos.y,
        w,
        h,
        i: (widget && widget.id) || 'chart-' + generateId()
      }
    },
    findAvailableGridPosition(layoutList, w, h, gridCols = 12) {
      const normalizeNum = (val, defaultVal) => {
        const num = Number(val)
        return Number.isFinite(num) ? num : defaultVal
      }

      if (!Array.isArray(layoutList) || layoutList.length === 0) {
        return {
          x: 0,
          y: 0
        }
      }

      const normalizedLayout = layoutList.map((item) => ({
        x: normalizeNum(item.x, 0),
        y: normalizeNum(item.y, 0),
        w: Math.max(normalizeNum(item.w, 1), 1),
        h: Math.max(normalizeNum(item.h, 1), 0.5)
      }))

      const maxBottom = normalizedLayout.reduce((max, item) => Math.max(max, item.y + item.h), 0)
      const yStep = 0.5

      for (let y = 0; y <= maxBottom + 50; y += yStep) {
        for (let x = 0; x <= gridCols - w; x++) {
          const hasOverlap = normalizedLayout.some((item) => {
            const overlapX = x < item.x + item.w && x + w > item.x
            const overlapY = y < item.y + item.h && y + h > item.y
            return overlapX && overlapY
          })

          if (!hasOverlap) {
            return {
              x,
              y: Number(y.toFixed(1))
            }
          }
        }
      }

      return {
        x: 0,
        y: Number((maxBottom + yStep).toFixed(1))
      }
    },

    checkWidgetMove(evt) {
      /* Only field widget can be dragged into sub-form */
      if (!!evt.draggedContext && !!evt.draggedContext.element) {
        let wgCategory = evt.draggedContext.element.category
        let wgType = evt.draggedContext.element.type
        if (!!evt.to) {
          if (evt.to.className === 'sub-form-table' && wgCategory === 'container') {
            //this.$message.info(this.vueInstance.i18nt('designer.hint.onlyFieldWidgetAcceptable'))
            return false
          }
        }
      }

      return true
    },

    checkFieldMove(evt) {
      if (!!evt.draggedContext && !!evt.draggedContext.element) {
        let wgCategory = evt.draggedContext.element.category
        let wgType = evt.draggedContext.element.type + ''
        if (!!evt.to) {
          if (evt.to.className === 'sub-form-table' && wgType === 'slot') {
            //this.$message.info(this.vueInstance.i18nt('designer.hint.onlyFieldWidgetAcceptable'))
            return false
          }
        }
      }

      return true
    },

    /**
     * 追加表格新行
     * @param widget
     */
    appendTableRow(widget) {
      let rowIdx = widget.rows.length //确定插入行位置
      let newRow = deepClone(widget.rows[widget.rows.length - 1])
      newRow.id = 'table-row-' + generateId()
      newRow.merged = false
      newRow.cols.forEach((col) => {
        col.id = 'table-cell-' + generateId()
        // col.options.name = col.id
        col.merged = false
        col.options.colspan = 1
        col.options.rowspan = 1
        col.widgetList.length = 0
      })
      widget.rows.splice(rowIdx, 0, newRow)

      this.emitHistoryChange()
    },

    /**
     * 追加表格新列
     * @param widget
     */
    appendTableCol(widget) {
      let colIdx = widget.rows[0].cols.length //确定插入列位置
      widget.rows.forEach((row) => {
        let newCol = deepClone(this.getContainerByType('table-cell'))
        newCol.id = 'table-cell-' + generateId()
        // newCol.options.name = newCol.id
        newCol.merged = false
        newCol.options.colspan = 1
        newCol.options.rowspan = 1
        newCol.widgetList.length = 0
        row.cols.splice(colIdx, 0, newCol)
      })

      this.emitHistoryChange()
    },

    insertTableRow(widget, insertPos, cloneRowIdx, curCol, aboveFlag) {
      let newRowIdx = !!aboveFlag ? insertPos : insertPos + 1 //初步确定插入行位置
      if (!aboveFlag) {
        //继续向下寻找同列第一个未被合并的单元格
        let tmpRowIdx = newRowIdx
        let rowFoundFlag = false
        while (tmpRowIdx < widget.rows.length) {
          if (!widget.rows[tmpRowIdx].cols[curCol].merged) {
            newRowIdx = tmpRowIdx
            rowFoundFlag = true
            break
          } else {
            tmpRowIdx++
          }
        }

        if (!rowFoundFlag) {
          newRowIdx = widget.rows.length
        }
      }

      let newRow = deepClone(widget.rows[cloneRowIdx])
      newRow.id = 'table-row-' + generateId()
      newRow.merged = false
      newRow.cols.forEach((col) => {
        col.id = 'table-cell-' + generateId()
        // col.options.name = col.id
        col.merged = false
        col.options.colspan = 1
        col.options.rowspan = 1
        col.widgetList.length = 0
      })
      widget.rows.splice(newRowIdx, 0, newRow)

      let colNo = 0
      while (newRowIdx < widget.rows.length - 1 && colNo < widget.rows[0].cols.length) {
        //越界判断
        const cellOfNextRow = widget.rows[newRowIdx + 1].cols[colNo]
        const rowMerged = cellOfNextRow.merged //确定插入位置下一行的单元格是否为合并单元格
        if (!!rowMerged) {
          let rowArray = widget.rows
          let unMergedCell = {}
          let startRowIndex = null
          for (let i = newRowIdx; i >= 0; i--) {
            //查找该行已合并的主单元格
            if (!rowArray[i].cols[colNo].merged && rowArray[i].cols[colNo].options.rowspan > 1) {
              startRowIndex = i
              unMergedCell = rowArray[i].cols[colNo]
              break
            }
          }

          if (!!unMergedCell.options) {
            //如果有符合条件的unMergedCell
            let newRowspan = unMergedCell.options.rowspan + 1
            this.setPropsOfMergedRows(widget.rows, startRowIndex, colNo, unMergedCell.options.colspan, newRowspan)
            colNo += unMergedCell.options.colspan
          } else {
            colNo += 1
          }
        } else {
          //colNo += 1
          colNo += cellOfNextRow.options.colspan || 1
        }
      }

      this.emitHistoryChange()
    },

    insertTableCol(widget, insertPos, curRow, leftFlag) {
      let newColIdx = !!leftFlag ? insertPos : insertPos + 1 //初步确定插入列位置
      if (!leftFlag) {
        //继续向右寻找同行第一个未被合并的单元格
        let tmpColIdx = newColIdx
        let colFoundFlag = false
        while (tmpColIdx < widget.rows[curRow].cols.length) {
          if (!widget.rows[curRow].cols[tmpColIdx].merged) {
            newColIdx = tmpColIdx
            colFoundFlag = true
            break
          } else {
            tmpColIdx++
          }

          if (!colFoundFlag) {
            newColIdx = widget.rows[curRow].cols.length
          }
        }
      }

      widget.rows.forEach((row) => {
        let newCol = deepClone(this.getContainerByType('table-cell'))
        newCol.id = 'table-cell-' + generateId()
        // newCol.options.name = newCol.id
        newCol.merged = false
        newCol.options.colspan = 1
        newCol.options.rowspan = 1
        newCol.widgetList.length = 0
        row.cols.splice(newColIdx, 0, newCol)
      })

      let rowNo = 0
      while (newColIdx < widget.rows[0].cols.length - 1 && rowNo < widget.rows.length) {
        //越界判断
        const cellOfNextCol = widget.rows[rowNo].cols[newColIdx + 1]
        const colMerged = cellOfNextCol.merged //确定插入位置右侧列的单元格是否为合并单元格
        if (!!colMerged) {
          let colArray = widget.rows[rowNo].cols
          let unMergedCell = {}
          let startColIndex = null
          for (let i = newColIdx; i >= 0; i--) {
            //查找该行已合并的主单元格
            if (!colArray[i].merged && colArray[i].options.colspan > 1) {
              startColIndex = i
              unMergedCell = colArray[i]
              break
            }
          }

          if (!!unMergedCell.options) {
            //如果有符合条件的unMergedCell
            let newColspan = unMergedCell.options.colspan + 1
            this.setPropsOfMergedCols(widget.rows, rowNo, startColIndex, newColspan, unMergedCell.options.rowspan)
            rowNo += unMergedCell.options.rowspan
          } else {
            rowNo += 1
          }
        } else {
          //rowNo += 1
          rowNo += cellOfNextCol.options.rowspan || 1
        }
      }

      this.emitHistoryChange()
    },

    setPropsOfMergedCols(rowArray, startRowIndex, startColIndex, newColspan, rowspan) {
      for (let i = startRowIndex; i < startRowIndex + rowspan; i++) {
        for (let j = startColIndex; j < startColIndex + newColspan; j++) {
          if (i === startRowIndex && j === startColIndex) {
            rowArray[i].cols[j].options.colspan = newColspan //合并后的主单元格
            continue
          }

          rowArray[i].cols[j].merged = true
          rowArray[i].cols[j].options.colspan = newColspan
          rowArray[i].cols[j].widgetList = []
        }
      }
    },

    setPropsOfMergedRows(rowArray, startRowIndex, startColIndex, colspan, newRowspan) {
      for (let i = startRowIndex; i < startRowIndex + newRowspan; i++) {
        for (let j = startColIndex; j < startColIndex + colspan; j++) {
          if (i === startRowIndex && j === startColIndex) {
            rowArray[i].cols[j].options.rowspan = newRowspan
            continue
          }

          rowArray[i].cols[j].merged = true
          rowArray[i].cols[j].options.rowspan = newRowspan
          rowArray[i].cols[j].widgetList = []
        }
      }
    },

    setPropsOfSplitCol(rowArray, startRowIndex, startColIndex, colspan, rowspan) {
      for (let i = startRowIndex; i < startRowIndex + rowspan; i++) {
        for (let j = startColIndex; j < startColIndex + colspan; j++) {
          rowArray[i].cols[j].merged = false
          rowArray[i].cols[j].options.rowspan = 1
          rowArray[i].cols[j].options.colspan = 1
        }
      }
    },

    setPropsOfSplitRow(rowArray, startRowIndex, startColIndex, colspan, rowspan) {
      for (let i = startRowIndex; i < startRowIndex + rowspan; i++) {
        for (let j = startColIndex; j < startColIndex + colspan; j++) {
          rowArray[i].cols[j].merged = false
          rowArray[i].cols[j].options.rowspan = 1
          rowArray[i].cols[j].options.colspan = 1
        }
      }
    },

    mergeTableCol(rowArray, colArray, curRow, curCol, leftFlag, cellWidget) {
      let mergedColIdx = !!leftFlag ? curCol : curCol + colArray[curCol].options.colspan

      // let remainedColIdx = !!leftFlag ? curCol - colArray[curCol - 1].options.colspan : curCol
      let remainedColIdx = !!leftFlag ? curCol - 1 : curCol
      if (!!leftFlag) {
        //继续向左寻找同行未被合并的第一个单元格
        let tmpColIdx = remainedColIdx
        while (tmpColIdx >= 0) {
          if (!rowArray[curRow].cols[tmpColIdx].merged) {
            remainedColIdx = tmpColIdx
            break
          } else {
            tmpColIdx--
          }
        }
      }

      if (!!colArray[mergedColIdx].widgetList && colArray[mergedColIdx].widgetList.length > 0) {
        //保留widgetList
        if (!colArray[remainedColIdx].widgetList || colArray[remainedColIdx].widgetList.length === 0) {
          colArray[remainedColIdx].widgetList = deepClone(colArray[mergedColIdx].widgetList)
        }
      }

      let newColspan = colArray[mergedColIdx].options.colspan * 1 + colArray[remainedColIdx].options.colspan * 1
      this.setPropsOfMergedCols(rowArray, curRow, remainedColIdx, newColspan, cellWidget.options.rowspan)

      this.emitHistoryChange()
    },

    mergeTableWholeRow(rowArray, colArray, rowIndex, colIndex) {
      //需要考虑操作的行存在已合并的单元格！！
      //整行所有单元格行高不一致不可合并！！
      let startRowspan = rowArray[rowIndex].cols[0].options.rowspan
      let unmatchedFlag = false
      for (let i = 1; i < rowArray[rowIndex].cols.length; i++) {
        if (rowArray[rowIndex].cols[i].options.rowspan !== startRowspan) {
          unmatchedFlag = true
          break
        }
      }
      if (unmatchedFlag) {
        this.vueInstance.$message.info(this.vueInstance.i18nt('designer.hint.rowspanNotConsistentForMergeEntireRow'))
        return
      }

      let widgetListCols = colArray.filter((colItem) => {
        return !colItem.merged && !!colItem.widgetList && colItem.widgetList.length > 0
      })
      if (!!widgetListCols && widgetListCols.length > 0) {
        //保留widgetList
        if (
          widgetListCols[0].id !== colArray[0].id &&
          (!colArray[0].widgetList || colArray[0].widgetList.length <= 0)
        ) {
          colArray[0].widgetList = deepClone(widgetListCols[0].widgetList)
        }
      }

      this.setPropsOfMergedCols(rowArray, rowIndex, 0, colArray.length, colArray[colIndex].options.rowspan)

      this.emitHistoryChange()
    },

    mergeTableRow(rowArray, curRow, curCol, aboveFlag, cellWidget) {
      let mergedRowIdx = !!aboveFlag ? curRow : curRow + cellWidget.options.rowspan

      //let remainedRowIdx = !!aboveFlag ? curRow - cellWidget.options.rowspan : curRow
      let remainedRowIdx = !!aboveFlag ? curRow - 1 : curRow
      if (!!aboveFlag) {
        //继续向上寻找同列未被合并的第一个单元格
        let tmpRowIdx = remainedRowIdx
        while (tmpRowIdx >= 0) {
          if (!rowArray[tmpRowIdx].cols[curCol].merged) {
            remainedRowIdx = tmpRowIdx
            break
          } else {
            tmpRowIdx--
          }
        }
      }

      if (
        !!rowArray[mergedRowIdx].cols[curCol].widgetList &&
        rowArray[mergedRowIdx].cols[curCol].widgetList.length > 0
      ) {
        //保留widgetList
        if (
          !rowArray[remainedRowIdx].cols[curCol].widgetList ||
          rowArray[remainedRowIdx].cols[curCol].widgetList.length === 0
        ) {
          rowArray[remainedRowIdx].cols[curCol].widgetList = deepClone(rowArray[mergedRowIdx].cols[curCol].widgetList)
        }
      }

      let newRowspan =
        rowArray[mergedRowIdx].cols[curCol].options.rowspan * 1 +
        rowArray[remainedRowIdx].cols[curCol].options.rowspan * 1
      this.setPropsOfMergedRows(rowArray, remainedRowIdx, curCol, cellWidget.options.colspan, newRowspan)

      this.emitHistoryChange()
    },

    mergeTableWholeCol(rowArray, colArray, rowIndex, colIndex) {
      //需要考虑操作的列存在已合并的单元格！！
      //整列所有单元格列宽不一致不可合并！！
      let startColspan = rowArray[0].cols[colIndex].options.colspan
      let unmatchedFlag = false
      for (let i = 1; i < rowArray.length; i++) {
        if (rowArray[i].cols[colIndex].options.colspan !== startColspan) {
          unmatchedFlag = true
          break
        }
      }
      if (unmatchedFlag) {
        this.vueInstance.$message.info(this.vueInstance.i18nt('designer.hint.colspanNotConsistentForMergeEntireColumn'))
        return
      }

      let widgetListCols = []
      rowArray.forEach((rowItem) => {
        let tempCell = rowItem.cols[colIndex]
        if (!tempCell.merged && !!tempCell.widgetList && tempCell.widgetList.length > 0) {
          widgetListCols.push(tempCell)
        }
      })

      let firstCellOfCol = rowArray[0].cols[colIndex]
      if (!!widgetListCols && widgetListCols.length > 0) {
        //保留widgetList
        if (
          widgetListCols[0].id !== firstCellOfCol.id &&
          (!firstCellOfCol.widgetList || firstCellOfCol.widgetList.length <= 0)
        ) {
          firstCellOfCol.widgetList = deepClone(widgetListCols[0].widgetList)
        }
      }

      this.setPropsOfMergedRows(rowArray, 0, colIndex, firstCellOfCol.options.colspan, rowArray.length)

      this.emitHistoryChange()
    },

    undoMergeTableCol(rowArray, rowIndex, colIndex, colspan, rowspan) {
      this.setPropsOfSplitCol(rowArray, rowIndex, colIndex, colspan, rowspan)

      this.emitHistoryChange()
    },

    undoMergeTableRow(rowArray, rowIndex, colIndex, colspan, rowspan) {
      this.setPropsOfSplitRow(rowArray, rowIndex, colIndex, colspan, rowspan)

      this.emitHistoryChange()
    },

    deleteTableWholeCol(rowArray, colIndex) {
      //需考虑删除的是合并列！！
      let onlyOneColFlag = true
      rowArray.forEach((ri) => {
        if (ri.cols[0].options.colspan !== rowArray[0].cols.length) {
          onlyOneColFlag = false
        }
      })
      //仅剩一列则不可删除！！
      if (onlyOneColFlag) {
        this.vueInstance.$message.info(this.vueInstance.i18nt('designer.hint.lastColCannotBeDeleted'))
        return
      }

      //整列所有单元格列宽不一致不可删除！！
      let startColspan = rowArray[0].cols[colIndex].options.colspan
      let unmatchedFlag = false
      for (let i = 1; i < rowArray.length; i++) {
        if (rowArray[i].cols[colIndex].options.colspan !== startColspan) {
          unmatchedFlag = true
          break
        }
      }
      if (unmatchedFlag) {
        this.vueInstance.$message.info(
          this.vueInstance.i18nt('designer.hint.colspanNotConsistentForDeleteEntireColumn')
        )
        return
      }

      rowArray.forEach((rItem) => {
        rItem.cols.splice(colIndex, startColspan)
      })

      this.emitHistoryChange()
    },

    deleteTableWholeRow(rowArray, rowIndex) {
      //需考虑删除的是合并行！！
      let onlyOneRowFlag = true
      rowArray[0].cols.forEach((ci) => {
        if (ci.options.rowspan !== rowArray.length) {
          onlyOneRowFlag = false
        }
      })
      //仅剩一行则不可删除！！
      if (onlyOneRowFlag) {
        this.vueInstance.$message.info(this.vueInstance.i18nt('designer.hint.lastRowCannotBeDeleted'))
        return
      }

      //整行所有单元格行高不一致不可删除！！
      let startRowspan = rowArray[rowIndex].cols[0].options.rowspan
      let unmatchedFlag = false
      for (let i = 1; i < rowArray[rowIndex].cols.length; i++) {
        if (rowArray[rowIndex].cols[i].options.rowspan !== startRowspan) {
          unmatchedFlag = true
          break
        }
      }
      if (unmatchedFlag) {
        this.vueInstance.$message.info(this.vueInstance.i18nt('designer.hint.rowspanNotConsistentForDeleteEntireRow'))
        return
      }

      rowArray.splice(rowIndex, startRowspan)

      this.emitHistoryChange()
    },

    getContainerByType(typeName) {
      let allWidgets = [...containers, ...basicFields, ...advancedFields, ...customFields]
      let foundCon = null
      allWidgets.forEach((con) => {
        if (!!con.category && !!con.type && con.type === typeName) {
          foundCon = con
        }
      })

      return foundCon
    },

    getFieldWidgetByType(typeName) {
      let allWidgets = [...containers, ...basicFields, ...advancedFields, ...customFields]
      let foundWidget = null
      allWidgets.forEach((widget) => {
        if (!!!widget.category && !!widget.type && widget.type === typeName) {
          foundWidget = widget
        }
      })

      return foundWidget
    },

    hasConfig(widget, configName) {
      let originalWidget = null
      if (!!widget.category) {
        originalWidget = this.getContainerByType(widget.type)
      } else {
        originalWidget = this.getFieldWidgetByType(widget.type)
      }

      if (!originalWidget || !originalWidget.options) {
        return false
      }

      return Object.keys(originalWidget.options).indexOf(configName) > -1
    },

    upgradeWidgetConfig(oldWidget) {
      let newWidget = null
      if (!!oldWidget.category) {
        newWidget = this.getContainerByType(oldWidget.type)
      } else {
        newWidget = this.getFieldWidgetByType(oldWidget.type)
      }

      if (!newWidget || !newWidget.options) {
        return
      }

      Object.keys(newWidget.options).forEach((ck) => {
        if (!oldWidget.hasOwnProperty(ck)) {
          oldWidget.options[ck] = deepClone(newWidget.options[ck])
        }
      })
    },

    upgradeFormConfig(oldFormConfig) {
      Object.keys(this.formConfig).forEach((fc) => {
        if (!oldFormConfig.hasOwnProperty(fc)) {
          oldFormConfig[fc] = deepClone(this.formConfig[fc])
        }
      })
    },

    cloneGridCol(widget, parentWidget) {
      let newGridCol = deepClone(this.getContainerByType('grid-col'))
      newGridCol.options.span = widget.options.span
      let tmpId = generateId()
      newGridCol.id = 'grid-col-' + tmpId
      newGridCol.options.name = 'gridCol' + tmpId

      parentWidget.cols.push(newGridCol)
    },

    cloneContainer(containWidget) {
      if (containWidget.type === 'grid') {
        let newGrid = deepClone(this.getContainerByType('grid'))
        newGrid.id = newGrid.type + generateId()
        newGrid.options.name = newGrid.id
        containWidget.cols.forEach((gridCol) => {
          let newGridCol = deepClone(this.getContainerByType('grid-col'))
          let tmpId = generateId()
          newGridCol.id = 'grid-col-' + tmpId
          newGridCol.options.name = 'gridCol' + tmpId
          newGridCol.options.span = gridCol.options.span
          newGrid.cols.push(newGridCol)
        })

        return newGrid
      } else if (containWidget.type === 'table') {
        let newTable = deepClone(this.getContainerByType('table'))
        newTable.id = newTable.type + generateId()
        newTable.options.name = newTable.id
        containWidget.rows.forEach((tRow) => {
          let newRow = deepClone(tRow)
          newRow.id = 'table-row-' + generateId()
          newRow.cols.forEach((col) => {
            col.id = 'table-cell-' + generateId()
            col.options.name = col.id
            col.widgetList = [] //清空组件列表
          })
          newTable.rows.push(newRow)
        })

        return newTable
      } else {
        //其他容器组件不支持clone操作
        return null
      }
    },

    moveUpWidget(parentList, indexOfParentList) {
      if (!!parentList) {
        if (indexOfParentList === 0) {
          this.vueInstance.$message(this.vueInstance.i18nt('designer.hint.moveUpFirstChildHint'))
          return
        }

        let tempWidget = parentList[indexOfParentList]
        parentList.splice(indexOfParentList, 1)
        parentList.splice(indexOfParentList - 1, 0, tempWidget)
      }
    },

    moveDownWidget(parentList, indexOfParentList) {
      if (!!parentList) {
        if (indexOfParentList === parentList.length - 1) {
          this.vueInstance.$message(this.vueInstance.i18nt('designer.hint.moveDownLastChildHint'))
          return
        }

        let tempWidget = parentList[indexOfParentList]
        parentList.splice(indexOfParentList, 1)
        parentList.splice(indexOfParentList + 1, 0, tempWidget)
      }
    },

    copyNewFieldWidget(origin) {
      let newWidget = deepClone(origin)
      let tempId = generateId()
      newWidget.id = newWidget.type.replace(/-/g, '') + tempId
      // newWidget.options.name = newWidget.id
      // newWidget.options.label = newWidget.options.label || newWidget.type.toLowerCase()

      delete newWidget.displayName
      return newWidget
    },

    copyNewContainerWidget(origin) {
      let newCon = deepClone(origin)
      newCon.id = newCon.type.replace(/-/g, '') + generateId()
      newCon.options.name = newCon.id
      if (newCon.type === 'grid') {
        let newCol = deepClone(this.getContainerByType('grid-col'))
        let tmpId = generateId()
        newCol.id = 'grid-col-' + tmpId
        newCol.options.name = 'gridCol' + tmpId
        newCon.cols.push(newCol)
        //
        newCol = deepClone(newCol)
        tmpId = generateId()
        newCol.id = 'grid-col-' + tmpId
        newCol.options.name = 'gridCol' + tmpId
        newCon.cols.push(newCol)
      } else if (newCon.type === 'table') {
        let newRow = { cols: [] }
        newRow.id = 'table-row-' + generateId()
        newRow.merged = false
        let newCell = deepClone(this.getContainerByType('table-cell'))
        newCell.id = 'table-cell-' + generateId()
        // newCell.options.name = newCell.id
        newCell.merged = false
        newCell.options.colspan = 1
        newCell.options.rowspan = 1
        newRow.cols.push(newCell)
        newCon.rows.push(newRow)
      } else if (newCon.type === 'tab') {
        let newTabPane = deepClone(this.getContainerByType('tab-pane'))
        newTabPane.id = 'tab-pane-' + generateId()
        newTabPane.options.name = 'tab1'
        newTabPane.options.label = 'tab 1'
        newCon.tabs.push(newTabPane)
      }
      //newCon.options.customClass = []

      delete newCon.displayName
      return newCon
    },

    addContainerByDbClick(container) {
      let newCon = this.copyNewContainerWidget(container)
      this.widgetList.push(newCon)
      this.setSelected(newCon)
    },

    addFieldByDbClick(widget) {
      let newWidget = this.copyNewFieldWidget(widget)
      if (!!this.selectedWidget && this.selectedWidget.type === 'tab') {
        //获取当前激活的tabPane
        let activeTab = this.selectedWidget.tabs[0]
        this.selectedWidget.tabs.forEach((tabPane) => {
          if (!!tabPane.options.active) {
            activeTab = tabPane
          }
        })

        !!activeTab && activeTab.widgetList.push(newWidget)
      } else if (!!this.selectedWidget && !!this.selectedWidget.widgetList) {
        this.selectedWidget.widgetList.push(newWidget)
      } else {
        this.widgetList.push(newWidget)
      }

      this.setSelected(newWidget)
      this.emitHistoryChange()
      this.setNamesList(newWidget)
    },

    deleteColOfGrid(gridWidget, colIdx) {
      if (!!gridWidget && !!gridWidget.cols) {
        gridWidget.cols.splice(colIdx, 1)
      }
    },

    addNewColOfGrid(gridWidget) {
      const cols = gridWidget.cols
      let newGridCol = deepClone(this.getContainerByType('grid-col'))
      let tmpId = generateId()
      newGridCol.id = 'grid-col-' + tmpId
      newGridCol.options.name = 'gridCol' + tmpId
      if (!!cols && cols.length > 0) {
        let spanSum = 0
        cols.forEach((col) => {
          spanSum += col.options.span
        })

        if (spanSum >= 24) {
          gridWidget.cols.push(newGridCol)
        } else {
          newGridCol.options.span = 24 - spanSum > 12 ? 12 : 24 - spanSum
          gridWidget.cols.push(newGridCol)
        }
      } else {
        gridWidget.cols = [newGridCol]
      }
    },

    addTabPaneOfTabs(tabsWidget) {
      const tabPanes = tabsWidget.tabs
      let newTabPane = deepClone(this.getContainerByType('tab-pane'))
      newTabPane.id = 'tab-pane-' + generateId()
      newTabPane.options.name = newTabPane.id
      newTabPane.options.label = 'tab ' + (tabPanes.length + 1)
      tabPanes.push(newTabPane)
    },

    deleteTabPaneOfTabs(tabsWidget, tpIdx) {
      tabsWidget.tabs.splice(tpIdx, 1)
    },

    emitEvent(evtName, evtData) {
      //用于兄弟组件发射事件
      this.vueInstance.$emit(evtName, evtData)
    },

    handleEvent(evtName, callback) {
      //用于兄弟组件接收事件
      this.vueInstance.$on(evtName, (data) => callback(data))
    },

    setCssClassList(cssClassList) {
      this.cssClassList = cssClassList
    },

    getCssClassList() {
      return this.cssClassList
    },

    registerFormWidget(formWidget) {
      this.formWidget = formWidget
    },

    initHistoryData() {
      this.loadFormContentFromStorage()
      this.historyData.index++
      this.historyData.steps[this.historyData.index] = {
        widgetList: deepClone(this.widgetList),
        formConfig: deepClone(this.formConfig)
      }
    },

    emitHistoryChange() {
      if (this.historyData.index === this.historyData.maxStep - 1) {
        this.historyData.steps.shift()
      } else {
        this.historyData.index++
      }

      this.historyData.steps[this.historyData.index] = {
        widgetList: deepClone(this.widgetList),
        formConfig: deepClone(this.formConfig),
        selectNames: deepClone(this.selectNames)
      }

      this.saveFormContentToStorage()

      if (this.historyData.index < this.historyData.steps.length - 1) {
        this.historyData.steps = this.historyData.steps.slice(0, this.historyData.index + 1)
      }
    },

    saveCurrentHistoryStep() {
      this.historyData.steps[this.historyData.index] = deepClone({
        widgetList: this.widgetList,
        formConfig: this.formConfig,
        selectNames: this.selectNames
      })

      this.saveFormContentToStorage()
    },

    undoHistoryStep() {
      if (this.historyData.index !== 0) {
        this.historyData.index--
      }
      this.widgetList = deepClone(this.historyData.steps[this.historyData.index].widgetList)
      this.formConfig = deepClone(this.historyData.steps[this.historyData.index].formConfig)
      this.selectNames = deepClone(this.historyData.steps[this.historyData.index].selectNames)
    },
    deleteHistoryStepNames(name) {
      let index = this.selectNames.findIndex((item) => item === name)
      this.selectNames.splice(index, 1)
    },
    redoHistoryStep() {
      if (this.historyData.index !== this.historyData.steps.length - 1) {
        this.historyData.index++
      }
      this.widgetList = deepClone(this.historyData.steps[this.historyData.index].widgetList)
      this.formConfig = deepClone(this.historyData.steps[this.historyData.index].formConfig)
      this.selectNames = deepClone(this.historyData.steps[this.historyData.index].selectNames)
    },

    undoEnabled() {
      return this.historyData.index > 0 && this.historyData.steps.length > 0
    },

    redoEnabled() {
      return this.historyData.index < this.historyData.steps.length - 1
    },
    // initHistoryData() {
    //   this.historyData = {
    //     index: -1,
    //     maxStep: 10,
    //     steps: []
    //   }
    // },
    saveFormContentToStorage() {
      window.localStorage.setItem('widget__list__backup', JSON.stringify(this.widgetList))
      window.localStorage.setItem('form__config__backup', JSON.stringify(this.formConfig))
    },

    loadFormContentFromStorage() {
      let widgetListBackup = window.localStorage.getItem('widget__list__backup')
      if (!!widgetListBackup) {
        this.widgetList = JSON.parse(widgetListBackup)
      }

      let formConfigBackup = window.localStorage.getItem('form__config__backup')
      if (!!formConfigBackup) {
        //this.formConfig = JSON.parse(formConfigBackup)
        overwriteObj(
          this.formConfig,
          JSON.parse(formConfigBackup)
        ) /* 用=赋值，会导致inject依赖注入的formConfig属性变成非响应式 */
      }
    }
  }
}
