/**
 * Build configuration for bud.js
 * @param {import('@roots/bud').Bud} bud
 */
export default async bud => {
    /**
     * The bud.js instance
     */
    bud

        .config({
            output: {
                chunkLoadingGlobal: 'dynamic-forms',
            },
        })

        /**
         * Set the project source directory
         */
        .setPath(`@src`, `resources`)

        /**
         * Set the application entrypoints
         * These paths are expressed relative to the `@src` directory
         */
        .entry({
            "dynamic-forms-frontend": [`/frontend/js/app.js`, `/frontend/scss/app.scss`], // [`./sources/app.js`, `./sources/app.css`]
            "dynamic-forms-admin": [`/admin/js/app.js`, `/admin/scss/app.scss`]
        })

        .provide({
            jquery: ['$', 'jQuery'],
        })

        .copyFile(['../node_modules/@easepick/bundle/dist/index.css', `css/easepicker.css`])
        .copyFile(['frontend/scss/easepicker.custom.css', `css`])

        /**
         * Copy static assets from `sources/static` to `dist/static`
         */
        .assets({
            from: bud.path(`@src/static`),
            to: bud.path(`@dist/static`),
            noErrorOnMissing: true,
        })
        .splitChunks()
        .minimize(bud.isProduction)
        .proxy(false) // Disable since we are using ddev
}
