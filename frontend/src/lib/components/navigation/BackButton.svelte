<script lang="ts">
	import { browser } from '$app/environment';
	import { goto } from '$app/navigation';
	
	export let href: string = '';
	export let fallbackText: string = '戻る';
	export let showText: boolean = true;
	
	function handleBack() {
		if (browser && window.history.length > 1) {
			// ブラウザの履歴がある場合は戻る
			window.history.back();
		} else if (href) {
			// fallbackのURLに移動
			goto(href);
		}
	}
</script>

<button 
	class="back-button touch-target"
	on:click={handleBack}
	aria-label="前のページに戻る"
>
	<svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
		<path d="M19 12H5M12 19l-7-7 7-7"/>
	</svg>
	{#if showText}
		<span class="back-text">{fallbackText}</span>
	{/if}
</button>

<style>
	.back-button {
		display: inline-flex;
		align-items: center;
		gap: var(--spacing-sm, 0.5rem);
		background: none;
		border: none;
		color: #4a90e2;
		font-size: var(--text-sm, 0.95rem);
		padding: var(--spacing-sm, 0.5rem);
		border-radius: clamp(4px, 1.5vw, 6px);
		cursor: pointer;
		transition: all 0.2s;
		min-height: 44px;
		-webkit-tap-highlight-color: transparent;
		user-select: none;
	}
	
	.back-button:hover {
		background: rgba(74, 144, 226, 0.1);
		color: #357abd;
	}
	
	.back-button:active {
		transform: scale(0.98);
		background: rgba(74, 144, 226, 0.15);
	}
	
	.back-icon {
		width: 18px;
		height: 18px;
		flex-shrink: 0;
	}
	
	.back-text {
		font-weight: 500;
	}
	
	/* モバイル最適化 */
	@media (max-width: 480px) {
		.back-button {
			padding: var(--spacing-xs, 0.25rem) var(--spacing-sm, 0.5rem);
		}
		
		.back-icon {
			width: 20px;
			height: 20px;
		}
	}
	
	/* reduced motion対応 */
	@media (prefers-reduced-motion: reduce) {
		.back-button {
			transition: none;
		}
		
		.back-button:active {
			transform: none;
		}
	}
</style>