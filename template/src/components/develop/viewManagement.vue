<!-- @FileDescription: 低代码-新建编辑视图管理-->
<template>
  <div class="oa-dialog">
    <el-dialog
      :close-on-click-modal="false"
      :show-close="false"
      :visible.sync="show"
      append-to-body
      top="8%"
      width="700px"
    >
      <template #title>
        <div class="header flex-between">
          <span class="title">视图管理</span>
          <span class="el-icon-close" @click="handleClose"></span>
        </div>
      </template>
      <div class="flex mb20 flex-between">
        <div>
          <el-input
            v-model="name"
            class="input"
            clearable
            placeholder="请输入视图名称"
            prefix-icon="el-icon-search"
            size="small"
            style="width: 300px"
          ></el-input>
        </div>
        <el-button size="small" type="primary" @click="addViewFn">新建视图</el-button>
      </div>

      <!-- 内容 -->
      <ul v-if="list.length > 0" class="content-title" v-loading="loading">
        <li>
          <p>顺序</p>
          <p>视图名称</p>
          <p>类型</p>
          <p>创建人</p>
          <p>操作</p>
        </li>
        <ul class="content-body">
          <draggable
            v-model="searchList"
            animation="1000"
            chosen-class="chosen"
            force-fallback="true"
            group="people"
            handle=".item-drag"
            @end="onEnd"
            @start="onStart"
          >
            <transition-group>
              <li v-for="(item, index) in searchList" :key="item.id">
                <p><i class="icon iconfont icontuodong item-drag"></i></p>
                <p class="text">{{ item.senior_title || item.title }}</p>
                <p v-if="!viewType" class="text">{{ item.senior_type == 0 ? '个人' : '公共' }}</p>

                <p v-else class="text">{{ item.is_public == 0 ? '个人' : '公共' }}</p>
                <p class="text">{{ item.admin?item.admin.name:'--' }}</p>
                <p>
                  <span class="mr10" @click.stop="editFn(item)" v-if="item.uid !== user_id">--</span>

                  <span class="iconfont iconbianji1 mr10" @click.stop="editFn(item)" v-if="item.uid == user_id"></span>

                  <span class="el-icon-delete" @click.stop="delFn(item, index)" v-if="item.uid == user_id"></span>
                </p>
              </li>
            </transition-group>
          </draggable>
        </ul>
      </ul>
      <div v-if="searchList.length == 0">
        <default-page :height="`200px`" :imgWidth="`117px`" :index="19"></default-page>
      </div>
    </el-dialog>
    <!-- 新建视图 -->
    <oa-dialog
      ref="oaDialog"
      :formConfig="formConfig"
      :formDataInit="formDataInit"
      :formRules="formRules"
      :fromData="fromData"
      @submit="submit"
    ></oa-dialog>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import defaultPage from '@/components/common/defaultPage'
