<script>
import { defineComponent } from 'vue'
import { toDay } from './dates'

/**
 * Poglądowa informacja, czy ukończenie zmieściło się w zaplanowanym oknie pracy działu.
 *
 * Używa jej raport "Ukończone zadania produkcyjne", w którym termin NIE wpływa na współczynnik -
 * stąd neutralna prezentacja (bez czerwieni) i jawny dopisek o braku wpływu na premię. Ocena
 * premiowa mieszka w TimelinessSummary.
 *
 * Porównanie jest kalendarzowe, bez liczenia dni roboczych: backend przysyła odchylenie w dniach
 * roboczych wyłącznie dla miernika premii za terminowość.
 */
export default defineComponent({
    name: 'WindowAdherence',
    props: {
        production: {
            type: Object,
            required: true,
        },
    },
    computed: {
        // null = nie ma czego oceniać (brak okna albo zadanie nieukończone)
        isMet() {
            if (!this.production.dateStart || !this.production.dateEnd || !this.production.completedAt) {
                return null
            }
            const done = toDay(this.production.completedAt)

            return done >= toDay(this.production.dateStart) && done <= toDay(this.production.dateEnd)
        },
    }
})
</script>

<template>
    <div v-if="isMet !== null">
        <div class="pop-row">
            <span class="pop-label">{{ $t('dashboard.plannedWindow.label') }}</span>
            <span class="pop-val text-muted">
                {{ isMet ? $t('dashboard.plannedWindow.met') : $t('dashboard.plannedWindow.missed') }}
            </span>
        </div>
        <div class="pop-note pop-note--small">{{ $t('dashboard.plannedWindow.note') }}</div>
    </div>
</template>

<style lang="scss">
.factor-cell-popover {
    .pop-note--small {
        font-size: 0.78rem;
    }
}
</style>
