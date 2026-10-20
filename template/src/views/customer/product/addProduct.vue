<template>
  <BaseContainer class="product-form none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="id > 0 ? '编辑产品页面' : '新增产品页面'" @back="backFn" />

    <div class="product-form__body">
      <div class="main">
        <oaForm :form-info="fromInfo" ref="oaForm" :isShowFooter="false" @submitOk="submitOk"></oaForm>
        <div class="from-item-title mb20">
          <span>规格售价</span>
        </div>

        <el-form ref="form" :label-width="specLabelWidth">
          <el-form-item label="规格：">
            <el-radio-group v-model="form.spec_type">
              <el-radio :label="0" :value="0" class="radio">单规格</el-radio>
              <el-radio :label="1" :value="1">多规格</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="商品规格：" required v-if="form.spec_type == 1">
            <div class="specifications">
              <draggable
                group="specifications"
                :disabled="attrs.length < 2"
                :list="attrs"
                handle=".move-icon"
                @end="onMoveSpec"
                animation="300"
                :item-key="$draggableKey"
              >
                <template #item="{ element: item, index }">
                  <div
                    class="specifications-item active"
                    @click="changeCurrentIndex(index)"
                  >
                    <div class="move-icon">
                      <span class="iconfont icontuodong"></span>
                    </div>
                    <i class="del el-icon-error" @click="handleRemoveRole(index, item.value)"></i>
                    <div class="specifications-item-box">
                      <div class="lineBox"></div>
                      <div class="specifications-item-name mb18">
                        <el-input
                          v-model="item.value"
                          placeholder="规格名称"
                          @change="attrChangeValue(index, item.value)"
                          @focus="handleFocus(item.value, item)"
                          class="specifications-item-name-input"
                          maxlength="30"
                          show-word-limit
                        ></el-input>
                      </div>
                      <div class="rulesBox">
                        <draggable
                          class="item"
                          :list="item.detail"
                          :disabled="item.detail.length < 2"
                          handle=".drag"
                          @end="onMoveSpec"
                          :item-key="$draggableKey"
                        >
                          <template #item="{ element: det, index: indexn }">
                            <div class="mr10 spec drag">
                              <i class="el-icon-error" @click="handleRemove2(item.detail, indexn, det.value)"></i>
                              <el-input
                                style="width: 120px"
                                v-model="det.value"
                                placeholder="规格值"
                                @change="attrDetailChangeValue(det.value, index)"
                                @focus="handleFocus(det.value)"
                                maxlength="30"
                                @blur="handleBlur()"
                              >
                                <template #prefix>
                                  <span class="iconfont icontuodong"></span>
                                </template>
                              </el-input>
                              <div class="img-popover" v-if="item.add_pic">
                                <div class="popper-arrow"></div>
                                <div class="popper" @click="handleSelImg(det, index, indexn)">
                                  <img class="img" v-if="det.pic" :src="det.pic" />
                                  <i v-else class="el-icon-plus"></i>
                                </div>
                                <i
                                  v-if="det.pic"
                                  class="img-del el-icon-error"
                                  @click="handleRemoveImg(det, index, indexn)"
                                ></i>
                              </div>
                            </div>
                          </template>
                          <template #footer>
                            <el-popover
                              :ref="'popoverRef_' + index"
                              v-model:visible="specPopoverOpen[index]"
                              placement="bottom"
                              width="210"
                              trigger="click"
                              @after-enter="handleShowPop(index)"
                            >
                              <el-input
                                style="min-width: 80px; width: 210"
                                :ref="'inputRef_' + index"
                                placeholder="请输入规格值后回车"
                                v-model="formDynamic.attrsVal"
                                @keyup.enter="createAttr(formDynamic.attrsVal, index)"
                                @blur="createAttr(formDynamic.attrsVal, index)"
                                maxlength="30"
                                show-word-limit
                              >
                              </el-input>
                              <template #reference>
                                <div class="addfont">添加规格值</div>
                              </template>
                            </el-popover>
                          </template>
                        </draggable>
                      </div>
                    </div>
                  </div>
                </template>
              </draggable>
              <el-button
                v-if="attrs.length < 4 && form.spec_type == 1"
                type="primary" link
                @click="handleAddRole()"
                >添加新规格</el-button
              >
            </div>
          </el-form-item>

          <!-- 单规格列表 -->
          <el-form-item v-if="form.spec_type === 0">
            <el-table :data="OneattrValue" class="tabNumWidth">
              <el-table-column label="图片" min-width="80">
                <template #default="scope">
                  <div class="upLoadPicBox specPictrue" @click="modalPicTap('1', 'dan')">
                    <div v-if="scope.row.image" class="pictrue tabPic">
                      <img :src="scope.row.image" />
                    </div>
                    <div v-else class="upLoad tabPic">
                      <i class="el-icon-camera cameraIconfont" />
                    </div>
                  </div>
                </template>
              </el-table-column>

              <el-table-column
                v-for="(item, iii) in attrValue"
                :key="iii"
                :label="formThead[iii] && formThead[iii].title"
                min-width="110"
              >
                <template #default="scope">
                  <div>
                    <el-input
                      v-if="formThead[iii].type === 'text'"
                      v-model="scope.row[iii]"
                      type="text"
                      class="priceBox"
                    />

                    <el-input-number
                      v-else
                      v-model="scope.row[iii]"
                      :min="0"
                      class="priceBox"
                      controls-position="right"
                    />
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </el-form-item>

          <!-- 多规格表格-->
          <el-form-item v-if="form.spec_type == 1" class="labeltop" label="规格列表：">
            <el-table
              :data="ManyAttrValue"
              style="width: 100%"
              :cell-class-name="tableCellClassName"
              :span-method="objectSpanMethod"
              :key="tableKey"
            >
              <el-table-column
                v-for="(item, index) in header"
                :key="index"
                :label="item.title"
                :min-width="item.minWidth || '100'"
                :fixed="item.fixed"
              >
                <template #default="scope">
                  <template v-if="item.key">
                    <template v-if="scope.$index == 0">
                      <div v-if="attrs.length && attrs[scope.column.index] && ManyAttrValue.length">
                        <el-select v-model="oneFormBatch[0][item.title]" :placeholder="`请选择`" clearable>
                          <el-option
                            v-for="val in attrs[scope.column.index].detail"
                            :key="val.value"
                            :label="val.value"
                            :value="val.value"
                          >
                          </el-option>
                        </el-select>
                      </div>
                    </template>
                    <div v-else>
                      <span>{{ scope.row.detail[item.key] }}</span>
                    </div>
                  </template>
                  <template v-if="item.slot === 'image'">
                    <div
                      class="upLoadPicBox specPictrue"
                      @click.stop="modalPicTap('1', scope.$index == 0 ? 'pi' : 'duo', scope.$index)"
                    >
                      <div class="upLoad tabPic" v-if="scope.row[item.slot]">
                        <img v-lazy="scope.row[item.slot]" />
                        <i class="el-icon-error btndel btnclose" @click.stop="scope.row[item.slot] = ''" />
                      </div>
                      <div class="upLoad tabPic" v-else>
                        <i class="el-icon-camera cameraIconfont"></i>
                      </div>
                    </div>
                  </template>
                  <template v-if="item.type == 'text'">
                    <el-input v-model="scope.row[item.slot]"></el-input>
                  </template>
                  <template v-if="item.type == 'num'">
                    <el-input-number
                      :controls="false"
                      v-model="scope.row[item.slot]"
                      :min="0"
                      :max="9999999999"
                      class="priceBox"
                    ></el-input-number>
                  </template>

                  <template v-else-if="item.slot === 'action' && scope.$index == 0">
                    <el-button type="primary" link @click="batchAdd">批量修改</el-button>
                    <el-button type="primary" link @click="batchDel">清空</el-button>
                  </template>
                </template>
              </el-table-column>
            </el-table>
          </el-form-item>
        </el-form>
      </div>
    </div>
    <!-- 底部按钮 -->
    <FormPageFooter>
      <el-button @click="backFn">取消</el-button>
      <el-button type="primary" @click="submit()">保存</el-button>
    </FormPageFooter>
  </BaseContainer>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { roterPre } from '@/settings.ts'
