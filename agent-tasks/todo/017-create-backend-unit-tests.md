# バックエンドAPIのユニットテスト作成

## 概要
Laravel APIの各エンドポイントと主要機能に対するユニットテストを作成する

## 作業内容
- [ ] グループ管理APIのテスト作成
- [ ] 子ども管理APIのテスト作成
- [ ] ストック管理APIのテスト作成
- [ ] モデルクラスのテスト作成
- [ ] バリデーションルールのテスト作成
- [ ] テストデータベースの設定

## 完了条件
- 全APIエンドポイントのテストが作成されている
- 正常系・異常系両方のテストケースがある
- テストカバレッジが80%以上
- CIでテストが自動実行される
- テストデータの適切なクリーンアップ

## 関連ファイル
- backend/tests/Feature/Api/GroupTest.php
- backend/tests/Feature/Api/ChildrenTest.php
- backend/tests/Feature/Api/StockTest.php
- backend/tests/Unit/Models/
- backend/phpunit.xml

## 備考
- テスト用DBとしてSQLiteを使用
- Factoryを活用したテストデータ生成
- 境界値テスト（ストック数0、最大値）の実装
- セキュリティテスト（不正トークン等）も含める