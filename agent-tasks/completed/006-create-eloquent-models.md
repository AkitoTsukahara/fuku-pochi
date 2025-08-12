# Laravel 12.x Eloquentモデル作成

## 概要
データベーステーブルと対応するEloquentモデルクラスを作成し、リレーションシップを定義する

## 作業内容
- [x] UserGroupモデルの作成とリレーション定義
- [x] Childrenモデルの作成とリレーション定義
- [x] ClothingCategoryモデルの作成
- [x] StockItemモデルの作成とリレーション定義
- [x] 各モデルのfillable属性設定
- [x] モデルリレーションシップのテスト

## 完了条件
- 全モデルクラスが作成され、適切なリレーションが定義されている
- モデル間の関連性が正しく動作する
- 必要な属性がfillableに設定されている
- 基本的なCRUD操作が可能

## 関連ファイル
- backend/app/Models/UserGroup.php
- backend/app/Models/Children.php
- backend/app/Models/ClothingCategory.php
- backend/app/Models/StockItem.php

## 備考
- UserGroup hasMany Children
- Children belongsTo UserGroup, hasMany StockItems
- StockItem belongsTo Children, belongsTo ClothingCategory
- share_tokenの自動生成ロジックも検討