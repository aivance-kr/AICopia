<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Config\Services;

/**
 * 문의 접수 Slack Incoming Webhook 알림.
 *
 * 문의는 이미 DB 에 저장된 뒤에 호출한다. Slack 이 죽어 있어도 접수 자체는 성공해야 하므로
 * 실패는 로그로만 남기고 호출부에 영향을 주지 않는다. (AIVance 홈페이지와 같은 채널·같은 형식)
 */
final class SlackInquiryNotifier
{
    private const TIMEOUT         = 3;
    private const CONNECT_TIMEOUT = 2;
    private const MESSAGE_LIMIT   = 1500;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly string $service,
    ) {
    }

    /** SLACK_INQUIRY_WEBHOOK_URL 환경변수로 만든다. 비어 있으면 notify() 가 조용히 건너뛴다. */
    public static function fromEnv(string $service): self
    {
        return new self((string) env('SLACK_INQUIRY_WEBHOOK_URL', ''), $service);
    }

    /**
     * @param array<string, string> $fields 라벨 => 값
     */
    public function notify(int $inquiryId, array $fields, string $message, string $adminUrl = ''): void
    {
        if ($this->webhookUrl === '') {
            return;
        }

        try {
            // 공유 인스턴스는 이전 요청의 옵션을 들고 있을 수 있어 새로 만든다
            $response = Services::curlrequest([], null, null, false)->post($this->webhookUrl, [
                'json'            => $this->buildPayload($inquiryId, $fields, $message, $adminUrl),
                'timeout'         => self::TIMEOUT,
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'http_errors'     => false,
            ]);

            if ($response->getStatusCode() >= 300) {
                log_message('error', sprintf('[Slack] 문의 알림 실패 http=%d', $response->getStatusCode()));
            }
        } catch (\Throwable $e) {
            log_message('error', '[Slack] 문의 알림 예외: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, string> $fields
     * @return array<string, mixed>
     */
    public function buildPayload(int $inquiryId, array $fields, string $message, string $adminUrl = ''): array
    {
        $title = sprintf('[%s] 새 문의 #%d', $this->service, $inquiryId);

        $blocks = [[
            'type' => 'header',
            'text' => ['type' => 'plain_text', 'text' => $title, 'emoji' => true],
        ]];

        $fieldBlocks = [];
        foreach ($fields as $label => $value) {
            $fieldBlocks[] = [
                'type' => 'mrkdwn',
                'text' => '*' . $label . "*\n" . ($value !== '' ? self::escape($value) : '-'),
            ];
        }
        // Slack section 의 fields 는 최대 10개까지만 렌더링된다.
        foreach (array_chunk($fieldBlocks, 10) as $chunk) {
            $blocks[] = ['type' => 'section', 'fields' => $chunk];
        }

        $blocks[] = [
            'type' => 'section',
            'text' => ['type' => 'mrkdwn', 'text' => "*문의 내용*\n" . self::quote($message)],
        ];

        if ($adminUrl !== '') {
            $blocks[] = [
                'type'     => 'actions',
                'elements' => [[
                    'type'  => 'button',
                    'text'  => ['type' => 'plain_text', 'text' => '관리자 페이지에서 열기'],
                    'url'   => $adminUrl,
                    'style' => 'primary',
                ]],
            ];
        }

        return [
            'text'   => $title . ' — ' . self::escape($fields['이름'] ?? ''),
            'blocks' => $blocks,
        ];
    }

    /** Slack mrkdwn 안전 처리 + 인용 블록으로 감싸기 (길면 자른다). */
    private static function quote(string $text): string
    {
        return '>' . str_replace("\n", "\n>", self::escape(mb_substr($text, 0, self::MESSAGE_LIMIT)));
    }

    /** 사용자 입력이 Slack 링크·멘션(<...>)으로 해석되지 않게 &, <, > 를 이스케이프한다. */
    private static function escape(string $text): string
    {
        return strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;']);
    }
}
