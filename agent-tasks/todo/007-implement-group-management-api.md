# グループ管理API実装

## 概要
家族グループの作成と取得を行うAPI エンドポイントを実装する

## 作業内容
- [ ] POST /api/groups エンドポイントの実装
- [ ] GET /api/groups/{token} エンドポイントの実装
- [ ] リクエストバリデーションの実装
- [ ] share_token生成ロジックの実装
- [ ] レスポンス形式の統一

## 完了条件
- 新しいグループが作成でき、share_tokenが生成される
- tokenによるグループ情報取得が動作する
- 不正なリクエストに対する適切なエラーレスポンス
- API仕様書通りのレスポンス形式

## 関連ファイル
- backend/app/Http/Controllers/Api/GroupController.php
- backend/routes/api.php
- backend/app/Http/Requests/CreateGroupRequest.php

## 備考
- share_tokenはユニークな文字列（UUID推奨）
- レスポンスにはchildrenの情報も含める
- 適切なHTTPステータスコード（201, 200, 404）を返す