# フロントエンド実装ガイドライン

## 概要

SvelteKitを使用したフロントエンド開発の指針とベストプラクティス。
Store不使用の方針でシンプルかつ保守性の高いコードを目指す。

## データ管理方針（Store不使用）

### 基本原則

1. **サーバーが真実の源泉**: 全データはサーバーから取得
2. **SvelteKit標準フロー**: load関数 + Form Actions
3. **状態同期なし**: クライアント側での状態管理を避ける
4. **データ整合性**: invalidateによる確実な再フェッチ

### データフロー

```
┌─────────────────┐    load function     ┌─────────────────┐
│   Page Load     │◄─────────────────────┤   Server Data   │
└─────────────────┘                      └─────────────────┘
         │                                         ▲
         ▼                                         │
┌─────────────────┐    Form Actions      ┌─────────────────┐
│  User Actions   │─────────────────────►│  Data Updates   │
└─────────────────┘                      └─────────────────┘
         │                                         ▲
         ▼            invalidate()                  │
┌─────────────────┐─────────────────────────────────┘
│  Data Refresh   │
└─────────────────┘
```

## ファイル構成

### API層 (`src/lib/api/`)

```
lib/api/
├── client.ts          # 基底APIクライアント
├── groups.ts          # グループ関連API
├── children.ts        # 子ども関連API
├── stock.ts           # 在庫関連API
├── categories.ts      # カテゴリ関連API
└── index.ts           # 統一エクスポート
```

#### APIクライアント設計原則

1. **fetchパラメータ受け取り**: SSR/CSR両対応
2. **統一エラーハンドリング**: ApiErrorクラス使用
3. **型安全性**: TypeScriptによる完全な型付け

```typescript
// 例: API関数の標準形
export const someApi = {
  getData: async (id: string, fetch?: typeof window.fetch): Promise<SomeData> => {
    return apiClient.get<SomeData>(`/some-endpoint/${id}`, fetch);
  }
};
```

### ページ層 (`src/routes/`)

```
routes/
├── +layout.svelte           # 共通レイアウト
├── +page.svelte             # トップページ
├── groups/
│   └── [token]/
│       ├── +page.svelte     # グループ詳細ページ
│       ├── +page.ts         # load関数
│       └── +page.server.ts  # Form Actions
└── stock/
    └── [childId]/
        ├── +page.svelte
        ├── +page.ts
        └── +page.server.ts
```

### コンポーネント層 (`src/lib/components/`)

```
components/
├── elements/           # 基本UI要素
│   ├── Button.svelte
│   ├── Counter.svelte
│   └── Icon.svelte
├── blocks/             # 機能ブロック
│   ├── StockGrid.svelte
│   └── StockItem.svelte
└── sections/           # ページセクション
    ├── Header.svelte
    └── MainContent.svelte
```

## 実装パターン

### 1. load関数でのデータフェッチ

```typescript
// src/routes/groups/[token]/+page.ts
import type { PageLoad } from './$types';
import { groupsApi } from '$lib';

export const load: PageLoad = async ({ params, fetch }) => {
  // 並列でデータ取得
  const [group, children] = await Promise.all([
    groupsApi.getGroupByToken(params.token, fetch),
    groupsApi.getGroupChildren(params.token, fetch)
  ]);

  return {
    group,
    children
  };
};
```

### 2. Form Actionsでのデータ更新

```typescript
// src/routes/groups/[token]/+page.server.ts
import type { Actions } from './$types';
import { fail } from '@sveltejs/kit';
import { groupsApi } from '$lib';

export const actions: Actions = {
  addChild: async ({ params, request, fetch }) => {
    const formData = await request.formData();
    const name = formData.get('name') as string;

    try {
      await groupsApi.createChild(params.token, { name }, fetch);
      return { success: true };
    } catch (error) {
      return fail(400, { error: error.message });
    }
  }
};
```

### 3. Svelteコンポーネントでの使用

