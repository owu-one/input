<template>
  <div>
    <h2 class="mb-2 text-base font-bold">Type</h2>
    <div>
      <InteractionTypeButton
        v-for="type in types"
        :key="type.label"
        class="mr-1 mb-1"
        v-bind="{ label: type.label, value: type.value, current: workbench.block?.type }"
        @onInput="updateInteractionType"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useWorkbench } from '@/stores';
import { ref, Ref } from 'vue';
import InteractionTypeButton from './InteractionTypeButton.vue';

const workbench = useWorkbench()

const types: Ref<Array<{ label: string, value: string }>> = ref([
  { label: "Message Only", value: "message" },
  { label: "Single Choice", value: "click" },
  { label: "Multiple Choice", value: "multiple" },
  { label: "Input", value: "input" },
])

const updateInteractionType = (event: FormBlockModel["type"]) => {
  workbench.updateBlock({
    type: event
  })
}
</script>