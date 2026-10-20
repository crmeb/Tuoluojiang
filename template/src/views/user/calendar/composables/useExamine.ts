import { ref } from 'vue';

/** 审批详情侧滑组件的实例方法 */
interface ExaminePanel {
  openBox: (data: { id: number | string }, type: string) => void
}

export const useExamine = () => {
  const examineEditRef = ref<ExaminePanel | null>(null);

  const openExaminePanel = (examineId: number | string) => {
    if (!examineEditRef.value) return;
    examineEditRef.value.openBox({
      id: examineId
    }, 'revoke');
  }

  return {
    examineEditRef,
    openExaminePanel
  };
}