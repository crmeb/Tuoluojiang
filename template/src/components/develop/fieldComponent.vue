<template>
    <div>

        <!-- 下拉选择 -->
        <el-select v-if="item.form_value === 'select' && selectList.includes(item.type)" v-model="item.option"
            size="small" :multiple="item.field === 'repeat' ? false : true" style="width: 100%" filterable>
            <el-option v-for="items in item.options" :key="items.value" :value="items.value"
                :label="items.label || items.name"></el-option>
        </el-select>
        <el-select style="width: 100%" v-model="item.option" v-if="item.form_value === 'switch'" size="small">
            <el-option value="1" label="是"></el-option>
            <el-option value="0" label="否"></el-option>
        </el-select>

        <!-- 输入框类型 -->
        <el-input v-model="item.option" size="small"
            v-if="item.form_value == 'input' && ['input', 'textarea'].includes(item.type)"></el-input>

        <!-- 数字类型 -->
        <template v-if="item.form_value == 'number'">
            <el-input v-model="item.option" size="small" style="width: 100%" v-if="item.value == 'regex'">
            </el-input>
            <el-input-number v-model="item.option" :controls="false" size="small"
                :precision="item.type == 'input_number' ? 0 : undefined" style="width: 100%"
                v-if="!['between', 'regex'].includes(item.value)"></el-input-number>
            <!-- 区间 -->
            <div v-if="item.value == 'between'" class="flex">
                <el-input-number v-model="item.min" :controls="false" style="width: 50%" size="small"
                    :precision="item.type == 'input_number' ? 0 : undefined"></el-input-number>
                <el-input-number v-model="item.max" :controls="false" style="width: 50%" class="ml10" size="small"
                    :precision="item.type == 'input_number' ? 0 : undefined"></el-input-number>
            </div>
        </template>

        <!-- 级联选择 -->
        <el-cascader
            v-show="['cascader', 'cascader_radio'].includes(item.type) && item.options && item.option !== undefined"
            v-model="item.option" :options="getOptions()" :props="{
                label: 'name',
                value: 'value',
                expandTrigger: 'hover',
                emitPath: item.emitPath === undefined ? true : item.emitPath,
                multiple: true
            }" style="width: 100%" size="small" filterable></el-cascader>

        <!-- 管理范围 -->
        <el-cascader v-if="!noRule && item.type == 'scope_frame'" style="width: 100%" size="small" v-model="item.option"
            :options="frameTreeData" :props="{ checkStrictly: true, emitPath: false, value: 'id', label: 'label' }"
            placeholder="管理范围" filterable clearable :show-all-levels="false"></el-cascader>
        <!-- 级联选择省市区 -->
        <el-cascader v-model="item.option" :options="item.options || []" :props="{
            checkStrictly: true,
            label: 'name',
            value: 'value',
            multiple: true
        }" style="width: 100%" filterable size="small" v-if="['cascader_address'].includes(item.type)"></el-cascader>

        <!-- 日期选择 -->
        <div v-if="item.type == 'date_picker' && !dateList.includes(item.value)">
            <div v-if="item.value == 'between' || !noRule" class="flex">
                <el-date-picker v-model="item.option" type="daterange" :picker-options="pickerOptions"
                    range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" style="width: 100%" size="small"
                    format=" yyyy/MM/dd" value-format="yyyy/MM/dd">
                </el-date-picker>
            </div>
            <el-input-number v-else-if="['n_day', 'last_day', 'next_day'].includes(item.value)" v-model="item.option"
                :controls="false" :min="0" style="width: 100%" size="small"></el-input-number>
            <el-date-picker v-else style="width: 100%" v-model="item.option" type="date" placeholder="选择日期"
                format=" yyyy/MM/dd" value-format="yyyy/MM/dd" size="small">
            </el-date-picker>
        </div>

        <!-- 日期时间选择 -->
        <div v-if="item.type == 'date_time_picker' && !lastYearList.includes(item.value)">
            <div v-if="item.value == 'between'" class="flex">
                <el-date-picker v-model="item.option" type="datetimerange" range-separator="至" start-placeholder="开始日期"
                    end-placeholder="结束日期" style="width: 100%" size="small" format=" yyyy/MM/dd HH:mm:ss"
                    value-format="yyyy/MM/dd HH:mm:ss" :picker-options="pickerOptions">
                </el-date-picker>
            </div>
            <el-input-number v-else-if="['n_day', 'last_day', 'next_day'].includes(item.value)" v-model="item.option"
                :controls="false" :min="0" style="width: 100%" size="small"></el-input-number>
            <el-date-picker v-else style="width: 100%" v-model="item.option" type="datetime"
                format=" yyyy/MM/dd HH:mm:ss" value-format="yyyy/MM/dd HH:mm:ss" placeholder="选择日期时间" size="small">
            </el-date-picker>
        </div>

        <!-- 一对一关联字段 -->
        <select-one v-if="item.type == 'input_select' && !item.category" :value="item.options[0] || {}" :id="item.id"
            :showType="item.association_show_type" style="width: 100%"
            @getSelection="getSelection($event, item)"></select-one>

        <!-- 选择标签 -->
        <select-label v-if="item.type == 'tag'" :list="item.options || []" :value="item.optionsList || []"
            style="width: 100%" :props="{ children: 'children', label: 'name' }"
            @handleLabelConf="handleLabelConf($event)"></select-label>

        <!-- 选择标签（弹窗形式），数据出入口与 tag 保持一致，仅交互形态不同 -->
        <div v-if="item.type == 'tag_dialog'" class="tag-dialog-field">
            <div class="tag-dialog-trigger plan-footer-one" @click="openLabelDialog">
                <span v-if="selectedLabels.length == 0" class="placeholder">请选择标签</span>
                <div v-else class="tag-dialog-list">
                    <span v-for="(label, labelIndex) in selectedLabels" :key="label.id"
                        class="el-tag el-tag--small el-tag--info el-tag--light">
                        {{ label.name }}
                        <i class="el-tag__close el-icon-close" @click.stop="removeLabel(labelIndex)" />
                    </span>
                </div>
                <span class="el-icon-arrow-down" v-if="selectedLabels.length == 0"></span>
                <span class="el-icon-error" v-else @click.stop="clearLabels"></span>
            </div>
            <label-dialog ref="labelDialogRef" :config="labelDialogConfig" @loaded="handleLabelSourceLoaded"
                @handleLabelConf="handleLabelDialogConf"></label-dialog>
        </div>

        <!-- 选择人员 -->
        <select-member v-if="item.category == 2" :onlyOne="['in', 'not_in'].includes(item.value) ? false : true"
            :value="item.options.userList || []" :selectIdData="item.option || []"
            @getSelectList="getSelectList($event, item)" style="width: 100%"></select-member>

        <!-- 选择部门 -->
        <select-department v-if="item.category == 1" :onlyOne="['in', 'not_in'].includes(item.value) ? false : true"
            :value="item.options.depList || []" @changeMastart="changeMastart($event, item)"
            style="width: 100%"></select-department>
    </div>
