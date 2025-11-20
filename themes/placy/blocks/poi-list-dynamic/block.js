/**
 * POI List Dynamic Block - Editor Script
 */

(function (blocks, element, blockEditor, components) {
    const el = element.createElement;
    const { InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, ToggleControl, SelectControl, TextControl, RangeControl, CheckboxControl } = components;

    blocks.registerBlockType('placy/poi-list-dynamic', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { 
                placesEnabled,
                placesCategory,
                placesKeyword,
                placesRadius,
                placesMinRating,
                placesMinReviews,
                placesExcludeTypes
            } = attributes;

            const blockProps = useBlockProps();

            // Helper functions for exclude types array
            const toggleExcludeType = function(type) {
                const currentTypes = placesExcludeTypes || [];
                const newTypes = currentTypes.includes(type)
                    ? currentTypes.filter(function(t) { return t !== type; })
                    : currentTypes.concat([type]);
                setAttributes({ placesExcludeTypes: newTypes });
            };

            const isTypeExcluded = function(type) {
                return (placesExcludeTypes || []).includes(type);
            };

            return el(
                'div',
                blockProps,
                [
                    // Sidebar controls
                    el(
                        InspectorControls,
                        { key: 'inspector' },
                        el(
                            PanelBody,
                            { title: 'Google Places API - Søkeparametere', initialOpen: true },
                            el(ToggleControl, {
                                label: 'Aktiver Google Places API',
                                help: 'Vis dynamiske steder fra Google Places API',
                                checked: placesEnabled,
                                onChange: function (value) {
                                    setAttributes({ placesEnabled: value });
                                },
                            }),
                            placesEnabled && [
                                el(SelectControl, {
                                    key: 'category',
                                    label: 'Kategori',
                                    help: 'Velg type sted å søke etter',
                                    value: placesCategory,
                                    onChange: function (value) {
                                        setAttributes({ placesCategory: value });
                                    },
                                    options: [
                                        { label: 'Restaurant', value: 'restaurant' },
                                        { label: 'Cafe', value: 'cafe' },
                                        { label: 'Bar', value: 'bar' },
                                        { label: 'Bakery', value: 'bakery' },
                                        { label: 'Takeaway', value: 'meal_takeaway' },
                                        { label: 'Food (generelt)', value: 'food' },
                                        { label: 'Apotek', value: 'pharmacy' },
                                        { label: 'Tannlege', value: 'dentist' },
                                        { label: 'Lege', value: 'doctor' },
                                        { label: 'Sykehus', value: 'hospital' },
                                        { label: 'Fysioterapeut', value: 'physiotherapist' },
                                        { label: 'Butikk', value: 'store' },
                                        { label: 'Supermarked', value: 'supermarket' },
                                        { label: 'Gym', value: 'gym' },
                                        { label: 'Spa', value: 'spa' },
                                        { label: 'Skjønnhetssalong', value: 'beauty_salon' },
                                        { label: 'Frisør', value: 'hair_care' },
                                        { label: 'Museum', value: 'museum' },
                                        { label: 'Kunstgalleri', value: 'art_gallery' },
                                        { label: 'Teater', value: 'performing_arts_theater' },
                                        { label: 'Kino', value: 'movie_theater' },
                                        { label: 'Park', value: 'park' },
                                        { label: 'Turistattraksjon', value: 'tourist_attraction' }
                                    ],
                                }),
                                el(TextControl, {
                                    key: 'keyword',
                                    label: 'Søkeord (valgfritt)',
                                    help: 'Type sted å søke etter',
                                    value: placesKeyword,
                                    onChange: function (value) {
                                        setAttributes({ placesKeyword: value });
                                    },
                                    placeholder: 'F.eks. "pizza", "sushi", "fine dining"',
                                }),
                                el(RangeControl, {
                                    key: 'radius',
                                    label: 'Søkeradius (meter)',
                                    help: 'Hvor langt fra sentrum skal vi søke?',
                                    value: placesRadius,
                                    onChange: function (value) {
                                        setAttributes({ placesRadius: value });
                                    },
                                    min: 500,
                                    max: 3000,
                                    step: 100,
                                }),
                                el(RangeControl, {
                                    key: 'rating',
                                    label: 'Minimum rating',
                                    help: 'Laveste godkjente rating (0-5)',
                                    value: placesMinRating,
                                    onChange: function (value) {
                                        setAttributes({ placesMinRating: value });
                                    },
                                    min: 3.0,
                                    max: 5.0,
                                    step: 0.1,
                                }),
                                el(RangeControl, {
                                    key: 'reviews',
                                    label: 'Minimum antall anmeldelser',
                                    help: 'Minimum antall Google-anmeldelser',
                                    value: placesMinReviews,
                                    onChange: function (value) {
                                        setAttributes({ placesMinReviews: value });
                                    },
                                    min: 0,
                                    max: 200,
                                    step: 10,
                                }),
                                el('hr', { key: 'divider', style: { margin: '16px 0' } }),
                                el('p', { key: 'exclude-title', style: { fontWeight: 'bold', marginBottom: '4px' } }, 'Ekskluder typer (valgfritt)'),
                                el('p', { 
                                    key: 'exclude-help', 
                                    style: { 
                                        fontSize: '12px', 
                                        color: '#666', 
                                        marginTop: '-8px',
                                        marginBottom: '12px'
                                    } 
                                }, 'Velg hvilke type steder som skal filtreres bort fra resultatene'),
                                el(CheckboxControl, {
                                    key: 'exclude-lodging',
                                    label: 'Hoteller (lodging)',
                                    checked: isTypeExcluded('lodging'),
                                    onChange: function () {
                                        toggleExcludeType('lodging');
                                    },
                                }),
                                el(CheckboxControl, {
                                    key: 'exclude-hospital',
                                    label: 'Sykehus/Apotek (hospital, pharmacy)',
                                    checked: isTypeExcluded('hospital') || isTypeExcluded('pharmacy'),
                                    onChange: function () {
                                        toggleExcludeType('hospital');
                                        toggleExcludeType('pharmacy');
                                    },
                                }),
                                el(CheckboxControl, {
                                    key: 'exclude-transport',
                                    label: 'Transport (gas_station, car_rental, parking)',
                                    checked: isTypeExcluded('gas_station') || isTypeExcluded('car_rental') || isTypeExcluded('parking'),
                                    onChange: function () {
                                        toggleExcludeType('gas_station');
                                        toggleExcludeType('car_rental');
                                        toggleExcludeType('parking');
                                    },
                                }),
                            ]
                        )
                    ),

                    // Block preview in editor
                    el(
                        'div',
                        {
                            key: 'preview',
                            style: {
                                padding: '20px',
                                backgroundColor: '#f0f0f0',
                                border: '2px dashed #ccc',
                                borderRadius: '4px',
                                textAlign: 'center'
                            }
                        },
                        [
                            el('p', { 
                                key: 'title',
                                style: { margin: 0, fontWeight: 'bold' } 
                            }, 'POI List Dynamic (Google Places API)'),
                            el('p', { 
                                key: 'config',
                                style: { margin: '8px 0 0', fontSize: '14px', color: '#666' } 
                            }, 'Kategori: ' + placesCategory + (placesKeyword ? ' | Søkeord: "' + placesKeyword + '"' : '')),
                            el('p', { 
                                key: 'note',
                                style: { margin: '4px 0 0', fontSize: '12px', color: '#999' } 
                            }, 'Dynamiske resultater vises på frontend')
                        ]
                    )
                ]
            );
        },

        save: function () {
            return null; // Dynamic block rendered via PHP
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
