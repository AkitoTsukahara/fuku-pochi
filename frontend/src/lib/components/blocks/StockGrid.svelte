<script lang="ts">
	import StockItem from './StockItem.svelte';
	import type { ClothingCategory, StockItem as StockData } from '$lib/data/types.js';
	
	export let categories: ClothingCategory[] = [];
	export let stockData: StockData[] = [];
	export let onStockChange: (categoryId: string, newValue: number) => void = () => {};

	function getStockQuantity(categoryId: string): number {
		const item = stockData.find(stock => stock.categoryId === categoryId);
		return item?.quantity || 0;
	}

	function handleStockChange(categoryId: string, newValue: number) {
		onStockChange(categoryId, newValue);
	}
</script>

<div class="stock-grid">
	{#each categories as category}
		<StockItem 
			itemName={category.name}
			currentStock={getStockQuantity(category.id)}
			iconSrc={category.iconPath || ''}
			onStockChange={(newValue) => handleStockChange(category.id, newValue)}
		/>
	{/each}
</div>

<style>
	.stock-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
		gap: 1rem;
		padding: 1rem;
	}

	@media (min-width: 480px) {
		.stock-grid {
			grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
			gap: 1.25rem;
		}
	}

	@media (min-width: 768px) {
		.stock-grid {
			grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
			gap: 1.5rem;
		}
	}

	@media (min-width: 1024px) {
		.stock-grid {
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 2rem;
		}
	}
</style>