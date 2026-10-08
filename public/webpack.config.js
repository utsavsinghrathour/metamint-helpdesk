const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules.map((rule) => {
        // Find the babel-loader rule for JS/JSX files
        if (rule.test && rule.test.toString().includes('jsx')) {
          return {
            ...rule,
            // Remove the include restriction to allow processing external files
            include: undefined,
            // Only exclude node_modules
            exclude: /node_modules/,
            // Explicitly set babel options
            use: {
              loader: require.resolve('babel-loader'),
              options: {
                presets: [
                  [
                    require.resolve('@babel/preset-react'),
                    {
                      runtime: 'automatic',
                    },
                  ],
                  require.resolve('@babel/preset-env'),
                ],
                babelrc: false, // Don't use .babelrc, use this config
              },
            },
          };
        }
        return rule;
      }),
    ],
  },
};
