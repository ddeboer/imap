<?php
/**
 * Created by PhpStorm.
 * User: Lukasz
 * Date: 2017-11-10
 * Time: 19:51
 */

namespace MailboxesParser;

use Ddeboer\Imap\MailboxesParser\MailboxesParser;
use Ddeboer\Imap\MailboxesParser\MailboxesTreeParser;
use Ddeboer\Imap\MailboxInterface;
use PHPUnit\Framework\TestCase;

class MailboxesTreeParserTest extends TestCase
{
    public function testTreeParser()
    {
        /** @var MailboxInterface[] $mailboxes */
        $mailboxes = [
            $this->createMailboxMock('inbox'),
            $this->createMailboxMock('inbox.first'),
            $this->createMailboxMock('inbox.second'),
            $this->createMailboxMock('inbox.second.other'),
            $this->createMailboxMock('inbox.third.another'),
        ];
        $parser = new MailboxesParser($mailboxes);
        $folders = $parser->getFolders();
        $parser = new MailboxesTreeParser();
        $zwroc = $parser->parse($folders);
        $spodziewane = [
            'inbox' => [
                'name'        => 'Inbox',
                'mailboxName' => 'inbox',
                'subfolders'  => [
                    'first'  => [
                        'name'        => 'First',
                        'mailboxName' => 'inbox.first',
                        'subfolders'  => [],
                    ],
                    'second' => [
                        'name'        => 'Second',
                        'mailboxName' => 'inbox.second',
                        'subfolders'  => [
                            'other' => [
                                'name'        => 'Other',
                                'subfolders'  => [],
                                'mailboxName' => 'inbox.second.other',
                            ],
                        ],
                    ],
                    'third'  => [
                        'subfolders' => [
                            'another' => [
                                'name'        => 'Another',
                                'subfolders'  => [],
                                'mailboxName' => 'inbox.third.another',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals($spodziewane, $zwroc);
    }

//    public function testTreeParserWithDashDelimiter()
//    {
//        $dane = [
//            'inbox'        => ['name' => 'Inbox'],
//            'inbox|first'  => ['name' => 'First'],
//            'inbox|second' => ['name' => 'Second'],
//        ];
//        $parser = new MailboxesTreeParser();
//        $zwroc = $parser->parse($dane, '|');
//        $spodziewane = [
//            'inbox' => [
//                'name'        => 'Inbox',
//                'mailboxName' => 'inbox',
//                'subfolders'  => [
//                    'first'  => [
//                        'name'        => 'First',
//                        'mailboxName' => 'inbox|first',
//                        'subfolders'  => [],
//                    ],
//                    'second' => [
//                        'name'        => 'Second',
//                        'mailboxName' => 'inbox|second',
//                        'subfolders'  => [],
//                    ],
//                ],
//            ],
//        ];
//        $this->assertEquals($spodziewane, $zwroc);
//    }

    private function createMailboxMock($mailboxName)
    {
        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->method('getName')
            ->willReturn($mailboxName);

        $mailbox->method('getDelimiter')
            ->willReturn('.');

        return $mailbox;
    }
}
