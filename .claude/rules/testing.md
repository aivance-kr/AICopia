# 개발 품질 게이트 (필수)

커밋·PR 전에 아래를 통과시킵니다. 한 번에 `composer check`(= cs + analyse + test).

## 검증 게이트 정책 — 어디서 무엇을 돌리는가

**검증은 로컬에서 끝낸다.** `feature/* → dev` PR에는 CI를 걸지 않고, CI는 `dev → main` 배포 PR에서만 돈다.

```
feature/*  ──[로컬 검증: cs + analyse + test]──▶  dev  ──[PR + CI]──▶  main
                    ↑                              ↑
              여기가 실질적 게이트              여기서만 CI가 돈다
```

| 시점 | 무엇을 | 누가 |
|------|--------|------|
| 개발 중 | `composer analyse` + `composer test`(필요한 부분만), 수시로 | 사람 / Claude |
| `dev` 푸시 전 | `composer cs` + `composer analyse` + `composer test` 전체 필수 — 실패하면 푸시하지 않는다 | pre-push 훅이 강제 |
| `feature/*` → `dev` PR | CI 없음. 코드 리뷰만 | — |
| `dev` → `main` PR | GitHub Actions 전체(PHPStan + PHPUnit) | CI(self-hosted 러너) |

`feature → dev`에 CI가 없다는 건 `dev` 브랜치가 검증받지 않은 코드를 받을 수 있다는 뜻이다. 그 상태로 여러 기능이 쌓인 뒤 배포 PR에서 처음 CI가 돌면, 어느 커밋이 깨뜨렸는지 찾는 비용이 커지고 배포가 막힌다. **로컬 검증(아래 pre-push 훅)이 유일한 방어선이므로 생략은 곧 규칙 위반이다.**

Claude가 작업할 때도 동일하다 — `dev`로 올리는 PR을 만들기 전에 위 명령을 실제로 실행하고 출력을 확인한 다음 진행한다. "통과할 것 같다"로 넘어가지 않는다.

## 0. pre-push 훅 (자동 게이트)

push 전 품질 게이트를 로컬에서 강제해 **"push → CI 실패 → 수정 → 재push" 왕복을 제거**한다. 훅 본체는 저장소에 추적되는 `.githooks/pre-push`.

- **활성화**: `composer install` 시 자동(`post-install-cmd` → `core.hooksPath=.githooks`). 수동은 `composer hooks:install`.
- **브랜치별 동작**:
  - `main` 직접 push → **무조건 차단**(배포는 `dev → main` PR/merge commit로만).
  - `dev` push → `cs` + `analyse` 항상 실행(~2초). `test`는 `app/`·`tests/`·`composer.*`·`phpunit.xml`·`phpstan.neon`가 바뀐 push에서만 실행(문서 전용 push는 생략).
  - `feature/*` push → **검증하지 않는다.** 작업 중 빠른 반복을 막지 않기 위해서다. `dev`로 합류하는 순간(`dev` push 시점)에 전체 검증이 걸린다.
- **테스트 DB 미설정**이면 훅이 감지해 이 문서(아래 4번)로 안내한다. 로컬 테스트 DB를 먼저 잡아야 훅의 test 단계가 통과한다.
- **긴급 우회**: `git push --no-verify` 또는 `SKIP_HOOKS=1 git push ...`.

## 1. 코드 스타일 — PHP-CS-Fixer

```bash
composer cs       # 검사 (변경 없음)
composer cs-fix   # 자동 수정
```

- 설정: `.php-cs-fixer.dist.php` — **PSR-12** 기반 + `declare(strict_types=1)` 강제 + import 정렬·short array 등.
- 대상: `app/`, `tests/`(Views 제외). CI에서 `composer cs`가 실패하면 머지 불가.
- **모든 PHP 파일 첫 줄에 `declare(strict_types=1);`** — cs-fixer가 자동 삽입/검사.

## 2. 정적 분석 — PHPStan

```bash
composer analyse
```

- 레벨 **6**(`phpstan.neon`), CI4 확장(`codeigniter/phpstan-codeigniter`) 사용. 대상은 `app/` 코드 디렉토리(Views 제외).
- 기존 억제는 `phpstan-baseline.neon`. 새 코드는 `@phpstan-ignore`로 덮지 말고 원인 수정.
- 새 클래스·메서드에는 제네릭 타입(`array<string, mixed>` 등) 명시.
- 점진적 상향 목표: 6 → 8 (baseline으로 단계적 적용).

