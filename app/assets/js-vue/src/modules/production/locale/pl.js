export default {
    productionScheduler: {
        strategyFast: {
            name: 'Strategia szybka 6-2-2-0-0-0-czwartek',
            description: 'Terminy zakończeń: Klejenie: 6 dni przed końcem, CNC i Intorex 2 dni przed końcem, pozostałe działy: termin końca = termin realizacji dla całości. Termin startu to termin zakończenia w poprzednim dziale.'
        },
        strategyCascade: {
            name: 'Strategia 14-9-9-7-2-0-czwartek',
            description: 'Terminy zakończeń: Klejenie: 14 dni, CNC i Intorex: 9 dni, Szlifowanie: 7 dni, Lakierowanie: 2 dni, Pakowanie: 0 dni przed czwartkiem. Termin startu to termin zakończenia w poprzednim dziale.'
        }
    }
}