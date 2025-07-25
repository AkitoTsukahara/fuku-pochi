FROM node:22-alpine

# 作業ディレクトリを設定
WORKDIR /app

# システムパッケージをインストール
RUN apk add --no-cache \
    git \
    curl

# node ユーザーに切り替え
USER node

# アプリケーションのソースをコピー（nodeユーザーで）
COPY --chown=node:node . .

# 依存関係をインストール（プロジェクトが存在する場合のみ）
RUN if [ -f package.json ]; then npm install; fi

# ポート5173を公開
EXPOSE 5173

# 起動コマンド（プロジェクトが存在しない場合は待機）
CMD ["sh", "-c", "if [ -f package.json ]; then npm run dev -- --host 0.0.0.0; else echo 'Waiting for SvelteKit project...'; sleep infinity; fi"]