import vuedraggable from 'vuedraggable'
import { productCreateApi, productSaveApi, productInfoApi, putProductApi } from '@/api/client.ts'
import BaseContainer from '@/components/BaseContainer/index.vue'
import FormPageHeader from '@/components/FormPageHeader/index.vue'
import FormPageFooter from '@/components/FormPageFooter/index.vue'
import { modalUpload } from '@/components/uploadPicture/uploadFrom/index.ts'

// 「规格售价」表单自身最长 label（规格列表）的字符数，作为宽度下限，
// 避免上方动态表单尚未返回时 label 被压得过窄
const SPEC_LABEL_MIN_CHAR_LENGTH = 4

export default {
  components: {
    draggable: vuedraggable,
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm')),
    BaseContainer,
    FormPageHeader,
    FormPageFooter
  },
  data() {
    const attrValue = [
      {
        image: '',
        price: null,
        cost: null,
        bar_code: '',
        product_bar_code: ''
      }
    ]

    return {
      form: { spec_type: 0, attr: [] },
      header: [],
      fromInfo: [],
      // 规格数据
      formDynamic: {
        attrsName: '',
        attrsVal: ''
      },
      specPopoverOpen: {},
      tableKey: 0,
      attrs: [],
      oneFormBatch: [
        {
          image: '',
          price: null,
          cost: null,
          bar_code: '',
          product_bar_code: ''
        }
      ],
      fromData: {},
      OneattrValue: [Object.assign({}, attrValue[0])], // 单规格
      ManyAttrValue: [Object.assign({}, attrValue[0])], // 多规格
      attrVal: {
        price: null,
        cost: null,
        bar_code: null,
        product_bar_code: null
      },
      formThead: {
        price: {
          title: '产品售价(元)'
        },
        cost: {
          title: '产品成本价（元）'
        },
        bar_code: {
          title: '产品规格编码',
          type: 'text'
        },
        product_bar_code: {
          title: '商品条码',
          type: 'text'
        }
      },
      id: 0,
      GoodsTableHead: [
        {
          title: '图片',
          slot: 'image',
          minWidth: '80px'
        },
        {
          title: '产品售价(元)',
          slot: 'price',
          type: 'num',
          minWidth: '120px'
        },
        {
          title: '产品成本(元)',
          slot: 'cost',
          type: 'num',
          minWidth: 120
        },

        {
          title: '产品规格编码',
          slot: 'bar_code',
          type: 'text',
          minWidth: '120px'
        },
        {
          title: '商品条码',
          slot: 'product_bar_code',
          type: 'text',
          minWidth: '120px'
        },
        {
          title: '操作',
          slot: 'action',
          minWidth: '120px',
          fixed: 'right'
        }
      ]
    }
  },
  watch: {
    attrs: {
      handler: function (val) {
        if (this.form.spec_type === 1) this.watCh(val)
      },
      immediate: false,
      deep: true
    }
  },
  computed: {
    // 与 oaForm 的 formLabelWidth 采用同一套算法：按最长 label 字符数换算宽度，
    // 保证「规格售价」表单与上方产品信息动态表单左对齐
    specLabelWidth() {
      const fields = this.fromInfo.flatMap((group) => group.data || [])
      const maxLabelCharLength = fields.reduce((max, field) => {
        const length = (field.key_name?.length || 0) + (field.required ? 1 : 0)
        return Math.max(max, length)
      }, SPEC_LABEL_MIN_CHAR_LENGTH)

      return `${(maxLabelCharLength + 1) * 14}px`
    },
    attrValue() {
      const obj = Object.assign({}, this.attrVal)
      return obj
    },
    specValue() {
      const obj = Object.assign({}, this.specVal)
      return obj
    }
  },
  created() {
    if (this.$route.query.id) {
      this.id = this.$route.query.id
    }
  },
  mounted() {
    if (this.id > 0) {
      this.getDetails()
    } else {
      this.generateHeader(this.attrs)
      this.getFromInfo()
    }
  },
  methods: {
    // 获取新增表单
    getFromInfo() {
      productCreateApi().then((res) => {
        this.fromInfo = res.data
      })
    },
    async getDetails() {
      const result = await productInfoApi(this.id)
      this.fromInfo = result.data.list
      this.form.spec_type = result.data.spec_type
      if (result.data.spec_type == 1) {
        this.attrs = result.data.attr
        this.generateHeader(this.attrs)
        this.ManyAttrValue = [...this.oneFormBatch, ...result.data.attrValue]
      } else {
        this.OneattrValue = result.data.attrValue
      }
    },

    // 规格名称改变
    attrChangeValue(i, val) {
      this.generateHeader(this.attrs)
      this.generateAttr(this.attrs)
    },

    handleShowPop(index) {
      const input = this.$refs['inputRef_' + index]
      const target = Array.isArray(input) ? input[0] : input
      target?.focus()
    },
    watCh(val) {
      const tmp = {}
      const tmpTab = {}
      val.forEach((o, i) => {
        tmp['value' + i] = { title: o.value }
        tmpTab['value' + i] = o.detail
      })

      this.formThead = Object.assign({}, this.formThead, tmp)
    },
    // 删除属性
    handleRemove2(item, index, val) {
      item.splice(index, 1)
      for (let i = 0; i < this.ManyAttrValue.length; i++) {
        let item = this.ManyAttrValue[i]
        if (item.attr_arr && item.attr_arr.includes(val)) {
          this.ManyAttrValue.splice(i, 1)
          i--
        }
      }
    },
    // 删除规格
    handleRemoveRole(index) {
      this.attrs.splice(index, 1)
      if (!this.attrs.length) {
        this.header = []
        this.ManyAttrValue = []
      } else {
        this.generateAttr(this.attrs)
      }
    },
    handleSelImg(item, index, indexn) {
      let that = this
      modalUpload(function (img) {
        item.pic = img.att_dir
        that.changeSpecImg([item.value], img.att_dir, index, indexn)
      })
    },
    // 生成列表 行 列 数据
    tableCellClassName({ row, column, rowIndex, columnIndex }) {
      //注意这里是解构
      //利用单元格的 className 的回调方法，给行列索引赋值
      row.index = rowIndex || ''
      column.index = columnIndex
    },
    changeSpecImg(arr, img, index, indexn) {
      // 判断是否存在规格图
      let isHas = false
      for (let i = 1; i < this.ManyAttrValue.length; i++) {
        let item = this.ManyAttrValue[i]
        if (item.image && this.isSubset(item.attr_arr, arr)) {
          isHas = true
          break
        }
      }
      if (isHas) {
        this.$confirm('可以同步修改下方该规格图片，确定要替换吗？', '提示', {
          confirmButtonText: '替换',
          cancelButtonText: '暂不',
          type: 'warning'
        })
          .then(() => {
            for (let val of this.ManyAttrValue) {
              if (this.isSubset(val.attr_arr, arr)) {
                val.image = img
              }
            }

            this.generateAttr(this.attrs)
          })
          .catch(() => {})
      } else {
        for (let val of this.ManyAttrValue) {
          if (this.isSubset(val.attr_arr, arr)) {
            val.image = img
          }
        }

        this.generateAttr(this.attrs)
      }
    },
    // 规格图片添加开关
    addPic(e, i) {
      if (e) {
        this.attrs.map((item, ii) => {
          if (ii !== i) {
            item.add_pic = 0
          }
        })
        this.canSel = false
      } else {
        this.canSel = true
      }
    },

    // 点击商品图
    modalPicTap(tit, num, i) {
      const _this = this
      const attr = []
      modalUpload(function (img) {
        if (tit === '1' && !num) {
          _this.form.image = img.att_dir
          _this.OneattrValue[0].image = img.att_dir
        }
        if (tit === '2' && !num) {
          img.map((item) => {
            attr.push(item.attachment_src)
            _this.form.slider_image.push(item)
            if (_this.form.slider_image.length > 10) {
              _this.form.slider_image.length = 10
            }
          })
        }
        if (tit === '1' && num === 'dan') {
          _this.OneattrValue[0].image = img.att_dir
        }
        if (tit === '1' && num === 'duo') {
          _this.ManyAttrValue[i].image = img.att_dir
        }
        if (tit === '1' && num === 'pi') {
          _this.oneFormBatch[0].image = img.att_dir
        }
      }, tit)
    },

    attrDetailChangeValue(val, i) {
      if (this.ManyAttrValue.length) {
        let key = this.attrs[i].value
        this.ManyAttrValue.map((item, i) => {
          if (i > 0) {
            if (Object.keys(item.detail).includes(key) && item.detail[key] === this.changeAttrValue) {
              item.detail[key] = val
              let index = item.attr_arr.findIndex((item) => item === this.changeAttrValue)
              item.attr_arr[index] = val
            }
          }
        })
        this.changeAttrValue = val
      } else {
        this.generateAttr(this.attrs, 1)
      }
    },

    changeCurrentIndex(i) {
      this.currentIndex = i
    },
    // 添加规格
    handleAddRole() {
      let data = {
        value: this.formDynamic.attrsName,
        add_pic: 0,
        detail: []
      }
      this.attrs.push(data)
    },
    // 规格拖拽排序后
    onMoveSpec() {
      this.generateAttr(this.attrs)
    },

    handleFocus(val, item) {
      this.changeAttrValue = val
    },
    handleBlur() {
      this.changeAttrValue = ''
    },

    // 合并单元格
    objectSpanMethod({ row, column, rowIndex, columnIndex }) {
      if (columnIndex === 0 && rowIndex > 0) {
        let lable = column.label
        //这里判断第几列需要合并
        const tagFamily = this.ManyAttrValue[rowIndex].detail[lable]
        const index = this.ManyAttrValue.findIndex((item, index) => {
          if (index > 0) return item.detail[lable] == tagFamily
        })
        if (rowIndex == index) {
          let len = 1
          for (let i = index + 1; i < this.ManyAttrValue.length; i++) {
            if (this.ManyAttrValue[i].detail[lable] !== tagFamily) {
              break
            }
            len++
          }
          return {
            rowspan: len,
            colspan: 1
          }
        } else {
          return {
            rowspan: 0,
            colspan: 0
          }
        }
      }
    },

    // 添加属性
    createAttr(num, idx) {
      if (num) {
        // 判断是否存在同样熟悉
        const isExist = this.attrs[idx].detail.some((item) => item.value === num)
        if (isExist) {
          this.$message.error('规格值已存在')
          return
        }
        this.attrs[idx].detail.push({ value: num, image: '' })
        this.form.attr = this.attrs
        if (this.ManyAttrValue.length) {
          this.addOneAttr(this.attrs[idx].value, num)
        } else {
          this.generateAttr(this.attrs)
        }
        this.specPopoverOpen[idx] = false
        this.clearAttr()
        // 关掉后再打开，方便连续添加下一个规格值
        setTimeout(() => {
          this.specPopoverOpen[idx] = true
        }, 20)
      } else {
        this.specPopoverOpen[idx] = false
      }
      // 监听多规格值变化，在新增时候默认选中规格要自动默认第一个数据
      let exists = this.ManyAttrValue.some((item) => item.is_default_select == 0)
      if (exists) {
        this.ManyAttrValue[1].is_default_select = 1
      }
    },

    clearAttr() {
      this.formDynamic.attrsName = ''
      this.formDynamic.attrsVal = ''
    },

    // 新增一条属性
    addOneAttr(val, val2) {
      this.generateAttr(this.attrs, val2)
    },
    backFn() {
      this.$router.push({ path: `${roterPre}/customer/product/index` })
    },
    // 提交
    submit() {
      this.$refs.oaForm.handleConfirm()
    },
    submitOk(data) {
      this.form.attr = this.attrs
      if (this.form.spec_type == 0) {
        this.form.attrValue = this.OneattrValue
      } else {
        if (this.ManyAttrValue.length < 2) return this.$message.warning('商品规格-规格数量最少1个')

        let newData = JSON.parse(JSON.stringify(this.ManyAttrValue))

        newData.shift()
        const emptyItem = newData.find((item) => {
          return item.detail?.[item.key] === ''
        })

        if (emptyItem) {
          this.$message.error(`规格值不能为空，请填写`)
          return false
        }
        this.form.attrValue = newData
      }
      let obj = { ...data, ...this.form }
      if (this.id > 0) {
        putProductApi(this.id, obj).then((res) => {
          if (res.status == 200) {
            this.$router.push({ path: `${roterPre}/customer/product/index` })
          }
        })
      } else {
        productSaveApi(obj).then((res) => {
          if (res.status == 200) {
            this.$router.push({ path: `${roterPre}/customer/product/index` })
          }
        })
      }
    },
    // 立即生成
    generateAttr(data, val) {
      // 判断该段Js执行时间
      this.generateHeader(data)

      const combinations = this.generateCombinations(data)

      let rows = combinations.map((combination) => {
        const row = {
          attr_arr: combination,
          detail: {},
          title: '',
          price: 0,
          image: '',
          bar_code: '',
          product_bar_code: '',
          cost: 0
        }
        for (let i = 0; i < combination.length; i++) {
          const value = combination[i]
          row[data[i].value] = value
          row.title = data[i].value
          row.key = data[i].value
          row.detail[data[i].value] = value
          // 如果manyFormValidate中存在该属性值，则赋值
          for (let k = 0; k < this.ManyAttrValue.length; k++) {
            const manyItem = this.ManyAttrValue[k]
            // 对比两个数组是否完全相等
            if (k > 0 && manyItem.attr_arr && this.arraysEqual(manyItem.attr_arr, combination)) {
              Object.assign(row, {
                price: manyItem.price,
                cost: manyItem.cost,
                bar_code: manyItem.bar_code,
                product_bar_code: manyItem.product_bar_code,
                ot_price: manyItem.ot_price,
                image: manyItem.image
              })
            }
          }
        }

        return row
      })

      this.$nextTick(() => {
        // rows数组第一项 新增默认数据 oneFormBatch
        this.ManyAttrValue = [...this.oneFormBatch, ...rows]
      })
    },

    arraysEqual(arr1, arr2) {
      // 如果两个数组的长度不同，直接返回 false
      if (arr1.length !== arr2.length) {
        return false
      }

      // 将两个数组分别排序
      const sortedArr1 = arr1.slice().sort()
      const sortedArr2 = arr2.slice().sort()

      // 比较排序后的数组
      for (let i = 0; i < sortedArr1.length; i++) {
        if (sortedArr1[i] !== sortedArr2[i]) {
          return false
        }
      }

      return true
    },

    // 生成规格组合
    generateCombinations(arr, prefix = []) {
      if (arr.length === 0) {
        return [prefix]
      }
      const [first, ...rest] = arr
      return first.detail.flatMap((detail) => this.generateCombinations(rest, [...prefix, detail.value]))
    },

    // 批量设置
    batchAdd() {
      let arr = []
      let obj = {
        image: '',
        price: '',
        cost: '',
        bar_code: '',
        product_bar_code: ''
      }
      for (let val of this.attrs) {
        if (this.oneFormBatch[0][val.value]) {
          arr.push(this.oneFormBatch[0][val.value])
        }
      }

      if (arr.length > 0) {
        for (let val of this.ManyAttrValue) {
          if (this.isSubset(val.attr_arr, arr)) {
            if (this.oneFormBatch[0].image) {
              val.image = this.oneFormBatch[0].image
            }
            if (this.oneFormBatch[0].price != undefined && this.oneFormBatch[0].price != '') {
              val.price = this.oneFormBatch[0].price
            }
            if (this.oneFormBatch[0].cost != undefined && this.oneFormBatch[0].cost != '') {
              val.cost = this.oneFormBatch[0].cost
            }
            if (this.oneFormBatch[0].bar_code != undefined && this.oneFormBatch[0].bar_code != '') {
              val.bar_code = this.oneFormBatch[0].bar_code
            }
            if (this.oneFormBatch[0].product_bar_code != undefined && this.oneFormBatch[0].product_bar_code != '') {
              val.product_bar_code = this.oneFormBatch[0].product_bar_code
            }
          }
        }
      } else {
        this.ManyAttrValue.forEach((item) => {
          for (let val in obj) {
            if (this.ManyAttrValue[0][val]) {
              item[val] = this.ManyAttrValue[0][val]
            }
          }
        })
      }
    },
    isSubset(arr1, arr2) {
      // 将数组转换为 Set，以便进行高效的包含检查
      const set1 = new Set(arr1)
      const set2 = new Set(arr2)
      // 检查 set2 中的每个元素是否都在 set1 中
      for (let elem of set2) {
        if (!set1.has(elem)) {
          return false
        }
      }
      return true
    },

    // 清除属性
    batchDel() {
      let obj = {
        image: '',
        price: '',
        cost: '',
        bar_code: '',
        product_bar_code: ''
      }
      this.oneFormBatch = [obj]
      this.ManyAttrValue[0] = obj
      this.tableKey += 1
    },

    // 根据不同商品类型动态生成商品规格表头
    generateHeader(data) {
      let array = []
      data.forEach((item) => {
        if (item.detail.length === 0) {
          return
          // return this.$message.error(`请添加${item.value}的规格值`)
        } else {
          item.detail.forEach((item2) => {
            if (item2.value == '') {
              this.$message.error(`请添加${item.value}的规格值`)
              return false
            }
          })
          array.push({
            title: item.value,
            key: item.value,
            minWidth: 140,
            fixed: 'left'
          })
        }
      })
      let arr = []
      arr = [...array, ...this.GoodsTableHead]
      this.header = arr
      this.tableKey += 1
    }
  }
}
</script>
<style lang="scss" scoped>
.product-form__body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 20px;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
}

