<script lang="ts">
	import type { ClothingCategory, StockItem } from '$lib/data/types';
	import StockItemCard from './StockItemCard.svelte';
	
	export let categories: ClothingCategory[] = [];
	export let stockItems: StockItem[] = [];
	export let loading: boolean = false;
	
	// カテゴリIDと在庫データをマッピング
	$: stockMap = new Map(stockItems.map(item => [item.categoryId, item]));
</script>

<div class="responsive-grid">
	{#each categories as category}
		<StockItemCard 
			{category} 
			stockItem={stockMap.get(category.id)}
			{loading}
		/>
	{/each}
</div>

<style>
	.responsive-grid {
		padding: var(--spacing-md, 1rem) 0;
		max-width: 100%;
	}
</style>