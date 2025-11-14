/**
 * Chapter Wrapper Block - Editor Script
 */

(function (blocks, element, blockEditor, components) {
    const el = element.createElement;
    const { InspectorControls, InnerBlocks, useBlockProps } = blockEditor;
    const { PanelBody, TextControl } = components;

    // Allowed blocks inside chapter wrapper
    const ALLOWED_BLOCKS = [
        'core/heading',
        'core/paragraph',
        'core/image',
        'core/list',
        'core/quote',
        'core/spacer',
        'acf/poi-list',
    ];

    // Template for new chapters
    const TEMPLATE = [
        ['core/heading', { level: 2, placeholder: 'Kapittel tittel...' }],
        ['core/paragraph', { placeholder: 'Skriv kapittelets introduksjonstekst her...' }],
        ['acf/poi-list', {}],
    ];

    blocks.registerBlockType('placy/chapter-wrapper', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { chapterId } = attributes;

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
                            })
                        )
                    ),

                    // Chapter ID indicator in editor
                    el(
                        'div',
                        {
                            key: 'chapter-id-display',
                            style: {
                                fontSize: '0.875rem',
                                fontWeight: '600',
                                color: '#76908D',
                                marginBottom: '1rem',
                                textTransform: 'uppercase',
                                letterSpacing: '0.05em',
                            },
                        },
                        chapterId ? 'Kapittel: ' + chapterId : 'Kapittel: (Sett ID i høyre sidebar)'
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

        save: function () {
            const blockProps = useBlockProps.save({
                className: 'chapter',
            });

            return el(
                'section',
                blockProps,
                el(InnerBlocks.Content)
            );
        },
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
