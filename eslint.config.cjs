/**
 * ESLint flat config (migrated from .eslintrc).
 *
 * Extends the default config of @wordpress/scripts.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
				jQuery: 'readonly',
			},
		},
		rules: {
			'no-alert': 'off',
			'prettier/prettier': 'off',
			'object-shorthand': 'off',
			'jsdoc/require-jsdoc': 'off',
			'jsdoc/no-undefined-types': 'off',
			'react/react-in-jsx-scope': 'off',
			'react/jsx-sort-props': 'off',
			'jsdoc/check-tag-names': 'off',
			'import/order': 'off',
			'no-prototype-builtins': 'off',
			'object-curly-newline': 'off',
			'object-property-newline': 'off',
			yoda: 'off',
			strict: 'off',
			'@wordpress/valid-sprintf': 'off',
		},
	},
];
