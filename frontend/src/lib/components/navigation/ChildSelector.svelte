<script lang="ts">
	import type { Child } from '$lib/data/types';
	import { page } from '$app/stores';
	
	export let children: Child[] = [];
	export let currentChildId: string;
	export let token: string;
	
	$: currentChild = children.find(child => child.id === currentChildId);
</script>

{#if children.length > 1}
	<div class="child-selector">
		<label for="child-select" class="selector-label">
			子どもを選択:
		</label>
		
		<select 
			id="child-select"
			class="selector-dropdown"
			value={currentChildId}
			on:change={(e) => {
				const newChildId = e.currentTarget.value;
				if (newChildId !== currentChildId) {
					// ページ遷移
					window.location.href = `/group/${token}/child/${newChildId}`;
				}
			}}
		>
			{#each children as child}
				<option value={child.id} selected={child.id === currentChildId}>
					{child.name}
				</option>
			{/each}
		</select>
		
		<!-- 代替ナビゲーション（ボタン形式） -->
		<div class="selector-buttons">
			{#each children as child}
				<a 
					href="/group/{token}/child/{child.id}"
					class="child-button"
					class:active={child.id === currentChildId}
					aria-current={child.id === currentChildId ? 'page' : undefined}
				>
					<span class="child-icon">👶</span>
					<span class="child-name">{child.name}</span>
				</a>
			{/each}
		</div>
	</div>
{:else if currentChild}
	<div class="child-header">
		<span class="child-icon">👶</span>
		<span class="child-name">{currentChild.name}さんの在庫</span>
	</div>
{/if}

<style>
	.child-selector {
		background: white;
		border-radius: 12px;
		padding: 1rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
		margin-bottom: 1.5rem;
	}
	
	.selector-label {
		display: block;
		color: #333;
		font-weight: 600;
		font-size: 0.9rem;
		margin-bottom: 0.5rem;
	}
	
	.selector-dropdown {
		width: 100%;
		padding: 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 1rem;
		background: white;
		cursor: pointer;
		transition: border-color 0.2s;
		margin-bottom: 1rem;
	}
	
	.selector-dropdown:focus {
		outline: none;
		border-color: #4a90e2;
	}
	
	.selector-buttons {
		display: flex;
		gap: 0.5rem;
		flex-wrap: wrap;
	}
	
	.child-button {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 0.75rem;
		border-radius: 20px;
		text-decoration: none;
		transition: all 0.2s;
		border: 2px solid #e0e0e0;
		background: #f8f8f8;
		color: #666;
		font-size: 0.9rem;
	}
	
	.child-button:hover {
		border-color: #87ceeb;
		background: white;
		color: #333;
	}
	
	.child-button.active {
		border-color: #4a90e2;
		background: #4a90e2;
		color: white;
	}
	
	.child-icon {
		font-size: 1.1rem;
	}
	
	.child-name {
		font-weight: 500;
	}
	
	.child-header {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		background: white;
		border-radius: 12px;
		padding: 1rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
		margin-bottom: 1.5rem;
	}
	
	.child-header .child-icon {
		font-size: 1.5rem;
	}
	
	.child-header .child-name {
		color: #333;
		font-weight: 600;
		font-size: 1.1rem;
	}
	
	/* モバイル対応 */
	@media (max-width: 600px) {
		.selector-dropdown {
			font-size: 16px; /* iOS Safariのズーム防止 */
		}
		
		.child-button {
			flex: 1;
			justify-content: center;
			min-width: calc(50% - 0.25rem);
		}
	}
</style>