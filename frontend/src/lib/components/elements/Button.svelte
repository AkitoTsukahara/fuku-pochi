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
		border-radius: 8px;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-weight: 500;
		transition: all 0.2s ease;
	}

	.btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.btn--sm {
		padding: 8px 16px;
		font-size: 14px;
	}

	.btn--md {
		padding: 12px 24px;
		font-size: 16px;
	}

	.btn--lg {
		padding: 16px 32px;
		font-size: 18px;
	}

	.btn--primary {
		background-color: #4a90e2;
		color: white;
	}

	.btn--primary:hover:not(:disabled) {
		background-color: #357abd;
	}

	.btn--secondary {
		background-color: #f5f5f5;
		color: #333;
	}

	.btn--secondary:hover:not(:disabled) {
		background-color: #e0e0e0;
	}

	.btn--icon {
		padding: 8px;
		border-radius: 50%;
		background-color: transparent;
		color: #666;
	}

	.btn--icon:hover:not(:disabled) {
		background-color: #f0f0f0;
	}
	
	.btn--outline {
		background-color: transparent;
		color: #4a90e2;
		border: 2px solid #4a90e2;
	}
	
	.btn--outline:hover:not(:disabled) {
		background-color: #4a90e2;
		color: white;
	}
</style>