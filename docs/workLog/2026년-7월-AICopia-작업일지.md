# 2026년 7월 AICopia 작업일지

- 저장소: aivance-kr/AICopia
- 기준: 해당 월에 닫힌 이슈 + 머지된 PR (일자는 KST 기준)
- 완료 이슈: 10건
- 머지된 PR: 54건

## 2026-07-01 (수)

SEO/GEO 전략 문서(`docs/seo-geo-strategy.md`) 기반 작업과 개발 환경 현대화를 마무리했다.

- **#27 [SEO/GEO] Phase 0 — 크롤링 토대 구축 (P0)**: `robots.txt`, `sitemap.xml` 동적 생성(캐시 1시간), `SeoHelper` JSON-LD(Organization, WebSite+SearchAction, Product, BreadcrumbList), canonical 태그 전역 삽입, OG 태그 정비.
- **#28 [SEO/GEO] Phase 1 — 리치결과·답변 최적화 (P1)**: 리뷰/FAQ/Article 스키마, 리뷰 요약·Q&A 상품 페이지 SSR 노출, 트위터 카드 적용. 데이터 모델 변경이 필요한 항목은 #33, #34로 분리.
- **#33 리뷰 별점(rating) 기능 + AggregateRating/Review 스키마**: `product_reviews.rating` 컬럼 추가, 리뷰 작성 폼 별점 입력, 평균 평점·리뷰 수 집계, 상세 화면 별점 SSR 표시, 실데이터가 있을 때만 `aggregateRating` 스키마 출력.
- **#34 카테고리 랜딩 카피 + FAQ 블록**: `categories`에 소개 카피·FAQ 구조 추가, 관리자 카테고리 편집 UI 입력 지원, 상품 목록 상단 소개/하단 FAQ SSR 렌더링, FAQPage JSON-LD 연결.
- **#39 Rector 타깃 PHP 8.5 셋 상향**: PHP 8.5 런타임 통일(#38)에 맞춰 rector 설정을 8.5 셋으로 올리고 대규모 코드 현대화(dry-run 기준 107개 파일) 적용.

#### 머지된 PR

