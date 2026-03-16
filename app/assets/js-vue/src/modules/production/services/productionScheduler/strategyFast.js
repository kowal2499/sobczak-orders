export default {
    name: 'Strategia szybka 6-2-0-0-0-czwartek',
    description: 'Terminy zakończeń: Klejenie: 7 dni przed końcem, CNC 2 dni przed końcem, pozostałe działy: termin końca = termin realizacji dla całości. Termin startu to termin zakończenia w poprzednim dziale.',
    default: false,
    deleted: false,
    strategy: {
        // dpt01 GLUING
        'dpt01.dateStart': {
            value: undefined,
            dependentOn: 'dpt01.dateEnd',
            steps: [
                {method: 'shiftByDays', params: {count: 2, direction: 'before'}},
            ]
        },

        'dpt01.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 6, direction: 'before'}},
            ]
        },

        // dpt02 CNC
        'dpt02.dateStart': {
            value: undefined,
            dependentOn: 'dpt01.dateEnd',
            steps: [],
        },

        'dpt02.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 2, direction: 'before'}},
            ]
        },

        // dpt06 Intorex
        'dpt06.dateStart': {
            value: undefined,
            dependentOn: 'dpt02.dateEnd',
            steps: [],
        },

        'dpt06.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 2, direction: 'before'}},
            ]
        },

        // dpt03 GRINDING
        'dpt03.dateStart': {
            value: undefined,
            dependentOn: 'dpt06.dateEnd',
            steps: [],
        },

        'dpt03.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
            ]
        },

        // dpt04 VARNISHING
        'dpt04.dateStart': {
            value: undefined,
            dependentOn: 'dpt02.dateEnd',
            steps: [],
        },
        'dpt04.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
            ]
        },

        // dpt05 PACKAGING
        'dpt05.dateStart': {
            value: undefined,
            dependentOn: 'dpt02.dateEnd',
            steps: [],
        },
        'dpt05.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
            ]
        }
    }
}