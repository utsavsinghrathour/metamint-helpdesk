// start of file
const webpack = require("webpack");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

class MmhdStripEmojiCdn {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap("MmhdStripEmojiCdn", (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: "MmhdStripEmojiCdn",
          stage: webpack.Compilation.PROCESS_ASSETS_STAGE_OPTIMIZE_SIZE,
        },
        (assets) => {
          for (const name of Object.keys(assets)) {
            if (!/\.js(\?.*)?$/.test(name)) continue;
            const source = assets[name].source().toString();
            if (!source.includes("cdn.jsdelivr.net/npm/emoji-datasource")) {
              continue;
            }
            const cleaned = source.replace(
              /https:\/\/cdn\.jsdelivr\.net\/npm\/emoji-datasource-[a-z]+\/img\/[a-z]+\/64\//g,
              "",
            );
            compilation.updateAsset(
              name,
              new webpack.sources.RawSource(cleaned),
            );
          }
        },
      );
    });
  }
}

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
  plugins: [...(defaultConfig.plugins || []), new MmhdStripEmojiCdn()],
};