import draggable from 'vuedraggable'
import oaDialog from '@/components/form-common/dialog-form'
import { saveViewSeachApi, viewSeachSortApi, delViewSeachApi, putViewSeachInfoApi } from '@/api/client'
import { crudSeniorSaveApi, crudSeniorDelApi, crudSeniorSortApi } from '@/api/develop'
import { getStorageJson } from '@/utils/storage'
export default {
  name: '',
  components: {
    oaDialog,
    draggable,
    defaultPage
  },
  props: {
    keyName: {
      type: String,
      default: ''
    },
    category: {
      // 客户管理的视图管理类型，判断是联系人/客户/订单
      type: String,
      default: ''
    },
    viewType: {
      // 值为customer是系统客户视图管理，为空值是低代码
      type: String,
      default: ''
    },
    view_search_boolean: {
      type: Number,
      default: 1
    },
    senior_search: {
      type: Array,
      default: []
    },
    list: {
      type: Array,
      default: []
    }
  },
  data() {
    return {
      show: false,
      loading: false,
      fromData: {
        width: '500px',
        title: '新建视图',
        btnText: '确定',
        labelWidth: '100px',
        type: ''
      },
      formDataInit: {
        senior_title: '',
        senior_type: '0'
      },
      user_id: getStorageJson('userInfo', {}).id,
      formConfig: [
        {
          type: 'input',
          label: '视图名称：',
          placeholder: '请输入视图名称(10个字以内)',
          key: 'senior_title'
        },
        {
          type: 'radio',
          label: '视图类型：',
          placeholder: '请选择视图类型',
          key: 'senior_type',
          options: [
            {
              value: '个人',
              label: '0'
            },
            {
              value: '公共',
              label: '1'
            }
          ]
        }
      ],
      formRules: {
        senior_title: [
          {
            required: true,
            message: '请输入视图名称',
            trigger: 'blur'
          },
          { min: 0, max: 10, message: '最多输入10个字', trigger: 'blur' }
        ],

        senior_type: [
          {
            required: true,
            message: '请选择视图类型',
            trigger: 'change'
          }
        ]
      },

      name: ''
    }
  },

  computed: {
    searchList: function () {
      let arr = []
      if (this.name !== '') {
        this.list.map((item) => {
          if (item.title.includes(this.name)) {
            arr.push(item)
          }
        })
      }

      if (this.name == '') {
        arr = this.list
      }
      return arr
    }
  },
  methods: {
    handleClose() {
      this.show = false
    },
    addViewFn() {
      this.formDataInit = {
        senior_title: '',
        senior_type: '0'
      }
      this.fromData.title = '新建视图'
      this.$refs.oaDialog.openBox()
    },
    delFn(item) {
      modalSure('确定删除此视图').then(() => {
        if (this.viewType === 'customer') {
          const viewData = getStorageJson('viewData')
          delViewSeachApi(item.id).then((res) => {
            if (res.status == 200) {
              for (let key in viewData) {
                if (this.category == key) {
                  this.$delete(viewData, this.category)
                }
              }
              localStorage.setItem('viewData', JSON.stringify(viewData))
              this.$emit('getViewList', 1)
            }
          })
        } else {
          crudSeniorDelApi(this.keyName, item.id).then((res) => {
            if (res.status == 200) {
              this.$emit('getViewList')
            }
          })
        }
      })
    },
    editFn(item) {
      this.formDataInit = {
        senior_title: item.senior_title || item.title,
        senior_type: item.senior_type || item.is_public + '',
        id: item.id,
        sort: item.sort,
        content: item.content,
        search_boolean: item.view_search_boolean,
        senior_search: item.senior_search || item.content
      }
      this.fromData.title = '编辑视图'
      this.$refs.oaDialog.openBox()
    },
    onStart() {},
    onEnd() {
      let obj = {
        id: []
      }
      this.searchList.map((item) => {
        obj.id.push(item.id)
      })
      if (this.viewType === 'customer') {
        viewSeachSortApi(obj).then((res) => {
          if (res.status == 200) {
            this.$emit('getViewList')
          }
        })
      } else {
        crudSeniorSortApi(this.keyName, obj).then((res) => {
          this.$emit('getViewList')
        })
      }
    },

    submit(data) {
      this.loading = true
      if (this.viewType === 'customer') {
        let obj = {
          title: data.senior_title,
          category: this.category,
          is_public: data.senior_type,
          sort: data.sort || 0,
          content: data.content
        }
        if (data.id) {
          putViewSeachInfoApi(data.id, obj).then((res) => {
            if (res.status == 200) {
              this.$emit('getViewList')
              this.$refs.oaDialog.handleClose()
            }
            this.loading = false
          })
        } else {
          saveViewSeachApi(obj).then((res) => {
            if (res.status == 200) {
              this.$emit('getViewList')
              this.$refs.oaDialog.handleClose()
            }
            this.loading = false
          })
        }
      } else {
        if (!data.id) {
          data.search_boolean = this.view_search_boolean
          data.senior_search = this.senior_search
        }
        data.search_boolean = this.view_search_boolean
        data.senior_search = this.senior_search
        if (data.senior_type == 2) {
          data.senior_type = 0
        }
        crudSeniorSaveApi(this.keyName, data).then((res) => {
          if (res.status == 200) {
            this.$emit('getViewList')
            this.$refs.oaDialog.handleClose()
          }
          this.loading = false
        })
      }
      this.loading = false
    },
    openBox() {
      this.show = true
    }
  }
}
</script>
<style lang="scss" scoped>
.oa-dialog {
  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #000;

    .el-icon-close {
      color: #c0c4cc;
      font-weight: 500;
      font-size: 14px;
    }
  }

  .content {
    max-height: calc(100vh - 420px);
    overflow-y: auto;
  }
  .content::-webkit-scrollbar {
    height: 0;
    width: 0;
  }
  .content:first-child {
    padding: 0 20px;
  }

  .vertical {
    display: flex;
    flex-direction: column;
  }
  .add-type {
    display: flex;
    justify-content: flex-start;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
  }
  ::v-deep .el-dialog {
    border-radius: 6px;
  }

  ::v-deep .el-button--medium {
    padding: 10px 15px;
  }
}

ul {
  list-style: none;
  padding: 0;

  li {
    padding: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;

    p {
      width: calc(100% / 5);
      padding-left: 20px;

      &:first-of-type {
        padding-left: 0;
      }
      &:nth-of-type(2) {
        width: calc(100% / 2);
      }
      &:last-of-type {
        width: calc(100% / 8);
      }
    }
  }
}
.iconbianji1 {
  font-size: 14px;
}
.el-icon-delete {
  font-size: 14px;
}
.content-title {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #9e9e9e;
  max-height: 400px;
  overflow: auto;
}
.el-icon-delete {
  margin-left: 6px;
  cursor: pointer;
}
.iconfont {
  cursor: pointer;
}
.text {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
}
</style>
