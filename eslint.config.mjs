import plesk from '@plesk/eslint-config';

export default [
    plesk,
    {
        languageOptions: {
            globals: {
                // Plesk JavaScript library, loaded by the Plesk page.
                Jsw: 'readonly',
            },
        },
        settings: {
            // The project has no Jest. The shared config enables Jest rules that need a version.
            jest: {
                version: 29,
            },
            react: {
                pragma: 'createElement',
            },
        },
        rules: {
            'react/prop-types': 0,
        },
    },
];
