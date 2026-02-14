export default {
    name: 'Strategia 14-9-7-2-0-czwartek',
    description: 'Terminy zakończeń: Klejenie: 14 dni, CNC: 9 dni, Szlifowanie: 7 dni, Lakierowanie: 2 dni, Pakowanie: 0 dni przed czwartkiem. Termin startu to termin zakończenia w poprzednim dziale.',
    default: false,
    deleted: false,
    strategy: {
        // dpt01 GLUING
        'dpt01.dateStart': {
            value: undefined,
            dependentOn: 'dpt01.dateEnd',
            steps: [
                {method: 'shiftByDays', params: {count: 7, direction: 'before'}},
            ]
        },

        'dpt01.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 14, direction: 'before'}},
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
                {method: 'shiftByDays', params: {count: 9, direction: 'before'}},
            ]
        },

        // dpt03 GRINDING
        'dpt03.dateStart': {
            value: undefined,
            dependentOn: 'dpt02.dateEnd',
            steps: [],
        },

        'dpt03.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 7, direction: 'before'}},
            ]
        },

        // dpt04 VARNISHING
        'dpt04.dateStart': {
            value: undefined,
            dependentOn: 'dpt03.dateEnd',
            steps: [],
        },
        'dpt04.dateEnd': {
            value: undefined,
            dependentOn: 'deadlineDate',
            steps: [
                {method: 'shiftByWeekday', params: {day: 'czwartek', direction: 'before'}},
                {method: 'shiftByDays', params: {count: 2, direction: 'before'}},
            ]
        },

        // dpt05 PACKAGING
        'dpt05.dateStart': {
            value: undefined,
            dependentOn: 'dpt04.dateEnd',
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