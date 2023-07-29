import {defineConfig} from 'vite';

export default defineConfig({
  build: {
    lib          : {
      entry: './src/admin.js',
      name : 'vite-admin',
    },
    sourcemap    : true,
    minify       : 'terser',
    terserOptions: {
      compress: true,
      output  : {
        comments: false,
      },
    },
    outDir       : './build-admin/',
    emptyOutDir  : true,
    rollupOptions: {
      output: {
        entryFileNames: `js/[name].js`,
        chunkFileNames: `js/[name].js`,
        assetFileNames: (assetInfo) => {
          let extType = assetInfo.name.split('.').
                                  at(1);
          if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
            extType = 'img';
          }
          // if extType is 'css' return 'admin.css'
          let fileName = extType === 'css' ? 'admin' : assetInfo.name;
          return `${ extType }/${ fileName }[extname]`;
        },
      },
    },
  },
});
