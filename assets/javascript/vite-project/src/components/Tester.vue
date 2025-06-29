<script setup lang="ts">
import {onMounted, ref} from "vue";

    const name: Ref<string> = ref('Sobczak')
</script>

<template>
    <h1>
        <div>Result: {{ name }}</div>
    </h1>
</template>

<style scoped>

</style>