- [#26](https://github.com/aivance-kr/AICopia/pull/26) 로그인 타입 오류 수정 + 🔔 관리자 알림 드롭다운 추가
- [#31](https://github.com/aivance-kr/AICopia/pull/31) SEO/GEO Phase 0: robots·sitemap·구조화 데이터 토대 구축
- [#32](https://github.com/aivance-kr/AICopia/pull/32) SEO/GEO Phase 1: FAQPage·Article 스키마 + 트위터 카드
- [#35](https://github.com/aivance-kr/AICopia/pull/35) SEO+GEO 전략문서 추가 (docs/seo-geo-strategy.md)
- [#36](https://github.com/aivance-kr/AICopia/pull/36) 리뷰 별점(rating) 기능 + AggregateRating/Review 스키마
- [#37](https://github.com/aivance-kr/AICopia/pull/37) 카테고리 랜딩 카피 + FAQ 블록 (+ FAQPage 스키마)
- [#38](https://github.com/aivance-kr/AICopia/pull/38) PHP 버전 8.5로 통일 (CI + composer)
- [#40](https://github.com/aivance-kr/AICopia/pull/40) Rector 타깃 PHP 8.5 셋 상향 + 자동 현대화 (107파일)
- 배포(dev→main): [#41](https://github.com/aivance-kr/AICopia/pull/41)

## 2026-07-02 (목)

#### 머지된 PR

- [#42](https://github.com/aivance-kr/AICopia/pull/42) env.example 샘플 파일 추가
- [#44](https://github.com/aivance-kr/AICopia/pull/44) 게시판 목록: board id/posts_per_page 문자열 TypeError 수정
- [#46](https://github.com/aivance-kr/AICopia/pull/46) 배포 SSH 시크릿 이름 정정 (SSH_KEY → SSH_PRIVATE_KEY)
- [#48](https://github.com/aivance-kr/AICopia/pull/48) codeigniter4/framework를 require로 이동 (운영 배포 누락 수정)
- [#50](https://github.com/aivance-kr/AICopia/pull/50) 배포 시 gitignore된 CI4 스켈레톤 자동 복원
- [#52](https://github.com/aivance-kr/AICopia/pull/52) 스켈레톤 복원을 cp -n 대신 없는 항목만 복사로 변경
- [#54](https://github.com/aivance-kr/AICopia/pull/54) 배포 워크플로 임시 진단 계측 (set -x + 단계 마커)
- [#56](https://github.com/aivance-kr/AICopia/pull/56) 배포 실패 수정: appleboy script_stop 비활성화 (if 조건 오탐)
- 배포(dev→main): [#43](https://github.com/aivance-kr/AICopia/pull/43), [#45](https://github.com/aivance-kr/AICopia/pull/45), [#47](https://github.com/aivance-kr/AICopia/pull/47), [#49](https://github.com/aivance-kr/AICopia/pull/49), [#51](https://github.com/aivance-kr/AICopia/pull/51), [#53](https://github.com/aivance-kr/AICopia/pull/53), [#55](https://github.com/aivance-kr/AICopia/pull/55), [#57](https://github.com/aivance-kr/AICopia/pull/57)

## 2026-07-03 (금)

#### 머지된 PR

- [#58](https://github.com/aivance-kr/AICopia/pull/58) README 옛 이름·잔재 정리 (AICopia)

## 2026-07-04 (토)

#### 머지된 PR

- [#59](https://github.com/aivance-kr/AICopia/pull/59) CI/CD Slack 알림 추가
- [#60](https://github.com/aivance-kr/AICopia/pull/60) Claude Code 프로젝트 권한 허용 목록 추가
- [#62](https://github.com/aivance-kr/AICopia/pull/62) CI/CD Slack 알림을 Block Kit 상세 메시지로 개선
- 배포(dev→main): [#61](https://github.com/aivance-kr/AICopia/pull/61), [#63](https://github.com/aivance-kr/AICopia/pull/63)

## 2026-07-06 (월)

#### 머지된 PR

- [#64](https://github.com/aivance-kr/AICopia/pull/64) 회원가입 성별·생년월일 서버 검증 강화
- [#65](https://github.com/aivance-kr/AICopia/pull/65) 회원가입 주소 필드 길이 검증 추가

## 2026-07-08 (수)

#### 머지된 PR

- [#66](https://github.com/aivance-kr/AICopia/pull/66) 고객+관리자 통합 사용 매뉴얼 추가 및 CLAUDE.md 갱신

## 2026-07-09 (목)

#### 머지된 PR

- [#67](https://github.com/aivance-kr/AICopia/pull/67) 통합 사용 매뉴얼 + 🔧 php-cs-fixer 자동 적용 훅

## 2026-07-13 (월)

#### 머지된 PR

- [#68](https://github.com/aivance-kr/AICopia/pull/68) CLAUDE.md 규칙 블록을 .claude/rules/로 분기
- [#69](https://github.com/aivance-kr/AICopia/pull/69) 팀 공유 pre-push 훅 (품질 게이트 로컬 강제)
- [#70](https://github.com/aivance-kr/AICopia/pull/70) CI Composer 캐시 개선 (속도)
- [#71](https://github.com/aivance-kr/AICopia/pull/71) ParaTest 병렬화로 CI 테스트 77초→22초

## 2026-07-14 (화)

- **#7 CD 관련 수동 수정사항**: 배포 워크플로우(`deploy.yml`) 도입 후 코드 밖에서 사람이 해야 할 설정(GitHub Secrets 등록 `SSH_HOST`/`SSH_USER`/SSH 키/`DEPLOY_PATH`, `production` 환경 설정, 서버 사전 준비 등)을 정리하고 종료.

## 2026-07-16 (목)

운영 중 발견된 관리자·상품 관련 문제를 일괄 처리했다.

- **#72 상품 슬러그 한글 허용 및 404 수정**: 상품 슬러그에 한글을 허용하고, 브라우저에서 한글 슬러그 접근 시 404가 나던 문제 해결.
- **#73 운영자 사이트설정 > 결제관리 에러**: `/admin/settings/pg`에서 `Invalid file: "admin/settings/pg.php"` 오류 발생. 뷰 파일 누락/경로 문제 해결.
- **#77 운영자 설정 항목 비교·정리**: 기본 탭과 외부 API 탭 사이에 중복된 설정 항목을 확인하고 정리. 외부 API 탭으로 옮길 수 있는 항목 이동 검토.
- **#79 상품 수정 > 네이버 상품검색 이미지 미표시**: 검색 결과 목록에서 상품 이미지가 보이지 않던 문제 수정.

#### 머지된 PR

- [#74](https://github.com/aivance-kr/AICopia/pull/74) 한글 상품 슬러그 URL 접근 시 404 오류 수정
- [#75](https://github.com/aivance-kr/AICopia/pull/75) 관리자 사이트설정 결제관리 페이지 404 오류 수정
- [#78](https://github.com/aivance-kr/AICopia/pull/78) 사이트설정 기본 탭에 노출되던 외부 API 키 중복 제거
- [#80](https://github.com/aivance-kr/AICopia/pull/80) 네이버 상품검색 결과 이미지 미노출 문제 수정
- [#82](https://github.com/aivance-kr/AICopia/pull/82) AICopia 로고·파비콘 디자인 추가
- [#83](https://github.com/aivance-kr/AICopia/pull/83) 네이버 상품검색 이미지가 CSP img-src에 막혀 서버에서 안보이던 문제 수정
- [#85](https://github.com/aivance-kr/AICopia/pull/85) 사이트설정 기본 탭에 남아있던 외부 API/결제/소셜 로그인 잔여 설정 정리
- 배포(dev→main): [#76](https://github.com/aivance-kr/AICopia/pull/76), [#81](https://github.com/aivance-kr/AICopia/pull/81), [#84](https://github.com/aivance-kr/AICopia/pull/84), [#86](https://github.com/aivance-kr/AICopia/pull/86)

## 2026-07-30 (목)

#### 머지된 PR

- [#87](https://github.com/aivance-kr/AICopia/pull/87) 검증 게이트 정책 반영 — CI를 dev→main PR 전용 self-hosted 러너로 전환
- [#88](https://github.com/aivance-kr/AICopia/pull/88) pre-push 훅이 브랜치 삭제 push에서 전체 검증을 실행하던 버그 수정
- [#90](https://github.com/aivance-kr/AICopia/pull/90) 로컬 개발 포트 8303으로 통일
- 배포(dev→main): [#89](https://github.com/aivance-kr/AICopia/pull/89)

### 월간 요약
- 월초: SEO/GEO 기반(Phase 0~1, 별점·카테고리 랜딩) 구축 및 Rector 8.5 상향.
- 월중: CD 수동 설정 정리, 관리자·상품 화면 버그 4건 수정.
