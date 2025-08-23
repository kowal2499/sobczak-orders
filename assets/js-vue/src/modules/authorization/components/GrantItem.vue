<script>

export default {
    name: 'GrantItem',
    props: {
        grant: {
            type: Object,
            required: true
        }
    }
}
</script>

<template>
    <div class="grant-item">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ grant.name }}</strong>
                <div class="text-sm text-muted">{{ grant.description }}</div>
            </div>
            <div>
                <div v-if="grant.type === 'boolean'">
                    <b-form-checkbox switch size="lg" />
                </div>
                <b-form-checkbox-group v-else
                    :options="grant.options.map(opt => ({ text: opt.label, value: opt.optionSlug }))"
                    buttons
                    button-variant="outline-primary"
                ></b-form-checkbox-group>
            </div>
        </div>

<!--        <pre> {{ grant }}</pre>-->
    </div>
</template>

<style scoped lang="scss">
.grant-item + .grant-item {
   border-top: 1px solid #e9ecef;
}
</style>