<script lang="ts">
	import { enhance } from '$app/forms';
	import Button from '$lib/components/elements/Button.svelte';
	import type { Child } from '$lib/data/types';
	
	export let child: Child | null = null;
	export let form: any = null;
	export let loading: boolean = false;
	export let mode: 'create' | 'edit' = 'create';
	
	$: isEditing = mode === 'edit' && child !== null;
	$: action = isEditing ? '?/updateChild' : '?/addChild';
	$: buttonText = isEditing ? '更新' : '追加';
	$: loadingText = isEditing ? '更新中...' : '追加中...';
	$: title = isEditing ? `${child?.name}さんの編集` : '新しいお子さまを追加';
	
	let nameValue = child?.name || '';
	
	// お子さまが変更されたら名前を更新
	$: if (child) {
		nameValue = child.name;
	}
</script>

<div class="child-form">
	<h3>{title}</h3>
	
	<form method="POST" {action} use:enhance>
		{#if isEditing && child}
			<input type="hidden" name="childId" value={child.id} />
		{/if}
		
		<div class="form-group">
			<label for="childName">お子さまの名前</label>
			<input
				type="text"
				id="childName"
				name="name"
				bind:value={nameValue}
				placeholder="例: たろう"
				required
				minlength="1"
				maxlength="100"
				disabled={loading}
			/>
			{#if form?.errors?.name}
				<p class="error">{form.errors.name}</p>
			{/if}
		</div>
		
		<div class="form-actions">
			<Button type="submit" variant="primary" disabled={loading || !nameValue.trim()}>
				{loading ? loadingText : buttonText}
			</Button>
		</div>
		
		{#if form?.error}
			<p class="error general-error">{form.error}</p>
		{/if}
		
		{#if form?.success}
			<p class="success">{form.message}</p>
		{/if}
	</form>
</div>

<style>
	.child-form {
		background: white;
		border-radius: 12px;
		padding: 1.5rem;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		max-width: 400px;
	}
	
	h3 {
		color: #333;
		font-size: 1.2rem;
		margin: 0 0 1.5rem;
		font-weight: 600;
	}
	
	.form-group {
		margin-bottom: 1.5rem;
	}
	
	label {
		display: block;
		margin-bottom: 0.5rem;
		color: #333;
		font-weight: 500;
		font-size: 0.95rem;
	}
	
	input {
		width: 100%;
		padding: 0.75rem;
		border: 2px solid #e0e0e0;
		border-radius: 8px;
		font-size: 1rem;
		transition: border-color 0.2s;
		background: #fafafa;
	}
	
	input:focus {
		outline: none;
		border-color: #87ceeb;
		background: white;
	}
	
	input:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	
	.form-actions {
		display: flex;
		justify-content: flex-end;
	}
	
	.form-actions :global(.btn) {
		min-width: 100px;
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
	
	.success {
		color: #2e7d32;
		font-size: 0.875rem;
		margin-top: 1rem;
		padding: 0.75rem;
		background: #e8f5e8;
		border-radius: 4px;
	}
	
	@media (max-width: 480px) {
		.child-form {
			padding: 1.25rem;
		}
		
		.form-actions :global(.btn) {
			width: 100%;
		}
	}
</style>