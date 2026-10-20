<template>
    <div>
        <oa-dialog :fromData="fromData" ref="oaDialog" @submit="submit">
            <el-form ref="form" :model="form" :rules="rules" label-width="auto" @submit.prevent>
                <el-form-item label="显示名称：" prop="field_name">
                    <el-input v-model="form.field_name" placeholder="请输入显示名称" />
                </el-form-item>
                <el-form-item label="实体名称：" prop="field_name_en">
                    <el-input :disabled="fromData.type == 'edit' ? true : false" v-model="form.field_name_en"
                        placeholder="英文小写字母开头，不可包含中文，空格，中间可输入下划线" class="refresh-input"
                        @focus="refreshFn()">
                        <template #suffix>
                          <el-button type="primary" class="refresh" :disabled="fromData.type == 'edit' ? true : false"
 @click.stop="refreshFn()">
                              刷新生成</el-button>
                        </template>
                    </el-input>
                </el-form-item>
                <el-form-item label="选项类型：" prop="value">
                    <el-select v-model="form.value" placeholder="请选择选项类型" style="width: 100%;">
                        <el-option v-for="item in textTypes" :key="item.value" :label="item.label"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <!-- <el-form-item label="选项数据：" prop="data_type" v-if="rowData.value == 'select'">
                    <el-radio-group v-model="form.data_type" class="vertical">
                        <el-radio label="1">静态数据</el-radio>
                        <el-radio label="0">数据字典</el-radio>
                    </el-radio-group>
                </el-form-item> -->

                <el-form-item label="关联字典：" prop="data_dict_id" v-if="form.data_type == '0'">
                    <el-select v-model="form.data_dict_id" placeholder="请搜索选择数据字典" style="width: 100%;">
                        <el-option v-for="item in dictListData" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>

                <!-- 一对一引用 -->
                <el-form-item label="引用实体：" v-if="form.value == 'input_select'">
                    <div class="el-input__inner select plan-footer-on flex-between h32" @click="checkboxDialogOpen()">
                        <div class="over-text1" @click="checkboxDialogOpen()">
                            <span @click="checkboxDialogOpen()" v-for="(items, indexs) in fieldList" :key="indexs"
                                @click.stop="">
                                {{ items.field_name }},
                            </span>
                        </div>
                        <i class="el-tag__close el-icon-arrow-down" />
                    </div>
                </el-form-item>
                <el-form-item label="字段唯一：" prop="is_uniqid" v-if="form.value == 'input'">
                    <el-switch v-model="form.is_uniqid" active-value="1" inactive-value="0"
                        active-text="开启" inactive-text="关闭">
                    </el-switch>
                </el-form-item>
                <el-form-item label="新增时：" prop="create_modify">
                    <el-radio-group v-model="form.create_modify" class="vertical">
                        <el-radio v-for="(itemOption, index) in options" :key="index" :value="itemOption.label">{{
                            itemOption.value
                        }}</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="编辑时：" prop="update_modify">
                    <el-radio-group v-model="form.update_modify" class="vertical">
                        <el-radio v-for="(itemOption, index) in options" :key="index" :value="itemOption.label">{{
                            itemOption.value
                        }}</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
        </oa-dialog>
        <!-- 引用实体弹窗 -->
        <checkboxDialog ref="checkboxDialog" @getData="getDataFn" :type="`field`" :showCrud="true"></checkboxDialog>
    </div>
