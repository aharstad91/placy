/**
 * Chapter Wrapper Block - Editor Script
 */

(function (blocks, element, blockEditor, components) {
    const el = element.createElement;
    const { InspectorControls, InnerBlocks, useBlockProps } = blockEditor;
    const { PanelBody, TextControl, ToggleControl, SelectControl, RangeControl, CheckboxControl } = components;

    // Allowed blocks inside chapter wrapper
    const ALLOWED_BLOCKS = [
        'core/heading',
        'core/paragraph',
        'core/image',
        'core/list',
        'core/quote',
        'core/spacer',
        'core/group',
        'core/columns',
        'core/column',
        'acf/poi-list',
        'placy/poi-list-dynamic',
        'acf/poi-highlight',
        'acf/poi-gallery',
        'acf/image-column',
        'acf/proximity-filter',
        'acf/travel-calculator',
        'acf/chapter-index',
        'acf/proximity-timeline',
        'acf/travel-mode-selector',
        'acf/chapter-heading',
        'acf/chapter-text',
        'acf/chapter-image',
        'acf/chapter-list',
        'acf/chapter-spacer',
        'acf/focus-panel',
        'acf/chip-scrollytelling',
        'acf/feature-spotlight',
    ];

    // Template for new chapters
    const TEMPLATE = [
        ['core/heading', { level: 2, placeholder: 'Kapittel tittel...' }],
        ['core/paragraph', { placeholder: 'Skriv kapittelets introduksjonstekst her...' }],
    ];

    blocks.registerBlockType('placy/chapter-wrapper', {
        deprecated: [
            {
                // Version without map grid (pre multi-map)
                attributes: {
                    chapterId: {
                        type: 'string',
                        default: ''
                    },
                    chapterAnchor: {
                        type: 'string',
                        default: ''
                    },
                    chapterTitle: {
                        type: 'string',
                        default: ''
                    }
                },
                save: function (props) {
                    const { attributes } = props;
                    const { chapterId, chapterAnchor, chapterTitle } = attributes;
                    
                    const blockProps = useBlockProps.save({
                        className: 'chapter',
                        id: chapterAnchor || chapterId || undefined,
                        'data-chapter-id': chapterId || '',
                        'data-chapter-anchor': chapterAnchor || '',
                        'data-chapter-title': chapterTitle || '',
                    });

                    return el(
                        'section',
                        blockProps,
                        el(InnerBlocks.Content)
                    );
                },
                migrate: function(attributes, innerBlocks) {
                    // CRITICAL: Must preserve both attributes AND innerBlocks to prevent data loss
                    return [attributes, innerBlocks];
                }
            },
            {
                // Old version that used client-side save() with section tags
                attributes: {
                    chapterId: {
                        type: 'string',
                        default: ''
                    },
                    chapterAnchor: {
                        type: 'string',
                        default: ''
                    },
                    chapterTitle: {
                        type: 'string',
                        default: ''
                    }
                },
                save: function () {
                    return el(
                        'section',
                        { className: 'chapter wp-block-placy-chapter-wrapper' },
                        el(InnerBlocks.Content)
                    );
                },
                migrate: function(attributes, innerBlocks) {
                    // CRITICAL: Must preserve both attributes AND innerBlocks to prevent data loss
                    return [attributes, innerBlocks];
                }
            }
        ],
        
        edit: function (props) {
            const { attributes, setAttributes, clientId } = props;
            const { 
                chapterId, 
                chapterAnchor, 
                chapterTitle,
                placesEnabled,
                placesCategory,
                placesKeyword,
                placesRadius,
                placesMinRating,
                placesMinReviews,
                placesExcludeTypes
            } = attributes;
            
            // Auto-generate chapter ID on first render if not set
            const { useEffect } = element;
            const { select } = window.wp.data;
            
            useEffect(function() {
                if (!chapterId) {
                    // Count existing chapter-wrapper blocks
                    const blocks = select('core/block-editor').getBlocks();
                    let chapterCount = 0;
                    
                    function countChapters(blockList) {
                        blockList.forEach(function(block) {
                            if (block.name === 'placy/chapter-wrapper') {
                                chapterCount++;
                            }
                            if (block.innerBlocks && block.innerBlocks.length > 0) {
                                countChapters(block.innerBlocks);
                            }
                        });
                    }
                    
                    countChapters(blocks);
                    setAttributes({ chapterId: 'chapter-' + chapterCount });
                }
            }, []);

            const blockProps = useBlockProps({
                className: 'chapter',
                'data-chapter-id': chapterId || 'chapter-new',
            });

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
                            { title: 'Kapittel Innstillinger', initialOpen: true },
                            el(TextControl, {
                                label: 'Kapittel ID',
                                help: 'Unikt ID for dette kapittelet (f.eks. "chapter-1", "chapter-2")',
                                value: chapterId,
                                onChange: function (value) {
                                    setAttributes({ chapterId: value });
                                },
                                placeholder: 'chapter-1',
                            }),
                            el(TextControl, {
                                label: 'Kapittel Anchor (URL-slug)',
                                help: 'Semantisk ankerpunkt for navigasjon (automatisk lowercase og bindestreker)',
                                value: chapterAnchor,
                                onChange: function (value) {
                                    // Auto-format: lowercase, replace spaces with hyphens, remove special chars
                                    const formatted = value
                                        .toLowerCase()
                                        .replace(/\s+/g, '-')           // Replace spaces with hyphens
                                        .replace(/[æ]/g, 'ae')          // Norwegian characters
                                        .replace(/[ø]/g, 'o')
                                        .replace(/[å]/g, 'a')
                                        .replace(/[^a-z0-9-]/g, '')     // Remove non-alphanumeric except hyphens
                                        .replace(/-+/g, '-')            // Replace multiple hyphens with single
                                        .replace(/^-|-$/g, '');         // Trim hyphens from start/end
                                    
                                    setAttributes({ chapterAnchor: formatted });
                                },
                                placeholder: 'michelin-dining',
                            }),
                            el(TextControl, {
                                label: 'Kapittel Tittel (navigasjon)',
                                help: 'Tittel som vises i navigasjonsmenyen',
                                value: chapterTitle,
                                onChange: function (value) {
                                    setAttributes({ chapterTitle: value });
                                },
                                placeholder: 'Michelin & Fine Dining',
                            }),
                            el(TextControl, {
                                label: 'Nav Label (kort)',
                                help: 'Kort label for sticky TOC-navbar (f.eks. "Transport", "Bygget")',
                                value: attributes.chapterNavLabel || '',
                                onChange: function (value) {
                                    setAttributes({ chapterNavLabel: value });
                                },
                                placeholder: 'Transport',
                            }),
                            el(ToggleControl, {
                                label: 'Vis kart',
                                help: 'Vis kart ved siden av kapittelinnholdet',
                                checked: attributes.showMap !== false,
                                onChange: function (value) {
                                    setAttributes({ showMap: value });
                                }
                            })
                        ),
                        el(
                            PanelBody,
                            { title: 'Google Places API Innstillinger', initialOpen: false },
                            el(ToggleControl, {
                                label: 'Aktiver Google Places API',
                                help: 'Vis dynamiske steder fra Google Places API',
                                checked: placesEnabled,
                                onChange: function (value) {
                                    setAttributes({ placesEnabled: value });
                                }
                            }),
                            placesEnabled && el(SelectControl, {
                                label: 'Kategori',
                                value: placesCategory,
                                options: [
                                    { label: 'Restaurant', value: 'restaurant' },
                                    { label: 'Cafe', value: 'cafe' },
                                    { label: 'Bar', value: 'bar' },
                                    { label: 'Bakery', value: 'bakery' },
                                    { label: 'Museum', value: 'museum' },
                                    { label: 'Tourist Attraction', value: 'tourist_attraction' },
                                ],
                                onChange: function (value) {
                                    setAttributes({ placesCategory: value });
                                }
                            }),
                            placesEnabled && el(TextControl, {
                                label: 'Søkeord (keyword)',
                                help: 'F.eks. "fine dining", "seafood", "michelin" - forbedrer søkeresultatene',
                                value: placesKeyword,
                                onChange: function (value) {
                                    setAttributes({ placesKeyword: value });
                                },
                                placeholder: 'fine dining'
                            }),
                            placesEnabled && el(RangeControl, {
                                label: 'Søkeradius (meter)',
                                value: placesRadius,
                                onChange: function (value) {
                                    setAttributes({ placesRadius: value });
                                },
                                min: 500,
                                max: 5000,
                                step: 100
                            }),
                            placesEnabled && el(RangeControl, {
                                label: 'Minimum Rating',
                                value: placesMinRating,
                                onChange: function (value) {
                                    setAttributes({ placesMinRating: value });
                                },
                                min: 0,
                                max: 5,
                                step: 0.1
                            }),
                            placesEnabled && el(RangeControl, {
                                label: 'Minimum Antall Anmeldelser',
                                value: placesMinReviews,
                                onChange: function (value) {
                                    setAttributes({ placesMinReviews: value });
                                },
                                min: 0,
                                max: 500,
                                step: 10
                            })
                        )
                    ),

                    // Chapter info indicator in editor
                    el(
                        'div',
                        {
                            key: 'chapter-id-display',
                            style: {
                                fontSize: '0.875rem',
                                fontWeight: '600',
                                color: '#76908D',
                                marginBottom: '1rem',
                                padding: '0.75rem',
                                backgroundColor: '#f8f9fa',
                                borderRadius: '0.375rem',
                                border: '1px solid #e5e7eb',
                            },
                        },
                        [
                            el('div', { key: 'id', style: { marginBottom: '0.25rem' } }, 
                                'ID: ' + (chapterId || '(ikke satt)')
                            ),
                            el('div', { key: 'anchor', style: { marginBottom: '0.25rem' } }, 
                                'Anchor: ' + (chapterAnchor || '(ikke satt)')
                            ),
                            el('div', { key: 'title' }, 
                                'Nav Tittel: ' + (chapterTitle || '(ikke satt)')
                            ),
                        ]
                    ),

                    // Inner blocks
                    el(InnerBlocks, {
                        key: 'innerblocks',
                        allowedBlocks: ALLOWED_BLOCKS,
                        template: TEMPLATE,
                        templateLock: false,
                    }),
                ]
            );
        },

        // Save function must return InnerBlocks.Content to preserve inner blocks
        // Even though we use render.php for server-side rendering, we MUST save the inner blocks
        // otherwise WordPress migration will DELETE all inner content
        save: function () {
            return el(InnerBlocks.Content);
        },
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
