import { ref } from 'vue';

export const useExamine = () => {
  const examineEditRef = ref(null);

  const openExaminePanel = (examineId) => {
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