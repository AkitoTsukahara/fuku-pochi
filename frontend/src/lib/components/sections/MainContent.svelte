<script lang="ts">
	import StockGrid from '../blocks/StockGrid.svelte';
	import type { ClothingCategory, StockItem } from '$lib/data/types.js';
	
	export let categories: ClothingCategory[] = [];
	export let stockData: StockItem[] = [];
	export let onStockChange: (categoryId: string, newValue: number) => void = () => {};
</script>

<main class="main-content">
	<StockGrid 
		{categories}
		{stockData}
		{onStockChange}
	/>
</main>

<style>
	.main-content {
		flex: 1;
		background-color: white;
		border-radius: 16px 16px 0 0;
		margin-top: 1rem;
		box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
		min-height: 0; /* flexboxでの縮小を許可 */
	}

	@media (min-width: 768px) {
		.main-content {
			border-radius: 16px;
			margin: 2rem 0;
		}
	}
</style>