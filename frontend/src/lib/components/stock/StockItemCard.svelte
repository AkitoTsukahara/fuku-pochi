<script lang="ts">
	import { enhance } from '$app/forms';
	import type { ClothingCategory, StockItem } from '$lib/data/types';
	import Button from '$lib/components/elements/Button.svelte';
	
	export let category: ClothingCategory;
	export let stockItem: StockItem | undefined = undefined;
	export let loading: boolean = false;
	
	// アイコンマッピング（絵文字を使用）
	const categoryIcons: Record<string, string> = {
		'1': '👕',  // Tシャツ
		'2': '👖',  // ズボン
		'3': '🧦',  // 靴下
		'4': '🤏',  // ハンカチ
		'5': '👙',  // 肌着
		'6': '👒',  // ぼうし
		'7': '🩱',  // 水着セット
		'8': '🛍️'   // ビニール袋
	};
	
	$: currentStock = stockItem?.quantity || 0;
	$: icon = categoryIcons[category.id] || '👕';
	$: canDecrement = currentStock > 0;
	
	let incrementing = false;
	let decrementing = false;
</script>

<div class="stock-card">
	<div class="stock-header">
		<div class="category-icon" role="img" aria-label={category.name}>
			{icon}
		</div>
		<div class="stock-count" class:zero={currentStock === 0}>
			{currentStock}
		</div>
	</div>
	
	<h3 class="category-name">{category.name}</h3>
	
	<div class="stock-controls">
		<form 
			method="POST" 
			action="?/decrementStock"
			use:enhance={() => {
				decrementing = true;
				return async ({ update }) => {
					decrementing = false;
					update();
				};
			}}
		>
			<input type="hidden" name="categoryId" value={category.id} />
			<Button 
				type="submit" 
				variant="outline" 
				size="sm"
				disabled={!canDecrement || decrementing || incrementing || loading}
				aria-label="{category.name}の在庫を1つ減らす"
			>
				{#if decrementing}
					<span class="spinner"></span>
				{:else}
					−
				{/if}
			</Button>
		</form>
		
		<form 
			method="POST" 
			action="?/incrementStock"
			use:enhance={() => {
				incrementing = true;
				return async ({ update }) => {
					incrementing = false;
					update();
				};
			}}
		>
			<input type="hidden" name="categoryId" value={category.id} />
			<Button 
				type="submit" 
				variant="primary" 
				size="sm"
				disabled={incrementing || decrementing || loading}
				aria-label="{category.name}の在庫を1つ増やす"
			>
				{#if incrementing}
					<span class="spinner"></span>
				{:else}
					＋
				{/if}
			</Button>
		</form>
	</div>
</div>

<style>
	.stock-card {
		background: white;
		border-radius: 16px;
		padding: 1.25rem 1rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
		border: 2px solid #f0f0f0;
		transition: all 0.2s ease;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.75rem;
		min-height: 160px;
		position: relative;
	}
	
	.stock-card:hover {
		border-color: #87ceeb;
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
	}
	
	.stock-header {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
	}
	
	.category-icon {
		font-size: 2.5rem;
		line-height: 1;
		filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
	}
	
	.stock-count {
		background: #4a90e2;
		color: white;
		font-size: 1.25rem;
		font-weight: 600;
		padding: 0.25rem 0.75rem;
		border-radius: 20px;
		min-width: 50px;
		text-align: center;
		box-shadow: 0 2px 6px rgba(74, 144, 226, 0.3);
	}
	
	.stock-count.zero {
		background: #999;
		box-shadow: 0 2px 6px rgba(153, 153, 153, 0.3);
	}
	
	.category-name {
		color: #333;
		font-size: 0.95rem;
		font-weight: 600;
		text-align: center;
		margin: 0;
		line-height: 1.3;
	}
	
	.stock-controls {
		display: flex;
		gap: 0.5rem;
		margin-top: auto;
		width: 100%;
		justify-content: center;
	}
	
	.stock-controls form {
		display: flex;
	}
	
	.stock-controls :global(.btn) {
		width: 44px;
		height: 44px;
		border-radius: 12px;
		font-size: 1.25rem;
		font-weight: 600;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}
	
	.stock-controls :global(.btn:active) {
		transform: scale(0.95);
	}
	
	.stock-controls :global(.btn:disabled) {
		transform: none;
		opacity: 0.5;
	}
	
	/* スピナー */
	.spinner {
		display: inline-block;
		width: 16px;
		height: 16px;
		border: 2px solid rgba(255, 255, 255, 0.3);
		border-top: 2px solid white;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}
	
	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	
	/* アクセシビリティ: reduced motionの場合はアニメーションを無効化 */
	@media (prefers-reduced-motion: reduce) {
		.stock-card {
			transition: none;
		}
		
		.stock-card:hover {
			transform: none;
		}
		
		.spinner {
			animation: none;
		}
	}
	
	/* レスポンシブ調整 */
	@media (max-width: 400px) {
		.stock-card {
			padding: 1rem 0.75rem;
			min-height: 140px;
		}
		
		.category-icon {
			font-size: 2rem;
		}
		
		.stock-count {
			font-size: 1.1rem;
			padding: 0.2rem 0.6rem;
		}
		
		.category-name {
			font-size: 0.9rem;
		}
		
		.stock-controls :global(.btn) {
			width: 40px;
			height: 40px;
			font-size: 1.1rem;
		}
	}
</style>