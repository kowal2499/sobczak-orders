<template>
    <div class="sb-info-card sb-attachments">
        <div v-if="attachments.length === 0" class="text-left">
            <i class="fa fa-ban mr-2"></i> {{ $t('orders.noattachments') }}
        </div>

        <ul v-else :style="horizontal ? 'display: flex' : ''">
            <li v-for="attachment in attachments">
                <a :href="attachment.path" target="_blank">

                    <template v-if="tooltip">
                        <tooltip>
                            <span slot="visible-content">
                                <img :src="attachment.thumbnail" :alt="attachment.name" v-if="attachment.thumbnail" >
                                <span class="ml-1 link-txt" v-if="showName">{{ ''.concat(attachment.originalName, '.', attachment.extension) }}</span>
                            </span>
                            <span slot="tooltip-content">{{ ''.concat(attachment.originalName, '.', attachment.extension) }}</span>
                        </tooltip>
                    </template>

                    <template v-else>
                        <img :src="attachment.thumbnail" :alt="attachment.name" v-if="attachment.thumbnail" >
                        <span class="ml-1 link-txt" v-if="showName">{{ ''.concat(attachment.originalName, '.', attachment.extension) }}</span>
                    </template>

                </a>
            </li>
        </ul>
    </div>
</template>

<script>
    import Tooltip from '../../base/Tooltip';

    export default {
        name: 'AttachmentsWidget',
        components: { Tooltip },
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

        methods: {
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

</style>