## 3. 자동 현대화 — Rector

```bash
composer rector       # 미리보기(dry-run)
composer rector-fix   # 적용
```

- 설정: `rector.php` — PHP 8.5 셋 + CODE_QUALITY / DEAD_CODE / TYPE_DECLARATION / EARLY_RETURN.
- 대량 변경이 나올 수 있으므로 **반드시 PR 단위로 검토**하고 cs-fix·analyse·test로 재검증.

## 4. 테스트 — PHPUnit

```bash
composer test
```

- 단위 테스트는 `tests/unit/`(`CIUnitTestCase` + `DatabaseTestTrait`, `$DBGroup = 'tests'`, `$migrate = false`).
- 테스트는 **미리 마이그레이션된 `tests` DB 그룹**을 가정한다(스스로 마이그레이션하지 않음).
- 마이그레이션은 **MySQL 전용 DDL**(`ALTER TABLE ... ADD UNIQUE KEY` 등)을 사용하므로 SQLite로는 실행 불가 — 테스트 DB도 **MySQL**이어야 한다.
- 마이그레이션이 prefix 없는 raw DDL(`ALTER TABLE users ...`)을 쓰므로 **DBPrefix는 빈 값**이어야 한다(프레임워크 기본 tests 그룹의 `db_`를 쓰면 안 됨).
- 로컬 테스트 준비 예시(default·tests 그룹을 같은 MySQL DB로):

```bash
# .env (default·tests 그룹 모두 동일 MySQL, prefix 없음)
#   database.default.DBDriver = MySQLi
#   database.default.database = aicopia_test
#   database.default.DBPrefix =
#   database.tests.DBDriver   = MySQLi
#   database.tests.database   = aicopia_test
#   database.tests.DBPrefix   =
php spark migrate --all      # default 그룹에 테이블 생성
composer test                # tests 그룹이 같은 DB를 읽음
```

- 운영 DB는 절대 사용 금지. CI는 `.github/workflows/ci.yml`의 `quality` 잡이 동일 흐름을 MySQL 컨테이너로 자동 수행.
- 새 기능(특히 Service/Model 로직)은 테스트를 함께 작성.

### 병렬 테스트 (ParaTest) — CI 기본

CI는 `composer test`(순차) 대신 **`composer test:coverage`**(ParaTest 4-worker + 커버리지)로 실행해 시간을 대폭 단축한다(로컬 실측 순차 140초 → 병렬+커버리지 43초). 커버리지가 필요 없는 로컬 실행은 `composer test:parallel`이 같은 병렬 실행을 커버리지 없이 돈다.

- 테스트는 트랜잭션으로 격리되지 않고 **실제 커밋 + `tearDown()` 수동 정리**를 쓴다. 따라서 worker가 DB를 공유하면 하드코딩된 unique 값 충돌·락 경합이 발생 → **worker별 전용 DB가 필수**.
- ParaTest는 worker마다 `TEST_TOKEN`(1..N)을 주입한다. `tests/bootstrap.php`가 이를 읽어 `tests` 그룹 DB명을 `aicopia_test_<token>`으로 바꾼다(testing 환경은 `defaultGroup='tests'`라 모든 연결이 따라온다). `TEST_TOKEN`이 없으면 순차 실행이라 그대로 `aicopia_test`.
- worker DB는 템플릿(`aicopia_test`)을 마이그레이션한 뒤 **`bin/clone-test-dbs.sh`**로 복제한다. 이 스크립트는 기본적으로 호스트에 설치된 `mysql`/`mysqldump`를 쓰고, `MYSQL_DOCKER_CONTAINER`에 컨테이너 이름을 주면 그 컨테이너 안의 클라이언트로 실행한다(CI가 쓰는 경로 — 아래 5번 참고).

로컬에서 병렬로 돌리려면:

```bash
php spark migrate --all                                          # 템플릿(aicopia_test) 준비
MYSQL_PWD=<비번> bin/clone-test-dbs.sh 4 127.0.0.1 <user> aicopia_test   # worker DB 4개 복제
composer test:parallel
```

> 순차 `composer test`(단일 `aicopia_test`)는 그대로 동작한다 — pre-push 훅은 순차를 쓰므로 로컬에서 worker DB를 만들지 않아도 된다.

## 5. CI는 배포 PR에서만, self-hosted 러너에서 돈다

`.github/workflows/ci.yml`의 트리거는 **`main`을 대상으로 하는 PR로만 한정**한다.

