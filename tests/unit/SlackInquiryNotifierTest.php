<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\SlackInquiryNotifier;
use PHPUnit\Framework\TestCase;

final class SlackInquiryNotifierTest extends TestCase
{
    public function testPayloadHasServicePrefixFieldsAndAdminButton(): void
    {
        $payload = (new SlackInquiryNotifier('https://hooks.example/x', 'AIDemo'))->buildPayload(
            7,
            ['이름' => '홍길동', '이메일' => 'a@b.kr', '연락처' => ''],
            "첫 줄\n둘째 줄",
            'https://demo.kr/admin/inquiries/7',
        );

        $this->assertSame('[AIDemo] 새 문의 #7', $payload['blocks'][0]['text']['text']);
        $this->assertSame('[AIDemo] 새 문의 #7 — 홍길동', $payload['text']);
        $this->assertSame("*연락처*\n-", $payload['blocks'][1]['fields'][2]['text']);
        $this->assertSame(">첫 줄\n>둘째 줄", substr($payload['blocks'][2]['text']['text'], strlen("*문의 내용*\n")));
        $this->assertSame('https://demo.kr/admin/inquiries/7', $payload['blocks'][3]['elements'][0]['url']);
    }

    public function testUserInputIsEscapedAndAdminButtonOmittedWithoutUrl(): void
    {
        $payload = (new SlackInquiryNotifier('https://hooks.example/x', 'AIDemo'))->buildPayload(
            1,
            ['이름' => '<!channel> & co'],
            '<@U123>',
        );

        $this->assertStringContainsString('&lt;!channel&gt; &amp; co', $payload['blocks'][1]['fields'][0]['text']);
        $this->assertStringContainsString('&lt;@U123&gt;', $payload['blocks'][2]['text']['text']);
        $this->assertCount(3, $payload['blocks']);
    }

    public function testBlankWebhookIsNoOp(): void
    {
        (new SlackInquiryNotifier('', 'AIDemo'))->notify(1, ['이름' => 'x'], 'm');

        $this->addToAssertionCount(1);
    }
}
