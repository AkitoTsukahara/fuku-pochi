# 子ども管理API実装

## 概要
グループ内の子どもの登録・更新・削除を行うAPI エンドポイントを実装する

## 作業内容
- [x] GET /api/groups/{token}/children エンドポイントの実装
- [x] POST /api/groups/{token}/children エンドポイントの実装
- [x] PUT /api/children/{id} エンドポイントの実装
- [x] DELETE /api/children/{id} エンドポイントの実装
- [x] リクエストバリデーションの実装
- [x] 単一アクションコントローラへの分割
- [x] Groupsコントローラも単一アクションコントローラに分割
- [x] 全エンドポイントに対するFeatureテスト実装
- [x] Factory作成（UserGroup, Children）

## 完了条件
- [x] 子どもの一覧取得が動作する
- [x] 新しい子どもの追加が可能
- [x] 子ども情報の更新・削除が可能
- [x] 適切な権限チェック（グループ所属確認）
- [x] バリデーションエラーの適切な処理
- [x] 単一アクションコントローラ（SRP準拠）
- [x] 包括的なFeatureテスト（46テストケース, 257アサーション）

## 実装されたAPI構造

### Groups API（単一アクションコントローラ）
- `CreateGroupController` - POST /api/groups
- `GetGroupController` - GET /api/groups/{token}

### Children API（単一アクションコントローラ）
- `GetChildrenController` - GET /api/groups/{token}/children
- `CreateChildController` - POST /api/groups/{token}/children
- `UpdateChildController` - PUT /api/children/{id}
- `DeleteChildController` - DELETE /api/children/{id}

## 関連ファイル

### Controllers
- backend/app/Http/Controllers/Api/Groups/CreateGroupController.php
- backend/app/Http/Controllers/Api/Groups/GetGroupController.php
- backend/app/Http/Controllers/Api/Children/GetChildrenController.php
- backend/app/Http/Controllers/Api/Children/CreateChildController.php
- backend/app/Http/Controllers/Api/Children/UpdateChildController.php
- backend/app/Http/Controllers/Api/Children/DeleteChildController.php

### Requests
- backend/app/Http/Requests/CreateGroupRequest.php
- backend/app/Http/Requests/CreateChildRequest.php
- backend/app/Http/Requests/UpdateChildRequest.php

### Models（Factory追加）
- backend/app/Models/UserGroup.php（HasFactory trait追加）
- backend/app/Models/Children.php（HasFactory trait追加）

### Factories
- backend/database/factories/UserGroupFactory.php
- backend/database/factories/ChildrenFactory.php

### Tests
- backend/tests/Feature/Groups/CreateGroupControllerTest.php
- backend/tests/Feature/Groups/GetGroupControllerTest.php
- backend/tests/Feature/Children/GetChildrenControllerTest.php
- backend/tests/Feature/Children/CreateChildControllerTest.php
- backend/tests/Feature/Children/UpdateChildControllerTest.php
- backend/tests/Feature/Children/DeleteChildControllerTest.php

### Routes
- backend/routes/api.php（単一アクションコントローラ対応）

## テスト結果
- **46テストケース** 全て成功
- **257アサーション** 全て通過
- 正常系・異常系・バリデーション・セキュリティ全て網羅

## 実装方針の変更
1. **単一アクションコントローラ採用**: 各エンドポイントを独立したコントローラクラスに分割
2. **ディレクトリ構造**: Groups/, Children/ サブディレクトリで整理
3. **包括的テスト**: 全エンドポイントに対するFeatureテスト実装

## 備考
- 子ども削除時は関連するstock_itemsも削除される（StockItem実装時）
- 255文字以内の名前制限
- 不正なグループトークンに対する404エラー
- 単一責任の原則（SRP）に従った設計
- テストファーストアプローチによる品質保証