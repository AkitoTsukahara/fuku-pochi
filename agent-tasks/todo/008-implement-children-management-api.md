# 子ども管理API実装

## 概要
グループ内の子どもの登録・更新・削除を行うAPI エンドポイントを実装する

## 作業内容
- [ ] GET /api/groups/{token}/children エンドポイントの実装
- [ ] POST /api/groups/{token}/children エンドポイントの実装
- [ ] PUT /api/children/{id} エンドポイントの実装
- [ ] DELETE /api/children/{id} エンドポイントの実装
- [ ] リクエストバリデーションの実装

## 完了条件
- 子どもの一覧取得が動作する
- 新しい子どもの追加が可能
- 子ども情報の更新・削除が可能
- 適切な権限チェック（グループ所属確認）
- バリデーションエラーの適切な処理

## 関連ファイル
- backend/app/Http/Controllers/Api/ChildrenController.php
- backend/routes/api.php
- backend/app/Http/Requests/CreateChildRequest.php
- backend/app/Http/Requests/UpdateChildRequest.php

## 備考
- 子ども削除時は関連するstock_itemsも削除される
- 255文字以内の名前制限
- 不正なグループトークンに対する404エラー