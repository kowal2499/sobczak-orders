<template>
    <b-modal
        v-model="visibleProxy"
        :title="modalTitle"
        hide-header-close
        @show="reset"
    >

        <b-form ref="form" @submit.prevent="onSubmit">
            <b-form-group
                :label="$t('tags.name')"
                :state="validation.name"
                :invalid-feedback="$t('tags.nameInvalid')"
                :disabled="isSaving"
            >
                <b-form-input
                    v-model="form.name"
                    type="text"
                    :placeholder="$t('tags.namePlaceholder')"
                    required
                />
            </b-form-group>

            <b-form-row>
                <b-col lg="5">
                    <b-form-group :label="$t('tags.icon')" :disabled="isSaving" class="mb-0">
                        <b-dropdown variant="outline-secondary" class="icon-picker">
                            <template #button-content>
                                <span v-if="!form.icon">{{ $t('tags.iconPlaceholder') }}</span>
                                <b-icon v-else :icon="form.icon" :style="{ color: form.color }" aria-hidden="true"></b-icon>
                            </template>

                            <b-dropdown-item v-for="icon in icons" :key="icon" @click="form.icon = icon">
                                <b-icon :icon="icon" :style="{ color: form.color }" aria-hidden="true"></b-icon>
                            </b-dropdown-item>
                        </b-dropdown>
                    </b-form-group>
                </b-col>

                <b-col lg="7">
                    <b-form-group :label="$t('tags.color')" :disabled="isSaving" class="mb-0">
                        <b-form-input v-model="form.color" type="color"/>
                    </b-form-group>
                </b-col>
            </b-form-row>
        </b-form>

        <template #modal-footer="{ cancel }">
            <div class="form-actions w-100 mt-0">
                <b-button variant="outline-secondary" :disabled="isSaving" @click="cancel()">
                    {{ $t('tags.cancel') }}
                </b-button>
                <b-button variant="primary" :disabled="isSaving" @click="onSubmit">
                    <i v-if="isSaving" class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                    {{ $t('tags.save') }}
                </b-button>
            </div>
        </template>
    </b-modal>
</template>

<script>
import {create, update} from "../repository";

export default {
    name: "Modal",
    props: {
        definitionData: {
            type: Object,
            default: () => ({})
        },
        visible: {
            type: Boolean,
            default: false,
        }
    },
    watch: {
        visible() {
            this.visibleProxy = this.visible;
        },
        visibleProxy() {
            if (!this.visibleProxy) {
                this.closeModal();
            }
        }
    },
    computed: {
        icons() {
            return ['award', 'bag', 'basket2', 'bookmark', 'brightnessHigh',
            'bug', 'building', 'calendar2Check', 'fileEarmarkText']
        },
        modalTitle() {
            return this.definitionData.id ? this.$t('tags.editTagTitle') : this.$t('tags.newTagTitle');
        }
    },
    methods: {
        checkFormValidity() {
            const valid = this.$refs.form.checkValidity()
            this.validation = {
                name: !!this.form.name,
                module: !!this.form.module
            }
            return valid
        },
        onSubmit() {
            if (!this.checkFormValidity()) {
                return
            }
            this.isSaving = true;
            const promise = this.definitionData.id
                ? update(this.definitionData.id, this.form)
                : create(this.form);
            promise
                .then(() => {
                    this.$nextTick(() => {
                        this.closeModal({
                            id: this.definitionData.id || null,
                            ...this.form
                        })
                    })
                })
                .finally(() => this.isSaving = false)
        },
        reset() {
            // the only module tags are used in; no longer editable, but the API still expects it
            this.form = {
                name: this.definitionData.name || '',
                module: 'agreement-line',
                icon: this.definitionData.icon || null,
                color: this.definitionData.color || '#ffffff'
            };
            this.validation.name = true;
            this.validation.module = true;
        },
        closeModal(returnValue = null) {
            this.$emit('close', returnValue)
        }
    },
    data: () => ({
        form: {},
        validation: {
            name: true,
            module: true
        },
        isSaving: false,
        visibleProxy: false
    })
}
</script>

<style scoped>
.icon-picker ::v-deep .dropdown-menu {
    min-width: auto;
}
</style>