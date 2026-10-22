<template>
    <div>

        <div v-if="item.form_value === 'image'" class="img-box">
            <img v-for="(val, index) in scope.row[item.field_name_en]" :key="index" :src="val.url" alt="" class="img"
                @click="lookViewer(val.url, val.name)" />
            <span v-if="!scope.row[item.field_name_en] || scope.row[item.field_name_en].length == 0">--</span>
        </div>
        <div v-else-if="item.form_value === 'input_percentage'">
            <el-progress :percentage="scope.row[item.field_name_en] ? scope.row[item.field_name_en] : 0"></el-progress>
        </div>
        <div v-else-if="item.form_value === 'tag'">
            <el-popover v-if="scope.row[item.field_name_en] && scope.row[item.field_name_en].length > 2"
                placement="top-start" trigger="hover">
                <template>
                    <div class="flex_box">
                        <div class="tips" v-for="(val, index) in scope.row[item.field_name_en]" :key="index">
                            <el-tag size="small">
                                {{ val }}
                            </el-tag>
                        </div>
                    </div>
                </template>
                <template #reference>
                  <div>
                      <div class="flex_box">
                          <div class="tips" v-for="(val, index) in scope.row[item.field_name_en]" :key="index">
                              <el-tag size="small" v-if="index < 2">
                                  {{ val }}
                              </el-tag>
                          </div>
                          <el-tag v-if="scope.row[item.field_name_en] && scope.row[item.field_name_en].length > 2"
                              size="small">...</el-tag>
                      </div>
                  </div>
                </template>
            </el-popover>
            <template v-else>
                <div class="flex_box">
                    <div class="tips" v-for="(val, index) in scope.row[item.field_name_en]" :key="index">
                        <el-tag size="small" v-if="index < 2">
                            {{ val }}
                        </el-tag>
                    </div>
                    <el-tag v-if="scope.row[item.field_name_en] && scope.row[item.field_name_en].length > 2"
                        size="small">...</el-tag>
                </div>
            </template>
            <span v-if="!scope.row[item.field_name_en] || scope.row[item.field_name_en].length == 0">--</span>
        </div>
        <div v-else-if="item.form_value === 'switch'">
            <el-switch disabled v-model="scope.row[item.field_name_en]" :active-value="1" :inactive-value="0"
                active-text="开启" inactive-text="关闭">
            </el-switch>
        </div>

        <div v-else-if="item.form_value === 'textarea'">
            <el-popover placement="top-start" width="350" trigger="hover" :content="scope.row[item.field_name_en]">
                <template #reference>
                  <div class="over-text"
                      v-if="scope.row[item.field_name_en] && scope.row[item.field_name_en].length > 11">
                      {{ scope.row[item.field_name_en] }}
                  </div>
                </template>
            </el-popover>
            <span v-if="scope.row[item.field_name_en] && scope.row[item.field_name_en].length <= 11">
                {{ scope.row[item.field_name_en] }}
            </span>
            <span v-if="!scope.row[item.field_name_en]">--</span>
        </div>
        <div v-else-if="['input_number', 'input_float', 'input_price'].includes(item.form_value)">
            {{ scope.row[item.field_name_en] }}
        </div>
        <div v-else class="flex-center">
            <span v-if="item.field_name_en == info.crudInfo.main_field_name" class="color-doc pointer"
                @click="checkRow(scope.row)">
                {{ getValue(scope.row[item.field_name_en], item.form_value) }}
                <span class="share-tag" v-if="scope.row.is_share"> 共享 </span></span>

            <!-- 多选 -->
            <div v-else-if="item.form_value == 'checkbox'">
                <div v-for="(val, index) in scope.row[item.field_name_en]" class="dictionaries-tag over-text mr10"
                    :style="{
                        color: val.color ? val.color : '#1890ff',
                        background: val.color ? getColorFn(val.color, '0.1') : getColorFn('#1890ff', '0.1')
                    }">
                    {{ val.name }}
                </div>
                <div v-if="scope.row[item.field_name_en].length == 0">--</div>
            </div>

            <!-- 关联字典颜色 -->
            <div v-else-if="
                scope.row[item.field_name_en] &&
                Object.prototype.hasOwnProperty.call(scope.row[item.field_name_en], 'color')
            " class="dictionaries-tag over-text" :style="{
                    color: scope.row[item.field_name_en].color ? scope.row[item.field_name_en].color : '#1890ff',
                    background: scope.row[item.field_name_en].color
                        ? getColorFn(scope.row[item.field_name_en].color, '0.1')
                        : getColorFn('#1890ff', '0.1')
                }">
                {{ scope.row[item.field_name_en].name }}
            </div>
            <span v-else > {{ getValue(scope.row[item.field_name_en], item.form_value)|| '--' }}</span>
        </div>
        <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
    </div>

