<script lang="ts">
	import Button from '$lib/components/elements/Button.svelte';
	
	export let show: boolean = false;
	export let title: string = '削除の確認';
	export let message: string = 'この操作は取り消せません。本当に削除しますか？';
	export let warningMessage: string = '';
	export let confirmText: string = '削除';
	export let cancelText: string = 'キャンセル';
	export let onConfirm: () => void;
	export let onCancel: () => void = () => { show = false; };
	
	function handleConfirm() {
		onConfirm();
		show = false;
	}
	
	function handleCancel() {
		onCancel();
		show = false;
	}
	
	function handleOverlayClick(event: MouseEvent) {
		if (event.target === event.currentTarget) {
			handleCancel();
		}
	}
</script>

{#if show}
	<div class="dialog-overlay" on:click={handleOverlayClick} role="presentation">
		<div class="dialog-content" role="dialog" aria-labelledby="dialog-title" aria-modal="true">
			<h3 id="dialog-title">{title}</h3>
			
			<div class="dialog-body">
				<p class="message">{message}</p>
				
				{#if warningMessage}
					<div class="warning-box">
						<svg class="warning-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
						</svg>
						<p class="warning-message">{warningMessage}</p>
					</div>
				{/if}
			</div>
			
			<div class="dialog-actions">
				<Button variant="outline" on:click={handleCancel}>
					{cancelText}
				</Button>
				<Button variant="primary" on:click={handleConfirm}>
					{confirmText}
				</Button>
			</div>
		</div>
	</div>
{/if}

<style>
	.dialog-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 2000;
		padding: 1rem;
		animation: fadeIn 0.2s ease;
	}
	
	.dialog-content {
		background: white;
		border-radius: 12px;
		padding: 1.5rem;
		max-width: 400px;
		width: 100%;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		animation: slideUp 0.3s ease;
	}
	
	h3 {
		color: #333;
		font-size: 1.25rem;
		margin: 0 0 1rem;
		font-weight: 600;
	}
	
	.dialog-body {
		margin-bottom: 1.5rem;
	}
	
	.message {
		color: #666;
		font-size: 0.95rem;
		line-height: 1.5;
		margin: 0 0 1rem;
	}
	
	.warning-box {
		background: #fff3cd;
		border: 1px solid #ffc107;
		border-radius: 8px;
		padding: 0.75rem;
		display: flex;
		gap: 0.5rem;
		align-items: flex-start;
	}
	
	.warning-icon {
		width: 20px;
		height: 20px;
		color: #f57c00;
		flex-shrink: 0;
		margin-top: 2px;
	}
	
	.warning-message {
		color: #856404;
		font-size: 0.875rem;
		line-height: 1.4;
		margin: 0;
	}
	
	.dialog-actions {
		display: flex;
		gap: 0.75rem;
		justify-content: flex-end;
	}
	
	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}
	
	@keyframes slideUp {
		from {
			transform: translateY(20px);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}
	
	@media (max-width: 480px) {
		.dialog-content {
			padding: 1.25rem;
		}
		
		.dialog-actions {
			flex-direction: column-reverse;
		}
		
		.dialog-actions :global(.btn) {
			width: 100%;
		}
	}
</style>