```yaml
on:
  pull_request:
    branches: [main]     # dev로 가는 PR에서는 돌지 않는다
```

`branches`를 비워두거나 `dev`를 포함시키면 모든 PR에서 돌아 위 정책이 무의미해진다. `dev → main` 배포 PR은 merge commit으로 머지하므로(전역 Git 워크플로우 규칙), CI가 통과한 커밋 조합이 그대로 `main`에 올라간다.

### self-hosted 러너에서 돈다

GitHub 호스팅 러너(`ubuntu-latest`)가 아니라 self-hosted Linux(X64) 머신을 러너로 등록해서 돈다. `quality`·`notify` 두 잡 모두 `runs-on: [self-hosted, Linux, X64]`. 스타일·정적분석·테스트·커버리지는 `quality` 한 잡에 모여 있다 — 러너가 1대라 잡을 쪼개면 checkout·`composer install`·MySQL 기동이 중복되고, 커버리지를 별도 잡으로 두면 같은 스위트를 한 번 더 돌리게 된다.

- **PHP/Composer**: 러너 머신에 이미 설치된 것을 그대로 쓴다(`shivammathur/setup-php` 액션 없이 `php`/`composer`가 PATH에 있다고 가정) — 그 액션은 self-hosted 러너에서 런타임을 다시 설치·relink 하다 다른 프로젝트 러너와 충돌한다. 버전은 개발 환경과 동일하게 유지해야 하며, `quality` 잡 첫 스텝이 PHP 8.5 이상 + 커버리지 드라이버(pcov/xdebug) 설치 여부를 검사해 어긋나면 즉시 실패시킨다.
- **MySQL**: Linux 러너는 `services:` 도커 컨테이너도 지원하지만, 이 저장소는 `quality` 잡에서 `docker run`으로 직접 기동하고 `if: always()` 스텝으로 정리하는 방식을 쓴다(러너 호스트에 다른 MySQL 프로세스가 떠 있을 가능성을 대비한 포트 분리 때문).
- **포트**: CI 전용 MySQL 컨테이너는 호스트 포트 **13306**을 쓴다(`CI_MYSQL_PORT` env로 오버라이드 가능). 러너 호스트가 기본 포트 3306을 쓰는 다른 프로세스와 공유될 수 있어 충돌을 피한다. `bin/clone-test-dbs.sh`도 5번째 인자로 포트를 받는다.
- **DB 클라이언트**: worker DB 복제는 러너 호스트의 클라이언트를 쓰지 않고 **MySQL 컨테이너 안의 `mysql`/`mysqldump`**로 한다(`MYSQL_DOCKER_CONTAINER="$CI_MYSQL_CONTAINER"`). 러너에 깔린 클라이언트가 MariaDB 판이면 MySQL 전용 옵션이 없어 옵션 파싱 단계에서 죽는다 — 실제로 러너를 Linux로 옮긴 뒤 첫 CI가 `mysqldump: unknown variable 'set-gtid-purged=OFF'`(exit 7)로 실패했다. 서버와 같은 이미지의 클라이언트를 쓰면 이 부류의 버전 불일치가 사라진다(스크립트는 지원하지 않는 덤프 옵션을 자동으로 빼기도 한다).
- **러너 등록(1회)**: `bin/setup-ci-runner.sh` 실행 한 번으로 끝난다.

```bash
bin/setup-ci-runner.sh
```

- `gh` CLI가 인증돼 있으면 등록 토큰을 자동 발급(`gh api .../actions/runners/registration-token`)하고, 없으면 GitHub Settings → Actions → Runners → New self-hosted runner 페이지에서 발급받은 토큰을 입력받는다.
- 최신 러너 패키지(Linux/X64)를 GitHub 공식 릴리스에서 받아 `~/actions-runners/AICopia`에 설치하고, systemd 서비스로 등록·기동한다(머신이 켜져 있으면 자동으로 리스닝).
- 이미 등록돼 있으면 재설치 없이 상태만 출력하고 종료한다(중복 등록 방지).
- 상태 확인/중지/제거 명령은 스크립트 실행 마지막에 출력된다.

- **호스팅 러너로 되돌리려면**: `runs-on`을 `ubuntu-latest`로 바꾸고, `quality` 잡의 `docker run` MySQL 기동 스텝을 `services:` 블록으로 바꾸면 된다(포트도 표준값 3306으로 원복 가능).
