# Laravelバックエンドプロジェクト初期化とパッケージ設定

## 概要
バックエンドAPIサーバーとなるLaravelプロジェクトの初期化と必要パッケージのセットアップを行う

## 作業内容
- [ ] Laravel 12.x の新規プロジェクト作成
- [ ] composer.jsonに必要パッケージを追加
- [ ] Laravel設定ファイルの調整（database, cors, etc）
- [ ] ディレクトリ構造の整備（Actions, Services フォルダ作成）
- [ ] 基本的なミドルウェア設定

## 完了条件
- Laravel アプリケーションが正常に起動する
- APIルートが動作する
- データベース接続が確立されている
- CORS設定が完了している

## 関連ファイル
- backend/composer.json
- backend/config/database.php
- backend/config/cors.php
- backend/app/Http/Kernel.php

## 備考
- Laravel 12.x はPHP 8.2以上が必要（PHP 8.4を使用）
- 2025年2月24日リリースの最新版、Laravel 11.xからの移行は容易
- DDD + クリーンアーキテクチャの構成を考慮したフォルダ構成
- Laravel Vaporの制約を考慮した設定
- Composer 2.x を使用