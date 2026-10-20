<template>
  <!-- 实体管理页面 -->
  <div class="divBox">
    <el-card class="normal-page">
        <formBox :title="`应用管理`" :total="total" :search="search" :btnText="`新建应用`" :isViewSearch="false"
          :sortSearch="false" @confirmData="confirmData" @addDataFn="addDataFn"></formBox>
        <div id="content-box " class="mt10" v-loading="loading">
          <default-page v-if="listData.length == 0" :index="14" :min-height="520" />
          <div class="list" id="listBox" ref="container">
            <div class="item" id="item" v-for="(item, index) in listData" :key="index" @click="handleClick(item)">
           
                <div class="item-header">
                <div class="icon-box">
                  <span v-if="item.menu&&item.menu.icon"  :class="getIconCss(item.menu.icon)"></span>
                </div>
                <div class="flex-column entry-name">
                  <div class="title over-text">{{ item.name }}</div>
                </div>
                 <span class="count">{{ item.crud_count }}个实体</span>
                </div>
               
            
              <div class="over-text2 content">
                {{ item.info ||'--'}}
              </div>
              <div class="operate flex flex-center">
                <span @click.stop="handleEdit(item)"> 编辑</span>
                <el-divider direction="vertical"></el-divider>
                <span @click.stop="deleteFn(item.id)"> 删除</span>
              </div>
            </div>
          </div>
          <el-pagination :current-page="where.page" :page-size="where.limit" :total="total" class="page-fixed"
            layout="total, prev, pager, next, jumper" @current-change="pageChange" />
        </div>
    </el-card>

    <applicatDialog v-if="applicatIsShow" ref="applicatDialog" @getList="getCrudAllType"></applicatDialog>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public.ts'
import formBox from '@/components/common/oaFromBox'
import defaultPage from '@/components/common/defaultPage'
import applicatDialog from './components/applicatDialog'
import { getcrudCateListApi ,delcrudCateApi} from '@/api/develop.ts'
import { roterPre } from '@/settings.ts'
export default {
  name: 'CrmebOaEntIndex',
  components: {
    applicatDialog,
    formBox,defaultPage
  },
  data() {
    return {
      where: {
        name: '',
        page: 1,
        limit: 10
      },
      applicatIsShow: false,
      search: [
        {
          form_value: 'input',
          field_name: '应用名称',
          field_name_en: 'name'
        }
      ],
      id: 0,
      total: 0,
      loading: false,
      listData: [],
      activeName: 0
    }
  },

  created() {
    if (this.$route.query.isShow == 1) {
      this.$router.replace({
        path: `${roterPre}/develop/crud/entityList`,
        query: { cate_id: this.$route.query.cate_id }
      })
      return
    }
    this.getCrudAllType()
  },

  methods: {
    // 新建应用
    addDataFn() {
      this.applicatIsShow = true
      setTimeout(() => {
        this.$refs.applicatDialog.openBox()
      }, 300)
    },

    // 获取应用分类
    async getCrudAllType() {
      const data = await getcrudCateListApi(this.where)
      this.total = data.data.count
      this.listData = data.data.list
    },
    handleClick(item) {
      this.$router.push({
        path: `${roterPre}/develop/crud/entityList`,
        query: { cate_id: item.id }
      })
    },
     getIconCss(cssName) {
      if (/^el-icon-/.test(cssName)) {
        return [cssName]
      } else {
        return ['iconfont', cssName]
      }
    },
    // 编辑应用
    handleEdit(item) {
      this.applicatIsShow = true
      setTimeout(() => {
        this.$refs.applicatDialog.openBox(item)
      }, 300)
    },
    // 删除应用
    async deleteFn(id) {
      await modalSure('你确定要删除当前应用吗')
      await delcrudCateApi(id)
      this.getCrudAllType(1)
    },

    confirmData(val) {
      if (val === 'reset') {
        this.where.name = ''
      } else {
        this.where.name = val.name
      }
      this.getCrudAllType(1)
    },
    pageChange(val) {
      this.where.page = val
      this.getCrudAllType(1)
    },


  }
}
</script>

<style lang="scss" scoped>
.list {
  box-sizing: border-box;
  /* 防止padding影响高度 */
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(302px, 0.33fr));
  grid-auto-rows: minmax(153px, auto);
  /* 行高自适应内容，最小150px */
  gap: 15px;
  /* 卡片间距 */
}

.item {
  cursor: pointer;
  border: 1px solid #DCDFE6;
  border-radius: 12px 12px 12px 12px;
  padding: 20px 15px;
  height: 168px;
  font-family: PingFang SC-Regular;
  position: relative;

  .item-header {
  position: relative;
    display: flex;
    height: 32px;
    line-height: 32px;
    margin-bottom: 15px;
    .count {
      position: absolute;
      right: 0;
      top: 0;
    
      font-weight: 400;
      font-size: 14px;
      color: #909399;
    }
  }

  .status {
    position: absolute;
    right: 14px;
    width: 48px;
    height: 20px;
    background: rgba(24, 144, 255, 0.05);
    border-radius: 3px 3px 3px 3px;
    border: 1px solid #1890ff;
    display: flex;
    align-items: center;

    justify-content: center;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 12px;
    color: #1890ff;
  }

  .content {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 14px;
    color: #606266;
    height: 44px;
    line-height: 22px;
    margin-bottom: 20px;
  }

  .operate {
    font-weight: 400;
    font-size: 13px;
    color: #1890ff;
    cursor: pointer;
  }

  .icon-box { 
    display: block;
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 3px;
    margin-right: 8px;
    background: #1890ff;
    color: #fff;
    text-align: center;
    line-height: 32px;
  }

  .title {
    width: 80%;
    font-family: PingFang SC-Medium;
    font-weight: 500;
    font-size: 14px;
    color: #303133;
  }

  .name {
    margin-top: 3px;
    font-weight: 400;
    font-size: 12px;
    color: #606266;
  }
}

.entry-name {
  flex: 1;
  overflow: hidden;
}
</style>
