// Configuration and data for Placy Prototype
// This separates content from code for easier maintenance

const SITE_CONFIG = {
    navigation: {
        logo: 'overvik-logo.svg',
        menuItems: [
            { text: 'Vi er Overvik', href: '#' },
            { text: 'Boligvelger', href: '#' },
            { text: 'Leiligheter', href: '#' },
            { text: 'Rekkehus', href: '#' },
            { text: 'Galleri', href: '#' },
            { text: 'Nyheter', href: '#' }
        ],
        cta: {
            text: 'Meld interesse',
            href: '#'
        }
    },
    
    hero: {
        backgroundImage: 'overvik-hero.jpg',
        title: 'Velkommen over til Overvik',
        subtitle: 'Velkommen til en helt ny bydel på Ranheim – midt mellom fjorden og marka. Et sjønært nabolag som gjør hverdagen enkel.'
    },
    
    tableOfContents: {
        title: 'Utforsk Overvik',
        subtitle: 'Oppdag alt som gjør Overvik til det perfekte stedet å kalle hjem',
        items: [
            {
                id: 'mikrolokasjon',
                icon: '📍',
                title: 'Mikrolokasjon & stemning',
                description: 'Midt mellom fjorden og marka'
            },
            {
                id: 'hverdagsliv',
                icon: '🛒',
                title: 'Hverdagsliv',
                description: 'Alt du trenger i nærheten'
            },
            {
                id: 'kafe-spisesteder',
                icon: '☕',
                title: 'Kafé & spisesteder',
                description: 'Hyggelige møteplasser'
            },
            {
                id: 'friluftsomrader',
                icon: '🌲',
                title: 'De rike friluftsområdene',
                description: 'Naturen på dørstokken'
            },
            {
                id: 'narhet-marka',
                icon: '🥾',
                title: 'Nærhet til marka',
                description: 'Turmuligheter året rundt'
            },
            {
                id: 'idrettsbydelen',
                icon: '⚽',
                title: 'Idrettsbydelen',
                description: 'Aktiv livsstil og fellesskap'
            },
            {
                id: 'kollektiv-mobilitet',
                icon: '🚌',
                title: 'Kollektiv & mobilitet',
                description: 'Enkel transport til byen'
            },
            {
                id: 'oppvekst',
                icon: '🎓',
                title: 'Oppvekst',
                description: 'Trygg og god oppvekst for barna'
            },
            {
                id: 'fellesskap',
                icon: '🏘️',
                title: 'Velkommen over til fellesskapet',
                description: 'Bli en del av det levende nabolaget'
            }
        ]
    },
    
    sections: {
        mikrolokasjon: {
            title: 'Mikrolokasjon & stemning',
            description: 'Ligger som en naturlig forlengelse av Ranheim: åpent og skjermet, med kort vei til fjorden og grønne lommer. Identiteten er sjønær, aktiv og familievennlig.',
            icon: 'location',
            cards: [
                {
                    title: 'Overvik/Nergrenda',
                    description: 'Nytt boligprosjekt som skaper en moderne og attraktiv bydel.'
                },
                {
                    title: 'Ranheim sentrum',
                    description: 'Etablert sentrumsområde med handel og tjenester i gangavstand.'
                },
                {
                    title: 'Være/Vikåsen',
                    description: 'Nærliggende områder som utvider tilbudet av tjenester og aktiviteter.'
                }
            ]
        },
        
        hverdagsliv: {
            title: 'Hverdagsliv',
            description: 'Dagligvare, apotek og pakkepunkt i nærområdet – det meste løses til fots eller på sykkel. Et planlagt nærsenter vil samle flere tilbud lokalt.',
            icon: 'shopping',
            cards: [
                {
                    title: 'Kiwi Ranheim',
                    description: 'Dagligvarebutikk på Humlamyra for dine daglige innkjøp.'
                },
                {
                    title: 'Coop Prix Olderdalen',
                    description: 'Alternativ dagligvarebutikk med godt utvalg.'
                },
                {
                    title: 'Extra Grilstad',
                    description: 'Større handelsområde med utvidet utvalg av varer.'
                },
                {
                    title: 'Post/Pakkeboks',
                    description: 'Praktisk postløsning for sending og mottak av pakker.'
                },
                {
                    title: 'Apotek',
                    description: 'Apotek på Ranheim/Charlottenlund for legemidler og helseprodukter.'
                }
            ]
        },
        
        'kafe-spisesteder': {
            title: 'Kafé & spisesteder',
            description: 'Fersk bakst ved fjorden, enkel lunsj eller uteservering mot kveldssola – utvalget dekker hverdagsbehovene. Promenaden på Grilstad og Lade gir flere hyggelige stopp.',
            icon: 'coffee',
            cards: [
                {
                    title: 'Rosenborg Bakeri',
                    description: 'Fersk bakst ved Ranheimsfjæra med fantastisk utsikt over fjorden.'
                }
            ]
        },
        
        friluftsomrader: {
            title: 'Friluftsområder',
            description: 'Natur og friluftsliv rett på dørstokken. Fra fjorden til marka - alt ligger innen rekkevidde.',
            icon: 'location',
            cards: [
                {
                    title: 'Ranheimsfjæra',
                    description: 'Idyllisk fjordområde perfekt for turer langs vannet.'
                },
                {
                    title: 'Marka',
                    description: 'Skogsområder med turstier og naturopplevelser.'
                },
                {
                    title: 'Grilstad Marina',
                    description: 'Populært utfluktsmål med restauranter og utsikt.'
                }
            ]
        },
        
        'narhet-marka': {
            title: 'Nærhet til marka',
            description: 'Kort vei til Trondheims grønne lunger. Perfekt for helgeturer og hverdagsmotion.',
            icon: 'location',
            cards: [
                {
                    title: 'Bymarka',
                    description: 'Utstrakte skogsområder med preparerte løyper.'
                },
                {
                    title: 'Estenstadmarka',
                    description: 'Populært turområde med variert terreng.'
                },
                {
                    title: 'Theisendammen',
                    description: 'Naturperle med fine turmuligheter.'
                }
            ]
        },
        
        idrettsbydelen: {
            title: 'Idrettsbydelen',
            description: 'En av Norges mest aktive bydeler med mangfoldig tilbud for alle aldre og interesser.',
            icon: 'location',
            cards: [
                {
                    title: 'Ranheim IL',
                    description: 'Mest kjent for sin sterke satsning på breddefotball og toppfotball.'
                },
                {
                    title: 'Trondheim Atletklubb',
                    description: 'Friidrett og løp for alle aldre og nivåer.'
                },
                {
                    title: 'Heimdal Svømmeklubb',
                    description: 'Svømming og vannsport i nærområdet.'
                },
                {
                    title: 'NTNUI',
                    description: 'Studentidrett med stort utvalg av aktiviteter.'
                }
            ]
        },
        
        'kollektiv-mobilitet': {
            title: 'Kollektiv & mobilitet',
            description: 'Utmerket kollektivdekning gjør det enkelt å komme seg til sentrum og andre deler av byen.',
            icon: 'location',
            cards: [
                {
                    title: 'Buss til sentrum',
                    description: 'Hyppige avganger til Trondheim sentrum.'
                },
                {
                    title: 'Sykkelveier',
                    description: 'Gode sykkelveier og -stier gjennom området.'
                },
                {
                    title: 'Ranheim stasjon',
                    description: 'Lokal togforbindelse og regionalbuss.'
                }
            ]
        },
        
        oppvekst: {
            title: 'Oppvekst',
            description: 'Trygg og god oppvekst for barn og unge med skoler, barnehager og aktivitetstilbud i nærheten.',
            icon: 'location',
            cards: [
                {
                    title: 'Ranheim skole',
                    description: 'Moderne barneskole med godt læringsmiljø.'
                },
                {
                    title: 'Utdanningsløp',
                    description: 'Gode videregående skoler i området.'
                },
                {
                    title: 'Barnehager',
                    description: 'Flere barnehager med kort venteliste.'
                },
                {
                    title: 'Aktiviteter for barn',
                    description: 'Rik tilgang på organiserte aktiviteter.'
                }
            ]
        },
        
        fellesskap: {
            title: 'Velkommen over til fellesskapet',
            description: 'Et levende nabolag der folk kjenner hverandre og bryr seg om fellesskapet.',
            icon: 'location',
            cards: [
                {
                    title: 'Nabolagsmiljø',
                    description: 'Tett og vennlig nabolagsmiljø.'
                },
                {
                    title: 'Lokalsamfunn',
                    description: 'Aktive foreninger og frivillige organisasjoner.'
                },
                {
                    title: 'Møteplasser',
                    description: 'Naturlige møteplasser for sosialt samvær.'
                },
                {
                    title: 'Arrangementer',
                    description: 'Jevnlige lokale arrangementer og aktiviteter.'
                }
            ]
        }
    },
    
    // POI data for interactive maps
    mapData: {
        idrett: [
            {
                id: 'ranheim-il',
                name: 'Ranheim IL',
                position: { top: '25%', left: '35%' },
                title: 'Ranheim IL',
                description: 'Mest kjent for sin sterke satsning på breddefotball og toppfotball. Her kan du prøve å spille selv eller nyte spennende kamper fra tribunen.',
                metadata: ['Fotball', 'Basketball', 'Håndball'],
                clickable: true
            },
            {
                id: 'atletklubb',
                name: 'Atletklubb',
                position: { top: '60%', left: '70%' },
                title: 'Trondheim Atletklubb',
                description: 'Friidrett og løp for alle aldre og nivåer. Profesjonelle treningsanlegg og erfarne trenere.',
                metadata: ['Friidrett', 'Løp', 'Kast'],
                clickable: true
            },
            {
                id: 'svommeklubb',
                name: 'Svømming',
                position: { top: '40%', left: '15%' },
                title: 'Heimdal Svømmeklubb',
                description: 'Svømming og vannsport i nærområdet. Moderne anlegg med oppvarmet basseng.',
                metadata: ['Svømming', 'Vannsport', 'Basseng'],
                clickable: false
            },
            {
                id: 'ntnui',
                name: 'NTNUI',
                position: { top: '70%', left: '45%' },
                title: 'NTNUI',
                description: 'Studentidrett med stort utvalg av aktiviteter. Over 50 forskjellige idretter å velge mellom.',
                metadata: ['Studentidrett', '50+ idretter', 'Sosialt'],
                clickable: false
            }
        ]
    }
};

// Icon mappings for easier maintenance
const ICON_MAP = {
    location: `<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>`,
    shopping: `<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6.5"/><circle cx="9" cy="19" r="1"/><circle cx="20" cy="19" r="1"/>`,
    coffee: `<path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>`
};

// Performance configuration
const PERFORMANCE_CONFIG = {
    scrollThrottle: 16, // ~60fps
    resizeDebounce: 150,
    animationDuration: {
        modal: 400,
        scroll: 1200,
        button: 200
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SITE_CONFIG, ICON_MAP, PERFORMANCE_CONFIG };
}
