<template>
    <div>
        <el-drawer title="关联订单" v-model="show" size="1000px"  :append-to-body="true">
            <div class="p20">
                <paymentTable ref="paymentTable" type="check"  @handleSelectionFn="handleSelectionFn"></paymentTable>
                <div class="button from-foot-btn fix btn-shadow">
                    <el-button @click="handleClose">取消</el-button>
                    <el-button type="primary" @click="submit">确定</el-button>
                </div>
            </div>
        </el-drawer>
    </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { contractLinkOrderApi } from '@/api/contractSign.ts'
export default {
  emits: ['getTableData'],
    name: 'paymentTableDialog',
    components: {
        paymentTable: defineAsyncComponent(() => import('./paymentTable.vue')),
    },
    props: {
        list: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            show: false,
          
            selectedList: [],
            rowData: {}
        }
    },
    methods: {
        openBox(row) {
            this.rowData = row
            this.show = true
            if(row.link_type==5){
                row.cid = []
            }
            setTimeout(() => {
                this.$refs.paymentTable.changeFn(row.eid, [])
            }, 100);
        },
        submit() {
            let obj = {
                cid: this.rowData.cid
            }
            if (this.selectedList.length > 0) {
                this.selectedList.forEach(item => {
                    obj.cid.push(item.id)
                })
            }

            contractLinkOrderApi(this.rowData.id, obj).then(res => {
                this.$emit('getTableData')
                this.handleClose()
            })


        },
        handleSelectionFn(val, list) {
            this.selectedList = val
        },
        handleClose() {
            this.show = false
            this.selectedList = []
            this.rowData = {}
        }
    }
}
</script>
<style lang="scss" scoped>
.p20 {
    padding: 20px;
}
</style>