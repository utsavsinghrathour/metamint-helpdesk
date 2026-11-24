// start of file
const webpack = require("webpack");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

/**
 * Remove remote emoji image URLs from the built bundle.
 *
 * The emoji picker ships with optional remote image URLs for its non-native
 * styles. The plugin only uses native system emoji, so those URLs are never
 * needed. Stripping them at build time keeps the distributed bundle free of
 * external asset references.
 */
class MmhdStripRemoteEmojiAssets {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap("MmhdStripRemoteEmojiAssets", (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: "MmhdStripRemoteEmojiAssets",
          stage: webpack.Compilation.PROCESS_ASSETS_STAGE_OPTIMIZE_SIZE,
        },
        (assets) => {
          for (const name of Object.keys(assets)) {
            if (!/\.js(\?.*)?$/.test(name)) continue;
            const source = assets[name].source().toString();
            // Match the remote emoji sprite path shape without naming any
            // particular third-party host in the build configuration.
            const cleaned = source.replace(
              /https?:\/\/[^"'\\\s]+\/img\/[a-z]+\/64\//g,
              "",
            );
            if (cleaned === source) continue;
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
  plugins: [...(defaultConfig.plugins || []), new MmhdStripRemoteEmojiAssets()],
};
