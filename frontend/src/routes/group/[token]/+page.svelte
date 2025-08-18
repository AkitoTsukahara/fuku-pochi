<script lang="ts">
	import type { PageData, ActionData } from './$types';
	import { enhance } from '$app/forms';
	import { page } from '$app/stores';
	import Header from '$lib/components/sections/Header.svelte';
	import ShareUrl from '$lib/components/forms/ShareUrl.svelte';
	import ChildrenList from '$lib/components/lists/ChildrenList.svelte';
	import Button from '$lib/components/elements/Button.svelte';
	
	export let data: PageData;
	export let form: ActionData;
	
	// 共有URLを構築
	$: shareUrl = `${$page.url.origin}/group/${data.token}`;
	
	let showAddChildForm = false;
	let addingChild = false;
	
	function toggleAddChildForm() {
		showAddChildForm = !showAddChildForm;
	}
</script>

<svelte:head>
	<title>{data.group.name} - ふくぽち</title>
	<meta name="description" content="{data.group.name}の衣類ストック管理" />
</svelte:head>

<div class="container">
	<Header 
		title="ふくぽち"
		subtitle="{data.group.name}の管理ページ"
	/>
	
	<div class="content">
		{#if data.isNewGroup}
			<!-- 新規グループの場合は共有URL表示 -->
			<ShareUrl {shareUrl} groupName={data.group.name} />
			
			<div class="next-action">
				<Button on:click={toggleAddChildForm} variant="primary">
					お子さまを追加する
				</Button>
			</div>
		{:else}
			<!-- 既存グループの場合は子ども一覧表示 -->
			<div class="group-info">
				<h2>{data.group.name}</h2>
				<div class="share-section-compact">
					<p>共有URL:</p>
					<input type="text" value={shareUrl} readonly on:click={(e) => e.currentTarget.select()} />
				</div>
			</div>
			
			<div class="children-section">
				<div class="section-header">
					<h3>お子さま一覧</h3>
					<Button on:click={toggleAddChildForm} variant="primary" size="small">
						お子さまを追加
					</Button>
				</div>
				
				<ChildrenList children={data.children} token={data.token} />
			</div>
		{/if}
		
		<!-- 子ども追加フォーム（モーダル風） -->
		{#if showAddChildForm}
			<div class="modal-overlay" on:click={toggleAddChildForm}>
				<div class="modal-content" on:click|stopPropagation>
					<h3>お子さまを追加</h3>
					<form 
						method="POST" 
						action="?/addChild" 
						use:enhance={() => {
							addingChild = true;
							return async ({ result, update }) => {
								addingChild = false;
								if (result.type === 'success') {
									showAddChildForm = false;
								}
								update();
							};
						}}
					>
						<div class="form-group">
							<label for="childName">お子さまの名前</label>
							<input
								type="text"
								id="childName"
								name="name"
								placeholder="例: たろう"
								required
								minlength="1"
								maxlength="100"
								disabled={addingChild}
							/>
							{#if form?.errors?.name}
								<p class="error">{form.errors.name}</p>
							{/if}
						</div>
						
						<div class="form-actions">
							<Button type="button" variant="outline" on:click={toggleAddChildForm} disabled={addingChild}>
								キャンセル
							</Button>
							<Button type="submit" variant="primary" disabled={addingChild}>
								{addingChild ? '追加中...' : '追加'}
							</Button>
						</div>
						
						{#if form?.error}
							<p class="error general-error">{form.error}</p>
						{/if}
					</form>
				</div>
			</div>
		{/if}
		
		{#if form?.success}
			<div class="success-message">
				{form.message}
			</div>
		{/if}
	</div>
</div>

<style>
	.content {
		padding: 2rem 1rem;
		max-width: 800px;
		margin: 0 auto;
	}
	
	.group-info {
		background: white;
		border-radius: 12px;
		padding: 1.5rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		margin-bottom: 2rem;
	}
	
	.group-info h2 {
		color: #333;
		font-size: 1.5rem;
		margin: 0 0 1rem;
		font-weight: 600;
	}
	
	.share-section-compact {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		font-size: 0.9rem;
	}
	
	.share-section-compact p {
		color: #666;
		margin: 0;
		flex-shrink: 0;
	}
	
	.share-section-compact input {
		flex: 1;
		padding: 0.5rem;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
		font-size: 0.85rem;
		font-family: monospace;
		background: #fafafa;
	}
	
	.children-section {
		background: white;
		border-radius: 12px;
		padding: 1.5rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	}
	
	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1.5rem;
	}
	
	.section-header h3 {
		color: #333;
		font-size: 1.2rem;
		margin: 0;
		font-weight: 600;
	}
	
	
	.next-action {
		text-align: center;
		margin-top: 2rem;
	}
	
	/* モーダルスタイル */
	.modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
		padding: 1rem;
	}
	
	.modal-content {
		background: white;
		border-radius: 12px;
		padding: 2rem;
		max-width: 500px;
		width: 100%;
		max-height: 90vh;
		overflow-y: auto;
	}
	
	.modal-content h3 {
		color: #333;
		font-size: 1.3rem;
		margin: 0 0 1.5rem;
		font-weight: 600;
	}
	
	.form-group {
		margin-bottom: 1.5rem;
	}
	
	.form-group label {
		display: block;
		margin-bottom: 0.5rem;
		color: #333;
		font-weight: 500;
		font-size: 0.95rem;
	}
	
	.form-group input {
		width: 100%;
		padding: 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 1rem;
		transition: border-color 0.2s;
		background: #fafafa;
	}
	
	.form-group input:focus {
		outline: none;
		border-color: #87ceeb;
		background: white;
	}
	
	.form-actions {
		display: flex;
		gap: 1rem;
		justify-content: flex-end;
	}
	
	.error {
		color: #d32f2f;
		font-size: 0.875rem;
		margin-top: 0.5rem;
	}
	
	.general-error {
		margin-top: 1rem;
		padding: 0.75rem;
		background: #ffebee;
		border-radius: 4px;
	}
	
	.success-message {
		position: fixed;
		bottom: 2rem;
		left: 50%;
		transform: translateX(-50%);
		background: #4caf50;
		color: white;
		padding: 1rem 2rem;
		border-radius: 8px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		animation: slideUp 0.3s ease;
		z-index: 100;
	}
	
	@keyframes slideUp {
		from {
			transform: translateX(-50%) translateY(100%);
			opacity: 0;
		}
		to {
			transform: translateX(-50%) translateY(0);
			opacity: 1;
		}
	}
	
	@media (max-width: 600px) {
		.content {
			padding: 1rem;
		}
		
		.modal-content {
			padding: 1.5rem;
		}
		
		.share-section-compact {
			flex-direction: column;
			align-items: stretch;
			gap: 0.5rem;
		}
		
		.share-section-compact input {
			width: 100%;
		}
	}
</style>