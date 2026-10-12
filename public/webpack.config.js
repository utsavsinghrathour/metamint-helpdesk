// start of file
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

module.exports = {
  ...defaultConfig,
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules.map((rule) => {
        if (rule.test && rule.test.toString().includes("jsx")) {
          return {
            ...rule,
            include: undefined,
            exclude: /node_modules/,
            use: {
              loader: require.resolve("babel-loader"),
              options: {
                presets: [
                  [
                    require.resolve("@babel/preset-react"),
                    { runtime: "automatic" },
                  ],
                  require.resolve("@babel/preset-env"),
                ],
                babelrc: false,
                browserslistrc: false,
              },
            },
          };
        }
        return rule;
      }),
    ],
  },
};
