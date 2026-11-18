# 2026년 8월 AICopia 작업일지

- 저장소: aivance-kr/AICopia
- 기준: 해당 월에 닫힌 이슈 + 머지된 PR (일자는 KST 기준)
- 완료 이슈: 25건
- 머지된 PR: 181건

## 2026-08-07 (금)

- **#101 장바구니 에러**: 장바구니에서 일부 상품만 선택해 주문해도 주문서에 전체 상품이 표시되던 문제 수정. 상품명이 길 때 장바구니·주문서 카드를 넘치던 문제를 두 줄 표시로 개선.

#### 머지된 PR

- [#91](https://github.com/aivance-kr/AICopia/pull/91) 우편번호 스크립트 로드 실패 시 daum is not defined 오류 수정
- [#92](https://github.com/aivance-kr/AICopia/pull/92) CSP에 막혀 있던 주소검색 복구 + 카카오 신규 CDN 전환
- [#93](https://github.com/aivance-kr/AICopia/pull/93) CSP가 토스페이먼츠 결제 SDK·이니시스/나이스페이 결제창을 막던 문제 수정
- [#95](https://github.com/aivance-kr/AICopia/pull/95) 에러 페이지를 공통 파셜 기반 한국어 디자인으로 통일
- [#97](https://github.com/aivance-kr/AICopia/pull/97) 이니시스·나이스페이 결제창이 열리지 않던 문제 수정 (#94)
- [#98](https://github.com/aivance-kr/AICopia/pull/98) 주소검색 창이 CSP frame-src에 막혀 "이 콘텐츠는 차단되어 있습니다"로 뜨던 문제 수정
- [#102](https://github.com/aivance-kr/AICopia/pull/102) 장바구니에서 선택한 상품만 주문되도록 수정 + 긴 상품명 두 줄 표시 (#101)
- [#104](https://github.com/aivance-kr/AICopia/pull/104) 토스 주문 시 orderId 규격 위반으로 결제창이 열리지 않던 문제 수정 (400)
- [#106](https://github.com/aivance-kr/AICopia/pull/106) 쿠폰·포인트 사용 시 PG 결제창 금액이 할인 전 금액이던 문제 수정
- [#107](https://github.com/aivance-kr/AICopia/pull/107) 토스 결제위젯 키(gck)로 결제창을 열어 400이 나던 문제 수정
- [#109](https://github.com/aivance-kr/AICopia/pull/109) 결제창을 닫으면 오류 경고창이 뜨고 결제 버튼이 잠기던 문제 수정
- [#112](https://github.com/aivance-kr/AICopia/pull/112) 토스 결제창 파라미터 테스트가 .env 키에 의존하던 문제 수정
- 배포(dev→main): [#96](https://github.com/aivance-kr/AICopia/pull/96), [#99](https://github.com/aivance-kr/AICopia/pull/99), [#100](https://github.com/aivance-kr/AICopia/pull/100), [#103](https://github.com/aivance-kr/AICopia/pull/103), [#105](https://github.com/aivance-kr/AICopia/pull/105), [#108](https://github.com/aivance-kr/AICopia/pull/108), [#110](https://github.com/aivance-kr/AICopia/pull/110), [#111](https://github.com/aivance-kr/AICopia/pull/111)

## 2026-08-08 (토)

결제 정합성 버그와 코드 리뷰에서 나온 보안 이슈를 집중 처리했다.

### 결제·주문
- **#94 이니시스·나이스페이 결제창 미열림**: `launchPG()`가 form을 결제 엔드포인트가 아닌 SDK 스크립트 URL로 POST하던 구현 오류 수정.
- **#113 주문 확정 시 재고 부족이면 결제·쿠폰·포인트 정합성 상실**: `confirmPaid`/`confirmFree`/`confirmBankTransfer`에서 재고 부족으로 롤백될 때, 앞 단계(`createPending`)에서 이미 소진한 쿠폰·포인트와 청구된 PG 결제가 되돌아가지 않던 문제 해결(PG 미취소, 무통장 영구 잠김 포함).
- **#123 쿠폰 이중 사용**: 쿠폰 검증과 소비 사이에 행 잠금·`affectedRows` 검사가 없어 동시 주문 시 이중 사용되던 문제 해결.
- **#124 SKU 옵션 추가금(`price_diff`) 결제 누락**: 주문 총액에는 빠지고 라인 아이템에만 포함되던 금액 불일치 수정.

### 보안
- **#117 게시판 본문 sanitizer**: 블랙리스트 방식으로 저장형 XSS를 막지 못하던 문제 개선.
- **#118 게시판 첨부파일 다운로드 권한 검사**: 비밀글·회원전용 게시판 첨부파일이 권한 검사 없이 내려가던 문제 수정.
- **#119 기본 관리자 계정 고정 비밀번호 시드**: 환경 가드·강제 변경 없이 시드되던 문제 대응(랜덤 비밀번호 생성, 최초 로그인 시 변경 강제).
- **#120 관리자 문의 목록 `filter`·`category` 파라미터 XSS**: href에 이스케이프 없이 출력되던 반사형 XSS 수정.
- **#121 OAuth 콜백 `state` 검증**: `state`와 세션 값이 모두 null이면 통과하던 검증 우회 수정.
- **#122 게시판 검색 `type` 파라미터 XSS**: 페이지네이션 href에 이스케이프 없이 출력되던 반사형 XSS 수정.

#### 머지된 PR

- [#114](https://github.com/aivance-kr/AICopia/pull/114) 주문 확정이 재고 부족으로 실패하면 쿠폰·포인트를 즉시 복구 (#113)
- [#116](https://github.com/aivance-kr/AICopia/pull/116) 확정 실패로 남은 PG 청구를 추적하고 관리자가 취소할 수 있게 (#113)
- [#125](https://github.com/aivance-kr/AICopia/pull/125) 게시판 첨부 다운로드에 게시판·비밀글 권한 검사 추가 (#118)
- [#126](https://github.com/aivance-kr/AICopia/pull/126) href에 쿼리스트링을 수동 연결하며 이스케이프를 빠뜨린 반사형 XSS 수정 (#120, #122)
- [#127](https://github.com/aivance-kr/AICopia/pull/127) 쿠폰 소비를 조건부 UPDATE + 반영행 검사로 확정해 이중 사용 차단 (#123)
- [#128](https://github.com/aivance-kr/AICopia/pull/128) OAuth 콜백 state 검증이 양쪽 모두 비어 있으면 통과하던 문제 수정 (#121)
- [#129](https://github.com/aivance-kr/AICopia/pull/129) SKU 옵션 추가금을 청구 금액에도 반영해 무상 제공되던 문제 수정 (#124)
- [#131](https://github.com/aivance-kr/AICopia/pull/131) 게시글 본문 정화를 허용목록 파서로 교체해 저장형 XSS 차단 (#117)
- [#132](https://github.com/aivance-kr/AICopia/pull/132) 기본 관리자 계정 고정 비밀번호 제거 및 초기 비밀번호 변경 강제 (#119)
- [#134](https://github.com/aivance-kr/AICopia/pull/134) 마이그레이션 실패를 배포 실패로 잡도록 deploy 워크플로 수정
- 배포(dev→main): [#115](https://github.com/aivance-kr/AICopia/pull/115), [#130](https://github.com/aivance-kr/AICopia/pull/130), [#133](https://github.com/aivance-kr/AICopia/pull/133), [#135](https://github.com/aivance-kr/AICopia/pull/135), [#136](https://github.com/aivance-kr/AICopia/pull/136)

## 2026-08-10 (월)

추가구성상품(애드온) 기능과 마이페이지·주문 화면 개선을 진행했다.

- **#137 소셜 로그인 미검증 이메일 자동 연동**: 소유 증명 없이 기존 계정에 소셜 계정이 연동되던 계정 탈취 위험 차단.
- **#147 추가구성상품(애드온) 기능**: 본품에 별매 부가상품을 연결해 함께 구매하는 기능 추가(`product_addons`, 장바구니 묶음 담기 등).
- **#152 주문상세 > 카드 영수증 팝업**: 카드사 등 정보가 부족하던 주문상세에 카드 영수증 팝업 추가.
- **#153 마이페이지 포인트내역**: 포인트 사용 건에서 해당 주문 상세로 이동 가능하도록 연결.
- **#157 관리자 계정으로 장바구니 접속 시 UI 가림**: 드롭다운 메뉴가 카트 상세 내역에 가려지던 문제 수정.
- **#160 애드온 후속 작업**: 주문서(`checkout.php`)의 묶음 표시, 상품 복제 시 애드온 연결 복사 보완.

#### 머지된 PR

- [#138](https://github.com/aivance-kr/AICopia/pull/138) 검증된 이메일일 때만 소셜 계정을 기존 계정에 연동 (#137)
- [#139](https://github.com/aivance-kr/AICopia/pull/139) pre-push 훅이 composer 프로세스 타임아웃에 걸리지 않게 상향
- [#141](https://github.com/aivance-kr/AICopia/pull/141) 소셜 로그인 앱 키 미설정·비활성 제공자 가드 추가
- [#143](https://github.com/aivance-kr/AICopia/pull/143) 네이버 로그인으로 받은 휴대폰번호·성별·생년월일이 DB에 저장되지 않던 문제
- [#144](https://github.com/aivance-kr/AICopia/pull/144) 카카오 로그인 프로필에 휴대폰번호·성별·생년월일 매핑 추가
- [#146](https://github.com/aivance-kr/AICopia/pull/146) 최근 본 상품·추천·찜 목록 이미지가 안 나오던 문제
- [#149](https://github.com/aivance-kr/AICopia/pull/149) strict_types TypeError로 500 나던 주문 상세·배송지 삭제·이메일 인증 수정
- [#154](https://github.com/aivance-kr/AICopia/pull/154) 주문상세에 카드 영수증 팝업 추가 (#152)
- [#155](https://github.com/aivance-kr/AICopia/pull/155) 포인트 내역에서 주문 상세로 이동하는 링크 추가 (#153)
- [#158](https://github.com/aivance-kr/AICopia/pull/158) 추가구성상품(애드온) 기능 추가 (#147)
- [#162](https://github.com/aivance-kr/AICopia/pull/162) 관리자 계정 장바구니 접속 시 드롭다운 메뉴가 주문요약 카드에 가려지는 문제 수정
- [#163](https://github.com/aivance-kr/AICopia/pull/163) dark/spring/violet 테마 zip 내비게이션 드롭다운 가려짐 문제 수정
- [#164](https://github.com/aivance-kr/AICopia/pull/164) 상품상세 스크립트 블록 조기 종료 버그 수정
- [#166](https://github.com/aivance-kr/AICopia/pull/166) 추가 옵션 상품 구매 - 추가 작업 (#160)
- 배포(dev→main): [#140](https://github.com/aivance-kr/AICopia/pull/140), [#142](https://github.com/aivance-kr/AICopia/pull/142), [#145](https://github.com/aivance-kr/AICopia/pull/145), [#148](https://github.com/aivance-kr/AICopia/pull/148), [#151](https://github.com/aivance-kr/AICopia/pull/151), [#156](https://github.com/aivance-kr/AICopia/pull/156), [#159](https://github.com/aivance-kr/AICopia/pull/159), [#165](https://github.com/aivance-kr/AICopia/pull/165), [#167](https://github.com/aivance-kr/AICopia/pull/167)

## 2026-08-11 (화)

- **#161 애드온에 옵션(SKU) 상품 지원**: 애드온 담기 경로의 `sku_id` 처리, SKU 재고 차감, 옵션 추가금 반영으로 옵션 상품도 애드온으로 연결 가능하게 확장.
- **#170 추가 옵션 상품의 배송비**: 본품이 무료배송이면 애드온 배송비가 부과되지 않도록 처리.

#### 머지된 PR

- [#168](https://github.com/aivance-kr/AICopia/pull/168) 추가구성상품(애드온)에 옵션(SKU) 상품 지원
- [#171](https://github.com/aivance-kr/AICopia/pull/171) 네이버 쇼핑 검색 API 종료로 인한 상품등록 검색 오류 수정
- [#173](https://github.com/aivance-kr/AICopia/pull/173) 본품이 무료배송이면 애드온 배송비도 부과되지 않도록 수정
- [#175](https://github.com/aivance-kr/AICopia/pull/175) 장바구니 진입 시 구매 가능한 상품 자동 선택
- [#176](https://github.com/aivance-kr/AICopia/pull/176) 무료배송 상품이 하나라도 있으면 전체 주문 무료배송으로 처리
- [#178](https://github.com/aivance-kr/AICopia/pull/178) 주문서 첫 배송지 저장 시 기본 배송지 미지정 문제 수정
- [#180](https://github.com/aivance-kr/AICopia/pull/180) 장바구니 본품 해제 시 애드온 자동 해제·잠금
- [#182](https://github.com/aivance-kr/AICopia/pull/182) 이니시스 결제 레이어가 부트스트랩5와 충돌해 먹통되는 문제 수정
- [#184](https://github.com/aivance-kr/AICopia/pull/184) 배포 전 저장된 배송지에 기본 배송지 소급 지정
- [#186](https://github.com/aivance-kr/AICopia/pull/186) 이니시스/나이스페이 결제 종료 시 화면이 안 뜨는 문제 수정
- [#188](https://github.com/aivance-kr/AICopia/pull/188) 이니시스/나이스페이 iframe 안에서 세션이 비어 보이는 문제 수정
- [#190](https://github.com/aivance-kr/AICopia/pull/190) 이니시스 결제창 닫으면 실패 화면 대신 주문서로 복귀
- [#191](https://github.com/aivance-kr/AICopia/pull/191) 카카오페이/PAYCO 결제 취소 시 실패 화면 대신 주문서로 복귀
- [#193](https://github.com/aivance-kr/AICopia/pull/193) 브랜딩 아이콘 300x300 PNG 추가
- [#194](https://github.com/aivance-kr/AICopia/pull/194) 카카오페이 결제 준비 실패 시 "지원하지 않는 PG" 오탐 수정
- [#196](https://github.com/aivance-kr/AICopia/pull/196) 카카오페이 시크릿 키 미설정 시 원인을 바로 보여주도록 수정
- [#198](https://github.com/aivance-kr/AICopia/pull/198) 카카오페이 curl 실패 원인을 msg로 구분해 노출
- [#200](https://github.com/aivance-kr/AICopia/pull/200) 네이버페이 결제창 실제 SDK 연동
- [#201](https://github.com/aivance-kr/AICopia/pull/201) PG 어댑터 5종에 curl 타임아웃 5초 추가
- [#203](https://github.com/aivance-kr/AICopia/pull/203) 카카오페이 msg 없는 에러 응답에 원본 바디를 실어 노출
- [#205](https://github.com/aivance-kr/AICopia/pull/205) 네이버페이 결제창 "유효하지 않은 가맹점" 오류 수정
- [#207](https://github.com/aivance-kr/AICopia/pull/207) 네이버페이 샌드박스 결제창 리소스 CSP 차단 수정
- [#209](https://github.com/aivance-kr/AICopia/pull/209) 네이버페이 결제창 "일시적 오류" — frame-src 도메인 실측 정정
- [#211](https://github.com/aivance-kr/AICopia/pull/211) 네이버페이·토스도 결제창 닫으면 실패 화면 대신 주문서로 복귀
- [#213](https://github.com/aivance-kr/AICopia/pull/213) 주문내역 "전체" 탭에서 만료 주문 기본 숨김
- 배포(dev→main): [#169](https://github.com/aivance-kr/AICopia/pull/169), [#172](https://github.com/aivance-kr/AICopia/pull/172), [#174](https://github.com/aivance-kr/AICopia/pull/174), [#177](https://github.com/aivance-kr/AICopia/pull/177), [#179](https://github.com/aivance-kr/AICopia/pull/179), [#181](https://github.com/aivance-kr/AICopia/pull/181), [#183](https://github.com/aivance-kr/AICopia/pull/183), [#185](https://github.com/aivance-kr/AICopia/pull/185), [#187](https://github.com/aivance-kr/AICopia/pull/187), [#189](https://github.com/aivance-kr/AICopia/pull/189), [#192](https://github.com/aivance-kr/AICopia/pull/192), [#195](https://github.com/aivance-kr/AICopia/pull/195), [#197](https://github.com/aivance-kr/AICopia/pull/197), [#199](https://github.com/aivance-kr/AICopia/pull/199), [#202](https://github.com/aivance-kr/AICopia/pull/202), [#204](https://github.com/aivance-kr/AICopia/pull/204), [#206](https://github.com/aivance-kr/AICopia/pull/206), [#208](https://github.com/aivance-kr/AICopia/pull/208), [#210](https://github.com/aivance-kr/AICopia/pull/210), [#212](https://github.com/aivance-kr/AICopia/pull/212)

## 2026-08-12 (수)

- **#214 주문 내역 기록 시점 재정의**: 결제창을 열고 이탈한 경우 취소/재주문만 가능한 주문 내역이 남던 문제 해결. 실제 결제 완료 건만 주문 내역으로 남기고, 결제 시도는 `order_attempts`로 로그 처리(관리자 로그 확인 페이지 신설).
- **#219 쿠폰 사용 방식 변경**: 쿠폰 코드 직접 입력 방식에서 보유 쿠폰 중 사용 가능한 쿠폰을 선택하는 방식으로 변경.
- **#222 시각 기준 UTC 저장 전환**: PHP(KST)와 MySQL(UTC) 시각이 섞여 저장되던 문제 정리. 시각 컬럼을 `TIMESTAMP`로 전환하고 DB 커넥션 세션 타임존을 `+09:00`으로 고정, `$appTimezone`은 `Config/Registrar.php`에서 관리(DB 저장 UTC, 앱·화면 KST).

#### 머지된 PR

- [#215](https://github.com/aivance-kr/AICopia/pull/215) 주문내역에 결제 확정 주문만 남기기 (order_attempts 도입)
- [#216](https://github.com/aivance-kr/AICopia/pull/216) 전환 실패 원인 구분 + 금액 불일치 청구의 환불 추적 (#214 PR2)
- [#217](https://github.com/aivance-kr/AICopia/pull/217) 전환 실패 원인 구분 + 금액 불일치 청구의 환불 추적 (#214 PR2 재적용)
- [#220](https://github.com/aivance-kr/AICopia/pull/220) 등급 전용 쿠폰 검증 누락 수정
- [#221](https://github.com/aivance-kr/AICopia/pull/221) 결제 이탈 UX 및 쿠폰 복구 후속 수정 (#214)
- [#223](https://github.com/aivance-kr/AICopia/pull/223) 관리자 주문시도 로그 페이지 (#214 PR2)
- [#224](https://github.com/aivance-kr/AICopia/pull/224) DB는 UTC 저장 / 앱·화면은 KST — TIMESTAMP + 커넥션 세션 타임존
- [#226](https://github.com/aivance-kr/AICopia/pull/226) 쿠폰 코드 검증(/coupon/check) 500 오류 수정
- [#227](https://github.com/aivance-kr/AICopia/pull/227) CI .env 에서 DBDriver 제거 — 타임존 인식 드라이버 복구
- [#228](https://github.com/aivance-kr/AICopia/pull/228) 쿠폰 적용 주문 500 — CouponService 반환 타입 정규화
- [#230](https://github.com/aivance-kr/AICopia/pull/230) 쿠폰을 보유 목록에서 고르는 방식으로 일원화 (#219)
- [#231](https://github.com/aivance-kr/AICopia/pull/231) 폼 페이지 플래시 메시지 중복 출력 제거 (레이아웃 상단 1회만 노출)
- [#233](https://github.com/aivance-kr/AICopia/pull/233) 이니시스 결제창 실패 시 주문서 먹통 해소 + 테스트/운영 도메인 자동 분기
- [#235](https://github.com/aivance-kr/AICopia/pull/235) 결제창 닫기로 생긴 포인트 선점·환급 허수를 내역에서 숨김
- [#236](https://github.com/aivance-kr/AICopia/pull/236) 소셜 로그인 계정 내 정보 수정 화면의 깨져 보이는 단일 탭 제거
- [#238](https://github.com/aivance-kr/AICopia/pull/238) 마이페이지 화면에 공통 좌측 메뉴 표시
- [#240](https://github.com/aivance-kr/AICopia/pull/240) 마이페이지 화면 좌우 폭을 1100px 로 통일
- 배포(dev→main): [#218](https://github.com/aivance-kr/AICopia/pull/218), [#225](https://github.com/aivance-kr/AICopia/pull/225), [#229](https://github.com/aivance-kr/AICopia/pull/229), [#232](https://github.com/aivance-kr/AICopia/pull/232), [#234](https://github.com/aivance-kr/AICopia/pull/234), [#237](https://github.com/aivance-kr/AICopia/pull/237), [#239](https://github.com/aivance-kr/AICopia/pull/239), [#241](https://github.com/aivance-kr/AICopia/pull/241)

## 2026-08-13 (목)

#### 머지된 PR

- [#242](https://github.com/aivance-kr/AICopia/pull/242) 스크롤바 유무로 화면이 좌우로 밀리던 문제 수정
- [#244](https://github.com/aivance-kr/AICopia/pull/244) 상품 목록 크리틱 개선 (모바일·빈상태·검증·접근성)
- [#245](https://github.com/aivance-kr/AICopia/pull/245) 상품 목록 크리틱 후속 수정 (PR #244 이후 누락분 복구)
- [#246](https://github.com/aivance-kr/AICopia/pull/246) 상품 상세 크리틱 개선 (모바일 전환·갤러리·CTA·접근성)
- [#247](https://github.com/aivance-kr/AICopia/pull/247) 장바구니 크리틱 개선 (수량 자동저장·모바일 결제·폴백·접근성)
- [#248](https://github.com/aivance-kr/AICopia/pull/248) 체크아웃 크리틱 개선 (모바일 결제·약관동의·인라인검증)
- [#249](https://github.com/aivance-kr/AICopia/pull/249) 메인화면 크리틱 개선 (히어로 중립화·시스템 정합·머천다이징)
- [#250](https://github.com/aivance-kr/AICopia/pull/250) 마이페이지 주문내역 개선 (모바일 필터·색 일관성·접근성)
- [#251](https://github.com/aivance-kr/AICopia/pull/251) 공용 토스트·확인 모달 컴포넌트 (네이티브 alert/confirm 대체)
- [#252](https://github.com/aivance-kr/AICopia/pull/252) 에셋 캐시 버스팅 + data-confirm 폼 핸들러 + 네이티브 dialog 전환(1차)
- [#253](https://github.com/aivance-kr/AICopia/pull/253) 네이티브 alert/confirm 전면 전환 (상세·체크아웃·주문상세)
- [#254](https://github.com/aivance-kr/AICopia/pull/254) 장바구니 마지막 네이티브 alert 전환 (dialog 0건 완성)
- [#255](https://github.com/aivance-kr/AICopia/pull/255) 크리틱 후속: product_card 파셜 통합 + .9rem 토큰화 + 홈 밴드 중복 제거
- [#256](https://github.com/aivance-kr/AICopia/pull/256) 주문 목록 카드에 대표 상품 썸네일 표시
- [#257](https://github.com/aivance-kr/AICopia/pull/257) 주문 목록 상태 필터를 5개 매크로 그룹으로 재편
- [#258](https://github.com/aivance-kr/AICopia/pull/258) 조건부 무료배송 기준 안내 (장바구니·체크아웃)
- [#259](https://github.com/aivance-kr/AICopia/pull/259) 체크아웃 최선 쿠폰 제안
- [#261](https://github.com/aivance-kr/AICopia/pull/261) 체크아웃 결제수단 브랜드 식별 칩
- [#263](https://github.com/aivance-kr/AICopia/pull/263) 로그인 불필요 화면에서 로그인 후 원래 페이지로 복귀
- [#264](https://github.com/aivance-kr/AICopia/pull/264) audit 후속: a11y·성능·테마 마감 (6항목)
- [#265](https://github.com/aivance-kr/AICopia/pull/265) 로그인 리다이렉트 same-origin 판정을 실제 접속 호스트 기준으로
- [#266](https://github.com/aivance-kr/AICopia/pull/266) 상품 상세 문의/리뷰 등록 후 탭 유지
- [#267](https://github.com/aivance-kr/AICopia/pull/267) audit P3 마감: SRI · LCP 우선순위 · 헤딩 계층
- [#269](https://github.com/aivance-kr/AICopia/pull/269) 로그인 리다이렉트 저장을 tempdata(TTL)에서 flashdata로 전환
- [#271](https://github.com/aivance-kr/AICopia/pull/271) 파생 테마 추가: 가을(autumn) · 겨울(winter) + 활성 테마 CSS 동적 로드
- 배포(dev→main): [#243](https://github.com/aivance-kr/AICopia/pull/243), [#260](https://github.com/aivance-kr/AICopia/pull/260), [#262](https://github.com/aivance-kr/AICopia/pull/262), [#268](https://github.com/aivance-kr/AICopia/pull/268), [#270](https://github.com/aivance-kr/AICopia/pull/270)

## 2026-08-14 (금)

- **#279 어드민 사이트 설정 개선**: 기본 탭의 로고·파비콘 입력을 미디어 라이브러리에서 선택하는 방식과 직접 업로드하는 방식 모두 지원.
- **#284 상품 등록 시 할인가 검증**: 할인가가 정상가보다 높거나 같으면 경고창을 띄우고 할인가를 비우도록 처리.

#### 머지된 PR

- [#273](https://github.com/aivance-kr/AICopia/pull/273) 로그인 폼 엔터키 제출 보강
- [#274](https://github.com/aivance-kr/AICopia/pull/274) PR #271 스쿼시 누락분 복구 (autumn 레이아웃·winter 테마 전체)
- [#276](https://github.com/aivance-kr/AICopia/pull/276) 마이페이지 주문내역 썸네일 중복 표시 수정
- [#277](https://github.com/aivance-kr/AICopia/pull/277) 프로모션 상품선택 모달 결과 메타바가 항상 노출되는 문제 수정
- [#280](https://github.com/aivance-kr/AICopia/pull/280) 스토어 홈페이지 설정 버그 수정 + UX 개선
- [#282](https://github.com/aivance-kr/AICopia/pull/282) 홈페이지 "기획전" 섹션명을 "PICK 상품"으로 변경
- [#285](https://github.com/aivance-kr/AICopia/pull/285) PICK 배지 누수 수정 + PICK 지정 시 중복노출 확인창
- [#287](https://github.com/aivance-kr/AICopia/pull/287) CI 컨테이너 정리 시 익명 볼륨까지 제거
- [#288](https://github.com/aivance-kr/AICopia/pull/288) 사이트 설정 로고/파비콘에 미디어 라이브러리 선택 + 즉시 업로드 지원
- [#289](https://github.com/aivance-kr/AICopia/pull/289) 상품 등록/수정 시 할인가가 정가 이상으로 입력되는 문제 수정
- [#291](https://github.com/aivance-kr/AICopia/pull/291) 사이트 설정에 GTM 컨테이너 ID 추가
- [#293](https://github.com/aivance-kr/AICopia/pull/293) 사이트 설정 SEO 탭에 GA4/GTM/네이버 인증 필드 설명 추가
- 배포(dev→main): [#272](https://github.com/aivance-kr/AICopia/pull/272), [#275](https://github.com/aivance-kr/AICopia/pull/275), [#278](https://github.com/aivance-kr/AICopia/pull/278), [#281](https://github.com/aivance-kr/AICopia/pull/281), [#283](https://github.com/aivance-kr/AICopia/pull/283), [#286](https://github.com/aivance-kr/AICopia/pull/286), [#290](https://github.com/aivance-kr/AICopia/pull/290), [#292](https://github.com/aivance-kr/AICopia/pull/292), [#294](https://github.com/aivance-kr/AICopia/pull/294)

## 2026-08-20 (목)

- **#296 비회원 장바구니 액션**: 비회원 장바구니에 담아도 확인할 수 없던 문제 개선. 장바구니까지는 열람 가능하게 하고 주문 시점에 로그인으로 이동.

#### 머지된 PR

- [#297](https://github.com/aivance-kr/AICopia/pull/297) 비회원 장바구니 조회 지원

## 2026-08-24 (월)

#### 머지된 PR

- [#298](https://github.com/aivance-kr/AICopia/pull/298) 관리자 좌측 메뉴 스크롤 지원

### 월간 요약
- 결제·주문 정합성 4건(#94, #113, #123, #124)과 보안 이슈 7건(#117~#122, #137) 해소.
- 추가구성상품(애드온) 기능 신규 도입 및 SKU·배송비·주문서 표시 후속 보완.
- 주문 내역 기록 정책, 쿠폰 선택 방식, 시각 기준(UTC 저장/KST 표시) 등 운영 정책 개선.
