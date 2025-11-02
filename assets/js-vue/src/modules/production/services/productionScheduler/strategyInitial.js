export default {
    name: 'Strategia prosta',
    description: 'Terminy zakończeń: Klejenie: 5 dni przed końcem, pozostałe działy: termin końca = termin realizacji dla całości. Termin startu dla wszystkich to data dzisiejsza.',
    default: false,
    deleted: false,
    strategy: {
        // dpt01 GLUING
        'dpt01.dateStart': {
            value: undefined,
            dependentOn: 'todayDate',
            steps: []
        },

        'dpt01.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByDays', params: {count: 5, direction: 'before'}},
                {method: 'shiftToWorkingDay', params: { direction: 'before' }},
            ]
        },

        // dpt02 CNC
        'dpt02.dateStart': {
            value: undefined,
            dependentOn: 'todayDate',
            steps: [],
        },

        'dpt02.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: []
        },

        // dpt03 GRINDING
        'dpt03.dateStart': {
            value: undefined,
            dependentOn: 'todayDate',
            steps: [],
        },

        'dpt03.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: []
        },

        // dpt04 VARNISHING
        'dpt04.dateStart': {
            value: undefined,
            dependentOn: 'todayDate',
            steps: [],
        },
        'dpt04.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: []
        },

        // dpt05 PACKAGING
        'dpt05.dateStart': {
            value: undefined,
            dependentOn: 'todayDate',
            steps: [],
        },
        'dpt05.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: []
        }
    }
}