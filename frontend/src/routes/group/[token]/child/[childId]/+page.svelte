<script lang="ts">
	import type { PageData, ActionData } from './$types';
	import { enhance } from '$app/forms';
	import { page } from '$app/stores';
	import Header from '$lib/components/sections/Header.svelte';
	import ChildSelector from '$lib/components/navigation/ChildSelector.svelte';
	import StockGrid from '$lib/components/stock/StockGrid.svelte';
	import Button from '$lib/components/elements/Button.svelte';
	
	export let data: PageData;
	export let form: ActionData;
	
	let globalLoading = false;
	
	// 成功メッセージの表示制御
	let showSuccessMessage = false;
	let successMessage = '';
	
	// フォームアクションの結果を監視
	$: if (form?.success) {
		successMessage = getActionMessage(form.action, form.categoryId);
		showSuccessMessage = true;
		setTimeout(() => {
			showSuccessMessage = false;
		}, 2000);
	}
	
	function getActionMessage(action: string, categoryId: string): string {
		const category = data.categories.find(c => c.id === categoryId);
		const categoryName = category?.name || 'アイテム';
		
		if (action === 'increment') {
			return `${categoryName}を1つ追加しました`;
		} else if (action === 'decrement') {
			return `${categoryName}を1つ減らしました`;
		}
		return '在庫を更新しました';
	}
</script>

<svelte:head>
	<title>{data.child.name}さんの在庫管理 - ふくぽち</title>
	<meta name="description" content="{data.child.name}さんの衣類在庫を管理" />
</svelte:head>

<div class="container">
	<Header 
		title="ふくぽち"
		subtitle="{data.child.name}さんの在庫管理"
	/>
	
	<div class="content">
		<!-- 戻るボタン -->
		<div class="navigation">
			<a href="/group/{data.token}" class="back-link">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M19 12H5M12 19l-7-7 7-7"/>
				</svg>
				グループに戻る
			</a>
		</div>
		
		<!-- 子ども選択（複数いる場合のみ表示） -->
		<ChildSelector 
			children={data.allChildren}
			currentChildId={data.childId}
			token={data.token}
		/>
		
		<!-- 在庫グリッド -->
		<div class="stock-section">
			<div class="section-header">
				<h2>衣類在庫</h2>
				<p class="section-description">
					＋ / − ボタンで在庫数を調整できます
				</p>
			</div>
			
			<StockGrid 
				categories={data.categories}
				stockItems={data.stockItems}
				loading={globalLoading}
			/>
		</div>
		
		<!-- 最終更新時刻 -->
		{#if data.stockItems.length > 0}
			{@const lastUpdated = data.stockItems.reduce((latest, item) => {
				const itemDate = new Date(item.updatedAt);
				return itemDate > latest ? itemDate : latest;
			}, new Date(0))}
			
			<div class="last-updated">
				最終更新: {lastUpdated.toLocaleString('ja-JP')}
			</div>
		{/if}
		
		<!-- エラーメッセージ -->
		{#if form?.error}
			<div class="error-message">
				{form.error}
			</div>
		{/if}
	</div>
</div>

<!-- 成功メッセージ -->
{#if showSuccessMessage}
	<div class="toast-message" class:show={showSuccessMessage}>
		<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="12" cy="12" r="10"></circle>
			<path d="M8 12l2 2 4-4"></path>
		</svg>
		{successMessage}
	</div>
{/if}

<style>
	.content {
		padding: 1rem;
		max-width: 800px;
		margin: 0 auto;
		padding-bottom: 2rem;
	}
	
	.navigation {
		margin-bottom: 1rem;
	}
	
	.back-link {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		color: #4a90e2;
		text-decoration: none;
		font-size: 0.95rem;
		padding: 0.5rem 0;
		transition: color 0.2s;
	}
	
	.back-link:hover {
		color: #357abd;
	}
	
	.back-link svg {
		width: 18px;
		height: 18px;
	}
	
	.stock-section {
		background: white;
		border-radius: 16px;
		padding: 1.5rem;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
		margin-bottom: 2rem;
	}
	
	.section-header {
		text-align: center;
		margin-bottom: 1.5rem;
	}
	
	.section-header h2 {
		color: #333;
		font-size: 1.4rem;
		margin: 0 0 0.5rem;
		font-weight: 600;
	}
	
	.section-description {
		color: #666;
		font-size: 0.9rem;
		margin: 0;
	}
	
	.last-updated {
		text-align: center;
		color: #999;
		font-size: 0.85rem;
		padding: 1rem;
		background: #f8f8f8;
		border-radius: 8px;
		margin-bottom: 1rem;
	}
	
	.error-message {
		background: #ffebee;
		color: #c62828;
		padding: 1rem;
		border-radius: 8px;
		margin: 1rem 0;
		text-align: center;
		font-size: 0.9rem;
		border: 1px solid #ffcdd2;
	}
	
	/* トーストメッセージ */
	.toast-message {
		position: fixed;
		bottom: 2rem;
		left: 50%;
		transform: translateX(-50%) translateY(100px);
		background: #4caf50;
		color: white;
		padding: 1rem 1.5rem;
		border-radius: 12px;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.9rem;
		font-weight: 500;
		z-index: 1000;
		opacity: 0;
		transition: all 0.3s ease;
		max-width: calc(100vw - 2rem);
		text-align: center;
	}
	
	.toast-message.show {
		opacity: 1;
		transform: translateX(-50%) translateY(0);
	}
	
	.toast-icon {
		width: 20px;
		height: 20px;
		flex-shrink: 0;
	}
	
	/* アニメーション最適化 */
	@media (prefers-reduced-motion: reduce) {
		.toast-message {
			transition: opacity 0.2s ease;
		}
		
		.toast-message.show {
			transform: translateX(-50%);
		}
	}
	
	/* モバイル最適化 */
	@media (max-width: 600px) {
		.content {
			padding: 0.75rem;
		}
		
		.stock-section {
			padding: 1rem;
		}
		
		.section-header h2 {
			font-size: 1.25rem;
		}
		
		.toast-message {
			bottom: 1rem;
			left: 1rem;
			right: 1rem;
			transform: translateY(100px);
			max-width: none;
		}
		
		.toast-message.show {
			transform: translateY(0);
		}
	}
</style>