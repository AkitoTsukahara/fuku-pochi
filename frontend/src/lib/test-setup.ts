import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock SvelteKit's environment
vi.mock('$app/environment', () => ({
	browser: true
}));

// Mock SvelteKit's forms
vi.mock('$app/forms', () => ({
	enhance: vi.fn(() => ({ destroy: vi.fn() }))
}));

// Mock environment variables
Object.defineProperty(import.meta, 'env', {
	value: {
		VITE_API_BASE_URL: 'http://localhost:8000/api',
		VITE_API_BASE_URL_SSR: 'http://localhost:8000/api'
	},
	writable: true,
});

// Mock fetch for API calls
global.fetch = vi.fn();