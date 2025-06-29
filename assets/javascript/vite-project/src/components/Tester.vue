<script setup lang="ts">
import {onMounted, ref} from "vue";

    const name = ref('Sobczak')

    onMounted(() => {
        console.log('mounted')
    })
</script>

<template>
    <div>Wynik: {{ name }}</div>
</template>

<style scoped>

</style>