</template>
<script>
import { pinyin } from 'pinyin-pro'
import oaDialog from '@/components/form-common/dialog-form.vue'
import checkboxDialog from '@/components/develop/checkboxDialog'
export default {
    name: "FieldDialig",
    emits: ["submit"],
    components: {
        oaDialog,
        checkboxDialog
    },
    props: {
        typesObj: {
            type: Object,
            default: () => { }
        },
        rowData: {
            type: Object,
            default: () => { }
        },
        dictList: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            visible: false,
            fromData: {
                width: '600px',
                title: '新建字段',
                btnText: '确定',
                labelWidth: '100px',
                type: 'slot'
            },
            fieldList: [],
            textTypes: [],
            options: [
                {
                    label: 1,
                    value: '允许编辑'
                },
                {
                    label: 0,
                    value: '不允许编辑'
                }
            ],
            form: {
                crud_id: 0,
                value: '',
                field_name: '',
                field_name_en: '',
                is_default_value_not_null: 1, // 允许空值
                is_table_show_row: 1, // 列表默认显示
                create_modify: 1, // 新增时修改
                update_modify: 1, // 更新时修改
                comment: '',
                data_dict_id: '',
                data_type: '1', // 数据选项
                is_city_show: 'city',
                customizeItems: [], // 自定义选项字段
                association_crud_id: '', // 关联表id
                association_field_names: [],
                association_field_names_list: null

            },
            rules: {
                field_name: [
                    {
                        required: true,
                        message: '请输入显示名称',
                        trigger: 'blur'
                    },
                    {
                        validator: function (rule, value, callback) {
                            if (/^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/.test(value) == false) {
                                callback(new Error('以中文，英文字母开头，中间可输入下划线，最多可输入16个字'))
                            } else {
                                callback()
                            }
                        },
                        trigger: 'blur'
                    }
                ],
                field_name_en: [
                    {
                        required: true,
                        message: '请输入字段名称',
                        trigger: 'blur'
                    },
                    {
                        validator: function (rule, value, callback) {
                            if (/^[a-z][A-Za-z0-9_]*$/.test(value) == false) {
                                callback(new Error('英文小写字母开头，不可包含中文，空格，中间可输入下划线'))
                            } else {
                                callback()
                            }
                        },
                        trigger: 'blur'
                    }
                ],
                value: [
                    {
                        required: true,
                        message: '请选择文本类型',
                        trigger: 'change'
                    }
                ],
                data_dict_id: [
                    {
                        required: true,
                        message: '请选择关联字典',
                        trigger: 'change'
                    }
                ]
            }
        }
    },
    watch: {

    },
    computed: {
        dictListData() {

            if (this.form.value == 'tag') {
                return this.dictList.filter((item) => item.level == 2)
            } else {
                return this.dictList
            }

        }
    },
    methods: {
        openBox(data) {
            if (data) {
                for (let key in data) {
                    this.form[key] = data[key]
                }
            }
            if (data && data.id) {
                this.fromData.title = '编辑字段'
            } else {
                this.textTypes = this.typesObj[this.rowData.value]
                this.fromData.title = '新建字段' + '-' + this.rowData.label
            }


            this.$refs.oaDialog.openBox()
        },
        submit() {
            this.$refs.form.validate((valid) => {
                if (valid) {
                    this.$emit('submit', this.form)
                }
            })

        },
        getDataFn(data) {
            this.form.association_crud_id = data.id
            this.form.association_field_names = []
            this.fieldList = data.selectList
            data.selectList.map((item) => {
                this.form.association_field_names.push(item.field_name_en)
            })
        },
        // 打开一对一弹窗
        checkboxDialogOpen() {
            if (this.form.association_field_names && this.form.association_field_names.length > 0) {
                let ids = []
                this.fieldList.map((item) => {
                    ids.push(item.id)
                })
                let data = {
                    type: this.fromData.type,
                    id: Number(this.form.association_crud_id),
                    ids,
                    selectList: this.fieldList
                }
                this.$refs.checkboxDialog.openBox(data)
            } else {
                this.$refs.checkboxDialog.openBox()
            }
        },
        handleClose() {
            this.$refs.oaDialog.handleClose()
            this.form = {
                crud_id: 0,
                value: '',
                field_name: '',
                field_name_en: '',
                is_default_value_not_null: 1, // 允许空值
                is_table_show_row: 1, // 列表默认显示
                create_modify: 1, // 新增时修改
                update_modify: 1, // 更新时修改
                comment: '',
                data_dict_id: '',
                data_type: '1', // 数据选项
                is_city_show: 'city',
                customizeItems: [], // 自定义选项字段
                association_crud_id: '', // 关联表id
                association_field_names: [],
                association_field_names_list: null

            }
        },
        // 刷新转拼音小写
        refreshFn() {
            if (this.form.field_name == '') {
                return false
            }
            const regex = /^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/
            if (!regex.test(this.form.field_name)) {
                return false
            }
            this.strData = pinyin(this.form.field_name, { toneType: 'none' })
            const reg = /[\t\r\f\n\s]*/g
            if (typeof this.strData === 'string') {
                this.strData = this.strData.replace(reg, '')
            }
              // 将 ü 转换为 V
        this.strData = this.strData.replace(/ü/, 'v')
            this.form.field_name_en = this.strData
        }, 
        
        // 首字母转成大写
        titleCase(str) {
            const newStr = str.slice(0, 1).toUpperCase() + str.slice(1).toLowerCase()
            return newStr
        },
    }
}
</script>