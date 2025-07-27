<script lang="ts">
	import Header from '$lib/components/sections/Header.svelte';
	import MainContent from '$lib/components/sections/MainContent.svelte';
	import { CLOTHING_CATEGORIES } from '$lib/data/clothing-categories.js';
	import type { StockItem } from '$lib/data/types.js';
	
	// Sample data for demonstration
	const currentChild = { id: 'child-1', name: 'たろう' };
	
	// Simple state management without stores
	let stockData: StockItem[] = [];
	
	function handleStockChange(categoryId: string, newValue: number) {
		const existingIndex = stockData.findIndex(item => item.categoryId === categoryId);
		
		if (existingIndex >= 0) {
			stockData[existingIndex] = {
				...stockData[existingIndex],
				quantity: newValue,
				updatedAt: new Date().toISOString()
			};
		} else {
			stockData.push({
				categoryId,
				quantity: newValue,
				updatedAt: new Date().toISOString()
			});
		}
		
		// Reactivity trigger
		stockData = stockData;
	}
</script>

<svelte:head>
	<title>ふくぽち - 衣類ストック管理</title>
</svelte:head>

<div class="container">
	<Header 
		title="ふくぽち"
		subtitle="おつかれさまです！{currentChild.name}くんの衣類ストックを管理しましょう"
	/>
	
	<MainContent 
		categories={CLOTHING_CATEGORIES}
		{stockData}
		onStockChange={handleStockChange}
	/>
</div>

<style>
	/* Container styles moved to global app.css */
</style>