</template>
<script>
import pickerOptions from '@/libs/pickerOptions'
export default {
    name: "FieldComponent",
    components: {
        selectMember: () => import('@/components/form-common/select-member'),
        selectDepartment: () => import('@/components/form-common/select-department'),
        selectOne: () => import('@/components/form-common/select-one'),
        selectLabel: () => import('@/components/form-common/select-label'),
        labelDialog: () => import('@/views/customer/list/components/labelDialog')
    },
    props: {
        item: Object,
        noRule: Boolean,
        activeIndex: Number,
        conditionConfig: Object,
        list: Array,
        index: Number,
        type: String // 图表设计'dashboard'  
    },


    data() {
        return {
            pickerOptions,
            selectList: ['select', 'radio', 'checkbox'],
            dateList: ['today', 'week', 'month', 'quarter', 'year'],
            lastYearList: ['today', 'week', 'month', 'quarter', 'year', 'last_year'],
            selectedLabels: [], // tag_dialog 已选标签（对象数组）
            labelSource: [], // 全量标签数据，用于只有标签 id 时补全名称
            labelDialogConfig: {}
        }
    },
    watch: {
        // 切换筛选字段时 item 会被整体替换，这里同步重置已选标签，避免残留上一个字段的数据
        item: {
            handler() {
                this.initSelectedLabels()
            },
            immediate: true
        }
    },
    methods: {
        // 从 item 上已有的数据还原 tag_dialog 的选中标签，兼容新增与编辑回显
        initSelectedLabels() {
            if (!this.item || this.item.type !== 'tag_dialog') {
                this.selectedLabels = []
                return
            }
            const source = this.item.optionsList && this.item.optionsList.length ? this.item.optionsList : this.item.option
            const ids = Array.isArray(source) ? source : []
            if (ids.length === 0) {
                this.selectedLabels = []
                return
            }
            const idSet = new Set(ids.map(Number))
            const matched = []
            // item.options 未选中时是标签树，选中后是扁平的标签集合，这里统一递归查找
            const walk = (nodes) => {
                if (!Array.isArray(nodes)) return
                nodes.forEach((node) => {
                    if (!node) return
                    if (idSet.has(Number(node.id))) matched.push(node)
                    if (node.children && node.children.length > 0) walk(node.children)
                })
            }
            walk(this.item.options)
            // 编辑回显时后端只返回标签 id，需要再从全量标签数据里补齐名称
            if (matched.length < idSet.size) walk(this.labelSource)
            this.selectedLabels = matched.filter(
                (obj, index) => matched.findIndex((el) => Number(el.id) === Number(obj.id)) === index
            )
        },
        handleLabelSourceLoaded(list) {
            this.labelSource = list || []
            this.initSelectedLabels()
        },
        openLabelDialog() {
            this.labelDialogConfig = {
                title: '客户标签',
                width: '540px',
                label: this.selectedLabels,
                edit: 1
            }
            this.$refs.labelDialogRef.handleOpen()
        },
        // 弹窗确定后的回调，落库字段与 select-label 保持一致
        handleLabelDialogConf(res) {
            this.selectedLabels = res.data || []
            this.syncSelectedLabels()
        },
        removeLabel(index) {
            this.selectedLabels.splice(index, 1)
            this.syncSelectedLabels()
        },
        clearLabels() {
            this.selectedLabels = []
            this.syncSelectedLabels()
        },
        syncSelectedLabels() {
            const ids = this.selectedLabels.map((label) => label.id)
            if (this.list && this.list[this.index]) {
                if (this.type !== 'dashboard') {
                    this.$set(this.list[this.index], 'options', this.selectedLabels)
                }
                this.$set(this.list[this.index], 'optionsList', ids)
                this.$set(this.list[this.index], 'option', ids)
            }
            this.$set(this.item, 'optionsList', ids)
            this.$set(this.item, 'option', ids)
        },
        getOptions() {
            return Array.isArray(this.item.options) ? this.item.options : [];
        },
        // 选择成员回调
        getSelectList(data, item) {
            if (data.length > 0) {
                data.forEach((item) => {
                    item.id = item.value
                })
            }
            item.options.userList = data
            let arr = []
            data.map((item) => {
                arr.push(item.id)
            })
            item.option = arr
            if (this.activeIndex) {
                this.activeIndex = -1
            }

        },
        // 选择部门完成回调
        changeMastart(data, item) {
            item.options.depList = data
            let arr = []
            data.map((item) => {
                arr.push(item.id)
            })
            item.option = arr
            if (this.activeIndex) {
                this.activeIndex = -1
            }
        },
        // 选中客户标签成功回调
        handleLabelConf(res) {
            if (this.type !== 'dashboard') {
                this.list[this.index].options = res.list
            }
            this.list[this.index].optionsList = res.ids
            this.list[this.index].option = res.ids
            this.item.option = res.ids
        },
        getSelection(data, item) {
            item.options = [data]
            item.option = data.id
            if (this.activeIndex) {
                this.activeIndex = -1
            }
        },


    }
}
</script>