```svelte
<!-- src/routes/groups/[token]/+page.svelte -->
<script lang="ts">
  import type { PageData } from './$types';
  import { enhance } from '$app/forms';
  
  export let data: PageData;
  
  // リアクティブ宣言でデータアクセス
  $: group = data.group;
  $: children = data.children;
</script>

<h1>{group.name}</h1>

<!-- Form Actionの使用 -->
<form method="POST" action="?/addChild" use:enhance>
  <input name="name" placeholder="子どもの名前" required />
  <button type="submit">追加</button>
</form>

<!-- データの表示 -->
{#each children as child}
  <div>{child.name}</div>
{/each}
```

## エラーハンドリング

### API エラー

```typescript
import { error } from '@sveltejs/kit';
import { ApiError } from '$lib';

try {
  const data = await someApi.getData(id, fetch);
  return { data };
} catch (err) {
  if (err instanceof ApiError) {
    throw error(err.status, err.message);
  }
  throw error(500, '予期しないエラーが発生しました');
}
```

### フォームエラー

```typescript
import { fail } from '@sveltejs/kit';

export const actions: Actions = {
  submit: async ({ request, fetch }) => {
    try {
      // 処理
    } catch (err) {
      if (err instanceof ApiError) {
        return fail(err.status, { 
          error: err.message,
          details: err.errors 
        });
      }
      return fail(500, { error: '処理に失敗しました' });
    }
  }
};
```

## ベストプラクティス

### DO（推奨）

✅ **load関数でのデータフェッチ**
```typescript
export const load = async ({ fetch }) => {
  const data = await api.getData(fetch);
  return { data };
};
```

✅ **Form Actionsでのデータ更新**
```typescript
export const actions = {
  update: async ({ request, fetch }) => {
    await api.updateData(data, fetch);
    return { success: true };
  }
};
```

✅ **enhance使用でのUX向上**
```svelte
<form use:enhance method="POST">
  <!-- フォーム内容 -->
</form>
```

✅ **型安全なAPI呼び出し**
```typescript
const result: UserGroup = await groupsApi.getGroupByToken(token, fetch);
```

### DON'T（非推奨）

❌ **Svelte Storeでの状態管理**
```typescript
// 使用しない
const store = writable(data);
```

❌ **クライアント側でのデータキャッシュ**
```typescript
// 使用しない
let cachedData = localStorage.getItem('data');
```

❌ **手動での状態同期**
```typescript
// 使用しない
onMount(() => {
  // データフェッチ・更新
});
```

## パフォーマンス最適化

### 1. 並列データフェッチ
```typescript
const [data1, data2] = await Promise.all([
  api1.getData(fetch),
  api2.getData(fetch)
]);
```

### 2. 適切なpreload設定
```typescript
export const prerender = false;  // 動的コンテンツの場合
export const ssr = true;         // SEOが重要な場合
```

### 3. 効率的なForm Actions
```typescript
// 成功時のみinvalidateが実行される
export const actions = {
  update: async () => {
    // 処理
    return { success: true };  // これがinvalidateトリガー
  }
};
```

## 型定義

### ページデータ型

```typescript
// lib/data/types.ts
export interface GroupPageData {
  group: UserGroup;
  children: Child[];
}

export interface StockPageData {
  child: Child;
  stockItems: StockItem[];
  categories: ClothingCategory[];
}
```

### API型

```typescript
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  data: null;
  errors?: Record<string, string[]>;
}
```

## 今後の開発指針

1. **一貫性の維持**: この方針に従った開発
2. **シンプルさ重視**: 複雑な状態管理を避ける
3. **SvelteKit準拠**: フレームワークの思想に沿う
4. **型安全性**: TypeScriptの恩恵を最大活用
5. **パフォーマンス**: SSR/CSRの適切な使い分け

---

*このガイドラインはプロジェクト全体の保守性と開発効率を向上させるために策定されました。新機能開発時は必ずこの指針に従ってください。*