#!/usr/bin/env bash
#
# 병렬 테스트(ParaTest)용 worker DB를 템플릿에서 복제한다.
# ParaTest worker는 TEST_TOKEN(1..N)마다 전용 DB(<template>_<token>)를 쓴다
# (tests/bootstrap.php 참고). 이 스크립트가 그 DB들을 미리 만든다.
#
# 사용:
#   MYSQL_PWD=<비번> bin/clone-test-dbs.sh <N> <host> <user> <template_db> [port]
#   - 비번 없으면 MYSQL_PWD 생략(빈 값), port 없으면 3306
#   - 예) 로컬:  MYSQL_PWD=secret bin/clone-test-dbs.sh 4 127.0.0.1 shop aicopia_test
#
# MYSQL_DOCKER_CONTAINER 를 주면 호스트 클라이언트 대신 그 컨테이너 안의
# mysql/mysqldump 를 쓴다. CI 러너에 깔린 클라이언트가 MariaDB 판이라
# MySQL 서버와 옵션이 어긋나는 문제를 원천 차단한다(서버와 같은 이미지의 클라이언트).
#   - 예) CI: MYSQL_PWD= MYSQL_DOCKER_CONTAINER=aicopia-ci-mysql bin/clone-test-dbs.sh 4 127.0.0.1 root aicopia_test 13306
set -euo pipefail

N="${1:-4}"
HOST="${2:-127.0.0.1}"
USER="${3:-root}"
TPL="${4:-aicopia_test}"
PORT="${5:-3306}"

CONTAINER="${MYSQL_DOCKER_CONTAINER:-}"

# 컨테이너 모드에서는 컨테이너 안에서 로컬 소켓/기본 포트로 붙으므로 호스트·포트 인자를 넘기지 않는다.
if [ -n "$CONTAINER" ]; then
  run_mysql()     { docker exec -i -e MYSQL_PWD="${MYSQL_PWD:-}" "$CONTAINER" mysql -u"$USER" "$@"; }
  run_mysqldump() { docker exec -i -e MYSQL_PWD="${MYSQL_PWD:-}" "$CONTAINER" mysqldump -u"$USER" "$@"; }
else
  run_mysql()     { mysql -u"$USER" -h"$HOST" -P"$PORT" "$@"; }
  run_mysqldump() { mysqldump -u"$USER" -h"$HOST" -P"$PORT" "$@"; }
fi

dump="$(mktemp)"
trap 'rm -f "$dump"' EXIT

# 덤프 옵션은 클라이언트가 실제로 지원하는 것만 넘긴다.
# 머신마다 mysqldump 가 MySQL 판일 수도 MariaDB 판일 수도 있는데, MariaDB 판에는
# --set-gtid-purged 가 없어 그대로 넘기면
# `mysqldump: unknown variable 'set-gtid-purged=OFF'` 로 즉시 죽는다(exit 7).
dump_help="$(run_mysqldump --help 2>/dev/null || true)"
dump_opts=()
case "$dump_help" in *--no-tablespaces*) dump_opts+=(--no-tablespaces) ;; esac
# GTID 제외 — 다른 DB 로 로드할 때 GTID 충돌을 막는다(MySQL 판에만 있는 옵션).
case "$dump_help" in *set-gtid-purged*) dump_opts+=(--set-gtid-purged=OFF) ;; esac

# 템플릿 스키마+시드 데이터 덤프
# (bash 3.2 는 set -u 에서 빈 배열 전개를 에러로 보므로 ${arr[@]+...} 로 감싼다)
run_mysqldump ${dump_opts[@]+"${dump_opts[@]}"} "$TPL" > "$dump"

for i in $(seq 1 "$N"); do
  db="${TPL}_${i}"
  run_mysql -e "DROP DATABASE IF EXISTS \`${db}\`; CREATE DATABASE \`${db}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
  run_mysql "$db" < "$dump"
done

echo "✓ worker DB ${TPL}_1 .. ${TPL}_${N} 준비 완료 (N=${N})"
