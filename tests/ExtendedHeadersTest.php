<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Message\ExtendedHeaders;

class ExtendedHeadersTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set("UTC");
    }
    /**
     * Testing wrong encoding in header
     *
     **/
    public function testParse()
    {
        $header =<<<TXT
Delivered-To: user@gmail.com
Received: by 10.42.176.5 with SMTP id bc5cs19805icb;
        Fri, 15 Jul 2011 07:58:04 -0700 (PDT)
Received: by 10.204.240.144 with SMTP id la16mr1280644bkb.271.1310741882850;
        Fri, 15 Jul 2011 07:58:02 -0700 (PDT)
Return-Path: <techsup@tourout.ru>
Received: from t.pdg.ru (static.14.80.4.46.clients.your-server.de [46.4.80.14])
        by mx.google.com with ESMTPS id c13si1665775fai.198.2011.07.15.07.58.02
        (version=TLSv1/SSLv3 cipher=OTHER);
        Fri, 15 Jul 2011 07:58:02 -0700 (PDT)
Received-SPF: pass (google.com: domain of techsup@tourout.ru designates 46.4.80.14 as permitted sender) client-ip=46.4.80.14;
Authentication-Results: mx.google.com; spf=pass (google.com: domain of techsup@tourout.ru designates 46.4.80.14 as permitted sender) smtp.mail=techsup@tourout.ru
Received: from www-data by t.pdg.ru with local (Exim 4.72)
    (envelope-from <techsup@tourout.ru>)
    id 1Qhjq6-0007D5-9q
    for Linasgreen@gmail.com; Fri, 15 Jul 2011 18:58:02 +0400
To: user2@gmail.com
Subject: ,  ����� ������ ��������� TourOut.ru � ������ ����!
X-PHP-Originating-Script: 33:Email.php
User-Agent: PDG.ru mail sender
Date: Fri, 15 Jul 2011 18:58:02 +0400
From: "������������� TourOut.ru" <techsup@tourout.ru>
Reply-To: "techsup@tourout.ru" <techsup@tourout.ru>
X-Sender: techsup@tourout.ru
X-Mailer: PDG.ru mail sender
X-Priority: 3 (Normal)
Message-ID: <4e20557a49160@tourout.ru>
Mime-Version: 1.0
Content-Type: multipart/alternative; boundary="B_ALT_4e20557a491ae"
TXT;

        $result =  ExtendedHeaders::parse($header);
        //var_export($result);
        $i = 0;
        foreach($result as $item ){
            if($item['name'] == 'Message-ID'){
                $i++;
                $this->assertEquals('<4e20557a49160@tourout.ru>',$item['value']);
            }

            if($item['name'] == 'To'){
                $i++;
                $this->assertEquals('user2@gmail.com',$item['value']);
            }
        }

        $this->assertEquals(2,$i);
    }

    /**
     * @dataProvider getReceivedHeaders
     *
     * @return void
     * @author skoryukin
     **/
    public function testReceivedParse($keys,$header)
    {
        $eh = new ExtendedHeaders('');
        $result = $this->invokeMethod($eh,'parseReceivedHeader',[$header]);

        foreach($keys as $key){
            $this->assertNotEmpty($result[$key],'Empty '.$key);
        }
    }
    public function getReceivedHeaders()
    {
        $result = [];
        $result[] =
        [['from','by','with','id','for','date'],
        "Received: from fastsrv.yandex.ru ([37.140.190.55])
            by mxback7o.mail.yandex.net with LMTP id ICkSHJ1E2Ost
            for <skoryukin@team.amocrm.com>; Wed, 26 Aug 2015 13:12:20 +0300"];
        $result[] =
        [['from','by','with','id','for'],
        'received: from mxfront1o.mail.yandex.net ([127.0.0.1])
            by mxfront1o.mail.yandex.net with lmtp id epw0kp53
            for <popsofts@ya.ru>; fri, 3 hй«ир…ш€lйоhй«ие…ш€lйцhй«иz…ш€hлt$ hй«иm…ш€hлt$hhй«и`…ш€hлі$а 2014 11:14:25 +0400'];
        $result[] =
        [['from','by','with','id','date'],
        'Received: from uspmta167011.emsmtp.com (uspmta167011.emsmtp.com [212.69.167.11])
            by mxfront1o.mail.yandex.net (nwsmtp/Yandex) with ESMTP id iIG70au6aA-EPPGs0VL;
            Fri,  3 Jan 2014 11:14:25 +0400'];
        $result[] =
        [['from','by','id','for'],
        'Received: from (10.200.200.53) by uspmta167011.emsmtp.com id hophl216nec4 for <popsofts@ya.ru>; Fri, 3 Jan 2014
        TXT'];

        return $result;
    }
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
