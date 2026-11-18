# 2026년 6월 AICopia 작업일지

- 저장소: aivance-kr/AICopia
- 기준: 해당 월에 닫힌 이슈 + 머지된 PR (일자는 KST 기준)
- 완료 이슈: 7건
- 머지된 PR: 17건

## 2026-06-29 (월)

#### 머지된 PR

- [#1](https://github.com/aivance-kr/AICopia/pull/1) themes 폴더 생성 및 zip 파일 이동
- [#2](https://github.com/aivance-kr/AICopia/pull/2) CLAUDE.md 전면 재작성 + Git 워크플로우 추가
- [#3](https://github.com/aivance-kr/AICopia/pull/3) CLAUDE.md 한글화 + 베스트프랙티스 보강
- [#5](https://github.com/aivance-kr/AICopia/pull/5) 모던 PHP 개발 프로세스 도입 (CS-Fixer · Rector · CI · strict_types)
- [#6](https://github.com/aivance-kr/AICopia/pull/6) CD 추가: main 머지 후 수동 승인 SSH 배포 (자동 마이그레이션)
- [#8](https://github.com/aivance-kr/AICopia/pull/8) 초기 설치 환경 정비 및 SETUP.md 현행화
- [#9](https://github.com/aivance-kr/AICopia/pull/9) PHPUnit 12 업그레이드 및 버그 수정
- [#10](https://github.com/aivance-kr/AICopia/pull/10) ShopController: product id int 캐스트 누락 TypeError 수정
- [#11](https://github.com/aivance-kr/AICopia/pull/11) OrderController: getWithItems() int 캐스트 누락 TypeError 수정
- [#12](https://github.com/aivance-kr/AICopia/pull/12) PHPStan 레벨 5 → 6 상향 및 baseline 재생성
- 배포(dev→main): [#4](https://github.com/aivance-kr/AICopia/pull/4)

## 2026-06-30 (화)

보안 점검에서 도출된 취약점 VLN-01 ~ VLN-07 7건을 같은 날 모두 조치·종료했다.

| 이슈 | 심각도 | 작업 내용 |
|------|--------|-----------|
| #18 VLN-01 | Critical | 404/400 에러 페이지가 URL 문자열을 이스케이프 없이 출력하던 반사형 XSS(CWE-79) 조치. 배포 전 필수 항목. |
| #19 VLN-02 | High | 운영 환경에 노출된 CI4 디버그바(Kint) 비활성화. 디버그바 JS 데이터로 관리자 라우트 목록이 방문자에게 노출되던 문제 차단. |
| #20 VLN-03 | High | 응답에 전혀 없던 보안 HTTP 헤더(CSP, X-Frame-Options, X-Content-Type-Options, HSTS 등) 적용. |
| #21 VLN-04 | High | `Server`, `X-Powered-By` 등 응답 헤더로 노출되던 웹서버·PHP 버전 정보 제거(`expose_php = Off` 등). |
| #22 VLN-05 | Medium | 404/403 에러 응답에 내부 예외 클래스명·메시지가 그대로 노출되던 문제 차단. |
| #23 VLN-06 | Medium | HTTP 평문 운영으로 로그인·결제 정보가 노출될 수 있던 문제 대응. HTTPS 전환, HTTP→HTTPS 리다이렉트, 세션 쿠키 `Secure` 속성 설정. |
| #24 VLN-07 | Low | `robots.txt`의 `Disallow`가 비어 있던 문제 개선. 관리자·인증·장바구니·주문 등 경로 색인 제한. |

#### 머지된 PR

- [#13](https://github.com/aivance-kr/AICopia/pull/13) PHPStan 레벨 6 baseline 27건 수정 (284 → 257)
- [#14](https://github.com/aivance-kr/AICopia/pull/14) PHPStan 레벨 6 iterable 제네릭 타입 어노테이션 전면 추가 (257 → 4건)
- [#15](https://github.com/aivance-kr/AICopia/pull/15) TinyMCE 자체 호스팅 전환 (API 키 불필요)
- [#16](https://github.com/aivance-kr/AICopia/pull/16) 상품 이미지 AI 배경 제거 기능 추가 (Clipdrop)
- [#17](https://github.com/aivance-kr/AICopia/pull/17) API 설정 저장 시 openrouter/clipdrop 키 누락 수정
- [#25](https://github.com/aivance-kr/AICopia/pull/25) 보안 강화: 응답 헤더·expose_php·robots.txt 개선

### 월간 요약
- 출시 전 보안 점검 결과 전 항목(Critical 1, High 3, Medium 2, Low 1) 조치 완료.
