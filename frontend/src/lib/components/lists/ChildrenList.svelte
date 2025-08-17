<script lang="ts">
	import { enhance } from '$app/forms';
	import type { Child } from '$lib/data/types';
	import Button from '$lib/components/elements/Button.svelte';
	import DeleteConfirmDialog from '$lib/components/dialogs/DeleteConfirmDialog.svelte';
	
	export let children: Child[] = [];
	export let token: string;
	
	let editingChildId: string | null = null;
	let editingName: string = '';
	let deleteTargetChild: Child | null = null;
	let showDeleteDialog = false;
	let deletingChildId: string | null = null;
	let updatingChildId: string | null = null;
	
	function startEdit(child: Child) {
		editingChildId = child.id;
		editingName = child.name;
	}
	
	function cancelEdit() {
		editingChildId = null;
		editingName = '';
	}
	
	function confirmDelete(child: Child) {
		deleteTargetChild = child;
		showDeleteDialog = true;
	}
	
	function handleDeleteConfirm() {
		if (deleteTargetChild) {
			// フォームをプログラマティックに送信
			const form = document.getElementById(`delete-form-${deleteTargetChild.id}`) as HTMLFormElement;
			if (form) {
				form.requestSubmit();
			}
		}
	}
</script>

<div class="children-list">
	{#if children.length > 0}
		<div class="list-container">
			{#each children as child}
				<div class="child-item" class:editing={editingChildId === child.id}>
					{#if editingChildId === child.id}
						<!-- 編集モード -->
						<form 
							method="POST" 
							action="?/updateChild"
							use:enhance={() => {
								updatingChildId = child.id;
								return async ({ result, update }) => {
									updatingChildId = null;
									if (result.type === 'success') {
										editingChildId = null;
										editingName = '';
									}
									update();
								};
							}}
						>
							<input type="hidden" name="childId" value={child.id} />
							<div class="edit-container">
								<input
									type="text"
									name="name"
									bind:value={editingName}
									placeholder="子どもの名前"
									required
									minlength="1"
									maxlength="100"
									disabled={updatingChildId === child.id}
									class="edit-input"
								/>
								<div class="edit-actions">
									<Button 
										type="submit" 
										variant="primary" 
										size="sm"
										disabled={updatingChildId === child.id || editingName.trim() === ''}
									>
										{updatingChildId === child.id ? '更新中...' : '保存'}
									</Button>
									<Button 
										type="button" 
										variant="outline" 
										size="sm"
										on:click={cancelEdit}
										disabled={updatingChildId === child.id}
									>
										キャンセル
									</Button>
								</div>
							</div>
						</form>
					{:else}
						<!-- 表示モード -->
						<a href="/group/{token}/child/{child.id}" class="child-link">
							<div class="child-icon">👶</div>
							<div class="child-info">
								<h4>{child.name}</h4>
								<p>タップして在庫管理へ</p>
							</div>
						</a>
						<div class="item-actions">
							<button
								type="button"
								class="action-btn edit-btn"
								on:click|preventDefault={() => startEdit(child)}
								title="名前を編集"
								disabled={deletingChildId === child.id}
							>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
									<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
								</svg>
							</button>
							<button
								type="button"
								class="action-btn delete-btn"
								on:click|preventDefault={() => confirmDelete(child)}
								title="削除"
								disabled={deletingChildId === child.id}
							>
								{#if deletingChildId === child.id}
									<span class="spinner"></span>
								{:else}
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="3 6 5 6 21 6"></polyline>
										<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
										<line x1="10" y1="11" x2="10" y2="17"></line>
										<line x1="14" y1="11" x2="14" y2="17"></line>
									</svg>
								{/if}
							</button>
						</div>
					{/if}
				</div>
				
				<!-- 削除用の隠しフォーム -->
				<form 
					id="delete-form-{child.id}"
					method="POST" 
					action="?/deleteChild"
					style="display: none;"
					use:enhance={() => {
						deletingChildId = child.id;
						return async ({ update }) => {
							deletingChildId = null;
							deleteTargetChild = null;
							update();
						};
					}}
				>
					<input type="hidden" name="childId" value={child.id} />
				</form>
			{/each}
		</div>
	{:else}
		<div class="empty-state">
			<p>まだ子どもが登録されていません</p>
		</div>
	{/if}
</div>

<DeleteConfirmDialog
	bind:show={showDeleteDialog}
	title="{deleteTargetChild?.name}さんを削除"
	message="この子どもを削除してもよろしいですか？"
	warningMessage="この操作は取り消せません。登録されている在庫データもすべて削除されます。"
	confirmText="削除する"
	onConfirm={handleDeleteConfirm}
/>

<style>
	.children-list {
		width: 100%;
	}
	
	.list-container {
		display: grid;
		gap: 0.75rem;
	}
	
	.child-item {
		background: #f8f8f8;
		border-radius: 8px;
		border: 2px solid transparent;
		transition: all 0.2s;
		display: flex;
		align-items: center;
		padding: 0.75rem;
	}
	
	.child-item:hover:not(.editing) {
		background: white;
		border-color: #87ceeb;
	}
	
	.child-item.editing {
		background: white;
		border-color: #4a90e2;
		padding: 1rem;
	}
	
	.child-link {
		display: flex;
		align-items: center;
		gap: 1rem;
		text-decoration: none;
		flex: 1;
	}
	
	.child-icon {
		font-size: 2rem;
		flex-shrink: 0;
	}
	
	.child-info {
		flex: 1;
	}
	
	.child-info h4 {
		color: #333;
		font-size: 1.1rem;
		margin: 0 0 0.25rem;
		font-weight: 600;
	}
	
	.child-info p {
		color: #666;
		font-size: 0.875rem;
		margin: 0;
	}
	
	.item-actions {
		display: flex;
		gap: 0.5rem;
		flex-shrink: 0;
	}
	
	.action-btn {
		width: 36px;
		height: 36px;
		border-radius: 6px;
		border: none;
		background: white;
		color: #666;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.2s;
		padding: 0;
	}
	
	.action-btn:hover:not(:disabled) {
		transform: scale(1.05);
	}
	
	.action-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}
	
	.action-btn svg {
		width: 18px;
		height: 18px;
	}
	
	.edit-btn:hover:not(:disabled) {
		background: #e3f2fd;
		color: #1976d2;
	}
	
	.delete-btn:hover:not(:disabled) {
		background: #ffebee;
		color: #c62828;
	}
	
	/* 編集モード */
	.edit-container {
		width: 100%;
		display: flex;
		gap: 0.75rem;
		align-items: center;
	}
	
	.edit-input {
		flex: 1;
		padding: 0.5rem 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 6px;
		font-size: 1rem;
		transition: border-color 0.2s;
		background: white;
	}
	
	.edit-input:focus {
		outline: none;
		border-color: #4a90e2;
	}
	
	.edit-input:disabled {
		background: #f5f5f5;
		opacity: 0.7;
	}
	
	.edit-actions {
		display: flex;
		gap: 0.5rem;
		flex-shrink: 0;
	}
	
	/* 空の状態 */
	.empty-state {
		text-align: center;
		padding: 3rem 1rem;
		color: #999;
		font-size: 0.95rem;
	}
	
	/* スピナー */
	.spinner {
		display: inline-block;
		width: 16px;
		height: 16px;
		border: 2px solid #f3f3f3;
		border-top: 2px solid #666;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}
	
	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	
	@media (max-width: 600px) {
		.child-item {
			padding: 0.75rem 0.5rem;
		}
		
		.child-item.editing {
			padding: 0.75rem;
		}
		
		.edit-container {
			flex-direction: column;
			align-items: stretch;
		}
		
		.edit-actions {
			width: 100%;
		}
		
		.edit-actions :global(.btn) {
			flex: 1;
		}
	}
</style>