<template>
    <div class="sb-info-card sb-attachments">
        <div v-if="attachments.length === 0" class="text-left">
            <i class="fa fa-ban mr-2"></i> {{ $t('orders.noattachments') }}
        </div>

        <ul v-else :class="horizontal ? 'd-flex flex-row gap-3' : ''">
            <li v-for="(attachment, index) in attachments" :key="attachment.id">
                <!-- Dla obrazów - otwieramy lightbox -->
                <a
                    v-if="isImage(attachment)"
                    href="#"
                    @click.prevent="openLightbox(index)"
                    style="cursor: pointer"
                >
                    <div
                        :class="!horizontal ? 'd-flex flex-row align-items-center gap-2' : 'd-flex flex-column gap-2'"
                        :style="horizontal ? 'width: 60px;' : ''"
                        :id="attachment.id"
                    >
                        <img
                            :src="attachment.thumbnail"
                            :alt="attachment.name"
                            v-if="attachment.thumbnail"
                        >
                        <span
                            v-if="showName"
                            class="ml-1 link-txt"
                            :style="'display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'"
                        >{{ ''.concat(attachment.originalName, '.', attachment.extension) }}</span>
                    </div>

                    <b-tooltip :target="String(attachment.id)" v-if="tooltip">
                        {{ ''.concat(attachment.originalName, '.', attachment.extension) }}
                    </b-tooltip>
                </a>

                <!-- Dla plików niebędących obrazami - normalny link do pobrania -->
                <a
                    v-else
                    :href="attachment.path"
                    target="_blank"
                >
                    <div
                        :class="!horizontal ? 'd-flex flex-row align-items-center gap-2' : 'd-flex flex-column gap-2'"
                        :style="horizontal ? 'width: 60px;' : ''"
                        :id="attachment.id"
                    >
                        <img
                            :src="attachment.thumbnail"
                            :alt="attachment.name"
                            v-if="attachment.thumbnail"
                        >
                        <span
                            v-if="showName"
                            class="ml-1 link-txt"
                            :style="'display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'"
                        >{{ ''.concat(attachment.originalName, '.', attachment.extension) }}</span>
                    </div>

                    <b-tooltip :target="String(attachment.id)" v-if="tooltip">
                        {{ ''.concat(attachment.originalName, '.', attachment.extension) }}
                    </b-tooltip>
                </a>
            </li>
        </ul>

        <!-- Lightbox dla obrazów -->
        <CoolLightBox
            :items="lightboxItems"
            :index="lightboxIndex"
            @close="lightboxIndex = null"
            :slideshow="false"
        />
    </div>
</template>

<script>
    import Tooltip from '../../base/Tooltip';
    import CoolLightBox from 'vue-cool-lightbox';
    import 'vue-cool-lightbox/dist/vue-cool-lightbox.min.css';

    export default  {
        name: 'AttachmentsWidget',
        components: {
            Tooltip,
            CoolLightBox
        },
        props: {
            'attachments': {
                type: Array,
                default: () => [],
            },
            'showName': {
                type: Boolean,
                default: true,
            },
            'tooltip': {
                type: Boolean,
                default: false,
            },
            'horizontal': {
                type: Boolean,
                default: false,
            }
        },

        data: () => ({
            lightboxIndex: null
        }),

        computed: {
            imageAttachments() {
                return this.attachments.filter(att => this.isImage(att));
            },

            lightboxItems() {
                return this.imageAttachments.map(att => ({
                    src: att.viewPath || att.path,
                    title: `${att.originalName}.${att.extension}`
                }));
            }
        },

        methods: {
            isImage(attachment) {
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                return attachment.extension && imageExtensions.includes(attachment.extension.toLowerCase());
            },

            openLightbox(attachmentIndex) {
                // Znajdź indeks tego załącznika w tablicy imageAttachments
                const attachment = this.attachments[attachmentIndex];
                const lightboxIndex = this.imageAttachments.findIndex(img => img.id === attachment.id);

                if (lightboxIndex !== -1) {
                    this.lightboxIndex = lightboxIndex;
                }
            }
        }
    }
</script>

<style lang="scss">

    .sb-attachments {
        img {
            width: 60px;
        }

        ul {
            list-style-type: none;
            margin: 0;
            padding: 0;

            li {
                margin-bottom: 20px;

                a:hover {
                    text-decoration: none;

                    .link-txt {
                        text-decoration: underline;
                    }
                }
            }

        }
    }

    // Lightbox images powinny mieć pełny rozmiar
    .cool-lightbox {
        img {
            width: auto !important;
            max-width: 100%;
            height: auto;
        }
    }

</style>