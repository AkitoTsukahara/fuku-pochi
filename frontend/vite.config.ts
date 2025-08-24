import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [sveltekit()],
	
	// ビルド最適化設定
	build: {
		// 本番環境での最適化
		target: 'es2022',
		cssTarget: 'chrome61',
		
		// バンドルサイズ最適化
		rollupOptions: {
			output: {
				manualChunks: {
					// ベンダーチャンクの分割でキャッシュ効率向上
					vendor: ['svelte', '@sveltejs/kit']
				}
			}
		},
		
		// ファイルサイズしきい値（KB）
		chunkSizeWarningLimit: 1000,
		
		// ソースマップは開発時のみ
		sourcemap: process.env.NODE_ENV === 'development',
		
		// 圧縮設定
		minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
		
		// レガシーブラウザサポートを削除（パフォーマンス向上）
		cssMinify: true
	},
	
	// 開発サーバー最適化
	server: {
		host: '0.0.0.0',
		port: 5173,
		strictPort: false
	},
	
	// 依存関係事前バンドル最適化
	optimizeDeps: {
		include: ['svelte', '@sveltejs/kit'],
		exclude: []
	},
	
	// キャッシュディレクトリ
	cacheDir: 'node_modules/.vite'
});
