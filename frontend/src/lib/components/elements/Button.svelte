<script lang="ts">
	export let variant: 'primary' | 'secondary' | 'icon' | 'outline' = 'primary';
	export let size: 'sm' | 'md' | 'lg' | 'small' = 'md';
	export let disabled: boolean = false;
	export let type: 'button' | 'submit' | 'reset' = 'button';
	
	// sizeのaliasを処理
	$: actualSize = size === 'small' ? 'sm' : size;
</script>

<button 
	class="btn btn--{variant} btn--{actualSize}" 
	{disabled}
	{type}
	on:click
>
	<slot />
</button>

<style>
	.btn {
		border: none;
		border-radius: clamp(6px, 2vw, 8px);
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-weight: 500;
		transition: all 0.2s ease;
		/* タッチフレンドリーな最小サイズ */
		min-height: 44px;
		min-width: 44px;
		/* タップ時の視覚的フィードバック */
		user-select: none;
		-webkit-tap-highlight-color: transparent;
	}

	.btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.btn:active:not(:disabled) {
		transform: scale(0.98);
	}

	.btn--sm {
		padding: var(--spacing-sm, 8px) var(--spacing-md, 16px);
		font-size: var(--text-sm, 14px);
		min-height: 40px;
		min-width: 40px;
	}

	.btn--md {
		padding: var(--spacing-md, 12px) var(--spacing-lg, 24px);
		font-size: var(--text-base, 16px);
	}

	.btn--lg {
		padding: var(--spacing-lg, 16px) var(--spacing-xl, 32px);
		font-size: var(--text-lg, 18px);
		min-height: 52px;
	}

	.btn--primary {
		background-color: #4a90e2;
		color: white;
		box-shadow: 0 2px 4px rgba(74, 144, 226, 0.2);
	}

	.btn--primary:hover:not(:disabled) {
		background-color: #357abd;
		box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
	}

	.btn--secondary {
		background-color: #f5f5f5;
		color: #333;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.btn--secondary:hover:not(:disabled) {
		background-color: #e0e0e0;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
	}

	.btn--icon {
		padding: var(--spacing-sm, 8px);
		border-radius: 50%;
		background-color: transparent;
		color: #666;
		min-width: 44px;
		min-height: 44px;
	}

	.btn--icon:hover:not(:disabled) {
		background-color: #f0f0f0;
	}
	
	.btn--outline {
		background-color: transparent;
		color: #4a90e2;
		border: 2px solid #4a90e2;
		box-shadow: none;
	}
	
	.btn--outline:hover:not(:disabled) {
		background-color: #4a90e2;
		color: white;
		box-shadow: 0 2px 4px rgba(74, 144, 226, 0.2);
	}

	/* モバイルでのタッチ最適化 */
	@media (pointer: coarse) {
		.btn {
			min-height: 48px;
			min-width: 48px;
		}
		
		.btn--sm {
			min-height: 44px;
			min-width: 44px;
		}
		
		.btn--lg {
			min-height: 56px;
		}
	}

	/* 横向き表示での調整 */
	@media (orientation: landscape) and (max-width: 767px) {
		.btn {
			min-height: 40px;
		}
		
		.btn--lg {
			min-height: 44px;
		}
	}

	/* reduced motion対応 */
	@media (prefers-reduced-motion: reduce) {
		.btn {
			transition: none;
		}
		
		.btn:active:not(:disabled) {
			transform: none;
		}
	}
</style>