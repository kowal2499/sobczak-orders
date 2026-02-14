import i18n from '@/../i18n'
export function getFactorName(source, value) {
    switch (source) {
        case 'agreement_line':
            return i18n.t('dashboard.productionMetric.baseFactor')
        case 'factor_adjustment_bonus':
            return value > 0 ? i18n.t('dashboard.productionMetric.bonus') : i18n.t('dashboard.productionMetric.penalty')
        case 'factor_adjustment_ratio':
            return i18n.t('dashboard.productionMetric.percentageModifier')
        default:
            return i18n.t('dashboard.productionMetric.unsupportedValue')
    }
}

export function getFactorValue(source, value) {
    switch (source) {
        case 'factor_adjustment_ratio':
            return String(Math.round(value * 10000) / 100).concat('%')
        default:
            return Math.round(value * 100) / 100
    }
}