<style scoped lang="scss">
.tag-dialog-field {
    // 外层占位 36px 对齐同行 el-select 的容器高度，内部 32px 触发框垂直居中，保证两侧输入框边框严格齐平
    display: flex;
    align-items: center;
    min-height: 36px;
    width: 100%;
}

.tag-dialog-trigger {
    position: relative;
    cursor: pointer;
    box-sizing: border-box;
    width: 100%;
    // 与同行 el-select 的输入框保持一致的 32px 可见高度：24px 内容 + 上下各 3px 内边距 + 1px 边框
    min-height: 32px;
    padding: 3px 26px 3px 15px;
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    font-size: 13px;
    // 该组件被放在 el-form-item 内，需要覆盖继承来的 36px 行高，否则整体会被撑到 44px
    line-height: 24px;
    color: #c0c4cc;
    transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);

    .el-tag.el-tag--info {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #303133;
    }
}

.tag-dialog-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    // 行间距 4px、列间距 10px，用 gap 代替标签自带 margin，避免多行时底部多出一段空白
    gap: 4px 10px;
    width: 100%;
}

.tag-dialog-trigger > .el-icon-arrow-down,
.tag-dialog-trigger > .el-icon-error {
    font-weight: 400;
    position: absolute;
    right: 10px;
}
</style>