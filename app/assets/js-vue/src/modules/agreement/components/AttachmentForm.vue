<template>
    <div class="attachment-subsection">
        <h6 class="subsection-title">Załączniki</h6>

        <div class="upload-area">
            <div class="upload-icon">
                <i class="fa fa-cloud-upload fa-3x text-muted"></i>
            </div>
            <p class="upload-text">
                <strong>Przeciągnij pliki tutaj lub kliknij, aby wybrać</strong>
            </p>
            <p class="upload-hint">
                Obsługiwane formaty: PDF, JPG, PNG, TXT (max 10MB)
            </p>
            <button class="btn btn-outline-primary btn-sm" type="button" disabled>
                <i class="fa fa-folder-open"></i> Wybierz pliki
            </button>
            <div class="info-message">
                <i class="fa fa-info-circle"></i> Funkcjonalność załączników będzie wkrótce dostępna
            </div>
        </div>

        <!-- Placeholder for future attachments list -->
        <div v-if="modelValue && modelValue.length > 0" class="attachments-list">
            <h6 class="list-title">Załączone pliki:</h6>
            <div class="attachment-items">
                <div
                    v-for="(attachment, index) in modelValue"
                    :key="index"
                    class="attachment-item"
                >
                    <div class="attachment-info">
                        <i class="fa fa-file"></i> {{ attachment.name || 'Plik ' + (index + 1) }}
                    </div>
                    <button
                        class="btn btn-sm btn-danger"
                        @click="removeAttachment(index)"
                        type="button"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AttachmentForm',

    props: {
        modelValue: {
            type: Array,
            default: () => []
        }
    },

    emits: ['update:modelValue'],

    methods: {
        removeAttachment(index) {
            const newAttachments = [...this.modelValue];
            newAttachments.splice(index, 1);
            this.$emit('update:modelValue', newAttachments);
        }
    }
};
</script>

<style lang="scss" scoped>
.attachment-subsection {
    // Brak własnego tła - dziedziczone z section-container
}

.subsection-title {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: none;
    letter-spacing: 0;
}

.upload-area {
    background-color: #ffffff;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem 1.5rem;
    text-align: center;
    transition: all 0.3s;

    &:hover {
        background-color: #fafbfc;
        border-color: #adb5bd;
    }
}

.upload-icon {
    margin-bottom: 1rem;
}

.upload-text {
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.upload-hint {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.info-message {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: #d1ecf1;
    color: #0c5460;
    border-radius: 6px;
    font-size: 0.9rem;
}

.attachments-list {
    margin-top: 1rem;
}

.list-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
}

.attachment-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.attachment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: all 0.2s;

    &:hover {
        border-color: #ced4da;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
}

.attachment-info {
    color: #495057;
    font-size: 0.9rem;
}
</style>
