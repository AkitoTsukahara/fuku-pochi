import { defineConfig } from 'vitest/config';

export default defineConfig({
	test: {
		include: ['src/**/*.{test,spec}.{js,ts}'],
		environment: 'jsdom',
		globals: true,
		setupFiles: ['src/lib/test-setup.ts'],
		coverage: {
			reporter: ['text', 'html', 'lcov'],
			exclude: ['node_modules/', 'src/lib/test-setup.ts']
		}
	},
	resolve: {
		alias: {
			'$lib': '/src/lib',
			'$app': '/src/app'
		}
	}
});