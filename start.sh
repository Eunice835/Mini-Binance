#!/bin/bash

redis-server --daemonize yes 2>/dev/null || true

cd /home/runner/workspace/api && php artisan serve --host=0.0.0.0 --port=8000 &

cd /home/runner/workspace/client && npm run dev
