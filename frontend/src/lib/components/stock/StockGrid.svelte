<script lang="ts">
	import type { ClothingCategory, StockItem } from '$lib/data/types';
	import StockItemCard from './StockItemCard.svelte';
	
	export let categories: ClothingCategory[] = [];
	export let stockItems: StockItem[] = [];
	export let loading: boolean = false;
	
	// カテゴリIDと在庫データをマッピング
	$: stockMap = new Map(stockItems.map(item => [item.categoryId, item]));
</script>

<div class="stock-grid">
	{#each categories as category}
		<StockItemCard 
			{category} 
			stockItem={stockMap.get(category.id)}
			{loading}
		/>
	{/each}
</div>

<style>
	.stock-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
		gap: 1rem;
		padding: 1rem 0;
		max-width: 100%;
	}
	
	/* スマートフォン: 2列表示 */
	@media (max-width: 480px) {
		.stock-grid {
			grid-template-columns: repeat(2, 1fr);
			gap: 0.75rem;
		}
	}
	
	/* タブレット: 3-4列表示 */
	@media (min-width: 481px) and (max-width: 768px) {
		.stock-grid {
			grid-template-columns: repeat(3, 1fr);
		}
	}
	
	/* デスクトップ: 4列表示 */
	@media (min-width: 769px) {
		.stock-grid {
			grid-template-columns: repeat(4, 1fr);
			max-width: 600px;
			margin: 0 auto;
		}
	}
</style>