</template>

<script>
import { getColor } from '@/utils/format'
import imageViewer from '@/components/common/imageViewer'
export default {
    props: {
        item: {
            type: Object,
            default: () => ({})
        },
        scope: {
            type: Object,
            default: () => ({})
        },
        info: {
            type: Object,
            default: () => ({})
        },
    },
    components: {
        imageViewer
    },
    data() {
        return {
            srcList: []
        }
    },
    methods: {
        checkRow(row) {
           this.$emit('checkRow', row)
        },
        getColorFn(color, opacity) {
            return getColor(color, opacity)
        },
        // 查看与下载附件
        lookViewer(url, name = '') {
            this.srcList.push(url)
            this.$refs.imageViewer.openImageViewer(url)
        },
        // 数组转成字符串
        getValue(val, type) {
            // 如果值为空字符串，直接返回 '--'
            if (val === '') return '--'

            // 处理包含 type 属性的对象
            if (val && val.type) {
                return `${val.name}(${val.type})`
            }

            // 处理 input_select 类型且值不是字符串的情况
            if (type === 'input_select' && typeof val !== 'string') {
                return val.type ? `${val.name}（${val.type}）` : val.name
            }

            // 处理数组类型的值
            if (Array.isArray(val)) {
                return val.toString()
            }

            // 其他情况直接返回值，若值为假值则返回 '--'
            return val || '--'
        },
    }
}

</script>
<style scoped lang="scss">
.img {
    cursor: pointer;
    display: block;
    width: 38px;
    height: 38px;
    margin-right: 4px;
    margin-bottom: 4px;
}

.share-tag {
    margin-left: 8px;
    display: inline-block;
    width: 36px;
    height: 22px;
    background: rgba(25, 190, 107, 0.05);
    color: #19be6b;
    border: 1px solid #19be6b;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 12px;
    border-radius: 3px 3px 3px 3px;
    line-height: 22px;
    text-align: center;
}

.flex_box {
    width: 100%;
    padding-right: 10px;
    display: flex;

    .tips {
        span {
            margin-right: 4px;
        }
    }
}

.img-box {
    display: flex;
    flex-wrap: wrap;
}

.dictionaries-tag {
    max-width: 100px;
    display: inline-block;
    margin: 0;
    box-sizing: border-box;
    height: 24px;
    padding: 0 8px;
    text-align: center;
    line-height: 24px;
    font-size: 12px;
    border-radius: 3px;
}

.mr10 {
    margin-right: 10px !important;
}

.batch-action-wrapper {
    position: absolute;
    bottom: -11px;
    left: 0;
    right: 0;
    height: 82px;
    background: rgba(255, 255, 255, 0.8);
    box-shadow: inset 0px 1px 0px 0px rgba(0, 0, 0, 0.05);

    display: flex;
    align-items: center;
    padding-left: 54px;

    .el-checkbox {
        margin-right: 10px;
    }

    .el-button {
        width: 74px;
        height: 32px;
        padding: 0;

        &:focus {
            background: #fff;
            border: 1px solid #dcdfe6;
            color: #606266;
        }

        &:hover {
            color: #1890ff;
            border-color: #badeff;
            background-color: #e8f4ff;
        }
    }
}

.table-box {
    ::v-deep .el-table__column-resize-proxy {
        border-left-color: #6fbaff;
    }

    ::v-deep .el-table--border {
        border: none;

        .el-table__cell {
            border-right: none;
        }
    }

    ::v-deep .el-table__fixed-right .el-table__fixed-body-wrapper {
        border-right: 1px solid #fff;
    }
}
</style>