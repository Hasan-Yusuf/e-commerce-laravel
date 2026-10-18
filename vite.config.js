import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            // The foundation is one entry: `my-app.css` imports the tokens,
            // the layers and the shared components itself, so only what is
            // loaded on demand belongs here.
            //
            // `resources/css/app.css` (the Tailwind entry) is deliberately not
            // an input: the storefront loads `my-app.css` and neither it nor
            // the Filament admin registers Tailwind, so building it only
            // produced an unused asset.
            input: [
                "resources/css/my-app.css",
                // Feature stylesheets: pushed by the page that needs one.
                "resources/css/features/catalog.css",
                "resources/css/features/filters.css",
                "resources/css/features/product-page.css",
                "resources/css/features/search.css",
                "resources/css/features/cart.css",
                "resources/css/features/wishlist.css",
                "resources/css/features/auth.css",
                "resources/css/features/checkout.css",
                "resources/css/features/orders.css",
                "resources/css/features/account.css",
                "resources/css/features/contact.css",
                "resources/js/app.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
