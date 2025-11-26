import js from '@eslint/js';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                // WordPress globals
                wp: 'readonly',
                jQuery: 'readonly',
                $: 'readonly',
                // Mapbox
                mapboxgl: 'readonly',
                // Theme globals (localized via wp_localize_script)
                placyMapConfig: 'readonly',
                placyMapbox: 'readonly',
                bysykkelSettings: 'readonly',
                enturSettings: 'readonly',
                // Console (allow for debugging)
                console: 'readonly'
            }
        },
        rules: {
            // Code quality
            'no-unused-vars': ['warn', { 
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_'
            }],
            'no-undef': 'error',
            'no-console': 'off', // Allow console for debugging
            
            // Best practices
            'eqeqeq': ['error', 'always', { null: 'ignore' }],
            'no-var': 'warn',
            'prefer-const': 'warn',
            
            // Style (relaxed for existing code)
            'indent': ['warn', 4, { SwitchCase: 1 }],
            'quotes': ['warn', 'single', { avoidEscape: true }],
            'semi': ['error', 'always'],
            'comma-dangle': ['warn', 'never'],
            'no-trailing-spaces': 'warn',
            'no-multiple-empty-lines': ['warn', { max: 2 }],
            
            // JSDoc
            'valid-jsdoc': 'off', // Deprecated, use eslint-plugin-jsdoc if needed
            
            // Functions
            'no-inner-declarations': 'off', // Allow function declarations in blocks (IIFE pattern)
            'no-loop-func': 'warn'
        }
    },
    {
        // Ignore minified files
        ignores: ['**/*.min.js', '**/node_modules/**', '**/vendor/**']
    }
];
