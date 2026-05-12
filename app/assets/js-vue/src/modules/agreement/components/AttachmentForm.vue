<template>
    <div class="attachment-subsection">
        <vue2dropzone
            ref="dropzone"
            id="agreement-dropzone"
            :options="dropzoneOptions"
            @vdropzone-file-added="onFileAdded"
            @vdropzone-removed-file="onFileRemoved"
            @vdropzone-queue-complete="$emit('vdropzone-queue-complete')"
            @vdropzone-error="onDropzoneError"
        />
    </div>
</template>

<script>
import Vue2dropzone from 'vue2-dropzone';
import 'vue2-dropzone/dist/vue2Dropzone.min.css';

export default {
    name: 'AttachmentForm',

    components: { Vue2dropzone },

    data() {
        return {
            removedIds: [],
            errorFiles: [],
        };
    },

    computed: {
        dropzoneOptions() {
            return {
                url: '/',
                thumbnailWidth: 150,
                maxFilesize: 50,
                acceptedFiles: 'image/*,application/pdf,.txt,.cs',
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: true,
                parallelUploads: 20,
                dictDefaultMessage: this.$t('agreement.attachment.dropzoneMessage'),
                dictRemoveFile: this.$t('agreement.attachment.removeFile'),
                dictCancelUpload: this.$t('agreement.attachment.cancelUpload'),
            };
        }
    },

    methods: {
        getQueuedFiles() {
            return this.$refs.dropzone.getQueuedFiles();
        },

        getRemovedIds() {
            return this.removedIds;
        },

        processQueue(url, appendFormValues) {
            this.$refs.dropzone.setOption('url', url);
            this.$refs.dropzone.$once('vdropzone-sending-multiple', (files, xhr, formData) => {
                appendFormValues(formData);
            });
            this.$refs.dropzone.processQueue();
        },

        getErrorFiles() {
            return this.$refs.dropzone.getFilesWithStatus('error');
        },

        loadExistingFiles(attachments) {
            this.removedIds = [];
            attachments.forEach(attachment => {
                const mockFile = {
                    id: attachment.id,
                    name: attachment.originalName + '.' + attachment.extension,
                    size: 0,
                    type: 'existing',
                    status: 'existing',
                    accepted: true,
                };
                this.$refs.dropzone.manuallyAddFile(mockFile, attachment.thumbnail || attachment.url);
            });
        },

        onDropzoneError(file, message, xhr) {
            if (!xhr) {
                // Lokalny błąd walidacji (za duży plik, zły typ) — brak requestu do serwera
                this.$emit('vdropzone-file-rejected', message);
            } else {
                // Z odpowiedzi serwera wyciągamy komunikat per plik (uploadMultiple
                // powoduje, że dropzone domyślnie wsadzi to samo `message` każdemu plikowi).
                const perFileMessage = this.findPerFileMessage(file, xhr);
                if (perFileMessage) {
                    this.replaceErrorMessageInDom(file, perFileMessage);
                    message = perFileMessage;
                }
                this.$emit('vdropzone-error', file, message, xhr);
            }
            if (!this.errorFiles.includes(file)) {
                this.errorFiles.push(file);
            }
            this.$emit('error-state-changed', this.errorFiles.length > 0);
        },

        onFileAdded() {
            this.$emit('files-changed', this.getQueuedFiles());
        },

        onFileRemoved(file) {
            if (file.id) {
                this.removedIds.push(file.id);
            }
            const idx = this.errorFiles.indexOf(file);
            if (idx !== -1) {
                this.errorFiles.splice(idx, 1);
            }
            this.$emit('files-changed', this.getQueuedFiles());
            this.$emit('error-state-changed', this.errorFiles.length > 0);
        },

        findPerFileMessage(file, xhr) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (Array.isArray(data.errors)) {
                    const match = data.errors.find(e => e.filename === file.name);
                    return match ? match.message : null;
                }
            } catch (e) { /* responseText nie jest JSON-em — fallback do domyślnego message */ }
            return null;
        },

        replaceErrorMessageInDom(file, message) {
            if (!file.previewElement) {
                return;
            }
            file.previewElement.querySelectorAll('[data-dz-errormessage]').forEach(el => {
                el.textContent = message;
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.attachment-subsection {
    :deep(.dropzone) {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        background: #ffffff;
        min-height: 120px;
        padding: 1.5rem;
        transition: all 0.3s;

        &:hover {
            border-color: #adb5bd;
            background: #fafbfc;
        }

        .dz-message {
            margin: 0.5rem 0;
        }

        .dz-preview {
            // Przycisk remove wyśrodkowany horyzontalnie dla wszystkich miniatur
            .dz-remove {
                left: 50%;
                transform: translateX(-50%);
                margin-left: 0;
            }

            &.dz-error {
                // Komunikat zawsze widoczny, nie tylko na hover
                .dz-error-message {
                    opacity: 1;
                    pointer-events: none;
                }

                // Przycisk remove: góra, wyśrodkowany, na hover, z tłem dla czytelności
                .dz-remove {
                    z-index: 1001;
                    top: 5px;
                    bottom: auto;
                    left: 50%;
                    transform: translateX(-50%);
                    margin-left: 0;
                    background-color: rgba(0, 0, 0, 0.55);
                    border-radius: 4px;
                }
            }
        }
    }
}
</style>
