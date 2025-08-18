<script lang="ts">
	import type { ActionData } from './$types';
	import Header from '$lib/components/sections/Header.svelte';
	import GroupForm from '$lib/components/forms/GroupForm.svelte';
	
	export let form: ActionData;
</script>

<svelte:head>
	<title>ふくぽち - 衣類ストック管理</title>
	<meta name="description" content="保育園児の衣類ストックを家族で共有管理" />
</svelte:head>

<div class="container">
	<Header 
		title="ふくぽち"
		subtitle="保育園の衣類ストックを家族でかんたん管理"
	/>
	
	<div class="welcome-section">
		<div class="welcome-message">
			<h2>ようこそ！ふくぽちへ</h2>
			<p>保育園に預けるお子さまの衣類ストック状況を、夫婦で共有しやすく管理できるアプリです。</p>
			<p>まずは家族のグループを作成しましょう。</p>
		</div>
		
		<GroupForm {form} />
		
		<div class="features">
			<h3>ふくぽちの特徴</h3>
			<div class="feature-list">
				<div class="feature-item">
					<span class="feature-icon">👕</span>
					<div>
						<h4>かんたん在庫管理</h4>
						<p>＋/−ボタンで簡単に在庫数を更新</p>
					</div>
				</div>
				<div class="feature-item">
					<span class="feature-icon">👨‍👩‍👧‍👦</span>
					<div>
						<h4>家族で共有</h4>
						<p>URLを共有するだけで家族みんなでアクセス</p>
					</div>
				</div>
				<div class="feature-item">
					<span class="feature-icon">📱</span>
					<div>
						<h4>スマホ最適化</h4>
						<p>朝の忙しい時間でも片手で操作可能</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.welcome-section {
		padding: 2rem 1rem;
		max-width: 800px;
		margin: 0 auto;
	}
	
	.welcome-message {
		text-align: center;
		margin-bottom: 3rem;
	}
	
	.welcome-message h2 {
		color: #333;
		font-size: 1.8rem;
		margin-bottom: 1rem;
		font-weight: 600;
	}
	
	.welcome-message p {
		color: #666;
		font-size: 1rem;
		line-height: 1.6;
		margin-bottom: 0.5rem;
	}
	
	.features {
		margin-top: 4rem;
		padding: 2rem;
		background: #f8f8f8;
		border-radius: 12px;
	}
	
	.features h3 {
		text-align: center;
		color: #333;
		font-size: 1.4rem;
		margin-bottom: 2rem;
		font-weight: 600;
	}
	
	.feature-list {
		display: grid;
		gap: 1.5rem;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	}
	
	.feature-item {
		display: flex;
		gap: 1rem;
		align-items: flex-start;
	}
	
	.feature-icon {
		font-size: 2rem;
		flex-shrink: 0;
	}
	
	.feature-item h4 {
		color: #333;
		font-size: 1rem;
		margin: 0 0 0.25rem;
		font-weight: 600;
	}
	
	.feature-item p {
		color: #666;
		font-size: 0.9rem;
		margin: 0;
		line-height: 1.4;
	}
	
	@media (max-width: 600px) {
		.welcome-section {
			padding: 1rem;
		}
		
		.features {
			padding: 1.5rem;
		}
		
		.feature-list {
			grid-template-columns: 1fr;
		}
	}
</style>