.main {
  width: 65%;

  :deep(.el-form--inline .el-form-item) {
    display: flex;
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  :deep(.el-input__inner) {
    text-align: left;
  }

  :deep(.el-date-editor) {
    width: 100%;
  }

  // 产品信息动态表单：控件统一 460px（产品简介富文本编辑器除外）
  :deep(.inline-edit-item) {
    .el-input,
    .el-textarea,
    .el-select,
    .el-cascader,
    .el-input-number,
    .el-date-editor {
      width: 460px !important;
    }
  }

  .from-item-title {
    border-left: 3px solid #1890ff;
    margin-bottom: 20px;

    span {
      padding-left: 10px;
      font-weight: bold;
      font-size: 14px;
    }
  }

  .drag {
    cursor: move;
  }

  .reservation-times-box {
    margin-top: 10px;
    padding: 10px 20px;
    width: 100%;
    background-color: #fafafa;
    border-radius: 10px;

    :deep(.el-checkbox__label) {
      font-size: 13px;
    }
  }

  .acea-row {
    display: flex;
    flex-wrap: wrap;
    margin-top: 14px;
  }

  .flex-1 {
    flex: 1;
  }

  .customize-time {
    display: flex;
    flex-wrap: wrap;

    .relative {
      position: relative;

      &:hover .el-icon-error {
        visibility: visible;
      }
    }

    .el-icon-error {
      visibility: hidden;
      cursor: pointer;
      font-size: 15px;
      color: #999999;
      position: absolute;
      top: -5px;
      right: 6px;
    }
  }

  // 多规格设置
  .specifications {
    .specifications-item:hover {
      background-color: #e8f4ff;
    }

    .specifications-item:hover .del {
      display: block;
    }

    .specifications-item:last-child {
      margin-bottom: 14px;
    }

    .specifications-item {
      position: relative;
      display: flex;
      align-items: center;
      padding: 20px 15px;
      transition: all 0.1s;
      background-color: #fafafa;
      margin-bottom: 10px;
      border-radius: 4px;

      .del {
        display: none;
        position: absolute;
        right: 15px;
        top: 15px;
        font-size: 22px;
        color: #1890ff;
        cursor: pointer;
        z-index: 9;
      }

      .specifications-item-box {
        position: relative;

        .lineBox {
          position: absolute;
          left: 13px;
          top: 30px;
          width: 27px;
          height: 47px;
          border-bottom-left-radius: 6px;
          border-left: 1px solid #dcdfe6;
          border-bottom: 1px solid #dcdfe6;
        }

        .specifications-item-name {
          .el-icon-info {
            color: #1890ff;
            font-size: 12px;
            margin-left: 5px;
          }
        }

        .specifications-item-name-input {
          width: 200px;
        }
      }
    }
  }

  .spec {
    display: block;
    margin: 5px 0;
    position: relative;

    .img-popover {
      cursor: pointer;
      width: 76px;
      height: 76px;
      padding: 6px;
      margin-top: 12px;
      background-color: #fff;
      position: relative;
      border: 1px solid #dcdfe6;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;

      &:hover .img-del {
        display: block;
      }

      .img-del {
        display: none;
        position: absolute;
        right: 3px;
        top: 3px;
        font-size: 16px;
        color: #1890ff;
        cursor: pointer;
        z-index: 9;
      }

      .popper {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
      }

      .popper-arrow,
      .popper-arrow:after {
        position: absolute;
        display: block;
        width: 0;
        height: 0;
        border-color: transparent;
        border-style: solid;
      }

      .popper-arrow {
        top: -13px;
        border-top-width: 0;
        border-bottom-color: #dcdfe6;
        border-width: 6px;
        filter: drop-shadow(0 2px 12px rgba(0, 0, 0, 0.03));

        &::after {
          top: -5px;
          margin-left: -6px;
          border-top-width: 0;
          border-bottom-color: #fff;
          content: ' ';
          border-width: 6px;
        }
      }
    }

    .el-icon-error {
      position: absolute;
      display: none;
      right: -3px;
      top: -3px;
      z-index: 9;
      color: #1890ff;
    }
  }

  .priceBox {
    width: 100%;
  }

  .tabPic {
    width: 40px !important;
    height: 40px !important;

    img {
      width: 100%;
      height: 100%;
    }
  }

  .pictrue {
    width: 60px;
    height: 60px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    margin-right: 15px;
    display: inline-block;
    position: relative;
    cursor: pointer;

    img {
      width: 100%;
      height: 100%;
    }

    .btndel {
      position: absolute;
      z-index: 1;
      width: 20px !important;
      height: 20px !important;
      left: 46px;
      top: -4px;
    }
  }

  .spec:hover {
    .el-icon-error {
      display: block;
      z-index: 999;
      cursor: pointer;
    }
  }

  .move-icon {
    width: 30px;
    cursor: move;
    margin-right: 10px;

    .icontuodong {
      color: #606266;
    }
  }

  .btndel {
    position: absolute;
    z-index: 1;
    width: 20px !important;
    height: 20px !important;
    left: 46px;
    top: -4px;

    &.btnclose {
      left: auto;
      right: 0;
      top: 0;
    }
  }

  .addfont {
    display: inline-block;
    font-size: 12px;
    font-weight: 400;
    color: #1890ff;
    margin-left: 30px;
    cursor: pointer;
  }

  .upLoadPicBox {
    position: relative;

    &.specPictrue {
      display: flex;
      align-items: center;
    }

    .upLoad {
      flex-direction: column;
      line-height: 20px;
    }

    span {
      font-size: 10px;
    }
  }

  .rulesBox {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin-left: 40px;

    .item {
      display: flex;
      flex-wrap: wrap;
    }

    .addfont {
      margin-top: 5px;
      margin-left: 0px;
      width: 100px;
    }

    :deep(.el-popover) {
      border: none;
      box-shadow: none;
      padding: 0;
      margin-top: 5px;
      line-height: 1.5;
    }
  }
  .mb18 {
    margin-bottom: 18px;
  }

  .ml30 {
    margin-left: 30px;
  }

  .mr10 {
    margin-right: 10px;
  }
}
</style>
