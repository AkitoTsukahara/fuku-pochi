# SvelteKitのAPI通信ライブラリ設定

## 概要
バックエンドAPIとの通信を行うクライアントライブラリを構築する。
SvelteKitの設計思想に従い、Storeは使用せずload関数とForm Actionsでのデータ管理を採用。

## 作業内容
- [x] API通信用のベースクライアント作成
- [x] 各エンドポイント用のAPIクライアント関数作成
- [x] SvelteKitのload関数対応（fetchパラメータ受け取り）
- [x] エラーハンドリングの共通化
- [x] SSR/CSR両対応の実装

## 完了条件
- ✅ 全APIエンドポイントとの通信が可能
- ✅ load関数でのデータフェッチが適切に動作
- ✅ エラーハンドリングが統一されている
- ✅ TypeScriptの型定義が完備されている

## 設計方針
- **Store不使用**: データの真実の源泉はサーバー
- **load関数**: データフェッチはload関数で実行
- **Form Actions**: データ更新はForm Actionsで実行
- **invalidate**: 更新後はinvalidateでデータ再フェッチ

## 実装詳細

### 1. APIクライアント (`lib/api/client.ts`)
- SSR/CSR両対応（環境に応じたbaseURL切り替え）
- SvelteKitのfetchパラメータ受け取り対応
- 統一的なエラーハンドリング（ApiErrorクラス）
- Laravelの統一レスポンス形式対応

### 2. エンドポイント別API
- **groups.ts**: グループ作成・取得・子ども作成
- **children.ts**: 子ども更新・削除
- **stock.ts**: 在庫取得・増減
- **categories.ts**: 衣類カテゴリ取得

### 3. 型定義 (`lib/data/types.ts`)
- `ApiResponse<T>`: 成功レスポンス型
- `ApiErrorResponse`: エラーレスポンス型
- `GroupPageData`, `StockPageData`: ページデータ型
- エンティティ型（UserGroup, Child, StockItem等）

### 4. 使用例 (`lib/examples/load-function-examples.ts`)
```typescript
// load関数での使用
export const load: PageLoad = async ({ params, fetch }) => {
  const [group, children] = await Promise.all([
    groupsApi.getGroupByToken(params.token, fetch),
    groupsApi.getGroupChildren(params.token, fetch)
  ]);
  return { group, children };
};

// Form Actionでの使用
export const actions: Actions = {
  incrementStock: async ({ params, request, fetch }) => {
    await stockApi.incrementStock(params.childId, data, fetch);
    return { success: true };
  }
};
```

## 方針変更の経緯
当初はSvelte Storesによる状態管理を予定していたが、以下の理由により不採用：
1. データの一貫性管理が複雑になる
2. サーバーとクライアントの状態同期が困難
3. SvelteKitの設計思想との不整合

代わりにSvelteKitの標準的なデータフロー（load関数 + Form Actions）を採用。

## 成果物
### 作成ファイル
- `lib/api/children.ts` - 子ども管理API
- `lib/api/categories.ts` - カテゴリAPI
- `lib/examples/load-function-examples.ts` - 使用例

### 更新ファイル
- `lib/api/client.ts` - SSR/CSR対応、fetchパラメータ対応
- `lib/api/groups.ts` - fetchパラメータ対応
- `lib/api/stock.ts` - fetchパラメータ対応
- `lib/data/types.ts` - Store関連型削除、PageData型追加
- `lib/api/index.ts` - エクスポート整理
- `lib/index.ts` - Store関連削除

### 削除ファイル
- `lib/stores/` ディレクトリ全体（当初作成したが方針変更により削除）

## メリット
- データの一貫性が保証される（サーバーが唯一の真実の源泉）
- 状態管理の複雑さを回避
- SvelteKitの設計思想に準拠
- コードがシンプルで理解しやすい
- SSR/CSRの切り替えが透過的

## 備考
- fetchベースのHTTPクライアント
- SSR/CSR両対応
- エラーメッセージの日本語化
- SvelteKitのfetchを使用（Cookie自動転送等の恩恵）