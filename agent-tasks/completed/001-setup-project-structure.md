# プロジェクト基盤構築 - Docker環境とディレクトリ構造を設定

## 概要
ふくぽちサービスの開発基盤となるDocker環境とプロジェクトディレクトリ構造を構築する

## 作業内容
- [ ] プロジェクトルートディレクトリ構造の作成
- [ ] Docker Compose設定ファイルの作成
- [ ] backend/frontend フォルダの作成
- [ ] 各サービス用のDockerfileの作成
- [ ] 環境変数ファイルのテンプレート作成

## 完了条件
- docker-compose upでバックエンド・フロントエンド・データベースが起動する
- 各サービスが正常に通信できる状態
- 開発者がローカル環境で開発を開始できる

## 関連ファイル
- docker-compose.yml
- backend/Dockerfile
- frontend/Dockerfile
- backend/.env.example
- frontend/.env.example

## 備考
- dev-env.mdの仕様に従って構築
- MySQL 8.4 LTS, PHP 8.4, Node.js 22 LTSの環境を構築
- 永続化ボリュームの設定も含める