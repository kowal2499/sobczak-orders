<template>
    <div class="entry" :class="{ compact }">
        <div class="entry-meta">
            <Avatar :name="authorName" class="entry-avatar" />
            <div class="dates">
                <span class="ago">{{ relativeDate }}</span>
                <span class="absolute">{{ absoluteDate }}</span>
            </div>
        </div>

        <span class="dot" aria-hidden="true"></span>

        <div class="bubble">
            <component :is="contentRenderer" :log="log" />
        </div>
    </div>
</template>

<script>
import { format as timeago } from 'timeago.js';
import Avatar from '@/components/base/Avatar.vue';
import DefaultContent from './ActivityLogContent/DefaultContent.vue';
import AgreementUpdatedContent from './ActivityLogContent/AgreementUpdatedContent.vue';
import ProductionStatusChangedContent from './ActivityLogContent/ProductionStatusChangedContent.vue';
import ProductionDateChangedContent from './ActivityLogContent/ProductionDateChangedContent.vue';

// Map of log.type -> Vue component used to render its content area.
// Add an entry here when a new log type needs custom rendering (e.g. field-diff).
// Falls back to DefaultContent for unmapped types.
const RENDERERS = {
    'agreement.updated': AgreementUpdatedContent,
    'agreement_line.production_status_changed': ProductionStatusChangedContent,
    'agreement_line.production_date_start_changed': ProductionDateChangedContent,
    'agreement_line.production_date_end_changed': ProductionDateChangedContent,
};

function pad(n) {
    return String(n).padStart(2, '0');
}

function formatAbsolute(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
        + `${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default {
    name: 'ActivityLogEntry',
    components: { Avatar },
    props: {
        log: {
            type: Object,
            required: true,
        },
        compact: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        authorName() {
            return this.log.user?.name || this.$t('agreement.activityLog.system');
        },
        contentRenderer() {
            return RENDERERS[this.log.type] || DefaultContent;
        },
        parsedDate() {
            return new Date(this.log.date);
        },
        relativeDate() {
            return timeago(this.parsedDate, this.$i18n.locale);
        },
        absoluteDate() {
            return formatAbsolute(this.parsedDate);
        },
    },
};
</script>

<style lang="scss" scoped>
.entry {
    display: grid;
    grid-template-columns: 130px 24px 1fr;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

// Author avatar + dates, sitting above the bubble, aligned to the right.
.entry-meta {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.5rem;
}

.entry-avatar {
    flex-shrink: 0;
}

.dates {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    text-align: right;
    line-height: 1.25;
}

.ago {
    font-size: 0.85rem;
    color: #212529;
    font-weight: 500;
}

.absolute {
    font-size: 0.75rem;
    color: #adb5bd;
    margin-top: 0.125rem;
}

.dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--colorPrimary);
    box-shadow: 0 0 0 3px #fff;
    justify-self: center;
    margin-top: 0.875rem;
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}

.bubble {
    position: relative;
    background: #fff;
    border: 1px solid var(--colorGrayLight80);
    border-radius: 6px;
    padding: 0.75rem;
    min-width: 0; // allow long text to wrap rather than overflow
}

// Arrow pointing left towards the dot — two triangles to mimic a 1px border.
.bubble::before,
.bubble::after {
    content: '';
    position: absolute;
    top: 1rem;
    width: 0;
    height: 0;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
}

.bubble::before {
    left: -9px;
    border-right: 9px solid var(--colorGrayLight80);
}

.bubble::after {
    left: -8px;
    border-right: 9px solid #fff;
}

// Compact mode: timeline axis + dot stay; meta (avatar + dates) sits on top.
// Layout: 2 columns ([dot] [1fr]) × 2 rows.
//   Row 1: empty col 1 | entry-meta (avatar + dates, right-aligned)
//   Row 2: dot         | bubble (arrow pointing left at the dot)
.entry.compact {
    display: grid;
    grid-template-columns: 24px 1fr;
    grid-template-rows: auto auto;
    column-gap: 0.625rem; // small gap between dot and bubble; arrow visually bridges it
    row-gap: 0.25rem;
    align-items: start;
    padding: 0.5rem 0;

    .entry-meta {
        grid-column: 2;
        grid-row: 1;
        padding-top: 0;
    }

    .dot {
        grid-column: 1;
        grid-row: 2;
        align-self: start;
        margin-top: 0.875rem;
    }

    .bubble {
        grid-column: 2;
        grid-row: 2;
        padding: 0.625rem;
    }
}

@media (max-width: 768px) {
    .entry {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.375rem;
        padding: 0.5rem 0;
    }

    .entry-meta {
        padding-top: 0;
        justify-content: flex-start;
    }

    .ago {
        font-size: 0.8rem;
    }

    .absolute {
        margin-top: 0;
    }

    .dot {
        display: none;
    }

    .bubble {
        &::before,
        &::after {
            display: none;
        }
    }
}
</style>
