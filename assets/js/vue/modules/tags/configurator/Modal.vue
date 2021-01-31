<template>
    <b-modal
        v-model="visibleProxy"
        :title="modalTitle"
        @ok="onOk"
        @cancel="onCancel"
        @show="reset"
        :cancel-disabled="isSaving"
        :ok-disabled="isSaving"
    >
        <b-form ref="form" @submit="onSubmit">
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

            <b-form-group
                :label="$t('tags.module')"
                :state="validation.module"
                :invalid-feedback="$t('tags.moduleInvalid')"
                :disabled="isSaving"
            >
                <b-form-select v-model="form.module" required>
                    <b-form-select-option :value="null">
                        {{ $t('tags.modulePlaceholder') }}
                    </b-form-select-option>

                    <b-form-select-option v-for="module in modules" :key="module" :value="module">
                        {{ module }}
                    </b-form-select-option>
                </b-form-select>
            </b-form-group>

            <b-form-row>
                <b-col lg="5">
                    <b-form-group :label="$t('tags.icon')" :disabled="isSaving">
                        <b-dropdown>
                            <template #button-content>
                                <span v-if="!form.icon">{{ $t('tags.iconPlaceholder') }}</span>
                                <b-icon :icon="form.icon" aria-hidden="true"></b-icon>
                            </template>

                            <b-dropdown-item v-for="icon in icons" :key="icon" @click="form.icon = icon">
                                <b-icon :icon="icon" aria-hidden="true"></b-icon>
                            </b-dropdown-item>
                        </b-dropdown>
                    </b-form-group>
                </b-col>

                <b-col lg="7">
                    <b-form-group :label="$t('tags.color')" :disabled="isSaving">
                        <b-form-input v-model="form.color" type="color"/>
                    </b-form-group>
                </b-col>
            </b-form-row>
        </b-form>

        <template #modal-ok>
            <b-icon icon="three-dots" animation="cylon" v-if="isSaving"></b-icon> {{ $t('tags.ok') }}
        </template>
        <template #modal-cancel>
            {{ $t('tags.cancel') }}
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
        modules() {
            return ['production', 'order', 'customer'];
        },
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
        onOk(bvModalEvt) {
            bvModalEvt.preventDefault();
            this.onSubmit();
        },
        onCancel() {

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
            this.form = {
                name: this.definitionData.name || '',
                module: this.definitionData.module || null,
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

</style>